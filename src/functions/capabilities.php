<?php

namespace _;

function capability_alter( array $roles, string $singular, string $plural, bool $grant = true, bool $add = true ): void {
	$function = ( $add ) ? 'add_cap' : 'remove_cap';
	foreach ( $roles as $role ) {
		$role = \get_role( $role );

		$role->{$function}( 'read_' . $singular, $grant );
		$role->{$function}( 'edit_' . $singular, $grant );

		$role->{$function}( 'read_private_' . $plural, $grant );
		$role->{$function}( 'edit_' . $plural, $grant );
		$role->{$function}( 'edit_others_' . $plural, $grant );
		$role->{$function}( 'edit_published_' . $plural, $grant );
		$role->{$function}( 'publish_' . $plural, $grant );
		$role->{$function}( 'delete_others_' . $plural, $grant );
		$role->{$function}( 'delete_private_' . $plural, $grant );
		$role->{$function}( 'delete_published_' . $plural, $grant );
	}
}

function capability_add( array $roles, string $singular, string $plural ): void {
	capability_alter( $roles, $singular, $plural, true, true );
}


function capability_remove( array $roles, string $singular, string $plural ): void {
	capability_alter( $roles, $singular, $plural, true, false );
}
