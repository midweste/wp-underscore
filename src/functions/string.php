<?php

namespace _;

function str_replace_start(string $search, string $replacement, string $subject): string
{
    return preg_replace('/^' . preg_quote($search, '/') . '/', $replacement, $subject, 1);
}

function str_replace_end(string $search, string $replacement, string $subject): string
{
    return preg_replace('/' . preg_quote($search, '/') . '$/', $replacement, $subject, 1);
}

function str_trim_start(string $search, string $subject)
{
    return str_replace_start($search, '', $subject);
}

function str_trim_end(string $search, string $subject)
{
    return str_replace_end($search, '', $subject);
}

function str_trim_ends(string $start_replace, string $end_replace, string $subject)
{
    return str_trim_start($start_replace, str_trim_end($end_replace, $subject));
}
