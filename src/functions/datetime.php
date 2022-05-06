<?php

namespace _;

/**
 * Return DateTimeZone in site timezone or America/Los_Angeles
 */
function datetimezone(string $default = 'America/Los_Angeles'): \DateTimeZone
{
    return new \DateTimeZone(get_option('timezone_string', $default));
}

function strtotime_timezoned(string $strtotime, string $timezone = '')
{
    $gmt_dt = new \DateTime('@' . strtotime($strtotime)); // @timestamp always in GMT
    $gmt_timestamp = $gmt_dt->format('U');
    $desired_zone = ($timezone) ? $timezone : get_option('timezone_string', 'GMT');
    $gmt_dt->setTimezone(new \DateTimeZone($desired_zone));
    $seconds_offset = $gmt_dt->format('Z');
    return $gmt_timestamp - $seconds_offset;
}
