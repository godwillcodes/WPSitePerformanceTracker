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
        if (score >= 90) return { status: 'good', label: 'Slaying', color: '#10b981' };
        if (score >= 70) return { status: 'warning', label: 'Could Be Better', color: '#f59e0b' };
        return { status: 'bad', label: 'Needs Work', color: '#ef4444' };
    }

    function getLCPStatus(lcp) {
        if (lcp <= 2500) return { status: 'good', label: 'Chef\'s Kiss', color: '#10b981' };
        if (lcp <= 4000) return { status: 'warning', label: 'It\'s Giving Slow', color: '#f59e0b' };
        return { status: 'bad', label: 'Users Are Gone', color: '#ef4444' };
    }

    function getCLSStatus(cls) {
        if (cls <= 0.1) return { status: 'good', label: 'Smooth Like Butter', color: '#10b981' };
        if (cls <= 0.25) return { status: 'warning', label: 'A Bit Janky', color: '#f59e0b' };
        return { status: 'bad', label: 'Identity Crisis', color: '#ef4444' };
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
        let html = '<div class="perfaudit-pro-recommendations"><h4>💡 Recommendations:</h4><ul>';
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
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📊</div><p>No audit data available yet. Create your first audit to get started!</p></div>';
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
        gradient.addColorStop(0, 'rgba(102, 126, 234, 0.3)');
        gradient.addColorStop(1, 'rgba(102, 126, 234, 0.05)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Performance Score',
                    data: scores,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: 'rgb(102, 126, 234)',
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
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🥧</div><p>No audit data available yet.</p></div>';
            return;
        }

        const scores = data.map(item => parseFloat(item.performance_score) || 0).filter(score => score > 0);
        if (scores.length === 0) {
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📊</div><p>No completed audits with scores yet.</p></div>';
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
                        'rgba(16, 185, 129, 0.9)',
                        'rgba(59, 130, 246, 0.9)',
                        'rgba(245, 158, 11, 0.9)',
                        'rgba(239, 68, 68, 0.9)'
                    ],
                    borderWidth: 2,
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
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon">⚡</div><p>No RUM data yet. Metrics will appear as users visit your site!</p></div>';
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
        gradient1.addColorStop(0, 'rgba(239, 68, 68, 0.3)');
        gradient1.addColorStop(1, 'rgba(239, 68, 68, 0.05)');

        const gradient2 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient2.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
        gradient2.addColorStop(1, 'rgba(59, 130, 246, 0.05)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average LCP',
                    data: avgLCP,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: gradient1,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'P75 LCP',
                    data: p75LCP,
                    borderColor: 'rgb(59, 130, 246)',
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
            ctx.parentElement.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🎨</div><p>No RUM data yet. Metrics will appear as users visit your site!</p></div>';
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
        gradient1.addColorStop(0, 'rgba(139, 92, 246, 0.3)');
        gradient1.addColorStop(1, 'rgba(139, 92, 246, 0.05)');

        const gradient2 = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient2.addColorStop(0, 'rgba(245, 158, 11, 0.3)');
        gradient2.addColorStop(1, 'rgba(245, 158, 11, 0.05)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average CLS',
                    data: avgCLS,
                    borderColor: 'rgb(139, 92, 246)',
                    backgroundColor: gradient1,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'P75 CLS',
                    data: p75CLS,
                    borderColor: 'rgb(245, 158, 11)',
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

    function renderRecentAuditsTable(data) {
        const container = document.getElementById('recent-audits-table');
        if (!container) return;

        if (!data || data.length === 0) {
            container.innerHTML = '<p>No audits found.</p>';
            return;
        }

        let html = '<table><thead><tr><th>URL</th><th>Score</th><th>LCP</th><th>FID</th><th>CLS</th><th>Date</th></tr></thead><tbody>';

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

            html += '<tr>';
            html += '<td>' + escapeHtml(item.url) + '</td>';
            html += '<td>' + scoreBadge + '</td>';
            html += '<td>' + (item.largest_contentful_paint ? parseFloat(item.largest_contentful_paint).toFixed(0) + 'ms' : 'N/A') + '</td>';
            html += '<td>' + (item.total_blocking_time ? parseFloat(item.total_blocking_time).toFixed(0) + 'ms' : 'N/A') + '</td>';
            html += '<td>' + (item.cumulative_layout_shift ? parseFloat(item.cumulative_layout_shift).toFixed(3) : 'N/A') + '</td>';
            html += '<td>' + new Date(item.created_at).toLocaleString() + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function createAudit(url) {
        return $.ajax({
            url: PerfAuditPro.apiUrl + 'create-audit',
            method: 'POST',
            data: JSON.stringify({
                url: url,
                audit_type: 'lighthouse'
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PerfAuditPro.nonce);
            }
        });
    }

    $(document).ready(function() {
        console.log('PerfAudit Pro: Initializing dashboard...', PerfAuditPro);

        // Handle create audit form
        $('#create-audit-form').on('submit', function(e) {
            e.preventDefault();
            const url = $('#audit-url').val();
            const messageDiv = $('#create-audit-message');
            const submitBtn = $(this).find('button[type="submit"]');

            submitBtn.prop('disabled', true).text('Creating...');
            messageDiv.html('');

            createAudit(url).done(function(response) {
                messageDiv.html('<div style="color: #00a32a; padding: 10px; background: #f0f6fc; border-left: 4px solid #00a32a; margin-top: 10px;">' +
                    'Audit created successfully! ID: ' + response.audit_id + '. ' +
                    'Note: An external worker is required to process the audit. See WORKER_SETUP.md for details.' +
                    '</div>');
                $('#audit-url').val('');
                
                // Reload data after a short delay
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }).fail(function(jqXHR) {
                let errorMsg = 'Failed to create audit. ';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMsg += jqXHR.responseJSON.message;
                } else {
                    errorMsg += 'Please check the console for details.';
                }
                messageDiv.html('<div style="color: #d63638; padding: 10px; background: #fcf0f1; border-left: 4px solid #d63638; margin-top: 10px;">' + errorMsg + '</div>');
            }).always(function() {
                submitBtn.prop('disabled', false).text('Create Audit');
            });
        });
        
        $.when(fetchAuditResults(), fetchRUMMetrics()).done(function(auditResponse, rumResponse) {
            console.log('PerfAudit Pro: API responses received', auditResponse, rumResponse);
            
            // jQuery $.when with $.ajax returns [data, textStatus, jqXHR]
            // WordPress REST API returns JSON array directly
            let auditData = [];
            let rumData = [];
            
            if (auditResponse && auditResponse.length > 0) {
                auditData = Array.isArray(auditResponse[0]) ? auditResponse[0] : (auditResponse[0] || []);
            }
            
            if (rumResponse && rumResponse.length > 0) {
                rumData = Array.isArray(rumResponse[0]) ? rumResponse[0] : (rumResponse[0] || []);
            }

            console.log('PerfAudit Pro: Processed data', auditData, rumData);

            // Always render charts, they'll show empty state if no data
            renderAuditTimelineChart(auditData);
            renderScoreDistributionChart(auditData);
            renderRecentAuditsTable(auditData);
            renderRUMLCPChart(rumData);
            renderRUMCLSChart(rumData);
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

