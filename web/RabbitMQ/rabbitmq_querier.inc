<?php

namespace MRBS;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once "ReceiveCallback.php";
require_once "config.php";

class RMQBroker
{
  private const host = \Config::host;
  private const port = \Config::port;
  private const username = (\Config::username == "") ? 'guest' : \Config::username;
  private const password = (\Config::password == "") ? 'guest' : \Config::password;
  private const exchange = (\Config::exchange == "") ? 'test' : \Config::exchange;
  private const exchange_type = (\Config::exchange_type == "") ? 'fanout' : \Config::exchange_type;
  private const queue = (\Config::queue == "") ? 'test_queue' : \Config::queue;
  private const routing_key = self::queue;
  private $connection;
  private $channel;

  var ReceiveCallback $receive_callback;

  function __construct()
  {
    if (\Config::is_active) {
      $this->receive_callback = new ReceiveCallback();
      $this->connection = new AMQPStreamConnection(self::host, self::port, self::username, self::password);
      $this->channel = $this->connection->channel();

      $this->channel->exchange_declare(self::exchange, self::exchange_type, false, false, false);
      $this->channel->queue_declare(self::queue, false, false, false, false);
    }
  }

  function send_message($data): void
  {
    $msg = new AMQPMessage($data);
    $this->channel->basic_publish($msg, self::exchange);
  }

  function receive_message(): void
  {
    list($queue_name, ,) = $this->channel->queue_declare(self::queue, false, false, false, false);
    $this->channel->queue_bind($queue_name, self::exchange);

    $callback = array($this->receive_callback, 'callback');
    $this->channel->basic_consume($queue_name, '', false, true, false, false, $callback);

    try {
      while (count($this->channel->callbacks)) {
        $this->channel->wait();
      }
      $this->channel->consume();
    } catch (\Throwable $exception) {
      echo $exception->getMessage();
    }
  }
}

function create_sending_data(string $name_to, string $action, $message): string
{
  $data = array(
    "name_to" => $name_to,
//    "name_from" => (\Config::service_name == "") ? "MRBS" : \Config::service_name,
    "name_from" => "TT",
    "action" => $action,
    "message" => $message,
  );
  return json_encode($data);
}
