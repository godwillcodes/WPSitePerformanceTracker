<?php
/**
 * Logger utility for PerfAudit Pro
 *
 * Provides structured logging with different log levels and proper error handling.
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Utils
 * @since 1.0.0
 */

namespace PerfAuditPro\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger class for structured logging
 */
class Logger {

    /**
     * Log levels
     */
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';

    /**
     * Log a message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context data
     * @return void
     */
    public static function log(string $level, string $message, array $context = []): void {
        // Only log if WP_DEBUG is enabled and WP_DEBUG_LOG is also enabled
        if (!defined('WP_DEBUG') || !WP_DEBUG || !defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }

        $log_entry = self::format_log_entry($level, $message, $context);
        
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- error_log() is conditionally used only when WP_DEBUG_LOG is enabled
        if (function_exists('error_log')) {
            error_log($log_entry);
        }
    }

    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function debug(string $message, array $context = []): void {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function info(string $message, array $context = []): void {
        self::log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function warning(string $message, array $context = []): void {
        self::log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function error(string $message, array $context = []): void {
        self::log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log critical message
     *
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    public static function critical(string $message, array $context = []): void {
        self::log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Format log entry
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array<string, mixed> $context Additional context
     * @return string Formatted log entry
     */
    private static function format_log_entry(string $level, string $message, array $context): string {
        $timestamp = current_time('mysql');
        $prefix = '[PerfAudit Pro]';
        
        $entry = sprintf(
            '%s [%s] %s: %s',
            $prefix,
            strtoupper($level),
            $timestamp,
            $message
        );

        if (!empty($context)) {
            $entry .= ' | Context: ' . wp_json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        return $entry;
    }
}

