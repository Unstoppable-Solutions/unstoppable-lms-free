<?php
/**
 * Cache management for the LMS.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Cache class.
 *
 * Handles caching for course structures, user progress, and other frequently accessed data.
 * Designed to work WITH page caching plugins, not against them.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Cache {

    /**
     * Cache group name.
     *
     * @var string
     */
    const GROUP = 'unstoppable_lms';

    /**
     * Cache version for invalidation.
     *
     * @var int
     */
    const VERSION = 1;

    /**
     * Get course structure from cache or build it.
     *
     * @since 1.0.0
     * @param int $course_id Course ID.
     * @return array|null Course structure or null if not found.
     */
    public static function get_course_structure( int $course_id ): ?array {
        $key    = self::key( 'course_structure', $course_id );
        $cached = wp_cache_get( $key, self::GROUP );

        if ( false !== $cached ) {
            return $cached;
        }

        // Build structure from database.
        $structure = self::build_course_structure( $course_id );

        if ( $structure ) {
            // Cache for 1 hour - course structure rarely changes.
            wp_cache_set( $key, $structure, self::GROUP, HOUR_IN_SECONDS );
        }

        return $structure;
    }

    /**
     * Get user progress from cache or database.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     * @return array Progress data indexed by item key.
     */
    public static function get_user_progress( int $user_id, int $course_id ): array {
        $key    = self::key( 'user_progress', $user_id, $course_id );
        $cached = wp_cache_get( $key, self::GROUP );

        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'lms_progress';

        $progress = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT item_id, item_type, status, completed_at, time_spent
                 FROM {$table}
                 WHERE user_id = %d AND course_id = %d",
                $user_id,
                $course_id
            ),
            ARRAY_A
        );

        // Index by item for quick lookups.
        $indexed = array();
        foreach ( $progress as $item ) {
            $indexed[ $item['item_type'] . '_' . $item['item_id'] ] = $item;
        }

        // Short cache - progress changes often.
        wp_cache_set( $key, $indexed, self::GROUP, 5 * MINUTE_IN_SECONDS );

        return $indexed;
    }

    /**
     * Get user enrollment status.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     * @return array|null Enrollment data or null if not enrolled.
     */
    public static function get_enrollment( int $user_id, int $course_id ): ?array {
        $key    = self::key( 'enrollment', $user_id, $course_id );
        $cached = wp_cache_get( $key, self::GROUP );

        if ( false !== $cached ) {
            return $cached ?: null;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'lms_enrollments';

        $enrollment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE user_id = %d AND course_id = %d AND status = 'active'",
                $user_id,
                $course_id
            ),
            ARRAY_A
        );

        // Cache even null results to avoid repeated DB queries.
        wp_cache_set( $key, $enrollment ?: array(), self::GROUP, 10 * MINUTE_IN_SECONDS );

        return $enrollment;
    }

    /**
     * Get quiz config from cache.
     *
     * @since 1.0.0
     * @param int $quiz_id Quiz ID.
     * @return array|null Quiz configuration or null.
     */
    public static function get_quiz_config( int $quiz_id ): ?array {
        $key    = self::key( 'quiz_config', $quiz_id );
        $cached = wp_cache_get( $key, self::GROUP );

        if ( false !== $cached ) {
            return $cached;
        }

        $quiz = get_post( $quiz_id );
        if ( ! $quiz || 'lms_quiz' !== $quiz->post_type ) {
            return null;
        }

        $config = array(
            'id'                  => $quiz_id,
            'title'               => $quiz->post_title,
            'passing_score'       => (int) get_post_meta( $quiz_id, '_lms_passing_score', true ) ?: 80,
            'time_limit'          => (int) get_post_meta( $quiz_id, '_lms_time_limit', true ) ?: 0,
            'attempts_allowed'    => (int) get_post_meta( $quiz_id, '_lms_attempts_allowed', true ) ?: 0,
            'randomize_questions' => (bool) get_post_meta( $quiz_id, '_lms_randomize_questions', true ),
            'randomize_answers'   => (bool) get_post_meta( $quiz_id, '_lms_randomize_answers', true ),
            'show_correct_answers' => get_post_meta( $quiz_id, '_lms_show_correct_answers', true ) ?: 'after_pass',
        );

        wp_cache_set( $key, $config, self::GROUP, HOUR_IN_SECONDS );

        return $config;
    }

    /**
     * Invalidate user progress cache.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     */
    public static function invalidate_user_progress( int $user_id, int $course_id ): void {
        $key = self::key( 'user_progress', $user_id, $course_id );
        wp_cache_delete( $key, self::GROUP );
    }

    /**
     * Invalidate course structure cache.
     *
     * @since 1.0.0
     * @param int $course_id Course ID.
     */
    public static function invalidate_course_structure( int $course_id ): void {
        $key = self::key( 'course_structure', $course_id );
        wp_cache_delete( $key, self::GROUP );

        // Also invalidate the database cache.
        global $wpdb;
        $table = $wpdb->prefix . 'lms_course_structure';
        $wpdb->delete( $table, array( 'course_id' => $course_id ) );
    }

    /**
     * Invalidate enrollment cache.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     */
    public static function invalidate_enrollment( int $user_id, int $course_id ): void {
        $key = self::key( 'enrollment', $user_id, $course_id );
        wp_cache_delete( $key, self::GROUP );
    }

    /**
     * Invalidate quiz config cache.
     *
     * @since 1.0.0
     * @param int $quiz_id Quiz ID.
     */
    public static function invalidate_quiz_config( int $quiz_id ): void {
        $key = self::key( 'quiz_config', $quiz_id );
        wp_cache_delete( $key, self::GROUP );
    }

    /**
     * Build course structure from database.
     *
     * @since 1.0.0
     * @param int $course_id Course ID.
     * @return array|null Course structure or null.
     */
    private static function build_course_structure( int $course_id ): ?array {
        $course = get_post( $course_id );
        if ( ! $course || 'lms_course' !== $course->post_type ) {
            return null;
        }

        // Get sections (stored as post meta with order).
        $sections_data = get_post_meta( $course_id, '_lms_sections', true );
        if ( ! is_array( $sections_data ) ) {
            $sections_data = array();
        }

        $structure = array(
            'id'          => $course_id,
            'title'       => $course->post_title,
            'sections'    => array(),
            'total_items' => 0,
        );

        foreach ( $sections_data as $section_index => $section ) {
            $section_structure = array(
                'id'      => $section['id'] ?? 'section_' . $section_index,
                'title'   => $section['title'] ?? __( 'Untitled Section', 'unstoppable-lms' ),
                'lessons' => array(),
            );

            // Get lessons for this section.
            $lesson_ids = $section['lessons'] ?? array();
            foreach ( $lesson_ids as $lesson_order => $lesson_id ) {
                $lesson = get_post( $lesson_id );
                if ( ! $lesson || 'lms_lesson' !== $lesson->post_type ) {
                    continue;
                }

                $lesson_structure = array(
                    'id'       => $lesson_id,
                    'title'    => $lesson->post_title,
                    'type'     => 'lesson',
                    'duration' => get_post_meta( $lesson_id, '_lms_duration', true ) ?: null,
                    'topics'   => array(),
                    'quiz'     => null,
                );

                $structure['total_items']++;

                // Get topics for this lesson.
                $topic_ids = get_post_meta( $lesson_id, '_lms_topics', true );
                if ( is_array( $topic_ids ) ) {
                    foreach ( $topic_ids as $topic_id ) {
                        $topic = get_post( $topic_id );
                        if ( ! $topic || 'lms_topic' !== $topic->post_type ) {
                            continue;
                        }

                        $lesson_structure['topics'][] = array(
                            'id'       => $topic_id,
                            'title'    => $topic->post_title,
                            'type'     => 'topic',
                            'duration' => get_post_meta( $topic_id, '_lms_duration', true ) ?: null,
                        );

                        $structure['total_items']++;
                    }
                }

                // Get quiz for this lesson.
                $quiz_id = get_post_meta( $lesson_id, '_lms_quiz', true );
                if ( $quiz_id ) {
                    $quiz = get_post( $quiz_id );
                    if ( $quiz && 'lms_quiz' === $quiz->post_type ) {
                        $lesson_structure['quiz'] = array(
                            'id'       => $quiz_id,
                            'title'    => $quiz->post_title,
                            'required' => (bool) get_post_meta( $lesson_id, '_lms_quiz_required', true ),
                        );

                        if ( $lesson_structure['quiz']['required'] ) {
                            $structure['total_items']++;
                        }
                    }
                }

                $section_structure['lessons'][] = $lesson_structure;
            }

            $structure['sections'][] = $section_structure;
        }

        // Store in database for persistent cache.
        self::store_structure_in_db( $course_id, $structure );

        return $structure;
    }

    /**
     * Store course structure in database for persistent cache.
     *
     * @since 1.0.0
     * @param int   $course_id Course ID.
     * @param array $structure Structure data.
     */
    private static function store_structure_in_db( int $course_id, array $structure ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'lms_course_structure';

        $json = wp_json_encode( $structure );
        $hash = md5( $json );

        $wpdb->replace(
            $table,
            array(
                'course_id'      => $course_id,
                'structure_json' => $json,
                'hash'           => $hash,
            ),
            array( '%d', '%s', '%s' )
        );
    }

    /**
     * Generate cache key.
     *
     * @since 1.0.0
     * @param string $type Cache type.
     * @param mixed  ...$ids Additional identifiers.
     * @return string Cache key.
     */
    private static function key( string $type, ...$ids ): string {
        return $type . '_' . implode( '_', $ids ) . '_v' . self::VERSION;
    }

    /**
     * Check if page caching is active.
     *
     * @since 1.0.0
     * @return bool
     */
    public static function is_page_cached(): bool {
        return defined( 'WP_CACHE' ) && WP_CACHE;
    }

    /**
     * Render a no-cache zone with AJAX fallback.
     *
     * For dynamic content that shouldn't be page-cached.
     *
     * @since 1.0.0
     * @param callable $callback Function to render content.
     * @return string HTML output.
     */
    public static function no_cache_zone( callable $callback ): string {
        if ( self::is_page_cached() ) {
            // Return placeholder that JS will populate.
            $placeholder_id = 'lms_dynamic_' . wp_generate_uuid4();

            add_action(
                'wp_footer',
                function () use ( $placeholder_id ) {
                    ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var el = document.getElementById('<?php echo esc_js( $placeholder_id ); ?>');
                            if (el) {
                                fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>?action=lms_fragment&id=<?php echo esc_js( $placeholder_id ); ?>&nonce=<?php echo esc_js( wp_create_nonce( 'lms_fragment' ) ); ?>')
                                    .then(function(r) { return r.text(); })
                                    .then(function(html) { el.innerHTML = html; });
                            }
                        });
                    </script>
                    <?php
                }
            );

            return '<div id="' . esc_attr( $placeholder_id ) . '" class="lms-loading">' . esc_html__( 'Loading...', 'unstoppable-lms' ) . '</div>';
        }

        // No page caching, render directly.
        ob_start();
        $callback();
        return ob_get_clean();
    }

    /**
     * Clear all LMS caches.
     *
     * @since 1.0.0
     */
    public static function flush_all(): void {
        wp_cache_flush_group( self::GROUP );

        // Clear database structure cache.
        global $wpdb;
        $table = $wpdb->prefix . 'lms_course_structure';
        $wpdb->query( "TRUNCATE TABLE {$table}" );
    }
}
