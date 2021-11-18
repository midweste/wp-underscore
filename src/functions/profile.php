<?php

namespace _;

function function_profile(callable $function, int $iterations = 1)
{
    $start = microtime(true);
    for ($i = 1; $i <= $iterations; $i++) {
        $function();
    }
    return microtime(true) - $start;
}

function function_name(string $function): string
{
    if (strpos($function, '\\') === false) {
        return $function;
    }
    $parts = explode('\\', $function);
    $count = count($parts);
    if ($count < 2) {
        return $function;
    }
    return $parts[$count - 1];
}
