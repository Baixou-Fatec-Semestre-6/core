<?php

namespace App\Services;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

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
     * @param  string  $routingKey  The message routing key.
     * @param  array  $payload  The data to encode as JSON.
     *
     * @throws \JsonException If the payload cannot be encoded as JSON.
     */
    public function publish(string $routingKey, array $payload): void
    {
        $message = new AMQPMessage(json_encode($payload, JSON_THROW_ON_ERROR), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, // survives broker restart
        ]);

        $channel = $this->channel();
        $channel->basic_publish($message, config('rabbitmq.exchange'), $routingKey, mandatory: true);
        $channel->wait_for_pending_acks_returns(5);
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
            $this->channel->confirm_select();
            $this->channel->set_return_listener(
                function (int $replyCode, string $replyText, string $exchange, string $routingKey): void {
                    throw new RuntimeException(
                        "RabbitMQ returned an unroutable message ({$replyCode}): {$replyText} ({$exchange}:{$routingKey}).",
                    );
                },
            );
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
