<?php

namespace _;

/**
 * Set permissions for a submenu item
 *
 * @param string $page Page slug of menu
 * @param string $permission Capability permission to set
 * @return bool True if successfully set, false if unable to set
 */
function menu_set_submenu_permission( string $page, string $permission ): bool {
	global $submenu;
	foreach ( $submenu as $m => &$s ) {
		foreach ( $s as $index => &$mdef ) {
			if ( $mdef[2] !== $page ) {
				continue;
			}
			$mdef[1] = $permission;
			return true;
		}
	}
    return false;
}

/**
 * Set permissions for a top level menu item
 *
 * @param string $page Page slug of menu
 * @param string $permission Capability permission to set
 * @return boolean True if successfully set, false if unable to set
 */
function menu_set_permission( string $page, string $permission ): bool {
	global $menu;
	foreach ( $menu as &$m ) {
		if ( $page !== $m[2] ) {
			continue;
		}
		$m[1] = $permission;
		return true;
	}
    return false;
}
