<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 8/03/2018
 * Time: 19:52
 */

namespace App\AlertDestination;

use App\Entity\Alert;

abstract class AlertDestinationInterface
{
    public function setParameters(array $parameters)
    {

    }

    public abstract function trigger(Alert $alert);
    public abstract function clear(Alert $alert);
}