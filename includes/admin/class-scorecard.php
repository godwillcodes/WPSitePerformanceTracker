<?php
/**
 * Performance scorecard
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

namespace PerfAuditPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Scorecard {

    /**
     * Get overall performance scorecard
     *
     * @return array
     */
    public static function get_scorecard() {
        require_once PERFAUDIT_PRO_PLUGIN_DIR . 'includes/database/class-audit-repository.php';
        $repository = new \PerfAuditPro\Database\Audit_Repository();

        $recent_audits = $repository->get_recent_audits(null, 30);
        $completed_audits = array_filter($recent_audits, function($a) {
            return $a['status'] === 'completed' && !empty($a['performance_score']);
        });

        // Reindex array to ensure sequential keys
        $completed_audits = array_values($completed_audits);

        if (empty($completed_audits)) {
            return array(
                'grade' => 'N/A',
                'score' => 0,
                'trend' => 'stable',
                'status' => 'no_data',
                'message' => 'No completed audits yet',
                'audit_count' => 0,
            );
        }

        $scores = array_map(function($a) {
            return floatval($a['performance_score']);
        }, $completed_audits);

        $avg_score = array_sum($scores) / count($scores);
        $latest_score = floatval($completed_audits[0]['performance_score']);

        $grade = self::calculate_grade($avg_score);
        $trend = self::calculate_trend($scores);

        return array(
            'grade' => $grade,
            'score' => round($avg_score, 1),
            'latest_score' => round($latest_score, 1),
            'trend' => $trend,
            'status' => $avg_score >= 90 ? 'excellent' : ($avg_score >= 70 ? 'good' : ($avg_score >= 50 ? 'needs_improvement' : 'poor')),
            'audit_count' => count($completed_audits),
        );
    }

    /**
     * Calculate grade from score
     */
    private static function calculate_grade($score) {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    /**
     * Calculate trend
     */
    private static function calculate_trend($scores) {
        if (count($scores) < 2) return 'stable';

        $recent = array_slice($scores, 0, 5);
        $older = array_slice($scores, 5, 5);

        if (empty($older)) return 'stable';

        $recent_avg = array_sum($recent) / count($recent);
        $older_avg = array_sum($older) / count($older);

        $diff = $recent_avg - $older_avg;

        if ($diff > 5) return 'improving';
        if ($diff < -5) return 'declining';
        return 'stable';
    }
}

