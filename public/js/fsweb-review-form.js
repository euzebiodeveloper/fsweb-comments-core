/**
 * FSWEB Reviews — review form submission.
 *
 * Progressively enhances the plain HTML form: validates required fields,
 * posts through admin-ajax and shows the success panel. Without JavaScript
 * the browser still validates the fields, so nothing breaks.
 */
( function () {
	'use strict';

	var i18n = ( window.fswebReviews && window.fswebReviews.i18n ) || {};

	function each( list, callback ) {
		Array.prototype.forEach.call( list, callback );
	}

	function clearError( field ) {
		field.classList.remove( 'fsweb-review-form-field--error' );
		var error = field.querySelector( '.fsweb-review-form-error' );
		if ( error ) {
			error.textContent = '';
		}
	}

	function fieldValue( field ) {
		var radios = field.querySelectorAll( 'input[type="radio"]' );

		if ( radios.length ) {
			var checked = field.querySelector( 'input[type="radio"]:checked' );
			return checked ? checked.value : '';
		}

		var input = field.querySelector( 'input, textarea, select' );
		return input ? input.value.trim() : '';
	}

	function validate( form ) {
		var fields = form.querySelectorAll( '.fsweb-review-form-field' );
		var invalid = [];

		each( fields, function ( field ) {
			clearError( field );

			var control = field.querySelector( '[required]' );
			if ( ! control ) {
				return;
			}

			if ( '' === fieldValue( field ) ) {
				field.classList.add( 'fsweb-review-form-field--error' );
				var error = field.querySelector( '.fsweb-review-form-error' );
				if ( error ) {
					error.textContent = i18n.required || 'Required field.';
				}
				invalid.push( field );
			}
		} );

		return invalid;
	}

	function showMessage( form, text ) {
		var box = form.querySelector( '.fsweb-review-form-message' );
		if ( ! box ) {
			return;
		}
		box.textContent = text;
		box.hidden = false;
	}

	function hideMessage( form ) {
		var box = form.querySelector( '.fsweb-review-form-message' );
		if ( box ) {
			box.hidden = true;
			box.textContent = '';
		}
	}

	function showSuccess( form, message ) {
		var success = form.querySelector( '.fsweb-review-form-success' );
		var text = form.querySelector( '.fsweb-review-form-success-message' );
		var fields = form.querySelectorAll( '.fsweb-review-form-field' );

		if ( text ) {
			text.textContent = message;
		}

		each( fields, function ( field ) {
			field.hidden = true;
		} );

		if ( success ) {
			success.hidden = false;
		}
	}

	function resetForm( form ) {
		form.reset();

		each( form.querySelectorAll( '.fsweb-review-form-field' ), function ( field ) {
			field.hidden = false;
			clearError( field );
		} );

		var success = form.querySelector( '.fsweb-review-form-success' );
		if ( success ) {
			success.hidden = true;
		}

		hideMessage( form );
	}

	function submit( form ) {
		if ( ! window.fswebReviews || ! window.fswebReviews.ajaxUrl ) {
			form.submit();
			return;
		}

		var button = form.querySelector( '.fsweb-review-form-submit' );
		var spinner = form.querySelector( '.fsweb-review-form-spinner' );

		hideMessage( form );

		if ( button ) {
			button.disabled = true;
			button.dataset.label = button.textContent;
			button.textContent = i18n.submitting || 'Sending…';
		}

		if ( spinner ) {
			spinner.hidden = false;
		}

		var data = new FormData( form );
		data.append( 'action', 'fsweb_review_submit' );

		fetch( window.fswebReviews.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( result && result.success ) {
					showSuccess( form, ( result.data && result.data.message ) || i18n.success );
					return;
				}

				showMessage( form, ( result && result.data && result.data.message ) || i18n.error );
			} )
			.catch( function () {
				showMessage( form, i18n.error );
			} )
			.finally( function () {
				if ( button ) {
					button.disabled = false;
					button.textContent = button.dataset.label || button.textContent;
				}
				if ( spinner ) {
					spinner.hidden = true;
				}
			} );
	}

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target;

		if ( ! form.matches || ! form.matches( '[data-fsweb-form]' ) ) {
			return;
		}

		event.preventDefault();

		if ( validate( form ).length > 0 ) {
			showMessage( form, i18n.required || 'Required field.' );
			return;
		}

		submit( form );
	} );

	document.addEventListener( 'change', function ( event ) {
		var input = event.target;

		if ( ! input.matches || ! input.matches( '.fsweb-review-image-input' ) ) {
			return;
		}

		var wrapper = input.closest( '.fsweb-review-image-upload' );
		var preview = wrapper ? wrapper.querySelector( '.fsweb-review-image-preview' ) : null;
		var file = input.files && input.files[ 0 ];

		if ( ! wrapper || ! preview ) {
			return;
		}

		if ( ! file || 0 !== file.type.indexOf( 'image/' ) ) {
			input.value = '';
			wrapper.classList.remove( 'fsweb-review-image-upload--has-image' );
			preview.hidden = true;
			preview.removeAttribute( 'src' );
			return;
		}

		var reader = new FileReader();

		reader.onload = function ( loaded ) {
			preview.src = loaded.target.result;
			preview.hidden = false;
			wrapper.classList.add( 'fsweb-review-image-upload--has-image' );
		};

		reader.readAsDataURL( file );
	} );

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.fsweb-review-form-success-btn' );

		if ( ! button ) {
			return;
		}

		var form = button.closest( 'form' );
		if ( form ) {
			resetForm( form );
		}
	} );
}() );
