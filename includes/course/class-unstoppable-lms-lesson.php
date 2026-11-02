<?php
/**
 * Lesson functionality.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Lesson class.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Lesson {

    /**
     * Get a lesson by ID.
     *
     * @since 1.0.0
     * @param int $lesson_id Lesson ID.
     * @return WP_Post|null Lesson post or null.
     */
    public static function get( int $lesson_id ): ?WP_Post {
        $lesson = get_post( $lesson_id );
        if ( ! $lesson || 'lms_lesson' !== $lesson->post_type ) {
            return null;
        }
        return $lesson;
    }

    /**
     * Get course ID for a lesson.
     *
     * @since 1.0.0
     * @param int $lesson_id Lesson ID.
     * @return int|null Course ID or null.
     */
    public static function get_course_id( int $lesson_id ): ?int {
        $course_id = get_post_meta( $lesson_id, '_lms_course_id', true );
        return $course_id ? (int) $course_id : null;
    }
}
