<?php

namespace _;

function attachment_create(string $path): int
{
    if (!is_file($path)) {
        throw new \Exception(sprintf('File %s does not exist', $path));
    }

    $filename    = basename($path);
    $wp_filetype = \wp_check_filetype($filename, null);

    $attachment = [
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => \sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    $attach_id = \wp_insert_attachment($attachment, $path);
    if (!is_numeric($attach_id)) {
        throw new \Exception('Could not create attachment for %s', $path);
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    \wp_generate_attachment_metadata($attach_id, $path);
    return $attach_id;
}

function attachment_change_path(int $attachment_id, string $filePath): bool
{
    $result = \update_attached_file($attachment_id, $filePath);

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $metadata = \wp_generate_attachment_metadata($attachment_id, $filePath);
    if (!is_array($metadata)) {
        throw new \Exception(sprintf('Could not generate attachment metadata for %s', $filePath));
    }

    \clean_post_cache(\get_post($attachment_id));
    return ($result === false) ? false : true;
}
