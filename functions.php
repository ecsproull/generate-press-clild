<?php
/**
 * Generate Press Child functions
 */

add_action( 'wp_enqueue_scripts', function() {
    // Enqueue child theme style dependent on parent theme
    wp_enqueue_style(
        'generatepress-child-style',
        get_stylesheet_uri(),
        array('generate-style'), // Add parent theme as dependency
        wp_get_theme()->get('Version')
    );

    wp_enqueue_style(
        'gp-child-custom',
        get_stylesheet_directory_uri() . '/assets/css/custom.css',
        array('generatepress-child-style'), // This will now load after parent AND child
        filemtime(get_stylesheet_directory() . '/assets/css/custom.css')
    );

    wp_enqueue_script(
        'tt4-child-js',
        get_stylesheet_directory_uri() . '/assets/js/site.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );
});

/**
  * This function adds support for passing parameters to pages.
  * You can add additional parameters here. For some reason I had
  * trouble until I added them in reverse order of their usage.
  */

function wwp_custom_query_vars_filter($vars) {
    $vars[] .= 'classtitle';
    $vars[] .= 'classid';
    return $vars;
}
add_filter( 'query_vars', 'wwp_custom_query_vars_filter' );

/**
 * Lists a directory of manuals.
 */
function popup_image_func( $args ) {

	ob_start();
	?>
		<span id="foo" class="image-span" data-url="<?php echo esc_html( $args['link'] ); ?>" >
				<?php echo esc_html( $args['text'] ); ?>
		</span>
	<?php

	return ob_get_clean();
}
add_shortcode( 'popup_image', 'popup_image_func' );

/**
 * PDF.js Viewer Shortcode
 * Usage: [pdfjs-viewer url="URL_TO_PDF" width="100%" height="800px" toolbar="true"]
 * You can also pass parameters via URL: ?url=URL_TO_PDF&toolbar=true
 */
function pdfjs_viewer_shortcode($atts) {
    $atts = shortcode_atts([
        'url' => '',
        'width' => '100%',
        'height' => '800px',
        'toolbar' => 'true'
    ], $atts);

    if (empty($atts['url']) && isset($_GET['url'])) {
        $atts['url'] = esc_url_raw($_GET['url']);
    }

    if (empty($atts['url'])) {
        return '<p style="color:red;">Error: No PDF URL provided.</p>';
    }

    if (isset($_GET['toolbar'])) {
        $atts['toolbar'] = esc_html($_GET['toolbar']);
    }

    // Path to PDF.js viewer 
    $viewer_url = site_url('/wp-content/pdfjs/web/viewer.html');

    // PDF file URL passed in shortcode
    $pdf_url = esc_url($atts['url']);

    // toolbar parameter → PDF.js uses toolbar=0 or 1
    $toolbar_value = ($atts['toolbar'] === 'false' || $atts['toolbar'] === '0') ? '0' : '1';

    // Build iframe src - use hash fragment with proper syntax
    $src = sprintf(
        '%s?file=%s#toolbar=%s',
        esc_url($viewer_url),
        rawurlencode($pdf_url),
        $toolbar_value
    );

    // Output iframe
    return sprintf(
        '<iframe src="%s" width="%s" height="%s" style="border:none;"></iframe>',
        $src,
        esc_attr($atts['width']),
        esc_attr($atts['height'])
    );
}
add_shortcode('pdfjs-viewer', 'pdfjs_viewer_shortcode');


add_filter( 'do_shortcode_tag', 'esp_bsk_pdfm_category_ul_redirect', 10, 3 );

function esp_bsk_pdfm_category_ul_redirect( $output, $tag, $attr ) {
    if ( $tag === 'bsk-pdfm-category-ul' ) {
        // Use DOMDocument to replace all PDF links with your viewer page
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $output);

        $links = $dom->getElementsByTagName('a');

        foreach ( $links as $link ) {
            $href = $link->getAttribute('href');

            // Only modify PDF links
            if ( preg_match('/\.pdf$/i', $href) ) {
                $viewer_url = add_query_arg( 'url', urlencode( $href ), home_url('/pdf-viewer/') );
                $link->setAttribute('href', $viewer_url);
                //$link->setAttribute('target', '_blank'); // optional
            }
        }

        // Save and return new HTML
        $output = $dom->saveHTML();
    }

    return $output;
}


