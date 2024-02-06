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

//  public const email_for_reporting = "elgaevav+booking@vogu35.ru";
  public const email_for_reporting = "ProVad2017@yandex.ru";
}
