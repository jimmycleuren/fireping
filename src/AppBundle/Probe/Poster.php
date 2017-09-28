<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 4/07/2017
 * Time: 14:40
 */

namespace AppBundle\Probe;


abstract class Poster implements PosterInterface
{
    protected $target;

    public function __construct($target = null)
    {
        $this->target = $target;
    }

    public function post(Message $message)
    {
        throw new \Exception("Please implement in child class.");
    }
}