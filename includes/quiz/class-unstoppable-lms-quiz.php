<?php
/**
 * Quiz functionality.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Quiz class.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Quiz {

    /**
     * Get a quiz by ID.
     *
     * @since 1.0.0
     * @param int $quiz_id Quiz ID.
     * @return WP_Post|null Quiz post or null.
     */
    public static function get( int $quiz_id ): ?WP_Post {
        $quiz = get_post( $quiz_id );
        if ( ! $quiz || 'lms_quiz' !== $quiz->post_type ) {
            return null;
        }
        return $quiz;
    }

    /**
     * Get quiz configuration.
     *
     * @since 1.0.0
     * @param int $quiz_id Quiz ID.
     * @return array|null Quiz config or null.
     */
    public static function get_config( int $quiz_id ): ?array {
        return Unstoppable_LMS_Cache::get_quiz_config( $quiz_id );
    }
}
