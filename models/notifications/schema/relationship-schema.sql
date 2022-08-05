CREATE TABLE IF NOT EXISTS `%snotification_relationships` ( 
    `notification_relationship_id` BIGINT(20) AUTO_INCREMENT, 
    `notification_id` BIGINT(20),
    `object_id` BIGINT(20) NOT NULL,
    `object_type` VARCHAR(32) NOT NULL, 
    PRIMARY KEY (`notification_relationship_id`, `notification_id`)
)