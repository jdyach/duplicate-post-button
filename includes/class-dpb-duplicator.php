<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Handles row action registration and post duplication logic.
 */
class DPB_Duplicator {

    public static function init(): void {
        add_filter( 'post_row_actions',  [ self::class, 'add_row_action' ], 10, 2 );
        add_filter( 'page_row_actions',  [ self::class, 'add_row_action' ], 10, 2 );
        add_action( 'admin_action_dpb_duplicate', [ self::class, 'handle_duplicate' ] );
        add_action( 'admin_notices', [ self::class, 'maybe_show_notice' ] );
    }

    // -------------------------------------------------------------------------
    // Row action link
    // -------------------------------------------------------------------------

    /**
     * Append a "Duplicate" link to the post/page row actions.
     *
     * @param array<string,string> $actions
     * @param WP_Post              $post
     * @return array<string,string>
     */
    public static function add_row_action( array $actions, WP_Post $post ): array {
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return $actions;
        }

        $url = wp_nonce_url(
            add_query_arg(
                [
                    'action'  => 'dpb_duplicate',
                    'post_id' => $post->ID,
                ],
                admin_url( 'admin.php' )
            ),
            'dpb_duplicate_' . $post->ID
        );

        $actions['duplicate'] = sprintf(
            '<a href="%s" aria-label="%s">%s</a>',
            esc_url( $url ),
            esc_attr( sprintf( __( 'Duplicate &#8220;%s&#8221;', 'duplicate-post-button' ), get_the_title( $post ) ) ),
            __( 'Duplicate', 'duplicate-post-button' )
        );

        return $actions;
    }

    // -------------------------------------------------------------------------
    // Duplication handler
    // -------------------------------------------------------------------------

    /**
     * Process the duplicate action triggered via GET request.
     */
    public static function handle_duplicate(): void {
        $post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_die( esc_html__( 'Invalid post ID.', 'duplicate-post-button' ) );
        }

        check_admin_referer( 'dpb_duplicate_' . $post_id );

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( esc_html__( 'You do not have permission to duplicate this post.', 'duplicate-post-button' ) );
        }

        $original = get_post( $post_id );

        if ( ! $original instanceof WP_Post ) {
            wp_die( esc_html__( 'Post not found.', 'duplicate-post-button' ) );
        }

        $new_id = self::duplicate_post( $original );

        if ( is_wp_error( $new_id ) ) {
            wp_die( esc_html( $new_id->get_error_message() ) );
        }

        // Redirect to the edit screen of the new draft.
        wp_safe_redirect(
            add_query_arg(
                [
                    'post'    => $new_id,
                    'action'  => 'edit',
                    'dpb_dup' => '1',
                ],
                admin_url( 'post.php' )
            )
        );
        exit;
    }

    // -------------------------------------------------------------------------
    // Core duplication logic
    // -------------------------------------------------------------------------

    /**
     * Clone a post with its meta and taxonomy terms.
     *
     * @return int|\WP_Error  New post ID on success, WP_Error on failure.
     */
    private static function duplicate_post( WP_Post $original ): int|\WP_Error {
        // Build new post array — always a draft, authored by the current user.
        $new_post = [
            'post_title'     => sprintf(
                /* translators: %s: original post title */
                __( '%s (Copy)', 'duplicate-post-button' ),
                $original->post_title
            ),
            'post_content'   => $original->post_content,
            'post_excerpt'   => $original->post_excerpt,
            'post_status'    => 'draft',
            'post_type'      => $original->post_type,
            'post_author'    => get_current_user_id(),
            'post_parent'    => $original->post_parent,
            'menu_order'     => $original->menu_order,
            'comment_status' => $original->comment_status,
            'ping_status'    => $original->ping_status,
            'post_password'  => $original->post_password,
        ];

        $new_id = wp_insert_post( wp_slash( $new_post ), true );

        if ( is_wp_error( $new_id ) ) {
            return $new_id;
        }

        // Copy all post meta (skip internal WordPress revision keys).
        self::copy_post_meta( $original->ID, $new_id );

        // Copy all taxonomy terms.
        self::copy_taxonomy_terms( $original->ID, $new_id, $original->post_type );

        return $new_id;
    }

    /**
     * Copy post meta from one post to another, skipping internal keys.
     */
    private static function copy_post_meta( int $source_id, int $target_id ): void {
        $all_meta = get_post_meta( $source_id );

        if ( empty( $all_meta ) ) {
            return;
        }

        // Keys managed by WordPress core that should not be copied.
        $skip_keys = [
            '_edit_lock',
            '_edit_last',
            '_wp_old_slug',
            '_wp_old_date',
        ];

        foreach ( $all_meta as $meta_key => $meta_values ) {
            if ( in_array( $meta_key, $skip_keys, true ) ) {
                continue;
            }

            foreach ( $meta_values as $meta_value ) {
                add_post_meta( $target_id, $meta_key, maybe_unserialize( $meta_value ) );
            }
        }
    }

    /**
     * Copy all taxonomy terms from one post to another.
     */
    private static function copy_taxonomy_terms( int $source_id, int $target_id, string $post_type ): void {
        $taxonomies = get_object_taxonomies( $post_type, 'names' );

        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $source_id, $taxonomy, [ 'fields' => 'ids' ] );

            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                continue;
            }

            wp_set_object_terms( $target_id, $terms, $taxonomy );
        }
    }

    // -------------------------------------------------------------------------
    // Admin notice
    // -------------------------------------------------------------------------

    /**
     * Show a brief success notice after redirect if dpb_dup=1 is present.
     */
    public static function maybe_show_notice(): void {
        $screen = get_current_screen();

        if ( ! $screen || $screen->base !== 'post' ) {
            return;
        }

        if ( empty( $_GET['dpb_dup'] ) ) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>'
            . esc_html__( 'Post duplicated successfully. You are now editing the new draft.', 'duplicate-post-button' )
            . '</p></div>';
    }
}
