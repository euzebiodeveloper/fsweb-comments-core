<?php
/**
 * FSWWP Comments Core — Review Editor
 *
 * Customises the fswwp_review edit screen with a meta box that displays
 * all form fields, plus custom columns on the list page.
 *
 * @package    Fswwp_Comments_Core
 * @subpackage Fswwp_Comments_Core/admin
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Review Editor — meta box, save, columns.
 */
class Fswwp_Comments_Core_Review_Editor {

	/**
	 * Constructor — registers hooks.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_fswwp_review', array( $this, 'save_meta_box' ) );

		// Custom columns on the list page.
		add_filter( 'manage_fswwp_review_posts_columns', array( $this, 'custom_columns' ) );
		add_action( 'manage_fswwp_review_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-fswwp_review_sortable_columns', array( $this, 'sortable_columns' ) );
	}

	// ------------------------------------------------------------------//
	//  Meta Box
	// ------------------------------------------------------------------//

	/**
	 * Add the "Review Details" meta box.
	 *
	 * @since    1.0.0
	 */
	public function add_meta_box() {
		add_meta_box(
			'fswwp_review_details',
			__( 'Review Details', 'fswwp-comments-core' ),
			array( $this, 'render_meta_box' ),
			'fswwp_review',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * @since    1.0.0
	 * @param    \WP_Post $post    The post being edited.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'fswwp_review_editor', 'fswwp_review_editor_nonce' );

		$fields = $this->get_form_fields();

		if ( empty( $fields ) ) {
			echo '<p>' . esc_html__( 'No form fields configured. Set up a form in the Form Builder first.', 'fswwp-comments-core' ) . '</p>';
			return;
		}

		echo '<div class="fswwp-review-editor-fields">';
		foreach ( $fields as $field ) {
			$this->render_field( $field, $post );
		}
		echo '</div>';
	}

