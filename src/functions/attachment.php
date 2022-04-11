<?php

namespace _;

function attachment_create(string $path): int
{
    if (!is_file($path)) {
        throw new \Exception(sprintf('File %s does not exist', $path));
    }

    $filename    = basename($path);
    $wp_filetype = wp_check_filetype($filename, null);

    $attachment = [
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    $attach_id = wp_insert_attachment($attachment, $path);
    if (!is_numeric($attach_id)) {
        throw new \Exception('Could not create attachment for %s', $path);
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_generate_attachment_metadata($attach_id, $path);
    return $attach_id;
}
