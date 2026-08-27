<?php
/**
 * Import the saved Divi Library layouts.
 *
 * These are the et_pb_layout posts the Theme Builder templates and pages pull
 * in as reusable blocks -- the partner logo slider, the sidebars, the header
 * block. They are not part of All Content.xml, so without this step the
 * sections that reference them render empty and their design classes never
 * appear.
 *
 * Divi's Library importer is an AJAX flow behind a nonce, so this inserts the
 * posts directly, preserving their IDs because the layouts are referenced by ID.
 */
$base = getenv( 'MDW_SUPP' );
$file = "$base/MrDemonWolf Divi Theme Builder Layouts.json";

if ( ! is_readable( $file ) ) {
    fwrite( STDERR, "Cannot read $file\n" );
    exit( 1 );
}

$export = json_decode( file_get_contents( $file ), true );
$data   = $export['data'] ?? [];

if ( ! $data ) {
    echo "  no layouts in the export\n";
    return;
}

wp_set_current_user( 1 );

$created = 0;
$skipped = 0;

foreach ( $data as $id => $post ) {
    $id = (int) $id;

    if ( get_post( $id ) ) {
        $skipped++;
        continue;
    }

    // import_id preserves the ID, which matters: templates and pages reference
    // these layouts by number, so a reassigned ID silently breaks the link.
    //
    // wp_slash is not optional. wp_insert_post runs wp_unslash over its input,
    // and Divi layout content is JSON full of \u003c and \u0022 escapes, so
    // passing it raw strips every backslash and the layout renders its own
    // markup as literal text -- "u003ch4u003eSearchu003c/h4u003e" on the page.
    $insert = wp_slash(
        [
            'import_id'      => $id,
            'post_title'     => $post['post_title'] ?? '',
            'post_name'      => $post['post_name'] ?? '',
            'post_content'   => $post['post_content'] ?? '',
            'post_excerpt'   => $post['post_excerpt'] ?? '',
            'post_status'    => $post['post_status'] ?? 'publish',
            'post_type'      => $post['post_type'] ?? 'et_pb_layout',
            'post_date'      => $post['post_date'] ?? '',
            'comment_status' => $post['comment_status'] ?? 'closed',
            'ping_status'    => $post['ping_status'] ?? 'closed',
            'menu_order'     => $post['menu_order'] ?? 0,
        ]
    );

    $new_id = wp_insert_post( $insert, true );

    if ( is_wp_error( $new_id ) ) {
        fwrite( STDERR, "  failed #$id: " . $new_id->get_error_message() . "\n" );
        continue;
    }

    // add_post_meta unslashes too, for the same reason.
    foreach ( (array) ( $post['post_meta'] ?? [] ) as $key => $values ) {
        foreach ( (array) $values as $value ) {
            add_post_meta( $new_id, $key, wp_slash( maybe_unserialize( $value ) ) );
        }
    }

    // Layout category/type terms drive which Library tab a layout appears in.
    foreach ( (array) ( $post['terms'] ?? [] ) as $term ) {
        $taxonomy = $term['taxonomy'] ?? '';
        $slug     = $term['slug'] ?? '';
        if ( ! $taxonomy || ! $slug || ! taxonomy_exists( $taxonomy ) ) {
            continue;
        }
        if ( ! term_exists( $slug, $taxonomy ) ) {
            wp_insert_term( $term['name'] ?? $slug, $taxonomy, [ 'slug' => $slug ] );
        }
        wp_set_object_terms( $new_id, $slug, $taxonomy, true );
    }

    $created++;
}

printf( "  layouts created: %d, already present: %d\n", $created, $skipped );

foreach ( get_posts( [ 'post_type' => 'et_pb_layout', 'numberposts' => -1, 'post_status' => 'any' ] ) as $l ) {
    printf( "    #%-7d %s\n", $l->ID, $l->post_title );
}
