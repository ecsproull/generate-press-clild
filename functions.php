<?php
/**
 * Twenty Twenty-Four Child functions
 */

add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'twentytwentyfour-child-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
});

wp_enqueue_script(
    'tt4-child-js',
    get_stylesheet_directory_uri() . '/assets/js/site.js',
    [],
    wp_get_theme()->get('Version'),
    true
);

