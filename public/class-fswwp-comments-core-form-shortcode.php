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

		// If ID provided, use that form.
		if ( ! empty( $atts['id'] ) && isset( $forms[ $atts['id'] ] ) ) {
			$fields = $forms[ $atts['id'] ]['fields'];
		} elseif ( ! empty( $forms ) ) {
			// Fallback to first form.
			$first  = reset( $forms );
			$fields = $first['fields'];
		} else {
			$fields = $this->default_fields();
		}

		$nonce = wp_create_nonce( 'fswwp_review_submit' );

		// Enqueue form assets.
		wp_enqueue_style(
			'fswwp-review-form',
			FSWWP_COMMENTS_CORE_URL . 'public/css/fswwp-reviews.css',
			array(),
			FSWWP_COMMENTS_CORE_VERSION
		);

		ob_start();
		?>
		<div class="fswwp-review-form-wrap">
			<form class="fswwp-review-form" method="post" data-fswwp-form>
				<input type="hidden" name="fswwp_nonce" value="<?php echo esc_attr( $nonce ); ?>">

				<?php foreach ( $fields as $field ) : ?>
					<div class="fswwp-review-form-field fswwp-review-form-field--<?php echo esc_attr( $field['type'] ); ?>">
						<label class="fswwp-review-form-label">
							<?php echo esc_html( $field['label'] ); ?>
							<?php if ( ! empty( $field['required'] ) ) : ?>
								<span class="fswwp-review-form-required">*</span>
							<?php endif; ?>
						</label>

						<?php $this->render_field( $field ); ?>
					</div>
				<?php endforeach; ?>

				<div class="fswwp-review-form-field">
					<button type="submit" class="fswwp-review-form-submit">
						<?php esc_html_e( 'Submit Review', 'fswwp-comments-core' ); ?>
					</button>
					<span class="fswwp-review-form-spinner" style="display:none;"></span>
				</div>

				<div class="fswwp-review-form-message" style="display:none;"></div>
			</form>
		</div>

		<script>
		( function() {
			var form = document.querySelector( '[data-fswwp-form]' );
			if ( ! form ) { return; }

			form.addEventListener( 'submit', function( e ) {
				e.preventDefault();

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
					msg.style.display = 'block';
					msg.className = 'fswwp-review-form-message fswwp-review-form-message--' + ( r.success ? 'success' : 'error' );
					msg.textContent = r.data.message;
					if ( r.success ) {
						form.reset();
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
			case 'textarea':
				echo '<textarea name="' . esc_attr( $field['id'] ) . '" rows="4"' . $attrs . '></textarea>';
				break;

			case 'email':
				echo '<input type="email" name="' . esc_attr( $field['id'] ) . '"' . $attrs . '>';
				break;

			case 'url':
				echo '<input type="url" name="' . esc_attr( $field['id'] ) . '"' . $attrs . '>';
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
					echo '<label for="' . esc_attr( $field['id'] . '_' . $i ) . '" title="' . (int) $i . '">&#9733;</label>';
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

		$fields = get_option( 'fswwp_review_form_fields', $this->default_fields() );

		$name    = '';
		$content = '';
		$meta    = array();

		foreach ( $fields as $field ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$value = isset( $_POST[ $field['id'] ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field['id'] ] ) ) : '';
			// phpcs:enable

			if ( ! empty( $field['required'] ) && '' === $value ) {
				wp_send_json_error( array(
					'message' => sprintf(
						/* translators: %s: field label */
						__( '%s is required.', 'fswwp-comments-core' ),
						$field['label']
					),
				) );
			}

			if ( 'name' === $field['id'] ) {
				$name = $value;
			} elseif ( 'review' === $field['id'] ) {
				$content = $value;
			} elseif ( 'email' === $field['type'] && ! empty( $value ) && ! is_email( $value ) ) {
				wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'fswwp-comments-core' ) ) );
			} elseif ( 'url' === $field['type'] && ! empty( $value ) && ! wp_http_validate_url( $value ) ) {
				wp_send_json_error( array( 'message' => __( 'Please enter a valid URL.', 'fswwp-comments-core' ) ) );
			} else {
				$meta[ $field['id'] ] = $value;
			}
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

		wp_send_json_success( array(
			'message' => __( 'Thank you! Your review has been submitted and is pending approval.', 'fswwp-comments-core' ),
		) );
	}
}
