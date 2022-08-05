<?php

namespace Teplosocial\models;

class ConstValueProvider
{
    public static function get_values(): array
    {
        $reflection = new \ReflectionClass(static::class);

        $values = array_values($reflection->getConstants());

        return $values;
    }
}

class NotificationTypes extends ConstValueProvider
{
    const NEW_CERTIFICATE = 'new_certificate';
    const NEW_COURSE = 'new_course';
    const NEW_TRACK = 'new_track';
    const POINTS_INCREASING = 'points_increasing';

    public static function get_notification_type_by_object_type(string $type): string
    {
        switch ($type) {
            case NotificationConnectedObjectNames::CERTIFICATE:
                return static::NEW_CERTIFICATE;
                break;
            case NotificationConnectedObjectNames::COURSE:
                return static::NEW_COURSE;
                break;
            case NotificationConnectedObjectNames::TRACK:
                return static::NEW_TRACK;
                break;
        }

        return "";
    }
}

class NotificationMessages
{
    private static array $messages = [];

    private static function load_messages(): void
    {
        try {
            $raw_data = file_get_contents(dirname(__FILE__) . '/messages.json');

            ['messages' => $messages] = json_decode($raw_data, true);

            if (!empty($messages)) {
                static::$messages = $messages;
            }
        } catch (\Exception $error) {
            trigger_error(\__('An error occured when requesting the notification message list.', 'tps'), E_USER_WARNING);
        }
    }
    public static function get_message_by_notification_type(string $notification_type): string
    {
        if (empty(static::$messages)) {
            static::load_messages();

            if (!empty(static::$messages[$notification_type])) {
                return static::$messages[$notification_type];
            }
        }

        return "";
    }
}

class NotificationStatusNames extends ConstValueProvider
{
    const CREATED = 'created';
    const DELIVERED = 'delivered';
    const READ = 'read';
    const DELETED = 'deleted';
}

class NotificationLearningObjectNames extends ConstValueProvider
{
    const COURSE = 'tps_course';
    const TRACK = 'tps_track';
}

class NotificationConnectedObjectNames extends NotificationLearningObjectNames
{
    const CERTIFICATE = 'certificate';
}

class Notifications
{
    const DB_MAIN_TABLE_NAME = 'notifications';
    const DB_STATUS_TABLE_NAME = 'notification_statuses';
    const DB_RELATIONSHIP_TABLE_NAME = 'notification_relationships';
    const CACHE_STORAGE_NAME = 'tps_notifications';

    public static function create_schema(): bool
    {
        try {
            require_once \ABSPATH . 'wp-admin/includes/upgrade.php';

            global $wpdb;

            $success = 0;

            $main_sql = file_get_contents(dirname(__FILE__) . '/schema/main-schema.sql');
            $status_sql = file_get_contents(dirname(__FILE__) . '/schema/status-schema.sql');
            $relationship_sql = file_get_contents(dirname(__FILE__) . '/schema/relationship-schema.sql');

            if (
                \maybe_create_table($wpdb->prefix . self::DB_MAIN_TABLE_NAME, sprintf($main_sql, $wpdb->prefix))
            ) {
                $success += 1;
            }

            if (\maybe_create_table($wpdb->prefix . self::DB_STATUS_TABLE_NAME, sprintf($status_sql, $wpdb->prefix, $wpdb->prefix))) {
                $success += 1;
            }

            if (
                \maybe_create_table($wpdb->prefix . self::DB_RELATIONSHIP_TABLE_NAME, sprintf($relationship_sql, $wpdb->prefix, $wpdb->prefix))
            ) {
                $success += 1;
            }

            return $success === 3;
        } catch (\Exception $error) {
            return false;
        }
    }

    public static function delete_all_data(): bool
    {
        global $wpdb;

        $main_table_name = $wpdb->prefix . self::DB_MAIN_TABLE_NAME;
        $status_table_name = $wpdb->prefix . self::DB_STATUS_TABLE_NAME;
        $relationship_table_name = $wpdb->prefix . self::DB_RELATIONSHIP_TABLE_NAME;


        $deleted_table_count = $wpdb->query(<<<SQL
            DROP TABLE IF EXISTS {$main_table_name}, {$status_table_name}, {$relationship_table_name}
        SQL);

        return $deleted_table_count !== false;
    }

