<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context         = Timber::context();
$timber_post     = Timber::get_post();
$context['post'] = $timber_post;

// Get recent posts for sidebar
$context['recent_posts'] = Timber::get_posts([
    'post_type' => 'post',
    'posts_per_page' => 5,
    'post__not_in' => [$timber_post->ID]
]);

// Get related posts by category
$categories = wp_get_post_categories($timber_post->ID);

if (!empty($categories)) {
    $context['related_posts'] = Timber::get_posts([
        'post_type' => 'post',
        'posts_per_page' => 3,
        'post__not_in' => [$timber_post->ID],
        'category__in' => $categories,
        'orderby' => 'date',
        'order' => 'DESC'
    ]);
}

if ( post_password_required( $timber_post->ID ) ) {
	Timber::render( 'single-password.twig', $context );
} else {
	Timber::render( array( 'single-' . $timber_post->ID . '.twig', 'single-' . $timber_post->post_type . '.twig', 'single-' . $timber_post->slug . '.twig', 'single.twig' ), $context );
}
