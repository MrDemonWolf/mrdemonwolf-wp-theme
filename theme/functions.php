<?php
//Activation of the child theme
function mrdemonwolf_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

	// Magnific Popup (loaded locally)
	wp_enqueue_style('magnific-popup-css', get_stylesheet_directory_uri() . '/assets/magnific-popup.min.css', [], '1.1.0', 'all');
	wp_enqueue_script('magnific-popup-js', get_stylesheet_directory_uri() . '/assets/jquery.magnific-popup.min.js', ['jquery'], '1.1.0', true);

	// Main script
	wp_enqueue_script('mrdemonwolf-script', get_stylesheet_directory_uri() . '/script.js', ['jquery', 'magnific-popup-js'], '1.0.0', true);
}
add_action( 'wp_enqueue_scripts', 'mrdemonwolf_enqueue_styles' );

//Deleting the Wordpress version number
function mrdemonwolf_delete_version() {
  return '';
}
add_filter('the_generator', 'mrdemonwolf_delete_version');

//Hide adminsitration login errors
function mrdemonwolf_hide_login_errors() {
	return __('The username or password is incorrect', 'mrdemonwolf');
}
add_filter('login_errors', 'mrdemonwolf_hide_login_errors');

// Allow SVG in WordPress Importer
function mrdemonwolf_allow_svg_in_importer( $data, $file, $filename, $mimes ) {

    if ( strpos( $filename, '.svg' ) !== false ) {
        $data['ext']  = 'svg';
        $data['type'] = 'image/svg+xml';
    }

    return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'mrdemonwolf_allow_svg_in_importer', 10, 4 );

// Allow SVG mime (restricted to admins)
function mrdemonwolf_allow_svg_mime( $mimes ) {
    if (!current_user_can('manage_options')) {
        return $mimes;
    }
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'mrdemonwolf_allow_svg_mime' );

// Disable year/month uploads folders
function mrdemonwolf_disable_year_month_uploads() {
    update_option( 'uploads_use_yearmonth_folders', 0 );
}
add_action( 'after_switch_theme', 'mrdemonwolf_disable_year_month_uploads' );

// ===============================
// Theme Cleanup on Switch
// ===============================
function mrdemonwolf_on_theme_switch() {
	$mu_dir  = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : ( ABSPATH . 'wp-content/mu-plugins' );
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
	$self = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : ( ABSPATH . 'wp-content/mu-plugins' );
	$self .= '/mdw-cleanup-notice.php';
	if ( file_exists( $self ) ) {
		unlink( $self );
	}

	wp_send_json_success();
}
PHP;

	file_put_contents( $mu_file, $mu_code );
}
add_action( 'switch_theme', 'mrdemonwolf_on_theme_switch' );

// ===============================
// Register "Service" Custom Post Type
// ===============================
function mrdemonwolf_register_service_cpt() {

    $labels = array(
        'name'               => __('Services', 'mrdemonwolf'),
        'singular_name'      => __('Service', 'mrdemonwolf'),
        'menu_name'          => __('Services', 'mrdemonwolf'),
        'add_new_item'       => __('Add New Service', 'mrdemonwolf'),
        'edit_item'          => __('Edit Service', 'mrdemonwolf'),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
        'menu_icon'          => 'dashicons-image-filter',
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'services'),
        'show_in_rest'       => true,
    );

    register_post_type('service', $args);
}
add_action('init', 'mrdemonwolf_register_service_cpt');

// Add custom image field
function mrdemonwolf_service_add_metabox() {
    add_meta_box(
        'mrdemonwolf_service_custom_image',
        __('Icon', 'mrdemonwolf'),
        'mrdemonwolf_service_custom_image_callback',
        'service',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'mrdemonwolf_service_add_metabox');

function mrdemonwolf_service_custom_image_callback($post) {

    wp_nonce_field('mrdemonwolf_service_image_nonce', 'mrdemonwolf_service_image_nonce_field');

    $image_url = get_post_meta($post->ID, '_mrdemonwolf_service_image', true);

    ?>
    <div>
        <img id="mdw-service-image-preview"
             src="<?php echo esc_url($image_url); ?>"
             style="max-width:100%;<?php echo $image_url ? '' : 'display:none;'; ?>" />

        <input type="hidden" id="mdw-service-image" name="mrdemonwolf_service_image" value="<?php echo esc_attr($image_url); ?>">

        <button type="button" class="button" id="mdw-service-upload-btn"><?php esc_html_e('Select Image', 'mrdemonwolf'); ?></button>
        <button type="button" class="button" id="mdw-service-remove-btn" style="<?php echo $image_url ? '' : 'display:none;'; ?>"><?php esc_html_e('Remove', 'mrdemonwolf'); ?></button>
    </div>

    <script>
    jQuery(function($){
        var frame;

        $('#mdw-service-upload-btn').on('click', function(e){
            e.preventDefault();

            if(frame){ frame.open(); return; }

            frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use this image' },
                multiple: false
            });

            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#mdw-service-image').val(attachment.url);
                $('#mdw-service-image-preview').attr('src', attachment.url).show();
                $('#mdw-service-remove-btn').show();
            });

            frame.open();
        });

        $('#mdw-service-remove-btn').on('click', function(){
            $('#mdw-service-image').val('');
            $('#mdw-service-image-preview').hide();
            $(this).hide();
        });
    });
    </script>
    <?php
}

