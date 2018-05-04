<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 13:04
 */

namespace App\OutputFormatter;


class DefaultOutputFormatter implements OutputFormatterInterface
{
    public function format($input)
    {
        return $input;
    }
}