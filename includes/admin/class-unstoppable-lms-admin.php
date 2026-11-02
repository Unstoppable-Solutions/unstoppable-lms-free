<?php
/**
 * Admin functionality.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Admin {

    /**
     * Initialize admin.
     *
     * @since 1.0.0
     */
    public static function init() {
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_lesson_meta_boxes' ) );
        add_action( 'save_post_lms_lesson', array( __CLASS__, 'save_lesson_meta' ), 10, 2 );
        Unstoppable_LMS_Admin_Menus::init();
    }

    /**
     * Add meta boxes for lessons.
     *
     * @since 1.2.4
     */
    public static function add_lesson_meta_boxes() {
        add_meta_box(
            'lms_lesson_materials',
            __( 'Lesson Materials', 'unstoppable-lms' ),
            array( __CLASS__, 'render_materials_meta_box' ),
            'lms_lesson',
            'normal',
            'high'
        );

        add_meta_box(
            'lms_lesson_settings',
            __( 'Lesson Settings', 'unstoppable-lms' ),
            array( __CLASS__, 'render_settings_meta_box' ),
            'lms_lesson',
            'side',
            'default'
        );
    }

    /**
     * Render materials meta box.
     *
     * @since 1.2.4
     * @param WP_Post $post Post object.
     */
    public static function render_materials_meta_box( $post ) {
        wp_nonce_field( 'lms_lesson_meta', 'lms_lesson_meta_nonce' );

        $attachments = get_post_meta( $post->ID, '_lms_attachments', true );
        $embed_ids = get_post_meta( $post->ID, '_lms_embed_ids', true );
        $video_url = get_post_meta( $post->ID, '_lms_video_url', true );

        if ( ! is_array( $attachments ) ) {
            $attachments = array();
        }
        if ( ! is_array( $embed_ids ) ) {
            $embed_ids = array();
        }
        ?>
        <div class="lms-meta-field">
            <label><strong><?php esc_html_e( 'Video URL', 'unstoppable-lms' ); ?></strong></label>
            <input type="url" name="lms_video_url" value="<?php echo esc_url( $video_url ); ?>" class="widefat" placeholder="YouTube, Vimeo, or direct video URL">
        </div>

        <div class="lms-meta-field" style="margin-top:20px;">
            <label><strong><?php esc_html_e( 'File Attachments', 'unstoppable-lms' ); ?></strong></label>
            <p class="description"><?php esc_html_e( 'Add PDFs, documents, or other files for students to view or download.', 'unstoppable-lms' ); ?></p>
            
            <div id="lms-attachments-list" style="margin:10px 0;">
                <?php foreach ( $attachments as $att_id ) : 
                    $att = get_post( $att_id );
                    if ( ! $att ) continue;
                    $is_pdf = get_post_mime_type( $att_id ) === 'application/pdf';
                    $is_embed = in_array( $att_id, $embed_ids );
                    $icon = wp_get_attachment_image_src( $att_id, array( 48, 48 ), true );
                ?>
                <div class="lms-attachment-row" data-id="<?php echo esc_attr( $att_id ); ?>" style="display:flex;align-items:center;gap:10px;padding:8px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;margin-bottom:8px;">
                    <img src="<?php echo esc_url( $icon[0] ); ?>" style="width:32px;height:32px;">
                    <span style="flex:1;"><?php echo esc_html( $att->post_title ); ?></span>
                    <?php if ( $is_pdf ) : ?>
                    <label style="font-size:12px;"><input type="checkbox" name="lms_embed_ids[]" value="<?php echo esc_attr( $att_id ); ?>" <?php checked( $is_embed ); ?>> Embed</label>
                    <?php endif; ?>
                    <button type="button" class="button button-small lms-remove-attachment" style="color:#b32d2e;">&times;</button>
                    <input type="hidden" name="lms_attachment_ids[]" value="<?php echo esc_attr( $att_id ); ?>">
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="button" id="lms-add-attachment">
                <span class="dashicons dashicons-paperclip" style="vertical-align:middle;"></span>
                <?php esc_html_e( 'Add Files', 'unstoppable-lms' ); ?>
            </button>
        </div>

        <script>
        jQuery(function($) {
            var frame;

            $('#lms-add-attachment').on('click', function(e) {
                e.preventDefault();
                
                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: '<?php esc_html_e( 'Select Files', 'unstoppable-lms' ); ?>',
                    button: { text: '<?php esc_html_e( 'Add to Lesson', 'unstoppable-lms' ); ?>' },
                    multiple: true
                });

                frame.on('select', function() {
                    var selection = frame.state().get('selection');
                    selection.each(function(attachment) {
                        var att = attachment.toJSON();
                        var existing = $('#lms-attachments-list').find('[data-id="'+att.id+'"]');
                        if (existing.length) return;

                        var isPdf = att.mime === 'application/pdf';
                        var embedCheckbox = isPdf ? '<label style="font-size:12px;"><input type="checkbox" name="lms_embed_ids[]" value="'+att.id+'" checked> Embed</label>' : '';

                        var html = '<div class="lms-attachment-row" data-id="'+att.id+'" style="display:flex;align-items:center;gap:10px;padding:8px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;margin-bottom:8px;">' +
                            '<img src="'+att.icon+'" style="width:32px;height:32px;">' +
                            '<span style="flex:1;">'+(att.title || att.filename)+'</span>' +
                            embedCheckbox +
                            '<button type="button" class="button button-small lms-remove-attachment" style="color:#b32d2e;">&times;</button>' +
                            '<input type="hidden" name="lms_attachment_ids[]" value="'+att.id+'">' +
                        '</div>';

                        $('#lms-attachments-list').append(html);
                    });
                });

                frame.open();
            });

            $(document).on('click', '.lms-remove-attachment', function() {
                $(this).closest('.lms-attachment-row').remove();
            });
        });
        </script>
        <?php
    }

    /**
     * Render settings meta box.
     *
     * @since 1.2.4
     * @param WP_Post $post Post object.
     */
    public static function render_settings_meta_box( $post ) {
        $discussion = get_post_meta( $post->ID, '_lms_discussion_enabled', true );
        $duration = get_post_meta( $post->ID, '_lms_duration', true );
        $course_id = get_post_meta( $post->ID, '_lms_course_id', true );
        ?>
        <p>
            <label><strong><?php esc_html_e( 'Duration (minutes)', 'unstoppable-lms' ); ?></strong></label><br>
            <input type="number" name="lms_duration" value="<?php echo esc_attr( $duration ); ?>" min="0" style="width:100px;">
        </p>
        <p>
            <label>
                <input type="checkbox" name="lms_discussion_enabled" value="1" <?php checked( $discussion ); ?>>
                <?php esc_html_e( 'Enable Discussion', 'unstoppable-lms' ); ?>
            </label>
        </p>
        <?php if ( $course_id ) : $course = get_post( $course_id ); ?>
        <p>
            <label><strong><?php esc_html_e( 'Course', 'unstoppable-lms' ); ?></strong></label><br>
            <a href="<?php echo admin_url( 'admin.php?page=unstoppable-lms-course-builder&course_id=' . $course_id ); ?>">
                <?php echo $course ? esc_html( $course->post_title ) : 'Course #' . $course_id; ?>
            </a>
        </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Save lesson meta.
     *
     * @since 1.2.4
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public static function save_lesson_meta( $post_id, $post ) {
        // Check nonce - use sanitize before verify
        if ( ! isset( $_POST['lms_lesson_meta_nonce'] ) ) {
            return;
        }
        
        $nonce = sanitize_text_field( wp_unslash( $_POST['lms_lesson_meta_nonce'] ) );
        if ( ! wp_verify_nonce( $nonce, 'lms_lesson_meta' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save video URL
        if ( isset( $_POST['lms_video_url'] ) ) {
            $video_url = esc_url_raw( wp_unslash( $_POST['lms_video_url'] ) );
            update_post_meta( $post_id, '_lms_video_url', $video_url );
        }

        // Save duration - validate range
        if ( isset( $_POST['lms_duration'] ) ) {
            $duration = absint( $_POST['lms_duration'] );
            // Reasonable max: 10000 minutes (about 166 hours)
            if ( $duration > 10000 ) {
                $duration = 10000;
            }
            update_post_meta( $post_id, '_lms_duration', $duration );
        }

        // Save discussion setting
        update_post_meta( $post_id, '_lms_discussion_enabled', isset( $_POST['lms_discussion_enabled'] ) ? 1 : 0 );

        // Save attachments - validate each is a real attachment
        $attachments = array();
        if ( ! empty( $_POST['lms_attachment_ids'] ) && is_array( $_POST['lms_attachment_ids'] ) ) {
            $raw_ids = array_map( 'absint', $_POST['lms_attachment_ids'] );
            foreach ( $raw_ids as $att_id ) {
                if ( $att_id > 0 ) {
                    $attachment = get_post( $att_id );
                    if ( $attachment && 'attachment' === $attachment->post_type ) {
                        $attachments[] = $att_id;
                    }
                }
            }
        }
        update_post_meta( $post_id, '_lms_attachments', $attachments );

        // Save embed IDs - must be subset of attachments
        $embed_ids = array();
        if ( ! empty( $_POST['lms_embed_ids'] ) && is_array( $_POST['lms_embed_ids'] ) ) {
            $raw_embed = array_map( 'absint', $_POST['lms_embed_ids'] );
            $embed_ids = array_intersect( $raw_embed, $attachments );
        }
        update_post_meta( $post_id, '_lms_embed_ids', $embed_ids );
    }

    /**
     * Enqueue admin assets.
     *
     * @since 1.0.0
     * @param string|null $hook Current admin page.
     */
    public static function enqueue_assets( $hook = null ) {
        // Ensure hook is a string
        $hook = is_string( $hook ) ? $hook : '';
        
        $screen = get_current_screen();

        // Only load on LMS pages - bail early if no screen
        if ( ! $screen || ! is_object( $screen ) ) {
            return;
        }
        
        // Safely get screen properties with empty string defaults
        $screen_id = '';
        $post_type = '';
        
        if ( isset( $screen->id ) && is_string( $screen->id ) ) {
            $screen_id = $screen->id;
        }
        
        if ( isset( $screen->post_type ) && is_string( $screen->post_type ) ) {
            $post_type = $screen->post_type;
        }
        
        // Check if we're on an LMS page
        $is_lms_page = false;
        if ( ! empty( $screen_id ) && strpos( $screen_id, 'unstoppable-lms' ) !== false ) {
            $is_lms_page = true;
        }
        if ( ! empty( $post_type ) && strpos( $post_type, 'lms_' ) !== false ) {
            $is_lms_page = true;
        }
        
        if ( ! $is_lms_page ) {
            return;
        }

        // Enqueue media uploader for file attachments
        wp_enqueue_media();

        $css_url = UNSTOPPABLE_LMS_PLUGIN_URL . 'assets/css/admin.css';
        $js_url = UNSTOPPABLE_LMS_PLUGIN_URL . 'assets/js/admin.js';
        
        if ( ! empty( $css_url ) ) {
            wp_enqueue_style(
                'unstoppable-lms-admin',
                $css_url,
                array(),
                UNSTOPPABLE_LMS_VERSION
            );
        }

        if ( ! empty( $js_url ) ) {
            wp_enqueue_script(
                'unstoppable-lms-admin',
                $js_url,
                array( 'jquery' ),
                UNSTOPPABLE_LMS_VERSION,
                true
            );
            
            wp_localize_script( 'unstoppable-lms-admin', 'unstoppableLMSAdmin', array(
                'apiUrl'    => rest_url( 'unstoppable-lms/v1/' ),
                'nonce'     => wp_create_nonce( 'wp_rest' ),
                'pluginUrl' => UNSTOPPABLE_LMS_PLUGIN_URL,
            ) );
        }
    }
}
