<?php

namespace _;

function array_to_ul_li( array $array ) {
	if ( empty( $array ) ) {
		return '';
	}
	$output = '<ul>';
	foreach ( $array as $key => $subArray ) {
		$output .= '<li>' . $key . array_to_ul_li( $subArray ) . '</li>';
	}
	$output .= '</ul>';
	return $output;
}

function array_replace_value_recursive( array $array, $search, $replace ) {
	if ( is_array( $search ) ) {
		throw new \Exception( 'Argument 2 passed to _\array_replace_value_recursive() must NOT be of the type array' );
	}
	if ( is_array( $replace ) ) {
		throw new \Exception( 'Argument 3 passed to _\array_replace_value_recursive() must NOT be of the type array' );
	}
	if ( $search === $replace ) {
		return $array;
	}
	return array_replace_values_recursive( $array, (array) $search, (array) $replace );
}

function array_replace_values_recursive( array $array, array $searches, array $replacements = [] ) {
	array_walk_recursive($array, function ( &$value ) use ( $searches, $replacements ) {
		do {
			$key = array_search( $value, $searches, true );
			if ( $key !== false ) {
				if ( $value === $replacements[ $key ] ) { // guard against replacement loop
					break;
				}
				$value = ( isset( $replacements[ $key ] ) ) ? $replacements[ $key ] : null;
			}
		} while ( $key !== false );
	});
	return $array;
}

function array_to_php( array $array ) {
	$out = '[' . PHP_EOL;
	foreach ( $array as $k => $v ) {
		if ( is_bool( $v ) ) {
			$bln  = ( $v === true ) ? 'true' : 'false';
			$out .= "'$k' => $bln," . PHP_EOL;
		} elseif ( is_int( $v ) ) {
			$out .= "'$k' => $v," . PHP_EOL;
		} elseif ( is_array( $v ) ) {
			$out .= "'$k' => " . PHP_EOL;
			$out .= array_to_php( $v );
			$out .= ',' . PHP_EOL;
		} else {
			$out .= "'$k' => '$v'," . PHP_EOL;
		}
	}
	$out = $out . ']' . PHP_EOL;
	return $out;
}
