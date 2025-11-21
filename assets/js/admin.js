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

    function renderAuditTimelineChart(data) {
        const ctx = document.getElementById('audit-timeline-chart');
        if (!ctx) return;

        if (!data || data.length === 0) {
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No audit data available yet. Create your first audit to get started.</p></div>';
            return;
        }

        const labels = data.map(item => new Date(item.created_at).toLocaleDateString());
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

        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(0, 123, 255, 0.2)');
        gradient.addColorStop(1, 'rgba(0, 123, 255, 0.02)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Performance Score',
                    data: scores,
                    borderColor: '#007BFF',
                    backgroundColor: gradient,
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#007BFF',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function renderScoreDistributionChart(data) {
        const ctx = document.getElementById('score-distribution-chart');
        if (!ctx) return;

        if (!data || data.length === 0) {
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No audit data available yet.</p></div>';
            return;
        }

        const scores = data.map(item => parseFloat(item.performance_score) || 0).filter(score => score > 0);
        if (scores.length === 0) {
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No completed audits with scores yet.</p></div>';
            return;
        }

        const ranges = {
            '90-100 (Slaying)': 0,
            '70-90 (Could Be Better)': 0,
            '50-70 (Meh)': 0,
            '0-50 (Yikes)': 0
        };

        scores.forEach(score => {
            if (score >= 90) ranges['90-100 (Slaying)']++;
            else if (score >= 70) ranges['70-90 (Could Be Better)']++;
            else if (score >= 50) ranges['50-70 (Meh)']++;
            else ranges['0-50 (Yikes)']++;
        });

        const total = scores.length;
        const excellentPct = (ranges['90-100 (Slaying)'] / total) * 100;
        const statusObj = excellentPct >= 70 ? 
            { status: 'good', label: 'Mostly Slaying' } :
            excellentPct >= 50 ?
            { status: 'warning', label: 'Room For Improvement' } :
            { status: 'bad', label: 'Needs Major Work' };
        
        renderStatusBadge('score-distribution-status', statusObj);

        const recommendations = [];
        if (ranges['0-50 (Yikes)'] > 0) {
            recommendations.push('You have audits scoring below 50 - this is urgent');
            recommendations.push('Focus on core web vitals and reduce JavaScript execution time');
        }
        if (ranges['50-70 (Meh)'] > 0) {
            recommendations.push('Optimize images and implement code splitting');
        }
        if (excellentPct < 70) {
            recommendations.push('Aim for 70%+ of audits to score 90+');
        }
        renderRecommendations('score-distribution-recommendations', recommendations);

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: Object.keys(ranges),
                datasets: [{
                    data: Object.values(ranges),
                    backgroundColor: [
                        '#007BFF',
                        'rgba(0, 123, 255, 0.7)',
                        '#FF6B6B',
                        'rgba(255, 107, 107, 0.8)'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    }

    function renderRUMLCPChart(data) {
        const ctx = document.getElementById('rum-lcp-chart');
        if (!ctx) return;

        if (!data || data.length === 0) {
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No RUM data yet. Metrics will appear as users visit your site.</p></div>';
            return;
        }

        const labels = data.map(item => item.date);
        const avgLCP = data.map(item => parseFloat(item.avg_lcp) || 0);
        const p75LCP = data.map(item => parseFloat(item.p75_lcp) || 0);
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

        const gradient1 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient1.addColorStop(0, 'rgba(255, 107, 107, 0.2)');
        gradient1.addColorStop(1, 'rgba(255, 107, 107, 0.02)');

        const gradient2 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient2.addColorStop(0, 'rgba(0, 123, 255, 0.2)');
        gradient2.addColorStop(1, 'rgba(0, 123, 255, 0.02)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average LCP',
                    data: avgLCP,
                    borderColor: '#FF6B6B',
                    backgroundColor: gradient1,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'P75 LCP',
                    data: p75LCP,
                    borderColor: '#007BFF',
                    backgroundColor: gradient2,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + 'ms';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function renderRUMCLSChart(data) {
        const ctx = document.getElementById('rum-cls-chart');
        if (!ctx) return;

        if (!data || data.length === 0) {
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No RUM data yet. Metrics will appear as users visit your site.</p></div>';
            return;
        }

        const labels = data.map(item => item.date);
        const avgCLS = data.map(item => parseFloat(item.avg_cls) || 0);
        const p75CLS = data.map(item => parseFloat(item.p75_cls) || 0);
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

        const gradient1 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient1.addColorStop(0, 'rgba(44, 62, 80, 0.15)');
        gradient1.addColorStop(1, 'rgba(44, 62, 80, 0.02)');

        const gradient2 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient2.addColorStop(0, 'rgba(255, 107, 107, 0.2)');
        gradient2.addColorStop(1, 'rgba(255, 107, 107, 0.02)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average CLS',
                    data: avgCLS,
                    borderColor: '#2C3E50',
                    backgroundColor: gradient1,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'P75 CLS',
                    data: p75CLS,
                    borderColor: '#FF6B6B',
                    backgroundColor: gradient2,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function renderRUMFIDChart(data) {
        const ctx = document.getElementById('rum-fid-chart');
        if (!ctx) return;

        if (!data || data.length === 0) {
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No RUM data yet. Metrics will appear as users visit your site.</p></div>';
            return;
        }

        const labels = data.map(item => item.date);
        const avgFID = data.map(item => parseFloat(item.avg_fid) || 0);
        const p75FID = data.map(item => parseFloat(item.p75_fid) || 0);
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

        const gradient1 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient1.addColorStop(0, 'rgba(0, 123, 255, 0.15)');
        gradient1.addColorStop(1, 'rgba(0, 123, 255, 0.02)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg FID (ms)',
                    data: avgFID,
                    borderColor: '#007BFF',
                    backgroundColor: gradient1,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'P75 FID (ms)',
                    data: p75FID,
                    borderColor: '#FF6B6B',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function renderRUMFCPChart(data) {
        const ctx = document.getElementById('rum-fcp-chart');
        if (!ctx) return;

        if (!data || data.length === 0) {
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No RUM data yet. Metrics will appear as users visit your site.</p></div>';
            return;
        }

        const labels = data.map(item => item.date);
        const avgFCP = data.map(item => parseFloat(item.avg_fcp) || 0);
        const p75FCP = data.map(item => parseFloat(item.p75_fcp) || 0);
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

        const gradient1 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient1.addColorStop(0, 'rgba(0, 123, 255, 0.15)');
        gradient1.addColorStop(1, 'rgba(0, 123, 255, 0.02)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg FCP (ms)',
                    data: avgFCP,
                    borderColor: '#007BFF',
                    backgroundColor: gradient1,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'P75 FCP (ms)',
                    data: p75FCP,
                    borderColor: '#FF6B6B',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function renderRUMTTFBChart(data) {
        const ctx = document.getElementById('rum-ttfb-chart');
        if (!ctx) return;

        if (!data || data.length === 0) {
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon"></div><p>No RUM data yet. Metrics will appear as users visit your site.</p></div>';
            return;
        }

        const labels = data.map(item => item.date);
        const avgTTFB = data.map(item => parseFloat(item.avg_ttfb) || 0);
        const p75TTFB = data.map(item => parseFloat(item.p75_ttfb) || 0);
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

        const gradient1 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient1.addColorStop(0, 'rgba(0, 123, 255, 0.15)');
        gradient1.addColorStop(1, 'rgba(0, 123, 255, 0.02)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Avg TTFB (ms)',
                    data: avgTTFB,
                    borderColor: '#007BFF',
                    backgroundColor: gradient1,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'P75 TTFB (ms)',
                    data: p75TTFB,
                    borderColor: '#FF6B6B',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function renderRecentAuditsTable(data) {
        const container = document.getElementById('recent-audits-table');
        if (!container) return;

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
                        $('#worker-status-text').text('🟢 Running').css('color', '#10b981');
                    } else {
                        $('#worker-start-btn').show();
                        $('#worker-stop-btn').hide();
                        $('#worker-status-text').text('⚪ Stopped').css('color', '#64748b');
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

    $(document).ready(function() {
        console.log('PerfAudit Pro: Initializing dashboard...', PerfAuditPro);

        // Worker management
        $('#worker-start-btn').on('click', startWorker);
        $('#worker-stop-btn').on('click', stopWorker);
        
        // Check worker status on load and every 5 seconds
        checkWorkerStatus();
        setInterval(checkWorkerStatus, 5000);

        // Handle create audit form
        $('#create-audit-form').on('submit', function(e) {
            e.preventDefault();
            const url = $('#audit-url').val();
            const device = $('#audit-device').val() || 'desktop';
            const messageDiv = $('#create-audit-message');
            const submitBtn = $(this).find('button[type="submit"]');

            submitBtn.prop('disabled', true).text('Creating...');
            messageDiv.html('');

            createAudit(url, device).done(function(response) {
                messageDiv.html('<div style="color: #00a32a; padding: 10px; background: #f0f6fc; border-left: 4px solid #00a32a; margin-top: 10px;">' +
                    'Audit created successfully! ID: ' + response.audit_id + '. ' +
                    'The built-in PHP worker will process this audit automatically when running.' +
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
                messageDiv.html('<div style="color: #d63638; padding: 10px; background: #fcf0f1; border-left: 4px solid #d63638; margin-top: 10px;">' + errorMsg + '</div>');
            }).always(function() {
                submitBtn.prop('disabled', false).text('Create Audit');
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
                status: $('#audit-filter-status').val(),
                search: $('#audit-search').val(),
                date_from: $('#audit-filter-date').val(),
            };
            const params = new URLSearchParams(filters);
            window.location.href = ajaxurl + '?action=perfaudit_export_csv&nonce=' + PerfAuditPro.workerNonce + '&' + params.toString();
        });

        $('#export-pdf-btn').on('click', function() {
            const filters = {
                status: $('#audit-filter-status').val(),
                search: $('#audit-search').val(),
                date_from: $('#audit-filter-date').val(),
            };
            const params = new URLSearchParams(filters);
            window.location.href = ajaxurl + '?action=perfaudit_export_pdf&nonce=' + PerfAuditPro.workerNonce + '&' + params.toString();
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

