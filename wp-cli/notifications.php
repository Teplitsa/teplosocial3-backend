<?php

namespace Teplosocial\cli;

use Teplosocial\models\{Notifications, Certificate};


if (!class_exists('WP_CLI')) {
    return;
}

class NotificationCLI
{
    public function create_schema($args, $assoc_args)
    {
        if (!Notifications::create_schema()) {
            \WP_CLI::error(\__('An error occured when creating the notification schema.', 'tps'));
        }

        \WP_CLI::success(__('The notification schema has successfuly created.', 'tps'));
    }

    public function delete_storage_data($args, $assoc_args)
    {
        if (!Notifications::delete_all_data()) {
            \WP_CLI::error(\__('An error occured when deleting the notification data.', 'tps'));
        }

        \WP_CLI::success(__('The notification data has successfuly deleted.', 'tps'));
    }

    public function mock_new_certificate($args, $assoc_args)
    {
        $admin_id = "4184";

        $default_course_name = '«Тестовый курс ' . md5((string) time()) . '»';

        ['user_id' => $user_id, 'course_name' => $course_name] = $assoc_args;

        Certificate::save_certificate(intval($user_id ?? $admin_id), $course_name ?? $default_course_name, 'course');

        \WP_CLI::success(__('The certificate has successfuly added.', 'tps'));
    }
}

\WP_CLI::add_command('tps_notifications', '\Teplosocial\cli\NotificationCLI');