    private static function add_new(int $user_id, string $type): int
    {
        global $wpdb;

        if ($wpdb->insert(
            $wpdb->prefix . self::DB_MAIN_TABLE_NAME,
            [
                'user_id' => $user_id,
                'type' => $type
            ],
            ['%d', '%s']
        ) !== false) {
            return $wpdb->insert_id;
        }

        return 0;
    }

    public static function status_will_update(int $notification_id, int $is_active = 0): bool
    {
        global $wpdb;

        return boolval($wpdb->update(
            $wpdb->prefix . self::DB_STATUS_TABLE_NAME,
            ['is_active' => $is_active],
            [
                'notification_id' => $notification_id,
                'is_active' => 1
            ],
            ['%d'],
            ['%d', '%d'],
        ));
    }

    public static function add_new_status(int $notification_id, string $status_name): bool
    {
        global $wpdb;

        return boolval($wpdb->insert(
            $wpdb->prefix . self::DB_STATUS_TABLE_NAME,
            [
                'notification_id' => $notification_id,
                'status_name' => $status_name
            ],
            ['%d', '%s']
        ));
    }

    private static function add_new_connected_object(int $notification_id, int $object_id, string $object_type): bool
    {
        global $wpdb;

        return boolval($wpdb->insert(
            $wpdb->prefix . self::DB_RELATIONSHIP_TABLE_NAME,
            [
                'notification_id' => $notification_id,
                'object_id' => $object_id,
                'object_type' => $object_type
            ],
            ['%d', '%d', '%s']
        ));
    }

    public static function new_certificate_handler(int $certificate_id, int $user_id): void
    {

        $notification_id = static::add_new($user_id, NotificationTypes::NEW_CERTIFICATE);

        if ($notification_id) {
            static::add_new_status($notification_id, NotificationStatusNames::CREATED);
            static::add_new_connected_object($notification_id, $certificate_id, NotificationConnectedObjectNames::CERTIFICATE);

            $account_link = \get_home_url() . '/dashboard';

            static::insert_item_cache($notification_id, $user_id, sprintf(NotificationMessages::get_message_by_notification_type(NotificationTypes::NEW_CERTIFICATE), $account_link));
        }
    }

