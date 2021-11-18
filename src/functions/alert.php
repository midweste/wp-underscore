<?php

namespace _;

function alert_admin_logger()
{
    return logger_session('admin');
}

function alert_admin(string $level, string $message, array $context = []): void
{
    alert_admin_logger()->log($level, $message, (array) $context);
}

function alert_admin_display(): void
{
    $logger = alert_admin_logger();
    $logs = $logger->get(function ($entry) use ($logger) {
        return $logger->formatter($entry);
    });
    $html = '<div class="notice notice-info fade"><p>' . implode('<br/>', $logs) . '</p></div>';
    echo $html;
    alert_admin_clear();
}

function alert_admin_clear(): void
{
    alert_admin_logger()->clear();
}
