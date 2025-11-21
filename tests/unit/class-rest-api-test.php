<?php
/**
 * Tests for REST API
 *
 * @package PerfAuditPro
 */

class Rest_API_Test extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        require_once dirname(dirname(dirname(__FILE__))) . '/includes/api/class-rest-api.php';
        require_once dirname(dirname(dirname(__FILE__))) . '/includes/database/class-audit-repository.php';
        require_once dirname(dirname(dirname(__FILE__))) . '/includes/database/class-rum-repository.php';
        \PerfAuditPro\API\Rest_API::init();
    }

    public function test_validate_url() {
        $reflection = new ReflectionClass('\PerfAuditPro\API\Rest_API');
        $method = $reflection->getMethod('validate_url');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null, 'https://example.com'));
        $this->assertTrue($method->invoke(null, 'http://example.com'));
        $this->assertFalse($method->invoke(null, 'not-a-url'));
        $this->assertFalse($method->invoke(null, ''));
    }

    public function test_check_capability() {
        $user_id = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($user_id);

        $reflection = new ReflectionClass('\PerfAuditPro\API\Rest_API');
        $method = $reflection->getMethod('check_capability');
        $method->setAccessible(true);

        $request = new WP_REST_Request();
        $this->assertTrue($method->invoke(null, $request));
    }

    public function test_check_capability_insufficient_permissions() {
        $user_id = $this->factory->user->create(array('role' => 'subscriber'));
        wp_set_current_user($user_id);

        $reflection = new ReflectionClass('\PerfAuditPro\API\Rest_API');
        $method = $reflection->getMethod('check_capability');
        $method->setAccessible(true);

        $request = new WP_REST_Request();
        $this->assertFalse($method->invoke(null, $request));
    }
}

