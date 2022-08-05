<?php

if ( ! function_exists( 'use_block_editor_for_post_type' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/post.php' );
}

add_action(
	'rest_api_init',
	function () {
		$post_types = get_post_types_by_support( [ 'editor' ] );
		foreach ( $post_types as $post_type ) {
			if ( use_block_editor_for_post_type( $post_type ) ) {
				register_rest_field(
					$post_type,
					'blocks',
					[
						'get_callback' => function ( array $post ) {
							return parse_blocks( $post['content']['raw'] );
						},
						'schema' => null,
					]
				);
			}
		}
	}
);