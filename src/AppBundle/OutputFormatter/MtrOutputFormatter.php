<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 9/06/2017
 * Time: 13:13
 */

namespace AppBundle\OutputFormatter;


class MtrOutputFormatter implements OutputFormatterInterface
{
    public function format($input)
    {
        $data = implode("\n", $input);
        $data = json_decode($data, true);
        return $data['report']['hubs'];
    }
}