/**
 * FSWWP Update Checker — Admin Scripts
 *
 * Handles the version rollback modal interaction inside the
 * "Previous Versions" thickbox, and the "Check for Updates"
 * inline AJAX action.
 *
 * Depends on jQuery (available in WP admin) and the global
 * FSWWP_UpdateChecker_Rollback object (localized via wp_localize_script).
 */
( function( $, data ) {
	'use strict';

	var $doc = $( document );

	// ------------------------------------------------------------------//
	//  Previous Versions — thickbox radio / install
	// ------------------------------------------------------------------//

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

		$btn.prop( 'disabled', true ).text( data.i18n.installing || 'Installing\u2026' );
		$result
			.removeClass( 'success error' )
			.hide()
			.html( '<p>' + ( data.i18n.downloading || 'Downloading and installing version' ) + ' ' + version + '\u2026</p>' )
			.show();

		$.post( window.ajaxurl, {
			action:       data.action,
			_ajax_nonce:  data.nonce,
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
				$btn.prop( 'disabled', false ).text( data.i18n.installBtn || 'Install Selected Version' );
				$result
					.removeClass( 'success' )
					.addClass( 'error' )
					.html( '<p>' + response.data.message + '</p>' );
			}
		}, 'json' ).fail( function() {
			$btn.prop( 'disabled', false ).text( data.i18n.installBtn || 'Install Selected Version' );
			$result
				.removeClass( 'success' )
				.addClass( 'error' )
				.html( '<p>' + ( data.i18n.networkError || 'Network error. Please try again.' ) + '</p>' );
		} );
	} );

	// ------------------------------------------------------------------//
	//  Check for Updates — inline AJAX
	// ------------------------------------------------------------------//

	/**
	 * Handle "Check for Updates" click: POST to admin-ajax,
	 * show inline feedback, then reload the page so WordPress
	 * picks up the fresh update data.
	 */
	$doc.on( 'click', '.fswwp-check-updates', function( e ) {
		e.preventDefault();

		var $link    = $( this );
		var $row     = $link.closest( 'tr' );
		var origText = $link.text();

		// Visual feedback while checking.
		$link.text( data.i18n.checking || 'Checking\u2026' );

		$.post( window.ajaxurl, {
			action:      $link.data( 'action' ),
			_ajax_nonce: $link.data( 'nonce' )
		}, function( response ) {
			if ( response.success ) {
				// Update available or already latest — reload so
				// WordPress shows the update notice inline.
				window.location.reload();
			} else {
				// Error (e.g. no license) — show inline and restore link.
				showInlineNotice( $row, response.data.message || ( data.i18n.checking || 'Check failed.' ), 'warning' );
				$link.text( origText );
			}
		}, 'json' ).fail( function() {
			showInlineNotice( $row, data.i18n.networkError || 'Network error.', 'error' );
			$link.text( origText );
		} );
	} );

	/**
	 * Show (or replace) an inline notice below a plugin row.
	 *
	 * Uses WordPress-standard classes so it looks identical to
	 * the native update notification row. Adds the `update` class
	 * to the plugin row so the two rows are visually fused (no gap).
	 *
	 * @param {jQuery} $row    The plugin <tr> element.
	 * @param {string} message Notice text (plain text, will be escaped).
	 * @param {string} type    'warning' or 'error'.
	 */
	function showInlineNotice( $row, message, type ) {
		// Remove any previous inline notice from this row.
		$row.next( '.fswwp-inline-notice' ).remove();

		var cls = 'notice-error';
		if ( 'warning' === type ) {
			cls = 'notice-warning';
		}

		// Add `update` class so WordPress CSS fuses the two rows together.
		$row.addClass( 'update' );

		var $notice = $( '<tr class="plugin-update-tr active fswwp-inline-notice">' +
			'<td colspan="4" class="plugin-update colspanchange">' +
			'<div class="update-message notice inline ' + cls + ' notice-alt">' +
			'<p>' + $( '<span>' ).text( message ).html() + '</p>' +
			'</div></td></tr>' );

		$row.after( $notice );
	}

} )( jQuery, window.FSWWP_UpdateChecker_Rollback || {} );