// Save the field as URL
function mrdemonwolf_service_save_image($post_id) {
	if (
    ! isset( $_POST['mrdemonwolf_service_image_nonce_field'] )
    || ! wp_verify_nonce(
        sanitize_text_field( wp_unslash( $_POST['mrdemonwolf_service_image_nonce_field'] ) ),
        'mrdemonwolf_service_image_nonce'
    )
) {
    return;
}
    if (!current_user_can('edit_post', $post_id)) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if(isset($_POST['mrdemonwolf_service_image'])){
        update_post_meta($post_id, '_mrdemonwolf_service_image', esc_url_raw($_POST['mrdemonwolf_service_image']));
    }
}
add_action('save_post_service', 'mrdemonwolf_service_save_image');

// ===============================
// Breadcrumbs Shortcode
// ===============================
function mrdemonwolf_breadcrumbs_shortcode($atts) {
	if (is_admin() && !wp_doing_ajax()) {
		return '';
	}

	global $post;

	if (!$post) {
		return '';
	}

	$atts = shortcode_atts(['home' => 'Home'], $atts, 'mrdemonwolf_breadcrumbs');
	$breadcrumb = '<nav class="mdw-breadcrumbs" aria-label="Breadcrumb">';
	$breadcrumb .= '<a href="' . esc_url(home_url('/')) . '">' . esc_html($atts['home']) . '</a>';

	if (function_exists('is_woocommerce') && is_woocommerce()) {
		if (is_singular('product')) {
			$terms = get_the_terms($post->ID, 'product_cat');
			if (!empty($terms) && !is_wp_error($terms)) {
				$term = reset($terms);
				$breadcrumb .= ' <span class="mdw-separator"> > </span>';
				$breadcrumb .= '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
			}
			$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html(get_the_title()) . '</span>';

		} elseif (is_tax('product_cat')) {
			$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html( single_term_title('', false) ) . '</span>';
		} elseif (is_shop()) {
			$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html(get_the_title(wc_get_page_id('shop'))) . '</span>';
		}
	} elseif (is_single() && 'post' === get_post_type()) {
		$categories = get_the_category($post->ID);
		if (!empty($categories)) {
			$breadcrumb .= ' <span class="mdw-separator"></span><a href="' . esc_url(get_category_link($categories[0]->term_id)) . '">' . esc_html($categories[0]->name) . '</a>';
		}
		$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html(get_the_title()) . '</span>';

	} elseif (is_single() && 'project' === get_post_type()) {
		$terms = get_the_terms($post->ID, 'project_category');
		if (!empty($terms) && !is_wp_error($terms)) {
			$term = reset($terms);
			$breadcrumb .= ' <span class="mdw-separator"></span>';
			$breadcrumb .= '<a href="' . esc_url(get_term_link($term->term_id, 'project_category')) . '">' . esc_html($term->name) . '</a>';
		}
		$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html(get_the_title()) . '</span>';
	} elseif (is_page()) {
		$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html(get_the_title()) . '</span>';

	} elseif (is_category()) {
		$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html( single_cat_title('', false) ) . '</span>';
	} else {
		$breadcrumb .= ' <span class="mdw-separator"></span><span>' . esc_html( preg_replace('/^.*?:\s*/', '', get_the_archive_title()) ) . '</span>';
	}

	return $breadcrumb . '</nav>';
}
add_shortcode('mrdemonwolf_breadcrumbs', 'mrdemonwolf_breadcrumbs_shortcode');


// ===============================
// Current Post Tags Shortcode
// ===============================
function mrdemonwolf_tags_shortcode() {
	$post_id = get_the_ID();
	if (!$post_id) return '';

	$post_type = get_post_type($post_id);

	// If it's a "project", use its custom taxonomy
	if ($post_type === 'project') {
		$tags = get_the_terms($post_id, 'project_tag');
	} else {
		$tags = get_the_tags($post_id);
	}

	if (empty($tags) || is_wp_error($tags)) return '';

	$html = '';
	foreach ($tags as $tag) {
		$html .= sprintf(
			'<a class="mdw-tags" href="%s">%s</a>',
			esc_url(get_term_link($tag->term_id)),
			esc_html($tag->name)
		);
	}

	return $html;
}
add_shortcode('mrdemonwolf_tags', 'mrdemonwolf_tags_shortcode');

// ===============================
// Social Share Shortcode
// ===============================
add_shortcode('mrdemonwolf_social_share', function() {
    $url   = urlencode(get_permalink());
    $title = urlencode(get_the_title());
	$buttons = "";

    // Facebook
    $buttons .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $url . '" target="_blank" rel="noopener">&#xe093;</a>';
    // Twitter / X
    $buttons .= '<a href="https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title . '" target="_blank" rel="noopener">&#xe094;</a>';
    // LinkedIn
    $buttons .= '<a href="https://www.linkedin.com/sharing/share-offsite/?url=' . $url . '" target="_blank" rel="noopener">&#xe09d;</a>';

    return $buttons;
});
