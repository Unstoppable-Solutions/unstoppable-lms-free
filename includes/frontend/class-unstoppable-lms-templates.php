<?php
/**
 * Frontend template handling.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Templates class.
 *
 * Handles frontend template loading and asset enqueueing.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Templates {

    /**
     * Initialize templates.
     *
     * @since 1.0.0
     */
    public static function init() {
        add_filter( 'template_include', array( __CLASS__, 'load_template' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_filter( 'the_content', array( __CLASS__, 'append_lesson_content' ), 20 );
    }

    /**
     * Append lesson materials and discussion to lesson content.
     *
     * @since 1.2.3
     * @param string $content Post content.
     * @return string Modified content.
     */
    public static function append_lesson_content( string $content ): string {
        // Handle course pages
        if ( is_singular( 'lms_course' ) ) {
            return self::append_course_content( $content );
        }

        // Handle lesson pages
        if ( ! is_singular( 'lms_lesson' ) ) {
            return $content;
        }

        $lesson_id = get_the_ID();
        if ( ! $lesson_id ) {
            return $content;
        }

        // Add video if set
        $video_url = get_post_meta( $lesson_id, '_lms_video_url', true );
        if ( ! empty( $video_url ) ) {
            $video_html = self::get_video_embed( $video_url );
            if ( $video_html ) {
                $content = $video_html . $content;
            }
        }

        // Add lesson materials (PDFs, downloads)
        $materials = self::get_lesson_materials( $lesson_id );
        if ( ! empty( $materials ) ) {
            $content .= $materials;
        }

        // Add discussion section if enabled
        $discussion_enabled = get_post_meta( $lesson_id, '_lms_discussion_enabled', true );
        if ( $discussion_enabled ) {
            $content .= self::get_discussion_section( $lesson_id );
        }

        // Add mark complete button
        $content .= self::get_complete_button( $lesson_id );

        return $content;
    }

    /**
     * Append course curriculum to course content.
     *
     * @since 1.2.5
     * @param string $content Post content.
     * @return string Modified content.
     */
    public static function append_course_content( string $content ): string {
        $course_id = get_the_ID();
        if ( ! $course_id ) {
            return $content;
        }

        $sections = get_post_meta( $course_id, '_lms_sections', true );
        if ( empty( $sections ) || ! is_array( $sections ) ) {
            return $content;
        }

        $user_id = get_current_user_id();
        $is_enrolled = false;
        
        if ( $user_id ) {
            $is_enrolled = Unstoppable_LMS_Enrollment::is_enrolled( $user_id, $course_id );
        }

        // Build curriculum HTML
        $output = '<div class="lms-course-curriculum" style="margin-top:30px;">';
        $output .= '<h2 style="margin-bottom:20px;">' . esc_html__( 'Course Content', 'unstoppable-lms' ) . '</h2>';

        foreach ( $sections as $section ) {
            $output .= '<div class="lms-curriculum-section" style="margin-bottom:20px;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">';
            $output .= '<div class="lms-curriculum-section-header" style="background:#f8f9fa;padding:15px 20px;font-weight:600;border-bottom:1px solid #e0e0e0;">';
            $output .= esc_html( $section['title'] ?? __( 'Section', 'unstoppable-lms' ) );
            $output .= '</div>';
            $output .= '<div class="lms-curriculum-section-content">';

            $lessons = $section['lessons'] ?? array();
            $quizzes = $section['quizzes'] ?? array();

            if ( empty( $lessons ) && empty( $quizzes ) ) {
                $output .= '<p style="padding:15px 20px;color:#666;margin:0;">' . esc_html__( 'No content in this section yet.', 'unstoppable-lms' ) . '</p>';
            } else {
                // Display lessons
                foreach ( $lessons as $lesson_id ) {
                    $lesson = get_post( $lesson_id );
                    if ( ! $lesson ) continue;

                    $lesson_url = get_permalink( $lesson_id );
                    $duration = get_post_meta( $lesson_id, '_lms_duration', true );
                    $attachments = get_post_meta( $lesson_id, '_lms_attachments', true );
                    $has_attachments = is_array( $attachments ) && count( $attachments ) > 0;
                    
                    // Check if completed
                    $is_complete = false;
                    if ( $user_id && $is_enrolled ) {
                        $is_complete = Unstoppable_LMS_Progress_Tracker::is_item_complete( $user_id, $lesson_id, 'lesson' );
                    }

                    $output .= '<div class="lms-curriculum-item" style="display:flex;align-items:center;padding:12px 20px;border-bottom:1px solid #f0f0f0;">';
                    
                    // Icon
                    if ( $is_complete ) {
                        $output .= '<span class="dashicons dashicons-yes-alt" style="color:#10B981;margin-right:12px;"></span>';
                    } else {
                        $output .= '<span class="dashicons dashicons-media-document" style="color:#2271b1;margin-right:12px;"></span>';
                    }

                    // Title and link
                    if ( $is_enrolled ) {
                        $output .= '<a href="' . esc_url( $lesson_url ) . '" style="flex:1;text-decoration:none;color:#1d2327;">';
                        $output .= esc_html( $lesson->post_title );
                        $output .= '</a>';
                    } else {
                        $output .= '<span style="flex:1;color:#1d2327;">' . esc_html( $lesson->post_title ) . '</span>';
                    }

                    // Meta info
                    $output .= '<span style="color:#666;font-size:13px;">';
                    if ( $duration ) {
                        $output .= $duration . ' ' . esc_html__( 'min', 'unstoppable-lms' );
                    }
                    if ( $has_attachments ) {
                        $output .= ' <span class="dashicons dashicons-paperclip" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span>';
                    }
                    $output .= '</span>';

                    $output .= '</div>';
                }

                // Display quizzes
                foreach ( $quizzes as $quiz_id ) {
                    $quiz = get_post( $quiz_id );
                    if ( ! $quiz ) continue;

                    $quiz_url = get_permalink( $quiz_id );

                    $output .= '<div class="lms-curriculum-item" style="display:flex;align-items:center;padding:12px 20px;border-bottom:1px solid #f0f0f0;">';
                    $output .= '<span class="dashicons dashicons-forms" style="color:#9b59b6;margin-right:12px;"></span>';
                    
                    if ( $is_enrolled ) {
                        $output .= '<a href="' . esc_url( $quiz_url ) . '" style="flex:1;text-decoration:none;color:#1d2327;">';
                        $output .= esc_html( $quiz->post_title );
                        $output .= '</a>';
                    } else {
                        $output .= '<span style="flex:1;color:#1d2327;">' . esc_html( $quiz->post_title ) . '</span>';
                    }
                    
                    $output .= '<span style="color:#666;font-size:13px;">' . esc_html__( 'Quiz', 'unstoppable-lms' ) . '</span>';
                    $output .= '</div>';
                }
            }

            $output .= '</div>'; // section-content
            $output .= '</div>'; // section
        }

        $output .= '</div>'; // course-curriculum

        // Add enroll button if not enrolled
        if ( ! $is_enrolled ) {
            $output .= '<div class="lms-enroll-box" style="margin-top:30px;padding:20px;background:#f8f9fa;border-radius:8px;text-align:center;">';
            if ( $user_id ) {
                $output .= '<p style="margin-bottom:15px;">' . esc_html__( 'Enroll in this course to access all lessons.', 'unstoppable-lms' ) . '</p>';
                $output .= '<button class="lms-enroll-btn lms-btn lms-btn--primary" data-course-id="' . esc_attr( $course_id ) . '">';
                $output .= esc_html__( 'Enroll Now', 'unstoppable-lms' );
                $output .= '</button>';
            } else {
                $output .= '<p style="margin-bottom:15px;">' . esc_html__( 'Please log in to enroll in this course.', 'unstoppable-lms' ) . '</p>';
                $output .= '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="lms-btn lms-btn--primary">';
                $output .= esc_html__( 'Log In', 'unstoppable-lms' );
                $output .= '</a>';
            }
            $output .= '</div>';
        } else {
            // Show progress for enrolled users
            $progress = Unstoppable_LMS_Progress_Tracker::get_course_progress( $user_id, $course_id );
            $output .= '<div class="lms-progress-box" style="margin-top:30px;padding:20px;background:#f8f9fa;border-radius:8px;">';
            $output .= '<div style="display:flex;justify-content:space-between;margin-bottom:10px;">';
            $output .= '<span>' . esc_html__( 'Your Progress', 'unstoppable-lms' ) . '</span>';
            $output .= '<span>' . esc_html( $progress['percent'] ) . '%</span>';
            $output .= '</div>';
            $output .= '<div class="lms-progress-bar" style="height:10px;background:#e0e0e0;border-radius:5px;overflow:hidden;">';
            $output .= '<div class="lms-progress-bar__fill" style="width:' . esc_attr( $progress['percent'] ) . '%;height:100%;background:var(--lms-primary, #3B82F6);"></div>';
            $output .= '</div>';
            $output .= '</div>';
        }

        return $content . $output;
    }

    /**
     * Get video embed HTML.
     *
     * @since 1.2.3
     * @param string $url Video URL.
     * @return string HTML embed code.
     */
    public static function get_video_embed( string $url ): string {
        if ( empty( $url ) ) {
            return '';
        }

        $output = '<div class="lms-video-wrapper" style="margin-bottom:24px;">';

        // YouTube
        if ( preg_match( '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches ) ) {
            $video_id = $matches[1];
            $output .= '<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;">';
            $output .= '<iframe src="https://www.youtube.com/embed/' . esc_attr( $video_id ) . '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;" allowfullscreen></iframe>';
            $output .= '</div>';
        }
        // Vimeo
        elseif ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches ) ) {
            $video_id = $matches[1];
            $output .= '<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;">';
            $output .= '<iframe src="https://player.vimeo.com/video/' . esc_attr( $video_id ) . '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;" allowfullscreen></iframe>';
            $output .= '</div>';
        }
        // Direct video file
        elseif ( preg_match( '/\.(mp4|webm|ogg)$/i', $url ) ) {
            $output .= '<video controls style="width:100%;max-width:100%;">';
            $output .= '<source src="' . esc_url( $url ) . '" type="video/' . pathinfo( $url, PATHINFO_EXTENSION ) . '">';
            $output .= esc_html__( 'Your browser does not support the video tag.', 'unstoppable-lms' );
            $output .= '</video>';
        }
        // Fallback: try WordPress embed
        else {
            global $wp_embed;
            if ( $wp_embed ) {
                $output .= $wp_embed->run_shortcode( '[embed]' . $url . '[/embed]' );
            }
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Get discussion section HTML.
     *
     * @since 1.2.3
     * @param int $lesson_id Lesson ID.
     * @return string HTML.
     */
    public static function get_discussion_section( int $lesson_id ): string {
        if ( ! comments_open( $lesson_id ) && get_comments_number( $lesson_id ) == 0 ) {
            // Enable comments for this post type temporarily
            add_filter( 'comments_open', '__return_true' );
        }

        $output = '<div class="lms-discussion-section" style="margin-top:40px;padding-top:30px;border-top:1px solid #e0e0e0;">';
        $output .= '<h3 style="margin-bottom:20px;">' . esc_html__( 'Discussion', 'unstoppable-lms' ) . '</h3>';
        
        // Get comments
        ob_start();
        comments_template();
        $comments_html = ob_get_clean();
        
        if ( ! empty( $comments_html ) ) {
            $output .= $comments_html;
        } else {
            // Fallback simple comment form
            if ( is_user_logged_in() ) {
                $output .= '<div class="lms-discussion-form">';
                $output .= '<p>' . esc_html__( 'Share your thoughts or ask a question about this lesson.', 'unstoppable-lms' ) . '</p>';
                ob_start();
                comment_form( array(
                    'title_reply' => '',
                    'label_submit' => __( 'Post Comment', 'unstoppable-lms' ),
                ), $lesson_id );
                $output .= ob_get_clean();
                $output .= '</div>';
            } else {
                $output .= '<p>' . sprintf( 
                    esc_html__( 'Please %s to join the discussion.', 'unstoppable-lms' ),
                    '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'log in', 'unstoppable-lms' ) . '</a>'
                ) . '</p>';
            }
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Get mark complete button HTML.
     *
     * @since 1.2.3
     * @param int $lesson_id Lesson ID.
     * @return string HTML.
     */
    public static function get_complete_button( int $lesson_id ): string {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        $user_id = get_current_user_id();
        $course_id = get_post_meta( $lesson_id, '_lms_course_id', true );
        
        if ( ! $course_id ) {
            return '';
        }

        // Check if already completed
        $is_completed = Unstoppable_LMS_Progress_Tracker::is_item_complete( $user_id, $lesson_id, 'lesson' );

        $output = '<div class="lms-lesson-actions" style="margin-top:30px;padding:20px;background:#f8f9fa;border-radius:8px;text-align:center;">';
        
        if ( $is_completed ) {
            $output .= '<button class="lms-complete-btn lms-btn lms-btn--secondary is-complete" disabled style="opacity:0.7;">';
            $output .= '<span class="dashicons dashicons-yes-alt" style="margin-right:8px;"></span>';
            $output .= esc_html__( 'Completed', 'unstoppable-lms' );
            $output .= '</button>';
        } else {
            $output .= '<button class="lms-complete-btn lms-btn lms-btn--primary" data-item-id="' . esc_attr( $lesson_id ) . '" data-item-type="lesson">';
            $output .= esc_html__( 'Mark Complete', 'unstoppable-lms' );
            $output .= '</button>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Load LMS templates.
     *
     * @since 1.0.0
     * @param string|null $template Current template.
     * @return string Template to load.
     */
    public static function load_template( $template ) {
        // Ensure template is a string
        if ( ! is_string( $template ) || empty( $template ) ) {
            return '';
        }
        
        if ( ! self::is_lms_content() ) {
            return $template;
        }

        $display_mode = Unstoppable_LMS_Settings::get( 'display.mode' );
        if ( ! is_string( $display_mode ) || empty( $display_mode ) ) {
            $display_mode = 'focus';
        }

        // Embedded mode uses theme template.
        if ( 'embedded' === $display_mode ) {
            return $template;
        }

        $custom_template = self::locate_template( $display_mode . '-mode.php' );
        if ( ! empty( $custom_template ) && file_exists( $custom_template ) ) {
            return $custom_template;
        }

        return $template;
    }

    /**
     * Enqueue frontend assets.
     *
     * @since 1.0.0
     */
    public static function enqueue_assets() {
        if ( ! self::is_lms_content() ) {
            return;
        }

        // Enqueue dashicons for PDF viewer and buttons
        wp_enqueue_style( 'dashicons' );

        $css_url = UNSTOPPABLE_LMS_PLUGIN_URL . 'assets/css/frontend.css';
        $js_url = UNSTOPPABLE_LMS_PLUGIN_URL . 'assets/js/frontend.js';

        if ( ! empty( $css_url ) ) {
            wp_enqueue_style(
                'unstoppable-lms-frontend',
                $css_url,
                array(),
                UNSTOPPABLE_LMS_VERSION
            );

            $css_vars = self::get_css_variables();
            if ( ! empty( $css_vars ) ) {
                wp_add_inline_style( 'unstoppable-lms-frontend', $css_vars );
            }
        }

        if ( ! empty( $js_url ) ) {
            wp_enqueue_script(
                'unstoppable-lms-frontend',
                $js_url,
                array(),
                UNSTOPPABLE_LMS_VERSION,
                true
            );

            wp_localize_script( 'unstoppable-lms-frontend', 'unstoppableLMS', array(
                'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'unstoppable_lms_frontend' ),
                'userId'   => get_current_user_id(),
                'courseId' => self::get_current_course_id(),
                'strings'  => array(
                    'markComplete'   => __( 'Mark Complete', 'unstoppable-lms' ),
                    'completed'      => __( 'Completed', 'unstoppable-lms' ),
                    'nextLesson'     => __( 'Next Lesson', 'unstoppable-lms' ),
                    'previousLesson' => __( 'Previous Lesson', 'unstoppable-lms' ),
                ),
            ) );
        }
    }

    /**
     * Check if current page is LMS content.
     *
     * @since 1.0.0
     * @return bool Whether this is LMS content.
     */
    public static function is_lms_content(): bool {
        if ( is_singular( Unstoppable_LMS_Post_Types::get_post_types() ) ) {
            return true;
        }

        if ( is_post_type_archive( 'lms_course' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Get current course ID.
     *
     * @since 1.0.0
     * @return int|null Course ID or null.
     */
    public static function get_current_course_id(): ?int {
        if ( is_singular( 'lms_course' ) ) {
            $id = get_the_ID();
            return $id ? (int) $id : null;
        }

        if ( is_singular( array( 'lms_lesson', 'lms_topic', 'lms_quiz' ) ) ) {
            $post_id = get_the_ID();
            if ( ! $post_id ) {
                return null;
            }
            $course_id = get_post_meta( $post_id, '_lms_course_id', true );
            return $course_id ? (int) $course_id : null;
        }

        return null;
    }

    /**
     * Locate a template file.
     *
     * @since 1.0.0
     * @param string $template_name Template name.
     * @return string|null Template path or null.
     */
    public static function locate_template( string $template_name ): string {
        $template_path = 'unstoppable-lms/';

        // Check theme/child theme.
        $template = locate_template( array(
            $template_path . $template_name,
            $template_name,
        ) );

        if ( $template ) {
            return $template;
        }

        // Check plugin templates.
        $plugin_template = UNSTOPPABLE_LMS_PLUGIN_DIR . 'templates/' . $template_name;
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }

        return '';
    }

    /**
     * Get CSS variables from settings.
     *
     * @since 1.0.0
     * @return string CSS custom properties.
     */
    private static function get_css_variables(): string {
        $primary   = Unstoppable_LMS_Settings::get( 'display.primary_color' ) ?: '#3B82F6';
        $secondary = Unstoppable_LMS_Settings::get( 'display.secondary_color' ) ?: '#10B981';

        return "
            :root {
                --lms-primary: {$primary};
                --lms-secondary: {$secondary};
                --lms-text: #1F2937;
                --lms-background: #F9FAFB;
                --lms-sidebar-width: 320px;
                --lms-content-width: 800px;
            }
        ";
    }

    /**
     * Display lesson materials/attachments.
     *
     * @since 1.2.0
     * @param int $lesson_id Lesson ID.
     * @return string HTML output.
     */
    public static function get_lesson_materials( int $lesson_id ): string {
        $attachments = get_post_meta( $lesson_id, '_lms_attachments', true );
        $embed_ids = get_post_meta( $lesson_id, '_lms_embed_ids', true );
        
        // Backwards compatibility: if embed_ids not set, check old embed_materials flag
        if ( ! is_array( $embed_ids ) ) {
            $embed_ids = array();
            $old_embed_enabled = get_post_meta( $lesson_id, '_lms_embed_materials', true );
            if ( $old_embed_enabled && is_array( $attachments ) ) {
                // Old behavior: embed all PDFs
                foreach ( $attachments as $att_id ) {
                    if ( get_post_mime_type( $att_id ) === 'application/pdf' ) {
                        $embed_ids[] = $att_id;
                    }
                }
            }
        }

        if ( empty( $attachments ) || ! is_array( $attachments ) ) {
            return '';
        }

        $output = '';
        $embedded_pdfs = array();
        $download_files = array();

        // Separate files into embedded PDFs and download-only files
        foreach ( $attachments as $attachment_id ) {
            $attachment = get_post( $attachment_id );
            if ( ! $attachment ) {
                continue;
            }

            $mime_type = get_post_mime_type( $attachment_id );
            $should_embed = in_array( $attachment_id, $embed_ids, true ) && $mime_type === 'application/pdf';
            
            if ( $should_embed ) {
                $embedded_pdfs[] = $attachment_id;
            } else {
                $download_files[] = $attachment_id;
            }
        }

        // Display embedded PDFs
        if ( ! empty( $embedded_pdfs ) ) {
            $output .= '<div class="lms-embedded-materials">';
            
            foreach ( $embedded_pdfs as $index => $pdf_id ) {
                $url = wp_get_attachment_url( $pdf_id );
                $title = get_the_title( $pdf_id );
                $active = $index === 0 ? 'active' : '';
                
                $output .= '<div class="lms-pdf-section ' . $active . '" data-pdf-index="' . $index . '">';
                $output .= '<div class="lms-pdf-header">';
                $output .= '<div class="lms-pdf-title"><span class="dashicons dashicons-pdf"></span> ' . esc_html( $title ) . '</div>';
                $output .= '<div class="lms-pdf-actions">';
                $output .= '<a href="' . esc_url( $url ) . '" target="_blank" class="lms-pdf-btn"><span class="dashicons dashicons-external"></span> Open in New Tab</a>';
                $output .= '<a href="' . esc_url( $url ) . '" download class="lms-pdf-btn"><span class="dashicons dashicons-download"></span> Download</a>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '<div class="lms-pdf-viewer">';
                $output .= '<iframe src="' . esc_url( $url ) . '#toolbar=1&navpanes=0&scrollbar=1" type="application/pdf" width="100%" height="800px" style="border:none;"></iframe>';
                $output .= '</div>';
                $output .= '</div>';
            }
            
            // PDF navigation if multiple embedded PDFs
            if ( count( $embedded_pdfs ) > 1 ) {
                $output .= '<div class="lms-pdf-nav">';
                foreach ( $embedded_pdfs as $index => $pdf_id ) {
                    $title = get_the_title( $pdf_id );
                    $active = $index === 0 ? 'active' : '';
                    $output .= '<button type="button" class="lms-pdf-nav-btn ' . $active . '" data-index="' . $index . '">' . esc_html( $title ) . '</button>';
                }
                $output .= '</div>';
            }
            
            $output .= '</div>';
        }

        // Display download-only files
        if ( ! empty( $download_files ) ) {
            $output .= '<div class="lms-lesson-materials">';
            $output .= '<h4>' . esc_html__( 'Downloadable Materials', 'unstoppable-lms' ) . '</h4>';

            foreach ( $download_files as $attachment_id ) {
                $attachment = get_post( $attachment_id );
                if ( ! $attachment ) {
                    continue;
                }

                $url = wp_get_attachment_url( $attachment_id );
                $title = get_the_title( $attachment_id );
                $filepath = get_attached_file( $attachment_id );
                $filetype = wp_check_filetype( $filepath );
                $filesize = file_exists( $filepath ) ? filesize( $filepath ) : 0;
                $filesize_formatted = size_format( $filesize );

                // Get icon based on file type
                $icon = 'media-document';
                $ext = strtolower( $filetype['ext'] ?? '' );
                if ( $ext === 'pdf' ) {
                    $icon = 'pdf';
                } elseif ( in_array( $ext, array( 'doc', 'docx' ), true ) ) {
                    $icon = 'media-document';
                } elseif ( in_array( $ext, array( 'xls', 'xlsx' ), true ) ) {
                    $icon = 'media-spreadsheet';
                } elseif ( in_array( $ext, array( 'ppt', 'pptx' ), true ) ) {
                    $icon = 'slides';
                } elseif ( $ext === 'zip' ) {
                    $icon = 'archive';
                }

                $output .= '<a href="' . esc_url( $url ) . '" class="lms-material-item" download>';
                $output .= '<span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span>';
                $output .= '<span class="material-info">';
                $output .= '<span class="material-title">' . esc_html( $title ) . '</span>';
                $output .= '<span class="material-size">' . esc_html( strtoupper( $ext ) ) . ' &bull; ' . esc_html( $filesize_formatted ) . '</span>';
                $output .= '</span>';
                $output .= '<span class="dashicons dashicons-download download-icon"></span>';
                $output .= '</a>';
            }

            $output .= '</div>';
        }

        return $output;
    }
}
