<?php
/**
 * Template Name: Certificate Verification
 * Description: Page template for certificate verification
 */

$context = Timber::context();
$timber_post = Timber::get_post();
$context['post'] = $timber_post;

// Initialize variables
$context['certificate'] = null;
$context['cert_id_param'] = '';

// Get certificate ID from URL parameter (for QR code redirect)
if (isset($_GET['cert_id'])) {
    $context['cert_id_param'] = sanitize_text_field($_GET['cert_id']);
}

// Handle form submission
if (isset($_POST['certificate_id']) && !empty($_POST['certificate_id'])) {
    $certificate_id = sanitize_text_field($_POST['certificate_id']);
    
    // Query custom post type for certificate
    $args = array(
        'post_type' => 'certificate',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'certificate_id',
                'value' => $certificate_id,
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        
        $context['certificate'] = array(
    'id' => get_post_meta($post_id, 'certificate_id', true),
    'recipient_name' => get_post_meta($post_id, 'recipient_name', true),
    'course_name' => get_post_meta($post_id, 'course_name', true),
    'issue_date' => get_post_meta($post_id, 'issue_date', true),
    'duration' => get_post_meta($post_id, 'duration', true),
    'description' => get_post_meta($post_id, 'description', true),
    'certificate_image' => get_post_meta($post_id, 'certificate_final_image', true) ?: get_post_meta($post_id, 'certificate_image', true),
    'status' => 'valid'
);

        
        wp_reset_postdata();
    } else {
        $context['certificate'] = array('status' => 'invalid');
    }
}

Timber::render('template-certificate-verification.twig', $context);
