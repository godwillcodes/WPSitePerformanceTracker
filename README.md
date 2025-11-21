# Site Performance Tracker

A WordPress performance governance plugin that performs automated website performance audits using synthetic lab audits (Lighthouse/Puppeteer) and Real User Monitoring (RUM) using web-vitals.

**Repository**: [https://github.com/godwillcodes/PerfAuditPro](https://github.com/godwillcodes/PerfAuditPro)

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
- Built-in PHP worker (no external dependencies required)

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

This plugin is licensed under the GPLv2 or later.

```
Copyright (C) 2024 Godwill Barasa

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
```

