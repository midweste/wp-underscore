<?php

namespace _;

/**
 * Create a new attachment based on a filepath
 *
 * @param string $path
 * @return integer
 */
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

/**
 * Regenerate all metadata for an attachment id
 *
 * @param integer $attachment_id
 * @return boolean
 */
function attachment_regenerate_metadata(int $attachment_id): bool
{
    $filepath = \get_attached_file($attachment_id);
    if (!is_file($filepath)) {
        throw new \Exception(sprintf('File does not exist for attachment %d', $attachment_id));
    }
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $metadata = \wp_generate_attachment_metadata($attachment_id, $filepath);
    if (!is_array($metadata)) {
        return false;
    }
    \clean_post_cache(\get_post($attachment_id));
    return true;
}

/**
 * Regenerate an attachment, optionally with a new attached file
 *
 * @param integer $attachment_id
 * @param string $new_path
 * @return boolean
 */
function attachment_regenerate(int $attachment_id, string $new_path = ''): bool
{
    $attached = $deleted = true;
    if ($new_path !== '') {
        $original_path = \get_attached_file($attachment_id);
        if ($original_path !== $new_path) {
            if (!is_file($new_path) || filesize($new_path) === false) {
                throw new \Exception(sprintf('Could not regenerate, file %s does not exist', $new_path));
            }
            // delete images from previous name
            $deleted = attachment_delete_images($attachment_id);
            $attached = \update_attached_file($attachment_id, $new_path);
        }
    }
    $regenerated = attachment_regenerate_metadata($attachment_id);
    \clean_post_cache(\get_post($attachment_id));
    return ($attached && $regenerated && $deleted) ? true : false;
}

/**
 * Delete all images associated with an attachment id, including the main image
 *
 * @param integer $attachment_id
 * @return boolean
 */
function attachment_delete_images(int $attachment_id): bool
{
    if (!\wp_attachment_is_image($attachment_id)) {
        return false;
    }
    // taken from wp_delete_attachment
    $meta = \wp_get_attachment_metadata($attachment_id);
    $backup_sizes = \get_post_meta($attachment_id, '_wp_attachment_backup_sizes', true);
    $file = \get_attached_file($attachment_id);
    \wp_delete_attachment_files($attachment_id, $meta, $backup_sizes, $file);
    if (is_file($file)) {
        throw new \Exception(sprintf('Could not clean old styles for attachment %d %s', $attachment_id, $file));
    }
    \clean_post_cache(\get_post($attachment_id));
    return true;
}

/**
 * Return a boolean true if an attachment has been edited
 *
 * @param integer $attachment_id
 * @return boolean
 */
function attachment_image_is_edited(int $attachment_id): bool
{
    $file = get_attached_file($attachment_id);
    $original = wp_get_original_image_path($attachment_id);
    return ($file !== $original) ? true : false;
}
