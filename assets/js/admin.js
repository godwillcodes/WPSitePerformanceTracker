(function($) {
    'use strict';

    const PerfAuditPro = window.perfauditPro || {};

    function fetchAuditResults() {
        return $.ajax({
            url: PerfAuditPro.apiUrl + 'audit-results',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PerfAuditPro.nonce);
            }
        });
    }

    function fetchRUMMetrics() {
        return $.ajax({
            url: PerfAuditPro.apiUrl + 'rum-metrics',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PerfAuditPro.nonce);
            }
        });
    }

    function renderAuditTimelineChart(data) {
        const ctx = document.getElementById('audit-timeline-chart');
        if (!ctx) return;

        const labels = data.map(item => new Date(item.created_at).toLocaleDateString());
        const scores = data.map(item => parseFloat(item.performance_score) || 0);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Performance Score',
                    data: scores,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }

    function renderScoreDistributionChart(data) {
        const ctx = document.getElementById('score-distribution-chart');
        if (!ctx) return;

        const scores = data.map(item => parseFloat(item.performance_score) || 0);
        const ranges = {
            '0-50': 0,
            '50-70': 0,
            '70-90': 0,
            '90-100': 0
        };

        scores.forEach(score => {
            if (score < 50) ranges['0-50']++;
            else if (score < 70) ranges['50-70']++;
            else if (score < 90) ranges['70-90']++;
            else ranges['90-100']++;
        });

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: Object.keys(ranges),
                datasets: [{
                    data: Object.values(ranges),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    function renderRUMLCPChart(data) {
        const ctx = document.getElementById('rum-lcp-chart');
        if (!ctx) return;

        const labels = data.map(item => item.date);
        const avgLCP = data.map(item => parseFloat(item.avg_lcp) || 0);
        const p75LCP = data.map(item => parseFloat(item.p75_lcp) || 0);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average LCP',
                    data: avgLCP,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)'
                }, {
                    label: 'P75 LCP',
                    data: p75LCP,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function renderRUMCLSChart(data) {
        const ctx = document.getElementById('rum-cls-chart');
        if (!ctx) return;

        const labels = data.map(item => item.date);
        const avgCLS = data.map(item => parseFloat(item.avg_cls) || 0);
        const p75CLS = data.map(item => parseFloat(item.p75_cls) || 0);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average CLS',
                    data: avgCLS,
                    borderColor: 'rgb(153, 102, 255)',
                    backgroundColor: 'rgba(153, 102, 255, 0.2)'
                }, {
                    label: 'P75 CLS',
                    data: p75CLS,
                    borderColor: 'rgb(255, 159, 64)',
                    backgroundColor: 'rgba(255, 159, 64, 0.2)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
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
            html += '<tr>';
            html += '<td>' + escapeHtml(item.url) + '</td>';
            html += '<td>' + (item.performance_score ? parseFloat(item.performance_score).toFixed(2) : 'N/A') + '</td>';
            html += '<td>' + (item.largest_contentful_paint ? parseFloat(item.largest_contentful_paint).toFixed(2) + 'ms' : 'N/A') + '</td>';
            html += '<td>' + (item.total_blocking_time ? parseFloat(item.total_blocking_time).toFixed(2) + 'ms' : 'N/A') + '</td>';
            html += '<td>' + (item.cumulative_layout_shift ? parseFloat(item.cumulative_layout_shift).toFixed(4) : 'N/A') + '</td>';
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

    $(document).ready(function() {
        $.when(fetchAuditResults(), fetchRUMMetrics()).done(function(auditResponse, rumResponse) {
            const auditData = auditResponse[0] || [];
            const rumData = rumResponse[0] || [];

            if (auditData.length > 0) {
                renderAuditTimelineChart(auditData);
                renderScoreDistributionChart(auditData);
                renderRecentAuditsTable(auditData);
            }

            if (rumData.length > 0) {
                renderRUMLCPChart(rumData);
                renderRUMCLSChart(rumData);
            }
        }).fail(function() {
            console.error('Failed to load audit data');
        });
    });
})(jQuery);

