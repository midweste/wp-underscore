<?php

namespace _;

function capability_alter(array $roles, string $singular, string $plural, bool $grant = true, bool $add = true): void
{
    $function = ($add) ? 'add_cap' : 'remove_cap';
    foreach ($roles as $role) {
        $role = \get_role($role);

        $role->{$function}('read_' . $singular, $grant);
        $role->{$function}('edit_' . $singular, $grant);

        $role->{$function}('read_private_' . $plural, $grant);
        $role->{$function}('edit_' . $plural, $grant);
        $role->{$function}('edit_others_' . $plural, $grant);
        $role->{$function}('edit_published_' . $plural, $grant);
        $role->{$function}('publish_' . $plural, $grant);
        $role->{$function}('delete_others_' . $plural, $grant);
        $role->{$function}('delete_private_' . $plural, $grant);
        $role->{$function}('delete_published_' . $plural, $grant);
    }
}

function capability_add(array $roles, string $singular, string $plural): void
{
    capability_alter($roles, $singular, $plural, true, true);
}


function capability_remove(array $roles, string $singular, string $plural): void
{
    capability_alter($roles, $singular, $plural, true, false);
}

// add_action('admin_init', function () {
//     global $wp_roles;

//     if (class_exists('WP_Roles') && !isset($wp_roles)) {
//         $wp_roles = new WP_Roles();
//     }

//     if (is_object($wp_roles)) {
//         $singular = 'thing';
//         $plural = 'things';

//         foreach (['administrator'] as $role) {
//             //$role = get_role($the_role);
//             $wp_roles->add_cap($role, 'read_' . $singular);
//             $wp_roles->add_cap($role, 'read_private_' . $plural);
//             $wp_roles->add_cap($role, 'edit_' . $singular);
//             $wp_roles->add_cap($role, 'edit_' . $plural);
//             $wp_roles->add_cap($role, 'edit_others_' . $plural);
//             $wp_roles->add_cap($role, 'edit_published_' . $plural);
//             $wp_roles->add_cap($role, 'publish_' . $plural);
//             $wp_roles->add_cap($role, 'delete_others_' . $plural);
//             $wp_roles->add_cap($role, 'delete_private_' . $plural);
//             $wp_roles->add_cap($role, 'delete_published_' . $plural);
//         }
//     }
// }, PHP_INT_MAX);