<?php

namespace _;

function is_rest_request(): bool
{
    return defined('REST_REQUEST') && REST_REQUEST === true;
    // "/wp-json/ysm/v1/search?id=default&query=savvy"
    //return (strpos($_SERVER['REQUEST_URI'], '/wp-json/') === 0) ? true : false;
}

function conditional_types(): array
{
    // https://codex.wordpress.org/Conditional_Tags
    // https://docs.woocommerce.com/document/conditional-tags/
    $conditionals = [
        // wp
        'is_admin',
        'is_front_page',
        'is_page',
        'is_single',
        'is_category',
        'is_tag',
        'is_tax',
        'is_post_type_archive',
        'is_author',
        'is_archive',
        'is_search',
        'is_404',
        'is_paged',
        'is_attachment',
        'is_singular',
        'is_main_query',
        'is_page_template',
        'wp_doing_ajax',
        // woo
        'is_woocommerce',
        'is_shop',
        'is_product_category',
        'is_product_tag',
        'is_product',
        'is_cart',
        'is_checkout',
        'is_account_page',
        'is_rest_request',
        // custom
        '_\user_is_admin'
    ];
    $conditionals = apply_filters('_conditional_types', $conditionals);

    $excluded = [
        'is_ajax', //deprecated
        // 'is_blog_installed',
        'is_comments_popup',
        // 'is_header_video_active',
        // 'is_favicon',
        // 'is_lighttpd_before_150',
        'is_plugin_page'
    ];
    $excluded = apply_filters('_conditional_types_excluded', $excluded);

    $functions = get_defined_functions(true)['user'];
    //$conditionals = [];
    foreach ($functions as $function) {
        if (strpos($function, 'is_') !== 0 || in_array($function, $excluded) || in_array($function, $conditionals)) {
            continue;
        }
        $reflection = new \ReflectionFunction($function);

        $params = $reflection->getParameters();
        $defaultParams = true;
        foreach ($params as $param) {
            if (!$param->isDefaultValueAvailable()) {
                $defaultParams = false;
                break;
            }
        }
        if (!$defaultParams || $reflection->getNumberOfParameters() !== 0) {
            continue;
        }

        $conditionals[] = $function;
    }
    sort($conditionals);

    $types = [];
    foreach ($conditionals as $conditional) {
        if (function_exists($conditional) && $conditional()) {
            $types[] = $conditional;
        }
    }

    return $types;
}
