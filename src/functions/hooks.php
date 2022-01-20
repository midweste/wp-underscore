<?php

namespace _;

function hook_exists(string $hook): bool
{
    global $wp_filter;
    return (empty($wp_filter)) ? false : in_array($hook, $wp_filter);
}

function hook_callback_priority(string $hook, string $function): ?int
{
    $priority = \has_filter($hook, $function);
    return (!$priority) ? null : $priority;
}

/**
 * Removes a hook callback regardless of priority
 *
 * @param string $hook
 * @param string $function
 * @return boolean
 */
function filter_remove(string $hook, string $function): bool
{
    $priority = hook_callback_priority($hook, $function);
    if (!$priority) {
        return false;
    }
    return \remove_filter($hook, $function, $priority);
}

/**
 * Removes a hook callback regardless of priority
 *
 * @param string $hook
 * @param string $function
 * @return boolean
 */
function action_remove(string $hook, string $function): bool
{
    return filter_remove($hook, $function);
}
