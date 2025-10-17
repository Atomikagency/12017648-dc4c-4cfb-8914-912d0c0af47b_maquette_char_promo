<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * REST API endpoint for products with faceted search
 */

/**
 * Register REST API endpoint
 */
add_action('rest_api_init', 'maquette_register_products_endpoint');
function maquette_register_products_endpoint() {
    register_rest_route('maquettecharpromo/v1', '/products', [
        'methods'             => 'GET',
        'callback'            => 'maquette_rest_get_products',
        'permission_callback' => '__return_true',
        'args'                => maquette_rest_products_args(),
    ]);
}

/**
 * Define endpoint arguments with validation
 */
function maquette_rest_products_args() {
    return [
        'page' => [
            'default'           => 1,
            'validate_callback' => function($param) {
                return is_numeric($param) && $param > 0;
            },
            'sanitize_callback' => 'absint',
        ],
        'per_page' => [
            'default'           => 12,
            'validate_callback' => function($param) {
                return is_numeric($param) && $param > 0 && $param <= 48;
            },
            'sanitize_callback' => 'absint',
        ],
        'orderby' => [
            'default'           => 'date',
            'validate_callback' => function($param) {
                return in_array($param, ['date', 'price', 'popularity', 'rating'], true);
            },
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'order' => [
            'default'           => 'desc',
            'validate_callback' => function($param) {
                return in_array($param, ['asc', 'desc'], true);
            },
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'search' => [
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'price_min' => [
            'default'           => '',
            'validate_callback' => function($param) {
                return $param === '' || (is_numeric($param) && $param >= 0);
            },
            'sanitize_callback' => function($param) {
                return $param === '' ? '' : floatval($param);
            },
        ],
        'price_max' => [
            'default'           => '',
            'validate_callback' => function($param) {
                return $param === '' || (is_numeric($param) && $param >= 0);
            },
            'sanitize_callback' => function($param) {
                return $param === '' ? '' : floatval($param);
            },
        ],
        'in_stock' => [
            'default'           => '',
            'sanitize_callback' => 'rest_sanitize_boolean',
        ],
        'on_sale' => [
            'default'           => '',
            'sanitize_callback' => 'rest_sanitize_boolean',
        ],
        'categories' => [
            'default'           => [],
            'validate_callback' => function($param) {
                return is_array($param) || empty($param);
            },
            'sanitize_callback' => function($param) {
                if (!is_array($param)) {
                    return [];
                }
                return array_map('sanitize_text_field', $param);
            },
        ],
        'tags' => [
            'default'           => [],
            'validate_callback' => function($param) {
                return is_array($param) || empty($param);
            },
            'sanitize_callback' => function($param) {
                if (!is_array($param)) {
                    return [];
                }
                return array_map('sanitize_text_field', $param);
            },
        ],
        'attributes' => [
            'default'           => [],
            'validate_callback' => function($param) {
                return is_array($param) || empty($param);
            },
            'sanitize_callback' => function($param) {
                if (!is_array($param)) {
                    return [];
                }
                $sanitized = [];
                foreach ($param as $key => $values) {
                    $sanitized[sanitize_text_field($key)] = is_array($values)
                        ? array_map('sanitize_text_field', $values)
                        : [sanitize_text_field($values)];
                }
                return $sanitized;
            },
        ],
        'include' => [
            'default'           => [],
            'validate_callback' => function($param) {
                return is_array($param) || empty($param);
            },
            'sanitize_callback' => function($param) {
                if (!is_array($param)) {
                    return [];
                }
                return array_map('absint', $param);
            },
        ],
        'exclude' => [
            'default'           => [],
            'validate_callback' => function($param) {
                return is_array($param) || empty($param);
            },
            'sanitize_callback' => function($param) {
                if (!is_array($param)) {
                    return [];
                }
                return array_map('absint', $param);
            },
        ],
    ];
}

/**
 * REST API callback - Get products with filters
 */
function maquette_rest_get_products($request) {
    // Check rate limiting (basic implementation)
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $rate_limit_key = 'maquette_rate_limit_' . md5($user_ip);
    $request_count = (int) get_transient($rate_limit_key);

    if ($request_count > 100) { // 100 requests per minute
        return new WP_Error(
            'rate_limit_exceeded',
            __('Trop de requêtes. Veuillez réessayer plus tard.', 'maquette-char-promo'),
            ['status' => 429]
        );
    }

    set_transient($rate_limit_key, $request_count + 1, 60);

    // Generate cache key from request signature
    $cache_key = 'maquette_products_' . md5(json_encode($request->get_params()));
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return rest_ensure_response($cached);
    }

    // Get parameters
    $page       = $request->get_param('page');
    $per_page   = $request->get_param('per_page');
    $orderby    = $request->get_param('orderby');
    $order      = $request->get_param('order');
    $search     = $request->get_param('search');
    $price_min  = $request->get_param('price_min');
    $price_max  = $request->get_param('price_max');
    $in_stock   = $request->get_param('in_stock');
    $on_sale    = $request->get_param('on_sale');
    $categories = $request->get_param('categories');
    $tags       = $request->get_param('tags');
    $attributes = $request->get_param('attributes');
    $include    = $request->get_param('include');
    $exclude    = $request->get_param('exclude');

    // Build WP_Query args
    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
    ];

    // Search
    if (!empty($search)) {
        $args['s'] = $search;
    }

    // Include/Exclude
    if (!empty($include)) {
        $args['post__in'] = $include;
    }
    if (!empty($exclude)) {
        $args['post__not_in'] = $exclude;
    }

    // Orderby
    switch ($orderby) {
        case 'price':
            $args['meta_key'] = '_price';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = $order;
            break;
        case 'popularity':
            $args['meta_key'] = 'total_sales';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = $order;
            break;
        case 'rating':
            $args['meta_key'] = '_wc_average_rating';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = $order;
            break;
        default: // date
            $args['orderby'] = 'date';
            $args['order']   = $order;
    }

    // Tax queries
    $tax_query = ['relation' => 'AND'];

    if (!empty($categories)) {
        $tax_query[] = [
            'taxonomy' => 'product_cat',
            'field'    => is_numeric($categories[0]) ? 'term_id' : 'slug',
            'terms'    => $categories,
        ];
    }

    if (!empty($tags)) {
        $tax_query[] = [
            'taxonomy' => 'product_tag',
            'field'    => is_numeric($tags[0]) ? 'term_id' : 'slug',
            'terms'    => $tags,
        ];
    }

    if (!empty($attributes)) {
        foreach ($attributes as $taxonomy => $terms) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $terms,
            ];
        }
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    // Meta queries
    $meta_query = ['relation' => 'AND'];

    if ($price_min !== '' || $price_max !== '') {
        if ($price_min !== '' && $price_max !== '') {
            $meta_query[] = [
                'key'     => '_price',
                'value'   => [$price_min, $price_max],
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            ];
        } elseif ($price_min !== '') {
            $meta_query[] = [
                'key'     => '_price',
                'value'   => $price_min,
                'type'    => 'NUMERIC',
                'compare' => '>=',
            ];
        } else {
            $meta_query[] = [
                'key'     => '_price',
                'value'   => $price_max,
                'type'    => 'NUMERIC',
                'compare' => '<=',
            ];
        }
    }

    if ($in_stock !== '') {
        $meta_query[] = [
            'key'   => '_stock_status',
            'value' => $in_stock ? 'instock' : 'outofstock',
        ];
    }

    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    // On sale filter (requires post__in)
    if ($on_sale !== '') {
        $sale_ids = wc_get_product_ids_on_sale();
        if (!empty($sale_ids)) {
            if (isset($args['post__in'])) {
                $args['post__in'] = array_intersect($args['post__in'], $sale_ids);
            } else {
                $args['post__in'] = $sale_ids;
            }
        } else {
            $args['post__in'] = [0]; // Force no results if no sales
        }
    }

    // Execute query
    $query = new WP_Query($args);

    // Format items
    $items = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());

            if (!$product) {
                continue;
            }

            // Get product attributes
            $product_attributes = [];
            foreach ($product->get_attributes() as $attr_name => $attr) {
                if ($attr->is_taxonomy()) {
                    $terms = wp_get_post_terms($product->get_id(), $attr->get_name());
                    if (!empty($terms) && !is_wp_error($terms)) {
                        $product_attributes[$attr->get_name()] = $terms[0]->name;
                    }
                } else {
                    $product_attributes[$attr_name] = $attr->get_options()[0] ?? '';
                }
            }

            // Determine badges
            $badges = [];
            if ($product->is_on_sale()) {
                $badges[] = 'sale';
            }

            // Check if new (less than 30 days old)
            $created = strtotime($product->get_date_created());
            if ($created > strtotime('-30 days')) {
                $badges[] = 'new';
            }

            $items[] = [
                'id'           => $product->get_id(),
                'title'        => $product->get_name(),
                'permalink'    => get_permalink($product->get_id()),
                'image'        => wp_get_attachment_url($product->get_image_id()),
                'price'        => [
                    'raw'  => (float) $product->get_price(),
                    'html' => $product->get_price_html(),
                ],
                'on_sale'      => $product->is_on_sale(),
                'stock_status' => $product->get_stock_status(),
                'rating'       => (float) $product->get_average_rating(),
                'attributes'   => $product_attributes,
                'badges'       => $badges,
            ];
        }
        wp_reset_postdata();
    }

    // Build facets
    $facets = maquette_build_facets($args);

    // Build response
    $response = [
        'items'      => $items,
        'pagination' => [
            'page'        => $page,
            'per_page'    => $per_page,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
        ],
        'facets'     => $facets,
    ];

    // Cache for 120 seconds
    set_transient($cache_key, $response, 120);

    return rest_ensure_response($response);
}

