<?php

namespace _;

/**
 * Return DateTimeZone in site timezone or America/Los_Angeles
 */
function datetimezone(string $default = 'America/Los_Angeles'): \DateTimeZone
{
    return new \DateTimeZone(get_option('timezone_string', $default));
}

function strtotime_timezoned(string $strtotime, string $format = 'U', string $timezone = '')
{
    $zone = ($timezone) ? $timezone : get_option('timezone_string', 'GMT');
    $dt = new \DateTime('@' . strtotime($strtotime));
    $dt->setTimeZone($zone);
    return $dt->format($format);
}
