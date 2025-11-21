<?php
/**
 * RUM repository for database operations
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Database
 */

namespace PerfAuditPro\Database;

if (!defined('ABSPATH')) {
    exit;
}

class RUM_Repository {

    /**
     * Add RUM metric
     *
     * @param string $url URL
     * @param array $metrics Metrics array
     * @return bool|\WP_Error
     */
    public function add_metric($url, $metrics) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_rum_metrics';
        $date = current_time('Y-m-d');

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE url = %s AND date = %s",
            $url,
            $date
        ));

        $metric_values = $this->extract_metrics($metrics);

        if ($existing) {
            return $this->update_aggregated_metrics($existing->id, $metric_values);
        } else {
            return $this->create_aggregated_metrics($url, $date, $metric_values);
        }
    }

    /**
     * Extract metrics from raw data
     *
     * @param array $raw_metrics Raw metrics
     * @return array Extracted metrics
     */
    private function extract_metrics($raw_metrics) {
        $extracted = array();

        if (isset($raw_metrics['lcp'])) {
            $extracted['lcp'] = floatval($raw_metrics['lcp']);
        }
        if (isset($raw_metrics['fid'])) {
            $extracted['fid'] = floatval($raw_metrics['fid']);
        }
        if (isset($raw_metrics['cls'])) {
            $extracted['cls'] = floatval($raw_metrics['cls']);
        }
        if (isset($raw_metrics['fcp'])) {
            $extracted['fcp'] = floatval($raw_metrics['fcp']);
        }
        if (isset($raw_metrics['ttfb'])) {
            $extracted['ttfb'] = floatval($raw_metrics['ttfb']);
        }

        return $extracted;
    }

    /**
     * Create aggregated metrics record
     *
     * @param string $url URL
     * @param string $date Date
     * @param array $metrics Metrics
     * @return bool|\WP_Error
     */
    private function create_aggregated_metrics($url, $date, $metrics) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_rum_metrics';

        $data = array(
            'url' => $url,
            'date' => $date,
            'page_views' => 1,
        );

        $format = array('%s', '%s', '%d');

        foreach ($metrics as $key => $value) {
            $data['avg_' . $key] = $value;
            $data['p75_' . $key] = $value;
            $format[] = '%f';
            $format[] = '%f';
        }

        $result = $wpdb->insert($table_name, $data, $format);

        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create RUM metrics', array('status' => 500));
        }

        return true;
    }

    /**
     * Update aggregated metrics
     *
     * @param int $id Record ID
     * @param array $metrics New metrics
     * @return bool|\WP_Error
     */
    private function update_aggregated_metrics($id, $metrics) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_rum_metrics';

        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);

        if (!$existing) {
            return new \WP_Error('not_found', 'Metrics record not found', array('status' => 404));
        }

        $page_views = intval($existing['page_views']) + 1;

        $data = array('page_views' => $page_views);
        $format = array('%d');

        foreach ($metrics as $key => $value) {
            $avg_key = 'avg_' . $key;
            $p75_key = 'p75_' . $key;

            $current_avg = floatval($existing[$avg_key]);
            $new_avg = (($current_avg * ($page_views - 1)) + $value) / $page_views;

            $data[$avg_key] = $new_avg;

            if ($value > floatval($existing[$p75_key])) {
                $data[$p75_key] = $value;
            }

            $format[] = '%f';
            $format[] = '%f';
        }

        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $id),
            $format,
            array('%d')
        );

        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to update RUM metrics', array('status' => 500));
        }

        return true;
    }

    /**
     * Get aggregated metrics
     *
     * @param string|null $url Optional URL filter
     * @param int $days Number of days
     * @return array
     */
    public function get_aggregated_metrics($url = null, $days = 30) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'perfaudit_rum_metrics';
        $start_date = date('Y-m-d', strtotime("-{$days} days"));

        $where = $wpdb->prepare(' WHERE date >= %s', $start_date);
        if ($url) {
            $where .= $wpdb->prepare(' AND url = %s', $url);
        }

        $query = "SELECT * FROM $table_name $where ORDER BY date DESC";
        return $wpdb->get_results($query, ARRAY_A);
    }
}

