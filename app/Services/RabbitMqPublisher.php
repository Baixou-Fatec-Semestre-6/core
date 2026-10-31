<?php

namespace App\Services;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Publishes JSON messages to RabbitMQ.
 */
class RabbitMqPublisher
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    /**
     * Publish a persistent JSON message using the given routing key.
     *
     * @param string $routingKey The message routing key.
     * @param array  $payload    The data to encode as JSON.
     *
     * @throws \JsonException If the payload cannot be encoded as JSON.
     */
    public function publish(string $routingKey, array $payload): void
    {
        $message = new AMQPMessage(json_encode($payload, JSON_THROW_ON_ERROR), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, // survives broker restart
        ]);

        $this->channel()->basic_publish($message, config('rabbitmq.exchange'), $routingKey);
    }

    /**
     * Return the lazily initialized RabbitMQ channel.
     */
    private function channel(): AMQPChannel
    {
        if ($this->channel === null) {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host'),
                config('rabbitmq.port'),
                config('rabbitmq.user'),
                config('rabbitmq.password'),
            );
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    /**
     * Close the channel and connection when the publisher is destroyed.
     */
    public function __destruct()
    {
        $this->channel?->close();
        $this->connection?->close();
    }
}