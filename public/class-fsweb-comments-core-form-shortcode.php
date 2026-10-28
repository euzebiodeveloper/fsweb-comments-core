<?php
/**
 * FSWEB Comments Core — [fsweb_review_form]
 *
 * Renders the review submission form and handles the AJAX request.
 *
 * @package    Fsweb_Comments_Core
 * @subpackage Fsweb_Comments_Core/public
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Review form shortcode and submission handler.
 *
 * The free edition ships a fixed, accessible form: name, email, rating and
 * review. The PRO add-on filters the field list and the submission payload.
 *
 * @since 1.0.0
 */
class Fsweb_Comments_Core_Form_Shortcode {

	/**
	 * Meta keys accepted from the form.
	 *
	 * @var array
	 */
	const META_FIELDS = array( 'fsweb_name', 'fsweb_email', 'fsweb_rating', 'fsweb_description' );

	/**
	 * Register the shortcode and the AJAX endpoints.
	 */
	public function __construct() {
		add_shortcode( 'fsweb_review_form', array( $this, 'render' ) );
		add_action( 'wp_ajax_fsweb_review_submit', array( $this, 'ajax_submit' ) );
		add_action( 'wp_ajax_nopriv_fsweb_review_submit', array( $this, 'ajax_submit' ) );
	}

	/**
	 * Default fields of the free form.
	 *
	 * @return array
	 */
	public function get_fields() {
		$fields = array(
			array(
				'id'       => 'fsweb_photo',
				'type'     => 'image',
				'label'    => __( 'Your photo', 'fsweb-comments-core' ),
				'required' => false,
			),
			array(
				'id'       => 'fsweb_name',
				'type'     => 'text',
				'label'    => __( 'Your name', 'fsweb-comments-core' ),
				'required' => true,
			),
			array(
				'id'       => 'fsweb_email',
				'type'     => 'email',
				'label'    => __( 'Your email', 'fsweb-comments-core' ),
				'required' => '1' === get_option( 'fsweb_comments_core_require_email', '1' ),
			),
			array(
				'id'       => 'fsweb_rating',
				'type'     => 'rating',
				'label'    => __( 'Your rating', 'fsweb-comments-core' ),
				'required' => true,
			),
			array(
				'id'       => 'fsweb_description',
				'type'     => 'textarea',
				'label'    => __( 'Your review', 'fsweb-comments-core' ),
				'required' => true,
			),
		);

		/**
		 * Filter the fields of the review form.
		 *
		 * @since 1.0.0
		 * @param array $fields Field definitions.
		 */
		return (array) apply_filters( 'fsweb_cc_form_fields', $fields );
	}

