<?php
/**
 * FSWEB Comments Core — Review Editor
 *
 * Adds the Review Details meta box and custom list columns.
 *
 * @package    Fsweb_Comments_Core
 * @subpackage Fsweb_Comments_Core/admin
 * @since      1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Review Editor — meta box, save and columns.
 */
class Fsweb_Comments_Core_Review_Editor {

	/**
	 * Register the hooks.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_fsweb_review', array( $this, 'save_meta_box' ) );

		add_filter( 'manage_fsweb_review_posts_columns', array( $this, 'custom_columns' ) );
		add_action( 'manage_fsweb_review_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Add the Review Details meta box.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			'fsweb_review_details',
			__( 'Review Details', 'fsweb-comments-core' ),
			array( $this, 'render_meta_box' ),
			'fsweb_review',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Post being edited.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'fsweb_review_editor', 'fsweb_review_editor_nonce' );

		$fields = $this->get_fields();

		echo '<div class="fsweb-review-editor-fields">';
		foreach ( $fields as $field ) {
			$this->render_field( $field, $post );
		}
		echo '</div>';
	}

	/**
	 * Save the meta box values.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_meta_box( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['fsweb_review_editor_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['fsweb_review_editor_nonce'] ) ), 'fsweb_review_editor' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields          = $this->get_fields();
		$name_handled    = false;
		$content_handled = false;

		foreach ( $fields as $field ) {
			$field_id   = $field['id'];
			$field_type = $field['type'];

			if ( 'image' === $field_type ) {
				continue;
			}

			// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per type below.
			$raw = isset( $_POST[ 'fsweb_field_' . $field_id ] ) ? wp_unslash( $_POST[ 'fsweb_field_' . $field_id ] ) : '';
			// phpcs:enable

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
					$value = min( 5, max( 0, (int) $raw ) );
					break;
				default:
					$value = sanitize_text_field( $raw );
					break;
			}

			if ( ! $name_handled && ( 'text' === $field_type || false !== stripos( $field_id, 'name' ) ) ) {
				$name_handled = true;
				if ( get_post_field( 'post_title', $post_id ) !== $value ) {
					remove_action( 'save_post_fsweb_review', array( $this, 'save_meta_box' ) );
					wp_update_post(
						array(
							'ID'         => $post_id,
							'post_title' => $value,
						)
					);
					add_action( 'save_post_fsweb_review', array( $this, 'save_meta_box' ) );
				}
				continue;
			}

			if ( ! $content_handled && ( 'textarea' === $field_type || false !== stripos( $field_id, 'desc' ) || false !== stripos( $field_id, 'review' ) ) ) {
				$content_handled = true;
				if ( get_post_field( 'post_content', $post_id ) !== $value ) {
					remove_action( 'save_post_fsweb_review', array( $this, 'save_meta_box' ) );
					wp_update_post(
						array(
							'ID'           => $post_id,
							'post_content' => $value,
						)
					);
					add_action( 'save_post_fsweb_review', array( $this, 'save_meta_box' ) );
				}
				continue;
			}

			update_post_meta( $post_id, $field_id, $value );
		}
	}

	/**
	 * Fields shown in the editor.
	 *
	 * The PRO add-on filters this list to reuse the fields of the form
	 * builder, so a review created by a custom form is fully editable.
	 *
	 * @return array
	 */
	private function get_fields() {
		$fields = array(
			array(
				'id'       => 'fsweb_name',
				'type'     => 'text',
				'label'    => __( 'Name', 'fsweb-comments-core' ),
				'required' => true,
			),
			array(
				'id'       => 'fsweb_email',
				'type'     => 'email',
				'label'    => __( 'Email', 'fsweb-comments-core' ),
				'required' => false,
			),
			array(
				'id'       => 'fsweb_rating',
				'type'     => 'rating',
				'label'    => __( 'Rating', 'fsweb-comments-core' ),
				'required' => true,
			),
			array(
				'id'       => 'fsweb_description',
				'type'     => 'textarea',
				'label'    => __( 'Review', 'fsweb-comments-core' ),
				'required' => true,
			),
		);

		/**
		 * Filter the fields displayed on the review edit screen.
		 *
		 * @since 1.0.0
		 * @param array $fields Field definitions.
		 */
		return (array) apply_filters( 'fsweb_cc_review_editor_fields', $fields );
	}

