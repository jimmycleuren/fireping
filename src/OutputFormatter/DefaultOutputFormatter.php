<?php

namespace App\OutputFormatter;


class DefaultOutputFormatter implements OutputFormatterInterface
{
    public function format($input)
    {
        return $input;
    }
}