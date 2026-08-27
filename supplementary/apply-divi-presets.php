<?php
/**
 * Import the Divi module presets carried in the Theme Builder Templates export.
 *
 * The presets are what attach the design classes to modules -- mdw-blurb-1,
 * mdw-btn-1, mdw-btn-3, mdw-testimonial-1 and so on. Without them the modules
 * render with no class, so the stylesheet has nothing to hook onto and cards
 * lose their padding, buttons lose their icon, and it reads as broken CSS
 * rather than as missing data.
 *
 * Divi's own importer for this is a chunked AJAX flow behind a nonce, which is
 * not reachable from WP-CLI, so this calls the underlying portability method
 * directly as the administrator.
 */
if ( ! class_exists( 'ET_Core_Portability' ) ) {
    fwrite( STDERR, "ET_Core_Portability is unavailable; is Divi active?\n" );
    exit( 1 );
}

$base = getenv( 'MDW_SUPP' );
$file = "$base/MrDemonWolf Divi Theme Builder Templates.json";

if ( ! is_readable( $file ) ) {
    fwrite( STDERR, "Cannot read $file\n" );
    exit( 1 );
}

$export  = json_decode( file_get_contents( $file ), true );
$presets = $export['presets'] ?? [];

if ( empty( $presets ) ) {
    echo "  no presets in the export, nothing to do\n";
    return;
}

wp_set_current_user( 1 );

$portability = new ET_Core_Portability( 'et_theme_builder' );
$portability->import_global_presets( $presets );

// GlobalPreset owns the storage; the raw builder_global_presets* options are
// not the authority in Divi 5 and read empty even on a successful import.
$gp = '\\ET\\Builder\\Packages\\GlobalData\\GlobalPreset';
if ( class_exists( $gp ) ) {
    $data = $gp::get_data();
    $modules = $data['module']['items'] ?? ( $data['module'] ?? [] );
    printf( "  module presets stored: %d\n", is_array( $modules ) ? count( $modules ) : 0 );
    foreach ( (array) $modules as $type => $entry ) {
        $items = $entry['items'] ?? $entry['presets'] ?? [];
        printf( "    %-22s %d\n", $type, is_array( $items ) ? count( $items ) : 0 );
    }
} else {
    echo "  GlobalPreset class not found\n";
}

// Global colours travel in this export too and are cheap to reapply here.
if ( ! empty( $export['global_colors'] ) ) {
    $portability->import_global_colors( $export['global_colors'] );
    echo "  global colors reapplied:     " . count( $export['global_colors'] ) . "\n";
}