/**
 * Build facets for filtering
 */
function maquette_build_facets($base_args) {
    $facets = [
        'categories'    => [],
        'tags'          => [],
        'attributes'    => [],
        'price_ranges'  => [],
    ];

    // Get all products matching current filters (without pagination)
    $facet_args = $base_args;
    unset($facet_args['paged']);
    unset($facet_args['posts_per_page']);
    $facet_args['posts_per_page'] = -1;
    $facet_args['fields'] = 'ids';

    $product_ids = get_posts($facet_args);

    if (empty($product_ids)) {
        return $facets;
    }

    // Categories facet
    $categories = wp_get_object_terms($product_ids, 'product_cat', ['fields' => 'all']);
    foreach ($categories as $cat) {
        $facets['categories'][] = [
            'key'   => $cat->slug,
            'label' => $cat->name,
            'count' => $cat->count,
        ];
    }

    // Tags facet
    $tags = wp_get_object_terms($product_ids, 'product_tag', ['fields' => 'all']);
    foreach ($tags as $tag) {
        $facets['tags'][] = [
            'key'   => $tag->slug,
            'label' => $tag->name,
            'count' => $tag->count,
        ];
    }

    // Attributes facet (common product attributes)
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    foreach ($attribute_taxonomies as $tax) {
        $taxonomy = wc_attribute_taxonomy_name($tax->attribute_name);
        $terms = wp_get_object_terms($product_ids, $taxonomy, ['fields' => 'all']);

        if (!empty($terms) && !is_wp_error($terms)) {
            $facets['attributes'][$taxonomy] = [];
            foreach ($terms as $term) {
                $facets['attributes'][$taxonomy][] = [
                    'key'   => $term->slug,
                    'label' => $term->name,
                    'count' => $term->count,
                ];
            }
        }
    }

    // Price ranges facet
    $price_ranges = [
        ['min' => 0, 'max' => 20],
        ['min' => 20, 'max' => 50],
        ['min' => 50, 'max' => 100],
        ['min' => 100, 'max' => 999999],
    ];

    foreach ($price_ranges as $range) {
        $count_args = $base_args;
        unset($count_args['paged']);
        unset($count_args['posts_per_page']);
        $count_args['posts_per_page'] = -1;
        $count_args['fields'] = 'ids';

        $count_args['meta_query'] = [
            [
                'key'     => '_price',
                'value'   => [$range['min'], $range['max']],
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            ],
        ];

        $count = count(get_posts($count_args));

        if ($count > 0) {
            $facets['price_ranges'][] = [
                'min'   => $range['min'],
                'max'   => $range['max'],
                'count' => $count,
            ];
        }
    }

    return $facets;
}

/**
 * Invalidate cache on product update
 */
add_action('save_post_product', 'maquette_invalidate_products_cache');
add_action('woocommerce_update_product', 'maquette_invalidate_products_cache');
function maquette_invalidate_products_cache() {
    global $wpdb;

    // Delete all transients starting with maquette_products_
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
        WHERE option_name LIKE '_transient_maquette_products_%'
        OR option_name LIKE '_transient_timeout_maquette_products_%'"
    );
}
