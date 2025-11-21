# Site Performance Tracker

**Contributors:** godwillcodes  
**Tags:** performance, monitoring, page speed, core web vitals, lighthouse  
**Requires at least:** 6.0  
**Tested up to:** 6.8  
**Stable tag:** 1.0.0  
**Requires PHP:** 8.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

> WordPress performance monitoring plugin with Google PageSpeed Insights API and Real User Monitoring.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange.svg)](https://github.com/godwillcodes/PerfAuditPro)

Site Performance Tracker is an enterprise-grade WordPress plugin that provides comprehensive website performance monitoring through automated synthetic audits and real user metrics. Built with Google PageSpeed Insights API integration, it delivers actionable insights to help you optimize your site's speed, Core Web Vitals, and overall user experience.

## 🚀 Features

### Core Capabilities

- **Automated Synthetic Audits**: Run performance audits on any URL using Google PageSpeed Insights API with desktop and mobile device testing
- **Real User Monitoring (RUM)**: Collect and analyze Core Web Vitals (LCP, CLS, FID, FCP, TTFB) from actual user sessions
- **PHP-Based Worker**: Built-in background worker that processes audits automatically—no Node.js or external dependencies required
- **Performance Scorecard**: Visual dashboard with circular progress indicators showing overall performance scores and trends
- **Score Distribution Analysis**: Track performance score distribution across different ranges (Excellent, Good, Needs Improvement, Poor)
- **Comprehensive Metrics Tracking**: Monitor all Core Web Vitals with detailed visualizations and recommendations

### Dashboard & Analytics

- **Modern Admin Dashboard**: Professional, minimalistic interface with real-time performance metrics
- **Circular Progress Indicators**: PageSpeed Insights-style visualizations for all metrics
- **Performance Trends**: Track performance improvements or declines over time
- **Actionable Recommendations**: Get specific, prioritized recommendations to improve your site's performance
- **Export Capabilities**: Export audit data as CSV or detailed HTML reports

### Advanced Features

- **Data Retention Management**: Configure automatic cleanup of old audit and RUM data
- **Bulk Operations**: Manage multiple audits efficiently
- **REST API Integration**: Secure endpoints for programmatic access to audit data
- **Worker Status Management**: Start, stop, and monitor the background audit processing worker
- **Stuck Audit Recovery**: Automatically detect and retry audits stuck in processing
- **Immediate Processing**: Process audits on-demand without waiting for scheduled checks

## 📋 Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 8.0 or higher
- **Google PageSpeed Insights API Key**: Required for synthetic audits (configure in Settings)
- **Browser**: Modern browser with JavaScript enabled (for admin dashboard)

### Optional

- **WP-CLI**: For command-line operations (optional)
- **Cron Support**: For scheduled audits (optional, WP-Cron compatible)

## 📦 Installation

### From WordPress Admin

1. Download the plugin ZIP file
2. Navigate to **Plugins → Add New** in your WordPress admin
3. Click **Upload Plugin** and select the ZIP file
4. Click **Install Now**
5. Click **Activate Plugin**

### Manual Installation

1. Upload the `AuditPulse` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **Site Performance Tracker** in the admin menu to configure

### Post-Installation

After activation, the plugin will:
- Automatically create required database tables
- Set up default configuration options
- Initialize the worker system

## ⚙️ Configuration

### Initial Setup

1. **Navigate to Settings**: Go to **Site Performance Tracker → Settings**
2. **Configure API Key**: Enter your Google PageSpeed Insights API key
   - Get your API key from [Google Cloud Console](https://console.cloud.google.com/)
   - Enable the PageSpeed Insights API for your project
3. **Enable RUM Tracking** (Optional): Toggle Real User Monitoring if you want to collect metrics from actual visitors
4. **Configure Data Retention**: Set how long to keep audit and RUM data

### Worker Configuration

The plugin includes a built-in PHP worker that processes audits automatically:

- **Start Worker**: Click "Start Worker" in the Worker Status card to begin processing audits
- **Automatic Processing**: The worker checks for pending audits every 30 seconds
- **Manual Processing**: Use "Process Now" to immediately process all pending audits
- **Stuck Audit Recovery**: Use "Retry Stuck Audits" if audits are stuck in processing

## 🎯 Usage

### Creating an Audit

1. Navigate to **Site Performance Tracker** in your WordPress admin
2. In the "Create New Audit" card, enter the URL you want to audit
3. Select **Desktop** or **Mobile** device type
4. Click **Create Audit**
5. The audit will be queued and processed automatically by the worker

### Viewing Results

- **Performance Scorecard**: See your overall performance score and trend at the top of the dashboard
- **Score Distribution**: View how your audits are distributed across performance ranges
- **Core Web Vitals**: Check individual metrics (LCP, CLS, FID, FCP, TTFB) with circular progress indicators
- **Recent Audits Table**: Browse all audits with filtering and search capabilities

### Exporting Data

1. Use the filters in the Recent Audits section to narrow down results
2. Click **Export CSV** to download audit data as a spreadsheet
3. Click **Export Report** to generate a detailed HTML performance report

## 🔌 REST API

The plugin provides secure REST API endpoints for programmatic access:

### Endpoints

- `GET /wp-json/perfaudit-pro/v1/audit-results` - Retrieve audit results
- `GET /wp-json/perfaudit-pro/v1/rum-metrics` - Retrieve RUM metrics
- `POST /wp-json/perfaudit-pro/v1/create-audit` - Create a new audit

All endpoints require proper authentication and user capabilities.

## 🗄️ Database Schema

The plugin creates the following database tables:

- `wp_perfaudit_synthetic_audits` - Stores synthetic audit results from PageSpeed Insights
- `wp_perfaudit_rum_metrics` - Stores aggregated Real User Monitoring metrics

All tables are automatically created on plugin activation and include proper indexes for optimal performance.

## 🔒 Security

Site Performance Tracker follows WordPress security best practices:

- ✅ **Input Sanitization**: All user inputs are sanitized before processing
- ✅ **Output Escaping**: All outputs are properly escaped for display
- ✅ **Nonce Verification**: CSRF protection on all form submissions
- ✅ **Capability Checks**: Proper permission checks for all admin functions
- ✅ **Prepared Statements**: All database queries use prepared statements
- ✅ **API Authentication**: Secure token-based authentication for REST endpoints
- ✅ **Data Validation**: Comprehensive validation of all incoming data

## 🎨 Customization

### Hooks and Filters

The plugin provides numerous WordPress hooks for customization:

```php
// Modify audit processing
add_filter('perfaudit_pro_audit_data', function($data) {
    // Customize audit data
    return $data;
});

// Add custom recommendations
add_filter('perfaudit_pro_recommendations', function($recommendations, $audit_data) {
    // Add custom recommendations
    return $recommendations;
});
```

### Styling

The plugin uses CSS custom properties (variables) for easy theming. Override styles in your theme's `style.css` or use the provided filter hooks.

## 🐛 Troubleshooting

### Common Issues

**Audits stuck in "Pending" or "Processing"**
- Ensure the worker is running (check Worker Status card)
- Click "Retry Stuck Audits" to reset stuck audits
- Verify your API key is valid and has proper permissions

**No RUM data appearing**
- Ensure RUM tracking is enabled in Settings
- Check that users have consented to data collection (if consent mode is enabled)
- Verify the frontend tracking script is loading (check browser console)

**API errors**
- Verify your Google PageSpeed Insights API key is correct
- Check API quota limits in Google Cloud Console
- Ensure the API is enabled for your project

## 📊 Performance Impact

Site Performance Tracker is designed to have minimal impact on your site's performance:

- **Frontend**: RUM tracking script is lightweight and loads asynchronously
- **Backend**: Worker processes audits in the background without blocking requests
- **Database**: Optimized queries with proper indexing
- **Caching**: Dashboard data is cached to reduce database load

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

1. Clone the repository
2. Set up a local WordPress development environment
3. Install dependencies (if any)
4. Activate the plugin in your development site
5. Make your changes and test thoroughly

## 📝 Changelog

### 1.0.0 (2024)
- Initial release
- Google PageSpeed Insights API integration
- Real User Monitoring (RUM) with Core Web Vitals
- Professional admin dashboard with circular progress indicators
- Automated worker system for audit processing
- Export functionality (CSV and HTML reports)
- Data retention and cleanup features
- Comprehensive REST API endpoints

## 🆘 Support

- **Documentation**: [GitHub Repository](https://github.com/godwillcodes/PerfAuditPro)
- **Issues**: [GitHub Issues](https://github.com/godwillcodes/PerfAuditPro/issues)
- **Author**: Godwill Barasa

## 📄 License

This plugin is licensed under the **GPLv2 or later**.

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

## 🙏 Credits

- **Google PageSpeed Insights API** - For providing the performance audit engine
- **Web Vitals Library** - For Core Web Vitals measurement
- **WordPress Community** - For the amazing platform and ecosystem

---

**Made with ❤️ by [Godwill Barasa](https://github.com/godwillcodes)**
