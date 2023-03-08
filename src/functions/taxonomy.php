<?php

namespace _;

// function taxonomy_copy_term_recursive( int|string|\WP_Term $term, string $source_taxonomy, string $destination_taxonomy, int $parent = 0 ): \WP_Term
function taxonomy_copy_term_recursive($term, string $source_taxonomy, string $destination_taxonomy, int $parent = 0): \WP_Term
{

    if (!$term instanceof \WP_Term) {
        $term = get_term($term, $source_taxonomy);
    }

    // check for existing
    $new_term_exists = term_exists($term->name, $destination_taxonomy);
    if ($new_term_exists) {
        return get_term((int) $new_term_exists['term_id'], $destination_taxonomy);
    }

    // copy ancestors
    $ancestors = array_reverse(get_ancestors($term->term_id, $source_taxonomy, 'taxonomy'));
    if (!empty($ancestors)) {
        foreach ($ancestors as $ancestor) {
            $new_ancestor = taxonomy_copy_term_recursive($ancestor, $source_taxonomy, $destination_taxonomy);
            $parent       = $new_ancestor->term_id;
            break;
        }
        // return get_term( $new_ancestor->term_id, $destination_taxonomy );
    }

    // create new
    $new_term = wp_insert_term($term->name, $destination_taxonomy, [
        'description' => $term->description,
        'slug'        => $term->slug,
        'parent'      => $parent,
    ]);
    if (is_wp_error($new_term)) {
        throw new \Exception(sprintf('Could not create term %d %s in %d', $term->term_id, $term->name, $destination_taxonomy));
    }
    return get_term((int) $new_term['term_id'], $destination_taxonomy);
}

function taxonomy_merged_child_ids(array $taxonomy_ids, string $taxonomy): array
{
    $children = array();
    foreach ($taxonomy_ids as $id) {
        $children[] = $id;
        $children = array_merge($children, get_term_children($id, $taxonomy));
    }
    return array_unique($children);
}

/**
 * Recursively get taxonomy hierarchy
 *
 * @source http://www.daggerhart.com/wordpress-get-taxonomy-hierarchy-including-children/
 * @param string $taxonomy
 * @param int    $parent - parent term id
 *
 * @return array
 */
function taxonomy_hierarchy(string $taxonomy, $parent = 0)
{
    $taxonomy = is_array($taxonomy) ? array_shift($taxonomy) : $taxonomy;
    $terms = get_terms($taxonomy, [
        'parent' => $parent,
        'hide_empty' => false
    ]);
    $children = array();

    foreach ($terms as $term) {
        $term->children = taxonomy_hierarchy($taxonomy, $term->term_id);
        $children[$term->term_id] = $term;
    }
    return $children;
}
