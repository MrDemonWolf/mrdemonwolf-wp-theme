<?php
// Apply the Theme Options + Customizer exports the way Divi's own portability
// importer does: epanel and et_divi_mods both land in the `et_divi` option,
// and the global colour map lands in `et_global_colors`.
$base = getenv( 'MDW_SUPP' );

function mdw_load( $file ) {
    $raw = file_get_contents( $file );
    if ( false === $raw ) {
        fwrite( STDERR, "cannot read $file\n" );
        exit( 1 );
    }
    return json_decode( $raw, true );
}

$et_divi = get_option( 'et_divi', [] );
if ( ! is_array( $et_divi ) ) {
    $et_divi = [];
}

// 1. Theme Options (epanel).
$opts = mdw_load( "$base/MrDemonWolf Divi Theme Options.json" );
$et_divi = array_merge( $et_divi, $opts['data'] );
echo '  theme options keys applied: ' . count( $opts['data'] ) . "\n";

// 2. Customizer settings (et_divi_mods). Applied second so it wins on overlap,
//    matching the documented import order.
$cust = mdw_load( "$base/MrDemonWolf Divi Theme Customizer Settings.json" );
$data = $cust['data'];

$globals = [];
if ( isset( $data['et_global_data']['global_colors'] ) ) {
    $globals = $data['et_global_data']['global_colors'];
}
unset( $data['et_global_data'] );

$et_divi = array_merge( $et_divi, $data );
echo '  customizer keys applied:    ' . count( $data ) . "\n";

// Divi stores its options in a single row, so et_get_option( 'et_global_colors' )
// reads et_divi['et_global_colors'] -- writing a standalone et_global_colors
// option looks right but Divi never reads it, and every gcid-* reference in the
// layouts silently falls back to a default colour.
if ( $globals ) {
    $et_divi['et_global_colors'] = $globals;
    echo '  global colors applied:      ' . count( $globals ) . "\n";
}

update_option( 'et_divi', $et_divi );
delete_option( 'et_global_colors' );

// Divi caches generated CSS keyed off these values.
delete_option( 'et_divi_dynamic_css_cache' );
if ( function_exists( 'et_core_clear_transients' ) ) {
    et_core_clear_transients();
}

echo "  accent_color now: " . ( get_option( 'et_divi' )['accent_color'] ?? '(unset)' ) . "\n";
