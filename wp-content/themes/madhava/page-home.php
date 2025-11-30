<?php
/**
 * Template Name: Home Page
 *
 * This is the template that displays the home page.
 *
 */

$context = \Timber\Timber::context();

\Timber\Timber::render( 'page-home.twig', $context );