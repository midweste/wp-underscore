<?php

namespace _;

/**
 * Return the registered image styles along with their dimensions and crop settings
 *
 * @global array $_wp_additional_image_sizes
 *
 * @link http://core.trac.wordpress.org/ticket/18947 Reference ticket
 *
 * @return array $image_sizes The image sizes
 */
function image_styles_registered()
{
    global $_wp_additional_image_sizes;

    $default_image_sizes = get_intermediate_image_sizes();
    $image_sizes = [];
    foreach ($default_image_sizes as $size) {
        $image_sizes[$size]['width']  = intval(get_option("{$size}_size_w"));
        $image_sizes[$size]['height'] = intval(get_option("{$size}_size_h"));
        $image_sizes[$size]['crop']   = get_option("{$size}_crop") ? get_option("{$size}_crop") : false;
    }

    if (isset($_wp_additional_image_sizes) && count($_wp_additional_image_sizes)) {
        $image_sizes = array_merge($image_sizes, $_wp_additional_image_sizes);
    }
    // d($_wp_additional_image_sizes, $default_image_sizes, $image_sizes);
    return $image_sizes;
}

function image_styles_registered_common()
{
    $default_image_sizes = get_intermediate_image_sizes();

    $image_sizes = [];
    foreach ($default_image_sizes as $size) {
        $image_sizes[$size]['width']  = intval(get_option("{$size}_size_w"));
        $image_sizes[$size]['height'] = intval(get_option("{$size}_size_h"));
        $image_sizes[$size]['crop']   = get_option("{$size}_crop") ? get_option("{$size}_crop") : false;
    }
    return $image_sizes;
}

/**
 * Return a registered image style along with its dimensions and crop settings
 *
 * @param string $slug
 * @return array
 */
function image_style(string $slug): array
{
    $styles = image_styles_registered();
    return (isset($styles[$slug])) ? $styles[$slug] : [];
}

/**
 * Return the parent image of an image style without the -###x### dimension
 *
 * @param string $path_or_uri
 * @return string
 */
function image_parent(string $path_or_uri): string
{
    $dimension_pattern = '/(.*?)\-\d*x\d*(\.\w*)$/';
    $parent = preg_replace($dimension_pattern, '\1\2', $path_or_uri);
    return $parent;
}

/**
 * Return the post_id of the parent image linked to the path provided
 *
 * @param string $path_or_uri
 * @return integer|null
 */
function image_parent_id(string $path_or_uri): ?int
{
    global $wpdb;
    $parent = image_parent($path_or_uri);
    $path_relative_uploads = uploads_relative($parent);
    $like = '%' . $wpdb->esc_like($path_relative_uploads) . '%';
    $media_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key IN ('_wp_attached_file', '_wp_attachment_metadata') AND meta_value LIKE %s", $like));
    return (is_numeric($media_id)) ? $media_id : null;
}

/**
 * Return image style based on path or uri.  Will choose the first matching size with matching dimensions
 *
 * @param string $path_or_uri
 * @return string
 */
function image_style_by_path(string $path_or_uri): string
{
    // check first if image is the base image
    $style = 'full';
    $dimension_pattern = '/.*?\-(\d*)x(\d*)\.\w*$/';
    preg_match($dimension_pattern, $path_or_uri, $matches);
    if (empty($matches) || !is_numeric($matches[1]) || !is_numeric($matches[2])) {
        return $style;
    }

    // image looks to be a sub style, get parent and check styles
    $width = (int) $matches[1];
    $height = (int) $matches[2];

    $parent_id = image_parent_id($path_or_uri);
    if (!is_numeric($parent_id)) {
        return $style;
    }
    $styles = image_styles_registered();
    foreach ($styles as $name => $data) {
        list($style_src, $style_width, $style_height) = image_downsize($parent_id, $name);
        if ($path_or_uri === $style_src && $width === $style_width && $height === $style_height) {
            return $name;
        }
    }
    return $style;
}
