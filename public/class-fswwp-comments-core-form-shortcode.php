<?php
/**
 * FSWWP Comments Core — Review Form Shortcode
 *
 * [fswwp_review_form] — renders the review submission form
 * built from the Form Builder configuration.
 *
 * @package    Fswwp_Comments_Core
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Form shortcode handler.
 */
class Fswwp_Comments_Core_Form_Shortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'fswwp_review_form', array( $this, 'render' ) );
		add_action( 'wp_ajax_fswwp_review_submit', array( $this, 'ajax_submit' ) );
		add_action( 'wp_ajax_nopriv_fswwp_review_submit', array( $this, 'ajax_submit' ) );
	}

	/**
	 * Render the review submission form.
	 *
	 * @param array $atts Shortcode attributes (id = form ID).
	 * @return string HTML.
	 */
	public function render( $atts ) {
		if ( ! $this->is_license_active() ) {
			return '';
		}

		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'fswwp_review_form' );

		$forms = get_option( 'fswwp_review_forms', array() );

		if ( ! empty( $atts['id'] ) && isset( $forms[ $atts['id'] ] ) ) {
			$form = $forms[ $atts['id'] ];
		} elseif ( ! empty( $forms ) ) {
			$form = reset( $forms );
		} else {
			$form = array( 'fields' => $this->default_fields(), 'multi_step' => false );
		}

		$fields     = isset( $form['fields'] ) ? $form['fields'] : array();
		$multi_step = ! empty( $form['multi_step'] );
		$steps      = $multi_step && isset( $form['steps'] ) ? $form['steps'] : array();

		$nonce = wp_create_nonce( 'fswwp_review_submit' );

		// Enqueue form assets.
		wp_enqueue_style(
			'fswwp-review-form',
			FSWWP_COMMENTS_CORE_URL . 'public/css/fswwp-review-form.css',
			array(),
			FSWWP_COMMENTS_CORE_VERSION
		);
		wp_enqueue_style(
			'fswwp-reviews',
			FSWWP_COMMENTS_CORE_URL . 'public/css/fswwp-reviews.css',
			array(),
			FSWWP_COMMENTS_CORE_VERSION
		);

		ob_start();
		?>
		<div class="fswwp-review-form-wrap">
			<form class="fswwp-review-form" method="post" data-fswwp-form data-fswwp-multi="<?php echo $multi_step ? '1' : '0'; ?>">
				<input type="hidden" name="fswwp_nonce" value="<?php echo esc_attr( $nonce ); ?>">

				<?php if ( $multi_step && ! empty( $steps ) ) : ?>
					<!-- Step indicator -->
					<div class="fswwp-review-steps-indicator">
						<?php foreach ( $steps as $si => $step ) : ?>
							<span class="fswwp-review-step-dot<?php echo 0 === $si ? ' active' : ''; ?>" data-step="<?php echo (int) $si; ?>">
								<span class="fswwp-review-step-num"><?php echo (int) ( $si + 1 ); ?></span>
								<span class="fswwp-review-step-label"><?php echo esc_html( $step['label'] ); ?></span>
							</span>
							<?php if ( $si < count( $steps ) - 1 ) : ?>
								<span class="fswwp-review-step-line"></span>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>

					<!-- Steps -->
					<?php foreach ( $steps as $si => $step ) : ?>
						<div class="fswwp-review-step-panel" data-step="<?php echo (int) $si; ?>" style="<?php echo 0 === $si ? '' : 'display:none;'; ?>">
							<?php foreach ( $step['fields'] as $field ) : ?>
								<div class="fswwp-review-form-field fswwp-review-form-field--<?php echo esc_attr( $field['type'] ); ?>">
									<label class="fswwp-review-form-label">
										<?php echo esc_html( $field['label'] ); ?>
										<?php if ( ! empty( $field['required'] ) ) : ?>
											<span class="fswwp-review-form-required">*</span>
										<?php endif; ?>
									</label>
									<?php $this->render_field( $field ); ?>
									<span class="fswwp-review-form-error"></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>

					<!-- Navigation -->
					<div class="fswwp-review-steps-nav">
						<button type="button" class="fswwp-review-step-prev" style="display:none;">
							&larr; <?php esc_html_e( 'Previous', 'fswwp-comments-core' ); ?>
						</button>
						<button type="button" class="fswwp-review-step-next">
							<?php esc_html_e( 'Next', 'fswwp-comments-core' ); ?> &rarr;
						</button>
						<button type="submit" class="fswwp-review-form-submit" style="display:none;">
							<?php esc_html_e( 'Submit Review', 'fswwp-comments-core' ); ?>
						</button>
						<span class="fswwp-review-form-spinner" style="display:none;"></span>
					</div>
				<?php else : ?>
					<!-- Single-step (normal) form -->
					<?php foreach ( $fields as $field ) : ?>
						<div class="fswwp-review-form-field fswwp-review-form-field--<?php echo esc_attr( $field['type'] ); ?>">
							<label class="fswwp-review-form-label">
								<?php echo esc_html( $field['label'] ); ?>
								<?php if ( ! empty( $field['required'] ) ) : ?>
									<span class="fswwp-review-form-required">*</span>
								<?php endif; ?>
							</label>
							<?php $this->render_field( $field ); ?>
							<span class="fswwp-review-form-error"></span>
						</div>
					<?php endforeach; ?>

					<div class="fswwp-review-form-field">
						<button type="submit" class="fswwp-review-form-submit">
							<?php esc_html_e( 'Submit Review', 'fswwp-comments-core' ); ?>
						</button>
						<span class="fswwp-review-form-spinner" style="display:none;"></span>
					</div>
				<?php endif; ?>

				<div class="fswwp-review-form-message" style="display:none;"></div>
				<div class="fswwp-review-form-success" style="display:none;">
					<div class="fswwp-review-form-success-icon">&#10003;</div>
					<p class="fswwp-review-form-success-message"></p>
					<button type="button" class="fswwp-review-form-success-btn"><?php esc_html_e( 'Submit Another Review', 'fswwp-comments-core' ); ?></button>
				</div>
			</form>
		</div>

		<?php if ( $multi_step && ! empty( $steps ) ) : ?>
		<script>
		( function() {
			var form       = document.querySelector( '[data-fswwp-multi="1"]' );
			if ( ! form ) { return; }
			var panels     = form.querySelectorAll( '.fswwp-review-step-panel' );
			var dots       = form.querySelectorAll( '.fswwp-review-step-dot' );
			var prevBtn    = form.querySelector( '.fswwp-review-step-prev' );
			var nextBtn    = form.querySelector( '.fswwp-review-step-next' );
			var submitBtn  = form.querySelector( '.fswwp-review-form-submit' );
			var spinner    = form.querySelector( '.fswwp-review-form-spinner' );
			var msg        = form.querySelector( '.fswwp-review-form-message' );
			var successOverlay = form.querySelector( '.fswwp-review-form-success' );
			var successMsg     = form.querySelector( '.fswwp-review-form-success-message' );
			var successBtn     = form.querySelector( '.fswwp-review-form-success-btn' );
			var total      = panels.length;
			var current    = 0;

			function clearFieldError( field ) {
				field.classList.remove( 'fswwp-review-form-field--error' );
				var err = field.querySelector( '.fswwp-review-form-error' );
				if ( err ) { err.textContent = ''; }
			}

			function validateField( field ) {
				var input      = field.querySelector( 'input, textarea, select' );
				var err        = field.querySelector( '.fswwp-review-form-error' );
				var isRequired = input && input.hasAttribute( 'required' );
				var type       = input ? ( input.getAttribute( 'type' ) || '' ).toLowerCase() : '';
				var tag        = input ? input.tagName.toLowerCase() : '';
				var value      = input ? input.value.trim() : '';

				// File — skip client-side validation (accept attr handles it).
				if ( tag === 'input' && type === 'file' ) { return true; }

				// Rating (radio group).
				if ( type === 'radio' ) {
					var checked = field.querySelector( 'input[type="radio"]:checked' );
					if ( isRequired && ! checked ) {
						field.classList.add( 'fswwp-review-form-field--error' );
						if ( err ) { err.textContent = '<?php echo esc_js( __( 'Please select a rating.', 'fswwp-comments-core' ) ); ?>'; }
						return false;
					}
					return true;
				}

				// Required — empty.
				if ( isRequired && value === '' ) {
					field.classList.add( 'fswwp-review-form-field--error' );
					if ( err ) { err.textContent = '<?php echo esc_js( __( 'This field is required.', 'fswwp-comments-core' ) ); ?>'; }
					return false;
				}

				// Email format (only when non-empty).
				if ( value !== '' && ( type === 'email' || ( input.name && input.name.toLowerCase().indexOf( 'email' ) !== -1 ) ) ) {
					var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					if ( ! emailRe.test( value ) ) {
						field.classList.add( 'fswwp-review-form-field--error' );
						if ( err ) { err.textContent = '<?php echo esc_js( __( 'Please enter a valid email address.', 'fswwp-comments-core' ) ); ?>'; }
						return false;
					}
				}

				// URL format (only when non-empty).
				if ( value !== '' && type === 'url' ) {
					var urlRe = /^https?:\/\/.+/i;
					if ( ! urlRe.test( value ) ) {
						field.classList.add( 'fswwp-review-form-field--error' );
						if ( err ) { err.textContent = '<?php echo esc_js( __( 'Please enter a valid URL.', 'fswwp-comments-core' ) ); ?>'; }
						return false;
					}
				}

				return true;
			}

			function validateStep( idx ) {
				var panel  = panels[ idx ];
				var fields = panel.querySelectorAll( '.fswwp-review-form-field' );
				var ok     = true;
				fields.forEach( function( f ) {
					if ( ! validateField( f ) ) { ok = false; }
				} );
				return ok;
			}

			function showStep( idx ) {
				panels.forEach( function( p, i ) { p.style.display = i === idx ? '' : 'none'; } );
				dots.forEach( function( d, i ) { d.classList.toggle( 'active', i === idx ); } );
				prevBtn.style.display = idx === 0 ? 'none' : '';
				nextBtn.style.display = idx === total - 1 ? 'none' : '';
				submitBtn.style.display = idx === total - 1 ? '' : 'none';
				current = idx;
			}

			// Clear error on user input/change.
			form.addEventListener( 'input', function( e ) {
				var field = e.target.closest( '.fswwp-review-form-field' );
				if ( field ) { clearFieldError( field ); }
			} );
			form.addEventListener( 'change', function( e ) {
				var field = e.target.closest( '.fswwp-review-form-field' );
				if ( field ) { clearFieldError( field ); }
			} );

			prevBtn.addEventListener( 'click', function() {
				if ( current > 0 ) { showStep( current - 1 ); }
			} );

			nextBtn.addEventListener( 'click', function() {
				if ( validateStep( current ) ) {
					showStep( current + 1 );
				}
			} );

			// "Submit Another" button — reset and hide overlay.
			successBtn.addEventListener( 'click', function() {
				form.reset();
				successOverlay.style.display = 'none';
				showStep( 0 );
			} );

			// Submit handler for multi-step.
			form.addEventListener( 'submit', function( e ) {
				e.preventDefault();
				if ( ! validateStep( current ) ) { return; }

				submitBtn.disabled = true;
				spinner.style.display = 'inline-block';
				msg.style.display = 'none';

				var data = new FormData( form );
				data.append( 'action', 'fswwp_review_submit' );

				fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					body: data
				} )
				.then( function( r ) { return r.json(); } )
				.then( function( r ) {
					submitBtn.disabled = false;
					spinner.style.display = 'none';
					if ( r.success ) {
						successMsg.textContent = r.data.message;
						successOverlay.style.display = '';
					} else {
						msg.style.display = 'block';
						msg.className = 'fswwp-review-form-message fswwp-review-form-message--error';
						msg.textContent = r.data.message;
					}
				} )
				.catch( function() {
					submitBtn.disabled = false;
					spinner.style.display = 'none';
					msg.style.display = 'block';
					msg.className = 'fswwp-review-form-message fswwp-review-form-message--error';
					msg.textContent = 'Network error. Please try again.';
				} );
			} );
		} )();
		</script>
		<?php else : ?>
		<script>
		( function() {
			var form = document.querySelector( '[data-fswwp-form]' );
			if ( ! form ) { return; }

			var successOverlay = form.querySelector( '.fswwp-review-form-success' );
			var successMsg     = form.querySelector( '.fswwp-review-form-success-message' );
			var successBtn     = form.querySelector( '.fswwp-review-form-success-btn' );

			function clearFieldError( field ) {
				field.classList.remove( 'fswwp-review-form-field--error' );
				var err = field.querySelector( '.fswwp-review-form-error' );
				if ( err ) { err.textContent = ''; }
			}

			function validateField( field ) {
				var input      = field.querySelector( 'input, textarea, select' );
				var err        = field.querySelector( '.fswwp-review-form-error' );
				var isRequired = input && input.hasAttribute( 'required' );
				var type       = input ? ( input.getAttribute( 'type' ) || '' ).toLowerCase() : '';
				var tag        = input ? input.tagName.toLowerCase() : '';
				var value      = input ? input.value.trim() : '';

				// File — skip client-side validation.
				if ( tag === 'input' && type === 'file' ) { return true; }

				// Rating (radio group).
				if ( type === 'radio' ) {
					var checked = field.querySelector( 'input[type="radio"]:checked' );
					if ( isRequired && ! checked ) {
						field.classList.add( 'fswwp-review-form-field--error' );
						if ( err ) { err.textContent = '<?php echo esc_js( __( 'Please select a rating.', 'fswwp-comments-core' ) ); ?>'; }
						return false;
					}
					return true;
				}

				// Required — empty.
				if ( isRequired && value === '' ) {
					field.classList.add( 'fswwp-review-form-field--error' );
					if ( err ) { err.textContent = '<?php echo esc_js( __( 'This field is required.', 'fswwp-comments-core' ) ); ?>'; }
					return false;
				}

				// Email format.
				if ( value !== '' && ( type === 'email' || ( input.name && input.name.toLowerCase().indexOf( 'email' ) !== -1 ) ) ) {
					var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					if ( ! emailRe.test( value ) ) {
						field.classList.add( 'fswwp-review-form-field--error' );
						if ( err ) { err.textContent = '<?php echo esc_js( __( 'Please enter a valid email address.', 'fswwp-comments-core' ) ); ?>'; }
						return false;
					}
				}

				// URL format.
				if ( value !== '' && type === 'url' ) {
					var urlRe = /^https?:\/\/.+/i;
					if ( ! urlRe.test( value ) ) {
						field.classList.add( 'fswwp-review-form-field--error' );
						if ( err ) { err.textContent = '<?php echo esc_js( __( 'Please enter a valid URL.', 'fswwp-comments-core' ) ); ?>'; }
						return false;
					}
				}

				return true;
			}

			function validateForm() {
				var fields = form.querySelectorAll( '.fswwp-review-form-field' );
				var ok = true;
				fields.forEach( function( f ) {
					if ( ! validateField( f ) ) { ok = false; }
				} );
				return ok;
			}

			// Clear error on user input/change.
			form.addEventListener( 'input', function( e ) {
				var field = e.target.closest( '.fswwp-review-form-field' );
				if ( field ) { clearFieldError( field ); }
			} );
			form.addEventListener( 'change', function( e ) {
				var field = e.target.closest( '.fswwp-review-form-field' );
				if ( field ) { clearFieldError( field ); }
			} );

			// "Submit Another" button.
			successBtn.addEventListener( 'click', function() {
				form.reset();
				successOverlay.style.display = 'none';
			} );

			form.addEventListener( 'submit', function( e ) {
				e.preventDefault();
				if ( ! validateForm() ) { return; }

				var btn     = form.querySelector( '.fswwp-review-form-submit' );
				var spinner = form.querySelector( '.fswwp-review-form-spinner' );
				var msg     = form.querySelector( '.fswwp-review-form-message' );
				var data    = new FormData( form );
				data.append( 'action', 'fswwp_review_submit' );

				btn.disabled = true;
				spinner.style.display = 'inline-block';
				msg.style.display = 'none';

				fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					body: data
				} )
				.then( function( r ) { return r.json(); } )
				.then( function( r ) {
					btn.disabled = false;
					spinner.style.display = 'none';
					if ( r.success ) {
						successMsg.textContent = r.data.message;
						successOverlay.style.display = '';
					} else {
						msg.style.display = 'block';
						msg.className = 'fswwp-review-form-message fswwp-review-form-message--error';
						msg.textContent = r.data.message;
					}
				} )
				.catch( function() {
					btn.disabled = false;
					spinner.style.display = 'none';
					msg.style.display = 'block';
					msg.className = 'fswwp-review-form-message fswwp-review-form-message--error';
					msg.textContent = 'Network error. Please try again.';
				} );
			} );
		} )();
		</script>
		<?php endif; ?>
		<script>
		( function() {
			var forms = document.querySelectorAll( '[data-fswwp-form]' );
			forms.forEach( function( form ) {
				form.addEventListener( 'change', function( e ) {
					var input = e.target;
					if ( ! input.classList.contains( 'fswwp-review-image-input' ) ) { return; }
					var wrapper = input.closest( '.fswwp-review-image-upload' );
					var preview = wrapper ? wrapper.querySelector( '.fswwp-review-image-preview' ) : null;
					if ( ! wrapper || ! preview ) { return; }

					var file = input.files && input.files[0];
					if ( file ) {
						var reader = new FileReader();
						reader.onload = function( ev ) {
							preview.src = ev.target.result;
							preview.style.display = '';
							wrapper.classList.add( 'fswwp-review-image-upload--has-image' );
						};
						reader.readAsDataURL( file );
					} else {
						preview.src = '';
						preview.style.display = 'none';
						wrapper.classList.remove( 'fswwp-review-image-upload--has-image' );
					}
				} );
			} );
		} )();
		</script>
		<?php
		return ob_get_clean();
	}

	// ------------------------------------------------------------------//
	//  Field rendering
	// ------------------------------------------------------------------//

	/**
	 * Render a single form field.
	 *
	 * @param array $field Field config.
	 * @return void
	 */
	private function render_field( $field ) {
		$attrs = '';
		if ( ! empty( $field['required'] ) ) {
			$attrs .= ' required';
		}
		if ( ! empty( $field['placeholder'] ) ) {
			$attrs .= ' placeholder="' . esc_attr( $field['placeholder'] ) . '"';
		}

		switch ( $field['type'] ) {
			case 'image':
				echo '<div class="fswwp-review-image-upload">';
				echo '<input type="file" name="' . esc_attr( $field['id'] ) . '" accept="image/*"' . $attrs . ' class="fswwp-review-image-input">';
				echo '<div class="fswwp-review-image-placeholder">';
				echo '<span class="fswwp-review-image-plus">+</span>';
				echo '<span class="fswwp-review-image-text">' . esc_html__( 'Click to upload', 'fswwp-comments-core' ) . '</span>';
				echo '</div>';
				echo '<img class="fswwp-review-image-preview" src="" alt="" style="display:none;">';
				echo '</div>';
				break;

			case 'textarea':
				echo '<textarea name="' . esc_attr( $field['id'] ) . '" rows="4"' . $attrs . '></textarea>';
				break;

			case 'email':
				echo '<input type="email" name="' . esc_attr( $field['id'] ) . '"' . $attrs . '>';
				break;

			case 'url':
				echo '<input type="url" name="' . esc_attr( $field['id'] ) . '"' . $attrs . '>';
				break;

			case 'date':
				echo '<input type="date" name="' . esc_attr( $field['id'] ) . '"' . $attrs . '>';
				break;

			case 'select':
				echo '<select name="' . esc_attr( $field['id'] ) . '"' . $attrs . '>';
				echo '<option value="">' . esc_html__( '— Select —', 'fswwp-comments-core' ) . '</option>';
				if ( ! empty( $field['options'] ) ) {
					$opts = explode( "\n", $field['options'] );
					foreach ( $opts as $opt ) {
						$opt = trim( $opt );
						if ( '' !== $opt ) {
							echo '<option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . '</option>';
						}
					}
				}
				echo '</select>';
				break;

			case 'rating':
				echo '<div class="fswwp-review-stars">';
				for ( $i = 5; $i >= 1; $i-- ) {
					echo '<input type="radio" id="' . esc_attr( $field['id'] . '_' . $i ) . '" name="' . esc_attr( $field['id'] ) . '" value="' . (int) $i . '"' . $attrs . '>';
					echo '<label for="' . esc_attr( $field['id'] . '_' . $i ) . '" title="' . (int) $i . '">★</label>';
				}
				echo '</div>';
				break;

			default: // text.
				echo '<input type="text" name="' . esc_attr( $field['id'] ) . '"' . $attrs . '>';
				break;
		}
	}

	// ------------------------------------------------------------------//
	//  Default fields
	// ------------------------------------------------------------------//

	/**
	 * Default form fields.
	 *
	 * @return array
	 */
	private function default_fields() {
		return array(
			array(
				'id'          => 'name',
				'type'        => 'text',
				'label'       => __( 'Full Name', 'fswwp-comments-core' ),
				'placeholder' => __( 'Your name', 'fswwp-comments-core' ),
				'required'    => true,
			),
			array(
				'id'          => 'review',
				'type'        => 'textarea',
				'label'       => __( 'Your Review', 'fswwp-comments-core' ),
				'placeholder' => __( 'Write your review here...', 'fswwp-comments-core' ),
				'required'    => true,
			),
		);
	}

	/**
	 * Check if license is active.
	 *
	 * @return bool
	 */
	private function is_license_active() {
		return 'active' === get_option( 'fswwp_license_status', '' );
	}

	// ------------------------------------------------------------------//
	//  AJAX — review submission
	// ------------------------------------------------------------------//

	/**
	 * Handle review form submission via AJAX.
	 *
	 * Creates a draft fswwp_review post from the submitted fields.
	 */
	public function ajax_submit() {
		check_ajax_referer( 'fswwp_review_submit', 'fswwp_nonce' );

		if ( ! $this->is_license_active() ) {
			wp_send_json_error( array( 'message' => __( 'License is not active.', 'fswwp-comments-core' ) ) );
		}

		$forms = get_option( 'fswwp_review_forms', array() );

		// Collect all fields (flatten steps if multi).
		$all_fields = array();
		if ( ! empty( $forms ) ) {
			$first = reset( $forms );
			if ( ! empty( $first['multi_step'] ) && ! empty( $first['steps'] ) ) {
				foreach ( $first['steps'] as $step ) {
					foreach ( $step['fields'] as $f ) {
						$all_fields[] = $f;
					}
				}
			} elseif ( ! empty( $first['fields'] ) ) {
				$all_fields = $first['fields'];
			}
		}
		if ( empty( $all_fields ) ) {
			$all_fields = $this->default_fields();
		}

		$name    = '';
		$content = '';
		$meta    = array();
		$found_name    = false;
		$found_content = false;

		foreach ( $all_fields as $field ) {
			$field_type = $field['type'];
			$field_id   = $field['id'];

			// Handle image — file upload via $_FILES.
			if ( 'image' === $field_type ) {
				$file_value = '';
				// phpcs:disable WordPress.Security.NonceVerification.Missing
				if ( isset( $_FILES[ $field_id ] ) && UPLOAD_ERR_OK === $_FILES[ $field_id ]['error'] ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';

					$upload = wp_handle_upload(
						$_FILES[ $field_id ],
						array( 'test_form' => false )
					);

					if ( isset( $upload['file'] ) ) {
						$attachment_id = wp_insert_attachment( array(
							'post_mime_type' => $upload['type'],
							'post_title'     => sanitize_file_name( basename( $upload['file'] ) ),
							'post_content'   => '',
							'post_status'    => 'inherit',
						), $upload['file'] );

						if ( ! is_wp_error( $attachment_id ) ) {
							wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
							wp_update_attachment_metadata(
								$attachment_id,
								wp_generate_attachment_metadata( $attachment_id, $upload['file'] )
							);
							$file_value = $attachment_id;
						} else {
							$file_value = $upload['url'];
						}
					} elseif ( isset( $upload['url'] ) ) {
						$file_value = $upload['url'];
					}
				}
				// phpcs:enable
				$meta[ $field_id ] = $file_value;
				continue;
			}

			// Handle rating — may come as radio value.
			$value = '';
			if ( 'rating' === $field_type ) {
				// phpcs:disable WordPress.Security.NonceVerification.Missing
				$value = isset( $_POST[ $field_id ] ) ? (int) $_POST[ $field_id ] : 0;
				// phpcs:enable
				if ( ! empty( $field['required'] ) && $value < 1 ) {
					wp_send_json_error( array(
						'message' => sprintf(
							/* translators: %s: field label */
							__( '%s is required.', 'fswwp-comments-core' ),
							$field['label']
						),
					) );
				}
				$meta[ $field_id ] = min( 5, max( 0, $value ) );
				continue;
			}

			// Text-based fields.
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$value = isset( $_POST[ $field_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) ) : '';
			// phpcs:enable

			// Required check for non-file, non-rating fields.
			if ( ! empty( $field['required'] ) && '' === $value ) {
				wp_send_json_error( array(
					'message' => sprintf(
						/* translators: %s: field label */
						__( '%s is required.', 'fswwp-comments-core' ),
						$field['label']
					),
				) );
			}

			// Match name field: first text field, or field with 'name' in ID.
			if ( ! $found_name && ( 'text' === $field_type || false !== stripos( $field_id, 'name' ) ) ) {
				$name        = $value;
				$found_name  = true;
				continue;
			}

			// Match content field: first textarea, or field with 'desc'/'review' in ID.
			if ( ! $found_content && ( 'textarea' === $field_type || false !== stripos( $field_id, 'desc' ) || false !== stripos( $field_id, 'review' ) ) ) {
				$content        = $value;
				$found_content  = true;
				continue;
			}

			// Validation by type.
			if ( 'email' === $field_type && ! empty( $value ) && ! is_email( $value ) ) {
				wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'fswwp-comments-core' ) ) );
			} elseif ( 'url' === $field_type && ! empty( $value ) && ! wp_http_validate_url( $value ) ) {
				wp_send_json_error( array( 'message' => __( 'Please enter a valid URL.', 'fswwp-comments-core' ) ) );
			}

			$meta[ $field_id ] = $value;
		}

		// Build the review post.
		$post_data = array(
			'post_type'    => 'fswwp_review',
			'post_status'  => 'pending',
			'post_title'   => ! empty( $name ) ? $name : __( 'Anonymous', 'fswwp-comments-core' ),
			'post_content' => $content,
			'meta_input'   => $meta,
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		// Set featured image from the first uploaded image field.
		foreach ( $all_fields as $field ) {
			if ( 'image' === $field['type'] && ! empty( $meta[ $field['id'] ] ) && is_numeric( $meta[ $field['id'] ] ) ) {
				set_post_thumbnail( $post_id, (int) $meta[ $field['id'] ] );
				break;
			}
		}

		wp_send_json_success( array(
			'message' => __( 'Thank you! Your review has been submitted and is pending approval.', 'fswwp-comments-core' ),
		) );
	}
}
