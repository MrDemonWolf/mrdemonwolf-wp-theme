<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Activation of the child theme
function mrdemonwolf_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

	// Magnific Popup (loaded locally)
	wp_enqueue_style( 'magnific-popup-css', get_stylesheet_directory_uri() . '/assets/magnific-popup.min.css', array(), '1.1.0', 'all' );
	wp_enqueue_script( 'magnific-popup-js', get_stylesheet_directory_uri() . '/assets/jquery.magnific-popup.min.js', array( 'jquery' ), '1.1.0', true );

	// Main script
	wp_enqueue_script( 'mrdemonwolf-script', get_stylesheet_directory_uri() . '/script.js', array( 'jquery', 'magnific-popup-js' ), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'mrdemonwolf_enqueue_styles' );

//Deleting the Wordpress version number
function mrdemonwolf_delete_version() {
	return '';
}
add_filter( 'the_generator', 'mrdemonwolf_delete_version' );

// Hide administration login errors
function mrdemonwolf_hide_login_errors() {
	return __( 'The username or password is incorrect', 'mrdemonwolf' );
}
add_filter( 'login_errors', 'mrdemonwolf_hide_login_errors' );

// SVG upload/rendering is handled by the SVG Support plugin.
// @see https://wordpress.org/plugins/svg-support/

// Disable year/month uploads folders
function mrdemonwolf_disable_year_month_uploads() {
	update_option( 'uploads_use_yearmonth_folders', 0 );
}
add_action( 'after_switch_theme', 'mrdemonwolf_disable_year_month_uploads' );

// Resolve the mu-plugins directory with a sensible fallback.
function mrdemonwolf_mu_dir() {
	return defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : ( ABSPATH . 'wp-content/mu-plugins' );
}

