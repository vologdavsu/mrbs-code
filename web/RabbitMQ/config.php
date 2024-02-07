<?php
class Config
{
  #Настройки RabbitMQ
  public const is_active = true;
  public const service_name = "MRBS";
  public const group = "schedule";
  public const host = "localhost";
  public const port = 5672;
  public const exchange = "test";
  public const username = "guest";
  public const password = "guest";
  public const exchange_type = "fanout";
  public const queue = "test_queue";

//  eMail не работает, пока что не вводи
  public const email_for_reporting = "*";
}
