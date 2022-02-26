<?php

namespace _;

function menu_set_submenu_permission( string $page, string $permission ) {
	global $submenu;
	foreach ( $submenu as $m => &$s ) {
		foreach ( $s as $index => &$mdef ) {
			if ( $mdef[2] !== $page ) {
				continue;
			}
			$mdef[1] = $permission;
			return;
		}
	}
}

function menu_set_permission( string $page, string $permission ) {
	global $menu;
	foreach ( $menu as &$m ) {
		if ( $page !== $m[2] ) {
			continue;
		}
		$m[1] = $permission;
		return;
	}
}
