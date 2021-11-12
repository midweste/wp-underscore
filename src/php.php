<?php

namespace _;

/**
  * Return all php variables in php.ini format.
  *
  * @return string
  */
function php_ini() {
    $a = ini_get_all();

    $data = [];
    foreach (array_keys($a) as $k) {
        $ss = split("\.", $k);
        if (count($ss) == 1) {
            $sec = "PHP";
            $v = $k;
        } else {
            $sec = $ss[0];
            $v = $ss[1];
        }
        $data[$sec][$v] = $a[$k]['global_value'];
    }
    ksort($data);
    $out = "";
    foreach ($data as $sec => $data) {
        $out .= "[$sec]\n";
        ksort($data);
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                $out .= sprintf("%-40s = %s\n", $k, $v);
            } else {
                $out .= sprintf("%-40s = \"%s\"\n", $k, $v);
            }
        }
        $out .= "\n";
    }
    return $out;
}
