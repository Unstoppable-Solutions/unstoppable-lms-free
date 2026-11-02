<?php
/**
 * Plugin installation and database setup.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Installation class.
 *
 * Handles plugin activation, deactivation, and database table creation.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Install {

    /**
     * Database version.
     *
     * @var string
     */
    const DB_VERSION = '1.0.0';

    /**
     * Activate the plugin.
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Buffer output to prevent "unexpected output" errors from deprecation warnings.
        ob_start();
        
        self::create_tables();
        self::create_roles();
        self::set_default_options();

        // Flush rewrite rules after registering post types.
        Unstoppable_LMS_Post_Types::register();
        flush_rewrite_rules();

        // Store version for upgrade checks.
        update_option( 'unstoppable_lms_version', UNSTOPPABLE_LMS_VERSION );
        update_option( 'unstoppable_lms_db_version', self::DB_VERSION );

        // Store install date for review notice timing.
        if ( ! get_option( 'unstoppable_lms_install_date' ) ) {
            update_option( 'unstoppable_lms_install_date', time() );
        }

        /**
         * Fires after Unstoppable LMS is activated.
         *
         * @since 1.0.0
         */
        do_action( 'unstoppable_lms_activated' );
        
        // Clear buffer - discard any output/warnings.
        ob_end_clean();
    }

    /**
     * Deactivate the plugin.
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        flush_rewrite_rules();

        /**
         * Fires after Unstoppable LMS is deactivated.
         *
         * @since 1.0.0
         */
        do_action( 'unstoppable_lms_deactivated' );
    }

    /**
     * Create database tables.
     *
     * @since 1.0.0
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Enrollments table.
        $enrollments_table = $wpdb->prefix . 'lms_enrollments';
        $enrollments_sql   = "CREATE TABLE {$enrollments_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            enrolled_at DATETIME NOT NULL,
            expires_at DATETIME NULL,
            status ENUM('active', 'expired', 'suspended', 'completed') DEFAULT 'active',
            enrolled_by BIGINT UNSIGNED NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_status (user_id, status),
            INDEX idx_course_status (course_id, status),
            INDEX idx_expires (expires_at),
            UNIQUE KEY unique_enrollment (user_id, course_id)
        ) {$charset_collate};";

        // Progress table.
        $progress_table = $wpdb->prefix . 'lms_progress';
        $progress_sql   = "CREATE TABLE {$progress_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            item_type ENUM('lesson', 'topic', 'quiz', 'assignment') NOT NULL,
            status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            time_spent INT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_course (user_id, course_id),
            INDEX idx_item (item_id, item_type),
            INDEX idx_status (status),
            UNIQUE KEY unique_progress (user_id, item_id, item_type)
        ) {$charset_collate};";

        // Progress events table (for audit trail / event sourcing).
        $events_table = $wpdb->prefix . 'lms_progress_events';
        $events_sql   = "CREATE TABLE {$events_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            item_id BIGINT UNSIGNED NOT NULL,
            item_type VARCHAR(50) NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            meta LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_course (user_id, course_id),
            INDEX idx_item (item_id, item_type),
            INDEX idx_event_type (event_type),
            INDEX idx_created (created_at)
        ) {$charset_collate};";

        // Quiz attempts table.
        $quiz_attempts_table = $wpdb->prefix . 'lms_quiz_attempts';
        $quiz_attempts_sql   = "CREATE TABLE {$quiz_attempts_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            quiz_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            started_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            score DECIMAL(5,2) NULL,
            points_earned INT UNSIGNED DEFAULT 0,
            points_possible INT UNSIGNED NOT NULL,
            passed TINYINT(1) NULL,
            time_spent INT UNSIGNED DEFAULT 0,
            answers_json LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_quiz (user_id, quiz_id),
            INDEX idx_course (course_id),
            INDEX idx_passed (passed),
            INDEX idx_completed (completed_at)
        ) {$charset_collate};";

        // Grades table (gradebook integration).
        $grades_table = $wpdb->prefix . 'lms_grades';
        $grades_sql   = "CREATE TABLE {$grades_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            gradable_id BIGINT UNSIGNED NOT NULL,
            gradable_type VARCHAR(50) NOT NULL,
            grade DECIMAL(5,2) NULL,
            points DECIMAL(10,2) NULL,
            max_points DECIMAL(10,2) NULL,
            weight DECIMAL(5,4) DEFAULT 1.0000,
            graded_by BIGINT UNSIGNED NULL,
            graded_at DATETIME NULL,
            notes TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_course (user_id, course_id),
            INDEX idx_gradable (gradable_id, gradable_type),
            UNIQUE KEY unique_grade (user_id, gradable_id, gradable_type)
        ) {$charset_collate};";

        // User achievements table (gamification).
        $achievements_table = $wpdb->prefix . 'lms_user_achievements';
        $achievements_sql   = "CREATE TABLE {$achievements_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            achievement_type VARCHAR(50) NOT NULL,
            achievement_id BIGINT UNSIGNED NULL,
            course_id BIGINT UNSIGNED NULL,
            points INT UNSIGNED DEFAULT 0,
            earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            meta LONGTEXT NULL,
            INDEX idx_user (user_id),
            INDEX idx_type (achievement_type),
            INDEX idx_course (course_id)
        ) {$charset_collate};";

        // Course structure cache table (for performance).
        $structure_table = $wpdb->prefix . 'lms_course_structure';
        $structure_sql   = "CREATE TABLE {$structure_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            course_id BIGINT UNSIGNED NOT NULL,
            structure_json LONGTEXT NOT NULL,
            hash VARCHAR(32) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_course (course_id),
            INDEX idx_hash (hash)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $enrollments_sql );
        dbDelta( $progress_sql );
        dbDelta( $events_sql );
        dbDelta( $quiz_attempts_sql );
        dbDelta( $grades_sql );
        dbDelta( $achievements_sql );
        dbDelta( $structure_sql );
    }

    /**
     * Create custom user roles.
     *
     * @since 1.0.0
     */
    public static function create_roles() {
        // LMS Student role.
        add_role(
            'lms_student',
            __( 'LMS Student', 'unstoppable-lms' ),
            array(
                'read' => true,
            )
        );

        // LMS Instructor role.
        add_role(
            'lms_instructor',
            __( 'LMS Instructor', 'unstoppable-lms' ),
            array(
                'read'                   => true,
                'edit_lms_courses'       => true,
                'edit_published_lms_courses' => true,
                'publish_lms_courses'    => true,
                'delete_lms_courses'     => true,
                'edit_lms_lessons'       => true,
                'edit_published_lms_lessons' => true,
                'publish_lms_lessons'    => true,
                'delete_lms_lessons'     => true,
                'edit_lms_quizzes'       => true,
                'edit_published_lms_quizzes' => true,
                'publish_lms_quizzes'    => true,
                'delete_lms_quizzes'     => true,
                'manage_lms_students'    => true,
                'view_lms_reports'       => true,
            )
        );

        // Add LMS capabilities to administrator.
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $admin_role->add_cap( 'manage_lms' );
            $admin_role->add_cap( 'edit_lms_courses' );
            $admin_role->add_cap( 'edit_others_lms_courses' );
            $admin_role->add_cap( 'edit_published_lms_courses' );
            $admin_role->add_cap( 'publish_lms_courses' );
            $admin_role->add_cap( 'delete_lms_courses' );
            $admin_role->add_cap( 'delete_others_lms_courses' );
            $admin_role->add_cap( 'edit_lms_lessons' );
            $admin_role->add_cap( 'edit_others_lms_lessons' );
            $admin_role->add_cap( 'edit_published_lms_lessons' );
            $admin_role->add_cap( 'publish_lms_lessons' );
            $admin_role->add_cap( 'delete_lms_lessons' );
            $admin_role->add_cap( 'delete_others_lms_lessons' );
            $admin_role->add_cap( 'edit_lms_quizzes' );
            $admin_role->add_cap( 'edit_others_lms_quizzes' );
            $admin_role->add_cap( 'edit_published_lms_quizzes' );
            $admin_role->add_cap( 'publish_lms_quizzes' );
            $admin_role->add_cap( 'delete_lms_quizzes' );
            $admin_role->add_cap( 'delete_others_lms_quizzes' );
            $admin_role->add_cap( 'manage_lms_students' );
            $admin_role->add_cap( 'view_lms_reports' );
            $admin_role->add_cap( 'manage_lms_settings' );
        }
    }

    /**
     * Set default plugin options.
     *
     * @since 1.0.0
     */
    public static function set_default_options() {
        $defaults = array(
            'course'       => array(
                'progression'       => 'linear',
                'completion_method' => 'all_items',
                'materials_access'  => 'enrolled',
                'certificate_enabled' => false,
            ),
            'lesson'       => array(
                'completion_method'     => 'manual',
                'video_autoplay'        => false,
                'video_progress_marker' => true,
            ),
            'quiz'         => array(
                'passing_score'        => 80,
                'attempts_allowed'     => 0,
                'show_correct_answers' => 'after_pass',
                'randomize_questions'  => true,
                'randomize_answers'    => true,
                'time_limit'           => 0,
            ),
            'display'      => array(
                'mode'                  => 'focus',
                'show_progress_bar'     => true,
                'show_time_estimates'   => true,
                'sidebar_default_state' => 'collapsed',
            ),
            'gamification' => array(
                'enabled'            => false,
                'points_per_lesson'  => 10,
                'points_per_quiz'    => 25,
                'show_leaderboard'   => false,
            ),
        );

        if ( false === get_option( 'unstoppable_lms_settings' ) ) {
            add_option( 'unstoppable_lms_settings', $defaults );
        }
    }

    /**
     * Check if database needs upgrade.
     *
     * @since 1.0.0
     * @return bool
     */
    public static function needs_upgrade() {
        $installed_version = get_option( 'unstoppable_lms_db_version', '0' );
        return version_compare( $installed_version, self::DB_VERSION, '<' );
    }

    /**
     * Run database upgrades.
     *
     * @since 1.0.0
     */
    public static function maybe_upgrade() {
        if ( self::needs_upgrade() ) {
            self::create_tables();
            update_option( 'unstoppable_lms_db_version', self::DB_VERSION );
        }
    }
}
