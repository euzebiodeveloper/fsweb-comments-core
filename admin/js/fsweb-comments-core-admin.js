/**
 * FSWEB Comments Core — admin behaviour.
 */
( function () {
	'use strict';

	function updateBadge( picker ) {
		var checked = picker.querySelector( 'input[type="radio"]:checked' );
		var badge = picker.querySelector( '.fsweb-review-editor-rating-current' );

		if ( badge ) {
			badge.textContent = checked ? checked.value : '0';
		}
	}

	document.addEventListener( 'change', function ( event ) {
		if ( ! event.target.matches( '.fsweb-review-editor-stars input[type="radio"]' ) ) {
			return;
		}

		updateBadge( event.target.closest( '.fsweb-review-editor-stars' ) );
	} );
}() );
