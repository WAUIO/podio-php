<?php
/**
 * User: bundinho
 * Date: 3/24/17
 * Time: 2:13 PM
 */

namespace WauPodio\Observer;


use WauKeenIo\Observer\ObservableInterface;
use WauKeenIo\Observer\ObserverInterface;

abstract class AbstractPodioObservable implements ObservableInterface
{
  protected $observers =  [];


  public abstract function notify();


  public function attach(ObserverInterface $observer_in)
  {
      $this->observers[] = $observer_in;
  }

  public function detach(ObserverInterface $observer_in)
  {
    foreach($this->observers as $key => $obs) {
      if($obs == $observer_in) {
        unset($this->observers[$key]);
      }
    }
  }

  public abstract function getRawData();
}