<?php

namespace _;

/**
 * Return an array of current php variable values.
 *
 * @return array
 */
function php_variables( bool $include_nulls = true ): array {
	$variables = ini_get_all();
	ksort( $variables, SORT_NATURAL | SORT_FLAG_CASE );

	$values = [];
	foreach ( $variables as $k => $v ) {
		if ( is_null( $v ) ) {
			if ( $include_nulls ) {
				$values[ $k ] = null;
			}
		} elseif ( is_numeric( $v ) ) {
			$values[ $k ] = (int) $v['local_value'];
		} else {
			$values[ $k ] = $v['local_value'];
		}
	}
	return $values;
}

/**
 * Return all php variables in php.ini format without sections.
 *
 * @return string
 */
function php_variables_ini( bool $include_nulls = true, int $spacing = 40 ): string {
	$variables = php_variables( $include_nulls );

	$out            = '';
	$spacing_string = '%-' . $spacing . 's';
	foreach ( $variables as $k => $v ) {
		if ( is_null( $v ) ) {
			$out .= sprintf( $spacing_string . " = \n", $k );
		} elseif ( is_numeric( $v ) ) {
			$out .= sprintf( $spacing_string . " = %s\n", $k, $v );
		} else {
			$out .= sprintf( $spacing_string . " = \"%s\"\n", $k, $v );
		}
	}
	$out .= "\n";

	return $out;
}

/**
 * Return all php variables in php.ini format with sections.
 *
 * @return string
 */
function php_variables_ini_sectioned( bool $include_nulls = true, int $spacing = 40 ): string {
	$a = php_variables( $include_nulls );

	$section_data = [];
	foreach ( array_keys( $a ) as $k ) {
		$parts = explode( '.', $k );
		if ( count( $parts ) == 1 ) {
			$sec     = 'PHP';
			$setting = $k;
		} else {
			$sec     = $parts[0];
			$setting = $parts[1];
		}
		$section_data[ $sec ][ $setting ] = $a[ $k ];
	}
	ksort( $section_data, SORT_NATURAL | SORT_FLAG_CASE );

	$out = '';
	foreach ( $section_data as $sec => $data ) {
		$out .= "[$sec]\n";

		ksort( $data, SORT_NATURAL | SORT_FLAG_CASE );
		foreach ( $data as $k => $v ) {
			if ( is_null( $v ) ) {
				$out .= sprintf( "%-40s = \n", $k );
			} elseif ( is_numeric( $v ) ) {
				$out .= sprintf( "%-40s = %s\n", $k, $v );
			} elseif ( is_string( $v ) ) {
				$out .= sprintf( "%-40s = \"%s\"\n", $k, $v );
			} else {
				throw new \Exception( "unknown type for $k" );
			}
		}
		$out .= "\n";
	}
	return $out;
}
