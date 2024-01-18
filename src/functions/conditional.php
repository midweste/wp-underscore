<?php

namespace _;

function is_rest_request(): bool
{
    return defined('REST_REQUEST') && REST_REQUEST === true;
    // "/wp-json/ysm/v1/search?id=default&query=savvy"
    //return (strpos($_SERVER['REQUEST_URI'], '/wp-json/') === 0) ? true : false;
}

function is_post_edit(): bool
{
    $uri = $_SERVER['REQUEST_URI'];
    $parsed = parse_url($uri);
    if (!isset($parsed['path'])) {
        return false;
    }
    $path = pathinfo($parsed['path']);
    $page = $path['basename'];
    $is_edit = ($page === 'post.php' && isset($_GET['action']) && $_GET['action'] === 'edit');
    return ($is_edit) ? true : false;
}

function is_post_new(): bool
{
    $uri = $_SERVER['REQUEST_URI'];
    $parsed = parse_url($uri);
    if (!isset($parsed['path'])) {
        return false;
    }
    $path = pathinfo($parsed['path']);
    $page = $path['basename'];
    $is_new = ($page === 'post-new.php');
    return ($is_new) ? true : false;
}

function is_post_edit_or_new(): bool
{
    return (is_post_edit() || is_post_new()) ? true : false;
}

function is_post_edit_or_new_type(string $post_type): bool
{
    $edit_or_new = is_post_edit_or_new();
    if (!$edit_or_new || !isset($_GET['post']) || !is_numeric($_GET['post'])) {
        return false;
    }
    $post = get_post($_GET['post']);
    if (!isset($post->post_type) || $post->post_type !== $post_type) {
        return false;
    }
    return true;
}

function conditional_types(): array
{
    $excluded = [
        'is_ajax', //deprecated
        'is_blog_user', //deprecated
        'is_comments_popup', //deprecated
        'is_plugin_page', // deprecated
        // 'is_header_video_active',
        // 'is_favicon',
        // 'is_lighttpd_before_150',
    ];
    $excluded = apply_filters('_conditional_types_excluded', $excluded);

    $functions = get_defined_functions(true)['user'];
    $conditionals = [];
    foreach ($functions as $function) {
        if (strpos($function, 'is_') !== 0 || in_array($function, $excluded)) {
            continue;
        }
        $reflection = new \ReflectionFunction($function);

        // no params
        if ($reflection->getNumberOfParameters() === 0) {
            $conditionals[] = $function;
            continue;
        }

        // params with default values
        $params        = $reflection->getParameters();
        $defaultParams = true;
        foreach ($params as $param) {
            if (!$param->isDefaultValueAvailable()) {
                $defaultParams = false;
                break;
            }
        }
        if ($defaultParams) {
            $conditionals[] = $function;
        }
    }

    // add in extras
    $conditionals = array_merge(
        $conditionals,
        [
            'wp_doing_ajax',
            'is_rest_request',
            'is_product_attribute_taxonomy',
            'is_product_download',
            '_\user_is_admin',
        ]
    );
    sort($conditionals);

    $types = [];
    foreach ($conditionals as $conditional) {
        if (function_exists($conditional) && $conditional()) {
            $types[] = $conditional;
        }
    }
    return $types;
}

function is_local_resource(string $uri): bool
{
    $uri = trim($uri);
    if (empty($uri)) {
        return true;
    }
    if (strpos($uri, '/') === 0) {
        return true;
    }
    if (strpos($uri, site_url()) === 0) {
        return true;
    }
    $remote = wp_parse_url($uri, PHP_URL_HOST);
    $site = wp_parse_url(site_url(), PHP_URL_HOST);
    if ($remote === $site) {
        return true;
    }
    return false;
}
