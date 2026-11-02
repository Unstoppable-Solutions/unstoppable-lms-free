<?php
/**
 * Progress tracking system.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Progress Tracker class.
 *
 * Handles reliable progress tracking with event sourcing.
 * Every progress change is logged as an event for audit trail and recovery.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Progress_Tracker {

    /**
     * Mark an item as complete.
     *
     * @since 1.0.0
     * @param int    $user_id   User ID.
     * @param int    $item_id   Item ID.
     * @param string $item_type Item type (lesson, topic, quiz).
     * @param array  $meta      Optional metadata (time_spent, etc.).
     * @return array Result with success status and additional data.
     */
    public static function complete_item( int $user_id, int $item_id, string $item_type, array $meta = array() ): array {
        $course_id = self::get_item_course( $item_id, $item_type );

        if ( ! $course_id ) {
            return array(
                'success' => false,
                'error'   => 'invalid_item',
            );
        }

        // Check enrollment.
        if ( ! self::user_can_access( $user_id, $course_id ) ) {
            return array(
                'success' => false,
                'error'   => 'access_denied',
            );
        }

        // Check prerequisites.
        if ( ! self::prerequisites_met( $user_id, $item_id, $item_type, $course_id ) ) {
            return array(
                'success' => false,
                'error'   => 'prerequisites_not_met',
            );
        }

        global $wpdb;
        $progress_table = $wpdb->prefix . 'lms_progress';

        $wpdb->query( 'START TRANSACTION' );

        try {
            // Log the event.
            self::log_event( array(
                'user_id'    => $user_id,
                'course_id'  => $course_id,
                'item_id'    => $item_id,
                'item_type'  => $item_type,
                'event_type' => 'completed',
                'meta'       => $meta,
            ) );

            // Update or insert progress.
            $wpdb->replace(
                $progress_table,
                array(
                    'user_id'      => $user_id,
                    'course_id'    => $course_id,
                    'item_id'      => $item_id,
                    'item_type'    => $item_type,
                    'status'       => 'completed',
                    'started_at'   => current_time( 'mysql' ),
                    'completed_at' => current_time( 'mysql' ),
                    'time_spent'   => $meta['time_spent'] ?? 0,
                ),
                array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d' )
            );

            // Check if course is now complete.
            $course_complete = self::check_course_completion( $user_id, $course_id );

            if ( $course_complete ) {
                self::complete_course( $user_id, $course_id );
            }

            $wpdb->query( 'COMMIT' );

            // Invalidate cache.
            Unstoppable_LMS_Cache::invalidate_user_progress( $user_id, $course_id );

            do_action( 'unstoppable_lms_item_completed', $user_id, $item_id, $item_type, $course_id );

            if ( $course_complete ) {
                do_action( 'unstoppable_lms_course_completed', $user_id, $course_id );
            }

            return array(
                'success'          => true,
                'course_complete'  => $course_complete,
                'next_item'        => self::get_next_item( $user_id, $course_id, $item_id, $item_type ),
                'progress_percent' => self::calculate_progress_percent( $user_id, $course_id ),
            );

        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            error_log( 'Unstoppable LMS Progress Error: ' . $e->getMessage() );

            return array(
                'success' => false,
                'error'   => 'database_error',
            );
        }
    }

    /**
     * Calculate course completion percentage.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     * @return float Completion percentage.
     */
    public static function calculate_progress_percent( int $user_id, int $course_id ): float {
        $structure = Unstoppable_LMS_Cache::get_course_structure( $course_id );

        if ( ! $structure || 0 === $structure['total_items'] ) {
            return 100.0;
        }

        $progress  = Unstoppable_LMS_Cache::get_user_progress( $user_id, $course_id );
        $completed = 0;

        foreach ( $progress as $item ) {
            if ( 'completed' === $item['status'] ) {
                $completed++;
            }
        }

        return round( ( $completed / $structure['total_items'] ) * 100, 1 );
    }

    /**
     * Check if an item is complete.
     *
     * @since 1.0.0
     * @param int    $user_id   User ID.
     * @param int    $item_id   Item ID.
     * @param string $item_type Item type.
     * @return bool Whether the item is complete.
     */
    public static function is_item_complete( int $user_id, int $item_id, string $item_type = 'lesson' ): bool {
        $course_id = self::get_item_course( $item_id, $item_type );
        if ( ! $course_id ) {
            return false;
        }

        $progress = Unstoppable_LMS_Cache::get_user_progress( $user_id, $course_id );
        $key      = $item_type . '_' . $item_id;

        return isset( $progress[ $key ] ) && 'completed' === $progress[ $key ]['status'];
    }

    /**
     * Get user's progress for a course.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     * @return array Progress summary.
     */
    public static function get_course_progress( int $user_id, int $course_id ): array {
        $structure = Unstoppable_LMS_Cache::get_course_structure( $course_id );
        $progress  = Unstoppable_LMS_Cache::get_user_progress( $user_id, $course_id );

        $completed_count   = 0;
        $in_progress_count = 0;

        foreach ( $progress as $item ) {
            if ( 'completed' === $item['status'] ) {
                $completed_count++;
            } elseif ( 'in_progress' === $item['status'] ) {
                $in_progress_count++;
            }
        }

        $total = $structure ? $structure['total_items'] : 0;

        return array(
            'total'       => $total,
            'completed'   => $completed_count,
            'in_progress' => $in_progress_count,
            'not_started' => max( 0, $total - $completed_count - $in_progress_count ),
            'percent'     => $total > 0 ? round( ( $completed_count / $total ) * 100, 1 ) : 0,
            'items'       => $progress,
        );
    }

    /**
     * Check if prerequisites are met for an item.
     *
     * @since 1.0.0
     * @param int    $user_id   User ID.
     * @param int    $item_id   Item ID.
     * @param string $item_type Item type.
     * @param int    $course_id Course ID.
     * @return bool Whether prerequisites are met.
     */
    public static function prerequisites_met( int $user_id, int $item_id, string $item_type, int $course_id ): bool {
        $progression = Unstoppable_LMS_Settings::get( 'course.progression', $course_id );

        if ( 'free' === $progression ) {
            return true;
        }

        $structure = Unstoppable_LMS_Cache::get_course_structure( $course_id );
        if ( ! $structure ) {
            return true;
        }

        $prev_item = null;

        foreach ( $structure['sections'] as $section ) {
            foreach ( $section['lessons'] as $lesson ) {
                if ( $lesson['id'] === $item_id && 'lesson' === $item_type ) {
                    return ! $prev_item || self::is_item_complete( $user_id, $prev_item['id'], $prev_item['type'] );
                }

                $prev_item = array(
                    'id'   => $lesson['id'],
                    'type' => 'lesson',
                );

                foreach ( $lesson['topics'] ?? array() as $topic ) {
                    if ( $topic['id'] === $item_id && 'topic' === $item_type ) {
                        return ! $prev_item || self::is_item_complete( $user_id, $prev_item['id'], $prev_item['type'] );
                    }
                    $prev_item = array(
                        'id'   => $topic['id'],
                        'type' => 'topic',
                    );
                }
            }
        }

        return true;
    }

    /**
     * Get the next item in the course.
     *
     * @since 1.0.0
     * @param int    $user_id      User ID.
     * @param int    $course_id    Course ID.
     * @param int    $current_id   Current item ID.
     * @param string $current_type Current item type.
     * @return array|null Next item data or null.
     */
    public static function get_next_item( int $user_id, int $course_id, int $current_id, string $current_type = 'lesson' ): ?array {
        $structure = Unstoppable_LMS_Cache::get_course_structure( $course_id );
        if ( ! $structure ) {
            return null;
        }

        $found_current = false;

        foreach ( $structure['sections'] as $section ) {
            foreach ( $section['lessons'] as $lesson ) {
                if ( $found_current ) {
                    return array(
                        'id'    => $lesson['id'],
                        'type'  => 'lesson',
                        'title' => $lesson['title'],
                        'url'   => get_permalink( $lesson['id'] ),
                    );
                }

                if ( $lesson['id'] === $current_id && 'lesson' === $current_type ) {
                    if ( ! empty( $lesson['topics'] ) ) {
                        return array(
                            'id'    => $lesson['topics'][0]['id'],
                            'type'  => 'topic',
                            'title' => $lesson['topics'][0]['title'],
                            'url'   => get_permalink( $lesson['topics'][0]['id'] ),
                        );
                    }
                    $found_current = true;
                    continue;
                }

                foreach ( $lesson['topics'] ?? array() as $index => $topic ) {
                    if ( $found_current ) {
                        return array(
                            'id'    => $topic['id'],
                            'type'  => 'topic',
                            'title' => $topic['title'],
                            'url'   => get_permalink( $topic['id'] ),
                        );
                    }

                    if ( $topic['id'] === $current_id && 'topic' === $current_type ) {
                        if ( isset( $lesson['topics'][ $index + 1 ] ) ) {
                            $next = $lesson['topics'][ $index + 1 ];
                            return array(
                                'id'    => $next['id'],
                                'type'  => 'topic',
                                'title' => $next['title'],
                                'url'   => get_permalink( $next['id'] ),
                            );
                        }
                        $found_current = true;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if course is complete.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     * @return bool Whether course is complete.
     */
    private static function check_course_completion( int $user_id, int $course_id ): bool {
        $structure = Unstoppable_LMS_Cache::get_course_structure( $course_id );
        if ( ! $structure || 0 === $structure['total_items'] ) {
            return true;
        }

        $progress  = Unstoppable_LMS_Cache::get_user_progress( $user_id, $course_id );
        $completed = 0;

        foreach ( $progress as $item ) {
            if ( 'completed' === $item['status'] ) {
                $completed++;
            }
        }

        return $completed >= $structure['total_items'];
    }

    /**
     * Mark course as complete.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     */
    private static function complete_course( int $user_id, int $course_id ): void {
        global $wpdb;

        $table = $wpdb->prefix . 'lms_enrollments';
        $wpdb->update(
            $table,
            array( 'status' => 'completed' ),
            array(
                'user_id'   => $user_id,
                'course_id' => $course_id,
            ),
            array( '%s' ),
            array( '%d', '%d' )
        );

        self::log_event( array(
            'user_id'    => $user_id,
            'course_id'  => $course_id,
            'item_id'    => $course_id,
            'item_type'  => 'course',
            'event_type' => 'course_completed',
        ) );

        Unstoppable_LMS_Cache::invalidate_enrollment( $user_id, $course_id );
    }

    /**
     * Log a progress event.
     *
     * @since 1.0.0
     * @param array $data Event data.
     * @return int|false Event ID or false on failure.
     */
    private static function log_event( array $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lms_progress_events';

        $result = $wpdb->insert(
            $table,
            array(
                'user_id'    => $data['user_id'],
                'course_id'  => $data['course_id'],
                'item_id'    => $data['item_id'],
                'item_type'  => $data['item_type'],
                'event_type' => $data['event_type'],
                'meta'       => isset( $data['meta'] ) ? wp_json_encode( $data['meta'] ) : null,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get course ID for an item.
     *
     * @since 1.0.0
     * @param int    $item_id   Item ID.
     * @param string $item_type Item type.
     * @return int|null Course ID or null.
     */
    private static function get_item_course( int $item_id, string $item_type ): ?int {
        $course_id = get_post_meta( $item_id, '_lms_course_id', true );
        return $course_id ? (int) $course_id : null;
    }

    /**
     * Check if user can access a course.
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     * @return bool Whether user has access.
     */
    private static function user_can_access( int $user_id, int $course_id ): bool {
        if ( user_can( $user_id, 'manage_lms' ) ) {
            return true;
        }

        $enrollment = Unstoppable_LMS_Cache::get_enrollment( $user_id, $course_id );

        if ( ! $enrollment ) {
            return false;
        }

        if ( $enrollment['expires_at'] && strtotime( $enrollment['expires_at'] ) < time() ) {
            return false;
        }

        return in_array( $enrollment['status'], array( 'active', 'completed' ), true );
    }

    /**
     * Rebuild progress from events (recovery function).
     *
     * @since 1.0.0
     * @param int $user_id   User ID.
     * @param int $course_id Course ID.
     */
    public static function rebuild_progress( int $user_id, int $course_id ): void {
        global $wpdb;

        $events_table   = $wpdb->prefix . 'lms_progress_events';
        $progress_table = $wpdb->prefix . 'lms_progress';

        $wpdb->delete(
            $progress_table,
            array(
                'user_id'   => $user_id,
                'course_id' => $course_id,
            ),
            array( '%d', '%d' )
        );

        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$events_table}
                 WHERE user_id = %d AND course_id = %d
                 ORDER BY created_at ASC",
                $user_id,
                $course_id
            )
        );

        $state = array();

        foreach ( $events as $event ) {
            $key = $event->item_type . '_' . $event->item_id;

            switch ( $event->event_type ) {
                case 'started':
                    if ( ! isset( $state[ $key ] ) ) {
                        $state[ $key ] = array(
                            'item_id'    => $event->item_id,
                            'item_type'  => $event->item_type,
                            'status'     => 'in_progress',
                            'started_at' => $event->created_at,
                        );
                    }
                    break;

                case 'completed':
                    $state[ $key ] = array(
                        'item_id'      => $event->item_id,
                        'item_type'    => $event->item_type,
                        'status'       => 'completed',
                        'started_at'   => $state[ $key ]['started_at'] ?? $event->created_at,
                        'completed_at' => $event->created_at,
                    );
                    break;

                case 'reset':
                    unset( $state[ $key ] );
                    break;
            }
        }

        foreach ( $state as $item ) {
            $wpdb->insert(
                $progress_table,
                array(
                    'user_id'      => $user_id,
                    'course_id'    => $course_id,
                    'item_id'      => $item['item_id'],
                    'item_type'    => $item['item_type'],
                    'status'       => $item['status'],
                    'started_at'   => $item['started_at'],
                    'completed_at' => $item['completed_at'] ?? null,
                ),
                array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
            );
        }

        Unstoppable_LMS_Cache::invalidate_user_progress( $user_id, $course_id );
    }
}
