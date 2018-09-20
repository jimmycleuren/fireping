<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 16:10
 */

namespace App\Processor;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\WrongTimestampRrdException;

class HttpProcessor extends SmokeProcessor
{
    protected $datasource = "http";
}