// ===============================
// Theme Cleanup on Switch
// ===============================
function mrdemonwolf_on_theme_switch() {
	$mu_dir  = mrdemonwolf_mu_dir();
	$mu_file = $mu_dir . '/mdw-cleanup-notice.php';

	if ( ! is_dir( $mu_dir ) ) {
		wp_mkdir_p( $mu_dir );
	}

	$mu_code = <<<'PHP'
<?php
/**
 * Plugin Name: MrDemonWolf Cleanup Notice
 * Description: One-time admin notice to clean up MrDemonWolf theme data after deactivation.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_notices', 'mdw_cleanup_admin_notice' );
function mdw_cleanup_admin_notice() {
	$nonce = wp_create_nonce( 'mdw_cleanup_action' );
	?>
	<div class="notice notice-warning is-dismissible" id="mdw-cleanup-notice">
		<p><strong><?php echo esc_html__( 'MrDemonWolf theme was deactivated.', 'mrdemonwolf' ); ?></strong>
		<?php echo esc_html__( 'Clean up theme data?', 'mrdemonwolf' ); ?></p>
		<p>
			<button class="button button-primary" id="mdw-cleanup-btn"><?php echo esc_html__( 'Clean Up', 'mrdemonwolf' ); ?></button>
			<button class="button" id="mdw-dismiss-btn"><?php echo esc_html__( 'Dismiss', 'mrdemonwolf' ); ?></button>
		</p>
	</div>
	<script>
	(function(){
		function mdwCleanupAjax(action) {
			var data = new FormData();
			data.append('action', 'mdw_cleanup_theme_data');
			data.append('cleanup', action);
			data.append('nonce', '<?php echo esc_js( $nonce ); ?>');
			fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: data })
				.then(function(r){ return r.json(); })
				.then(function(){ document.getElementById('mdw-cleanup-notice').remove(); });
		}
		document.getElementById('mdw-cleanup-btn').addEventListener('click', function(){ mdwCleanupAjax('clean'); });
		document.getElementById('mdw-dismiss-btn').addEventListener('click', function(){ mdwCleanupAjax('dismiss'); });
	})();
	</script>
	<?php
}

add_action( 'wp_ajax_mdw_cleanup_theme_data', 'mdw_cleanup_theme_data_handler' );
function mdw_cleanup_theme_data_handler() {
	check_ajax_referer( 'mdw_cleanup_action', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Insufficient permissions.' );
	}

	$cleanup = isset( $_POST['cleanup'] ) ? sanitize_text_field( wp_unslash( $_POST['cleanup'] ) ) : '';

	if ( 'clean' === $cleanup ) {
		update_option( 'uploads_use_yearmonth_folders', 1 );
		delete_post_meta_by_key( '_mrdemonwolf_service_image' );
		flush_rewrite_rules();
	}

	// Delete this mu-plugin file regardless of clean/dismiss
	$mu_dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : ( ABSPATH . 'wp-content/mu-plugins' );
	$self   = realpath( $mu_dir . '/mdw-cleanup-notice.php' );
	if ( $self && strpos( $self, realpath( $mu_dir ) ) === 0 && file_exists( $self ) ) {
		unlink( $self );
	}

	wp_send_json_success();
}
PHP;

	if ( false === file_put_contents( $mu_file, $mu_code ) ) {
		error_log( 'MrDemonWolf: failed to write cleanup mu-plugin to ' . $mu_file );
	}
}
add_action( 'switch_theme', 'mrdemonwolf_on_theme_switch' );

// ===============================
// Register "Service" Custom Post Type
// ===============================
function mrdemonwolf_register_service_cpt() {

	$labels = array(
		'name'               => __( 'Services', 'mrdemonwolf' ),
		'singular_name'      => __( 'Service', 'mrdemonwolf' ),
		'menu_name'          => __( 'Services', 'mrdemonwolf' ),
		'add_new_item'       => __( 'Add New Service', 'mrdemonwolf' ),
		'edit_item'          => __( 'Edit Service', 'mrdemonwolf' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'menu_icon'          => 'dashicons-image-filter',
		'has_archive'        => true,
		'rewrite'            => array( 'slug' => 'services' ),
		'show_in_rest'       => true,
	);

	register_post_type( 'service', $args );
}
add_action( 'init', 'mrdemonwolf_register_service_cpt' );

// Add custom image field
function mrdemonwolf_service_add_metabox() {
	add_meta_box(
		'mrdemonwolf_service_custom_image',
		__( 'Icon', 'mrdemonwolf' ),
		'mrdemonwolf_service_custom_image_callback',
		'service',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'mrdemonwolf_service_add_metabox' );

// Enqueue media picker script on the service edit screen only.
function mrdemonwolf_service_admin_assets( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'service' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'mrdemonwolf-service-metabox',
		get_stylesheet_directory_uri() . '/assets/admin-service-metabox.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);
	wp_localize_script(
		'mrdemonwolf-service-metabox',
		'mdwServiceMetabox',
		array(
			'title'      => __( 'Select Image', 'mrdemonwolf' ),
			'buttonText' => __( 'Use this image', 'mrdemonwolf' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'mrdemonwolf_service_admin_assets' );

function mrdemonwolf_service_custom_image_callback( $post ) {

	wp_nonce_field( 'mrdemonwolf_service_image_nonce', 'mrdemonwolf_service_image_nonce_field' );

	$image_url = get_post_meta( $post->ID, '_mrdemonwolf_service_image', true );

	?>
	<div>
		<img id="mdw-service-image-preview"
			 src="<?php echo esc_url( $image_url ); ?>"
			 style="max-width:100%;<?php echo $image_url ? '' : 'display:none;'; ?>" />

		<input type="hidden" id="mdw-service-image" name="mrdemonwolf_service_image" value="<?php echo esc_attr( $image_url ); ?>">

		<button type="button" class="button" id="mdw-service-upload-btn"><?php esc_html_e( 'Select Image', 'mrdemonwolf' ); ?></button>
		<button type="button" class="button" id="mdw-service-remove-btn" style="<?php echo $image_url ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'mrdemonwolf' ); ?></button>
	</div>
	<?php
}

// Save the field as URL
function mrdemonwolf_service_save_image( $post_id ) {
	if (
		! isset( $_POST['mrdemonwolf_service_image_nonce_field'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['mrdemonwolf_service_image_nonce_field'] ) ),
			'mrdemonwolf_service_image_nonce'
		)
	) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['mrdemonwolf_service_image'] ) ) {
		$image_url = esc_url_raw( sanitize_text_field( wp_unslash( $_POST['mrdemonwolf_service_image'] ) ) );
		update_post_meta( $post_id, '_mrdemonwolf_service_image', $image_url );
	}
}
add_action( 'save_post_service', 'mrdemonwolf_service_save_image' );

// ===============================
// Breadcrumbs Shortcode
// ===============================
function mrdemonwolf_breadcrumb_sep() {
	return ' <span class="mdw-separator"></span>';
}

// Render a breadcrumb link segment (separator + anchor).
function mrdemonwolf_breadcrumb_link( $url, $label ) {
	return mrdemonwolf_breadcrumb_sep()
		. '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
}

// Render a breadcrumb current-page segment (separator + span).
function mrdemonwolf_breadcrumb_current( $label ) {
	return mrdemonwolf_breadcrumb_sep() . '<span>' . esc_html( $label ) . '</span>';
}

// Return a breadcrumb link for the first term of $taxonomy on $post_id, or ''.
function mrdemonwolf_primary_term_link( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}
	$term = reset( $terms );
	return mrdemonwolf_breadcrumb_link( get_term_link( $term->term_id, $taxonomy ), $term->name );
}

function mrdemonwolf_breadcrumbs_shortcode( $atts ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return '';
	}

	global $post;

	if ( ! $post ) {
		return '';
	}

	$atts = shortcode_atts( array( 'home' => 'Home' ), $atts, 'mrdemonwolf_breadcrumbs' );
	$breadcrumb = '<nav class="mdw-breadcrumbs" aria-label="Breadcrumb">';
	$breadcrumb .= '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( $atts['home'] ) . '</a>';

	if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
		if ( is_singular( 'product' ) ) {
			$breadcrumb .= mrdemonwolf_primary_term_link( $post->ID, 'product_cat' );
			$breadcrumb .= mrdemonwolf_breadcrumb_current( get_the_title() );
		} elseif ( is_tax( 'product_cat' ) ) {
			$breadcrumb .= mrdemonwolf_breadcrumb_current( single_term_title( '', false ) );
		} elseif ( is_shop() ) {
			$breadcrumb .= mrdemonwolf_breadcrumb_current( get_the_title( wc_get_page_id( 'shop' ) ) );
		}
	} elseif ( is_single() && 'post' === get_post_type() ) {
		$categories = get_the_category( $post->ID );
		if ( ! empty( $categories ) ) {
			$breadcrumb .= mrdemonwolf_breadcrumb_link( get_category_link( $categories[0]->term_id ), $categories[0]->name );
		}
		$breadcrumb .= mrdemonwolf_breadcrumb_current( get_the_title() );

	} elseif ( is_single() && 'project' === get_post_type() ) {
		$breadcrumb .= mrdemonwolf_primary_term_link( $post->ID, 'project_category' );
		$breadcrumb .= mrdemonwolf_breadcrumb_current( get_the_title() );
	} elseif ( is_page() ) {
		$breadcrumb .= mrdemonwolf_breadcrumb_current( get_the_title() );

	} elseif ( is_category() ) {
		$breadcrumb .= mrdemonwolf_breadcrumb_current( single_cat_title( '', false ) );
	} else {
		$breadcrumb .= mrdemonwolf_breadcrumb_current( preg_replace( '/^.*?:\s*/', '', get_the_archive_title() ) );
	}

	return $breadcrumb . '</nav>';
}
add_shortcode( 'mrdemonwolf_breadcrumbs', 'mrdemonwolf_breadcrumbs_shortcode' );


