CREATE TABLE IF NOT EXISTS `%snotification_statuses` ( 
    `status_id` BIGINT(20) AUTO_INCREMENT, 
    `notification_id` BIGINT(20), 
    `status_name` VARCHAR(32) NOT NULL, 
    `is_active` BOOLEAN DEFAULT TRUE,
    `modified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`status_id`, `notification_id`)
)