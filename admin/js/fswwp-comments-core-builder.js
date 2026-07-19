/**
 * FSWWP Comments Core — Form Builder JS
 *
 * Multi-form drag-and-drop builder using jQuery UI Sortable.
 */
( function( $ ) {
	'use strict';

	var $sortable  = $( '#fswwp-cc-form-fields' );
	var $empty     = $( '#fswwp-cc-builder-empty' );
	var $msg       = $( '#fswwp-cc-builder-message' );
	var $nameInput = $( '#fswwp-cc-form-name' );
	var $formId    = $( '#fswwp-cc-form-id' );
	var $codeOut   = $( '#fswwp-cc-shortcode-output' );
	var $codeText  = $( '#fswwp-cc-shortcode-text' );
	var fieldIndex = $sortable.find( '.fswwp-cc-field-card' ).length;

	// Sortable init.
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
			var labels = { text:'Text', textarea:'Textarea', email:'Email', url:'URL', select:'Select', rating:'Rating' };
			var id = 'fld_' + ( ++fieldIndex ) + '_' + Date.now();
			ui.item.replaceWith( $( buildCard( id, type, labels[type] ) ) );
			$sortable.sortable( 'refresh' );
			$empty.hide();
		}
	} );

	// Toggle field body.
	$sortable.on( 'click', '.fswwp-cc-field-toggle', function() {
		$( this ).closest( '.fswwp-cc-field-card' ).find( '.fswwp-cc-field-body' ).slideToggle( 150 );
		$( this ).toggleClass( 'dashicons-arrow-down-alt2 dashicons-arrow-up-alt2' );
	} );

	// Delete field.
	$sortable.on( 'click', '.fswwp-cc-field-delete', function() {
		$( this ).closest( '.fswwp-cc-field-card' ).slideUp( 150, function() {
			$( this ).remove();
			if ( ! $sortable.find( '.fswwp-cc-field-card' ).length ) {
				$empty.show();
			}
		} );
	} );

	// Type change → badge + options toggle.
	$sortable.on( 'change', '.fswwp-cc-field-type', function() {
		var $card = $( this ).closest( '.fswwp-cc-field-card' );
		var type  = $( this ).val();
		var labels = { text:'Text', textarea:'Textarea', email:'Email', url:'URL', select:'Select', rating:'Rating' };
		$card.find( '.fswwp-cc-field-type-badge' ).text( labels[type] || type );
		$card.find( '.fswwp-cc-field-options-row' ).toggle( type === 'select' );
	} );

	// Label input → header text.
	$sortable.on( 'input', '.fswwp-cc-field-label', function() {
		$( this ).closest( '.fswwp-cc-field-card' ).find( '.fswwp-cc-field-label-text' ).text( $( this ).val() );
	} );

	// ------------------------------------------------------------------//
	//  Save
	// ------------------------------------------------------------------//
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
				$codeText.text( r.data.shortcode );
				$codeOut.show();
				showMsg( r.data.message, 'success' );
				// Refresh page to show in list.
				setTimeout( function() { window.location.search = '?page=fswwp-comments-core-builder&post_type=fswwp_review&form_id=' + r.data.form_id; }, 1000 );
			} else {
				showMsg( r.data.message, 'error' );
			}
			$btn.prop( 'disabled', false ).text( 'Save Form' );
		}, 'json' ).fail( function() {
			showMsg( 'Network error.', 'error' );
			$btn.prop( 'disabled', false ).text( 'Save Form' );
		} );
	} );

	// ------------------------------------------------------------------//
	//  New form
	// ------------------------------------------------------------------//
	$( '#fswwp-cc-new-form' ).on( 'click', function() {
		$formId.val( '' );
		$nameInput.val( '' ).focus();
		$codeOut.hide();
		$sortable.empty();
		$empty.show();
		fieldIndex = 0;
	} );

	// ------------------------------------------------------------------//
	//  Delete form
	// ------------------------------------------------------------------//
	$( '.fswwp-cc-delete-form' ).on( 'click', function() {
		if ( ! confirm( 'Delete this form?' ) ) { return; }
		var $btn = $( this );
		var id   = $btn.data( 'id' );

		$.post( window.ajaxurl, {
			action:  'fswwp_cc_delete_form',
			nonce:   $sortable.data( 'nonce' ),
			form_id: id
		}, function() {
			window.location.reload();
		} );
	} );

	// ------------------------------------------------------------------//
	//  Copy shortcode to clipboard
	// ------------------------------------------------------------------//
	$( '.fswwp-cc-shortcode-copy' ).on( 'click', function() {
		var code = $( this ).text();
		navigator.clipboard.writeText( code ).then( function() {
			/* tiny feedback */;
		} );
	} );

	// ------------------------------------------------------------------//
	//  Helpers
	// ------------------------------------------------------------------//
	function showMsg( text, type ) {
		$msg.removeClass( 'notice-success notice-error' )
			.addClass( 'notice notice-' + type + ' notice-alt' )
			.html( '<p>' + $( '<span>' ).text( text ).html() + '</p>' )
			.show().delay( 3000 ).fadeOut( 400 );
	}

	function buildCard( id, type, typeLabel ) {
		var opts = type === 'select'
			? '<div class="fswwp-cc-field-row fswwp-cc-field-options-row"><label>Options</label><textarea class="fswwp-cc-field-options" rows="3" placeholder="One per line"></textarea><p class="description">One option per line.</p></div>'
			: '<div class="fswwp-cc-field-row fswwp-cc-field-options-row" style="display:none;"><label>Options</label><textarea class="fswwp-cc-field-options" rows="3" placeholder="One per line"></textarea><p class="description">One option per line.</p></div>';

		return '<div class="fswwp-cc-field-card" data-id="' + id + '">' +
			'<div class="fswwp-cc-field-header">' +
			'<span class="dashicons dashicons-move fswwp-cc-field-handle"></span>' +
			'<strong class="fswwp-cc-field-label-text">' + typeLabel + '</strong>' +
			'<span class="fswwp-cc-field-type-badge">' + typeLabel + '</span>' +
			'<button type="button" class="fswwp-cc-field-toggle dashicons dashicons-arrow-down-alt2"></button>' +
			'<button type="button" class="fswwp-cc-field-delete dashicons dashicons-trash"></button>' +
			'</div>' +
			'<div class="fswwp-cc-field-body" style="display:none;">' +
			'<div class="fswwp-cc-field-row"><label>Type</label><select class="fswwp-cc-field-type">' +
			'<option value="text"' + ( type==='text'?' selected':'') + '>Text</option>' +
			'<option value="textarea"' + ( type==='textarea'?' selected':'') + '>Textarea</option>' +
			'<option value="email"' + ( type==='email'?' selected':'') + '>Email</option>' +
			'<option value="url"' + ( type==='url'?' selected':'') + '>URL</option>' +
			'<option value="select"' + ( type==='select'?' selected':'') + '>Select</option>' +
			'<option value="rating"' + ( type==='rating'?' selected':'') + '>Rating</option>' +
			'</select></div>' +
			'<div class="fswwp-cc-field-row"><label>Label</label><input type="text" class="fswwp-cc-field-label" value="' + typeLabel + '"></div>' +
			'<div class="fswwp-cc-field-row"><label>Placeholder</label><input type="text" class="fswwp-cc-field-placeholder" value=""></div>' +
			opts +
			'<div class="fswwp-cc-field-row"><label><input type="checkbox" class="fswwp-cc-field-required"> Required</label></div>' +
			'</div></div>';
	}

} )( jQuery );
