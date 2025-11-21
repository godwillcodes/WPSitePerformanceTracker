<?php
/**
 * Tests for Audit Repository
 *
 * @package PerfAuditPro
 */

class Audit_Repository_Test extends WP_UnitTestCase {

    /**
     * @var \PerfAuditPro\Database\Audit_Repository
     */
    private $repository;

    public function setUp(): void {
        parent::setUp();
        require_once dirname(dirname(dirname(__FILE__))) . '/includes/database/class-audit-repository.php';
        require_once dirname(dirname(dirname(__FILE__))) . '/includes/database/class-schema.php';
        \PerfAuditPro\Database\Schema::create_tables();
        $this->repository = new \PerfAuditPro\Database\Audit_Repository();
    }

    public function test_create_synthetic_audit() {
        $audit_id = $this->repository->create_synthetic_audit('https://example.com', 'lighthouse');

        $this->assertIsInt($audit_id);
        $this->assertGreaterThan(0, $audit_id);
    }

    public function test_update_audit_results() {
        $audit_id = $this->repository->create_synthetic_audit('https://example.com', 'lighthouse');

        $results = array(
            'performance_score' => 85.5,
            'first_contentful_paint' => 1200,
            'largest_contentful_paint' => 2000,
            'total_blocking_time' => 300,
            'cumulative_layout_shift' => 0.1,
        );

        $result = $this->repository->update_audit_results($audit_id, $results);

        $this->assertNotInstanceOf('WP_Error', $result);
        $this->assertTrue($result);
    }

    public function test_get_recent_audits() {
        $this->repository->create_synthetic_audit('https://example.com', 'lighthouse');
        $this->repository->create_synthetic_audit('https://example.com/page', 'lighthouse');

        $audits = $this->repository->get_recent_audits(null, 10);

        $this->assertIsArray($audits);
        $this->assertGreaterThanOrEqual(2, count($audits));
    }

    public function test_get_recent_audits_with_url_filter() {
        $this->repository->create_synthetic_audit('https://example.com', 'lighthouse');
        $this->repository->create_synthetic_audit('https://example.com/page', 'lighthouse');

        $audits = $this->repository->get_recent_audits('https://example.com', 10);

        $this->assertIsArray($audits);
        foreach ($audits as $audit) {
            $this->assertEquals('https://example.com', $audit['url']);
        }
    }
}

