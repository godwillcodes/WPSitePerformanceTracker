# PerfAudit Pro Worker

This is the built-in worker for processing Lighthouse audits. It runs as a separate Node.js process and polls WordPress for pending audits.

## Quick Start

1. **Install Dependencies**
   ```bash
   cd worker
   npm install
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your WordPress URL and API token
   ```

3. **Get Your API Token**
   - In WordPress, set the API token:
     ```php
     update_option('perfaudit_pro_api_token', 'your-secure-token-here');
     ```
   - Add the same token to your `.env` file

4. **Run the Worker**
   ```bash
   npm start
   ```

## Configuration

Edit `.env` file:

- `WORDPRESS_URL`: Your WordPress site URL (e.g., `https://yoursite.com`)
- `API_TOKEN`: The API token set in WordPress options
- `POLL_INTERVAL`: How often to check for new audits (milliseconds, default: 30000)
- `MAX_CONCURRENT`: Maximum audits to process simultaneously (default: 2)
- `WORKER_ID`: Unique identifier for this worker instance

## Running as a Service

### Using PM2 (Recommended)

```bash
npm install -g pm2
pm2 start worker.js --name perfaudit-worker
pm2 save
pm2 startup
```

### Using systemd (Linux)

Create `/etc/systemd/system/perfaudit-worker.service`:

```ini
[Unit]
Description=PerfAudit Pro Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/perfaudit-pro/worker
Environment=NODE_ENV=production
ExecStart=/usr/bin/node worker.js
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Then:
```bash
sudo systemctl enable perfaudit-worker
sudo systemctl start perfaudit-worker
```

## Requirements

- Node.js 14+ 
- Chrome/Chromium installed
- Network access to WordPress site
- API token configured in WordPress

## Troubleshooting

- **No audits processing**: Check API token is correct
- **Chrome errors**: Ensure Chrome/Chromium is installed
- **Connection errors**: Verify WORDPRESS_URL is correct and accessible

