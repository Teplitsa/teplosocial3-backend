<?php

namespace Teplosocial\models;

final class MongoClient
{
    private static ?MongoClient $instance = null;

    private static ?\MongoDB\Client $client = null;

    public static function getInstance(): \MongoDB\Client
    {
        if (static::$instance === null) {
            static::$instance = new static();

            try {
                static::$instance::$client = new \MongoDB\Client(\Teplosocial\Config::MONGO_CONNECTION);
            } catch (\Exception $e) {
                echo 'Не удалось подключиться к базе данных (mongodb): ',  $e->getMessage(), "\n";
            }
        }

        return static::$instance::$client;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
    }
}
