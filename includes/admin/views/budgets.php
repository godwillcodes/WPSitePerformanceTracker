<?php
/**
 * Performance budgets page view
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$budgets = \PerfAuditPro\Admin\Performance_Budgets::get_budgets();
$violations = get_option('perfaudit_pro_budget_violations', array());
$violations = array_slice(array_reverse($violations), 0, 50); // Last 50
?>
<div class="wrap perfaudit-pro-budgets">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="perfaudit-pro-card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Performance Budgets</h2>
            <button id="add-budget-btn" class="button button-primary">+ Add Budget</button>
        </div>
        <p>Set performance budgets to track when metrics exceed limits. Think of it as your performance credit card limit.</p>
    </div>

    <div id="budgets-list">
        <?php if (empty($budgets)): ?>
            <div class="perfaudit-pro-card">
                <p style="text-align: center; color: #64748b; padding: 40px;">No budgets configured. Create one to start tracking!</p>
            </div>
        <?php else: ?>
            <?php foreach ($budgets as $budget): ?>
                <div class="perfaudit-pro-card budget-item" data-budget-id="<?php echo esc_attr($budget['id']); ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <h3 style="margin-top: 0;">
                                <?php echo esc_html($budget['name']); ?>
                                <span class="budget-status <?php echo $budget['enabled'] ? 'enabled' : 'disabled'; ?>">
                                    <?php echo $budget['enabled'] ? '✓ Active' : '✗ Paused'; ?>
                                </span>
                            </h3>
                            <div class="budget-details">
                                <p><strong>Metric:</strong> <?php echo esc_html(ucfirst(str_replace('_', ' ', $budget['metric']))); ?></p>
                                <p><strong>Budget Limit:</strong> <?php echo esc_html($budget['limit']); ?></p>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button class="button toggle-budget" data-budget-id="<?php echo esc_attr($budget['id']); ?>" data-enabled="<?php echo $budget['enabled'] ? '1' : '0'; ?>">
                                <?php echo $budget['enabled'] ? 'Pause' : 'Resume'; ?>
                            </button>
                            <button class="button edit-budget" data-budget='<?php echo esc_attr(wp_json_encode($budget)); ?>'>Edit</button>
                            <button class="button button-link-delete delete-budget" data-budget-id="<?php echo esc_attr($budget['id']); ?>">Delete</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($violations)): ?>
    <div class="perfaudit-pro-card" style="margin-top: 20px;">
        <h2>Recent Budget Violations</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Budget</th>
                    <th>Metric</th>
                    <th>Limit</th>
                    <th>Actual</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($violations as $violation): ?>
                <tr>
                    <td><?php echo esc_html($violation['budget_name']); ?></td>
                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $violation['metric']))); ?></td>
                    <td><?php echo esc_html($violation['limit']); ?></td>
                    <td style="color: #FF6B6B; font-weight: 600;"><?php echo esc_html($violation['actual']); ?></td>
                    <td><?php echo esc_html($violation['timestamp']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div id="budget-modal" style="display: none;">
        <div class="perfaudit-pro-card" style="max-width: 600px; margin: 20px auto;">
            <h2 id="modal-title">Add Performance Budget</h2>
            <form id="budget-form">
                <input type="hidden" id="budget-id" name="budget_id" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="budget-name">Budget Name</label></th>
                        <td><input type="text" id="budget-name" name="name" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="budget-metric">Metric</label></th>
                        <td>
                            <select id="budget-metric" name="metric" required>
                                <option value="largest_contentful_paint">LCP (Largest Contentful Paint)</option>
                                <option value="first_input_delay">FID (First Input Delay)</option>
                                <option value="cumulative_layout_shift">CLS (Cumulative Layout Shift)</option>
                                <option value="first_contentful_paint">FCP (First Contentful Paint)</option>
                                <option value="time_to_first_byte">TTFB (Time to First Byte)</option>
                                <option value="total_blocking_time">TBT (Total Blocking Time)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="budget-limit">Budget Limit</label></th>
                        <td><input type="number" id="budget-limit" name="limit" step="0.01" required /></td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Budget</button>
                    <button type="button" class="button cancel-modal">Cancel</button>
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.budget-item {
    margin-bottom: 16px;
}
.budget-status {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
    margin-left: 8px;
}
.budget-status.enabled {
    background: #10b981;
    color: white;
}
.budget-status.disabled {
    background: #64748b;
    color: white;
}
.budget-details {
    margin-top: 12px;
    color: #64748b;
    font-size: 14px;
}
.budget-details p {
    margin: 4px 0;
}
#budget-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    overflow-y: auto;
    padding: 40px 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce('perfaudit_budgets'); ?>';

    $('#add-budget-btn, .edit-budget').on('click', function() {
        const budgetData = $(this).data('budget');
        if (budgetData) {
            $('#budget-id').val(budgetData.id);
            $('#budget-name').val(budgetData.name);
            $('#budget-metric').val(budgetData.metric);
            $('#budget-limit').val(budgetData.limit);
            $('#modal-title').text('Edit Budget');
        } else {
            $('#budget-form')[0].reset();
            $('#budget-id').val('');
            $('#modal-title').text('Add Performance Budget');
        }
        $('#budget-modal').show();
    });

    $('.cancel-modal').on('click', function() {
        $('#budget-modal').hide();
    });

    $('#budget-form').on('submit', function(e) {
        e.preventDefault();
        const budgetData = {
            id: $('#budget-id').val() || 'budget_' + Date.now(),
            name: $('#budget-name').val(),
            metric: $('#budget-metric').val(),
            limit: parseFloat($('#budget-limit').val()),
            enabled: true
        };

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'perfaudit_save_budget',
                nonce: nonce,
                budget: JSON.stringify(budgetData)
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });

    $('.delete-budget').on('click', function() {
        if (!confirm('Are you sure you want to delete this budget?')) return;
        const budgetId = $(this).data('budget-id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'perfaudit_delete_budget',
                nonce: nonce,
                budget_id: budgetId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    $('.toggle-budget').on('click', function() {
        const budgetId = $(this).data('budget-id');
        const enabled = $(this).data('enabled') === '1' ? false : true;
        const budgets = <?php echo wp_json_encode($budgets); ?>;
        const budget = budgets.find(b => b.id === budgetId);
        if (budget) {
            budget.enabled = enabled;
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'perfaudit_save_budget',
                    nonce: nonce,
                    budget: JSON.stringify(budget)
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });
});
</script>

