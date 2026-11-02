<?php
/**
 * Topic functionality.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Topic class.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Topic {

    /**
     * Get a topic by ID.
     *
     * @since 1.0.0
     * @param int $topic_id Topic ID.
     * @return WP_Post|null Topic post or null.
     */
    public static function get( int $topic_id ): ?WP_Post {
        $topic = get_post( $topic_id );
        if ( ! $topic || 'lms_topic' !== $topic->post_type ) {
            return null;
        }
        return $topic;
    }
}
