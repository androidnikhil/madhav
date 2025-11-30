<?php
/**
 * Single Course Template
 */

$context = Timber::context();
$post = Timber::get_post();
$context['post'] = $post;

// Get course meta data
$post->tutor_name = get_post_meta($post->ID, '_course_tutor_name', true);
$post->duration = get_post_meta($post->ID, '_course_duration', true);
$post->price = get_post_meta($post->ID, '_course_price', true);
$post->course_level = get_post_meta($post->ID, '_course_level', true);
$post->students_enrolled = get_post_meta($post->ID, '_students_enrolled', true);
$post->course_contents = get_post_meta($post->ID, '_course_contents', true);
$post->what_you_learn = get_post_meta($post->ID, '_what_you_learn', true);
$post->prerequisites = get_post_meta($post->ID, '_prerequisites', true);

// Convert textarea content to arrays
$post->course_contents_array = [];
$post->what_you_learn_array = [];
$post->prerequisites_array = [];

if ($post->course_contents) {
    $post->course_contents_array = array_filter(preg_split('/\r\n|\r|\n/', $post->course_contents));
}

if ($post->what_you_learn) {
    $post->what_you_learn_array = array_filter(preg_split('/\r\n|\r|\n/', $post->what_you_learn));
}

if ($post->prerequisites) {
    $post->prerequisites_array = array_filter(preg_split('/\r\n|\r|\n/', $post->prerequisites));
}

// Get related courses
$context['related_courses'] = Timber::get_posts(array(
    'post_type' => 'course',
    'posts_per_page' => 3,
    'post__not_in' => array($post->ID),
    'orderby' => 'rand'
));

foreach ($context['related_courses'] as $course) {
    $course->tutor_name = get_post_meta($course->ID, '_course_tutor_name', true);
    $course->price = get_post_meta($course->ID, '_course_price', true);
}

Timber::render('single-course.twig', $context);
