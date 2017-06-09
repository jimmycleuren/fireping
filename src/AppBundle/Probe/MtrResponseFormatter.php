<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 1/06/2017
 * Time: 13:53
 */

namespace AppBundle\Probe;


class MtrResponseFormatter
{
    public function __construct()
    {

    }

    public function format($input)
    {
        $output = array();
        $output[] = array(
            'ip' => $input['report']['mtr']['dst'],
            'result' => $input['report']['hubs'],
        );
        return $output;
    }

    public function transformResult($result)
    {
        return $result;
    }
}