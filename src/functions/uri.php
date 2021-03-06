<?php

namespace _;

/**
 * Retrieves the full permalink for the current post or post ID.
 *
 * @since 1.0.0
 *
 * @param int|WP_Post $post      Optional. Post ID or post object. Default is the global `$post`.
 * @param bool        $leavename Optional. Whether to keep post name or page name. Default false.
 * @return string|false The permalink URL or false if post does not exist.
 */
function permalink($post = 0, $leavename = false)
{
    $rewritecode = array(
        '%year%',
        '%monthnum%',
        '%day%',
        '%hour%',
        '%minute%',
        '%second%',
        $leavename ? '' : '%postname%',
        '%post_id%',
        '%category%',
        '%author%',
        $leavename ? '' : '%pagename%',
    );

    if (is_object($post) && isset($post->filter) && 'sample' == $post->filter) {
        $sample = true;
    } else {
        $post   = get_post($post);
        $sample = false;
    }

    if (empty($post->ID)) {
        return false;
    }

    // override status to get actual permalink regardless of post status
    $post->post_status = 'publish';

    if ('page' === $post->post_type) {
        return get_page_link($post, $leavename, $sample);
    } elseif ('attachment' === $post->post_type) {
        return get_attachment_link($post, $leavename);
    } elseif (in_array($post->post_type, get_post_types(array('_builtin' => false)))) {
        return get_post_permalink($post, $leavename, $sample);
    }

    $permalink = get_option('permalink_structure');

    /**
     * Filters the permalink structure for a post before token replacement occurs.
     *
     * Only applies to posts with post_type of 'post'.
     *
     * @since 3.0.0
     *
     * @param string  $permalink The site's permalink structure.
     * @param WP_Post $post      The post in question.
     * @param bool    $leavename Whether to keep the post name.
     */
    $permalink = apply_filters('pre_post_link', $permalink, $post, $leavename);

    if ('' != $permalink) {

        $category = '';
        if (strpos($permalink, '%category%') !== false) {
            $cats = get_the_category($post->ID);
            if ($cats) {
                $cats = wp_list_sort(
                    $cats,
                    array(
                        'term_id' => 'ASC',
                    )
                );

                /**
                 * Filters the category that gets used in the %category% permalink token.
                 *
                 * @since 3.5.0
                 *
                 * @param WP_Term  $cat  The category to use in the permalink.
                 * @param array    $cats Array of all categories (WP_Term objects) associated with the post.
                 * @param WP_Post  $post The post in question.
                 */
                $category_object = apply_filters('post_link_category', $cats[0], $cats, $post);

                $category_object = get_term($category_object, 'category');
                $category        = $category_object->slug;
                if ($category_object->parent) {
                    $category = get_category_parents($category_object->parent, false, '/', true) . $category;
                }
            }
            // Show default category in permalinks,
            // without having to assign it explicitly.
            if (empty($category)) {
                $default_category = get_term(get_option('default_category'), 'category');
                if ($default_category && !is_wp_error($default_category)) {
                    $category = $default_category->slug;
                }
            }
        }

        $author = '';
        if (strpos($permalink, '%author%') !== false) {
            $authordata = get_userdata($post->post_author);
            $author     = $authordata->user_nicename;
        }

        // This is not an API call because the permalink is based on the stored post_date value,
        // which should be parsed as local time regardless of the default PHP timezone.
        $date = explode(' ', str_replace(array('-', ':'), ' ', $post->post_date));

        $rewritereplace = array(
            $date[0],
            $date[1],
            $date[2],
            $date[3],
            $date[4],
            $date[5],
            $post->post_name,
            $post->ID,
            $category,
            $author,
            $post->post_name,
        );

        $permalink = home_url(str_replace($rewritecode, $rewritereplace, $permalink));
        $permalink = user_trailingslashit($permalink, 'single');
    } else { // If they're not using the fancy permalink option.
        $permalink = home_url('?p=' . $post->ID);
    }

    /**
     * Filters the permalink for a post.
     *
     * Only applies to posts with post_type of 'post'.
     *
     * @since 1.5.0
     *
     * @param string  $permalink The post's permalink.
     * @param WP_Post $post      The post in question.
     * @param bool    $leavename Whether to keep the post name.
     */
    return apply_filters('post_link', $permalink, $post, $leavename);
}

