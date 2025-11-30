<?php
/**
 * Template Name: Blog Page Display
 * Description: Displays a custom list of all blog posts using the archive-blog.twig layout.
 */

// Get the standard Timber context
$context = Timber\Timber::context();

// 1. Get the current WordPress page (The page where this template is assigned)
$context['post'] = Timber\Timber::get_post(); 

// --- CRITICAL FIX: SAFELY RESOLVE THE PAGE NUMBER (Fixes the stray '1' and enables pagination) ---
// On a static page, we must check for the 'page' query variable, not just 'paged'.

$paged = 1; // Start with page 1

// 1. Check for 'paged' (standard blog pagination)
$paged_var = get_query_var('paged');
if ($paged_var) {
    $paged = (int) $paged_var;
}

// 2. If not found, check for 'page' (static front page pagination)
if ($paged === 1) { 
    $page_var = get_query_var('page');
    if ($page_var) {
        $paged = (int) $page_var;
    }
}
// -------------------------------------------------------------------------------------------------


// 2. Define the Query Arguments for all Posts
$args = array(
    'post_type'      => 'post',
    'posts_per_page' => 10, 
    'paged'          => $paged,
    'post_status'    => 'publish',
);

// 3. --- FIX: Run standard WP_Query and temporarily set the global query for pagination ---
// This is the most reliable way to handle a custom query with pagination outside the main loop
// and fixes the Timber\PostQuery TypeErrors.

// First, run the standard WP_Query
$custom_query = new WP_Query( $args );

// Then, wrap the results using Timber::get_posts() and pass the WP_Query object
$context['posts'] = Timber\Timber::get_posts( $custom_query ); 

// Set the global $wp_query object to our custom query
// This is ESSENTIAL for Timber\Timber::get_pagination() to work correctly.
global $wp_query;
$temp_query = $wp_query; // Save original query
$wp_query = $custom_query; // Set to custom query


// 4. Assign PAGINATION
// Now Timber::get_pagination() will correctly use the data from $custom_query
$context['pagination'] = Timber\Timber::get_pagination();

// Restore the original global query to prevent further conflicts
$wp_query = $temp_query; 
// -------------------------------------------------------------------------------------------------


// 5. Assign other context variables (used by archive-blog.twig)
$context['title'] = $context['post']->title; 
$context['categories'] = Timber\Timber::get_terms('category');
$context['has_sidebar'] = is_active_sidebar( 'blog-sidebar' ); 
$context['search_query'] = get_search_query();

// 6. Render the existing archive-blog.twig file
// Your blog list logic in archive-blog.twig will now execute.
Timber\Timber::render( array( 'archive-blog.twig', 'page.twig' ), $context );