<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 4/07/2017
 * Time: 14:40
 */

namespace AppBundle\Probe;


interface PosterInterface
{
    public function post(Message $message);
}