/**
 * FSWWP Comments Core — Form Builder JS
 *
 * Multi-form drag-and-drop builder using jQuery UI Sortable.
 * Field editing via modal instead of inline expand.
 */
( function( $ ) {
	'use strict';

	var $sortable  = $( '#fswwp-cc-form-fields' );
	var $empty     = $( '#fswwp-cc-builder-empty' );
	var $msg       = $( '#fswwp-cc-builder-message' );
	var $nameInput = $( '#fswwp-cc-form-name' );
	var $formId    = $( '#fswwp-cc-form-id' );

	// Modal elements.
	var $modal       = $( '#fswwp-cc-field-modal' );
	var $modalId     = $( '#fswwp-cc-modal-field-id' );
	var $modalType   = $( '#fswwp-cc-modal-field-type' );
	var $modalLabel  = $( '#fswwp-cc-modal-field-label' );
	var $modalPlh    = $( '#fswwp-cc-modal-field-placeholder' );
	var $modalOpts   = $( '#fswwp-cc-modal-field-options' );
	var $modalOptsRow = $modal.find( '.fswwp-cc-modal-options-row' );
	var $modalReq    = $( '#fswwp-cc-modal-field-required' );
	var currentFieldCard = null;

	var fieldIndex = $sortable.find( '.fswwp-cc-field-card' ).length;

	// Type defaults.
	var typeDefaults = {
		image:    { label: 'Image',       placeholder: '' },
		text:     { label: 'Text',        placeholder: 'Insert your text' },
		textarea: { label: 'Description', placeholder: 'Insert your description' },
		email:    { label: 'Email',       placeholder: 'Insert your email' },
		url:      { label: 'URL',         placeholder: 'https://' },
		date:     { label: 'Date',        placeholder: '' },
		select:   { label: 'Select',      placeholder: '' },
		rating:   { label: 'Rating',      placeholder: '' }
	};

	var typeLabels = { image:'Image', text:'Text', textarea:'Textarea', email:'Email', url:'URL', date:'Date', select:'Select', rating:'Rating' };
	var typeIcons  = { image:'dashicons-format-image', text:'dashicons-editor-textcolor', textarea:'dashicons-editor-paragraph', email:'dashicons-email', url:'dashicons-admin-links', date:'dashicons-calendar', select:'dashicons-menu-alt', rating:'dashicons-star-filled' };

	// ---- Sortable init ----------------------------------------------------
	$sortable.sortable( {
		handle: '.fswwp-cc-field-handle',
		placeholder: 'fswwp-cc-field-placeholder',
		forcePlaceholderSize: true,
		opacity: 0.8,
		tolerance: 'pointer'
	} );

	// Palette draggable.
	$( '.fswwp-cc-palette-item' ).draggable( {
		connectToSortable: '#fswwp-cc-form-fields',
		helper: 'clone',
		revert: 'invalid',
		zIndex: 1000
	} );

	// Drop palette item → create card.
	$sortable.on( 'sortreceive', function( e, ui ) {
		if ( ui.item.hasClass( 'fswwp-cc-palette-item' ) ) {
			var type = ui.item.data( 'type' );
			var def  = typeDefaults[ type ] || { label: typeLabels[type] || type, placeholder: '' };
			var id   = 'fld_' + ( ++fieldIndex ) + '_' + Date.now();
			ui.item.replaceWith( $( buildCard( id, type, def.label, def.placeholder, '', false ) ) );
			$sortable.sortable( 'refresh' );
			$empty.hide();
		}
	} );

	// ---- Delete field -----------------------------------------------------
	$sortable.on( 'click', '.fswwp-cc-field-delete', function() {
		$( this ).closest( '.fswwp-cc-field-card' ).slideUp( 150, function() {
			$( this ).remove();
			if ( ! $sortable.find( '.fswwp-cc-field-card' ).length ) {
				$empty.show();
			}
		} );
	} );

	// ---- Open modal on card header click (except handle, edit, delete) -----
	$sortable.on( 'click', '.fswwp-cc-field-header', function( e ) {
		// Ignore clicks on the handle, edit button, or delete button.
		if ( $( e.target ).closest( '.fswwp-cc-field-handle, .fswwp-cc-field-edit, .fswwp-cc-field-delete' ).length ) {
			return;
		}
		openModal( $( this ).closest( '.fswwp-cc-field-card' ) );
	} );

	// Also open via edit button.
	$sortable.on( 'click', '.fswwp-cc-field-edit', function() {
		openModal( $( this ).closest( '.fswwp-cc-field-card' ) );
	} );

	// ---- Modal: type change → update defaults -----------------------------
	$modalType.on( 'change', function() {
		var type = $( this ).val();
		var def  = typeDefaults[ type ] || {};
		// Only update label/placeholder if they look "default-ish" (empty or matching a previous default).
		$modalLabel.val( def.label || '' );
		$modalPlh.val( def.placeholder || '' );
		$modalOptsRow.toggle( type === 'select' );
	} );

	// ---- Modal: save -----------------------------------------------------
	$( '#fswwp-cc-modal-save' ).on( 'click', function() {
		if ( ! currentFieldCard ) { return; }

		var id    = $modalId.val().trim();
		var type  = $modalType.val();
		var label = $modalLabel.val().trim();
		var plh   = $modalPlh.val().trim();
		var opts  = $modalOpts.val().trim();
		var req   = $modalReq.is( ':checked' );

		if ( ! id ) {
			alert( 'Field ID is required.' );
			return;
		}
		if ( ! label ) {
			alert( 'Label is required.' );
			return;
		}

		// Rebuild the card.
		var newCard = buildCard( id, type, label, plh, opts, req );
		currentFieldCard.replaceWith( $( newCard ) );
		$sortable.sortable( 'refresh' );

		closeModal();
	} );

	// ---- Modal: cancel / close --------------------------------------------
	$( '#fswwp-cc-modal-cancel, .fswwp-cc-modal-close, .fswwp-cc-modal-backdrop' ).on( 'click', function() {
		closeModal();
	} );

	// ---- Modal: keyboard escape -------------------------------------------
	$( document ).on( 'keydown', function( e ) {
		if ( e.key === 'Escape' && $modal.is( ':visible' ) ) {
			closeModal();
		}
	} );

	function openModal( $card ) {
		currentFieldCard = $card;

		var id    = $card.data( 'id' );
		var type  = $card.find( '.fswwp-cc-field-type' ).val();
		var label = $card.find( '.fswwp-cc-field-label' ).val();
		var plh   = $card.find( '.fswwp-cc-field-placeholder' ).val();
		var opts  = $card.find( '.fswwp-cc-field-options' ).val();
		var req   = $card.find( '.fswwp-cc-field-required' ).is( ':checked' );

		$modalId.val( id );
		$modalType.val( type );
		$modalLabel.val( label );
		$modalPlh.val( plh );
		$modalOpts.val( opts || '' );
		$modalOptsRow.toggle( type === 'select' );
		$modalReq.prop( 'checked', req );

		$modal.fadeIn( 200 );
		$modalId.focus();
	}

	function closeModal() {
		$modal.fadeOut( 150 );
		currentFieldCard = null;
	}

	// ---- Save -------------------------------------------------------------
	$( '#fswwp-cc-save-form' ).on( 'click', function() {
		var $btn   = $( this );
		var fields = [];
		var valid  = true;
		var name   = $nameInput.val().trim();

		$sortable.find( '.fswwp-cc-field-card' ).each( function() {
			var $card = $( this );
			var label = $card.find( '.fswwp-cc-field-label' ).val().trim();
			if ( ! label ) {
				valid = false;
				$card.css( 'border-color', '#d63638' );
			} else {
				$card.css( 'border-color', '' );
			}
			fields.push( {
				id: $card.data( 'id' ),
				type: $card.find( '.fswwp-cc-field-type' ).val(),
				label: label,
				placeholder: $card.find( '.fswwp-cc-field-placeholder' ).val().trim(),
				required: $card.find( '.fswwp-cc-field-required' ).is( ':checked' ),
				options: $card.find( '.fswwp-cc-field-options' ).val().trim()
			} );
		} );

		if ( ! valid ) {
			showMsg( 'Please fill in all field labels.', 'error' );
			return;
		}

		$btn.prop( 'disabled', true ).text( 'Saving…' );

		$.post( window.ajaxurl, {
			action:    'fswwp_cc_save_form',
			nonce:     $sortable.data( 'nonce' ),
			form_name: name,
			form_id:   $formId.val(),
			fields:    JSON.stringify( fields )
		}, function( r ) {
			if ( r.success ) {
				$formId.val( r.data.form_id );
				showMsg( r.data.message, 'success' );
				// Navigate to edit mode for the saved form.
				setTimeout( function() {
					window.location.search = '?page=fswwp-comments-core-builder&post_type=fswwp_review&form_id=' + r.data.form_id;
				}, 800 );
			} else {
				showMsg( r.data.message, 'error' );
			}
			$btn.prop( 'disabled', false ).text( 'Save Form' );
		}, 'json' ).fail( function() {
			showMsg( 'Network error.', 'error' );
			$btn.prop( 'disabled', false ).text( 'Save Form' );
		} );
	} );

	// ---- Clear & New -------------------------------------------------------
	$( '#fswwp-cc-new-form' ).on( 'click', function() {
		window.location.search = '?page=fswwp-comments-core-builder&post_type=fswwp_review&new=1';
	} );

	// ---- Delete form (on list page) ----------------------------------------
	$( '.fswwp-cc-delete-form' ).on( 'click', function() {
		if ( ! confirm( 'Delete this form?' ) ) { return; }
		var $btn = $( this );
		var id   = $btn.data( 'id' );

		$.post( window.ajaxurl, {
			action:  'fswwp_cc_delete_form',
			nonce:   $sortable.length ? $sortable.data( 'nonce' ) : '',
			form_id: id
		}, function() {
			window.location.reload();
		} );
	} );

	// ---- Copy shortcode ----------------------------------------------------
	// List page copy buttons.
	$( '.fswwp-cc-copy-btn' ).on( 'click', function() {
		var code = $( this ).data( 'code' );
		copyToClipboard( code, $( this ) );
	} );

	// Click-to-copy on shortcode text.
	$( '.fswwp-cc-shortcode-copy' ).on( 'click', function() {
		var code = $( this ).text();
		copyToClipboard( code, $( this ) );
	} );

	function copyToClipboard( text, $btn ) {
		navigator.clipboard.writeText( text ).then( function() {
			if ( $btn.length ) {
				var orig = $btn.find( '.dashicons' ).length ? '' : $btn.text();
				if ( $btn.find( '.dashicons' ).length ) {
					$btn.find( '.dashicons' ).removeClass( 'dashicons-clipboard' ).addClass( 'dashicons-yes' );
					setTimeout( function() {
						$btn.find( '.dashicons' ).removeClass( 'dashicons-yes' ).addClass( 'dashicons-clipboard' );
					}, 1500 );
				}
			}
		} ).catch( function() {
			// Fallback: select text for manual copy.
			var $tmp = $( '<textarea>' ).val( text ).appendTo( 'body' ).select();
			document.execCommand( 'copy' );
			$tmp.remove();
		} );
	}

	// ---- Helpers -----------------------------------------------------------
	function showMsg( text, type ) {
		$msg.removeClass( 'notice-success notice-error' )
			.addClass( 'notice notice-' + type + ' notice-alt' )
			.html( '<p>' + $( '<span>' ).text( text ).html() + '</p>' )
			.show().delay( 3000 ).fadeOut( 400 );
	}

	function buildCard( id, type, label, placeholder, options, required ) {
		var icon = typeIcons[ type ] || 'dashicons-edit';
		var badge = typeLabels[ type ] || type;

		var optsRow = type === 'select'
			? '<label>Options</label><textarea class="fswwp-cc-field-options" rows="3" placeholder="One per line">' + ( options || '' ) + '</textarea><p class="description">One option per line.</p>'
			: '';

		return '<div class="fswwp-cc-field-card" data-id="' + id + '">' +
			'<div class="fswwp-cc-field-header">' +
			'<span class="dashicons dashicons-move fswwp-cc-field-handle"></span>' +
			'<span class="dashicons ' + icon + ' fswwp-cc-field-icon"></span>' +
			'<strong class="fswwp-cc-field-label-text">' + label + '</strong>' +
			'<span class="fswwp-cc-field-type-badge">' + badge + '</span>' +
			'<button type="button" class="fswwp-cc-field-edit dashicons dashicons-edit" title="Edit field"></button>' +
			'<button type="button" class="fswwp-cc-field-delete dashicons dashicons-trash"></button>' +
			'</div>' +
			'<div class="fswwp-cc-field-body" style="display:none;">' +
			'<input type="hidden" class="fswwp-cc-field-id-val" value="' + id + '">' +
			'<select class="fswwp-cc-field-type" style="display:none;">' +
			'<option value="image"' + ( type==='image'?' selected':'') + '>Image</option>' +
			'<option value="text"' + ( type==='text'?' selected':'') + '>Text</option>' +
			'<option value="textarea"' + ( type==='textarea'?' selected':'') + '>Textarea</option>' +
			'<option value="email"' + ( type==='email'?' selected':'') + '>Email</option>' +
			'<option value="url"' + ( type==='url'?' selected':'') + '>URL</option>' +
			'<option value="date"' + ( type==='date'?' selected':'') + '>Date</option>' +
			'<option value="select"' + ( type==='select'?' selected':'') + '>Select</option>' +
			'<option value="rating"' + ( type==='rating'?' selected':'') + '>Rating</option>' +
			'</select>' +
			'<input type="hidden" class="fswwp-cc-field-label" value="' + label + '">' +
			'<input type="hidden" class="fswwp-cc-field-placeholder" value="' + ( placeholder || '' ) + '">' +
			( type === 'select'
				? '<textarea class="fswwp-cc-field-options" style="display:none;">' + ( options || '' ) + '</textarea>'
				: '<input type="hidden" class="fswwp-cc-field-options" value="">' ) +
			'<input type="hidden" class="fswwp-cc-field-required" value="' + ( required ? '1' : '' ) + '">' +
			'</div></div>';
	}

} )( jQuery );
