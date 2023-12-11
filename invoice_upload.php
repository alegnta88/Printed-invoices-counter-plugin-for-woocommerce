<?php
/*
Plugin Name: Custom Order Handler
Description: Custom functionality to handle order data for 24 hours.
Version: 1.0
Author: Alegnta Lolamo
*/

register_activation_hook(__FILE__, 'custom_order_handler_activate');
register_deactivation_hook(__FILE__, 'custom_order_handler_deactivate');
add_action('init', 'handle_order_data');
add_action('wp', 'schedule_daily_cleanup'); // Schedule daily cleanup

function custom_order_handler_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'invoicer';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        document_type varchar(255) NOT NULL,
        order_ids varchar(255) NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function custom_order_handler_deactivate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'invoicer';

    // Delete the existing table
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

function handle_order_data() {
    if (isset($_GET['document_type']) && isset($_GET['order_ids'])) {
        $documentType = sanitize_text_field($_GET['document_type']);
        $orderIds = sanitize_text_field($_GET['order_ids']);

        global $wpdb;

        $table_name = $wpdb->prefix . 'invoicer';

        // Split multiple order IDs based on 'x' delimiter
        $orderIdsArray = explode('x', $orderIds);

        foreach ($orderIdsArray as $singleOrderId) {
            // Trim and sanitize each order ID
            $singleOrderId = sanitize_text_field(trim($singleOrderId));

            if (empty($singleOrderId)) {
                continue; // Skip empty order IDs
            }

            // Check if the order number already exists
            $existingOrderCheck = $wpdb->get_results("SELECT * FROM $table_name WHERE order_ids = '$singleOrderId'");

            if (empty($existingOrderCheck)) {
                // Order number doesn't exist, proceed with insertion
                $wpdb->insert(
                    $table_name,
                    array('document_type' => $documentType, 'order_ids' => $singleOrderId, 'timestamp' => current_time('mysql', 1)),
                    array('%s', '%s', '%s')
                );
            } else {
                // Order number already exists, handle accordingly (update or skip)
                echo "Order number $singleOrderId already exists. You may choose to update the record or skip insertion.";
            }
        }

        echo "Records inserted successfully";
    }
}

function schedule_daily_cleanup() {
    // Commenting out the cleanup functionality
    /*
    if (!wp_next_scheduled('custom_order_handler_cleanup')) {
        // Schedule the cleanup event daily at midnight
        wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'custom_order_handler_cleanup');
    }
    */
}

// Commenting out the cleanup functionality
/*
// Hook for cleaning up old records
add_action('custom_order_handler_cleanup', 'cleanup_old_records');

function cleanup_old_records() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'invoicer';

    // Calculate the timestamp for records older than 24 hours
    $older_than_timestamp = date('Y-m-d H:i:s', strtotime('-24 hours'));

    // Delete records older than 24 hours
    $wpdb->query("DELETE FROM $table_name WHERE timestamp < '$older_than_timestamp'");
}
*/