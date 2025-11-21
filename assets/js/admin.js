(function($) {
    'use strict';

    const PerfAuditPro = window.perfauditPro || {};

    function fetchAuditResults() {
        return $.ajax({
            url: PerfAuditPro.apiUrl + 'audit-results',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PerfAuditPro.nonce);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching audit results:', error, xhr.responseText);
            }
        });
    }

    function fetchRUMMetrics() {
        return $.ajax({
            url: PerfAuditPro.apiUrl + 'rum-metrics',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PerfAuditPro.nonce);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching RUM metrics:', error, xhr.responseText);
            }
        });
    }

    function getScoreStatus(score) {
        if (score >= 90) return { status: 'good', label: 'Slaying', color: '#007BFF' };
        if (score >= 70) return { status: 'warning', label: 'Could Be Better', color: '#FF6B6B' };
        return { status: 'bad', label: 'Needs Work', color: '#FF6B6B' };
    }

    function getLCPStatus(lcp) {
        if (lcp <= 2500) return { status: 'good', label: 'Chef\'s Kiss', color: '#007BFF' };
        if (lcp <= 4000) return { status: 'warning', label: 'It\'s Giving Slow', color: '#FF6B6B' };
        return { status: 'bad', label: 'Users Are Gone', color: '#FF6B6B' };
    }

    function getCLSStatus(cls) {
        if (cls <= 0.1) return { status: 'good', label: 'Smooth Like Butter', color: '#007BFF' };
        if (cls <= 0.25) return { status: 'warning', label: 'A Bit Janky', color: '#FF6B6B' };
        return { status: 'bad', label: 'Identity Crisis', color: '#FF6B6B' };
    }

    function renderStatusBadge(containerId, statusObj) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<div class="perfaudit-pro-status ' + statusObj.status + '">' + statusObj.label + '</div>';
    }

    function getChartOptions(yLabelCallback) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 12,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.85)',
                    padding: 12,
                    titleFont: {
                        size: 13,
                        weight: '600'
                    },
                    bodyFont: {
                        size: 14,
                        weight: '500'
                    },
                    borderColor: '#2563eb',
                    borderWidth: 2,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + (yLabelCallback ? yLabelCallback(context.parsed.y) : context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.06)',
                        lineWidth: 1,
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 11,
                            weight: '500'
                        },
                        color: '#6b7280',
                        padding: 8,
                        callback: yLabelCallback || function(value) {
                            return value;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 11,
                            weight: '500'
                        },
                        color: '#6b7280',
                        padding: 8
                    }
                }
            }
        };
    }

    function createGradient(ctx, color, stops = 3) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        if (stops === 3) {
            gradient.addColorStop(0, color.replace('rgb', 'rgba').replace(')', ', 0.25)'));
            gradient.addColorStop(0.5, color.replace('rgb', 'rgba').replace(')', ', 0.1)'));
            gradient.addColorStop(1, color.replace('rgb', 'rgba').replace(')', ', 0.02)'));
        } else {
            gradient.addColorStop(0, color.replace('rgb', 'rgba').replace(')', ', 0.2)'));
            gradient.addColorStop(1, color.replace('rgb', 'rgba').replace(')', ', 0.02)'));
        }
        return gradient;
    }

    function getChartDatasetDefaults(color) {
        const rgbColor = color === '#2563eb' ? 'rgb(37, 99, 235)' : 
                        color === '#ef4444' ? 'rgb(239, 68, 68)' :
                        color === '#f59e0b' ? 'rgb(245, 158, 11)' :
                        color === '#10b981' ? 'rgb(16, 185, 129)' : 'rgb(37, 99, 235)';
        return {
            borderWidth: 3,
            tension: 0.5,
            fill: true,
            pointRadius: 5,
            pointHoverRadius: 8,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: color,
            pointBorderWidth: 3,
            pointHoverBackgroundColor: color,
            pointHoverBorderColor: '#ffffff',
            pointHoverBorderWidth: 3
        };
    }

    function renderRecommendations(containerId, recommendations) {
        const container = document.getElementById(containerId);
        if (!container || !recommendations || recommendations.length === 0) {
            if (container) container.innerHTML = '';
            return;
        }
        let html = '<div class="perfaudit-pro-recommendations"><h4>Recommendations</h4><ul>';
        recommendations.forEach(rec => {
            html += '<li>' + escapeHtml(rec) + '</li>';
        });
        html += '</ul></div>';
        container.innerHTML = html;
    }

    function renderCircularProgress(containerId, score, label, color) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const percentage = Math.round(score);
        const circumference = 2 * Math.PI * 54; // radius = 54
        const offset = circumference - (percentage / 100) * circumference;

        container.innerHTML = `
            <div class="perfaudit-pro-circular-progress-wrapper">
                <svg class="perfaudit-pro-circular-progress-svg" viewBox="0 0 120 120">
                    <circle class="perfaudit-pro-circular-progress-bg" cx="60" cy="60" r="54" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                    <circle class="perfaudit-pro-circular-progress-fill" cx="60" cy="60" r="54" fill="none" stroke="${color}" stroke-width="8" stroke-linecap="round" 
                        stroke-dasharray="${circumference}" 
                        stroke-dashoffset="${offset}" 
                        transform="rotate(-90 60 60)"
                        style="transition: stroke-dashoffset 1.5s ease-in-out;"/>
                </svg>
                <div class="perfaudit-pro-circular-progress-inner">
                    <div class="perfaudit-pro-circular-progress-value">${percentage}</div>
                    <div class="perfaudit-pro-circular-progress-label">${label}</div>
                </div>
            </div>
        `;
    }

    function getScoreColor(score) {
        if (score >= 90) return '#10b981'; // green
        if (score >= 50) return '#f59e0b'; // yellow
        return '#ef4444'; // red
    }

    function getMetricScore(value, thresholds) {
        // thresholds: { excellent: max, good: max, needsImprovement: max }
        // Returns a score 0-100
        if (value <= thresholds.excellent) return 100;
        if (value <= thresholds.good) return 75;
        if (value <= thresholds.needsImprovement) return 50;
        return Math.max(0, 100 - ((value - thresholds.needsImprovement) / thresholds.needsImprovement) * 50);
    }

    function renderAuditTimelineChart(data) {
        const container = document.getElementById('audit-timeline-progress');
        if (!container) return;

        if (!data || data.length === 0) {
            container.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No audit data available yet. Create your first audit to get started.</p></div>';
            return;
        }

        const scores = data.map(item => parseFloat(item.performance_score) || 0);
        const latestScore = scores[scores.length - 1];
        const avgScore = scores.reduce((a, b) => a + b, 0) / scores.length;
        const trend = scores.length > 1 ? (scores[scores.length - 1] - scores[0]) : 0;

        const statusObj = getScoreStatus(latestScore);
        renderStatusBadge('audit-timeline-status', statusObj);

        const recommendations = [];
        if (latestScore < 90) {
            recommendations.push('Optimize images and use modern formats (WebP, AVIF)');
            recommendations.push('Enable lazy loading for below-the-fold content');
            recommendations.push('Minify CSS and JavaScript files');
        }
        if (trend < -5) {
            recommendations.push('Performance is declining - check recent changes');
        }
        if (avgScore < 70) {
            recommendations.push('Consider using a CDN to improve load times');
            recommendations.push('Reduce server response time (TTFB)');
        }
        renderRecommendations('audit-timeline-recommendations', recommendations);

        const color = getScoreColor(latestScore);
        renderCircularProgress('audit-timeline-progress', latestScore, 'Performance', color);
    }

    function renderScoreDistributionChart(data) {
        const container = document.getElementById('score-distribution-container');
        if (!container) return;

        if (!data || data.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No audit data available yet.</p></div>';
            return;
        }

        const scores = data.map(item => parseFloat(item.performance_score) || 0).filter(score => score > 0);
        if (scores.length === 0) {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No completed audits with scores yet.</p></div>';
            return;
        }

        const ranges = [
            { label: 'Excellent', range: '90-100', min: 90, max: 100, count: 0, color: '#10b981', icon: '✓' },
            { label: 'Good', range: '70-90', min: 70, max: 90, count: 0, color: '#3b82f6', icon: '→' },
            { label: 'Needs Improvement', range: '50-70', min: 50, max: 70, count: 0, color: '#f59e0b', icon: '⚠' },
            { label: 'Poor', range: '0-50', min: 0, max: 50, count: 0, color: '#ef4444', icon: '✗' }
        ];

        scores.forEach(score => {
            if (score >= 90) ranges[0].count++;
            else if (score >= 70) ranges[1].count++;
            else if (score >= 50) ranges[2].count++;
            else ranges[3].count++;
        });

        const total = scores.length;
        const excellentPct = (ranges[0].count / total) * 100;
        const statusObj = excellentPct >= 70 ? 
            { status: 'good', label: 'Mostly Excellent' } :
            excellentPct >= 50 ?
            { status: 'warning', label: 'Room For Improvement' } :
            { status: 'bad', label: 'Needs Major Work' };
        
        renderStatusBadge('score-distribution-status', statusObj);

        const recommendations = [];
        if (ranges[3].count > 0) {
            recommendations.push('You have audits scoring below 50 - this is urgent');
            recommendations.push('Focus on core web vitals and reduce JavaScript execution time');
        }
        if (ranges[2].count > 0) {
            recommendations.push('Optimize images and implement code splitting');
        }
        if (excellentPct < 70) {
            recommendations.push('Aim for 70%+ of audits to score 90+');
        }
        renderRecommendations('score-distribution-recommendations', recommendations);

        let html = '<div class="perfaudit-pro-score-distribution-grid">';
        ranges.forEach(range => {
            const percentage = total > 0 ? ((range.count / total) * 100).toFixed(1) : 0;
            html += `
                <div class="perfaudit-pro-score-range-card" style="border-left: 4px solid ${range.color};">
                    <div class="perfaudit-pro-score-range-header">
                        <div class="perfaudit-pro-score-range-label">
                            <span class="perfaudit-pro-score-range-icon" style="color: ${range.color};">${range.icon}</span>
                            <div>
                                <strong class="perfaudit-pro-score-range-title">${range.label}</strong>
                                <span class="perfaudit-pro-score-range-value">${range.range}</span>
                            </div>
                        </div>
                        <div class="perfaudit-pro-score-range-stats">
                            <span class="perfaudit-pro-score-range-count">${range.count}</span>
                            <span class="perfaudit-pro-score-range-percentage">${percentage}%</span>
                        </div>
                    </div>
                    <div class="perfaudit-pro-score-range-bar">
                        <div class="perfaudit-pro-score-range-bar-fill" style="width: ${percentage}%; background-color: ${range.color};"></div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    function renderRUMLCPChart(data) {
        const container = document.getElementById('rum-lcp-progress');
        if (!container) return;

        if (!data || data.length === 0) {
            container.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No RUM data yet. Metrics will appear as users visit your site.</p></div>';
            return;
        }

        const p75LCP = data.map(item => parseFloat(item.p75_lcp) || 0);
        const avgLCP = data.map(item => parseFloat(item.avg_lcp) || 0);
        const latestP75 = p75LCP[p75LCP.length - 1] || avgLCP[avgLCP.length - 1] || 0;

        const statusObj = getLCPStatus(latestP75);
        renderStatusBadge('rum-lcp-status', statusObj);

        const recommendations = [];
        if (latestP75 > 4000) {
            recommendations.push('LCP is critical - optimize your largest content element');
            recommendations.push('Preload key resources and improve server response time');
        } else if (latestP75 > 2500) {
            recommendations.push('Reduce LCP by optimizing images and using efficient formats');
            recommendations.push('Consider using a CDN and caching strategies');
        }
        if (avgLCP.length > 0 && Math.max(...avgLCP) > 3000) {
            recommendations.push('Monitor LCP trends - it\'s been high recently');
        }
        renderRecommendations('rum-lcp-recommendations', recommendations);

        // LCP thresholds: excellent <= 2500ms, good <= 4000ms, needs improvement > 4000ms
        const score = getMetricScore(latestP75, { excellent: 2500, good: 4000, needsImprovement: 4000 });
        const color = getScoreColor(score);
        const displayValue = Math.round(latestP75);
        renderCircularProgress('rum-lcp-progress', score, displayValue + 'ms', color);
    }

    function renderRUMCLSChart(data) {
        const container = document.getElementById('rum-cls-progress');
        if (!container) return;

        if (!data || data.length === 0) {
            container.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No RUM data yet. Metrics will appear as users visit your site.</p></div>';
            return;
        }

        const p75CLS = data.map(item => parseFloat(item.p75_cls) || 0);
        const avgCLS = data.map(item => parseFloat(item.avg_cls) || 0);
        const latestP75 = p75CLS[p75CLS.length - 1] || avgCLS[avgCLS.length - 1] || 0;

        const statusObj = getCLSStatus(latestP75);
        renderStatusBadge('rum-cls-status', statusObj);

        const recommendations = [];
        if (latestP75 > 0.25) {
            recommendations.push('CLS is critical - your layout is shifting too much');
            recommendations.push('Set explicit width/height on images and videos');
            recommendations.push('Avoid inserting content above existing content');
        } else if (latestP75 > 0.1) {
            recommendations.push('Reduce layout shifts by reserving space for dynamic content');
            recommendations.push('Use CSS aspect-ratio for responsive images');
        }
        if (avgCLS.length > 0 && Math.max(...avgCLS) > 0.2) {
            recommendations.push('Monitor CLS - layout stability needs attention');
        }
        renderRecommendations('rum-cls-recommendations', recommendations);

        // CLS thresholds: excellent <= 0.1, good <= 0.25, needs improvement > 0.25
        // For CLS, lower is better, so we invert the score
        const score = getMetricScore(latestP75, { excellent: 0.1, good: 0.25, needsImprovement: 0.25 });
        const color = getScoreColor(score);
        const displayValue = latestP75.toFixed(3);
        renderCircularProgress('rum-cls-progress', score, displayValue, color);
    }

    function renderRUMFIDChart(data) {
        const container = document.getElementById('rum-fid-progress');
        if (!container) return;

        if (!data || data.length === 0) {
            container.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No RUM data yet. Metrics will appear as users visit your site.</p></div>';
            return;
        }

        const p75FID = data.map(item => parseFloat(item.p75_fid) || 0);
        const avgFID = data.map(item => parseFloat(item.avg_fid) || 0);
        const latestP75 = p75FID[p75FID.length - 1] || avgFID[avgFID.length - 1] || 0;

        const statusObj = latestP75 < 100 ? { status: 'good', label: 'Good' } : latestP75 < 300 ? { status: 'warning', label: 'Needs Improvement' } : { status: 'bad', label: 'Poor' };
        renderStatusBadge('rum-fid-status', statusObj);

        const recommendations = [];
        if (latestP75 > 300) {
            recommendations.push('FID is critical - users experience significant delay');
            recommendations.push('Break up long tasks with code splitting');
            recommendations.push('Optimize JavaScript execution');
        } else if (latestP75 > 100) {
            recommendations.push('Reduce JavaScript execution time');
            recommendations.push('Use web workers for heavy computations');
        }
        renderRecommendations('rum-fid-recommendations', recommendations);

        // FID thresholds: excellent <= 100ms, good <= 300ms, needs improvement > 300ms
        const score = getMetricScore(latestP75, { excellent: 100, good: 300, needsImprovement: 300 });
        const color = getScoreColor(score);
        const displayValue = Math.round(latestP75) + 'ms';
        renderCircularProgress('rum-fid-progress', score, displayValue, color);
    }

    function renderRUMFCPChart(data) {
        const container = document.getElementById('rum-fcp-progress');
        if (!container) return;

        if (!data || data.length === 0) {
            container.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No RUM data yet. Metrics will appear as users visit your site.</p></div>';
            return;
        }

        const p75FCP = data.map(item => parseFloat(item.p75_fcp) || 0);
        const avgFCP = data.map(item => parseFloat(item.avg_fcp) || 0);
        const latestP75 = p75FCP[p75FCP.length - 1] || avgFCP[avgFCP.length - 1] || 0;

        const statusObj = latestP75 < 1800 ? { status: 'good', label: 'Good' } : latestP75 < 3000 ? { status: 'warning', label: 'Needs Improvement' } : { status: 'bad', label: 'Poor' };
        renderStatusBadge('rum-fcp-status', statusObj);

        const recommendations = [];
        if (latestP75 > 3000) {
            recommendations.push('FCP is critical - first content takes too long');
            recommendations.push('Optimize server response time');
            recommendations.push('Minimize render-blocking resources');
        } else if (latestP75 > 1800) {
            recommendations.push('Improve server response time');
            recommendations.push('Optimize critical rendering path');
        }
        renderRecommendations('rum-fcp-recommendations', recommendations);

        // FCP thresholds: excellent <= 1800ms, good <= 3000ms, needs improvement > 3000ms
        const score = getMetricScore(latestP75, { excellent: 1800, good: 3000, needsImprovement: 3000 });
        const color = getScoreColor(score);
        const displayValue = Math.round(latestP75) + 'ms';
        renderCircularProgress('rum-fcp-progress', score, displayValue, color);
    }

    function renderRUMTTFBChart(data) {
        const container = document.getElementById('rum-ttfb-progress');
        if (!container) return;

        if (!data || data.length === 0) {
            container.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No RUM data yet. Metrics will appear as users visit your site.</p></div>';
            return;
        }

        const p75TTFB = data.map(item => parseFloat(item.p75_ttfb) || 0);
        const avgTTFB = data.map(item => parseFloat(item.avg_ttfb) || 0);
        const latestP75 = p75TTFB[p75TTFB.length - 1] || avgTTFB[avgTTFB.length - 1] || 0;

        const statusObj = latestP75 < 800 ? { status: 'good', label: 'Good' } : latestP75 < 1800 ? { status: 'warning', label: 'Needs Improvement' } : { status: 'bad', label: 'Poor' };
        renderStatusBadge('rum-ttfb-status', statusObj);

        const recommendations = [];
        if (latestP75 > 1800) {
            recommendations.push('TTFB is critical - server response is too slow');
            recommendations.push('Optimize server performance');
            recommendations.push('Use a CDN');
            recommendations.push('Enable caching');
        } else if (latestP75 > 800) {
            recommendations.push('Improve server response time');
            recommendations.push('Consider using a faster hosting provider');
        }
        renderRecommendations('rum-ttfb-recommendations', recommendations);

        // TTFB thresholds: excellent <= 800ms, good <= 1800ms, needs improvement > 1800ms
        const score = getMetricScore(latestP75, { excellent: 800, good: 1800, needsImprovement: 1800 });
        const color = getScoreColor(score);
        const displayValue = Math.round(latestP75) + 'ms';
        renderCircularProgress('rum-ttfb-progress', score, displayValue, color);
    }

    function renderRecentAuditsTable(data) {
        const container = document.getElementById('recent-audits-table');
        if (!container) return;

        // Remove loading state
        container.setAttribute('aria-busy', 'false');

        if (!data || data.length === 0) {
            container.innerHTML = '<p>No audits found.</p>';
            return;
        }

        let html = '<table><thead><tr><th><input type="checkbox" id="select-all-checkbox" /></th><th>URL</th><th>Score</th><th>LCP</th><th>FID</th><th>CLS</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>';

        data.forEach(item => {
            const score = parseFloat(item.performance_score) || 0;
            let scoreBadge = '';
            if (score >= 90) {
                scoreBadge = '<span class="score-badge excellent">' + score.toFixed(0) + '</span>';
            } else if (score >= 70) {
                scoreBadge = '<span class="score-badge good">' + score.toFixed(0) + '</span>';
            } else if (score >= 50) {
                scoreBadge = '<span class="score-badge needs-improvement">' + score.toFixed(0) + '</span>';
            } else if (score > 0) {
                scoreBadge = '<span class="score-badge poor">' + score.toFixed(0) + '</span>';
            } else {
                scoreBadge = '<span style="color: #94a3b8;">Pending</span>';
            }

            const statusBadge = item.status === 'completed' ? 
                '<span style="background: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Completed</span>' :
                item.status === 'pending' ?
                '<span style="background: #f59e0b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Pending</span>' :
                item.status === 'processing' ?
                '<span style="background: #3b82f6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Processing</span>' :
                '<span style="background: #ef4444; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">' + escapeHtml(item.status) + '</span>';

            html += '<tr data-audit-id="' + item.id + '">';
            html += '<td><input type="checkbox" class="audit-checkbox" value="' + item.id + '" /></td>';
            html += '<td>' + escapeHtml(item.url) + '</td>';
            html += '<td>' + scoreBadge + '</td>';
            html += '<td>' + (item.largest_contentful_paint ? parseFloat(item.largest_contentful_paint).toFixed(0) + 'ms' : 'N/A') + '</td>';
            html += '<td>' + (item.total_blocking_time ? parseFloat(item.total_blocking_time).toFixed(0) + 'ms' : 'N/A') + '</td>';
            html += '<td>' + (item.cumulative_layout_shift ? parseFloat(item.cumulative_layout_shift).toFixed(3) : 'N/A') + '</td>';
            html += '<td>' + statusBadge + '</td>';
            html += '<td>' + new Date(item.created_at).toLocaleString() + '</td>';
            html += '<td><button class="button button-small view-audit-details" data-audit-id="' + item.id + '">View</button></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;

        // Attach event handlers
        attachAuditTableHandlers();
    }

    function attachAuditTableHandlers() {
        $('#select-all-checkbox, #select-all-audits').on('change', function() {
            const checked = $(this).is(':checked');
            $('.audit-checkbox').prop('checked', checked);
            updateBulkActions();
        });

        $('.audit-checkbox').on('change', function() {
            updateBulkActions();
        });

        $('#bulk-delete-btn').on('click', function() {
            const selected = $('.audit-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selected.length === 0) {
                alert('Please select audits to delete');
                return;
            }

            if (!confirm('Are you sure you want to delete ' + selected.length + ' audit(s)?')) {
                return;
            }

            $.ajax({
                url: PerfAuditPro.apiUrl + 'delete-audits',
                method: 'POST',
                data: JSON.stringify({ audit_ids: selected }),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', PerfAuditPro.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        });

        $('.view-audit-details').on('click', function() {
            const auditId = $(this).data('audit-id');
            window.location.href = '?page=perfaudit-pro&audit_id=' + auditId;
        });
    }

    function updateBulkActions() {
        const count = $('.audit-checkbox:checked').length;
        if (count > 0) {
            $('#bulk-delete-btn').show().text('Delete Selected (' + count + ')');
        } else {
            $('#bulk-delete-btn').hide();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function createAudit(url, device = 'desktop') {
        return $.ajax({
            url: PerfAuditPro.apiUrl + 'create-audit',
            method: 'POST',
            data: JSON.stringify({
                url: url,
                device: device,
                audit_type: 'lighthouse'
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PerfAuditPro.nonce);
            }
        });
    }

    function checkWorkerStatus() {
        $.ajax({
            url: PerfAuditPro.ajaxUrl,
            method: 'POST',
            data: {
                action: 'perfaudit_check_worker',
                nonce: PerfAuditPro.workerNonce
            },
            success: function(response) {
                if (response.success) {
                    const status = response.data.status;
                    const nodeAvailable = response.data.node_available;
                    
                    if (status === 'running') {
                        $('#worker-start-btn').hide();
                        $('#worker-stop-btn').show();
                        $('#worker-status-badge-modern').removeClass('stopped').addClass('running');
                        $('#worker-status-text').text('Running');
                    } else {
                        $('#worker-start-btn').show();
                        $('#worker-stop-btn').hide();
                        $('#worker-status-badge-modern').removeClass('running').addClass('stopped');
                        $('#worker-status-text').text('Stopped');
                    }
                    
                    // PHP worker always available, no Node.js check needed
                    $('#worker-message').hide();
                    $('#worker-start-btn').prop('disabled', false);
                }
            }
        });
    }

    function startWorker() {
        $('#worker-start-btn').prop('disabled', true).text('Starting...');
        
        $.ajax({
            url: PerfAuditPro.ajaxUrl,
            method: 'POST',
            data: {
                action: 'perfaudit_start_worker',
                nonce: PerfAuditPro.workerNonce
            },
            success: function(response) {
                if (response.success) {
                    $('#worker-message').html('<strong>✅ ' + response.data.message + '</strong>').css('background', 'rgba(16, 185, 129, 0.2)').show();
                    setTimeout(checkWorkerStatus, 1000);
                } else {
                    $('#worker-message').html('<strong>❌ ' + response.data.message + '</strong>').css('background', 'rgba(239, 68, 68, 0.2)').show();
                    $('#worker-start-btn').prop('disabled', false).text('▶ Start Worker');
                }
            },
            error: function() {
                $('#worker-message').html('<strong>❌ Failed to start worker</strong>').css('background', 'rgba(239, 68, 68, 0.2)').show();
                $('#worker-start-btn').prop('disabled', false).text('▶ Start Worker');
            }
        });
    }

    function stopWorker() {
        $('#worker-stop-btn').prop('disabled', true).text('Stopping...');
        
        $.ajax({
            url: PerfAuditPro.ajaxUrl,
            method: 'POST',
            data: {
                action: 'perfaudit_stop_worker',
                nonce: PerfAuditPro.workerNonce
            },
            success: function(response) {
                if (response.success) {
                    $('#worker-message').html('<strong>✅ ' + response.data.message + '</strong>').css('background', 'rgba(16, 185, 129, 0.2)').show();
                    setTimeout(checkWorkerStatus, 1000);
                } else {
                    $('#worker-message').html('<strong>❌ ' + response.data.message + '</strong>').css('background', 'rgba(239, 68, 68, 0.2)').show();
                }
                $('#worker-stop-btn').prop('disabled', false).text('⏹ Stop Worker');
            }
        });
    }

    function processNow() {
        $('#process-now-btn').prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: PerfAuditPro.ajaxUrl,
            method: 'POST',
            data: {
                action: 'perfaudit_process_now',
                nonce: PerfAuditPro.workerNonce
            },
            success: function(response) {
                if (response.success) {
                    $('#worker-message').html('<strong>✅ ' + response.data.message + '</strong>').css('background', 'rgba(16, 185, 129, 0.2)').show();
                    // Reload page after 2 seconds to show updated audit status
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('#worker-message').html('<strong>❌ ' + response.data.message + '</strong>').css('background', 'rgba(239, 68, 68, 0.2)').show();
                    $('#process-now-btn').prop('disabled', false).text('Process Now');
                }
            },
            error: function() {
                $('#worker-message').html('<strong>❌ Failed to process audits</strong>').css('background', 'rgba(239, 68, 68, 0.2)').show();
                $('#process-now-btn').prop('disabled', false).text('Process Now');
            }
        });
    }

    function resetStuckAudits() {
        if (!confirm('This will retry any audits stuck in "processing" state. They will be reset to "pending" and processed again. Continue?')) {
            return;
        }

        $('#reset-stuck-btn').prop('disabled', true).text('Retrying...');
        
        $.ajax({
            url: PerfAuditPro.ajaxUrl,
            method: 'POST',
            data: {
                action: 'perfaudit_reset_stuck',
                nonce: PerfAuditPro.workerNonce
            },
            success: function(response) {
                if (response.success) {
                    $('#worker-message').html('<strong>✅ ' + response.data.message + '</strong>').css('background', 'rgba(16, 185, 129, 0.2)').show();
                    // Reload page after 1 second to show updated audit status
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $('#worker-message').html('<strong>❌ ' + response.data.message + '</strong>').css('background', 'rgba(239, 68, 68, 0.2)').show();
                    $('#reset-stuck-btn').prop('disabled', false).text('Retry Stuck Audits');
                }
            },
            error: function() {
                $('#worker-message').html('<strong>❌ Failed to reset stuck audits</strong>').css('background', 'rgba(239, 68, 68, 0.2)').show();
                $('#reset-stuck-btn').prop('disabled', false).text('Reset Stuck Audits');
            }
        });
    }

    $(document).ready(function() {
        console.log('PerfAudit Pro: Initializing dashboard...', PerfAuditPro);

        // Worker management
        $('#worker-start-btn').on('click', startWorker);
        $('#worker-stop-btn').on('click', stopWorker);
        $('#process-now-btn').on('click', processNow);
        $('#reset-stuck-btn').on('click', resetStuckAudits);
        
        // Check worker status on load and every 5 seconds
        checkWorkerStatus();
        setInterval(checkWorkerStatus, 5000);

        // Handle create audit form
        $('#create-audit-form').on('submit', function(e) {
            e.preventDefault();
            const url = $('#audit-url').val();
            const device = $('input[name="device"]:checked').val() || 'desktop';
            const messageDiv = $('#create-audit-message');
            const submitBtn = $(this).find('button[type="submit"]');
            const buttonContent = submitBtn.find('.perfaudit-pro-button-content');
            const buttonLoader = submitBtn.find('.perfaudit-pro-button-loader');

            submitBtn.prop('disabled', true);
            buttonContent.hide();
            buttonLoader.show();
            messageDiv.html('');

            createAudit(url, device).done(function(response) {
                messageDiv.html('<div class="notice notice-success">' +
                    '<p><strong>Success!</strong> Audit created successfully (ID: ' + response.audit_id + '). ' +
                    'The worker will process this audit automatically when running.</p>' +
                    '</div>');
                $('#audit-url').val('');
                
                // Reload data after a short delay
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }).fail(function(jqXHR) {
                let errorMsg = 'Failed to create audit. ';
                if (jqXHR.responseJSON) {
                    // WordPress REST API error format
                    if (jqXHR.responseJSON.message) {
                        errorMsg += jqXHR.responseJSON.message;
                    } else if (jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                        errorMsg += jqXHR.responseJSON.data.message;
                    } else if (jqXHR.responseJSON.code) {
                        errorMsg += jqXHR.responseJSON.code;
                    } else {
                        errorMsg += 'Please check the console for details.';
                    }
                } else {
                    errorMsg += 'Please check the console for details.';
                }
                console.error('Audit creation error:', jqXHR);
                messageDiv.html('<div class="notice notice-error"><p><strong>Error:</strong> ' + errorMsg + '</p></div>');
            }).always(function() {
                submitBtn.prop('disabled', false);
                buttonContent.show();
                buttonLoader.hide();
            });
        });
        
        function loadAuditData() {
            const filters = {
                status: $('#audit-filter-status').val(),
                search: $('#audit-search').val(),
                date_from: $('#audit-filter-date').val(),
            };

            $.ajax({
                url: PerfAuditPro.apiUrl + 'audit-results',
                method: 'GET',
                data: filters,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', PerfAuditPro.nonce);
                },
                success: function(auditData) {
                    renderAuditTimelineChart(auditData);
                    renderScoreDistributionChart(auditData);
                    renderRecentAuditsTable(auditData);
                }
            });
        }

        function loadRUMData() {
            $.ajax({
                url: PerfAuditPro.apiUrl + 'rum-metrics',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', PerfAuditPro.nonce);
                },
                success: function(rumData) {
                    renderRUMLCPChart(rumData);
                    renderRUMCLSChart(rumData);
                    renderRUMFIDChart(rumData);
                    renderRUMFCPChart(rumData);
                    renderRUMTTFBChart(rumData);
                }
            });
        }

        // Cleanup handler (for settings page)
        $('#cleanup-now-btn').on('click', function() {
            if (!confirm('This will permanently delete old audits and RUM metrics based on your retention settings. Continue?')) {
                return;
            }

            const $btn = $(this);
            const $message = $('#cleanup-message');
            $btn.prop('disabled', true).text('Cleaning up...');
            $message.html('');

            $.ajax({
                url: PerfAuditPro.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'perfaudit_cleanup_now',
                    nonce: PerfAuditPro.workerNonce
                },
                success: function(response) {
                    if (response.success) {
                        $message.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    } else {
                        $message.html('<div class="notice notice-error"><p>Error: ' + (response.data.message || 'Failed to run cleanup') + '</p></div>');
                    }
                },
                error: function() {
                    $message.html('<div class="notice notice-error"><p>Failed to run cleanup. Please try again.</p></div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Run Cleanup Now');
                }
            });
        });

        // Load data
        loadAuditData();
        loadRUMData();

        // Filter handlers
        $('#audit-search, #audit-filter-status, #audit-filter-date').on('change keyup', function() {
            clearTimeout(window.auditFilterTimeout);
            window.auditFilterTimeout = setTimeout(loadAuditData, 500);
        });

        // Export handlers
        $('#export-csv-btn').on('click', function() {
            const filters = {
                status: $('#audit-filter-status').val() || '',
                search: $('#audit-search').val() || '',
                date_from: $('#audit-filter-date').val() || '',
            };
            const params = new URLSearchParams();
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    params.append(key, filters[key]);
                }
            });
            params.append('action', 'perfaudit_export_csv');
            params.append('nonce', PerfAuditPro.exportNonce || PerfAuditPro.workerNonce);
            window.location.href = ajaxurl + '?' + params.toString();
        });

        $('#export-pdf-btn').on('click', function() {
            const filters = {
                status: $('#audit-filter-status').val() || '',
                search: $('#audit-search').val() || '',
                date_from: $('#audit-filter-date').val() || '',
            };
            const params = new URLSearchParams();
            Object.keys(filters).forEach(key => {
                if (filters[key]) {
                    params.append(key, filters[key]);
                }
            });
            params.append('action', 'perfaudit_export_pdf');
            params.append('nonce', PerfAuditPro.exportNonce || PerfAuditPro.workerNonce);
            window.location.href = ajaxurl + '?' + params.toString();
        });

        $.when(fetchRUMMetrics()).done(function(rumResponse) {
            console.log('PerfAudit Pro: RUM response received', rumResponse);
            
            let rumData = [];
            if (rumResponse && rumResponse.length > 0) {
                rumData = Array.isArray(rumResponse[0]) ? rumResponse[0] : (rumResponse[0] || []);
            }

            renderRUMLCPChart(rumData);
            renderRUMCLSChart(rumData);
            renderRUMFIDChart(rumData);
            renderRUMFCPChart(rumData);
            renderRUMTTFBChart(rumData);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('PerfAudit Pro: Failed to load audit data:', textStatus, errorThrown, jqXHR);
            const container = document.getElementById('recent-audits-table');
            if (container) {
                let errorMsg = 'Error loading data. ';
                if (jqXHR.status === 403) {
                    errorMsg += 'Permission denied. Please refresh the page.';
                } else if (jqXHR.status === 404) {
                    errorMsg += 'API endpoint not found.';
                } else {
                    errorMsg += 'Please check the browser console for details.';
                }
                container.innerHTML = '<p style="color: #d63638; padding: 20px;">' + errorMsg + '</p>';
            }
        });
    });
})(jQuery);

