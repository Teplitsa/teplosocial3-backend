CREATE TABLE IF NOT EXISTS `%snotifications` ( 
    `notification_id` BIGINT(20) AUTO_INCREMENT, 
    `user_id` BIGINT(20), 
    `type` VARCHAR(32) NOT NULL, 
    PRIMARY KEY (`notification_id`, `user_id`)
)