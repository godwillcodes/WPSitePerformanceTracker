<?php
/**
 * Rules configuration page view
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$rules = \PerfAuditPro\Admin\Rules_Page::get_rules();
?>
<div class="wrap perfaudit-pro-rules">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="perfaudit-pro-card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Performance Rules</h2>
            <button id="add-rule-btn" class="button button-primary">+ Add New Rule</button>
        </div>
        <p>Configure performance thresholds and enforcement actions. Rules are evaluated after each audit.</p>
    </div>

    <div id="rules-list">
        <?php foreach ($rules as $rule): ?>
            <div class="perfaudit-pro-card rule-item" data-rule-id="<?php echo esc_attr($rule['id']); ?>">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <h3 style="margin-top: 0;">
                            <?php echo esc_html($rule['name']); ?>
                            <span class="rule-status <?php echo $rule['enabled'] ? 'enabled' : 'disabled'; ?>">
                                <?php echo $rule['enabled'] ? '✓ Enabled' : '✗ Disabled'; ?>
                            </span>
                        </h3>
                        <div class="rule-details">
                            <p><strong>Metric:</strong> <?php echo esc_html(ucfirst(str_replace('_', ' ', $rule['metric']))); ?></p>
                            <p><strong>Threshold:</strong> <?php echo esc_html($rule['operator']); ?> <?php echo esc_html($rule['threshold']); ?></p>
                            <p><strong>Enforcement:</strong> <?php echo esc_html(ucfirst($rule['enforcement'])); ?></p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button class="button toggle-rule" data-rule-id="<?php echo esc_attr($rule['id']); ?>" data-enabled="<?php echo $rule['enabled'] ? '1' : '0'; ?>">
                            <?php echo $rule['enabled'] ? 'Disable' : 'Enable'; ?>
                        </button>
                        <button class="button edit-rule" data-rule='<?php echo esc_attr(wp_json_encode($rule)); ?>'>Edit</button>
                        <button class="button button-link-delete delete-rule" data-rule-id="<?php echo esc_attr($rule['id']); ?>">Delete</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="rule-modal" style="display: none;">
        <div class="perfaudit-pro-card" style="max-width: 600px; margin: 20px auto;">
            <h2 id="modal-title">Add New Rule</h2>
            <form id="rule-form">
                <input type="hidden" id="rule-id" name="rule_id" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="rule-name">Rule Name</label></th>
                        <td><input type="text" id="rule-name" name="name" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rule-metric">Metric</label></th>
                        <td>
                            <select id="rule-metric" name="metric" required>
                                <option value="performance_score">Performance Score</option>
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
                        <th scope="row"><label for="rule-operator">Operator</label></th>
                        <td>
                            <select id="rule-operator" name="operator" required>
                                <option value="gt">Greater Than (&gt;)</option>
                                <option value="gte">Greater Than or Equal (&gt;=)</option>
                                <option value="lt">Less Than (&lt;)</option>
                                <option value="lte">Less Than or Equal (&lt;=)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rule-threshold">Threshold</label></th>
                        <td><input type="number" id="rule-threshold" name="threshold" step="0.01" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rule-enforcement">Enforcement</label></th>
                        <td>
                            <select id="rule-enforcement" name="enforcement" required>
                                <option value="hard">Hard (Fail audit)</option>
                                <option value="soft">Soft (Warning only)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Rule</button>
                    <button type="button" class="button cancel-modal">Cancel</button>
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.rule-item {
    margin-bottom: 16px;
}
.rule-status {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
    margin-left: 8px;
}
.rule-status.enabled {
    background: #10b981;
    color: white;
}
.rule-status.disabled {
    background: #64748b;
    color: white;
}
.rule-details {
    margin-top: 12px;
    color: #64748b;
    font-size: 14px;
}
.rule-details p {
    margin: 4px 0;
}
#rule-modal {
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
    const nonce = '<?php echo wp_create_nonce('perfaudit_rules'); ?>';

    $('#add-rule-btn, .edit-rule').on('click', function() {
        const ruleData = $(this).data('rule');
        if (ruleData) {
            $('#rule-id').val(ruleData.id);
            $('#rule-name').val(ruleData.name);
            $('#rule-metric').val(ruleData.metric);
            $('#rule-operator').val(ruleData.operator);
            $('#rule-threshold').val(ruleData.threshold);
            $('#rule-enforcement').val(ruleData.enforcement);
            $('#modal-title').text('Edit Rule');
        } else {
            $('#rule-form')[0].reset();
            $('#rule-id').val('');
            $('#modal-title').text('Add New Rule');
        }
        $('#rule-modal').show();
    });

    $('.cancel-modal').on('click', function() {
        $('#rule-modal').hide();
    });

    $('#rule-form').on('submit', function(e) {
        e.preventDefault();
        const ruleData = {
            id: $('#rule-id').val() || 'rule_' + Date.now(),
            name: $('#rule-name').val(),
            metric: $('#rule-metric').val(),
            operator: $('#rule-operator').val(),
            threshold: parseFloat($('#rule-threshold').val()),
            enforcement: $('#rule-enforcement').val(),
            enabled: true
        };

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'perfaudit_save_rule',
                nonce: nonce,
                rule: JSON.stringify(ruleData)
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

    $('.delete-rule').on('click', function() {
        if (!confirm('Are you sure you want to delete this rule?')) return;
        const ruleId = $(this).data('rule-id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'perfaudit_delete_rule',
                nonce: nonce,
                rule_id: ruleId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    $('.toggle-rule').on('click', function() {
        const ruleId = $(this).data('rule-id');
        const enabled = $(this).data('enabled') === '1' ? false : true;
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'perfaudit_toggle_rule',
                nonce: nonce,
                rule_id: ruleId,
                enabled: enabled
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
});
</script>