	/**
	 * Save meta box data.
	 *
	 * @since    1.0.0
	 * @param    int $post_id    Post ID.
	 */
	public function save_meta_box( $post_id ) {
		// Bail on autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check nonce.
		if ( ! isset( $_POST['fswwp_review_editor_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['fswwp_review_editor_nonce'] ), 'fswwp_review_editor' ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = $this->get_form_fields();
		$name_handled    = false;
		$content_handled = false;

		foreach ( $fields as $field ) {
			$field_id   = $field['id'];
			$field_type = $field['type'];

			// Skip image — file uploads should use media library.
			if ( 'image' === $field_type ) {
				continue;
			}

			// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per-type in switch below.
			$raw = isset( $_POST[ 'fswwp_field_' . $field_id ] ) ? wp_unslash( $_POST[ 'fswwp_field_' . $field_id ] ) : '';
			// phpcs:enable

			// Sanitise by type.
			switch ( $field_type ) {
				case 'email':
					$value = sanitize_email( $raw );
					break;
				case 'url':
					$value = esc_url_raw( $raw );
					break;
				case 'textarea':
					$value = sanitize_textarea_field( $raw );
					break;
				case 'rating':
					$value = min( 5, max( 1, (int) $raw ) );
					break;
				default:
					$value = sanitize_text_field( $raw );
					break;
			}

			// Match name field: first text field, or field with 'name' in ID.
			if ( ! $name_handled && ( 'text' === $field_type || false !== stripos( $field_id, 'name' ) ) ) {
				$name_handled = true;
				if ( get_post_field( 'post_title', $post_id ) !== $value ) {
					remove_action( 'save_post_fswwp_review', array( $this, 'save_meta_box' ) );
					wp_update_post( array(
						'ID'         => $post_id,
						'post_title' => $value,
					) );
					add_action( 'save_post_fswwp_review', array( $this, 'save_meta_box' ) );
				}
				continue;
			}

			// Match content field: first textarea, or field with 'desc'/'review' in ID.
			if ( ! $content_handled && ( 'textarea' === $field_type || false !== stripos( $field_id, 'desc' ) || false !== stripos( $field_id, 'review' ) ) ) {
				$content_handled = true;
				if ( get_post_field( 'post_content', $post_id ) !== $value ) {
					remove_action( 'save_post_fswwp_review', array( $this, 'save_meta_box' ) );
					wp_update_post( array(
						'ID'           => $post_id,
						'post_content' => $value,
					) );
					add_action( 'save_post_fswwp_review', array( $this, 'save_meta_box' ) );
				}
				continue;
			}

			// Store everything else as post meta.
			update_post_meta( $post_id, $field_id, $value );
		}
	}

	// ------------------------------------------------------------------//
	//  Field rendering
	// ------------------------------------------------------------------//

	/**
	 * Get form fields from the stored form configuration.
	 *
	 * Falls back to default fields if no form exists.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function get_form_fields() {
		$forms = get_option( 'fswwp_review_forms', array() );

		if ( empty( $forms ) ) {
			return $this->default_fields();
		}

		$first = reset( $forms );

		// Flatten multi-step fields.
		if ( ! empty( $first['multi_step'] ) && ! empty( $first['steps'] ) ) {
			$all = array();
			foreach ( $first['steps'] as $step ) {
				foreach ( $step['fields'] as $f ) {
					$all[] = $f;
				}
			}
			return $all;
		}

		if ( ! empty( $first['fields'] ) ) {
			return $first['fields'];
		}

		return $this->default_fields();
	}

	/**
	 * Default fields when no form is configured.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	private function default_fields() {
		return array(
			array(
				'id'       => 'name',
				'type'     => 'text',
				'label'    => __( 'Name', 'fswwp-comments-core' ),
				'required' => true,
			),
			array(
				'id'       => 'email',
				'type'     => 'email',
				'label'    => __( 'Email', 'fswwp-comments-core' ),
				'required' => false,
			),
			array(
				'id'       => 'rating',
				'type'     => 'rating',
				'label'    => __( 'Rating', 'fswwp-comments-core' ),
				'required' => true,
			),
			array(
				'id'       => 'description',
				'type'     => 'textarea',
				'label'    => __( 'Description', 'fswwp-comments-core' ),
				'required' => true,
			),
		);
	}

	/**
	 * Resolve the current value of a field for a given post.
	 *
	 * @since    1.0.0
	 * @param    array    $field    Field config.
	 * @param    \WP_Post $post     Post object.
	 * @return   string
	 */
	private function get_field_value( $field, $post ) {
		$id   = $field['id'];
		$type = $field['type'];

		// Match by type + keyword — same logic as the AJAX submit handler.
		if ( 'text' === $type || false !== stripos( $id, 'name' ) ) {
			// Only return post_title for the first text/name field.
			static $name_resolved = false;
			if ( ! $name_resolved ) {
				$name_resolved = true;
				return get_post_field( 'post_title', $post );
			}
		}
		if ( 'textarea' === $type || false !== stripos( $id, 'desc' ) || false !== stripos( $id, 'review' ) ) {
			static $content_resolved = false;
			if ( ! $content_resolved ) {
				$content_resolved = true;
				return get_post_field( 'post_content', $post );
			}
		}

		return (string) get_post_meta( $post->ID, $id, true );
	}

	/**
	 * Render a single field row inside the meta box.
	 *
	 * @since    1.0.0
	 * @param    array    $field    Field config.
	 * @param    \WP_Post $post     Post object.
	 */
	private function render_field( $field, $post ) {
		$type = $field['type'];

		// Image is handled by the Featured Image meta box — skip.
		if ( 'image' === $type ) {
			return;
		}

		$value    = $this->get_field_value( $field, $post );
		$field_id = esc_attr( $field['id'] );
		$name     = 'fswwp_field_' . $field_id;
		$required = ! empty( $field['required'] );
		?>
		<div class="fswwp-review-editor-field">
			<label class="fswwp-review-editor-label" for="<?php echo esc_attr( $name ); ?>">
				<?php echo esc_html( $field['label'] ); ?>
				<?php if ( $required ) : ?>
					<span class="fswwp-review-editor-required">*</span>
				<?php endif; ?>
			</label>

			<div class="fswwp-review-editor-input">
				<?php
				switch ( $type ) {
					case 'textarea':
						echo '<textarea id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" rows="4" class="widefat">' . esc_textarea( $value ) . '</textarea>';
						break;

					case 'email':
						echo '<input type="email" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="widefat">';
						break;

					case 'url':
						echo '<input type="url" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="widefat">';
						break;

					case 'rating':
						$this->render_rating_input( $name, $field_id, $value, $required );
						break;

					case 'date':
						echo '<input type="date" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
						break;

					case 'select':
						echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" class="regular-text">';
						echo '<option value="">' . esc_html__( '— Select —', 'fswwp-comments-core' ) . '</option>';
						if ( ! empty( $field['options'] ) ) {
							$opts = explode( "\n", $field['options'] );
							foreach ( $opts as $opt ) {
								$opt = trim( $opt );
								if ( '' !== $opt ) {
									echo '<option value="' . esc_attr( $opt ) . '"' . selected( $value, $opt, false ) . '>' . esc_html( $opt ) . '</option>';
								}
							}
						}
						echo '</select>';
						break;

					default: // text + name.
						echo '<input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="widefat">';
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a star-rating input (radio group).
	 *
	 * @since    1.0.0
	 * @param    string $name      Input name attr.
	 * @param    string $field_id  Field identifier.
	 * @param    string $value     Current rating (1-5).
	 * @param    bool   $required  Whether a rating is required.
	 */
	private function render_rating_input( $name, $field_id, $value, $required ) {
		$current = (int) $value;
		?>
		<div class="fswwp-review-editor-stars" data-field="<?php echo esc_attr( $field_id ); ?>">
			<span class="fswwp-review-editor-rating-badge">
				(<span class="fswwp-review-editor-rating-current"><?php echo esc_html( $current ); ?></span>/5)
			</span>
			<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
				<input type="radio"
					id="<?php echo esc_attr( $name . '_' . $i ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo (int) $i; ?>"
					<?php checked( $current, $i ); ?>
					<?php if ( $required && 1 === $i ) { echo 'required'; } ?>>
				<label for="<?php echo esc_attr( $name . '_' . $i ); ?>" title="<?php echo (int) $i; ?>">&#9733;</label>
			<?php endfor; ?>
		</div>
		<?php
	}

	/**
	 * Render an image field — preview + current URL.
	 *
	 * @since    1.0.0
	 * @param    string $name      Input name attr.
	 * @param    string $field_id  Field identifier.
	 * @param    string $value     Current image URL or attachment ID.
	 */
	private function render_image_field( $name, $field_id, $value ) {
		$thumb_url = '';

		if ( ! empty( $value ) ) {
			// Check if it looks like a URL.
			if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
				$thumb_url = $value;
			} elseif ( is_numeric( $value ) ) {
				$thumb_url = wp_get_attachment_image_url( (int) $value, 'thumbnail' );
			}
		}
		?>
		<div class="fswwp-review-editor-image">
			<div class="fswwp-review-editor-image-preview">
				<?php if ( $thumb_url ) : ?>
					<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" style="max-width:150px;max-height:150px;display:block;margin-bottom:8px;border-radius:4px;">
				<?php else : ?>
					<div class="fswwp-review-editor-image-empty">
						<?php esc_html_e( 'No image', 'fswwp-comments-core' ); ?>
					</div>
				<?php endif; ?>
			</div>
			<input type="text" id="<?php echo esc_attr( $name ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				class="widefat"
				placeholder="<?php esc_attr_e( 'Image URL or attachment ID', 'fswwp-comments-core' ); ?>">
		</div>
		<?php
	}

	// ------------------------------------------------------------------//
	//  Custom Columns
	// ------------------------------------------------------------------//

	/**
	 * Customise the list-table columns for fswwp_review.
	 *
	 * @since    1.0.0
	 * @param    array $columns    Existing columns.
	 * @return   array
	 */
	public function custom_columns( $columns ) {
		$new = array();

		// Rebuild: cb → image → title → rating → email → category → date.
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['fswwp_image']  = __( 'Photo', 'fswwp-comments-core' );
				$new[ $key ]         = $label;
				continue;
			}
			if ( 'date' === $key ) {
				$new['fswwp_rating'] = __( 'Rating', 'fswwp-comments-core' );
				$new['fswwp_email']  = __( 'Email', 'fswwp-comments-core' );
			}
			$new[ $key ] = $label;
		}

		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @since    1.0.0
	 * @param    string $column     Column ID.
	 * @param    int    $post_id    Post ID.
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'fswwp_image':
				$thumb = get_the_post_thumbnail( $post_id, array( 40, 40 ) );
				if ( $thumb ) {
					echo wp_kses_post( $thumb );
				} else {
					// Try image field from meta.
					$img = get_post_meta( $post_id, 'fswwp_image', true );
					if ( ! empty( $img ) ) {
						$url = filter_var( $img, FILTER_VALIDATE_URL ) ? $img : wp_get_attachment_image_url( (int) $img, array( 40, 40 ) );
						if ( $url ) {
							echo '<img src="' . esc_url( $url ) . '" width="40" height="40" style="border-radius:4px;object-fit:cover;" alt="">';
						}
					}
				}
				break;

			case 'fswwp_rating':
				$rating = (int) get_post_meta( $post_id, 'fswwp_rating', true );
				if ( $rating > 0 ) {
					echo wp_kses_post('<span style="color:#f0c040;font-size:16px;letter-spacing:1px;">'
						. str_repeat( '★', $rating )
						. str_repeat( '☆', 5 - $rating )
						. '</span>' );
				} else {
					echo '<span style="color:#c3c4c7;">—</span>';
				}
				break;

			case 'fswwp_email':
				$email = get_post_meta( $post_id, 'fswwp_email', true );
				if ( ! empty( $email ) ) {
					echo '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>';
				} else {
					echo '<span style="color:#c3c4c7;">—</span>';
				}
				break;
		}
	}

	/**
	 * Make custom columns sortable.
	 *
	 * @since    1.0.0
	 * @param    array $columns    Sortable columns.
	 * @return   array
	 */
	public function sortable_columns( $columns ) {
		$columns['fswwp_rating'] = 'fswwp_rating';
		$columns['fswwp_email']  = 'fswwp_email';
		return $columns;
	}
}
