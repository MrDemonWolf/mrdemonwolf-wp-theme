<?php
/**
 * Plugin Name: MrDemonWolf Theme Customizer
 * Description: Live-preview and apply brand color changes to the MrDemonWolf Divi child theme.
 * Version:     1.0.0
 * Author:      MrDemonWolf, Inc.
 * Author URI:  https://www.mrdemonwolf.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mdw-color-preview
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDW_Color_Preview {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_panel' ) );
		add_action( 'wp_ajax_mdw_cp_apply_colors', array( $this, 'ajax_apply_colors' ) );
		register_deactivation_hook( __FILE__, array( 'MDW_Color_Preview', 'deactivate' ) );
	}

	/**
	 * Clean up plugin data on deactivation.
	 */
	public static function deactivate() {
		delete_option( 'mdw_cp_current_colors' );
	}

	/**
	 * Color definitions: all 11 brand colors.
	 */
	public function get_color_definitions() {
		return array(
			'primary'   => array(
				'label'    => 'Primary',
				'default'  => '#1e8a8a',
				'css_var'  => '--gcid-primary-color',
				'source'   => null,
			),
			'secondary' => array(
				'label'    => 'Secondary',
				'default'  => '#0c1e21',
				'css_var'  => '--gcid-secondary-color',
				'source'   => null,
			),
			'background' => array(
				'label'    => 'Background',
				'default'  => '#d8e5e5',
				'css_var'  => '--gcid-qn8h12q0c7',
				'source'   => 'primary',
				'role'     => 'bg',
			),
			'light_bg'  => array(
				'label'    => 'Light BG',
				'default'  => '#ecf0f0',
				'css_var'  => '--gcid-xsweq3oku6',
				'source'   => 'primary',
				'role'     => 'bg',
			),
			'text2'     => array(
				'label'    => 'Text 2',
				'default'  => '#a9b8b8',
				'css_var'  => '--gcid-0ny19batqe',
				'source'   => 'primary',
				'role'     => 'text',
			),
			'extra1'    => array(
				'label'    => 'Extra 1 (borders)',
				'default'  => '#c9d1d1',
				'css_var'  => null,
				'source'   => 'primary',
				'role'     => 'accent',
			),
			'extra4'    => array(
				'label'    => 'Extra 4 (light accents)',
				'default'  => '#e9eded',
				'css_var'  => null,
				'source'   => 'primary',
				'role'     => 'bg',
			),
			'body_text' => array(
				'label'    => 'Body Text',
				'default'  => '#364e52',
				'css_var'  => '--gcid-body-color',
				'source'   => 'secondary',
				'role'     => 'text',
			),
			'dark2'     => array(
				'label'    => 'Dark 2',
				'default'  => '#18292c',
				'css_var'  => '--gcid-hhvnnvrog9',
				'source'   => 'secondary',
				'role'     => 'bg',
			),
			'extra2'    => array(
				'label'    => 'Extra 2 (accents)',
				'default'  => '#67787a',
				'css_var'  => null,
				'source'   => 'secondary',
				'role'     => 'accent',
			),
			'extra3'    => array(
				'label'    => 'Extra 3 (dark layout)',
				'default'  => '#313d3d',
				'css_var'  => null,
				'source'   => 'secondary',
				'role'     => 'bg',
			),
		);
	}

	/**
	 * Selector map: hardcoded colors that need live-preview overrides.
	 */
	public function get_selector_map() {
		return array(
			'light_bg' => array(
				array(
					'selector' => 'body',
					'property' => 'background',
				),
				array(
					'selector' => '.mdw-header .et_pb_menu .et_pb_menu__icon',
					'property' => 'background',
				),
				array(
					'selector' => '.mdw-box-border:before, .mdw-box-border:after, .mdw-box-border-right:before, .mdw-box-border-right:after',
					'property' => 'background',
					'type'     => 'svg_data_uri',
				),
			),
			'extra1' => array(
				array(
					'selector' => '.mdw-search .et_pb_searchsubmit',
					'property' => 'border-left-color',
				),
				array(
					'selector' => '.mdw-accordion .et_pb_toggle_content',
					'property' => 'border-top-color',
				),
				array(
					'selector' => '.mdw-portfolio .post-meta a',
					'property' => 'border-color',
				),
				array(
					'selector' => '.mdw-timeline-column-date:before',
					'property' => 'border-left-color',
				),
				array(
					'selector' => '.mdw-timeline-row:before',
					'property' => 'border-left-color',
				),
				array(
					'selector' => '.mdw-blog-loop span.mdw-blog-cat',
					'property' => 'border-color',
				),
				array(
					'selector' => 'body #main-content .wp-pagenavi a, body #main-content .wp-pagenavi span.current',
					'property' => 'border-color',
				),
				array(
					'selector' => '.mdw-pagination .meta-nav:before',
					'property' => 'border-color',
				),
			),
			'text2' => array(
				array(
					'selector' => '.mdw-person .et_pb_team_member_image',
					'property' => 'background',
				),
			),
			'extra2' => array(
				array(
					'selector' => '.mdw-timeline-column-date:after',
					'property' => 'border-color',
				),
				array(
					'selector' => '.mdw-timeline-row:after',
					'property' => 'border-color',
				),
			),
			'primary_rgba' => array(
				array(
					'selector' => '.mdw-card-1 .et_pb_image',
					'property' => 'background',
					'type'     => 'gradient',
				),
				array(
					'selector' => '.mdw-card-1 .et_pb_image',
					'property' => 'border-color',
					'type'     => 'rgba_015',
				),
				array(
					'selector' => '.mdw-service-icon',
					'property' => 'background',
					'type'     => 'gradient',
				),
				array(
					'selector' => '.mdw-service-icon',
					'property' => 'border-color',
					'type'     => 'rgba_015',
				),
			),
			'body_text' => array(
				array(
					'selector' => '.mdw-footer-row .et_pb_text_inner, .mdw-footer-row .et_pb_text_inner p, .mdw-footer-row .et_pb_text_inner a, .mdw-footer-row .et_pb_text_inner li',
					'property' => 'color',
				),
			),
			'secondary' => array(
				array(
					'selector' => '.mdw-footer-row h1, .mdw-footer-row h2, .mdw-footer-row h3, .mdw-footer-row h4, .mdw-footer-row h5, .mdw-footer-row h6',
					'property' => 'color',
				),
			),
		);
	}

	/**
	 * Enqueue assets only for admins on the frontend.
	 */
	public function enqueue_assets() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$plugin_url = plugin_dir_url( __FILE__ );
		$version    = '1.0.0';

		wp_enqueue_style(
			'mdw-color-preview',
			$plugin_url . 'assets/color-preview.css',
			array(),
			$version
		);

		wp_enqueue_script(
			'mdw-color-preview',
			$plugin_url . 'assets/color-preview.js',
			array(),
			$version,
			true
		);

		$current_colors = get_option( 'mdw_cp_current_colors', array() );

		wp_localize_script( 'mdw-color-preview', 'mdwColorPreview', array(
			'colors'      => $this->get_color_definitions(),
			'selectorMap' => $this->get_selector_map(),
			'current'     => $current_colors,
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'mdw_cp_apply' ),
		) );
	}

	/**
	 * Render the color preview panel HTML in the footer.
	 */
	public function render_panel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$colors = $this->get_color_definitions();
		$current = get_option( 'mdw_cp_current_colors', array() );

		$base_colors = array();
		$derived_colors = array();
		foreach ( $colors as $key => $color ) {
			$value = isset( $current[ $key ] ) ? $current[ $key ] : $color['default'];
			$color['key'] = $key;
			$color['value'] = $value;
			if ( null === $color['source'] ) {
				$base_colors[] = $color;
			} else {
				$derived_colors[] = $color;
			}
		}

		?>
		<div id="mdw-cp-panel" class="mdw-cp-panel mdw-cp-closed">
			<button id="mdw-cp-toggle" class="mdw-cp-toggle" aria-label="<?php echo esc_attr__( 'Toggle color preview panel', 'mdw-color-preview' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M12 2C6.49 2 2 6.49 2 12s4.49 10 10 10c1.38 0 2.5-1.12 2.5-2.5 0-.61-.23-1.2-.64-1.67-.08-.1-.13-.21-.13-.33 0-.28.22-.5.5-.5H16c3.31 0 6-2.69 6-6 0-4.96-4.49-9-10-9zM6.5 13c-.83 0-1.5-.67-1.5-1.5S5.67 10 6.5 10s1.5.67 1.5 1.5S7.33 13 6.5 13zm3-4C8.67 9 8 8.33 8 7.5S8.67 6 9.5 6s1.5.67 1.5 1.5S10.33 9 9.5 9zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 6 14.5 6s1.5.67 1.5 1.5S15.33 9 14.5 9zm3 4c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" fill="currentColor"/>
				</svg>
			</button>

			<div class="mdw-cp-content">
				<div class="mdw-cp-header">
					<h3><?php echo esc_html__( 'Color Preview', 'mdw-color-preview' ); ?></h3>
					<span class="mdw-cp-badge"><?php echo esc_html__( 'Admin Only', 'mdw-color-preview' ); ?></span>
				</div>

				<div class="mdw-cp-mode-toggle">
					<span class="mdw-cp-mode-label"><?php echo esc_html__( 'Derivation', 'mdw-color-preview' ); ?></span>
					<div class="mdw-cp-mode-buttons">
						<button class="mdw-cp-mode-btn mdw-cp-mode-active" data-mode="delta"><?php echo esc_html__( 'Delta', 'mdw-color-preview' ); ?></button>
						<button class="mdw-cp-mode-btn" data-mode="relative"><?php echo esc_html__( 'Relative', 'mdw-color-preview' ); ?></button>
						<button class="mdw-cp-mode-btn" data-mode="dark"><?php echo esc_html__( 'Dark', 'mdw-color-preview' ); ?></button>
					</div>
				</div>

				<div class="mdw-cp-scroll">
					<div class="mdw-cp-section">
						<h4><?php echo esc_html__( 'Base Colors', 'mdw-color-preview' ); ?></h4>
						<?php foreach ( $base_colors as $i => $color ) : ?>
						<div class="mdw-cp-row" data-key="<?php echo esc_attr( $color['key'] ); ?>">
							<div class="mdw-cp-row-top">
								<label><?php echo esc_html( $color['label'] ); ?></label>
								<span class="mdw-cp-contrast-ratio"></span>
								<span class="mdw-cp-badge-type">source</span>
							</div>
							<div class="mdw-cp-row-controls">
								<input type="color"
									class="mdw-cp-picker"
									data-key="<?php echo esc_attr( $color['key'] ); ?>"
									value="<?php echo esc_attr( $color['value'] ); ?>">
								<input type="text"
									class="mdw-cp-hex"
									data-key="<?php echo esc_attr( $color['key'] ); ?>"
									value="<?php echo esc_attr( $color['value'] ); ?>"
									maxlength="7"
									pattern="#[0-9a-fA-F]{6}">
							</div>
						</div>
						<?php if ( 0 === $i ) : ?>
						<div class="mdw-cp-swap-row">
							<button id="mdw-cp-swap" class="mdw-cp-swap" title="<?php echo esc_attr__( 'Swap primary and secondary', 'mdw-color-preview' ); ?>">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M16 17.01V10h-2v7.01h-3L15 21l4-3.99h-3zM9 3L5 6.99h3V14h2V6.99h3L9 3z" fill="currentColor"/>
								</svg>
							</button>
						</div>
						<?php endif; ?>
						<?php endforeach; ?>
					</div>

					<div class="mdw-cp-section">
						<h4><?php echo esc_html__( 'Generated Colors', 'mdw-color-preview' ); ?></h4>
						<?php foreach ( $derived_colors as $color ) : ?>
						<div class="mdw-cp-row" data-key="<?php echo esc_attr( $color['key'] ); ?>">
							<div class="mdw-cp-row-top">
								<label><?php echo esc_html( $color['label'] ); ?></label>
								<span class="mdw-cp-contrast-ratio"></span>
								<span class="mdw-cp-badge-type mdw-cp-auto">auto</span>
							</div>
							<div class="mdw-cp-row-controls">
								<input type="color"
									class="mdw-cp-picker"
									data-key="<?php echo esc_attr( $color['key'] ); ?>"
									value="<?php echo esc_attr( $color['value'] ); ?>">
								<input type="text"
									class="mdw-cp-hex"
									data-key="<?php echo esc_attr( $color['key'] ); ?>"
									value="<?php echo esc_attr( $color['value'] ); ?>"
									maxlength="7"
									pattern="#[0-9a-fA-F]{6}">
								<button class="mdw-cp-lock" data-key="<?php echo esc_attr( $color['key'] ); ?>" title="<?php echo esc_attr__( 'Toggle auto/manual', 'mdw-color-preview' ); ?>">
									<svg class="mdw-cp-icon-unlock" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" fill="currentColor"/></svg>
									<svg class="mdw-cp-icon-lock" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6h2c0-1.66 1.34-3 3-3s3 1.34 3 3v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm0 12H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" fill="currentColor"/></svg>
								</button>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="mdw-cp-actions">
					<button id="mdw-cp-reset" class="mdw-cp-btn mdw-cp-btn-secondary"><?php echo esc_html__( 'Reset', 'mdw-color-preview' ); ?></button>
					<button id="mdw-cp-export" class="mdw-cp-btn mdw-cp-btn-secondary"><?php echo esc_html__( 'Export', 'mdw-color-preview' ); ?></button>
					<button id="mdw-cp-autofix" class="mdw-cp-btn mdw-cp-btn-warning"><?php echo esc_html__( 'Auto-fix Contrast', 'mdw-color-preview' ); ?></button>
					<button id="mdw-cp-apply" class="mdw-cp-btn mdw-cp-btn-primary"><?php echo esc_html__( 'Apply to Theme', 'mdw-color-preview' ); ?></button>
				</div>
			</div>
		</div>

		<div id="mdw-cp-export-modal" class="mdw-cp-modal" style="display:none;">
			<div class="mdw-cp-modal-content">
				<div class="mdw-cp-modal-header">
					<h4><?php echo esc_html__( 'Export Colors', 'mdw-color-preview' ); ?></h4>
					<button id="mdw-cp-modal-close" class="mdw-cp-modal-close">&times;</button>
				</div>
				<textarea id="mdw-cp-export-text" class="mdw-cp-export-text" readonly></textarea>
				<button id="mdw-cp-copy" class="mdw-cp-btn mdw-cp-btn-primary"><?php echo esc_html__( 'Copy to Clipboard', 'mdw-color-preview' ); ?></button>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler: apply colors to theme/style.css.
	 */
	public function ajax_apply_colors() {
		check_ajax_referer( 'mdw_cp_apply', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'Insufficient permissions.', 'mdw-color-preview' ) );
		}

		$new_colors = array();
		$definitions = $this->get_color_definitions();

		foreach ( $definitions as $key => $def ) {
			if ( isset( $_POST[ $key ] ) ) {
				$hex = sanitize_hex_color( wp_unslash( $_POST[ $key ] ) );
				if ( $hex ) {
					$new_colors[ $key ] = $hex;
				}
			}
		}

		if ( empty( $new_colors ) ) {
			wp_send_json_error( esc_html__( 'No valid colors provided.', 'mdw-color-preview' ) );
		}

		$style_path = get_stylesheet_directory() . '/style.css';

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! file_exists( $style_path ) || ! $wp_filesystem->is_writable( $style_path ) ) {
			wp_send_json_error( esc_html__( 'style.css is not writable.', 'mdw-color-preview' ) );
		}

		$css = $wp_filesystem->get_contents( $style_path );
		if ( false === $css ) {
			wp_send_json_error( esc_html__( 'Could not read style.css.', 'mdw-color-preview' ) );
		}

		// Determine "old" colors: either from saved option or defaults.
		$old_colors = get_option( 'mdw_cp_current_colors', array() );

		foreach ( $definitions as $key => $def ) {
			if ( ! isset( $new_colors[ $key ] ) ) {
				continue;
			}

			$old_hex = isset( $old_colors[ $key ] ) ? $old_colors[ $key ] : $def['default'];
			$new_hex = $new_colors[ $key ];

			if ( strtolower( $old_hex ) === strtolower( $new_hex ) ) {
				continue;
			}

			// Replace hardcoded hex values (case-insensitive).
			$css = str_ireplace( $old_hex, $new_hex, $css );

			// Replace URL-encoded hex in SVG data URIs (%23xxxxxx).
			$old_encoded = '%23' . ltrim( $old_hex, '#' );
			$new_encoded = '%23' . ltrim( $new_hex, '#' );
			$css = str_ireplace( $old_encoded, $new_encoded, $css );

			// Replace RGBA values derived from primary color.
			if ( 'primary' === $key ) {
				$old_rgb = $this->hex_to_rgb( $old_hex );
				$new_rgb = $this->hex_to_rgb( $new_hex );

				if ( $old_rgb && $new_rgb ) {
					$css = preg_replace(
						'/rgba\(\s*' . $old_rgb['r'] . '\s*,\s*' . $old_rgb['g'] . '\s*,\s*' . $old_rgb['b'] . '\s*,/i',
						'rgba(' . $new_rgb['r'] . ', ' . $new_rgb['g'] . ', ' . $new_rgb['b'] . ',',
						$css
					);
				}
			}
		}

		$result = $wp_filesystem->put_contents( $style_path, $css, FS_CHMOD_FILE );
		if ( false === $result ) {
			wp_send_json_error( esc_html__( 'Could not write style.css.', 'mdw-color-preview' ) );
		}

		update_option( 'mdw_cp_current_colors', $new_colors );

		wp_send_json_success( esc_html__( 'Colors applied successfully.', 'mdw-color-preview' ) );
	}

	/**
	 * Convert hex to RGB array.
	 */
	private function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) !== 6 ) {
			return null;
		}
		return array(
			'r' => hexdec( substr( $hex, 0, 2 ) ),
			'g' => hexdec( substr( $hex, 2, 2 ) ),
			'b' => hexdec( substr( $hex, 4, 2 ) ),
		);
	}
}

MDW_Color_Preview::instance();
