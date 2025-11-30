<?php
/**
 * The main template file (index.php)
 *
 * This is the most generic template file in a WordPress theme.
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

use Timber\Timber; // Use the namespace for cleaner code

$context = Timber::context();

// 1. Fetch posts (using false to fetch the main WordPress query)
$context['posts'] = Timber::get_posts(false);

// 2. Assign PAGINATION data
// Note: Timber::get_pagination() is used instead of Timber\Timber::get_pagination()
// because of the 'use Timber\Timber' statement above.
$context['pagination'] = Timber::get_pagination();

// 3. Added variables needed for the blog template and sidebar
$context['categories']    = Timber::get_terms('category');
$context['has_sidebar']   = is_active_sidebar( 'blog-sidebar' ); 
$context['search_query']  = get_search_query();
$context['title']         = 'Insights & Resources'; // Default title for blog index
// $context['foo'] is inherited from the minimal file but not necessary,
// included here only to show it's possible to add extra variables.
$context['foo']           = 'bar'; 

// 4. Define the template hierarchy
// Prioritize 'archive-blog.twig' for the blog index, and fall back to 'index.twig'.
$templates = array( 'archive-blog.twig', 'index.twig' );

// If this is the main blog/posts page, also check for front-page.twig and home.twig first.
if ( is_home() && is_front_page() ) {
    // If it's the static front page that IS the blog page (rare configuration)
	array_unshift( $templates, 'front-page.twig', 'home.twig' );
} elseif ( is_home() ) {
    // If it's the designated posts page
    array_unshift( $templates, 'home.twig' );
} elseif ( is_front_page() ) {
    // If it's the static front page (not the posts page)
    array_unshift( $templates, 'front-page.twig' );
}

// 5. Render the template
Timber::render( $templates, $context );