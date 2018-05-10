<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 4/07/2017
 * Time: 13:54
 */

namespace App\Probe;


interface MessageQueueInterface
{
    public function addMessage(Message $message);
    public function process(PosterInterface $poster);
}