/**
 * Return a relative link for any host
 *
 * @param string $uri
 * @return string
 */
function uri_relative(string $uri): string
{
    $p = parse_url($uri);
    if ($p == null || $p == false) {
        return $uri;
    }
    $qs = (!empty($p['query'])) ? '?' . $p['query'] : '';
    if (empty($p['path']) || $p['path'] == '/') {
        return '/' . $qs;
    }
    return rtrim($p['path'], '/') . $qs;

    // $urls =  [
    //     'https://www.example.com',
    //     'https://www.example.com/',
    //     'https://www.example.com?v=7516fd43adaa',
    //     'https://www.example.com/?v=7516fd43adaa',
    //     '/asdf/asdf?v=7516fd43adaa',
    //     '/?v=7516fd43adaa',
    //     '?v=7516fd43adaa',

    // ];
    // $parsed = [];
    // foreach ($urls as $url) {
    //     $parsed[$url] = url_relative($url);
    // }
    // d($parsed);
    // exit();
}

/**
 * Return a relative uri only for links on current host
 *
 * @param string $uri
 * @return string
 */
function uri_server_relative(string $uri, string $host = ''): string
{
    $p = parse_url($uri);
    if ($p == null || $p == false) {
        return $uri;
    }
    // bail on links to other servers
    $host_check = ($host === '' || !is_string($host)) ? server_host() : $host;
    if (!empty($p['host']) && $p['host'] !== $host_check) {
        return $uri;
    }
    $qs = (!empty($p['query'])) ? '?' . $p['query'] : '';
    if (empty($p['path']) || $p['path'] == '/') {
        return '/' . $qs;
    }
    return rtrim($p['path'], '/') . $qs;

    // $urls =  [
    //     'https://www.example.com',
    //     'https://www.example.com/',
    //     'https://www.example.com?v=7516fd43adaa',
    //     'https://www.example.com/?v=7516fd43adaa',
    //     'https://tcbwoo.lndo.site',
    //     'https://tcbwoo.lndo.site/',
    //     'https://tcbwoo.lndo.site?v=7516fd43adaa',
    //     'https://tcbwoo.lndo.site/?v=7516fd43adaa',
    //     '/asdf/asdf?v=7516fd43adaa',
    //     '/?v=7516fd43adaa',
    //     '?v=7516fd43adaa',

    // ];
    // $parsed = [];
    // foreach ($urls as $url) {
    //     $parsed[$url] = url_relative($url);
    // }
    // d($parsed);
    // exit();

}

function uri_to_path(string $url): string
{
    $relative = str_replace(WP_HOME . '/', '', $url);
    return ABSPATH . $relative;
}

function is_uri(string $uri): bool
{
    return (filter_var($uri, FILTER_VALIDATE_URL) !== false);
}

function uri_to_absolute(string $uri): string
{
    $replaced = str_replace(WP_HOME, '', $uri);
    return (strpos($replaced, '/') === 0) ? WP_HOME . $replaced : WP_HOME . '/' . $replaced;
}

function uri_is_absolute(string $uri): string
{
    return strpos(trim($uri), '//') === 0 || strpos(trim($uri), '/') === 0;
}

function uri_is_relative(string $uri): string
{
    return uri_is_absolute($uri) === false;
}

function server_protocol()
{
    $isSecure = false;
    if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
        $visitorData = @json_decode($_SERVER['HTTP_CF_VISITOR']); //phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        if (!empty($visitorData) && !empty($visitorData->scheme) && $visitorData->scheme === 'https') {
            $isSecure = true;
        }
    } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $isSecure = true;
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        $isSecure = true;
    }

    return $isSecure ? 'https' : 'http';
}

function server_host()
{
    $host = $_SERVER['HTTP_HOST'];
    if (!empty($_SERVER['HTTP_X_ORIGINAL_HOST'])) {
        $host = $_SERVER['HTTP_X_ORIGINAL_HOST'];
    }

    // remove port number if using secure protocol and port is detected as 80 due to proxy
    if (server_protocol() === 'https' && strpos($host, ':80') !== false) {
        $host = current(explode(':', $host));
    }

    return $host;
}

function server_domain()
{
    return server_protocol() . '://' . server_host();
}
