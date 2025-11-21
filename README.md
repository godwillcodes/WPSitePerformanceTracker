# PerfAudit Pro

A WordPress performance governance plugin that performs automated website performance audits using synthetic lab audits (Lighthouse/Puppeteer) and Real User Monitoring (RUM) using web-vitals.

## Features

- **Synthetic Lab Audits**: Automated Lighthouse/Puppeteer-based performance testing
- **Real User Monitoring (RUM)**: Web Vitals collection from actual user sessions
- **Performance Rules Engine**: Configurable thresholds and automated enforcement
- **Admin Dashboard**: Visual analytics with charts and metrics
- **REST API**: Secure endpoints for audit intake and data retrieval
- **Cron Scheduling**: Automated audit execution via WP-Cron

## Requirements

- PHP 8.0+
- WordPress 6.0+
- WP-CLI compatible
- External worker for synthetic audits (not executed within WordPress)

## Installation

1. Upload the plugin to `/wp-content/plugins/perfaudit-pro/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings in the admin dashboard

## Security

All code follows WordPress security standards:
- Sanitization and escaping
- Type-safe comparisons
- Prepared statements
- Permission checks
- CSRF protection

## License

Proprietary