// ===============================
// Current Post Tags Shortcode
// ===============================
function mrdemonwolf_tags_shortcode() {
	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$post_type = get_post_type( $post_id );

	// If it's a "project", use its custom taxonomy
	if ( 'project' === $post_type ) {
		$tags = get_the_terms( $post_id, 'project_tag' );
	} else {
		$tags = get_the_tags( $post_id );
	}

	if ( empty( $tags ) || is_wp_error( $tags ) ) {
		return '';
	}

	$html = '';
	foreach ( $tags as $tag ) {
		$html .= sprintf(
			'<a class="mdw-tags" href="%s">%s</a>',
			esc_url( get_term_link( $tag->term_id ) ),
			esc_html( $tag->name )
		);
	}

	return $html;
}
add_shortcode( 'mrdemonwolf_tags', 'mrdemonwolf_tags_shortcode' );

// ===============================
// Social Share Shortcode
// ===============================
add_shortcode(
	'mrdemonwolf_social_share',
	function () {
		$url   = rawurlencode( get_permalink() );
		$title = rawurlencode( get_the_title() );

		$platforms = array(
			array(
				'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
				'icon' => '&#xe093;',
			),
			array(
				'href' => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
				'icon' => '&#xe094;',
			),
			array(
				'href' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url,
				'icon' => '&#xe09d;',
			),
		);

		$buttons = '';
		foreach ( $platforms as $p ) {
			$buttons .= '<a href="' . esc_url( $p['href'] ) . '" target="_blank" rel="noopener">' . $p['icon'] . '</a>';
		}

		return $buttons;
	}
);
