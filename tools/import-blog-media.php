<?php
/**
 * Sideload the blog images that live in the website repo and wire them up.
 *
 * Every post's images were downloaded once into
 *   apps/website/public/media/blog/<slug>/
 * with the featured image named *-post-featured.*. Production blocks
 * foreign-referer requests, so re-fetching them is unreliable; this reads the
 * local copies.
 *
 * Blog media deliberately does not live in this theme repo -- it is site
 * content, not theme content.
 *
 * This runs as a single PHP process on purpose. The shell version forked two
 * WP-CLI invocations per file, which is ~300 PHP bootstraps and gets killed
 * before it finishes.
 *
 *   MEDIA_ROOT=... wp eval-file tools/import-blog-media.php
 */
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$root = getenv( 'MEDIA_ROOT' );
if ( ! $root ) {
    $root = getenv( 'HOME' ) . '/Developer/mrdemonwolf/website/apps/website/public/media/blog';
}

if ( ! is_dir( $root ) ) {
    fwrite( STDERR, "Blog media not found at $root\n" );
    exit( 1 );
}

// SVG uploads and some mime checks are gated on capabilities.
$admin = get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] );
wp_set_current_user( $admin ? (int) $admin[0] : 1 );

const SOURCE_META = '_mrdemonwolf_source_file';

/** Find an attachment previously imported from this relative source path. */
function mdw_existing_attachment( $rel ) {
    global $wpdb;
    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
              WHERE meta_key = %s AND meta_value = %s LIMIT 1",
            SOURCE_META,
            $rel
        )
    );
}

$posts_matched = 0;
$imported      = 0;
$reused        = 0;
$featured      = 0;
$orphan_slugs  = [];

foreach ( glob( "$root/*", GLOB_ONLYDIR ) as $dir ) {
    $slug = basename( $dir );

    $post = get_posts(
        [
            'post_type'   => 'post',
            'name'        => $slug,
            'numberposts' => 1,
            'post_status' => 'any',
        ]
    );

    if ( ! $post ) {
        $orphan_slugs[] = $slug;
        continue;
    }

    $post_id = $post[0]->ID;
    $posts_matched++;

    foreach ( glob( "$dir/*" ) as $file ) {
        if ( ! is_file( $file ) || '.' === basename( $file )[0] ) {
            continue;
        }

        $rel = $slug . '/' . basename( $file );
        $id  = mdw_existing_attachment( $rel );

        if ( $id ) {
            $reused++;
        } else {
            // media_handle_sideload moves the file, so hand it a copy.
            $tmp = wp_tempnam( basename( $file ) );
            copy( $file, $tmp );

            $id = media_handle_sideload(
                [
                    'name'     => basename( $file ),
                    'tmp_name' => $tmp,
                ],
                $post_id
            );

            if ( is_wp_error( $id ) ) {
                @unlink( $tmp );
                fwrite( STDERR, "  failed $rel: " . $id->get_error_message() . "\n" );
                continue;
            }

            update_post_meta( $id, SOURCE_META, $rel );
            $imported++;
        }

        if ( preg_match( '/-post-featured\.[a-z0-9]+$/i', basename( $file ) ) ) {
            update_post_meta( $post_id, '_thumbnail_id', $id );
            $featured++;
        }
    }
}

printf(
    "  posts matched %d, imported %d, reused %d, featured set %d\n",
    $posts_matched,
    $imported,
    $reused,
    $featured
);

if ( $orphan_slugs ) {
    printf( "  media dirs with no matching post (%d): %s\n", count( $orphan_slugs ), implode( ', ', array_slice( $orphan_slugs, 0, 8 ) ) );
}
