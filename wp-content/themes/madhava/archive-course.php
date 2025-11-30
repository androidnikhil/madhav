<?php
/**
 * Template Name: Courses Archive
 * Description: Display all courses
 */

$context = Timber::context();
$context['title'] = 'Our Courses';
$context['courses'] = Timber::get_posts(array(
    'post_type' => 'course',
    'posts_per_page' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC'
));

// Add course meta data to each course
foreach ($context['courses'] as $course) {
    $course->tutor_name = get_post_meta($course->ID, '_course_tutor_name', true);
    $course->duration = get_post_meta($course->ID, '_course_duration', true);
    $course->price = get_post_meta($course->ID, '_course_price', true);
    $course->course_level = get_post_meta($course->ID, '_course_level', true);
}

Timber::render('archive-course.twig', $context);
