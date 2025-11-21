<?php
/**
 * Tests for Rules Engine
 *
 * @package PerfAuditPro
 */

class Rules_Engine_Test extends WP_UnitTestCase {

    /**
     * @var \PerfAuditPro\Rules\Rules_Engine
     */
    private $engine;

    public function setUp(): void {
        parent::setUp();
        require_once dirname(dirname(dirname(__FILE__))) . '/includes/rules/class-rules-engine.php';
        $this->engine = new \PerfAuditPro\Rules\Rules_Engine();
    }

    public function test_evaluate_passing_metrics() {
        $metrics = array(
            'lcp' => 2000,
            'fid' => 50,
            'cls' => 0.1,
        );

        $rules = array(
            array(
                'metric' => 'lcp',
                'threshold' => 2500,
                'operator' => 'lt',
                'enforcement' => 'hard',
            ),
        );

        $result = $this->engine->evaluate($metrics, $rules);

        $this->assertTrue($result['passed']);
        $this->assertEmpty($result['violations']);
    }

    public function test_evaluate_failing_metrics() {
        $metrics = array(
            'lcp' => 3000,
            'fid' => 50,
            'cls' => 0.1,
        );

        $rules = array(
            array(
                'metric' => 'lcp',
                'threshold' => 2500,
                'operator' => 'lt',
                'enforcement' => 'hard',
            ),
        );

        $result = $this->engine->evaluate($metrics, $rules);

        $this->assertFalse($result['passed']);
        $this->assertNotEmpty($result['violations']);
        $this->assertEquals('lcp', $result['violations'][0]['metric']);
    }

    public function test_evaluate_warning_enforcement() {
        $metrics = array(
            'lcp' => 3000,
        );

        $rules = array(
            array(
                'metric' => 'lcp',
                'threshold' => 2500,
                'operator' => 'lt',
                'enforcement' => 'soft',
            ),
        );

        $result = $this->engine->evaluate($metrics, $rules);

        $this->assertTrue($result['passed']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_compare_operators() {
        $reflection = new ReflectionClass($this->engine);
        $method = $reflection->getMethod('compare');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->engine, 10, 5, 'gt'));
        $this->assertTrue($method->invoke($this->engine, 10, 10, 'gte'));
        $this->assertTrue($method->invoke($this->engine, 5, 10, 'lt'));
        $this->assertTrue($method->invoke($this->engine, 10, 10, 'lte'));
        $this->assertTrue($method->invoke($this->engine, 10, 10, 'eq'));
        $this->assertTrue($method->invoke($this->engine, 10, 5, 'neq'));
    }
}

