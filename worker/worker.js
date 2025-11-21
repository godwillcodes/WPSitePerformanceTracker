#!/usr/bin/env node

/**
 * PerfAudit Pro Worker
 * 
 * Polls WordPress for pending audits and processes them using Lighthouse
 */

const lighthouse = require('lighthouse');
const chromeLauncher = require('chrome-launcher');
const fetch = require('node-fetch');
const fs = require('fs');
const path = require('path');
require('dotenv').config();

// Configuration
const CONFIG = {
    WORDPRESS_URL: process.env.WORDPRESS_URL || 'http://localhost',
    API_TOKEN: process.env.API_TOKEN || '',
    POLL_INTERVAL: parseInt(process.env.POLL_INTERVAL || '30000', 10), // 30 seconds
    WORKER_ID: process.env.WORKER_ID || `worker-${Date.now()}`,
    MAX_CONCURRENT: parseInt(process.env.MAX_CONCURRENT || '2', 10),
    CHROME_FLAGS: ['--headless', '--no-sandbox', '--disable-gpu']
};

// State
let isRunning = false;
let processingAudits = new Set();

/**
 * Get pending audits from WordPress
 */
async function getPendingAudits() {
    try {
        const response = await fetch(`${CONFIG.WORDPRESS_URL}/wp-json/perfaudit-pro/v1/pending-audits`, {
            method: 'GET',
            headers: {
                'X-PerfAudit-Token': CONFIG.API_TOKEN,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            console.error(`Failed to fetch pending audits: ${response.status} ${response.statusText}`);
            return [];
        }

        const data = await response.json();
        return Array.isArray(data) ? data : [];
    } catch (error) {
        console.error('Error fetching pending audits:', error.message);
        return [];
    }
}

/**
 * Mark audit as processing
 */
async function markAuditProcessing(auditId) {
    try {
        const response = await fetch(`${CONFIG.WORDPRESS_URL}/wp-json/perfaudit-pro/v1/mark-processing`, {
            method: 'POST',
            headers: {
                'X-PerfAudit-Token': CONFIG.API_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                audit_id: auditId,
                worker_id: CONFIG.WORKER_ID
            })
        });

        return response.ok;
    } catch (error) {
        console.error(`Error marking audit ${auditId} as processing:`, error.message);
        return false;
    }
}

/**
 * Run Lighthouse audit
 */
async function runLighthouseAudit(url) {
    let chrome = null;
    
    try {
        console.log(`Starting Lighthouse audit for: ${url}`);
        
        // Launch Chrome
        chrome = await chromeLauncher.launch({
            chromeFlags: CONFIG.CHROME_FLAGS,
            port: 9222
        });

        // Run Lighthouse
        const options = {
            logLevel: 'info',
            output: 'json',
            onlyCategories: ['performance'],
            port: chrome.port
        };

        const runnerResult = await lighthouse(url, options);
        await chrome.kill();
        chrome = null;

        return runnerResult.lhr;
    } catch (error) {
        if (chrome) {
            await chrome.kill();
        }
        throw error;
    }
}

/**
 * Extract metrics from Lighthouse result
 */
function extractMetrics(lhr) {
    const audits = lhr.audits;
    
    return {
        performance_score: Math.round((lhr.categories.performance.score || 0) * 100),
        first_contentful_paint: audits['first-contentful-paint']?.numericValue || null,
        largest_contentful_paint: audits['largest-contentful-paint']?.numericValue || null,
        total_blocking_time: audits['total-blocking-time']?.numericValue || null,
        cumulative_layout_shift: audits['cumulative-layout-shift']?.numericValue || null,
        speed_index: audits['speed-index']?.numericValue || null,
        time_to_interactive: audits['interactive']?.numericValue || null,
        lighthouse_json: JSON.stringify(lhr)
    };
}

/**
 * Submit audit results to WordPress
 */
async function submitResults(auditId, results) {
    try {
        const response = await fetch(`${CONFIG.WORDPRESS_URL}/wp-json/perfaudit-pro/v1/submit-audit-results`, {
            method: 'POST',
            headers: {
                'X-PerfAudit-Token': CONFIG.API_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                audit_id: auditId,
                results: results
            })
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const data = await response.json();
        console.log(`✓ Audit ${auditId} completed successfully`);
        return data;
    } catch (error) {
        console.error(`Error submitting results for audit ${auditId}:`, error.message);
        throw error;
    }
}

/**
 * Mark audit as failed
 */
async function markAuditFailed(auditId, error) {
    try {
        // You might want to add a failed status endpoint
        console.error(`Marking audit ${auditId} as failed:`, error.message);
    } catch (err) {
        console.error(`Error marking audit as failed:`, err.message);
    }
}

/**
 * Process a single audit
 */
async function processAudit(audit) {
    const auditId = audit.id;
    const url = audit.url;

    if (processingAudits.has(auditId)) {
        return;
    }

    processingAudits.add(auditId);

    try {
        // Mark as processing
        const marked = await markAuditProcessing(auditId);
        if (!marked) {
            console.log(`Audit ${auditId} already being processed by another worker`);
            processingAudits.delete(auditId);
            return;
        }

        // Run Lighthouse
        const lhr = await runLighthouseAudit(url);
        
        // Extract metrics
        const results = extractMetrics(lhr);
        
        // Submit results
        await submitResults(auditId, results);
        
    } catch (error) {
        console.error(`Error processing audit ${auditId}:`, error.message);
        await markAuditFailed(auditId, error);
    } finally {
        processingAudits.delete(auditId);
    }
}

/**
 * Main worker loop
 */
async function workerLoop() {
    if (isRunning) {
        return;
    }

    isRunning = true;

    try {
        // Get pending audits
        const pendingAudits = await getPendingAudits();
        
        if (pendingAudits.length === 0) {
            console.log('No pending audits found');
            return;
        }

        console.log(`Found ${pendingAudits.length} pending audit(s)`);

        // Process audits (respecting max concurrent)
        const availableSlots = CONFIG.MAX_CONCURRENT - processingAudits.size;
        const auditsToProcess = pendingAudits.slice(0, availableSlots);

        for (const audit of auditsToProcess) {
            // Process in parallel (up to MAX_CONCURRENT)
            processAudit(audit).catch(err => {
                console.error(`Unhandled error processing audit ${audit.id}:`, err);
            });
        }
    } catch (error) {
        console.error('Error in worker loop:', error.message);
    } finally {
        isRunning = false;
    }
}

/**
 * Start the worker
 */
function start() {
    console.log('🚀 PerfAudit Pro Worker Starting...');
    console.log(`Worker ID: ${CONFIG.WORKER_ID}`);
    console.log(`WordPress URL: ${CONFIG.WORDPRESS_URL}`);
    console.log(`Poll Interval: ${CONFIG.POLL_INTERVAL}ms`);
    console.log(`Max Concurrent: ${CONFIG.MAX_CONCURRENT}`);

    if (!CONFIG.API_TOKEN) {
        console.error('❌ API_TOKEN not set! Please configure it in .env file');
        process.exit(1);
    }

    // Initial run
    workerLoop();

    // Set up polling interval
    setInterval(() => {
        workerLoop();
    }, CONFIG.POLL_INTERVAL);

    console.log('✅ Worker is running. Press Ctrl+C to stop.');
}

// Handle graceful shutdown
process.on('SIGINT', () => {
    console.log('\n🛑 Shutting down worker...');
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log('\n🛑 Shutting down worker...');
    process.exit(0);
});

// Start if run directly
if (require.main === module) {
    start();
}

module.exports = { start, processAudit, getPendingAudits };

