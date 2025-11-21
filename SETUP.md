# PerfAudit Pro - Setup Instructions

## Installation

1. Upload the plugin to `/wp-content/plugins/perfaudit-pro/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create the required database tables upon activation

## Configuration

### API Token Setup

For external workers to submit audit results, you need to configure an API token:

1. Generate a secure token (recommended: 32+ character random string)
2. Add the token to WordPress options:
   ```php
   update_option('perfaudit_pro_api_token', 'your-secure-token-here');
   ```
3. Use this token in the `X-PerfAudit-Token` header when calling the synthetic audit endpoint

### REST API Endpoints

#### Synthetic Audit Intake
- **URL**: `/wp-json/perfaudit-pro/v1/synthetic-audit`
- **Method**: POST
- **Authentication**: Token-based (X-PerfAudit-Token header)
- **Parameters**:
  - `url` (required): URL to audit
  - `audit_type` (optional): Type of audit (default: 'lighthouse')

#### RUM Intake
- **URL**: `/wp-json/perfaudit-pro/v1/rum-intake`
- **Method**: POST
- **Authentication**: Nonce-based
- **Parameters**:
  - `url` (required): Page URL
  - `metrics` (required): Object containing web vitals metrics

#### Get Audit Results
- **URL**: `/wp-json/perfaudit-pro/v1/audit-results`
- **Method**: GET
- **Authentication**: User capability (manage_options)
- **Parameters**:
  - `url` (optional): Filter by URL
  - `limit` (optional): Number of results (default: 10, max: 100)

#### Get RUM Metrics
- **URL**: `/wp-json/perfaudit-pro/v1/rum-metrics`
- **Method**: GET
- **Authentication**: User capability (manage_options)
- **Parameters**:
  - `url` (optional): Filter by URL
  - `days` (optional): Number of days (default: 30, max: 365)

## Database Tables

The plugin creates three custom tables:

1. `wp_perfaudit_synthetic_audits` - Stores synthetic audit results
2. `wp_perfaudit_lighthouse_json` - Stores full Lighthouse JSON reports
3. `wp_perfaudit_rum_metrics` - Stores aggregated RUM metrics

## Cron Scheduling

The plugin uses WP-Cron to process pending audits hourly. The cron event `perfaudit_pro_run_audit` is automatically scheduled upon plugin activation.

## Requirements

- PHP 8.0 or higher
- WordPress 6.0 or higher
- MySQL 5.6 or higher (for foreign key support)

