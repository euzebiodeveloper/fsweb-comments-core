/**
 * FSWWP Update Checker — Admin Scripts
 *
 * Handles the version rollback modal interaction inside the
 * "Previous Versions" thickbox.
 *
 * Depends on jQuery (available in WP admin) and the global
 * FSWWP_UpdateChecker_Rollback object (localized via wp_localize_script).
 */
( function( $, rollbackData ) {
	'use strict';

	var $doc = $( document );

	/**
	 * Enable the install button when a non-current version radio
	 * is selected.
	 */
	$doc.on( 'change', 'input[name="fswwp_version"]', function() {
		$( '#fswwp-install-version' ).prop( 'disabled', false );
	} );

	/**
	 * Handle install button click: POST the selected version
	 * to the AJAX install endpoint.
	 */
	$doc.on( 'click', '#fswwp-install-version', function() {
		var $btn     = $( this );
		var $checked = $( 'input[name="fswwp_version"]:checked' );

		if ( ! $checked.length ) {
			return;
		}

		var downloadUrl = $checked.val();
		var version     = $checked.data( 'version' );
		var $result     = $( '#fswwp-install-result' );

		$btn.prop( 'disabled', true ).text( rollbackData.i18n.installing || 'Installing\u2026' );
		$result
			.removeClass( 'success error' )
			.hide()
			.html( '<p>' + ( rollbackData.i18n.downloading || 'Downloading and installing version' ) + ' ' + version + '\u2026</p>' )
			.show();

		$.post( window.ajaxurl, {
			action:       rollbackData.action,
			_ajax_nonce:  rollbackData.nonce,
			download_url: downloadUrl,
			version:      version
		}, function( response ) {
			if ( response.success ) {
				$result
					.removeClass( 'error' )
					.addClass( 'success' )
					.html( '<p>' + response.data.message + '</p>' );

				// Reload the plugins page after a short delay
				// so the version number updates in the UI.
				setTimeout( function() {
					if ( window.parent && window.parent.tb_remove ) {
						window.parent.tb_remove();
					}
					window.location.reload();
				}, 1500 );
			} else {
				$btn.prop( 'disabled', false ).text( rollbackData.i18n.installBtn || 'Install Selected Version' );
				$result
					.removeClass( 'success' )
					.addClass( 'error' )
					.html( '<p>' + response.data.message + '</p>' );
			}
		}, 'json' ).fail( function() {
			$btn.prop( 'disabled', false ).text( rollbackData.i18n.installBtn || 'Install Selected Version' );
			$result
				.removeClass( 'success' )
				.addClass( 'error' )
				.html( '<p>' + ( rollbackData.i18n.networkError || 'Network error. Please try again.' ) + '</p>' );
		} );
	} );

} )( jQuery, window.FSWWP_UpdateChecker_Rollback || {} );
