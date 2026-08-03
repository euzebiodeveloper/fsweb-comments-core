/**
 * FSWWP Comments Core — Form Builder JS
 *
 * Multi-form drag-and-drop builder using jQuery UI Sortable.
 * Field editing via modal instead of inline expand.
 * Multi-step support.
 */
( function( $ ) {
	'use strict';

	var $sortable     = $( '#fswwp-cc-form-fields' );
	var $stepsWrap    = $( '#fswwp-cc-steps-container' );
	var $addStepBtn   = $( '#fswwp-cc-add-step' );
	var $multiToggle  = $( '#fswwp-cc-multi-step' );
	var $empty        = $( '#fswwp-cc-builder-empty' );
	var $msg          = $( '#fswwp-cc-builder-message' );
	var $nameInput    = $( '#fswwp-cc-form-name' );
	var $formId       = $( '#fswwp-cc-form-id' );

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
	var stepIndex  = $stepsWrap.find( '.fswwp-cc-step' ).length;

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

	// ======================================================================
	//  MULTI-STEP TOGGLE
	// ======================================================================

	function isMultiStep() {
		return $multiToggle.is( ':checked' );
	}

	function getActiveSortables() {
		return isMultiStep()
			? $stepsWrap.find( '.fswwp-cc-step-fields' )
			: $sortable;
	}

	function reindexBadges() {
		$stepsWrap.find( '.fswwp-cc-step' ).each( function( i ) {
			$( this ).find( '.fswwp-cc-step-badge' ).text( i + 1 );
		} );
	}

	function toggleEmptyState() {
		if ( isMultiStep() ) {
			$empty.hide();
		} else {
			var count = $sortable.find( '.fswwp-cc-field-card' ).length;
			$empty.toggle( count === 0 );
		}
	}

	$multiToggle.on( 'change', function() {
		if ( isMultiStep() ) {
			// Switch TO multi-step: wrap flat fields into a single step.
			var $fields = $sortable.find( '.fswwp-cc-field-card' );
			$sortable.hide();
			$stepsWrap.show();

			// Clear existing steps and create one with current fields.
			$stepsWrap.find( '.fswwp-cc-step' ).remove();
			var $step = $( buildStepHtml( 'step_1', 'Step 1' ) );
			$step.insertBefore( $addStepBtn );
			var $stepFields = $step.find( '.fswwp-cc-step-fields' );
			if ( $fields.length ) {
				$stepFields.append( $fields );
			}
			stepIndex = 1;
			initStepSortable( $stepFields );
			reindexBadges();
			toggleEmptyState();
		} else {
			// Switch FROM multi-step: flatten all step fields into flat sortable.
			var $allFields = $stepsWrap.find( '.fswwp-cc-field-card' );
			$stepsWrap.hide();
			$sortable.show();
			if ( $allFields.length ) {
				$sortable.append( $allFields );
			}
			initSortable();
			fieldIndex = $sortable.find( '.fswwp-cc-field-card' ).length;
			toggleEmptyState();
		}
	} );

	// ======================================================================
	//  STEP MANAGEMENT
	// ======================================================================

	function buildStepHtml( id, label ) {
		return '<div class="fswwp-cc-step" data-step-id="' + id + '">' +
			'<div class="fswwp-cc-step-header">' +
			'<span class="dashicons dashicons-move fswwp-cc-step-handle"></span>' +
			'<input type="text" class="fswwp-cc-step-label" value="' + label + '" placeholder="Step name">' +
			'<span class="fswwp-cc-step-badge">-</span>' +
			'<button type="button" class="fswwp-cc-step-delete dashicons dashicons-trash" title="Delete step"></button>' +
			'</div>' +
			'<div class="fswwp-cc-step-fields fswwp-cc-fields-sortable" data-step="' + id + '"></div>' +
			'</div>';
	}

	$addStepBtn.on( 'click', function() {
		stepIndex++;
		var id = 'step_' + stepIndex + '_' + Date.now();
		var label = 'Step ' + stepIndex;
		var $step = $( buildStepHtml( id, label ) );
		$step.insertBefore( $addStepBtn );
		initStepSortable( $step.find( '.fswwp-cc-step-fields' ) );
		reindexBadges();
		refreshStepsSortable();
	} );

	$stepsWrap.on( 'click', '.fswwp-cc-step-delete', function() {
		var $step = $( this ).closest( '.fswwp-cc-step' );
		var count = $stepsWrap.find( '.fswwp-cc-step' ).length;
		if ( count <= 1 ) { return; } // Keep at least one step.
		$step.slideUp( 150, function() {
			$( this ).remove();
			reindexBadges();
			refreshStepsSortable();
		} );
	} );

	// ======================================================================
	//  SORTABLES — steps and fields
	// ======================================================================

	function initStepSortable( $el ) {
		$el.sortable( {
			handle: '.fswwp-cc-field-handle',
			placeholder: 'fswwp-cc-field-placeholder',
			forcePlaceholderSize: true,
			opacity: 0.8,
			tolerance: 'pointer',
			connectWith: '.fswwp-cc-step-fields'
		} );
	}

	function initSortable() {
		$sortable.sortable( {
			handle: '.fswwp-cc-field-handle',
			placeholder: 'fswwp-cc-field-placeholder',
			forcePlaceholderSize: true,
			opacity: 0.8,
			tolerance: 'pointer'
		} );
	}

	function refreshStepsSortable() {
		// Make step containers sortable among themselves.
		if ( $stepsWrap.data( 'ui-sortable' ) ) {
			$stepsWrap.sortable( 'destroy' );
		}
		$stepsWrap.sortable( {
			handle: '.fswwp-cc-step-handle',
			placeholder: 'fswwp-cc-step-placeholder',
			forcePlaceholderSize: true,
			opacity: 0.8,
			tolerance: 'pointer',
			items: '.fswwp-cc-step',
			update: reindexBadges
		} );
		// Also refresh step field sortables.
		$stepsWrap.find( '.fswwp-cc-step-fields' ).each( function() {
			if ( ! $( this ).data( 'ui-sortable' ) ) {
				initStepSortable( $( this ) );
			} else {
				$( this ).sortable( 'refresh' );
			}
		} );
	}

	// Init flat sortable initially.
	initSortable();

	// Init step sortables + steps sortable if multi-step is on.
	if ( isMultiStep() ) {
		$stepsWrap.find( '.fswwp-cc-step-fields' ).each( function() {
			initStepSortable( $( this ) );
		} );
		refreshStepsSortable();
	}

	// ======================================================================
	//  PALETTE — drag into active canvas
	// ======================================================================

	$( '.fswwp-cc-palette-item' ).draggable( {
		connectToSortable: isMultiStep() ? '.fswwp-cc-step-fields' : '#fswwp-cc-form-fields',
		helper: 'clone',
		revert: 'invalid',
		zIndex: 1000
	} );

	// Re-bind draggable connect when toggling.
	$multiToggle.on( 'change', function() {
		$( '.fswwp-cc-palette-item' ).draggable( 'option', 'connectToSortable',
			isMultiStep() ? '.fswwp-cc-step-fields' : '#fswwp-cc-form-fields' );
	} );

	// Drop palette item → create card.
	$( document ).on( 'sortreceive', '.fswwp-cc-fields-sortable', function( e, ui ) {
		if ( ui.item.hasClass( 'fswwp-cc-palette-item' ) ) {
			var type = ui.item.data( 'type' );
			var def  = typeDefaults[ type ] || { label: typeLabels[type] || type, placeholder: '' };
			var id   = 'fld_' + ( ++fieldIndex ) + '_' + Date.now();
			ui.item.replaceWith( $( buildCard( id, type, def.label, def.placeholder, '', false ) ) );
			var $target = $( this );
			if ( $target.is( '.fswwp-cc-step-fields' ) ) {
				$target.sortable( 'refresh' );
			} else {
				$sortable.sortable( 'refresh' );
			}
			toggleEmptyState();
		}
	} );

	// ======================================================================
	//  DELETE FIELD
	// ======================================================================

	$( document ).on( 'click', '.fswwp-cc-field-delete', function() {
		var $card = $( this ).closest( '.fswwp-cc-field-card' );
		var $container = $card.closest( '.fswwp-cc-fields-sortable' );
		$card.slideUp( 150, function() {
			$( this ).remove();
			toggleEmptyState();
			if ( $container.is( '.fswwp-cc-step-fields' ) ) {
				$container.sortable( 'refresh' );
			} else if ( $container.is( '#fswwp-cc-form-fields' ) ) {
				$sortable.sortable( 'refresh' );
			}
		} );
	} );

	// ======================================================================
	//  OPEN MODAL
	// ======================================================================

	$( document ).on( 'click', '.fswwp-cc-field-header', function( e ) {
		if ( $( e.target ).closest( '.fswwp-cc-field-handle, .fswwp-cc-field-edit, .fswwp-cc-field-delete' ).length ) {
			return;
		}
		openModal( $( this ).closest( '.fswwp-cc-field-card' ) );
	} );

	$( document ).on( 'click', '.fswwp-cc-field-edit', function() {
		openModal( $( this ).closest( '.fswwp-cc-field-card' ) );
	} );

	// ======================================================================
	//  MODAL: TYPE CHANGE
	// ======================================================================

	$modalType.on( 'change', function() {
		var type = $( this ).val();
		var def  = typeDefaults[ type ] || {};
		$modalLabel.val( def.label || '' );
		$modalPlh.val( def.placeholder || '' );
		$modalOptsRow.toggle( type === 'select' );
	} );

	// ======================================================================
	//  MODAL: SAVE
	// ======================================================================

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

		var newCard = buildCard( id, type, label, plh, opts, req );
		currentFieldCard.replaceWith( $( newCard ) );

		var $container = currentFieldCard.closest( '.fswwp-cc-fields-sortable' );
		if ( $container.is( '.fswwp-cc-step-fields' ) ) {
			$container.sortable( 'refresh' );
		} else {
			$sortable.sortable( 'refresh' );
		}

		closeModal();
	} );

	// ======================================================================
	//  MODAL: CANCEL / CLOSE
	// ======================================================================

	$( '#fswwp-cc-modal-cancel, .fswwp-cc-modal-close, .fswwp-cc-modal-backdrop' ).on( 'click', function() {
		closeModal();
	} );

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

	// ======================================================================
	//  SAVE FORM
	// ======================================================================

	$( '#fswwp-cc-save-form' ).on( 'click', function() {
		var $btn   = $( this );
		var name   = $nameInput.val().trim();
		var valid  = true;
		var multi  = isMultiStep();

		var payload = {
			action:     'fswwp_cc_save_form',
			nonce:      $sortable.data( 'nonce' ),
			form_name:  name,
			form_id:    $formId.val(),
			multi_step: multi ? '1' : '0'
		};

		if ( multi ) {
			// Collect steps.
			var steps = [];
			$stepsWrap.find( '.fswwp-cc-step' ).each( function() {
				var $step   = $( this );
				var stepId  = $step.data( 'step-id' );
				var stepLbl = $step.find( '.fswwp-cc-step-label' ).val().trim() || 'Step';
				var fields  = [];
				$step.find( '.fswwp-cc-field-card' ).each( function() {
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
				steps.push( { id: stepId, label: stepLbl, fields: fields } );
			} );
			payload.steps  = JSON.stringify( steps );
			payload.fields = '[]'; // not used in multi mode, but sent.
		} else {
			// Collect flat fields.
			var fields = [];
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
			payload.fields = JSON.stringify( fields );
		}

		if ( ! valid ) {
			showMsg( 'Please fill in all field labels.', 'error' );
			return;
		}

		$btn.prop( 'disabled', true ).text( 'Saving…' );

		$.post( window.ajaxurl, payload, function( r ) {
			if ( r.success ) {
				$formId.val( r.data.form_id );
				showMsg( r.data.message, 'success' );
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

	// ======================================================================
	//  CLEAR & NEW
	// ======================================================================

	$( '#fswwp-cc-new-form' ).on( 'click', function() {
		window.location.search = '?page=fswwp-comments-core-builder&post_type=fswwp_review&new=1';
	} );

	// ======================================================================
	//  DELETE FORM (list page)
	// ======================================================================

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

	// ======================================================================
	//  COPY SHORTCODE
	// ======================================================================

	$( '.fswwp-cc-copy-btn' ).on( 'click', function() {
		var code = $( this ).data( 'code' );
		copyToClipboard( code, $( this ) );
	} );

	$( '.fswwp-cc-shortcode-copy' ).on( 'click', function() {
		var code = $( this ).text();
		copyToClipboard( code, $( this ) );
	} );

	function copyToClipboard( text, $btn ) {
		navigator.clipboard.writeText( text ).then( function() {
			if ( $btn.length ) {
				if ( $btn.find( '.dashicons' ).length ) {
					$btn.find( '.dashicons' ).removeClass( 'dashicons-clipboard' ).addClass( 'dashicons-yes' );
					setTimeout( function() {
						$btn.find( '.dashicons' ).removeClass( 'dashicons-yes' ).addClass( 'dashicons-clipboard' );
					}, 1500 );
				}
			}
		} ).catch( function() {
			var $tmp = $( '<textarea>' ).val( text ).appendTo( 'body' ).select();
			document.execCommand( 'copy' );
			$tmp.remove();
		} );
	}

	// ======================================================================
	//  HELPERS
	// ======================================================================

	function showMsg( text, type ) {
		$msg.removeClass( 'notice-success notice-error' )
			.addClass( 'notice notice-' + type + ' notice-alt' )
			.html( '<p>' + $( '<span>' ).text( text ).html() + '</p>' )
			.show().delay( 3000 ).fadeOut( 400 );
	}

	function buildCard( id, type, label, placeholder, options, required ) {
		var icon = typeIcons[ type ] || 'dashicons-edit';
		var badge = typeLabels[ type ] || type;

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
