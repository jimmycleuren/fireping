<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 4/07/2017
 * Time: 14:41
 */

namespace AppBundle\Probe;


class EchoPoster extends Poster
{
    public function __construct($target = null)
    {
        parent::__construct($target);
    }

    public function post(Message $message)
    {
        return $message;
    }
}