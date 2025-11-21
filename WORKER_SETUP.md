# Worker Setup Guide

PerfAudit Pro includes a built-in worker in the `worker/` directory that processes Lighthouse audits. You can use this worker or build your own.

## Quick Start (Built-in Worker)

The easiest way to get started is using the built-in worker:

1. **Navigate to worker directory**
   ```bash
   cd worker
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your WordPress URL and API token
   ```

4. **Set API token in WordPress**
   ```php
   update_option('perfaudit_pro_api_token', 'your-secure-token-here');
   ```

5. **Run the worker**
   ```bash
   npm start
   ```

See `worker/README.md` for detailed setup instructions.

## Architecture

The plugin does NOT execute Lighthouse audits within WordPress. Instead:

1. WordPress creates audit records with status "pending"
2. External worker polls for pending audits or receives webhook notifications
3. Worker executes Lighthouse/Puppeteer audit
4. Worker submits results back to WordPress via REST API

## Worker Implementation

### Step 1: Poll for Pending Audits

Query the WordPress database directly or use a custom REST endpoint to get pending audits:

```sql
SELECT * FROM wp_perfaudit_synthetic_audits 
WHERE status = 'pending' 
ORDER BY created_at ASC 
LIMIT 10;
```

### Step 2: Mark Audit as Processing

Update the audit status to "processing" to prevent other workers from picking it up:

```sql
UPDATE wp_perfaudit_synthetic_audits 
SET status = 'processing', worker_id = 'your-worker-id' 
WHERE id = :audit_id;
```

### Step 3: Execute Lighthouse Audit

Run Lighthouse/Puppeteer against the URL:

```javascript
const lighthouse = require('lighthouse');
const chromeLauncher = require('chrome-launcher');

async function runAudit(url) {
  const chrome = await chromeLauncher.launch({chromeFlags: ['--headless']});
  const options = {logLevel: 'info', output: 'json', port: chrome.port};
  const runnerResult = await lighthouse(url, options);
  await chrome.kill();
  return runnerResult.lhr;
}
```

### Step 4: Submit Results

POST the results to WordPress REST API:

```javascript
const results = {
  performance_score: lhr.categories.performance.score * 100,
  first_contentful_paint: lhr.audits['first-contentful-paint'].numericValue,
  largest_contentful_paint: lhr.audits['largest-contentful-paint'].numericValue,
  total_blocking_time: lhr.audits['total-blocking-time'].numericValue,
  cumulative_layout_shift: lhr.audits['cumulative-layout-shift'].numericValue,
  speed_index: lhr.audits['speed-index'].numericValue,
  time_to_interactive: lhr.audits['interactive'].numericValue,
  lighthouse_json: JSON.stringify(lhr)
};

fetch('https://yoursite.com/wp-json/perfaudit-pro/v1/audit-results', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-PerfAudit-Token': 'your-api-token'
  },
  body: JSON.stringify({
    audit_id: auditId,
    results: results
  })
});
```

## Alternative: Direct Database Update

If you prefer to update the database directly (bypassing REST API):

```php
require_once '/path/to/wordpress/wp-load.php';
require_once '/path/to/wordpress/wp-content/plugins/perfaudit-pro/includes/database/class-audit-repository.php';

$repository = new \PerfAuditPro\Database\Audit_Repository();
$repository->update_audit_results($audit_id, $results);
```

## Worker Requirements

- Node.js 14+ (for Lighthouse)
- Chrome/Chromium installed
- Network access to WordPress site
- API token for authentication

## Rate Limiting

The REST API implements rate limiting:
- 100 requests per minute per IP address
- Use exponential backoff if rate limited

## Error Handling

- If audit fails, update status to "failed"
- Log errors for debugging
- Implement retry logic for transient failures

## Example Worker Script

See `examples/worker-example.js` for a complete Node.js worker implementation.

