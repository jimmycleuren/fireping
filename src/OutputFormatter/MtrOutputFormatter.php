<?php

namespace App\OutputFormatter;


class MtrOutputFormatter implements OutputFormatterInterface
{
    public function format($input)
    {
        $data = implode("\n", $input);
        $data = json_decode($data, true);
        return $data['report']['hubs'];
    }
}