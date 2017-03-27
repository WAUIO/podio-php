<?php
/**
 * User: bundinho
 * Date: 3/24/17
 * Time: 2:24 PM
 */

namespace WauPodio\Observer;


class PodioObservable extends AbstractPodioObservable
{
  /**
   * @var string
   */
  protected $method;
  /**
   * @var string
   */
  protected $url;
  /**
   * @var array
   */
  protected $attributes = [];
  /**
   * @var array
   */
  protected $options = [];

  public function __construct($method, $url, $attributes = [], $options = [])
  {
    $this->method = $method;
    $this->url = $url;
    $this->attributes = $attributes;
    $this->options = $options;
  }

  /**
   * fires the event notification
   */
  public  function notify()
  {
    foreach($this->observers as $obs) {
      $obs->update($this);
    }
  }

  /**
   * Constructs the raw data
   *
   * @return array
   */
  public  function getRawData()
  {
    return [
      'method' => $this->method,
      'url' => $this->url,
      'attributes' => $this->attributes,
      "options" => $this->options,
    ];
  }
}