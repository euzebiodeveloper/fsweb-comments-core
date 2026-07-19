/**
 * FSWWP Comments Core — Admin Scripts
 *
 * License activation/deactivation UI. AJAX calls are routed to
 * fswwp-license-checker (the license backend), which handles the
 * actual LMFWC communication.
 */
( function( $, data ) {
	'use strict';

	var $message  = $( '#fswwp-cc-license-message' );
	var $status   = $( '#fswwp-cc-license-status' );
	var $expires  = $( '#fswwp-cc-license-expires' );
	var $expRow   = $( '#fswwp-cc-expires-row' );
	var $keyInput = $( '#fswwp-cc-license-key' );
	var $submit   = $( '.fswwp-cc-license-card .submit' );

	function showMessage( text, type ) {
		$message
			.removeClass( 'notice-success notice-error notice-warning' )
			.addClass( 'notice notice-' + type )
			.html( '<p>' + $( '<span>' ).text( text ).html() + '</p>' )
			.show();
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
			$submit.html( '<button type="button" id="fswwp-cc-deactivate-license" class="button button-secondary">Deactivate</button>' );
		} else {
			$keyInput.prop( 'readonly', false );
			$submit.html( '<button type="button" id="fswwp-cc-activate-license" class="button button-primary">Activate</button>' );
		}
	}

	function doAjax( action, key ) {
		var $btn      = $( '#fswwp-cc-' + action + '-license' );
		var labelKey  = action + 'ing';
		var origText  = $btn.text();

		$btn.prop( 'disabled', true ).text( data.i18n[ labelKey ] || '…' );

		// Calls fswwp-license-checker's AJAX handlers.
		$.post( data.ajax_url, {
			action:      'fswwp_' + action + '_license',
			nonce:       data.nonce,
			license_key: key
		}, function( response ) {
			if ( response.success ) {
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

} )( jQuery, window.FSWWP_CommentsCore_License || {} );
