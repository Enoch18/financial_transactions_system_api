<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    private $connection;
    private $channel;

    public function __construct()
    {
        $config = config('rabbitmq');
        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password']
        );
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare($config['exchange'], 'direct', false, true, false);
    }

    public function publishMessage($routingKey, $data)
    {
        $msg = new AMQPMessage(json_encode($data), ['delivery_mode' => 2]);
        $this->channel->basic_publish($msg, config('rabbitmq.exchange'), $routingKey);
    }

    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
