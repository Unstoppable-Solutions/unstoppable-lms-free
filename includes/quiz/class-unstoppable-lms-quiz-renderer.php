<?php
/**
 * Quiz rendering.
 *
 * @package Unstoppable_LMS
 * @since 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Quiz Renderer class.
 *
 * Renders quizzes with pre-rendered HTML for caching compatibility.
 *
 * @since 1.0.0
 */
class Unstoppable_LMS_Quiz_Renderer {

    /**
     * Render a quiz.
     *
     * @since 1.0.0
     * @param int $quiz_id Quiz ID.
     * @return string Quiz HTML.
     */
    public static function render( int $quiz_id ): string {
        $config = Unstoppable_LMS_Quiz::get_config( $quiz_id );
        if ( ! $config ) {
            return '';
        }

        // TODO: Implement full quiz rendering.
        return '<div class="lms-quiz">Quiz rendering coming soon.</div>';
    }
}