	/**
	 * Render the form.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'  => '',
				'button' => __( 'Submit Review', 'fsweb-comments-core' ),
			),
			$atts,
			'fsweb_review_form'
		);

		if ( 'closed' === get_option( 'fsweb_comments_core_form_status', 'open' ) || ! $this->forms_open() ) {
			return '<p class="fsweb-review-form-closed">' . esc_html__( 'Review submissions are closed.', 'fsweb-comments-core' ) . '</p>';
		}

		Fsweb_Comments_Core_Public::enqueue_form_assets();

		$fields = $this->get_fields();

		ob_start();
		?>
		<div class="fsweb-review-form-wrap">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h3 class="fsweb-review-form-title"><?php echo esc_html( $atts['title'] ); ?></h3>
			<?php endif; ?>

			<form class="fsweb-review-form" method="post" enctype="multipart/form-data" data-fsweb-form<?php foreach ( $this->form_attributes() as $fsweb_attribute_name => $fsweb_attribute_value ) : ?> <?php echo esc_attr( $fsweb_attribute_name ) . '="' . esc_attr( $fsweb_attribute_value ) . '"'; ?><?php endforeach; ?>>
				<?php wp_nonce_field( 'fsweb_review_submit', 'fsweb_nonce' ); ?>

				<?php foreach ( $fields as $field ) : ?>
					<div class="fsweb-review-form-field fsweb-review-form-field--<?php echo esc_attr( $field['type'] ); ?>">
						<label class="fsweb-review-form-label" for="<?php echo esc_attr( $field['id'] ); ?>">
							<?php echo esc_html( $field['label'] ); ?>
							<?php if ( ! empty( $field['required'] ) ) : ?>
								<span class="fsweb-review-form-required">*</span>
							<?php endif; ?>
						</label>
						<?php $this->render_field( $field ); ?>
						<span class="fsweb-review-form-error"></span>
					</div>
				<?php endforeach; ?>

				<div class="fsweb-review-form-field">
					<button type="submit" class="fsweb-review-form-submit">
						<?php echo esc_html( $atts['button'] ); ?>
					</button>
					<span class="fsweb-review-form-spinner" hidden></span>
				</div>

				<div class="fsweb-review-form-message" role="alert" hidden></div>

				<div class="fsweb-review-form-success" hidden>
					<div class="fsweb-review-form-success-icon" aria-hidden="true">&#10003;</div>
					<p class="fsweb-review-form-success-message"></p>
					<button type="button" class="fsweb-review-form-success-btn">
						<?php esc_html_e( 'Submit another review', 'fsweb-comments-core' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a single field.
	 *
	 * @param array $field Field definition.
	 * @return void
	 */
	protected function render_field( $field ) {
		$id       = isset( $field['id'] ) ? sanitize_key( $field['id'] ) : '';
		$type     = isset( $field['type'] ) ? $field['type'] : 'text';
		$required = ! empty( $field['required'] ) ? ' required' : '';
		$name     = 'fsweb_field_' . $id;

		/**
		 * Render a field the free edition does not know.
		 *
		 * Returning a non-empty string short-circuits the built-in renderer.
		 *
		 * @since 1.0.0
		 * @param string $html     Field markup.
		 * @param array  $field    Field definition.
		 * @param string $name     Input name.
		 * @param string $required Required attribute.
		 */
		$custom = apply_filters( 'fsweb_cc_form_field_html', '', $field, $name, $required );

		if ( '' !== $custom ) {
			echo wp_kses( $custom, Fsweb_Comments_Core_Public::allowed_form_field_markup() );
			return;
		}

		switch ( $type ) {
			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="5" maxlength="2000"%3$s></textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					$required // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string.
				);
				break;

			case 'email':
				printf(
					'<input type="email" id="%1$s" name="%2$s" autocomplete="email"%3$s>',
					esc_attr( $id ),
					esc_attr( $name ),
					$required // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string.
				);
				break;

			case 'url':
				printf(
					'<input type="url" id="%1$s" name="%2$s"%3$s>',
					esc_attr( $id ),
					esc_attr( $name ),
					$required // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string.
				);
				break;

			case 'image':
				printf(
					'<div class="fsweb-review-image-upload"><input type="file" id="%1$s" name="%2$s" accept="image/jpeg,image/png,image/webp,image/gif" class="fsweb-review-image-input"%3$s><span class="fsweb-review-image-placeholder" aria-hidden="true"><span class="fsweb-review-image-plus">+</span><span class="fsweb-review-image-text">%4$s</span></span><img class="fsweb-review-image-preview" alt="" hidden></div>',
					esc_attr( $id ),
					esc_attr( $name ),
					$required, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string.
					esc_html__( 'Click to upload', 'fsweb-comments-core' )
				);
				break;

			case 'rating':
				$this->render_rating( $id, $name, $required );
				break;

			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $required . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string.
				echo '<option value="">' . esc_html__( '— Select —', 'fsweb-comments-core' ) . '</option>';
				$options = isset( $field['options'] ) ? $field['options'] : array();
				if ( is_string( $options ) ) {
					$options = array_filter( array_map( 'trim', explode( "\n", $options ) ) );
				}
				foreach ( (array) $options as $option ) {
					echo '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
				}
				echo '</select>';
				break;

			default:
				printf(
					'<input type="text" id="%1$s" name="%2$s" maxlength="120" autocomplete="name"%3$s>',
					esc_attr( $id ),
					esc_attr( $name ),
					$required // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string.
				);
				break;
		}
	}

	/**
	 * Render the star rating input.
	 *
	 * @param string $id       Field id.
	 * @param string $name     Input name.
	 * @param string $required Required attribute.
	 * @return void
	 */
	protected function render_rating( $id, $name, $required ) {
		echo '<div class="fsweb-review-rating-input" role="radiogroup">';
		for ( $i = 5; $i >= 1; $i-- ) {
			printf(
				'<input type="radio" id="%1$s" name="%2$s" value="%3$d"%4$s><label for="%1$s" title="%3$d">&#9733;<span class="screen-reader-text">%5$s</span></label>',
				esc_attr( $id . '_' . $i ),
				esc_attr( $name ),
				(int) $i,
				$required, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attribute string.
				/* translators: %d: number of stars. */
				esc_html( sprintf( __( '%d stars', 'fsweb-comments-core' ), $i ) )
			);
		}
		echo '</div>';
	}

	/**
	 * Extra attributes for the <form> tag.
	 *
	 * Names are validated against the HTML attribute syntax; values are
	 * escaped when the template prints them.
	 *
	 * @return array Valid attribute name => value pairs.
	 */
	protected function form_attributes() {
		/**
		 * Filter extra attributes printed on the review form.
		 *
		 * Return an associative array of name => value pairs, not an escaped
		 * attribute string.
		 *
		 * @since 1.0.0
		 * @since 1.0.9 Filter callbacks receive and return an array of attributes.
		 *
		 * @param array $attributes Attributes added to the form tag.
		 */
		$attributes = (array) apply_filters( 'fsweb_cc_form_attributes', array() );
		$valid      = array();

		foreach ( $attributes as $name => $value ) {
			$name = (string) $name;

			if ( ! preg_match( '/^[a-zA-Z_:][a-zA-Z0-9_:.-]*$/', $name ) ) {
				continue;
			}

			$valid[ $name ] = (string) $value;
		}

		return $valid;
	}

	/**
	 * Whether submissions are open.
	 *
	 * @return bool
	 */
	protected function forms_open() {
		/**
		 * Filter whether the review form accepts new submissions.
		 *
		 * @since 1.0.0
		 * @param bool $open Whether the form is open.
		 */
		return (bool) apply_filters( 'fsweb_cc_forms_open', true );
	}

	/**
	 * Store an uploaded review photo.
	 *
	 * The file is validated by extension and real mime type, size limited and
	 * handed to wp_handle_upload(); the resulting attachment is returned so the
	 * caller can set it as the review thumbnail.
	 *
	 * @param string $field_name Input name of the file field.
	 * @return int|WP_Error Attachment id on success.
	 */
	protected function handle_photo_upload( $field_name ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The nonce is checked by the caller and wp_handle_upload() validates the file.
		$file = isset( $_FILES[ $field_name ] ) ? $_FILES[ $field_name ] : null;
		// phpcs:enable

		if ( ! is_array( $file ) || ! isset( $file['tmp_name'], $file['error'], $file['size'], $file['name'] ) ) {
			return new WP_Error( 'fsweb_photo_missing', __( 'We could not read the image. Please try again.', 'fsweb-comments-core' ) );
		}

		if ( UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
			return new WP_Error( 'fsweb_photo_missing', __( 'We could not read the image. Please try again.', 'fsweb-comments-core' ) );
		}

		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new WP_Error( 'fsweb_photo_upload', __( 'The image upload failed. Please try again.', 'fsweb-comments-core' ) );
		}

		if ( (int) $file['size'] > 2 * MB_IN_BYTES ) {
			return new WP_Error( 'fsweb_photo_size', __( 'The image must be 2 MB or smaller.', 'fsweb-comments-core' ) );
		}

		$allowed = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
		);

		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );

		if ( empty( $checked['type'] ) || empty( $checked['ext'] ) ) {
			return new WP_Error( 'fsweb_photo_type', __( 'Please upload a JPG, PNG, GIF or WebP image.', 'fsweb-comments-core' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$upload = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => $allowed,
			)
		);

		if ( ! is_array( $upload ) || isset( $upload['error'] ) || empty( $upload['file'] ) ) {
			return new WP_Error( 'fsweb_photo_upload', __( 'The image upload failed. Please try again.', 'fsweb-comments-core' ) );
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $upload['type'],
				'post_title'     => sanitize_file_name( basename( $upload['file'] ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file']
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return new WP_Error( 'fsweb_photo_attachment', __( 'We could not save the image. Please try again.', 'fsweb-comments-core' ) );
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );

		if ( $metadata ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		return (int) $attachment_id;
	}

	/**
	 * Handle the AJAX submission.
	 *
	 * @return void
	 */
	public function ajax_submit() {
		/**
		 * Whether the free handler should process the submission.
		 *
		 * The PRO add-on returns false here so its builder-driven handler — with
		 * the custom fields, uploads and redirects — is the one that runs.
		 *
		 * @since 1.0.0
		 * @param bool $handle Whether to handle the request.
		 */
		if ( ! apply_filters( 'fsweb_cc_form_handle_submission', true ) ) {
			return;
		}

		check_ajax_referer( 'fsweb_review_submit', 'fsweb_nonce' );

		if ( ! $this->forms_open() ) {
			wp_send_json_error( array( 'message' => __( 'Review submissions are closed.', 'fsweb-comments-core' ) ), 403 );
		}

		$name  = isset( $_POST['fsweb_field_fsweb_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fsweb_field_fsweb_name'] ) ) : '';
		$email = isset( $_POST['fsweb_field_fsweb_email'] ) ? sanitize_email( wp_unslash( $_POST['fsweb_field_fsweb_email'] ) ) : '';
		$text  = isset( $_POST['fsweb_field_fsweb_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fsweb_field_fsweb_description'] ) ) : '';
		$stars = isset( $_POST['fsweb_field_fsweb_rating'] ) ? min( 5, max( 0, absint( wp_unslash( $_POST['fsweb_field_fsweb_rating'] ) ) ) ) : 0;

		if ( '' === $name || '' === $text || 0 === $stars || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill in every required field.', 'fsweb-comments-core' ) ), 400 );
		}

		$photo_id = 0;

		if ( isset( $_FILES['fsweb_field_fsweb_photo'] ) && isset( $_FILES['fsweb_field_fsweb_photo']['error'] ) && UPLOAD_ERR_NO_FILE !== (int) $_FILES['fsweb_field_fsweb_photo']['error'] ) {
			$photo = $this->handle_photo_upload( 'fsweb_field_fsweb_photo' );

			if ( is_wp_error( $photo ) ) {
				wp_send_json_error( array( 'message' => $photo->get_error_message() ), 400 );
			}

			$photo_id = (int) $photo;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'fsweb_review',
				'post_status'  => 'pending',
				'post_title'   => $name,
				'post_content' => $text,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'We could not save your review. Please try again.', 'fsweb-comments-core' ) ), 500 );
		}

		update_post_meta( $post_id, 'fsweb_name', $name );
		update_post_meta( $post_id, 'fsweb_email', $email );
		update_post_meta( $post_id, 'fsweb_rating', $stars );
		update_post_meta( $post_id, 'fsweb_description', $text );

		if ( $photo_id > 0 ) {
			set_post_thumbnail( $post_id, $photo_id );
		}

		/**
		 * Fires after a review is submitted from the front end.
		 *
		 * @since 1.0.0
		 * @param int   $post_id New review ID.
		 * @param array $data    Submitted values.
		 */
		do_action(
			'fsweb_cc_review_submitted',
			$post_id,
			array(
				'name'   => $name,
				'email'  => $email,
				'rating' => $stars,
				'review' => $text,
			)
		);

		wp_send_json_success(
			array(
				'message' => __( 'Thank you! Your review was submitted and is awaiting moderation.', 'fsweb-comments-core' ),
			)
		);
	}
}
