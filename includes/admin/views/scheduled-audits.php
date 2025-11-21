<?php
/**
 * Scheduled audits page view
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$schedules = get_option('perfaudit_pro_scheduled_audits', array());
?>
<div class="wrap perfaudit-pro-scheduled">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="perfaudit-pro-card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Scheduled Audits</h2>
            <button id="add-schedule-btn" class="button button-primary">+ Add Schedule</button>
        </div>
        <p>Set up recurring audits that run automatically. Perfect for monitoring your site's performance over time.</p>
    </div>

    <div id="schedules-list">
        <?php if (empty($schedules)): ?>
            <div class="perfaudit-pro-card">
                <p style="text-align: center; color: #64748b; padding: 40px;">No scheduled audits yet. Click "Add Schedule" to create one.</p>
            </div>
        <?php else: ?>
            <?php foreach ($schedules as $schedule): ?>
                <div class="perfaudit-pro-card schedule-item" data-schedule-id="<?php echo esc_attr($schedule['id']); ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <h3 style="margin-top: 0;">
                                <?php echo esc_html($schedule['name']); ?>
                                <span class="schedule-status <?php echo $schedule['enabled'] ? 'enabled' : 'disabled'; ?>">
                                    <?php echo $schedule['enabled'] ? '✓ Active' : '✗ Paused'; ?>
                                </span>
                            </h3>
                            <div class="schedule-details">
                                <p><strong>Frequency:</strong> <?php echo esc_html(ucfirst($schedule['frequency'])); ?></p>
                                <p><strong>URLs:</strong> <?php echo esc_html(is_array($schedule['urls']) ? implode(', ', $schedule['urls']) : $schedule['urls']); ?></p>
                                <p><strong>Device:</strong> <?php echo esc_html(ucfirst($schedule['device'] ?? 'desktop')); ?></p>
                                <?php if (!empty($schedule['last_run'])): ?>
                                    <p><strong>Last Run:</strong> <?php echo esc_html($schedule['last_run']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button class="button toggle-schedule" data-schedule-id="<?php echo esc_attr($schedule['id']); ?>" data-enabled="<?php echo $schedule['enabled'] ? '1' : '0'; ?>">
                                <?php echo $schedule['enabled'] ? 'Pause' : 'Resume'; ?>
                            </button>
                            <button class="button edit-schedule" data-schedule='<?php echo esc_attr(wp_json_encode($schedule)); ?>'>Edit</button>
                            <button class="button button-link-delete delete-schedule" data-schedule-id="<?php echo esc_attr($schedule['id']); ?>">Delete</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="schedule-modal" style="display: none;">
        <div class="perfaudit-pro-card" style="max-width: 600px; margin: 20px auto;">
            <h2 id="modal-title">Add Scheduled Audit</h2>
            <form id="schedule-form">
                <input type="hidden" id="schedule-id" name="schedule_id" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="schedule-name">Schedule Name</label></th>
                        <td><input type="text" id="schedule-name" name="name" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="schedule-urls">URLs to Audit</label></th>
                        <td>
                            <textarea id="schedule-urls" name="urls" rows="3" class="large-text" required placeholder="One URL per line"></textarea>
                            <p class="description">Enter one URL per line for multiple URLs</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="schedule-frequency">Frequency</label></th>
                        <td>
                            <select id="schedule-frequency" name="frequency" required>
                                <option value="hourly">Hourly</option>
                                <option value="daily" selected>Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="schedule-device">Device</label></th>
                        <td>
                            <select id="schedule-device" name="device" required>
                                <option value="desktop" selected>Desktop</option>
                                <option value="mobile">Mobile</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Schedule</button>
                    <button type="button" class="button cancel-modal">Cancel</button>
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.schedule-item {
    margin-bottom: 16px;
}
.schedule-status {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
    margin-left: 8px;
}
.schedule-status.enabled {
    background: #10b981;
    color: white;
}
.schedule-status.disabled {
    background: #64748b;
    color: white;
}
.schedule-details {
    margin-top: 12px;
    color: #64748b;
    font-size: 14px;
}
.schedule-details p {
    margin: 4px 0;
}
#schedule-modal {
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
    const nonce = '<?php echo wp_create_nonce('perfaudit_schedules'); ?>';

    $('#add-schedule-btn, .edit-schedule').on('click', function() {
        const scheduleData = $(this).data('schedule');
        if (scheduleData) {
            $('#schedule-id').val(scheduleData.id);
            $('#schedule-name').val(scheduleData.name);
            $('#schedule-frequency').val(scheduleData.frequency);
            $('#schedule-device').val(scheduleData.device || 'desktop');
            const urls = Array.isArray(scheduleData.urls) ? scheduleData.urls.join('\n') : scheduleData.urls;
            $('#schedule-urls').val(urls);
            $('#modal-title').text('Edit Schedule');
        } else {
            $('#schedule-form')[0].reset();
            $('#schedule-id').val('');
            $('#schedule-device').val('desktop');
            $('#modal-title').text('Add Scheduled Audit');
        }
        $('#schedule-modal').show();
    });

    $('.cancel-modal').on('click', function() {
        $('#schedule-modal').hide();
    });

    $('#schedule-form').on('submit', function(e) {
        e.preventDefault();
        const urls = $('#schedule-urls').val().split('\n').filter(url => url.trim());
        const scheduleData = {
            id: $('#schedule-id').val() || 'schedule_' + Date.now(),
            name: $('#schedule-name').val(),
            urls: urls,
            frequency: $('#schedule-frequency').val(),
            device: $('#schedule-device').val(),
            enabled: true
        };

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'perfaudit_save_schedule',
                nonce: nonce,
                schedule: JSON.stringify(scheduleData)
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

    $('.delete-schedule').on('click', function() {
        if (!confirm('Are you sure you want to delete this schedule?')) return;
        const scheduleId = $(this).data('schedule-id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'perfaudit_delete_schedule',
                nonce: nonce,
                schedule_id: scheduleId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    $('.toggle-schedule').on('click', function() {
        const scheduleId = $(this).data('schedule-id');
        const enabled = $(this).data('enabled') === '1' ? false : true;
        const schedules = <?php echo wp_json_encode($schedules); ?>;
        const schedule = schedules.find(s => s.id === scheduleId);
        if (schedule) {
            schedule.enabled = enabled;
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'perfaudit_save_schedule',
                    nonce: nonce,
                    schedule: JSON.stringify(schedule)
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

