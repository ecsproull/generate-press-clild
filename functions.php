<?php
/**
 * Generate Press Child functions
 */

if ( ! class_exists( 'SendGridMail' ) ) {
    $sendgridmail_file = __DIR__ . '/class-sendgridmail.php';
    if ( file_exists( $sendgridmail_file ) ) {
        require_once $sendgridmail_file;
    } else {
        error_log( 'class-sendgridmail.php not found in theme directory' );
    }
}

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

function scw_render_menu_subtree_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'menu'      => '',   // menu slug or name
        'parent_id' => 0,    // menu item ID
        'depth'     => 0,    // 0 = unlimited
    ], $atts );

    if ( empty( $atts['menu'] ) || empty( $atts['parent_id'] ) ) {
        return '';
    }

    $menu = wp_get_nav_menu_object( $atts['menu'] );
    if ( ! $menu ) {
        return '';
    }

    $items = wp_get_nav_menu_items( $menu->term_id );
    if ( ! $items ) {
        return '';
    }

    // Index items by parent
    $children = [];
    foreach ( $items as $item ) {
        $children[ $item->menu_item_parent ][] = $item;
    }

    // Recursive walker
    $render_items = function( $parent_id, $level = 0 ) use ( &$render_items, $children, $atts ) {
        if ( ! isset( $children[ $parent_id ] ) ) {
            return '';
        }

        if ( $atts['depth'] > 0 && $level >= $atts['depth'] ) {
            return '';
        }

        $html = '<ul class="submenu-section">';
        foreach ( $children[ $parent_id ] as $item ) {
            $html .= '<li class="menu-item menu-item-' . esc_attr( $item->ID ) . '">';
            $html .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
            $html .= $render_items( $item->ID, $level + 1 );
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    };

    return $render_items( (int) $atts['parent_id'] );
}
add_shortcode( 'menu_subtree', 'scw_render_menu_subtree_shortcode' );

add_filter( 'comments_open', function ( $open, $post_id ) {

    // Change this to your parent page ID
    $parent_page_id = 4579;

    $post = get_post( $post_id );
    if ( ! $post ) {
        return $open;
    }

    // Only apply to pages
    if ( $post->post_type !== 'page' ) {
        return $open;
    }

    // If this page is a child of the parent
    if ( (int) $post->post_parent === $parent_page_id ) {
        return true;
    }

    return $open;
 
}, 10, 2 );

function scw_send_admin_alert_email( $subject, $message ) {
    $sendgrid_autoload = WP_PLUGIN_DIR . '/SignUps/vendor/autoload.php';
    if ( file_exists( $sendgrid_autoload ) ) {
        require_once $sendgrid_autoload;
    }

    if ( ! class_exists( 'SendGridMail' ) || ! class_exists( '\SendGrid' ) ) {
        error_log( 'SendGridMail admin alert skipped: SendGrid SDK not available.' );
        return;
    }

    try {
        $sgm = new SendGridMail();
        $sgm->send_mail( 'ecsproull765@gmail.com', $subject, $message );
    } catch ( \Throwable $e ) {
        error_log( 'SendGridMail admin alert failed: ' . $e->getMessage() );
    }
}

add_action( 'set_user_role', function( $user_id, $role, $old_roles ) {
    if ( 'administrator' !== $role ) {
        return;
    }

    $user       = get_userdata( $user_id );
    $user_login = $user ? $user->user_login : "user #{$user_id}";
    $is_new     = empty( $old_roles );

    scw_send_admin_alert_email(
        'Administrator Role Granted',
        sprintf(
            '%s "%s" was %s administrator on %s.',
            $is_new ? 'New user' : 'User',
            $user_login,
            $is_new ? 'created as' : 'changed to',
            wp_parse_url( home_url(), PHP_URL_HOST )
        )
    );
}, 10, 3 );

add_action('wp_login', function($user_login, $user) {
    global $wpdb;
    $user_roles = "";
    foreach ( $user->roles as $role ) {
        $user_roles .= $role . ", ";
    }

    $log_data['logs_text']          = $user_login . ' Roles : ' . $user_roles;
	$log_data['logs_function_name'] = "wp_login";
	$log_data['logs_file_name']     = "functions.php";
	$log_data['logs_ip_address']    = $_SERVER['HTTP_X_REAL_IP'] ?? '';
	$now                            = new DateTime( 'now', new DateTimeZone( 'America/Phoenix' ) );
	$log_data['logs_date_time']     = $now->format( 'Y-m-d g:i A' );
	$wpdb->insert( 'wp_scw_logs', $log_data );

    if ( in_array( 'administrator', $user->roles, true ) ) {
        scw_send_admin_alert_email(
            'Administrator Login Alert',
            sprintf(
                'Administrator "%s" logged in on %s at %s from IP %s.',
                $user_login,
                wp_parse_url( home_url(), PHP_URL_HOST ),
                $log_data['logs_date_time'],
                $log_data['logs_ip_address']
            )
        );
    }

}, 10, 2);

add_action('wp_login_failed', function($username) {
    global $wpdb;

    if ( $username == 'petermeyer') {
        return;
    }

    $log_data['logs_text']          = $username;
	$log_data['logs_function_name'] = "wp_login_failed";
	$log_data['logs_file_name']     = "functions.php";
	$log_data['logs_ip_address']    = $_SERVER['HTTP_X_REAL_IP'] ?? '';
	$now                            = new DateTime( 'now', new DateTimeZone( 'America/Phoenix' ) );
	$log_data['logs_date_time']     = $now->format( 'Y-m-d g:i A' );
	$wpdb->insert( 'wp_scw_logs', $log_data );
    
    $ip = $_SERVER['HTTP_X_REAL_IP'] ?? 'unknown';
    $log_line = sprintf(
        "%s Failed login for username=%s from IP=%s\n",
        date('Y-m-d H:i:s'),
        $username,
        $ip
    );
    error_log( $log_line, 3, '/var/log/wp-failed-logins.log' );
});

add_filter( 'rest_endpoints', function( $endpoints ) {
    unset( $endpoints['/wp/v2/users'] );
    unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    return $endpoints;
});

add_filter( 'request', function( $query_vars ) {
    if ( isset( $query_vars['author'] ) && ! is_user_logged_in() ) {
        wp_safe_redirect( home_url(), 301 );
        exit;
    }
    return $query_vars;
});

/**
 * mu-plugin: Require valid login nonce (blocks direct POST to wp-login.php)
 */
add_action( 'login_form', function() {
    // Only add the nonce field on the login form itself
    wp_nonce_field( 'wp_login_check', '_login_nonce' );
} );

add_filter( 'authenticate', function( $user, $username, $password ) {
    // Only enforce on the actual login POST, not other authenticate() calls
    // (REST auth, XML-RPC, etc. don't go through wp-login.php POST)
    if ( ! empty( $_POST['log'] ) && ! empty( $_POST['pwd'] ) ) {
        if (
            empty( $_POST['_login_nonce'] ) ||
            ! wp_verify_nonce( $_POST['_login_nonce'], 'wp_login_check' )
        ) {
            return new WP_Error( 'invalid_nonce', __( 'Security check failed. Please try logging in again.' ) );
        }
    }
    return $user;
}, 20, 3 ); // priority 20 = runs after core's username/password checks are queued, before they execute

add_filter( 'wp_is_application_passwords_available', '__return_false' );