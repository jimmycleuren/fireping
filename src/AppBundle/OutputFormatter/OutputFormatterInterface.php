<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 13:03
 */

namespace AppBundle\OutputFormatter;


interface OutputFormatterInterface
{
    function format($input);
}