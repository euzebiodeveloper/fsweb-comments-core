/**
 * FSWWP Comments Core — Admin Scripts
 *
 * License activation/deactivation UI. AJAX calls are routed to
 * fswwp-license-checker (the license backend), which handles the
 * actual LMFWC communication.
 */
( function( $, data ) {
	'use strict';

	var $message   = $( '#fswwp-cc-license-message' );
	var $status    = $( '#fswwp-cc-license-status' );
	var $expires   = $( '#fswwp-cc-license-expires' );
	var $expRow    = $( '#fswwp-cc-expires-row' );
	var $keyInput  = $( '#fswwp-cc-license-key' );
	var $btnActivate   = $( '#fswwp-cc-activate-license' );
	var $btnDeactivate = $( '#fswwp-cc-deactivate-license' );
	var $btnValidate   = $( '#fswwp-cc-validate-license' );
	var $spinner       = $( '#fswwp-cc-validate-spinner' );

	function showMessage( text, type ) {
		$message
			.removeClass( 'notice-success notice-error notice-warning' )
			.addClass( 'notice notice-' + type )
			.html( '<p>' + $( '<span>' ).text( text ).html() + '</p>' )
			.show();
	}

	function hideMessage() {
		$message.hide();
	}

	function updateUI( status, expiresText ) {
		var labels = { active: 'Active', inactive: 'Inactive', expired: 'Expired', invalid: 'Invalid', pending: 'Pending' };

		$status
			.removeClass()
			.addClass( 'fswwp-cc-status fswwp-cc-status--' + status )
			.text( labels[ status ] || status );

		if ( expiresText ) {
			$expires.text( expiresText );
			$expRow.show();
		} else if ( 'active' === status ) {
			$expires.text( 'Lifetime' );
			$expRow.show();
		} else {
			$expRow.hide();
		}

		if ( 'active' === status ) {
			$keyInput.prop( 'readonly', true );
			$btnActivate.hide();
			$btnDeactivate.show();
			$btnValidate.hide();
		} else if ( 'pending' === status ) {
			// Sold or Delivered with available slots — ready to activate.
			$keyInput.prop( 'readonly', true );
			$btnDeactivate.hide();
			$btnActivate.show();
			$btnValidate.show();
		} else if ( 'inactive' === status ) {
			$keyInput.prop( 'readonly', false );
			$btnDeactivate.hide();
			$btnActivate.hide();
			$btnValidate.show();
		} else {
			// expired, invalid — show Verify, hide Activate/Deactivate.
			$keyInput.prop( 'readonly', false );
			$btnActivate.hide();
			$btnDeactivate.hide();
			$btnValidate.show();
		}
	}

	function doAjax( action, key ) {
		var $btn      = $( '#fswwp-cc-' + action + '-license' );
		var labelKey  = action + 'ing';
		var origText  = $btn.text();

		hideMessage();
		$btn.prop( 'disabled', true ).text( data.i18n[ labelKey ] || '…' );

		// Calls fswwp-license-checker's AJAX handlers.
		$.post( data.ajax_url, {
			action:      'fswwp_' + action + '_license',
			nonce:       data.nonce,
			license_key: key
		}, function( response ) {
			if ( response.success ) {
				$btn.prop( 'disabled', false ).text( origText );
				updateUI( response.data.status, response.data.expires || '' );
				showMessage( response.data.message, 'success' );
			} else {
				showMessage( response.data.message, 'error' );
				$btn.prop( 'disabled', false ).text( origText );
			}
		}, 'json' ).fail( function() {
			showMessage( 'Network error. Please try again.', 'error' );
			$btn.prop( 'disabled', false ).text( origText );
		} );
	}

	$( document ).on( 'click', '#fswwp-cc-activate-license', function() {
		var key = $keyInput.val().trim();
		if ( ! key ) {
			showMessage( data.i18n.empty_key || 'Please enter a license key.', 'error' );
			return;
		}
		doAjax( 'activate', key );
	} );

	$( document ).on( 'click', '#fswwp-cc-deactivate-license', function() {
		if ( ! confirm( data.i18n.confirm_deactivate || 'Are you sure?' ) ) {
			return;
		}
		doAjax( 'deactivate', $keyInput.val().trim() );
	} );

	// Verify / sync license status with the remote server.
	$( document ).on( 'click', '#fswwp-cc-validate-license', function() {
		hideMessage();
		$btnValidate.prop( 'disabled', true );
		$spinner.show();

		$.post( data.ajax_url, {
			action:      'fswwp_validate_license',
			nonce:       data.nonce,
			license_key: $keyInput.val().trim()
		}, function( response ) {
			$btnValidate.prop( 'disabled', false );
			$spinner.hide();

			if ( response.success ) {
				updateUI( response.data.status, response.data.expires || '' );
				showMessage( response.data.message, 'success' );
			} else {
				showMessage( response.data.message, 'error' );
			}
		}, 'json' ).fail( function() {
			$btnValidate.prop( 'disabled', false );
			$spinner.hide();
			showMessage( 'Network error. Please try again.', 'error' );
		} );
	} );

	// Rating stars in Review Details meta box — update (4/5) badge on change.
	$( document ).on( 'change', '.fswwp-review-editor-stars input[type="radio"]', function() {
		var val = parseInt( this.value, 10 ) || 0;
		$( this ).closest( '.fswwp-review-editor-stars' )
			.find( '.fswwp-review-editor-rating-current' )
			.text( val );
	} );

} )( jQuery, window.FSWWP_CommentsCore_License || {} );