    public static function new_learning_object_handler(string $new_status, string $old_status, \WP_Post $object): void
    {
        [
            'ID'         => $object_id,
            'post_type'  => $object_type,
            'post_title' => $object_title
        ] = (array) $object;

        if (
            !($old_status !== 'publish' && $new_status === 'publish')
            || !in_array($object_type, NotificationLearningObjectNames::get_values())
        ) {
            return;
        }

        $user_ids = \get_users(['fields' => 'ID']);

        if (empty($user_ids)) {
            trigger_error(\__('No users to be notified.', 'tps'), E_USER_WARNING);

            return;
        }

        global $wpdb;

        $main_table_name = $wpdb->prefix . self::DB_MAIN_TABLE_NAME;

        $notification_type = NotificationTypes::get_notification_type_by_object_type($object_type);

        $sql_main_table_rows = array_map(fn ($user_id) => "({$user_id}, '{$notification_type}')", $user_ids);

        $sql_main_table_rows = implode(', ', $sql_main_table_rows);

        $new_notification_count = $wpdb->query(<<<SQL
            INSERT INTO {$main_table_name} (user_id, type)
                VALUES {$sql_main_table_rows}
        SQL);

        if ($new_notification_count !== false) {
            $item_cache = [];
            $notification_message = sprintf(
                NotificationMessages::get_message_by_notification_type($notification_type),
                $object_title
            );
            $status_table_name = $wpdb->prefix . self::DB_STATUS_TABLE_NAME;
            $status_name = NotificationStatusNames::CREATED;
            $relationship_table_name = $wpdb->prefix . self::DB_RELATIONSHIP_TABLE_NAME;
            $connected_object_type = $object_type;
            $sql_status_table_rows = [];
            $sql_relationship_table_rows = [];
            $notification_id = $wpdb->insert_id;
            $limit = $wpdb->insert_id + $new_notification_count;

            while ($notification_id < $limit) {
                $sql_status_table_rows[] = "({$notification_id}, '{$status_name}')";
                $sql_relationship_table_rows[] = "({$notification_id}, {$object_id}, '{$connected_object_type}')";

                $item_cache[] = [$notification_id, $notification_message];

                $notification_id++;
            }

            $sql_status_table_rows = implode(', ', $sql_status_table_rows);

            $new_status_count = $wpdb->query(<<<SQL
                INSERT INTO {$status_table_name} (notification_id, status_name)
                    VALUES {$sql_status_table_rows}
            SQL);

            if ($new_status_count === false) {
                trigger_error(sprintf(\__('No "%s" notification statuses have been added.', 'tps'), $notification_type), E_USER_WARNING);
            }

            $sql_relationship_table_rows = implode(', ', $sql_relationship_table_rows);

            $new_relationship_count = $wpdb->query(<<<SQL
            INSERT INTO {$relationship_table_name} (notification_id, object_id, object_type)
                VALUES {$sql_relationship_table_rows}
            SQL);

            if ($new_relationship_count === false) {
                trigger_error(sprintf(\__('No "%s" notification relationships have been added.', 'tps'), $notification_type), E_USER_WARNING);
            }

            static::insert_items_cache($item_cache, $user_ids);
        } else {
            trigger_error(sprintf(\__('No notifications of type "%s" have been added.', 'tps'), $notification_type), E_USER_WARNING);
        }
    }

    public static function insert_items_cache(array $items, array $user_ids): void
    {
        try {
            $mongo_client = MongoClient::getInstance();

            $notifications = [];

            $now = time() * 1000;

            foreach ($items as $i => $item) {
                $user_id = (int) $user_ids[$i];
                [0 => $notification_id, 1 => $text] = $item;

                $notifications[] = [
                    'externalId' => $notification_id,
                    'userId'     => $user_id,
                    'timestamp'  => $now,
                    'status'     => NotificationStatusNames::CREATED,
                    'text'       => $text,
                ];
            }

            $collection = $mongo_client->{static::CACHE_STORAGE_NAME}->notification_list;

            $collection->insertMany($notifications);
        } catch (\Exception $error) {
            trigger_error(\__('An error occured when inserting items to the notification cache.', 'tps'), E_USER_WARNING);
        }
    }

    public static function insert_item_cache(int $notification_id, int $user_id, string $text): void
    {
        try {
            $mongo_client = MongoClient::getInstance();
            $collection = $mongo_client->{static::CACHE_STORAGE_NAME}->notification_list;

            $collection->insertOne(
                [
                    'externalId' => $notification_id,
                    'userId'     => $user_id,
                    'timestamp'  => time() * 1000,
                    'status'     => NotificationStatusNames::CREATED,
                    'text'       => $text,
                ]
            );
        } catch (\Exception $error) {
            trigger_error(\__('An error occured when inserting an item to the notification cache.', 'tps'), E_USER_WARNING);
        }
    }

    public static function update_item_cache(int $notification_id, int $user_id, array $item): void
    {
        try {
            $mongo_client = MongoClient::getInstance();
            $collection = $mongo_client->{static::CACHE_STORAGE_NAME}->notification_list;

            $update_cache_result = $collection->findOneAndUpdate([
                'externalId' => $notification_id,
                'userId'     => $user_id,
            ], ['$set' => $item]);

            if (is_null($update_cache_result)) {
                throw new \Exception(\__('No item found to update.', 'tps'));
            }
        } catch (\Exception $error) {
            trigger_error(\__('An error occured when updating the item in the notification cache.', 'tps'), E_USER_WARNING);
        }
    }
}
