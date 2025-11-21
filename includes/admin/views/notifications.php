<?php
/**
 * Notifications page view
 *
 * @package PerfAuditPro
 * @namespace PerfAuditPro\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$notifications = get_option('perfaudit_pro_notifications', array());
$notifications = array_reverse($notifications); // Most recent first
?>
<div class="wrap perfaudit-pro-notifications">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="perfaudit-pro-card" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0;">Performance Alerts</h2>
            <button id="mark-all-read" class="button">Mark All as Read</button>
        </div>
        <p>Notifications for rule violations and performance issues.</p>
    </div>

    <div id="notifications-list">
        <?php if (empty($notifications)): ?>
            <div class="perfaudit-pro-card">
                <p style="text-align: center; color: #64748b; padding: 40px;">No notifications yet. You're all good! 🎉</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="perfaudit-pro-card notification-item <?php echo empty($notification['read']) ? 'unread' : ''; ?>" data-notification-id="<?php echo esc_attr($notification['id']); ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <h3 style="margin-top: 0;">
                                <?php if ($notification['type'] === 'violation'): ?>
                                    ⚠️ Performance Violations Detected
                                <?php else: ?>
                                    ℹ️ <?php echo esc_html(ucfirst($notification['type'])); ?>
                                <?php endif; ?>
                                <?php if (empty($notification['read'])): ?>
                                    <span style="background: #FF6B6B; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 8px;">New</span>
                                <?php endif; ?>
                            </h3>
                            <p style="color: #64748b; margin: 8px 0;">
                                <strong>Time:</strong> <?php echo esc_html($notification['timestamp']); ?>
                            </p>
                            <?php if (!empty($notification['violations'])): ?>
                                <div style="margin-top: 12px;">
                                    <strong>Violations:</strong>
                                    <ul style="margin: 8px 0; padding-left: 20px;">
                                        <?php foreach ($notification['violations'] as $violation): ?>
                                            <li style="color: #FF6B6B;"><?php echo esc_html($violation['message']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button class="button mark-read" data-notification-id="<?php echo esc_attr($notification['id']); ?>">
                            <?php echo empty($notification['read']) ? 'Mark Read' : 'Mark Unread'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-item {
    margin-bottom: 16px;
    border-left: 4px solid #007BFF;
}
.notification-item.unread {
    background: #f0f7ff;
    border-left-color: #FF6B6B;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.mark-read, #mark-all-read').on('click', function() {
        const notificationId = $(this).data('notification-id');
        const notifications = <?php echo wp_json_encode($notifications); ?>;
        
        if ($(this).attr('id') === 'mark-all-read') {
            notifications.forEach(n => n.read = true);
        } else {
            const notif = notifications.find(n => n.id === notificationId);
            if (notif) {
                notif.read = !notif.read;
            }
        }

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'perfaudit_update_notifications',
                nonce: '<?php echo wp_create_nonce('perfaudit_notifications'); ?>',
                notifications: JSON.stringify(notifications)
            },
            success: function() {
                location.reload();
            }
        });
    });
});
</script>

