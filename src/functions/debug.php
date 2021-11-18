<?php

namespace _;

function debug($variable): void
{
    if (!user_is_admin()) {
        return;
    }
    d($variable);
}

function debug_inline($variable): string
{
    if (!user_is_admin()) {
        return '';
    }
    \Kint\Renderer\RichRenderer::$folder = false;
    return @d($variable);
}
