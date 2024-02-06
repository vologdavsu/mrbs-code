<?php

spl_autoload_register(function ($class) {

  $base_dir = __DIR__ . '/';

  // Replace namespace separators with directory separators.
  // Append '.php'
  $file = str_replace('\\', '/', $class) . '.php';

  // Special treatment for some vendors
  $pathinfo = pathinfo($file);
  if ($pathinfo['dirname'] == 'PHPMailer/PHPMailer')
  {
    $file = 'PHPMailer/src/' . $pathinfo['basename'];
  }

  $file = $base_dir . $file;
  if (is_readable($file))
  {
    require $file;
  }
});

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
