# Extending PerfAudit Pro

This document explains how to extend PerfAudit Pro with custom functionality.

## Plugin Structure

```
perfaudit-pro/
├── includes/
│   ├── admin/          # Admin dashboard
│   ├── api/            # REST API endpoints
│   ├── core/           # Core plugin functionality
│   ├── cron/           # Cron scheduling
│   ├── database/       # Database repositories
│   ├── frontend/       # Frontend scripts
│   ├── rules/          # Rules engine
│   └── security/       # Security utilities
├── assets/
│   ├── css/            # Stylesheets
│   └── js/             # JavaScript files
└── tests/              # PHPUnit tests
```

## Adding Custom Rules

Extend the rules engine to add custom performance rules:

```php
add_filter('perfaudit_pro_rules', function($rules) {
    $rules[] = array(
        'metric' => 'custom_metric',
        'threshold' => 1000,
        'operator' => 'lt',
        'enforcement' => 'hard',
    );
    return $rules;
});
```

## Adding Custom Actions

Add custom enforcement actions:

```php
add_filter('perfaudit_pro_actions', function($actions) {
    $actions[] = array(
        'type' => 'custom_webhook',
        'url' => 'https://example.com/webhook',
    );
    return $actions;
});

add_action('perfaudit_pro_execute_action', function($action, $results) {
    if ($action['type'] === 'custom_webhook') {
        // Custom action logic
    }
}, 10, 2);
```

## Custom REST Endpoints

Add custom REST API endpoints:

```php
add_action('rest_api_init', function() {
    register_rest_route('perfaudit-pro/v1', '/custom-endpoint', array(
        'methods' => 'GET',
        'callback' => 'your_callback_function',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ));
});
```

## Custom Database Tables

To add custom database tables, extend the Schema class:

```php
add_action('perfaudit_pro_create_tables', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'perfaudit_custom';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        data text,
        PRIMARY KEY (id)
    ) " . $wpdb->get_charset_collate();
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
});
```

## Hooks and Filters

### Actions

- `perfaudit_pro_audit_completed` - Fired when audit completes
  - Parameters: `$audit_id`, `$results`
- `perfaudit_pro_rum_metric_recorded` - Fired when RUM metric is recorded
  - Parameters: `$url`, `$metrics`
- `perfaudit_pro_rule_violation` - Fired when rule violation detected
  - Parameters: `$violation`, `$results`

### Filters

- `perfaudit_pro_rules` - Modify rules before evaluation
  - Parameters: `$rules` (array)
- `perfaudit_pro_actions` - Modify actions before execution
  - Parameters: `$actions` (array)
- `perfaudit_pro_rate_limit` - Modify rate limit
  - Parameters: `$limit` (int), `$endpoint` (string)
- `perfaudit_pro_audit_results` - Modify audit results before storage
  - Parameters: `$results` (array), `$audit_id` (int)

## Namespace

All plugin classes are namespaced under `PerfAuditPro\`. When extending, maintain this namespace structure:

```php
namespace PerfAuditPro\Extensions;

class Custom_Feature {
    // Your code
}
```

## Best Practices

1. **Security**: Always sanitize inputs and escape outputs
2. **Database**: Use prepared statements for all queries
3. **Capabilities**: Check user capabilities before allowing actions
4. **Nonces**: Use nonces for all forms and AJAX requests
5. **Hooks**: Use WordPress hooks instead of modifying core files
6. **Testing**: Write unit tests for custom functionality

## Example Extension

See `examples/custom-extension.php` for a complete example of extending the plugin.

