<?php
/**
 * Admin menus - Course-Centric Architecture
 *
 * @package Unstoppable_LMS
 * @since 1.1.0
 */

defined( 'ABSPATH' ) || exit;

class Unstoppable_LMS_Admin_Menus {

    /**
     * Brand color for menu styling.
     *
     * @var string
     */
    private static $brand_color = '#f0b849'; // Gold - Unstoppable Solutions brand color

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
        add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_menu' ), 100 );
        add_action( 'admin_head', array( __CLASS__, 'menu_icon_styles' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
    }

    /**
     * Register admin menus.
     */
    public static function register_menus() {
        // Use a custom SVG icon encoded as data URI
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>' );

        add_menu_page( 
            __( 'Unstoppable LMS', 'unstoppable-lms' ), 
            __( 'Unstoppable LMS', 'unstoppable-lms' ), 
            'edit_lms_courses', 
            'unstoppable-lms', 
            array( __CLASS__, 'render_dashboard' ), 
            $icon_svg, 
            25 
        );
        add_submenu_page( 'unstoppable-lms', __( 'Dashboard', 'unstoppable-lms' ), __( 'Dashboard', 'unstoppable-lms' ), 'edit_lms_courses', 'unstoppable-lms', array( __CLASS__, 'render_dashboard' ) );
        add_submenu_page( 'unstoppable-lms', __( 'Courses', 'unstoppable-lms' ), __( 'Courses', 'unstoppable-lms' ), 'edit_lms_courses', 'unstoppable-lms-courses', array( __CLASS__, 'render_courses' ) );
        // Course Builder - hidden from menu, accessible via direct URL only
        add_submenu_page( '', __( 'Course Builder', 'unstoppable-lms' ), __( 'Course Builder', 'unstoppable-lms' ), 'edit_lms_courses', 'unstoppable-lms-course-builder', array( __CLASS__, 'render_course_builder' ) );
        add_submenu_page( 'unstoppable-lms', __( 'Students', 'unstoppable-lms' ), __( 'Students', 'unstoppable-lms' ), 'manage_lms_students', 'unstoppable-lms-students', array( __CLASS__, 'render_students' ) );
        // Pro features - show upgrade page
        add_submenu_page( 'unstoppable-lms', __( 'Gradebook', 'unstoppable-lms' ), __( 'Gradebook ⭐', 'unstoppable-lms' ), 'view_lms_reports', 'unstoppable-lms-gradebook', array( __CLASS__, 'render_pro_feature_gradebook' ) );
        add_submenu_page( 'unstoppable-lms', __( 'Classes', 'unstoppable-lms' ), __( 'Classes ⭐', 'unstoppable-lms' ), 'edit_lms_courses', 'unstoppable-lms-cohorts', array( __CLASS__, 'render_pro_feature_cohorts' ) );
        add_submenu_page( 'unstoppable-lms', __( 'Discussions', 'unstoppable-lms' ), __( 'Discussions ⭐', 'unstoppable-lms' ), 'edit_lms_courses', 'unstoppable-lms-discussions', array( __CLASS__, 'render_pro_feature_discussions' ) );
        add_submenu_page( 'unstoppable-lms', __( 'Reports', 'unstoppable-lms' ), __( 'Reports', 'unstoppable-lms' ), 'view_lms_reports', 'unstoppable-lms-reports', array( __CLASS__, 'render_reports' ) );
        add_submenu_page( 'unstoppable-lms', __( 'Settings', 'unstoppable-lms' ), __( 'Settings', 'unstoppable-lms' ), 'manage_lms_settings', 'unstoppable-lms-settings', array( __CLASS__, 'render_settings' ) );
        add_submenu_page( 'unstoppable-lms', __( 'Upgrade to Pro', 'unstoppable-lms' ), '<span style="color: #f0b849;">⭐ ' . __( 'Upgrade to Pro', 'unstoppable-lms' ) . '</span>', 'manage_lms_settings', 'unstoppable-lms-upgrade', array( __CLASS__, 'render_upgrade_page' ) );
    }

    /**
     * Add admin bar menu for quick access.
     *
     * @param WP_Admin_Bar $admin_bar Admin bar instance.
     */
    public static function add_admin_bar_menu( $admin_bar ) {
        if ( ! current_user_can( 'edit_lms_courses' ) ) {
            return;
        }

        // Main menu item with colored icon
        $admin_bar->add_node( array(
            'id'    => 'unstoppable-lms',
            'title' => '<span class="ab-icon dashicons dashicons-book-alt" style="color: ' . esc_attr( self::$brand_color ) . '; font-family: dashicons; font-size: 18px; line-height: 1.3;"></span> <span class="ab-label" style="color: ' . esc_attr( self::$brand_color ) . ';">' . esc_html__( 'Unstoppable LMS', 'unstoppable-lms' ) . '</span>',
            'href'  => admin_url( 'admin.php?page=unstoppable-lms' ),
            'meta'  => array(
                'title' => __( 'Unstoppable LMS', 'unstoppable-lms' ),
            ),
        ) );

        // Submenu items
        $admin_bar->add_node( array(
            'id'     => 'unstoppable-lms-dashboard',
            'parent' => 'unstoppable-lms',
            'title'  => __( 'Dashboard', 'unstoppable-lms' ),
            'href'   => admin_url( 'admin.php?page=unstoppable-lms' ),
        ) );

        $admin_bar->add_node( array(
            'id'     => 'unstoppable-lms-courses',
            'parent' => 'unstoppable-lms',
            'title'  => __( 'All Courses', 'unstoppable-lms' ),
            'href'   => admin_url( 'admin.php?page=unstoppable-lms-courses' ),
        ) );

        $admin_bar->add_node( array(
            'id'     => 'unstoppable-lms-new-course',
            'parent' => 'unstoppable-lms',
            'title'  => __( '+ New Course', 'unstoppable-lms' ),
            'href'   => admin_url( 'admin.php?page=unstoppable-lms-courses&action=new' ),
        ) );

        if ( current_user_can( 'manage_lms_students' ) ) {
            $admin_bar->add_node( array(
                'id'     => 'unstoppable-lms-students',
                'parent' => 'unstoppable-lms',
                'title'  => __( 'Students', 'unstoppable-lms' ),
                'href'   => admin_url( 'admin.php?page=unstoppable-lms-students' ),
            ) );
        }

        if ( current_user_can( 'manage_lms_settings' ) ) {
            $admin_bar->add_node( array(
                'id'     => 'unstoppable-lms-settings',
                'parent' => 'unstoppable-lms',
                'title'  => __( 'Settings', 'unstoppable-lms' ),
                'href'   => admin_url( 'admin.php?page=unstoppable-lms-settings' ),
            ) );
        }
    }

    /**
     * Add custom styles for the menu icon color.
     */
    public static function menu_icon_styles() {
        $color = self::$brand_color;
        ?>
        <style>
            /* Colored menu item in sidebar */
            #adminmenu .toplevel_page_unstoppable-lms .wp-menu-name {
                color: <?php echo esc_attr( $color ); ?> !important;
                font-weight: 600;
            }
            #adminmenu .toplevel_page_unstoppable-lms .wp-menu-image svg {
                fill: <?php echo esc_attr( $color ); ?> !important;
            }
            #adminmenu .toplevel_page_unstoppable-lms .wp-menu-image img {
                filter: brightness(0) saturate(100%) invert(61%) sepia(52%) saturate(501%) hue-rotate(115deg) brightness(95%) contrast(92%);
            }
            #adminmenu .toplevel_page_unstoppable-lms:hover .wp-menu-name,
            #adminmenu .toplevel_page_unstoppable-lms.current .wp-menu-name {
                color: #fff !important;
            }
            #adminmenu .toplevel_page_unstoppable-lms:hover .wp-menu-image svg,
            #adminmenu .toplevel_page_unstoppable-lms.current .wp-menu-image svg {
                fill: #fff !important;
            }
            
            /* Admin bar styling */
            #wpadminbar #wp-admin-bar-unstoppable-lms > .ab-item {
                background: transparent !important;
            }
            #wpadminbar #wp-admin-bar-unstoppable-lms:hover > .ab-item {
                background: rgba(0,0,0,0.1) !important;
            }
        </style>
        <?php
    }

    public static function handle_actions() {
        // Create course
        if ( isset( $_POST['lms_create_course'] ) ) {
            if ( ! check_admin_referer( 'lms_create_course' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'unstoppable-lms' ) );
            }
            if ( ! current_user_can( 'edit_lms_courses' ) ) {
                wp_die( esc_html__( 'You do not have permission to create courses.', 'unstoppable-lms' ) );
            }
            
            $title = isset( $_POST['course_title'] ) ? sanitize_text_field( wp_unslash( $_POST['course_title'] ) ) : '';
            if ( empty( $title ) ) {
                Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Course title is required.', 'unstoppable-lms' ), 'error' );
                return;
            }
            
            $id = wp_insert_post( array( 
                'post_type'   => 'lms_course', 
                'post_title'  => $title, 
                'post_status' => 'draft', 
                'post_author' => get_current_user_id() 
            ) );
            
            if ( $id && ! is_wp_error( $id ) ) {
                update_post_meta( $id, '_lms_sections', array() );
                wp_safe_redirect( admin_url( 'admin.php?page=unstoppable-lms-course-builder&course_id=' . $id . '&new=1' ) );
                exit;
            }
        }

        // Create lesson
        if ( isset( $_POST['lms_create_lesson'] ) ) {
            if ( ! check_admin_referer( 'lms_course_builder' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'unstoppable-lms' ) );
            }
            if ( ! current_user_can( 'edit_lms_lessons' ) ) {
                wp_die( esc_html__( 'You do not have permission to create lessons.', 'unstoppable-lms' ) );
            }
            self::handle_create_lesson();
        }

        // Create quiz
        if ( isset( $_POST['lms_create_quiz'] ) ) {
            if ( ! check_admin_referer( 'lms_course_builder' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'unstoppable-lms' ) );
            }
            if ( ! current_user_can( 'edit_lms_quizzes' ) ) {
                wp_die( esc_html__( 'You do not have permission to create quizzes.', 'unstoppable-lms' ) );
            }
            self::handle_create_quiz();
        }

        // Add section
        if ( isset( $_POST['lms_add_section'] ) ) {
            if ( ! check_admin_referer( 'lms_course_builder' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'unstoppable-lms' ) );
            }
            if ( ! current_user_can( 'edit_lms_courses' ) ) {
                wp_die( esc_html__( 'You do not have permission to edit courses.', 'unstoppable-lms' ) );
            }
            self::handle_add_section();
        }

        // Delete item
        if ( isset( $_GET['action'] ) && 'delete_item' === $_GET['action'] && isset( $_GET['_wpnonce'] ) ) {
            $item_id = isset( $_GET['item_id'] ) ? absint( $_GET['item_id'] ) : 0;
            $course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
            
            if ( ! $item_id || ! $course_id ) {
                wp_die( esc_html__( 'Invalid request.', 'unstoppable-lms' ) );
            }
            
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_item_' . $item_id ) ) {
                wp_die( esc_html__( 'Security check failed.', 'unstoppable-lms' ) );
            }
            
            if ( ! current_user_can( 'delete_post', $item_id ) ) {
                wp_die( esc_html__( 'You do not have permission to delete this item.', 'unstoppable-lms' ) );
            }
            
            wp_delete_post( $item_id, true );
            Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Item deleted successfully.', 'unstoppable-lms' ), 'success' );
            wp_safe_redirect( admin_url( 'admin.php?page=unstoppable-lms-course-builder&course_id=' . $course_id . '&deleted=1' ) );
            exit;
        }

        // Enroll student
        if ( isset( $_POST['lms_enroll_student'] ) ) {
            if ( ! check_admin_referer( 'lms_enroll_student' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'unstoppable-lms' ) );
            }
            if ( ! current_user_can( 'manage_lms_students' ) ) {
                wp_die( esc_html__( 'You do not have permission to enroll students.', 'unstoppable-lms' ) );
            }
            
            $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
            $course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
            
            if ( $user_id && $course_id && class_exists( 'Unstoppable_LMS_Enrollment' ) ) {
                $result = Unstoppable_LMS_Enrollment::enroll( $user_id, $course_id, null, get_current_user_id() );
                if ( $result ) {
                    Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Student enrolled successfully.', 'unstoppable-lms' ), 'success' );
                } else {
                    Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Failed to enroll student.', 'unstoppable-lms' ), 'error' );
                }
            }
        }
    }

    private static function handle_create_lesson() {
        // Sanitize inputs
        $course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
        $section_id = isset( $_POST['section_id'] ) ? sanitize_key( wp_unslash( $_POST['section_id'] ) ) : 'section_1';
        $title = isset( $_POST['lesson_title'] ) ? sanitize_text_field( wp_unslash( $_POST['lesson_title'] ) ) : '';
        
        // Validate required fields
        if ( ! $course_id ) {
            Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Invalid course.', 'unstoppable-lms' ), 'error' );
            return;
        }
        
        if ( empty( $title ) ) {
            Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Lesson title is required.', 'unstoppable-lms' ), 'error' );
            wp_safe_redirect( admin_url( 'admin.php?page=unstoppable-lms-course-builder&course_id=' . $course_id ) );
            exit;
        }
        
        // Verify course exists
        $course = get_post( $course_id );
        if ( ! $course || 'lms_course' !== $course->post_type ) {
            Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Course not found.', 'unstoppable-lms' ), 'error' );
            return;
        }
        
        // Create lesson
        $lesson_content = isset( $_POST['lesson_content'] ) ? wp_kses_post( wp_unslash( $_POST['lesson_content'] ) ) : '';
        
        $lesson_id = wp_insert_post( array( 
            'post_type'    => 'lms_lesson', 
            'post_title'   => $title, 
            'post_content' => $lesson_content, 
            'post_status'  => 'publish', 
            'post_author'  => get_current_user_id() 
        ) );
        
        if ( ! $lesson_id || is_wp_error( $lesson_id ) ) {
            Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Failed to create lesson.', 'unstoppable-lms' ), 'error' );
            wp_safe_redirect( admin_url( 'admin.php?page=unstoppable-lms-course-builder&course_id=' . $course_id ) );
            exit;
        }
        
        // Save lesson meta
        update_post_meta( $lesson_id, '_lms_course_id', $course_id );
        update_post_meta( $lesson_id, '_lms_discussion_enabled', isset( $_POST['enable_discussion'] ) ? 1 : 0 );
        
        $video_url = isset( $_POST['video_url'] ) ? esc_url_raw( wp_unslash( $_POST['video_url'] ) ) : '';
        update_post_meta( $lesson_id, '_lms_video_url', $video_url );
        
        $duration = isset( $_POST['duration'] ) ? absint( $_POST['duration'] ) : 0;
        update_post_meta( $lesson_id, '_lms_duration', $duration );
        
        // Handle file attachments - validate each attachment exists and is valid
        $attachments = array();
        if ( ! empty( $_POST['attachment_ids'] ) ) {
            $raw_ids = sanitize_text_field( wp_unslash( $_POST['attachment_ids'] ) );
            $attachment_ids = array_map( 'absint', explode( ',', $raw_ids ) );
            
            foreach ( $attachment_ids as $att_id ) {
                if ( $att_id > 0 ) {
                    $attachment = get_post( $att_id );
                    if ( $attachment && 'attachment' === $attachment->post_type ) {
                        $attachments[] = $att_id;
                    }
                }
            }
        }
        update_post_meta( $lesson_id, '_lms_attachments', $attachments );
        
        // Handle embed IDs (which files should be embedded vs download only)
        $embed_ids = array();
        if ( ! empty( $_POST['embed_ids'] ) ) {
            $raw_embed = sanitize_text_field( wp_unslash( $_POST['embed_ids'] ) );
            $embed_ids = array_map( 'absint', explode( ',', $raw_embed ) );
            // Only keep embed_ids that are in attachments
            $embed_ids = array_intersect( $embed_ids, $attachments );
        }
        update_post_meta( $lesson_id, '_lms_embed_ids', $embed_ids );
        
        // Add lesson to section
        $sections = get_post_meta( $course_id, '_lms_sections', true );
        if ( ! is_array( $sections ) ) {
            $sections = array();
        }
        
        $added = false;
        foreach ( $sections as &$sec ) { 
            if ( isset( $sec['id'] ) && $sec['id'] === $section_id ) { 
                if ( ! isset( $sec['lessons'] ) ) {
                    $sec['lessons'] = array();
                }
                $sec['lessons'][] = $lesson_id; 
                $added = true; 
                break; 
            } 
        }
        unset( $sec );
        
        if ( ! $added ) { 
            if ( empty( $sections ) ) { 
                $sections[] = array( 
                    'id' => 'section_1', 
                    'title' => __( 'Course Content', 'unstoppable-lms' ), 
                    'lessons' => array( $lesson_id ) 
                ); 
            } else { 
                if ( ! isset( $sections[0]['lessons'] ) ) {
                    $sections[0]['lessons'] = array();
                }
                $sections[0]['lessons'][] = $lesson_id; 
            } 
        }
        
        update_post_meta( $course_id, '_lms_sections', $sections );
        
        Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Lesson created successfully.', 'unstoppable-lms' ), 'success' );
        wp_safe_redirect( admin_url( 'admin.php?page=unstoppable-lms-course-builder&course_id=' . $course_id . '&lesson_created=1' ) );
        exit;
    }

    private static function handle_create_quiz() {
        $course_id = absint( $_POST['course_id'] ?? 0 );
        $section_id = sanitize_text_field( $_POST['section_id'] ?? 'section_1' );
        $title = sanitize_text_field( $_POST['quiz_title'] ?? '' );
        if ( ! $course_id || ! $title ) return;
        $quiz_id = wp_insert_post( array( 'post_type' => 'lms_quiz', 'post_title' => $title, 'post_status' => 'publish', 'post_author' => get_current_user_id() ) );
        if ( $quiz_id && ! is_wp_error( $quiz_id ) ) {
            update_post_meta( $quiz_id, '_lms_course_id', $course_id );
            update_post_meta( $quiz_id, '_lms_passing_score', absint( $_POST['passing_score'] ?? 80 ) );
            $sections = get_post_meta( $course_id, '_lms_sections', true ) ?: array();
            foreach ( $sections as &$sec ) { if ( $sec['id'] === $section_id ) { if ( ! isset( $sec['quizzes'] ) ) $sec['quizzes'] = array(); $sec['quizzes'][] = $quiz_id; break; } }
            update_post_meta( $course_id, '_lms_sections', $sections );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=unstoppable-lms-course-builder&course_id=' . $course_id . '&quiz_created=1' ) );
        exit;
    }

    private static function handle_add_section() {
        $course_id = absint( $_POST['course_id'] ?? 0 );
        $title = sanitize_text_field( $_POST['section_title'] ?? '' );
        if ( ! $course_id || ! $title ) return;
        $sections = get_post_meta( $course_id, '_lms_sections', true ) ?: array();
        $sections[] = array( 'id' => 'section_' . count( $sections ) . '_' . time(), 'title' => $title, 'lessons' => array() );
        update_post_meta( $course_id, '_lms_sections', $sections );
        wp_safe_redirect( admin_url( 'admin.php?page=unstoppable-lms-course-builder&course_id=' . $course_id . '&section_added=1' ) );
        exit;
    }

    private static function styles() {
        echo '<style>
        .lms-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin:20px 0}
        .lms-stat{background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:20px;text-align:center}
        .lms-stat b{display:block;font-size:32px;color:#1d2327}
        .lms-stat span{font-size:13px;color:#646970}
        .lms-card{background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:20px;margin-bottom:20px}
        .lms-card h2{margin-top:0}
        .lms-status{display:inline-block;padding:3px 8px;border-radius:3px;font-size:12px;font-weight:500}
        .lms-status--active,.lms-status--publish{background:#d1fae5;color:#065f46}
        .lms-status--draft{background:#fef3c7;color:#92400e}
        .lms-status--completed{background:#dbeafe;color:#1e40af}
        .lms-tabs{display:flex;border-bottom:1px solid #c3c4c7;margin-bottom:20px}
        .lms-tab{padding:12px 20px;text-decoration:none;color:#646970;border-bottom:2px solid transparent;margin-bottom:-1px}
        .lms-tab:hover{color:#2271b1}
        .lms-tab.active{color:#2271b1;border-bottom-color:#2271b1;font-weight:600}
        .lms-section{background:#f9f9f9;border:1px solid #e0e0e0;border-radius:6px;margin-bottom:16px}
        .lms-section-head{padding:12px 16px;background:#f0f0f0;border-radius:6px 6px 0 0;font-weight:600}
        .lms-section-body{padding:16px;background:#fff;border-radius:0 0 6px 6px}
        .lms-item{display:flex;align-items:center;gap:12px;padding:12px;background:#fafafa;border:1px solid #e5e5e5;border-radius:4px;margin-bottom:8px}
        .lms-item:last-child{margin-bottom:0}
        .lms-item-info{flex:1}
        .lms-item-info b{display:block}
        .lms-item-meta{font-size:12px;color:#646970}
        .lms-modal{display:none;position:fixed;inset:0;z-index:100000;align-items:center;justify-content:center}
        .lms-modal.open{display:flex}
        .lms-modal-bg{position:absolute;inset:0;background:rgba(0,0,0,.5)}
        .lms-modal-box{position:relative;background:#fff;border-radius:8px;width:600px;max-width:90%;max-height:90vh;overflow:auto}
        .lms-modal-head{display:flex;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e0e0e0}
        .lms-modal-head h3{margin:0}
        .lms-modal-close{background:none;border:none;font-size:24px;cursor:pointer}
        .lms-modal-body{padding:20px}
        .lms-modal-foot{padding:16px 20px;border-top:1px solid #e0e0e0;text-align:right}
        .lms-field{margin-bottom:16px}
        .lms-field label{display:block;font-weight:600;margin-bottom:6px}
        .lms-field input[type=text],.lms-field input[type=url],.lms-field input[type=number],.lms-field textarea{width:100%}
        .lms-types{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .lms-type{display:flex;flex-direction:column;align-items:center;padding:20px;border:2px solid #e0e0e0;border-radius:8px;background:#fff;cursor:pointer}
        .lms-type:hover{border-color:#2271b1;background:#f0f6fc}
        .lms-type .dashicons{font-size:32px;width:32px;height:32px;color:#2271b1;margin-bottom:8px}
        </style>';
    }

    public static function render_dashboard() {
        global $wpdb;
        $pub = wp_count_posts('lms_course')->publish;
        $draft = wp_count_posts('lms_course')->draft;
        $tbl = $wpdb->prefix . 'lms_enrollments';
        $has_tbl = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tbl)) === $tbl;
        $enrolled = $has_tbl ? (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tbl} WHERE status='active'") : 0;
        $done = $has_tbl ? (int)$wpdb->get_var("SELECT COUNT(*) FROM {$tbl} WHERE status='completed'") : 0;
        $recent = get_posts(array('post_type'=>'lms_course','posts_per_page'=>5,'post_status'=>array('publish','draft'),'orderby'=>'modified','order'=>'DESC'));
        $ld = defined('LEARNDASH_VERSION');
        $ld_c = $ld ? wp_count_posts('sfwd-courses')->publish : 0;
        self::styles();
        ?>
        <div class="wrap" style="max-width:1200px">
            <h1><?php esc_html_e('Unstoppable LMS','unstoppable-lms'); ?></h1>
            <?php if($ld && $ld_c): ?>
            <div class="notice notice-info" style="padding:15px;display:flex;align-items:center;gap:15px">
                <span class="dashicons dashicons-migrate" style="font-size:24px;color:#72aee6"></span>
                <div><strong><?php esc_html_e('LearnDash Detected','unstoppable-lms'); ?></strong>
                <p style="margin:5px 0"><?php printf(__('Found %d LearnDash courses.','unstoppable-lms'),$ld_c); ?></p>
                <a href="<?php echo admin_url('admin.php?page=unstoppable-lms-settings&tab=migrate'); ?>" class="button button-primary"><?php esc_html_e('Migrate','unstoppable-lms'); ?></a></div>
            </div>
            <?php endif; ?>
            <div class="lms-stats">
                <div class="lms-stat"><b><?php echo $pub; ?></b><span><?php esc_html_e('Published','unstoppable-lms'); ?></span></div>
                <div class="lms-stat"><b><?php echo $draft; ?></b><span><?php esc_html_e('Drafts','unstoppable-lms'); ?></span></div>
                <div class="lms-stat"><b><?php echo $enrolled; ?></b><span><?php esc_html_e('Students','unstoppable-lms'); ?></span></div>
                <div class="lms-stat"><b><?php echo $done; ?></b><span><?php esc_html_e('Completions','unstoppable-lms'); ?></span></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                <div class="lms-card" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none">
                    <h2 style="color:#fff"><?php esc_html_e('Create a Course','unstoppable-lms'); ?></h2>
                    <p style="opacity:.9"><?php esc_html_e('Add lessons, quizzes, discussions—all in one place.','unstoppable-lms'); ?></p>
                    <form method="post" style="display:flex;gap:10px;margin-top:15px">
                        <?php wp_nonce_field('lms_create_course'); ?>
                        <input type="text" name="course_title" placeholder="<?php esc_attr_e('Course title...','unstoppable-lms'); ?>" required style="flex:1;padding:10px;border:none;border-radius:4px">
                        <button type="submit" name="lms_create_course" class="button button-primary"><?php esc_html_e('Create','unstoppable-lms'); ?></button>
                    </form>
                </div>
                <div class="lms-card">
                    <h2><?php esc_html_e('Recent Courses','unstoppable-lms'); ?></h2>
                    <?php if(empty($recent)): ?>
                        <p style="color:#646970;font-style:italic"><?php esc_html_e('No courses yet.','unstoppable-lms'); ?></p>
                    <?php else: ?>
                        <ul style="margin:0;padding:0;list-style:none">
                        <?php foreach($recent as $c): ?>
                            <li style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f1">
                                <div>
                                    <a href="<?php echo admin_url('admin.php?page=unstoppable-lms-course-builder&course_id='.$c->ID); ?>" style="font-weight:600;text-decoration:none"><?php echo esc_html($c->post_title); ?></a>
                                    <?php if($c->post_status==='draft'): ?><span class="lms-status lms-status--draft">Draft</span><?php endif; ?>
                                </div>
                                <a href="<?php echo admin_url('admin.php?page=unstoppable-lms-course-builder&course_id='.$c->ID); ?>" class="button button-small"><?php esc_html_e('Edit','unstoppable-lms'); ?></a>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                        <p><a href="<?php echo admin_url('admin.php?page=unstoppable-lms-courses'); ?>"><?php esc_html_e('View All →','unstoppable-lms'); ?></a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_courses() {
        $courses = get_posts(array('post_type'=>'lms_course','posts_per_page'=>-1,'post_status'=>array('publish','draft'),'orderby'=>'title','order'=>'ASC'));
        self::styles();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Courses','unstoppable-lms'); ?></h1>
            <hr class="wp-header-end">
            <form method="post" style="display:flex;gap:10px;margin:20px 0;max-width:500px">
                <?php wp_nonce_field('lms_create_course'); ?>
                <input type="text" name="course_title" placeholder="<?php esc_attr_e('New course title...','unstoppable-lms'); ?>" required style="flex:1">
                <button type="submit" name="lms_create_course" class="button button-primary"><?php esc_html_e('Create','unstoppable-lms'); ?></button>
            </form>
            <?php if(empty($courses)): ?>
                <div style="text-align:center;padding:60px;background:#fff;border:1px solid #c3c4c7;border-radius:4px">
                    <span class="dashicons dashicons-welcome-learn-more" style="font-size:48px;color:#c3c4c7"></span>
                    <h2><?php esc_html_e('No courses yet','unstoppable-lms'); ?></h2>
                </div>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px">
                <?php foreach($courses as $c):
                    $secs = get_post_meta($c->ID,'_lms_sections',true);
                    if(!is_array($secs)) $secs = array();
                    $lc = 0; foreach($secs as $s) $lc += count($s['lessons']??array());
                    $preview = get_permalink($c->ID);
                    if(!$preview) $preview = '#';
                ?>
                    <div class="lms-card" style="margin:0">
                        <div style="display:flex;justify-content:space-between">
                            <h3 style="margin:0 0 10px"><a href="<?php echo admin_url('admin.php?page=unstoppable-lms-course-builder&course_id='.$c->ID); ?>" style="text-decoration:none;color:#1d2327"><?php echo esc_html($c->post_title); ?></a></h3>
                            <span class="lms-status lms-status--<?php echo $c->post_status; ?>"><?php echo ucfirst($c->post_status); ?></span>
                        </div>
                        <p style="color:#646970;font-size:13px;margin-bottom:15px"><?php printf(__('%d lessons','unstoppable-lms'),$lc); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=unstoppable-lms-course-builder&course_id='.$c->ID); ?>" class="button button-primary"><?php esc_html_e('Edit','unstoppable-lms'); ?></a>
                        <a href="<?php echo esc_url($preview); ?>" class="button" target="_blank"><?php esc_html_e('Preview','unstoppable-lms'); ?></a>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_course_builder() {
        $cid = absint($_GET['course_id']??0);
        $course = get_post($cid);
        if(!$course || $course->post_type !== 'lms_course') { echo '<div class="wrap"><h1>Course not found</h1></div>'; return; }
        $tab = sanitize_text_field($_GET['tab']??'content');
        $secs = get_post_meta($cid,'_lms_sections',true);
        if(!is_array($secs)) $secs = array();
        if(empty($secs)) { $secs = array(array('id'=>'section_1','title'=>'Course Content','lessons'=>array())); update_post_meta($cid,'_lms_sections',$secs); }
        if(isset($_GET['new'])) echo '<div class="notice notice-success"><p>Course created!</p></div>';
        if(isset($_GET['lesson_created'])) echo '<div class="notice notice-success"><p>Lesson added.</p></div>';
        if(isset($_GET['quiz_created'])) echo '<div class="notice notice-success"><p>Quiz added.</p></div>';
        if(isset($_GET['section_added'])) echo '<div class="notice notice-success"><p>Section added.</p></div>';
        if(isset($_GET['deleted'])) echo '<div class="notice notice-success"><p>Item deleted.</p></div>';
        self::styles();
        $preview_url = get_permalink($cid);
        if(!$preview_url) $preview_url = '#';
        ?>
        <div class="wrap" style="max-width:1000px">
            <div style="display:flex;justify-content:space-between;margin-bottom:20px">
                <div>
                    <a href="<?php echo admin_url('admin.php?page=unstoppable-lms-courses'); ?>" style="font-size:13px;color:#646970;text-decoration:none">← All Courses</a>
                    <h1 style="margin:5px 0"><?php echo esc_html($course->post_title); ?> <span class="lms-status lms-status--<?php echo $course->post_status; ?>" style="vertical-align:middle;margin-left:10px"><?php echo ucfirst($course->post_status); ?></span></h1>
                </div>
                <a href="<?php echo esc_url($preview_url); ?>" class="button" target="_blank"><?php esc_html_e('Preview','unstoppable-lms'); ?></a>
            </div>
            <nav class="lms-tabs">
                <a href="<?php echo add_query_arg('tab','content'); ?>" class="lms-tab <?php echo $tab==='content'?'active':''; ?>">Content</a>
                <a href="<?php echo add_query_arg('tab','students'); ?>" class="lms-tab <?php echo $tab==='students'?'active':''; ?>">Students</a>
                <a href="<?php echo add_query_arg('tab','settings'); ?>" class="lms-tab <?php echo $tab==='settings'?'active':''; ?>">Settings</a>
            </nav>
            <div class="lms-card">
                <?php if($tab==='students'): self::course_students($cid); elseif($tab==='settings'): self::course_settings($cid,$course); else: self::course_content($cid,$secs); endif; ?>
            </div>
        </div>
        <?php
    }

    private static function course_content($cid,$secs) {
        foreach($secs as $sec): ?>
        <div class="lms-section">
            <div class="lms-section-head"><?php echo esc_html($sec['title']); ?></div>
            <div class="lms-section-body">
                <?php $lessons = $sec['lessons']??array(); $quizzes = $sec['quizzes']??array();
                if(empty($lessons) && empty($quizzes)): ?>
                    <p style="color:#646970;font-style:italic">No content yet.</p>
                <?php else:
                    foreach($lessons as $lid): $l = get_post($lid); if(!$l) continue; 
                    $disc = get_post_meta($lid,'_lms_discussion_enabled',true); 
                    $attachments = get_post_meta($lid,'_lms_attachments',true);
                    $att_count = is_array($attachments) ? count($attachments) : 0;
                    ?>
                    <div class="lms-item">
                        <span class="dashicons dashicons-media-document" style="color:#2271b1"></span>
                        <div class="lms-item-info"><b><?php echo esc_html($l->post_title); ?></b><span class="lms-item-meta">Lesson<?php if($disc) echo ' · 💬'; ?><?php if($att_count > 0) echo ' · 📎 '.$att_count; ?></span></div>
                        <div>
                            <a href="<?php echo admin_url('post.php?post='.$lid.'&action=edit'); ?>">Edit</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=unstoppable-lms-course-builder&course_id='.$cid.'&action=delete_item&item_id='.$lid),'delete_item_'.$lid); ?>" style="color:#b32d2e;margin-left:10px" onclick="return confirm('Delete?')">Delete</a>
                        </div>
                    </div>
                    <?php endforeach;
                    foreach($quizzes as $qid): $q = get_post($qid); if(!$q) continue; ?>
                    <div class="lms-item">
                        <span class="dashicons dashicons-forms" style="color:#9b59b6"></span>
                        <div class="lms-item-info"><b><?php echo esc_html($q->post_title); ?></b><span class="lms-item-meta">Quiz</span></div>
                        <div>
                            <a href="<?php echo admin_url('post.php?post='.$qid.'&action=edit'); ?>">Edit</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=unstoppable-lms-course-builder&course_id='.$cid.'&action=delete_item&item_id='.$qid),'delete_item_'.$qid); ?>" style="color:#b32d2e;margin-left:10px" onclick="return confirm('Delete?')">Delete</a>
                        </div>
                    </div>
                    <?php endforeach;
                endif; ?>
                <p style="margin-top:16px"><button type="button" class="button add-item" data-sec="<?php echo esc_attr($sec['id']); ?>"><span class="dashicons dashicons-plus-alt2" style="vertical-align:middle"></span> Add Item</button></p>
            </div>
        </div>
        <?php endforeach; ?>
        <form method="post" style="display:flex;gap:10px;margin-top:20px">
            <?php wp_nonce_field('lms_course_builder'); ?>
            <input type="hidden" name="course_id" value="<?php echo $cid; ?>">
            <input type="text" name="section_title" placeholder="New section title..." required>
            <button type="submit" name="lms_add_section" class="button">Add Section</button>
        </form>
        <div id="type-modal" class="lms-modal"><div class="lms-modal-bg"></div><div class="lms-modal-box"><div class="lms-modal-head"><h3>Add Item</h3><button class="lms-modal-close">&times;</button></div><div class="lms-modal-body"><div class="lms-types"><button type="button" class="lms-type" data-t="lesson"><span class="dashicons dashicons-media-document"></span><b>Lesson</b></button><button type="button" class="lms-type" data-t="quiz"><span class="dashicons dashicons-forms"></span><b>Quiz</b></button></div></div></div></div>
        <div id="lesson-modal" class="lms-modal"><div class="lms-modal-bg"></div><div class="lms-modal-box" style="width:700px"><form method="post"><?php wp_nonce_field('lms_course_builder'); ?><input type="hidden" name="course_id" value="<?php echo $cid; ?>"><input type="hidden" name="section_id" id="l-sec"><input type="hidden" name="attachment_ids" id="attachment-ids"><input type="hidden" name="embed_ids" id="embed-ids"><div class="lms-modal-head"><h3>Add Lesson</h3><button type="button" class="lms-modal-close">&times;</button></div><div class="lms-modal-body"><div class="lms-field"><label>Title *</label><input type="text" name="lesson_title" required></div><div class="lms-field"><label>Content</label><textarea name="lesson_content" rows="5"></textarea></div><div style="display:flex;gap:16px"><div class="lms-field" style="flex:1"><label>Video URL</label><input type="url" name="video_url" placeholder="YouTube, Vimeo, or direct video URL"></div><div class="lms-field" style="width:100px"><label>Duration (min)</label><input type="number" name="duration" min="0"></div></div><div class="lms-field"><label>Lesson Materials</label><div id="attachment-list" class="lms-attachment-list"></div><button type="button" class="button" id="add-attachment"><span class="dashicons dashicons-paperclip" style="vertical-align:middle;margin-right:4px"></span> Add Files</button><p class="description">Add PDFs, Word docs, or other files. Toggle "Embed" to display PDFs inline or as download links.</p></div><div class="lms-field"><label><input type="checkbox" name="enable_discussion" value="1"> Enable discussion</label></div></div><div class="lms-modal-foot"><button type="button" class="button lms-modal-close">Cancel</button> <button type="submit" name="lms_create_lesson" class="button button-primary">Add Lesson</button></div></form></div></div>
        <div id="quiz-modal" class="lms-modal"><div class="lms-modal-bg"></div><div class="lms-modal-box"><form method="post"><?php wp_nonce_field('lms_course_builder'); ?><input type="hidden" name="course_id" value="<?php echo $cid; ?>"><input type="hidden" name="section_id" id="q-sec"><div class="lms-modal-head"><h3>Add Quiz</h3><button type="button" class="lms-modal-close">&times;</button></div><div class="lms-modal-body"><div class="lms-field"><label>Title *</label><input type="text" name="quiz_title" required></div><div class="lms-field"><label>Passing Score (%)</label><input type="number" name="passing_score" value="80" min="0" max="100"></div></div><div class="lms-modal-foot"><button type="button" class="button lms-modal-close">Cancel</button> <button type="submit" name="lms_create_quiz" class="button button-primary">Add Quiz</button></div></form></div></div>
        <script>jQuery(function($){
            var sec='';
            var attachments = [];
            var mediaFrame;
            
            $('.add-item').on('click',function(){sec=$(this).data('sec');$('#type-modal').addClass('open');});
            $('.lms-type').on('click',function(){$('#type-modal').removeClass('open');if($(this).data('t')==='lesson'){$('#l-sec').val(sec);attachments=[];updateAttachmentList();$('#lesson-modal').addClass('open');}else{$('#q-sec').val(sec);$('#quiz-modal').addClass('open');}});
            $('.lms-modal-close,.lms-modal-bg').on('click',function(){$(this).closest('.lms-modal').removeClass('open');});
            
            // File attachment handling
            $('#add-attachment').on('click', function(e) {
                e.preventDefault();
                if (mediaFrame) {
                    mediaFrame.open();
                    return;
                }
                mediaFrame = wp.media({
                    title: '<?php esc_html_e( 'Select or Upload Files', 'unstoppable-lms' ); ?>',
                    button: { text: '<?php esc_html_e( 'Add to Lesson', 'unstoppable-lms' ); ?>' },
                    multiple: true
                });
                mediaFrame.on('select', function() {
                    var selection = mediaFrame.state().get('selection');
                    selection.each(function(attachment) {
                        var att = attachment.toJSON();
                        if (!attachments.find(function(a) { return a.id === att.id; })) {
                            var isPdf = att.mime === 'application/pdf';
                            attachments.push({ 
                                id: att.id, 
                                title: att.title || att.filename, 
                                url: att.url, 
                                icon: att.icon, 
                                mime: att.mime,
                                embed: isPdf // PDFs default to embed, others to download
                            });
                        }
                    });
                    updateAttachmentList();
                });
                mediaFrame.open();
            });
            
            function updateAttachmentList() {
                var $list = $('#attachment-list');
                $list.empty();
                var ids = [];
                var embedIds = [];
                
                attachments.forEach(function(att) {
                    ids.push(att.id);
                    if (att.embed) {
                        embedIds.push(att.id);
                    }
                    
                    var isPdf = att.mime === 'application/pdf';
                    var embedToggle = '';
                    
                    if (isPdf) {
                        var checked = att.embed ? 'checked' : '';
                        embedToggle = '<label class="lms-embed-toggle" style="margin-left:auto;margin-right:12px;display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;"><input type="checkbox" class="toggle-embed" data-id="'+att.id+'" '+checked+' style="margin:0"> Embed</label>';
                    }
                    
                    var modeLabel = '';
                    if (isPdf) {
                        modeLabel = att.embed ? 
                            '<span style="background:#2271b1;color:#fff;font-size:10px;padding:2px 6px;border-radius:3px;margin-left:8px">Will Embed</span>' : 
                            '<span style="background:#646970;color:#fff;font-size:10px;padding:2px 6px;border-radius:3px;margin-left:8px">Download Only</span>';
                    } else {
                        modeLabel = '<span style="background:#646970;color:#fff;font-size:10px;padding:2px 6px;border-radius:3px;margin-left:8px">Download</span>';
                    }
                    
                    $list.append(
                        '<div class="lms-attachment-item" data-id="'+att.id+'" style="flex-wrap:wrap;">' +
                            '<img src="'+att.icon+'" alt="" style="width:20px;height:20px;vertical-align:middle;margin-right:8px">' +
                            '<span style="flex:1">'+att.title+modeLabel+'</span>' +
                            embedToggle +
                            '<button type="button" class="remove-attachment" style="background:none;border:none;color:#b32d2e;cursor:pointer;font-size:18px">&times;</button>' +
                        '</div>'
                    );
                });
                
                $('#attachment-ids').val(ids.join(','));
                $('#embed-ids').val(embedIds.join(','));
            }
            
            // Toggle embed for individual files
            $(document).on('change', '.toggle-embed', function() {
                var id = parseInt($(this).data('id'));
                var isChecked = $(this).is(':checked');
                
                attachments.forEach(function(att) {
                    if (att.id === id) {
                        att.embed = isChecked;
                    }
                });
                
                updateAttachmentList();
            });
            
            $(document).on('click', '.remove-attachment', function() {
                var id = $(this).closest('.lms-attachment-item').data('id');
                attachments = attachments.filter(function(a) { return a.id !== id; });
                updateAttachmentList();
            });
        });</script>
        <?php
    }

    private static function course_students($cid) {
        global $wpdb;
        $tbl = $wpdb->prefix.'lms_enrollments';
        $has = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s",$tbl))===$tbl;
        $students = $has ? $wpdb->get_results($wpdb->prepare("SELECT e.*,u.display_name,u.user_email FROM {$tbl} e JOIN {$wpdb->users} u ON e.user_id=u.ID WHERE e.course_id=%d ORDER BY e.enrolled_at DESC",$cid)) : array();
        $users = get_users(array('orderby'=>'display_name'));
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h2 style="margin:0">Enrolled Students</h2>
            <button type="button" class="button" id="show-enroll">Enroll Student</button>
        </div>
        <form method="post" id="enroll-form" style="display:none;padding:16px;background:#f9f9f9;border-radius:4px;margin-bottom:20px">
            <?php wp_nonce_field('lms_enroll_student'); ?>
            <input type="hidden" name="course_id" value="<?php echo $cid; ?>">
            <select name="user_id" required style="min-width:300px">
                <option value="">Select student...</option>
                <?php foreach($users as $u): ?><option value="<?php echo $u->ID; ?>"><?php echo esc_html($u->display_name); ?> (<?php echo esc_html($u->user_email); ?>)</option><?php endforeach; ?>
            </select>
            <button type="submit" name="lms_enroll_student" class="button button-primary">Enroll</button>
            <button type="button" class="button" id="hide-enroll">Cancel</button>
        </form>
        <?php if(empty($students)): ?>
            <p style="color:#646970;font-style:italic">No students enrolled yet.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Student</th><th>Enrolled</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($students as $s): ?>
                    <tr><td><b><?php echo esc_html($s->display_name); ?></b><br><small><?php echo esc_html($s->user_email); ?></small></td><td><?php echo date_i18n(get_option('date_format'),strtotime($s->enrolled_at)); ?></td><td><span class="lms-status lms-status--<?php echo $s->status; ?>"><?php echo ucfirst($s->status); ?></span></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <script>jQuery(function($){$('#show-enroll').on('click',function(){$('#enroll-form').slideDown();});$('#hide-enroll').on('click',function(){$('#enroll-form').slideUp();});});</script>
        <?php
    }

    private static function course_settings($cid,$course) {
        // Handle form submission
        if ( isset( $_POST['lms_save_course_settings'] ) && check_admin_referer( 'lms_course_settings' ) ) {
            update_post_meta( $cid, '_lms_access_type', sanitize_text_field( $_POST['access_type'] ?? 'open' ) );
            update_post_meta( $cid, '_lms_price', sanitize_text_field( $_POST['course_price'] ?? '' ) );
            update_post_meta( $cid, '_lms_max_students', absint( $_POST['max_students'] ?? 0 ) );
            update_post_meta( $cid, '_lms_course_duration', sanitize_text_field( $_POST['course_duration'] ?? '' ) );
            update_post_meta( $cid, '_lms_difficulty', sanitize_text_field( $_POST['difficulty'] ?? '' ) );
            update_post_meta( $cid, '_lms_certificate_enabled', isset( $_POST['certificate_enabled'] ) ? 1 : 0 );
            update_post_meta( $cid, '_lms_sequential_progress', isset( $_POST['sequential_progress'] ) ? 1 : 0 );
            update_post_meta( $cid, '_lms_course_materials', wp_kses_post( $_POST['course_materials'] ?? '' ) );
            
            // Update post status if changed
            if ( ! empty( $_POST['post_status'] ) && $_POST['post_status'] !== $course->post_status ) {
                wp_update_post( array(
                    'ID' => $cid,
                    'post_status' => sanitize_text_field( $_POST['post_status'] )
                ) );
                $course = get_post( $cid ); // Refresh
            }
            
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'unstoppable-lms' ) . '</p></div>';
        }

        // Get current values
        $access_type = get_post_meta( $cid, '_lms_access_type', true ) ?: 'open';
        $price = get_post_meta( $cid, '_lms_price', true );
        $max_students = get_post_meta( $cid, '_lms_max_students', true );
        $course_duration = get_post_meta( $cid, '_lms_course_duration', true );
        $difficulty = get_post_meta( $cid, '_lms_difficulty', true );
        $certificate_enabled = get_post_meta( $cid, '_lms_certificate_enabled', true );
        $sequential_progress = get_post_meta( $cid, '_lms_sequential_progress', true );
        $course_materials = get_post_meta( $cid, '_lms_course_materials', true );
        ?>
        <form method="post">
            <?php wp_nonce_field( 'lms_course_settings' ); ?>
            
            <h2 style="margin-top:0"><?php esc_html_e( 'General Settings', 'unstoppable-lms' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="post_status"><?php esc_html_e( 'Status', 'unstoppable-lms' ); ?></label></th>
                    <td>
                        <select name="post_status" id="post_status">
                            <option value="publish" <?php selected( $course->post_status, 'publish' ); ?>><?php esc_html_e( 'Published', 'unstoppable-lms' ); ?></option>
                            <option value="draft" <?php selected( $course->post_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'unstoppable-lms' ); ?></option>
                            <option value="private" <?php selected( $course->post_status, 'private' ); ?>><?php esc_html_e( 'Private', 'unstoppable-lms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="difficulty"><?php esc_html_e( 'Difficulty Level', 'unstoppable-lms' ); ?></label></th>
                    <td>
                        <select name="difficulty" id="difficulty">
                            <option value="" <?php selected( $difficulty, '' ); ?>><?php esc_html_e( '-- Select --', 'unstoppable-lms' ); ?></option>
                            <option value="beginner" <?php selected( $difficulty, 'beginner' ); ?>><?php esc_html_e( 'Beginner', 'unstoppable-lms' ); ?></option>
                            <option value="intermediate" <?php selected( $difficulty, 'intermediate' ); ?>><?php esc_html_e( 'Intermediate', 'unstoppable-lms' ); ?></option>
                            <option value="advanced" <?php selected( $difficulty, 'advanced' ); ?>><?php esc_html_e( 'Advanced', 'unstoppable-lms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="course_duration"><?php esc_html_e( 'Course Duration', 'unstoppable-lms' ); ?></label></th>
                    <td>
                        <input type="text" name="course_duration" id="course_duration" value="<?php echo esc_attr( $course_duration ); ?>" placeholder="e.g., 4 weeks, 10 hours" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Estimated time to complete the course.', 'unstoppable-lms' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Access & Enrollment', 'unstoppable-lms' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="access_type"><?php esc_html_e( 'Access Type', 'unstoppable-lms' ); ?></label></th>
                    <td>
                        <select name="access_type" id="access_type">
                            <option value="open" <?php selected( $access_type, 'open' ); ?>><?php esc_html_e( 'Open - Anyone can enroll for free', 'unstoppable-lms' ); ?></option>
                            <option value="closed" <?php selected( $access_type, 'closed' ); ?>><?php esc_html_e( 'Closed - Admin enrollment only', 'unstoppable-lms' ); ?></option>
                            <option value="paid" <?php selected( $access_type, 'paid' ); ?>><?php esc_html_e( 'Paid - Requires purchase', 'unstoppable-lms' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr class="price-row" style="<?php echo $access_type !== 'paid' ? 'display:none;' : ''; ?>">
                    <th><label for="course_price"><?php esc_html_e( 'Price', 'unstoppable-lms' ); ?></label></th>
                    <td>
                        <input type="text" name="course_price" id="course_price" value="<?php echo esc_attr( $price ); ?>" placeholder="e.g., 99.00" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Course price (integrates with WooCommerce if installed).', 'unstoppable-lms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="max_students"><?php esc_html_e( 'Max Students', 'unstoppable-lms' ); ?></label></th>
                    <td>
                        <input type="number" name="max_students" id="max_students" value="<?php echo esc_attr( $max_students ); ?>" min="0" class="small-text">
                        <p class="description"><?php esc_html_e( 'Maximum number of students (0 = unlimited).', 'unstoppable-lms' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Progress & Completion', 'unstoppable-lms' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Sequential Progress', 'unstoppable-lms' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="sequential_progress" value="1" <?php checked( $sequential_progress ); ?>>
                            <?php esc_html_e( 'Students must complete lessons in order', 'unstoppable-lms' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Certificate', 'unstoppable-lms' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="certificate_enabled" value="1" <?php checked( $certificate_enabled ); ?>>
                            <?php esc_html_e( 'Award certificate on course completion', 'unstoppable-lms' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Course Materials', 'unstoppable-lms' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="course_materials"><?php esc_html_e( 'Materials & Requirements', 'unstoppable-lms' ); ?></label></th>
                    <td>
                        <textarea name="course_materials" id="course_materials" rows="4" class="large-text"><?php echo esc_textarea( $course_materials ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'List any required materials, software, or prerequisites for this course.', 'unstoppable-lms' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Course Info', 'unstoppable-lms' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Created', 'unstoppable-lms' ); ?></th>
                    <td><?php echo get_the_date( '', $course ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Modified', 'unstoppable-lms' ); ?></th>
                    <td><?php echo get_the_modified_date( '', $course ); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Author', 'unstoppable-lms' ); ?></th>
                    <td><?php echo get_the_author_meta( 'display_name', $course->post_author ); ?></td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="lms_save_course_settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'unstoppable-lms' ); ?></button>
                <a href="<?php echo admin_url('post.php?post='.$cid.'&action=edit'); ?>" class="button"><?php esc_html_e( 'Edit in WordPress', 'unstoppable-lms' ); ?></a>
            </p>
        </form>

        <script>
        jQuery(function($) {
            $('#access_type').on('change', function() {
                if ($(this).val() === 'paid') {
                    $('.price-row').show();
                } else {
                    $('.price-row').hide();
                }
            });
        });
        </script>
        <?php
    }

    public static function render_students() {
        global $wpdb;
        $tbl = $wpdb->prefix.'lms_enrollments';
        $has = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s",$tbl))===$tbl;
        self::styles();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('All Students','unstoppable-lms'); ?></h1>
            <p>View all enrolled students across courses.</p>
            <?php if(!$has): ?>
                <p>Database not ready. Deactivate and reactivate the plugin.</p>
            <?php else:
                $rows = $wpdb->get_results("SELECT e.*,u.display_name,u.user_email,p.post_title FROM {$tbl} e JOIN {$wpdb->users} u ON e.user_id=u.ID JOIN {$wpdb->posts} p ON e.course_id=p.ID ORDER BY e.enrolled_at DESC LIMIT 100");
                if(empty($rows)): ?>
                    <p style="color:#646970;font-style:italic">No students enrolled yet.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped" style="margin-top:20px">
                        <thead><tr><th>Student</th><th>Course</th><th>Enrolled</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach($rows as $r): ?>
                            <tr><td><b><?php echo esc_html($r->display_name); ?></b><br><small><?php echo esc_html($r->user_email); ?></small></td><td><a href="<?php echo admin_url('admin.php?page=unstoppable-lms-course-builder&course_id='.$r->course_id.'&tab=students'); ?>"><?php echo esc_html($r->post_title); ?></a></td><td><?php echo date_i18n(get_option('date_format'),strtotime($r->enrolled_at)); ?></td><td><span class="lms-status lms-status--<?php echo $r->status; ?>"><?php echo ucfirst($r->status); ?></span></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the Gradebook page.
     */
    public static function render_gradebook() {
        $course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;

        // Handle CSV export
        if ( isset( $_GET['export'] ) && 'csv' === $_GET['export'] && $course_id ) {
            if ( ! current_user_can( 'view_lms_reports' ) ) {
                wp_die( esc_html__( 'Permission denied.', 'unstoppable-lms' ) );
            }
            
            $course = get_post( $course_id );
            $filename = sanitize_file_name( $course->post_title . '-gradebook-' . date( 'Y-m-d' ) ) . '.csv';
            
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=' . $filename );
            
            echo Unstoppable_LMS_Gradebook::export_csv( $course_id );
            exit;
        }

        self::styles();
        self::gradebook_styles();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Gradebook', 'unstoppable-lms' ); ?></h1>
            <?php self::render_gradebook_tab( $course_id, 0 ); ?>
        </div>
        <?php
    }

    public static function render_reports() {
        $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';

        self::styles();
        self::gradebook_styles();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Reports', 'unstoppable-lms' ); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-reports&tab=overview' ) ); ?>" class="nav-tab <?php echo 'overview' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Overview', 'unstoppable-lms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-reports&tab=students' ) ); ?>" class="nav-tab <?php echo 'students' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Student Progress', 'unstoppable-lms' ); ?>
                </a>
            </nav>

            <?php if ( 'overview' === $current_tab ) : ?>
                <?php self::render_overview_tab(); ?>
            <?php elseif ( 'students' === $current_tab ) : ?>
                <?php self::render_students_progress_tab(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Gradebook-specific styles.
     */
    private static function gradebook_styles() {
        ?>
        <style>
            .gradebook-filters { display: flex; gap: 15px; align-items: center; margin: 20px 0; flex-wrap: wrap; }
            .gradebook-filters select { min-width: 250px; }
            .gradebook-table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .gradebook-table th, .gradebook-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
            .gradebook-table th { background: #f8f9fa; font-weight: 600; position: sticky; top: 0; }
            .gradebook-table tr:hover { background: #f5f5f5; }
            .gradebook-table .grade-cell { text-align: center; min-width: 80px; }
            .gradebook-table .student-cell { min-width: 200px; }
            .gradebook-table .overall-cell { background: #f0f7ff; font-weight: 600; }
            
            .grade-display { display: flex; flex-direction: column; align-items: center; gap: 2px; }
            .grade-points { font-size: 14px; font-weight: 600; color: #1d2327; }
            .grade-percent { font-size: 12px; color: #646970; }
            .grade-letter { font-size: 11px; padding: 2px 6px; border-radius: 3px; font-weight: 600; }
            
            .grade-a { background: #d1fae5; color: #065f46; }
            .grade-b { background: #dbeafe; color: #1e40af; }
            .grade-c { background: #fef3c7; color: #92400e; }
            .grade-d { background: #fed7aa; color: #9a3412; }
            .grade-f { background: #fee2e2; color: #991b1b; }
            
            .grade-na { background: #f3f4f6; color: #6b7280; font-style: italic; }
            
            .student-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
            .summary-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; }
            .summary-card h3 { margin: 0 0 15px 0; font-size: 14px; color: #646970; text-transform: uppercase; }
            .summary-card .big-number { font-size: 36px; font-weight: 700; color: #1d2327; }
            .summary-card .sub-text { font-size: 14px; color: #646970; margin-top: 5px; }
            
            .grade-input-row { display: flex; gap: 10px; align-items: center; margin: 10px 0; }
            .grade-input-row input[type="number"] { width: 80px; }
            .grade-input-row .of-text { color: #646970; }
            
            .manual-grade-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .manual-grade-form h3 { margin-top: 0; }
            
            .export-btn { float: right; }
        </style>
        <?php
    }

    /**
     * Render the gradebook tab.
     */
    private static function render_gradebook_tab( int $course_id, int $user_id ) {
        // Get all courses
        $courses = get_posts( array(
            'post_type'      => 'lms_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        ?>
        <div class="gradebook-filters">
            <label for="course-select"><strong><?php esc_html_e( 'Select Course:', 'unstoppable-lms' ); ?></strong></label>
            <select id="course-select" onchange="window.location.href='<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-reports&tab=gradebook&course_id=' ) ); ?>' + this.value">
                <option value=""><?php esc_html_e( '— Select a Course —', 'unstoppable-lms' ); ?></option>
                <?php foreach ( $courses as $course ) : ?>
                    <option value="<?php echo esc_attr( $course->ID ); ?>" <?php selected( $course_id, $course->ID ); ?>>
                        <?php echo esc_html( $course->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <?php if ( $course_id ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-reports&tab=gradebook&course_id=' . $course_id . '&export=csv' ) ); ?>" class="button export-btn">
                    <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Export CSV', 'unstoppable-lms' ); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if ( ! $course_id ) : ?>
            <div class="lms-card">
                <p><?php esc_html_e( 'Please select a course to view the gradebook.', 'unstoppable-lms' ); ?></p>
            </div>
        <?php else : 
            $matrix = Unstoppable_LMS_Gradebook::get_gradebook_matrix( $course_id );
            $course = get_post( $course_id );
            
            if ( empty( $matrix['rows'] ) ) : ?>
                <div class="lms-card">
                    <p><?php esc_html_e( 'No enrolled students or grades found for this course.', 'unstoppable-lms' ); ?></p>
                </div>
            <?php else : ?>
                <div class="lms-card" style="overflow-x: auto;">
                    <h2><?php echo esc_html( $course->post_title ); ?> — <?php esc_html_e( 'Gradebook', 'unstoppable-lms' ); ?></h2>
                    
                    <table class="gradebook-table">
                        <thead>
                            <tr>
                                <th class="student-cell"><?php esc_html_e( 'Student', 'unstoppable-lms' ); ?></th>
                                <?php foreach ( $matrix['columns'] as $col ) : ?>
                                    <th class="grade-cell">
                                        <?php echo esc_html( $col['title'] ?: __( 'Item', 'unstoppable-lms' ) ); ?>
                                        <br><small style="color:#646970;"><?php echo esc_html( $col['max_points'] ); ?> pts</small>
                                    </th>
                                <?php endforeach; ?>
                                <th class="grade-cell overall-cell"><?php esc_html_e( 'Overall', 'unstoppable-lms' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $matrix['rows'] as $row ) : ?>
                                <tr>
                                    <td class="student-cell">
                                        <strong><?php echo esc_html( $row['display_name'] ); ?></strong>
                                        <br><small style="color:#646970;"><?php echo esc_html( $row['user_email'] ); ?></small>
                                    </td>
                                    <?php foreach ( $matrix['columns'] as $col ) : 
                                        $grade = $row['grades'][ $col['gradable_id'] ] ?? null;
                                    ?>
                                        <td class="grade-cell">
                                            <?php if ( $grade ) : ?>
                                                <div class="grade-display">
                                                    <span class="grade-points"><?php echo esc_html( $grade['points_display'] ); ?></span>
                                                    <span class="grade-percent"><?php echo esc_html( number_format( $grade['grade'], 0 ) ); ?>%</span>
                                                    <span class="grade-letter <?php echo esc_attr( self::get_grade_class( $grade['letter_grade'] ) ); ?>">
                                                        <?php echo esc_html( $grade['letter_grade'] ); ?>
                                                    </span>
                                                </div>
                                            <?php else : ?>
                                                <span class="grade-letter grade-na">—</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="grade-cell overall-cell">
                                        <div class="grade-display">
                                            <span class="grade-points"><?php echo esc_html( $row['overall']['points_display'] ); ?></span>
                                            <span class="grade-percent"><?php echo esc_html( $row['overall']['percentage'] ); ?>%</span>
                                            <span class="grade-letter <?php echo esc_attr( self::get_grade_class( $row['overall']['letter_grade'] ) ); ?>">
                                                <?php echo esc_html( $row['overall']['letter_grade'] ); ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Manual Grade Entry -->
                <?php self::render_manual_grade_form( $course_id ); ?>

            <?php endif; ?>
        <?php endif; ?>
        <?php
    }

    /**
     * Get CSS class for letter grade.
     */
    private static function get_grade_class( string $letter ): string {
        $first = strtoupper( substr( $letter, 0, 1 ) );
        $classes = array(
            'A' => 'grade-a',
            'B' => 'grade-b',
            'C' => 'grade-c',
            'D' => 'grade-d',
            'F' => 'grade-f',
        );
        return $classes[ $first ] ?? 'grade-na';
    }

    /**
     * Render manual grade entry form.
     */
    private static function render_manual_grade_form( int $course_id ) {
        // Handle form submission
        if ( isset( $_POST['add_manual_grade'] ) ) {
            if ( ! check_admin_referer( 'unstoppable_lms_manual_grade' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'unstoppable-lms' ) );
            }
            if ( ! current_user_can( 'view_lms_reports' ) ) {
                wp_die( esc_html__( 'Permission denied.', 'unstoppable-lms' ) );
            }

            $student_id = absint( $_POST['student_id'] ?? 0 );
            $item_title = sanitize_text_field( wp_unslash( $_POST['item_title'] ?? '' ) );
            $item_type = sanitize_key( $_POST['item_type'] ?? 'manual' );
            $points = floatval( $_POST['points_earned'] ?? 0 );
            $max_points = floatval( $_POST['points_possible'] ?? 0 );
            $notes = sanitize_textarea_field( wp_unslash( $_POST['grade_notes'] ?? '' ) );

            if ( $student_id && $item_title && $max_points > 0 ) {
                // Create a "manual grade" post to reference
                $gradable_id = wp_insert_post( array(
                    'post_type'   => 'lms_grade_item',
                    'post_title'  => $item_title,
                    'post_status' => 'publish',
                    'meta_input'  => array(
                        '_lms_course_id' => $course_id,
                        '_lms_max_points' => $max_points,
                    ),
                ) );

                if ( $gradable_id ) {
                    Unstoppable_LMS_Gradebook::record_grade(
                        $student_id,
                        $course_id,
                        $gradable_id,
                        $item_type,
                        $points,
                        $max_points,
                        array( 'notes' => $notes )
                    );
                    
                    Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Grade recorded successfully.', 'unstoppable-lms' ), 'success' );
                    wp_safe_redirect( admin_url( 'admin.php?page=unstoppable-lms-reports&tab=gradebook&course_id=' . $course_id ) );
                    exit;
                }
            }
        }

        // Get enrolled students
        $students = Unstoppable_LMS_Enrollment::get_course_students( $course_id, 'active', 100 );
        ?>
        <div class="manual-grade-form">
            <h3><?php esc_html_e( 'Add Manual Grade', 'unstoppable-lms' ); ?></h3>
            <form method="post">
                <?php wp_nonce_field( 'unstoppable_lms_manual_grade' ); ?>
                <input type="hidden" name="course_id" value="<?php echo esc_attr( $course_id ); ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="student_id"><?php esc_html_e( 'Student', 'unstoppable-lms' ); ?></label></th>
                        <td>
                            <select name="student_id" id="student_id" required>
                                <option value=""><?php esc_html_e( '— Select Student —', 'unstoppable-lms' ); ?></option>
                                <?php foreach ( $students as $student ) : ?>
                                    <option value="<?php echo esc_attr( $student['user_id'] ); ?>">
                                        <?php echo esc_html( $student['display_name'] . ' (' . $student['user_email'] . ')' ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="item_title"><?php esc_html_e( 'Item Name', 'unstoppable-lms' ); ?></label></th>
                        <td>
                            <input type="text" name="item_title" id="item_title" class="regular-text" required placeholder="<?php esc_attr_e( 'e.g., Final Exam, Participation', 'unstoppable-lms' ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="item_type"><?php esc_html_e( 'Type', 'unstoppable-lms' ); ?></label></th>
                        <td>
                            <select name="item_type" id="item_type">
                                <option value="exam"><?php esc_html_e( 'Exam', 'unstoppable-lms' ); ?></option>
                                <option value="quiz"><?php esc_html_e( 'Quiz', 'unstoppable-lms' ); ?></option>
                                <option value="assignment"><?php esc_html_e( 'Assignment', 'unstoppable-lms' ); ?></option>
                                <option value="project"><?php esc_html_e( 'Project', 'unstoppable-lms' ); ?></option>
                                <option value="participation"><?php esc_html_e( 'Participation', 'unstoppable-lms' ); ?></option>
                                <option value="manual" selected><?php esc_html_e( 'Manual Grade', 'unstoppable-lms' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Points', 'unstoppable-lms' ); ?></label></th>
                        <td>
                            <div class="grade-input-row">
                                <input type="number" name="points_earned" step="0.5" min="0" required placeholder="13">
                                <span class="of-text"><?php esc_html_e( 'out of', 'unstoppable-lms' ); ?></span>
                                <input type="number" name="points_possible" step="0.5" min="0.5" required placeholder="15">
                                <span class="of-text"><?php esc_html_e( 'points', 'unstoppable-lms' ); ?></span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="grade_notes"><?php esc_html_e( 'Notes', 'unstoppable-lms' ); ?></label></th>
                        <td>
                            <textarea name="grade_notes" id="grade_notes" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Optional notes about this grade...', 'unstoppable-lms' ); ?>"></textarea>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button type="submit" name="add_manual_grade" class="button button-primary">
                        <?php esc_html_e( 'Add Grade', 'unstoppable-lms' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render overview tab with statistics.
     */
    private static function render_overview_tab() {
        global $wpdb;
        
        $enrollments_table = $wpdb->prefix . 'lms_enrollments';
        $progress_table = $wpdb->prefix . 'lms_progress';
        $grades_table = $wpdb->prefix . 'lms_grades';

        // Get stats
        $total_courses = wp_count_posts( 'lms_course' )->publish;
        $total_lessons = wp_count_posts( 'lms_lesson' )->publish;
        $total_quizzes = wp_count_posts( 'lms_quiz' )->publish;
        
        $total_enrollments = $wpdb->get_var( "SELECT COUNT(*) FROM {$enrollments_table} WHERE status = 'active'" );
        $total_completions = $wpdb->get_var( "SELECT COUNT(*) FROM {$progress_table} WHERE status = 'completed'" );
        $avg_grade = $wpdb->get_var( "SELECT AVG(grade) FROM {$grades_table}" );

        ?>
        <div class="student-summary" style="margin-top:20px;">
            <div class="summary-card">
                <h3><?php esc_html_e( 'Total Courses', 'unstoppable-lms' ); ?></h3>
                <div class="big-number"><?php echo esc_html( number_format( $total_courses ) ); ?></div>
            </div>
            <div class="summary-card">
                <h3><?php esc_html_e( 'Total Lessons', 'unstoppable-lms' ); ?></h3>
                <div class="big-number"><?php echo esc_html( number_format( $total_lessons ) ); ?></div>
            </div>
            <div class="summary-card">
                <h3><?php esc_html_e( 'Total Quizzes', 'unstoppable-lms' ); ?></h3>
                <div class="big-number"><?php echo esc_html( number_format( $total_quizzes ) ); ?></div>
            </div>
            <div class="summary-card">
                <h3><?php esc_html_e( 'Active Enrollments', 'unstoppable-lms' ); ?></h3>
                <div class="big-number"><?php echo esc_html( number_format( $total_enrollments ?: 0 ) ); ?></div>
            </div>
            <div class="summary-card">
                <h3><?php esc_html_e( 'Lessons Completed', 'unstoppable-lms' ); ?></h3>
                <div class="big-number"><?php echo esc_html( number_format( $total_completions ?: 0 ) ); ?></div>
            </div>
            <div class="summary-card">
                <h3><?php esc_html_e( 'Average Grade', 'unstoppable-lms' ); ?></h3>
                <div class="big-number">
                    <?php 
                    if ( $avg_grade ) {
                        echo esc_html( number_format( $avg_grade, 1 ) . '%' );
                    } else {
                        esc_html_e( 'N/A', 'unstoppable-lms' );
                    }
                    ?>
                </div>
                <?php if ( $avg_grade ) : ?>
                    <div class="sub-text">
                        <span class="grade-letter <?php echo esc_attr( self::get_grade_class( Unstoppable_LMS_Gradebook::percentage_to_letter( $avg_grade ) ) ); ?>">
                            <?php echo esc_html( Unstoppable_LMS_Gradebook::percentage_to_letter( $avg_grade ) ); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="lms-card">
            <h2><?php esc_html_e( 'Recent Activity', 'unstoppable-lms' ); ?></h2>
            <?php
            $recent_progress = $wpdb->get_results(
                "SELECT p.*, u.display_name, 
                        COALESCE(post.post_title, 'Unknown') as item_title
                 FROM {$progress_table} p
                 JOIN {$wpdb->users} u ON p.user_id = u.ID
                 LEFT JOIN {$wpdb->posts} post ON p.item_id = post.ID
                 WHERE p.status = 'completed'
                 ORDER BY p.completed_at DESC
                 LIMIT 10"
            );

            if ( $recent_progress ) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Student', 'unstoppable-lms' ); ?></th>
                            <th><?php esc_html_e( 'Item', 'unstoppable-lms' ); ?></th>
                            <th><?php esc_html_e( 'Type', 'unstoppable-lms' ); ?></th>
                            <th><?php esc_html_e( 'Completed', 'unstoppable-lms' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $recent_progress as $item ) : ?>
                            <tr>
                                <td><?php echo esc_html( $item->display_name ); ?></td>
                                <td><?php echo esc_html( $item->item_title ); ?></td>
                                <td><?php echo esc_html( ucfirst( $item->item_type ) ); ?></td>
                                <td><?php echo esc_html( human_time_diff( strtotime( $item->completed_at ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No recent activity.', 'unstoppable-lms' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render student progress tab.
     */
    private static function render_students_progress_tab() {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'lms_enrollments';

        // Get all enrolled students with course counts
        $students = $wpdb->get_results(
            "SELECT e.user_id, u.display_name, u.user_email,
                    COUNT(DISTINCT e.course_id) as course_count,
                    MAX(e.enrolled_at) as last_enrolled
             FROM {$enrollments_table} e
             JOIN {$wpdb->users} u ON e.user_id = u.ID
             WHERE e.status = 'active'
             GROUP BY e.user_id
             ORDER BY u.display_name"
        );

        ?>
        <div class="lms-card" style="margin-top:20px;">
            <h2><?php esc_html_e( 'Student Progress', 'unstoppable-lms' ); ?></h2>
            
            <?php if ( $students ) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Student', 'unstoppable-lms' ); ?></th>
                            <th><?php esc_html_e( 'Email', 'unstoppable-lms' ); ?></th>
                            <th><?php esc_html_e( 'Courses Enrolled', 'unstoppable-lms' ); ?></th>
                            <th><?php esc_html_e( 'Last Enrolled', 'unstoppable-lms' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'unstoppable-lms' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $students as $student ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $student->display_name ); ?></strong></td>
                                <td><?php echo esc_html( $student->user_email ); ?></td>
                                <td><?php echo esc_html( $student->course_count ); ?></td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $student->last_enrolled ) ) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $student->user_id ) ); ?>" class="button button-small">
                                        <?php esc_html_e( 'View Profile', 'unstoppable-lms' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No enrolled students found.', 'unstoppable-lms' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_settings() {
        // Handle form submission
        if ( isset( $_POST['unstoppable_lms_save_settings'] ) ) {
            if ( ! check_admin_referer( 'unstoppable_lms_settings' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'unstoppable-lms' ) );
            }
            if ( ! current_user_can( 'manage_lms_settings' ) ) {
                wp_die( esc_html__( 'You do not have permission to change settings.', 'unstoppable-lms' ) );
            }

            // General Settings
            $general = array(
                'currency'           => sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) ),
                'currency_position'  => sanitize_key( $_POST['currency_position'] ?? 'before' ),
                'date_format'        => sanitize_text_field( wp_unslash( $_POST['date_format'] ?? 'F j, Y' ) ),
            );
            update_option( 'unstoppable_lms_general', $general );

            // Course Settings
            $courses = array(
                'default_access'       => sanitize_key( $_POST['default_access'] ?? 'open' ),
                'enable_reviews'       => isset( $_POST['enable_reviews'] ) ? 1 : 0,
                'enable_certificates'  => isset( $_POST['enable_certificates'] ) ? 1 : 0,
                'sequential_default'   => isset( $_POST['sequential_default'] ) ? 1 : 0,
            );
            update_option( 'unstoppable_lms_courses', $courses );

            // Lesson Settings
            $lessons = array(
                'enable_comments'      => isset( $_POST['enable_comments'] ) ? 1 : 0,
                'video_autoplay'       => isset( $_POST['video_autoplay'] ) ? 1 : 0,
                'mark_complete_button' => isset( $_POST['mark_complete_button'] ) ? 1 : 0,
                'completion_text'      => sanitize_text_field( wp_unslash( $_POST['completion_text'] ?? 'Mark Complete' ) ),
            );
            update_option( 'unstoppable_lms_lessons', $lessons );

            // Email Settings
            $emails = array(
                'from_name'           => sanitize_text_field( wp_unslash( $_POST['from_name'] ?? get_bloginfo( 'name' ) ) ),
                'from_email'          => sanitize_email( wp_unslash( $_POST['from_email'] ?? get_option( 'admin_email' ) ) ),
                'enrollment_email'    => isset( $_POST['enrollment_email'] ) ? 1 : 0,
                'completion_email'    => isset( $_POST['completion_email'] ) ? 1 : 0,
            );
            update_option( 'unstoppable_lms_emails', $emails );

            // Pages Settings
            $pages = array(
                'courses_page'   => absint( $_POST['courses_page'] ?? 0 ),
                'account_page'   => absint( $_POST['account_page'] ?? 0 ),
            );
            update_option( 'unstoppable_lms_pages', $pages );

            Unstoppable_LMS_Admin_Notices::add_flash_notice( __( 'Settings saved successfully.', 'unstoppable-lms' ), 'success' );
            wp_safe_redirect( admin_url( 'admin.php?page=unstoppable-lms-settings&saved=1' ) );
            exit;
        }

        // Get current settings
        $general = get_option( 'unstoppable_lms_general', array() );
        $courses = get_option( 'unstoppable_lms_courses', array() );
        $lessons = get_option( 'unstoppable_lms_lessons', array() );
        $emails  = get_option( 'unstoppable_lms_emails', array() );
        $pages   = get_option( 'unstoppable_lms_pages', array() );

        // Defaults
        $general = wp_parse_args( $general, array(
            'currency'          => 'USD',
            'currency_position' => 'before',
            'date_format'       => 'F j, Y',
        ) );
        $courses = wp_parse_args( $courses, array(
            'default_access'      => 'open',
            'enable_reviews'      => 0,
            'enable_certificates' => 0,
            'sequential_default'  => 0,
        ) );
        $lessons = wp_parse_args( $lessons, array(
            'enable_comments'      => 1,
            'video_autoplay'       => 0,
            'mark_complete_button' => 1,
            'completion_text'      => 'Mark Complete',
        ) );
        $emails = wp_parse_args( $emails, array(
            'from_name'        => get_bloginfo( 'name' ),
            'from_email'       => get_option( 'admin_email' ),
            'enrollment_email' => 1,
            'completion_email' => 1,
        ) );
        $pages = wp_parse_args( $pages, array(
            'courses_page' => 0,
            'account_page' => 0,
        ) );

        $current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

        self::styles();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Unstoppable LMS Settings', 'unstoppable-lms' ); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-settings&tab=general' ) ); ?>" class="nav-tab <?php echo 'general' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'General', 'unstoppable-lms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-settings&tab=courses' ) ); ?>" class="nav-tab <?php echo 'courses' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Courses', 'unstoppable-lms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-settings&tab=lessons' ) ); ?>" class="nav-tab <?php echo 'lessons' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Lessons', 'unstoppable-lms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-settings&tab=emails' ) ); ?>" class="nav-tab <?php echo 'emails' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Emails', 'unstoppable-lms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-settings&tab=pages' ) ); ?>" class="nav-tab <?php echo 'pages' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Pages', 'unstoppable-lms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-settings&tab=license' ) ); ?>" class="nav-tab <?php echo 'license' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'License', 'unstoppable-lms' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=unstoppable-lms-settings&tab=migration' ) ); ?>" class="nav-tab <?php echo 'migration' === $current_tab ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Migration', 'unstoppable-lms' ); ?>
                </a>
            </nav>

            <form method="post" action="">
                <?php wp_nonce_field( 'unstoppable_lms_settings' ); ?>

                <?php if ( 'general' === $current_tab ) : ?>
                <div class="lms-card" style="margin-top:20px;">
                    <h2><?php esc_html_e( 'General Settings', 'unstoppable-lms' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="currency"><?php esc_html_e( 'Currency', 'unstoppable-lms' ); ?></label></th>
                            <td>
                                <select name="currency" id="currency">
                                    <option value="USD" <?php selected( $general['currency'], 'USD' ); ?>>USD ($)</option>
                                    <option value="EUR" <?php selected( $general['currency'], 'EUR' ); ?>>EUR (€)</option>
                                    <option value="GBP" <?php selected( $general['currency'], 'GBP' ); ?>>GBP (£)</option>
                                    <option value="CAD" <?php selected( $general['currency'], 'CAD' ); ?>>CAD ($)</option>
                                    <option value="AUD" <?php selected( $general['currency'], 'AUD' ); ?>>AUD ($)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="currency_position"><?php esc_html_e( 'Currency Position', 'unstoppable-lms' ); ?></label></th>
                            <td>
                                <select name="currency_position" id="currency_position">
                                    <option value="before" <?php selected( $general['currency_position'], 'before' ); ?>><?php esc_html_e( 'Before ($99)', 'unstoppable-lms' ); ?></option>
                                    <option value="after" <?php selected( $general['currency_position'], 'after' ); ?>><?php esc_html_e( 'After (99$)', 'unstoppable-lms' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="date_format"><?php esc_html_e( 'Date Format', 'unstoppable-lms' ); ?></label></th>
                            <td>
                                <input type="text" name="date_format" id="date_format" value="<?php echo esc_attr( $general['date_format'] ); ?>" class="regular-text">
                                <p class="description"><?php esc_html_e( 'PHP date format. Default: F j, Y', 'unstoppable-lms' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php endif; ?>

                <?php if ( 'courses' === $current_tab ) : ?>
                <div class="lms-card" style="margin-top:20px;">
                    <h2><?php esc_html_e( 'Course Settings', 'unstoppable-lms' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="default_access"><?php esc_html_e( 'Default Access Type', 'unstoppable-lms' ); ?></label></th>
                            <td>
                                <select name="default_access" id="default_access">
                                    <option value="open" <?php selected( $courses['default_access'], 'open' ); ?>><?php esc_html_e( 'Open - Anyone can enroll', 'unstoppable-lms' ); ?></option>
                                    <option value="closed" <?php selected( $courses['default_access'], 'closed' ); ?>><?php esc_html_e( 'Closed - Admin enrollment only', 'unstoppable-lms' ); ?></option>
                                    <option value="paid" <?php selected( $courses['default_access'], 'paid' ); ?>><?php esc_html_e( 'Paid - Requires purchase', 'unstoppable-lms' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Course Features', 'unstoppable-lms' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_reviews" value="1" <?php checked( $courses['enable_reviews'] ); ?>>
                                    <?php esc_html_e( 'Enable course reviews', 'unstoppable-lms' ); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="enable_certificates" value="1" <?php checked( $courses['enable_certificates'] ); ?>>
                                    <?php esc_html_e( 'Enable certificates', 'unstoppable-lms' ); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="sequential_default" value="1" <?php checked( $courses['sequential_default'] ); ?>>
                                    <?php esc_html_e( 'Sequential progress by default', 'unstoppable-lms' ); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php endif; ?>

                <?php if ( 'lessons' === $current_tab ) : ?>
                <div class="lms-card" style="margin-top:20px;">
                    <h2><?php esc_html_e( 'Lesson Settings', 'unstoppable-lms' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Lesson Features', 'unstoppable-lms' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_comments" value="1" <?php checked( $lessons['enable_comments'] ); ?>>
                                    <?php esc_html_e( 'Enable lesson comments/discussion', 'unstoppable-lms' ); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="video_autoplay" value="1" <?php checked( $lessons['video_autoplay'] ); ?>>
                                    <?php esc_html_e( 'Autoplay videos', 'unstoppable-lms' ); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="mark_complete_button" value="1" <?php checked( $lessons['mark_complete_button'] ); ?>>
                                    <?php esc_html_e( 'Show "Mark Complete" button', 'unstoppable-lms' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="completion_text"><?php esc_html_e( 'Completion Button Text', 'unstoppable-lms' ); ?></label></th>
                            <td>
                                <input type="text" name="completion_text" id="completion_text" value="<?php echo esc_attr( $lessons['completion_text'] ); ?>" class="regular-text">
                            </td>
                        </tr>
                    </table>
                </div>
                <?php endif; ?>

                <?php if ( 'emails' === $current_tab ) : ?>
                <div class="lms-card" style="margin-top:20px;">
                    <h2><?php esc_html_e( 'Email Settings', 'unstoppable-lms' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="from_name"><?php esc_html_e( 'From Name', 'unstoppable-lms' ); ?></label></th>
                            <td>
                                <input type="text" name="from_name" id="from_name" value="<?php echo esc_attr( $emails['from_name'] ); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="from_email"><?php esc_html_e( 'From Email', 'unstoppable-lms' ); ?></label></th>
                            <td>
                                <input type="email" name="from_email" id="from_email" value="<?php echo esc_attr( $emails['from_email'] ); ?>" class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Email Notifications', 'unstoppable-lms' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enrollment_email" value="1" <?php checked( $emails['enrollment_email'] ); ?>>
                                    <?php esc_html_e( 'Send email on enrollment', 'unstoppable-lms' ); ?>
                                </label><br>
                                <label>
                                    <input type="checkbox" name="completion_email" value="1" <?php checked( $emails['completion_email'] ); ?>>
                                    <?php esc_html_e( 'Send email on course completion', 'unstoppable-lms' ); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php endif; ?>

                <?php if ( 'pages' === $current_tab ) : ?>
                <div class="lms-card" style="margin-top:20px;">
                    <h2><?php esc_html_e( 'Page Settings', 'unstoppable-lms' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Select pages for LMS functionality. Create pages first, then select them here.', 'unstoppable-lms' ); ?></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="courses_page"><?php esc_html_e( 'Courses Page', 'unstoppable-lms' ); ?></label></th>
                            <td>
                                <?php
                                wp_dropdown_pages( array(
                                    'name'              => 'courses_page',
                                    'id'                => 'courses_page',
                                    'selected'          => $pages['courses_page'],
                                    'show_option_none'  => __( '— Select —', 'unstoppable-lms' ),
                                    'option_none_value' => 0,
                                ) );
                                ?>
                                <p class="description"><?php esc_html_e( 'Page to display the course catalog.', 'unstoppable-lms' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="account_page"><?php esc_html_e( 'Student Account Page', 'unstoppable-lms' ); ?></label></th>
                            <td>
                                <?php
                                wp_dropdown_pages( array(
                                    'name'              => 'account_page',
                                    'id'                => 'account_page',
                                    'selected'          => $pages['account_page'],
                                    'show_option_none'  => __( '— Select —', 'unstoppable-lms' ),
                                    'option_none_value' => 0,
                                ) );
                                ?>
                                <p class="description"><?php esc_html_e( 'Page for students to view enrolled courses and progress.', 'unstoppable-lms' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php endif; ?>

                <?php if ( 'license' === $current_tab ) : ?>
                <div class="lms-card" style="margin-top:20px;">
                    <h2><?php esc_html_e( 'License Activation', 'unstoppable-lms' ); ?></h2>
                    <?php
                    $license = Unstoppable_LMS_License::get_instance();
                    $license_key = $license->get_license_key();
                    $license_data = $license->get_license_data();
                    $is_active = $license->is_active();

                    // Handle license actions.
                    if ( isset( $_POST['unstoppable_lms_activate_license'] ) ) {
                        if ( check_admin_referer( 'unstoppable_lms_license' ) ) {
                            $new_key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
                            if ( ! empty( $new_key ) ) {
                                $result = $license->activate( $new_key );
                                if ( is_wp_error( $result ) ) {
                                    echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
                                } else {
                                    echo '<div class="notice notice-success"><p>' . esc_html__( 'License activated successfully!', 'unstoppable-lms' ) . '</p></div>';
                                    $is_active = true;
                                    $license_key = $new_key;
                                }
                            }
                        }
                    }

                    if ( isset( $_POST['unstoppable_lms_deactivate_license'] ) ) {
                        if ( check_admin_referer( 'unstoppable_lms_license' ) ) {
                            $result = $license->deactivate();
                            if ( is_wp_error( $result ) ) {
                                echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
                            } else {
                                echo '<div class="notice notice-success"><p>' . esc_html__( 'License deactivated successfully.', 'unstoppable-lms' ) . '</p></div>';
                                $is_active = false;
                                $license_key = '';
                            }
                        }
                    }
                    ?>

                    <form method="post">
                        <?php wp_nonce_field( 'unstoppable_lms_license' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="license_key"><?php esc_html_e( 'License Key', 'unstoppable-lms' ); ?></label></th>
                                <td>
                                    <?php if ( $is_active ) : ?>
                                        <input type="text" value="<?php echo esc_attr( $license->get_masked_key() ); ?>" class="regular-text" disabled>
                                        <span style="color: #46b450; font-weight: bold; margin-left: 10px;">✓ <?php esc_html_e( 'Active', 'unstoppable-lms' ); ?></span>
                                    <?php else : ?>
                                        <input type="text" name="license_key" id="license_key" value="" class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX">
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ( $is_active && ! empty( $license_data ) ) : ?>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'License Type', 'unstoppable-lms' ); ?></th>
                                <td><?php echo esc_html( ucfirst( $license_data['license_type'] ?? 'Standard' ) ); ?></td>
                            </tr>
                            <?php if ( ! empty( $license_data['expires'] ) ) : ?>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Expires', 'unstoppable-lms' ); ?></th>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $license_data['expires'] ) ) ); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ( isset( $license_data['sites_allowed'] ) ) : ?>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Sites', 'unstoppable-lms' ); ?></th>
                                <td>
                                    <?php
                                    printf(
                                        /* translators: %1$d: active sites, %2$d: allowed sites */
                                        esc_html__( '%1$d of %2$d sites activated', 'unstoppable-lms' ),
                                        intval( $license_data['sites_active'] ?? 1 ),
                                        intval( $license_data['sites_allowed'] )
                                    );
                                    ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endif; ?>
                        </table>

                        <p class="submit">
                            <?php if ( $is_active ) : ?>
                                <input type="submit" name="unstoppable_lms_deactivate_license" class="button" value="<?php esc_attr_e( 'Deactivate License', 'unstoppable-lms' ); ?>">
                            <?php else : ?>
                                <input type="submit" name="unstoppable_lms_activate_license" class="button button-primary" value="<?php esc_attr_e( 'Activate License', 'unstoppable-lms' ); ?>">
                            <?php endif; ?>
                        </p>
                    </form>

                    <hr>
                    <p>
                        <?php esc_html_e( "Don't have a license key?", 'unstoppable-lms' ); ?>
                        <a href="https://unstoppablesolutions.app/Dashboard" target="_blank"><?php esc_html_e( 'Get your license key', 'unstoppable-lms' ); ?></a>
                    </p>
                </div>
                <?php endif; ?>

                <?php if ( 'migration' === $current_tab ) : ?>
                <div class="lms-card" style="margin-top:20px;">
                    <h2><?php esc_html_e( 'Migration Tools', 'unstoppable-lms' ); ?></h2>
                    <?php
                    if ( class_exists( 'Unstoppable_LMS_Migration' ) ) {
                        Unstoppable_LMS_Migration::render_migration_page();
                    } else {
                        echo '<p>' . esc_html__( 'Migration tools not available.', 'unstoppable-lms' ) . '</p>';
                    }
                    ?>
                </div>
                <?php else : ?>
                <p class="submit">
                    <input type="submit" name="unstoppable_lms_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'unstoppable-lms' ); ?>">
                </p>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render Pro feature page - Gradebook.
     */
    public static function render_pro_feature_gradebook() {
        self::render_pro_feature_page(
            __( 'Advanced Gradebook', 'unstoppable-lms' ),
            __( 'The gradebook schools actually need.', 'unstoppable-lms' ),
            array(
                __( 'Triple grade display: Points (13/15), Percentage (86.7%), Letter Grade (B+)', 'unstoppable-lms' ),
                __( 'GPA calculation on 4.0 scale', 'unstoppable-lms' ),
                __( 'Letter grades with +/- modifiers (A+, A, A-, B+, etc.)', 'unstoppable-lms' ),
                __( 'Color-coded grade matrix — see entire class at a glance', 'unstoppable-lms' ),
                __( 'Manual grade entry for exams, projects, participation', 'unstoppable-lms' ),
                __( 'CSV export for report cards and records', 'unstoppable-lms' ),
                __( 'Weighted averages across all assignments', 'unstoppable-lms' ),
            ),
            'dashicons-awards'
        );
    }

    /**
     * Render Pro feature page - Cohorts.
     */
    public static function render_pro_feature_cohorts() {
        self::render_pro_feature_page(
            __( 'Cohorts / Class Instances', 'unstoppable-lms' ),
            __( 'The killer feature for real schools — reuse courses for multiple terms.', 'unstoppable-lms' ),
            array(
                __( 'Create "Fall 2025", "Spring 2026" — same curriculum, fresh students', 'unstoppable-lms' ),
                __( 'Each cohort has separate enrollments, progress, grades, and discussions', 'unstoppable-lms' ),
                __( 'Set start/end dates for each term', 'unstoppable-lms' ),
                __( 'Assign different instructors per cohort', 'unstoppable-lms' ),
                __( 'Max student limits per section', 'unstoppable-lms' ),
                __( 'Duplicate cohorts for new school year with one click', 'unstoppable-lms' ),
            ),
            'dashicons-groups'
        );
    }

    /**
     * Render Pro feature page - Discussions.
     */
    public static function render_pro_feature_discussions() {
        self::render_pro_feature_page(
            __( 'Discussion Forums', 'unstoppable-lms' ),
            __( 'Per-lesson discussion boards for student engagement.', 'unstoppable-lms' ),
            array(
                __( 'Students create topics and reply to each other', 'unstoppable-lms' ),
                __( 'Threaded/nested replies for organized conversations', 'unstoppable-lms' ),
                __( 'Instructors can pin important topics', 'unstoppable-lms' ),
                __( 'Close discussions when needed', 'unstoppable-lms' ),
                __( 'Enrollment-based access — only enrolled students can participate', 'unstoppable-lms' ),
                __( 'Per-cohort discussions (with Cohorts feature)', 'unstoppable-lms' ),
            ),
            'dashicons-format-chat'
        );
    }

    /**
     * Render a Pro feature placeholder page.
     *
     * @param string $title       Feature title.
     * @param string $description Feature description.
     * @param array  $features    List of feature benefits.
     * @param string $icon        Dashicon class.
     */
    private static function render_pro_feature_page( $title, $description, $features, $icon ) {
        ?>
        <div class="wrap">
            <div style="max-width: 800px; margin: 40px auto; text-align: center;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 60px 40px; border-radius: 12px; margin-bottom: 30px;">
                    <span class="dashicons <?php echo esc_attr( $icon ); ?>" style="font-size: 60px; width: 60px; height: 60px; margin-bottom: 20px;"></span>
                    <h1 style="color: #fff; font-size: 32px; margin: 0 0 10px;"><?php echo esc_html( $title ); ?></h1>
                    <p style="font-size: 18px; opacity: 0.9; margin: 0;"><?php echo esc_html( $description ); ?></p>
                </div>

                <div style="background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: left;">
                    <h2 style="margin-top: 0;"><?php esc_html_e( 'What you get with Pro:', 'unstoppable-lms' ); ?></h2>
                    <ul style="font-size: 16px; line-height: 2;">
                        <?php foreach ( $features as $feature ) : ?>
                            <li><span style="color: #46b450; margin-right: 8px;">✓</span> <?php echo esc_html( $feature ); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <div style="text-align: center; margin-top: 30px; padding-top: 30px; border-top: 1px solid #eee;">
                        <p style="font-size: 24px; font-weight: bold; color: #333; margin: 0 0 5px;">
                            <?php esc_html_e( 'Just $29.99/year', 'unstoppable-lms' ); ?>
                        </p>
                        <p style="color: #666; margin: 0 0 20px;">
                            <?php esc_html_e( 'That\'s less than $2.50/month for unlimited courses, gradebook, discussions, cohorts, and more.', 'unstoppable-lms' ); ?>
                        </p>
                        <a href="https://unstoppablesolutions.app/PluginDetail?slug=unstoppable-lms" target="_blank" class="button button-primary button-hero" style="font-size: 18px; padding: 12px 40px;">
                            <?php esc_html_e( 'Upgrade to Pro', 'unstoppable-lms' ); ?>
                        </a>
                        <p style="margin-top: 15px; color: #999; font-size: 13px;">
                            <?php esc_html_e( '30-day money-back guarantee', 'unstoppable-lms' ); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the upgrade to Pro page.
     */
    public static function render_upgrade_page() {
        ?>
        <div class="wrap">
            <div style="max-width: 900px; margin: 20px auto;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 50px 40px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
                    <h1 style="color: #fff; font-size: 36px; margin: 0 0 10px;">
                        <?php esc_html_e( 'Upgrade to Unstoppable LMS Pro', 'unstoppable-lms' ); ?>
                    </h1>
                    <p style="font-size: 20px; opacity: 0.9; margin: 0 0 20px;">
                        <?php esc_html_e( 'Unlock the full power of your LMS for just $29.99/year', 'unstoppable-lms' ); ?>
                    </p>
                    <p style="font-size: 16px; opacity: 0.8; margin: 0;">
                        <?php esc_html_e( 'Compare to LearnDash at $199/year — save over 85%!', 'unstoppable-lms' ); ?>
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <!-- Gradebook Card -->
                    <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <span class="dashicons dashicons-awards" style="font-size: 40px; width: 40px; height: 40px; color: #667eea; margin-bottom: 15px;"></span>
                        <h3 style="margin: 0 0 10px;"><?php esc_html_e( 'Advanced Gradebook', 'unstoppable-lms' ); ?></h3>
                        <p style="color: #666; margin: 0;">
                            <?php esc_html_e( 'Points, percentages, letter grades (A+, B-, etc.), GPA calculation, CSV export, and more.', 'unstoppable-lms' ); ?>
                        </p>
                    </div>

                    <!-- Cohorts Card -->
                    <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <span class="dashicons dashicons-groups" style="font-size: 40px; width: 40px; height: 40px; color: #667eea; margin-bottom: 15px;"></span>
                        <h3 style="margin: 0 0 10px;"><?php esc_html_e( 'Cohorts / Class Terms', 'unstoppable-lms' ); ?></h3>
                        <p style="color: #666; margin: 0;">
                            <?php esc_html_e( 'Reuse courses for multiple semesters with separate enrollments, grades, and discussions per term.', 'unstoppable-lms' ); ?>
                        </p>
                    </div>

                    <!-- Discussions Card -->
                    <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <span class="dashicons dashicons-format-chat" style="font-size: 40px; width: 40px; height: 40px; color: #667eea; margin-bottom: 15px;"></span>
                        <h3 style="margin: 0 0 10px;"><?php esc_html_e( 'Discussion Forums', 'unstoppable-lms' ); ?></h3>
                        <p style="color: #666; margin: 0;">
                            <?php esc_html_e( 'Per-lesson discussion boards where students can create topics and reply to each other.', 'unstoppable-lms' ); ?>
                        </p>
                    </div>

                    <!-- Migration Card -->
                    <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <span class="dashicons dashicons-migrate" style="font-size: 40px; width: 40px; height: 40px; color: #667eea; margin-bottom: 15px;"></span>
                        <h3 style="margin: 0 0 10px;"><?php esc_html_e( 'LearnDash Migration', 'unstoppable-lms' ); ?></h3>
                        <p style="color: #666; margin: 0;">
                            <?php esc_html_e( 'One-click migration of courses, lessons, students, and progress from LearnDash.', 'unstoppable-lms' ); ?>
                        </p>
                    </div>

                    <!-- Unlimited Card -->
                    <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <span class="dashicons dashicons-infinity" style="font-size: 40px; width: 40px; height: 40px; color: #667eea; margin-bottom: 15px;"></span>
                        <h3 style="margin: 0 0 10px;"><?php esc_html_e( 'Unlimited Everything', 'unstoppable-lms' ); ?></h3>
                        <p style="color: #666; margin: 0;">
                            <?php esc_html_e( 'Unlimited courses, unlimited lessons, unlimited students. No artificial limits.', 'unstoppable-lms' ); ?>
                        </p>
                    </div>

                    <!-- Support Card -->
                    <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <span class="dashicons dashicons-sos" style="font-size: 40px; width: 40px; height: 40px; color: #667eea; margin-bottom: 15px;"></span>
                        <h3 style="margin: 0 0 10px;"><?php esc_html_e( 'Priority Support', 'unstoppable-lms' ); ?></h3>
                        <p style="color: #666; margin: 0;">
                            <?php esc_html_e( 'Email support within 24 hours. Direct access to our development team.', 'unstoppable-lms' ); ?>
                        </p>
                    </div>
                </div>

                <!-- CTA Section -->
                <div style="background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center;">
                    <h2 style="margin: 0 0 10px; font-size: 28px;"><?php esc_html_e( 'Ready to unlock the full LMS?', 'unstoppable-lms' ); ?></h2>
                    <p style="color: #666; font-size: 16px; margin: 0 0 25px;">
                        <?php esc_html_e( 'Join hundreds of schools and course creators who chose Unstoppable LMS.', 'unstoppable-lms' ); ?>
                    </p>
                    
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; display: inline-block; margin-bottom: 25px;">
                        <span style="font-size: 48px; font-weight: bold; color: #333;">$29.99</span>
                        <span style="color: #666; font-size: 18px;">/year</span>
                        <p style="margin: 10px 0 0; color: #999; font-size: 14px;">
                            <del>$199/year (LearnDash)</del> — <?php esc_html_e( 'Save 85%', 'unstoppable-lms' ); ?>
                        </p>
                    </div>

                    <div>
                        <a href="https://unstoppablesolutions.app/PluginDetail?slug=unstoppable-lms" target="_blank" class="button button-primary button-hero" style="font-size: 20px; padding: 15px 50px; background: #667eea; border-color: #667eea;">
                            <?php esc_html_e( 'Get Pro Now', 'unstoppable-lms' ); ?>
                        </a>
                    </div>
                    
                    <p style="margin-top: 20px; color: #999;">
                        <span class="dashicons dashicons-yes" style="color: #46b450;"></span> <?php esc_html_e( '30-day money-back guarantee', 'unstoppable-lms' ); ?>
                        &nbsp;&nbsp;
                        <span class="dashicons dashicons-yes" style="color: #46b450;"></span> <?php esc_html_e( 'Instant activation', 'unstoppable-lms' ); ?>
                        &nbsp;&nbsp;
                        <span class="dashicons dashicons-yes" style="color: #46b450;"></span> <?php esc_html_e( 'Cancel anytime', 'unstoppable-lms' ); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}