	/**
	 * Resolve the current value of a field.
	 *
	 * @param array   $field Field definition.
	 * @param WP_Post $post  Post object.
	 * @return string
	 */
	private function get_field_value( $field, $post ) {
		$id   = $field['id'];
		$type = $field['type'];

		static $name_resolved    = false;
		static $content_resolved = false;

		if ( ( 'text' === $type || false !== stripos( $id, 'name' ) ) && ! $name_resolved ) {
			$name_resolved = true;
			return get_post_field( 'post_title', $post );
		}

		if ( ( 'textarea' === $type || false !== stripos( $id, 'desc' ) || false !== stripos( $id, 'review' ) ) && ! $content_resolved ) {
			$content_resolved = true;
			return get_post_field( 'post_content', $post );
		}

		return (string) get_post_meta( $post->ID, $id, true );
	}

	/**
	 * Render one field.
	 *
	 * @param array   $field Field definition.
	 * @param WP_Post $post  Post object.
	 * @return void
	 */
	private function render_field( $field, $post ) {
		$type = $field['type'];

		if ( 'image' === $type ) {
			return;
		}

		$value    = $this->get_field_value( $field, $post );
		$field_id = esc_attr( $field['id'] );
		$name     = 'fsweb_field_' . $field_id;
		$required = ! empty( $field['required'] );
		?>
		<div class="fsweb-review-editor-field">
			<label class="fsweb-review-editor-label" for="<?php echo esc_attr( $name ); ?>">
				<?php echo esc_html( $field['label'] ); ?>
				<?php if ( $required ) : ?>
					<span class="fsweb-review-editor-required">*</span>
				<?php endif; ?>
			</label>

			<div class="fsweb-review-editor-input">
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
						$this->render_rating_input( $name, $value );
						break;

					case 'date':
						echo '<input type="date" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
						break;

					case 'select':
						echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" class="regular-text">';
						echo '<option value="">' . esc_html__( '— Select —', 'fsweb-comments-core' ) . '</option>';
						if ( ! empty( $field['options'] ) ) {
							$options = explode( "\n", $field['options'] );
							foreach ( $options as $option ) {
								$option = trim( $option );
								if ( '' !== $option ) {
									echo '<option value="' . esc_attr( $option ) . '"' . selected( $value, $option, false ) . '>' . esc_html( $option ) . '</option>';
								}
							}
						}
						echo '</select>';
						break;

					default:
						echo '<input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="widefat">';
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Star rating radio group.
	 *
	 * @param string $name  Input name.
	 * @param string $value Current rating.
	 * @return void
	 */
	private function render_rating_input( $name, $value ) {
		$current = (int) $value;
		?>
		<div class="fsweb-review-editor-stars">
			<span class="fsweb-review-editor-rating-badge">
				(<span class="fsweb-review-editor-rating-current"><?php echo esc_html( $current ); ?></span>/5)
			</span>
			<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
				<input type="radio"
					id="<?php echo esc_attr( $name . '_' . $i ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					value="<?php echo (int) $i; ?>"
					<?php checked( $current, $i ); ?>>
				<label for="<?php echo esc_attr( $name . '_' . $i ); ?>" title="<?php echo (int) $i; ?>">&#9733;</label>
			<?php endfor; ?>
		</div>
		<?php
	}

	/**
	 * Add the custom columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function custom_columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['fsweb_image'] = __( 'Photo', 'fsweb-comments-core' );
				$new[ $key ]        = $label;
				continue;
			}
			if ( 'date' === $key ) {
				$new['fsweb_rating'] = __( 'Rating', 'fsweb-comments-core' );
				$new['fsweb_email']  = __( 'Email', 'fsweb-comments-core' );
			}
			$new[ $key ] = $label;
		}

		return $new;
	}

	/**
	 * Render a custom column.
	 *
	 * @param string $column  Column id.
	 * @param int    $post_id Post id.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'fsweb_image':
				$thumb = get_the_post_thumbnail( $post_id, array( 40, 40 ) );
				if ( $thumb ) {
					echo wp_kses_post( $thumb );
				} else {
					echo '<span class="fsweb-cc-muted">—</span>';
				}
				break;

			case 'fsweb_rating':
				$rating = (int) get_post_meta( $post_id, 'fsweb_rating', true );
				if ( $rating > 0 ) {
					echo wp_kses_post(
						'<span class="fsweb-cc-stars">'
						. str_repeat( '★', min( 5, $rating ) )
						. str_repeat( '☆', max( 0, 5 - $rating ) )
						. '</span>'
					);
				} else {
					echo '<span class="fsweb-cc-muted">—</span>';
				}
				break;

			case 'fsweb_email':
				$email = get_post_meta( $post_id, 'fsweb_email', true );
				if ( ! empty( $email ) ) {
					echo '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>';
				} else {
					echo '<span class="fsweb-cc-muted">—</span>';
				}
				break;
		}
	}
}
