/**
 * FSWEB Comments Core — Admin Scripts
 *
 * License activation/deactivation UI. The manual "verify" action was removed:
 * the status is synced with the license server when the page loads.
 */
( function( $, data ) {
	'use strict';

	var $message   = $( '#fsweb-cc-license-message' );
	var $status    = $( '#fsweb-cc-license-status' );
	var $expires   = $( '#fsweb-cc-license-expires' );
	var $expRow    = $( '#fsweb-cc-expires-row' );
	var $keyInput  = $( '#fsweb-cc-license-key' );
	var $btnActivate   = $( '#fsweb-cc-activate-license' );
	var $btnDeactivate = $( '#fsweb-cc-deactivate-license' );

	// Site date format (PHP date() tokens), sent by wp_localize_script.
	var dateFormat = data.date_format || 'd/m/Y';

	/**
	 * Render an expiry date without ever printing a raw ISO string.
	 *
	 * PHP already sends a formatted date. Should a raw ISO-8601 value reach
	 * the response anyway, it is formatted in UTC so midnight UTC never
	 * shifts to the previous day on UTC-3 sites.
	 */
	function formatExpires( value ) {
		var date, pad, tokens;

		value = ( value == null ? '' : String( value ) ).trim();

		if ( ! value ) {
			return '';
		}

		// Already a formatted date — pass it through untouched.
		if ( ! /^\d{4}-\d{2}-\d{2}[T ]/.test( value ) ) {
			return value;
		}

		date = new Date( value );

		if ( isNaN( date.getTime() ) ) {
			return '';
		}

		pad = function( number ) {
			return ( number < 10 ? '0' : '' ) + number;
		};

		tokens = {
			d: pad( date.getUTCDate() ),
			j: String( date.getUTCDate() ),
			m: pad( date.getUTCMonth() + 1 ),
			n: String( date.getUTCMonth() + 1 ),
			Y: String( date.getUTCFullYear() ),
			y: String( date.getUTCFullYear() ).slice( -2 )
		};

		return dateFormat.replace( /[djmnYy]/g, function( token ) {
			return tokens[ token ];
		} );
	}

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

		expiresText = formatExpires( expiresText );

		$status
			.removeClass()
			.addClass( 'fsweb-cc-status fsweb-cc-status--' + status )
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
		} else {
			// Inactive, pending, expired or invalid — the key stays editable
			// so the user can fix it and activate again.
			$keyInput.prop( 'readonly', false );
			$btnActivate.show();
			$btnDeactivate.hide();
		}
	}

	function doAjax( action, key ) {
		var $btn      = $( '#fsweb-cc-' + action + '-license' );
		var labelKey  = action + 'ing';
		var origText  = $btn.text();

		hideMessage();
		$btn.prop( 'disabled', true ).text( data.i18n[ labelKey ] || '…' );

		// Calls fsweb-license-checker's AJAX handlers.
		$.post( data.ajax_url, {
			action:      'fsweb_' + action + '_license',
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

	$( document ).on( 'click', '#fsweb-cc-activate-license', function() {
		var key = $keyInput.val().trim();
		if ( ! key ) {
			showMessage( data.i18n.empty_key || 'Please enter a license key.', 'error' );
			return;
		}
		doAjax( 'activate', key );
	} );

	$( document ).on( 'click', '#fsweb-cc-deactivate-license', function() {
		if ( ! confirm( data.i18n.confirm_deactivate || 'Are you sure?' ) ) {
			return;
		}
		doAjax( 'deactivate', $keyInput.val().trim() );
	} );

	// Rating stars in Review Details meta box — update (4/5) badge on change.
	$( document ).on( 'change', '.fsweb-review-editor-stars input[type="radio"]', function() {
		var val = parseInt( this.value, 10 ) || 0;
		$( this ).closest( '.fsweb-review-editor-stars' )
			.find( '.fsweb-review-editor-rating-current' )
			.text( val );
	} );

} )( jQuery, window.FSWEB_CommentsCore_License || {} );
