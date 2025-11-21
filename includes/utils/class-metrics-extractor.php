<?php
/**
 * Metrics extraction utility
 *
 * Pure functions for extracting and transforming performance metrics.
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
 * Metrics extractor for transforming API responses
 */
class Metrics_Extractor {

    /**
     * Extract metrics from PageSpeed Insights API response
     *
     * Pure function that extracts performance metrics from Google PageSpeed Insights JSON.
     *
     * @param array<string, mixed> $api_response Raw API response
     * @return array<string, mixed> Extracted metrics
     */
    public static function extract_from_pagespeed(array $api_response): array {
        $metrics = array();

        if (!isset($api_response['lighthouseResult']) || !isset($api_response['lighthouseResult']['categories'])) {
            return $metrics;
        }

        $lhr = $api_response['lighthouseResult'];
        $categories = $lhr['categories'] ?? array();
        $audits = $lhr['audits'] ?? array();

        // Performance score
        if (isset($categories['performance']['score'])) {
            $metrics['performance_score'] = (float) $categories['performance']['score'] * 100;
        }

        // Core Web Vitals and other metrics
        $metric_mappings = array(
            'first-contentful-paint' => 'first_contentful_paint',
            'largest-contentful-paint' => 'largest_contentful_paint',
            'total-blocking-time' => 'total_blocking_time',
            'cumulative-layout-shift' => 'cumulative_layout_shift',
            'speed-index' => 'speed_index',
            'interactive' => 'time_to_interactive',
        );

        foreach ($metric_mappings as $lighthouse_key => $metric_key) {
            if (isset($audits[$lighthouse_key]['numericValue'])) {
                $value = (float) $audits[$lighthouse_key]['numericValue'];
                // Convert to milliseconds for time-based metrics (except CLS)
                if ($lighthouse_key !== 'cumulative-layout-shift' && $value > 0) {
                    $value = $value; // Already in milliseconds from PageSpeed
                }
                $metrics[$metric_key] = $value;
            }
        }

        return $metrics;
    }

    /**
     * Validate extracted metrics
     *
     * Ensures all metric values are within reasonable bounds.
     *
     * @param array<string, mixed> $metrics Extracted metrics
     * @return array<string, float> Validated metrics
     */
    public static function validate_metrics(array $metrics): array {
        $validated = array();

        $constraints = array(
            'performance_score' => array('min' => 0.0, 'max' => 100.0),
            'first_contentful_paint' => array('min' => 0.0, 'max' => 60000.0),
            'largest_contentful_paint' => array('min' => 0.0, 'max' => 60000.0),
            'total_blocking_time' => array('min' => 0.0, 'max' => 10000.0),
            'cumulative_layout_shift' => array('min' => 0.0, 'max' => 10.0),
            'speed_index' => array('min' => 0.0, 'max' => 60000.0),
            'time_to_interactive' => array('min' => 0.0, 'max' => 120000.0),
        );

        foreach ($metrics as $key => $value) {
            if (!isset($constraints[$key])) {
                continue;
            }

            $float_value = (float) $value;
            $constraint = $constraints[$key];

            // Clamp value to constraints
            if (isset($constraint['min']) && $float_value < $constraint['min']) {
                $float_value = $constraint['min'];
            }
            if (isset($constraint['max']) && $float_value > $constraint['max']) {
                $float_value = $constraint['max'];
            }

            $validated[$key] = $float_value;
        }

        return $validated;
    }
}

