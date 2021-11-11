<?php

namespace _;

if (!defined('WPINC')) {
    die;
}

define('WPUNDERSCORE', dirname(__FILE__));

call_user_func(function () {
    require_once WPUNDERSCORE . '/vendor/autoload.php';
    foreach (glob(WPUNDERSCORE . '/src/*.php') as $autoload) {
        require_once $autoload;
    }
});
