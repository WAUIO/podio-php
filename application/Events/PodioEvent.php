<?php

namespace PodioApplication\Events;

class PodioEvent
{
  private static $intance = null;

  private function __construct()
  {
  }

  public static function getInstance(\Illuminate\Contracts\Container\Container $container = null)
  {
    if (is_null(self::$intance)) {
      self::$intance = new \Illuminate\Events\Dispatcher($container);
    }
    return self::$intance;
  }
}