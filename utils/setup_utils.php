<?php

class TstSetupUtils
{
    public static function get_post($post_id, $post_type = 'post')
    {
        global $wpdb;
        $post = null;
        if (preg_match('/^\d+$/', $post_id)) {
            $post = get_post($post_id, OBJECT);
        } else {
            $post_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s LIMIT 1 ",
                    $post_id,
                    $post_type
                )
            );
            if ($post_id) {
                $post = get_post($post_id, OBJECT);
            }
        }
        return $post;
    }

    public static function setup_posts_data($posts_data, $post_type = 'post')
    {
        $post_id_list = array();
        foreach ($posts_data as $post_data) {
            $post_id = self::setup_post_data($post_data, $post_type);
            $post_id_list[] = $post_id;
        }

        return $post_id_list;
    }

    public static function setup_post_data($post_data, $post_type = 'post')
    {
        global $wpdb;

        $post_data['post_type'] = $post_type;
        $post_data['post_status'] = 'publish';

        $post_name = empty($post_data['post_name']) ? sanitize_title($post_data['post_title']) : $post_data['post_name'];
        $exist_post = self::get_post($post_name, $post_type);

        if ($exist_post) {
            $post_data['ID'] = $exist_post->ID;
        }

        $post_id = wp_insert_post($post_data);

        if (!empty($post_data['post_content_raw'])) {
            $wpdb->update(
                $wpdb->posts,
                array(
                    'post_content' => $post_data['post_content_raw'],
                ),
                array('ID' => $post_id),
                array(
                    '%s',
                ),
                array('%d')
            );
        }

        if (!empty($post_data['meta'])) {
            foreach ($post_data['meta'] as $k => $v) {
                update_post_meta($post_id, $k, $v);
            }
        }

        if (!empty($post_data['thumbnail_path'])) {
            // $attachment_id = TST_Import::get_instance()->maybe_import_local_file(get_template_directory() . $post_data['thumbnail_path']);
            $attachment_id = self::upload_locale_media(get_template_directory() . $post_data['thumbnail_path']);
            echo get_template_directory() . $post_data['thumbnail_path'] . "\n";
            echo "attachment_id=$attachment_id\n";
            set_post_thumbnail($post_id, $attachment_id);
        }

        if (!empty($post_data['tax_terms'])) {
            foreach ($post_data['tax_terms'] as $tax => $terms) {
                wp_set_object_terms($post_id, $terms, $tax, true);
            }
        }

        return $post_id;
    }

    public static function setup_terms_data($terms_data, $tax, $force_update=false)
    {
        foreach ($terms_data as $category) {
            $term = get_term_by('slug', $category['slug'], $tax);
            if ($term === false) {
                $term_id = wp_insert_term($category['name'], $tax, $category);
            } else {
                $term_id = $term->term_id;

                if($force_update) {
                    wp_update_term($term->term_id, $tax, $category);
                }
            }

            if (!empty($category['meta'])) {
                foreach ($category['meta'] as $k => $v) {
                    update_term_meta($term_id, $k, $v);
                }
            }
        }
    }

    public static function link_posts_from_old_to_new_terms($terms_data, $tax, $post_type)
    {
        foreach ($terms_data as $category) {
            $term = get_term_by('slug', $category['slug'], $tax);
            if($term === false) {
                throw new Exception("term not found:" . $category['slug']);
            }

            echo "process term: " . $category['slug'] . "\n";

            if (!empty($category['old_terms'])) {
                foreach ($category['old_terms'] as $old_term_slug) {
                    $params = [
                        'post_type' => $post_type,
                        'post_status' => array_keys(tst_get_task_status_list()),
                        'nopaging' => true,
                        'suppress_filters' => true,
                        'tax_query' => array(
                            array(
                                'taxonomy' => $tax,
                                'field'    => 'slug',
                                'terms'    => $old_term_slug,
                            ),
                        ),                            
                    ];
                    $query = new WP_Query( $params );
                    $posts = $query->get_posts();
                    $posts_count = count($posts);

                    foreach($posts as $index => $post) {
                        wp_set_object_terms($post->ID, $term->term_id, $tax, true);
                        echo "processed " . ($index + 1) . " posts of " . $posts_count . "\n";
                    }
                }
            }
        }
    }

    public static function delete_terms_beoynd_parents($tax, $terms_data)
    {
        $parent_id_list = [];
        foreach ($terms_data as $category) {
            $term = get_term_by('slug', $category['slug'], $tax);
            if($term === false) {
                throw new Exception("term not found:" . $category['slug']);
            }
            $parent_id_list[] = $term->term_id;
        }

        $terms = get_terms([
            'taxonomy' => $tax,
            'hide_empty' => false,
            'exclude' => $parent_id_list,
        ]);

        foreach($terms as $term) {
            if(!$term->parent) {
                echo "delete term: " . $term->slug . "\n";
                wp_delete_term($term->term_id, $tax);
            }
        }
    }

    public static function upload_locale_media(string $src, string $alt = "", string $attachment_title = "", int $attachment_parent_id = 0): int
    {
        $filename = basename($src);

        $upload_file = wp_upload_bits($filename, null, file_get_contents($src));

        if (!$upload_file['error']) {
            $wp_filetype = wp_check_filetype($filename, null);

            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_parent'    => $attachment_parent_id,
                'post_title'     => $attachment_title ? $attachment_title : ($alt ? $alt : preg_replace('/\.[^.]+$/', '', $filename)),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $attachment_parent_id);

            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . "wp-admin" . '/includes/image.php');

                $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);

                wp_update_attachment_metadata($attachment_id,  $attachment_data);

                update_post_meta(
                    $attachment_id,
                    '_wp_attachment_image_alt',
                    $alt
                );

                return $attachment_id;
            }

            return 0;
        }

        return 0;
    }
}



