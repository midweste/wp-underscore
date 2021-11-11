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
