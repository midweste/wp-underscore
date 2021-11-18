<?php

namespace _;

function array_to_ul_li(array $array)
{
    if (empty($array)) return '';
    $output = '<ul>';
    foreach ($array as $key => $subArray) {
        $output .= '<li>' . $key . array_to_ul_li($subArray) . '</li>';
    }
    $output .= '</ul>';
    return $output;
}

function array_replace_recursive_value(array $array, $search, $replace)
{
    array_walk_recursive($array, function (&$value) use ($search, $replace) {
        if ($value === $search) {
            $value = $replace;
        }
    });
    return $array;
}
