<?php

namespace _;

function user_is_admin(): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	return current_user_can( 'activate_plugins' );
}
