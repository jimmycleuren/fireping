<?php

namespace App\Storage;

use App\Exception\RrdException;
use App\Exception\WrongTimestampRrdException;

class RrdCachedStorage extends RrdStorage
{
    private $address = "unix:///var/run/rrdcached.sock";
    private $socket = null;

    private function connect()
    {
        if(!$this->socket) {
            $this->socket = stream_socket_client($this->address, $errorno, $errorstr, 5);
            stream_set_timeout($this->socket, 5);
        }
    }

    protected function update($filename, $probe, $timestamp, $data)
    {
        $this->connect();

        $filename = realpath($filename);

        $last = $this->getLastUpdate($filename);
        if ($last >= $timestamp) {
            throw new WrongTimestampRrdException("RRD $filename last update was ".$last.", cannot update at ".$timestamp);
        }

        $sources = $this->getDatasources($filename);

        $values = array($timestamp);
        foreach($sources as $source) {
            $values[] = $data[$source];
        }


        $this->send("UPDATE $filename ".implode(":", $values));
        $message = $this->read();
        $this->logger->info($message);
    }

    private function getLastUpdate($filename)
    {
        $this->connect();

        $this->send('LAST '.$filename);
        $timestamp = explode(" ", $this->read())[1];

        return $timestamp;
    }

    private function getDatasources($filename)
    {
        $sources = array();

        $this->send("INFO $filename");
        $message = $this->read();
        $message = explode("\n", $message);
        foreach($message as $line) {
            if(preg_match("/ds\[([\w]+)\]/", $line, $match)) {
                if (!in_array($match[1], $sources)) {
                    $sources[] = $match[1];
                }
            }
        }

        return $sources;
    }

    private function send($command)
    {
        if(!fwrite($this->socket, $command.PHP_EOL)) {
            throw new RrdException("Could not write to rrdcached");
        }
    }

    private function read()
    {
        $line = fgets($this->socket, 8192);
        $result = $line;

        $code = explode(" ", $line);
        $code = $code[0];

        for($i = 0; $i < $code; $i++) {
            $result .= "\n".fgets($this->socket, 8192);
        }
        /*
        if (!($message = fread($this->socket, 16384))) {
            throw new RrdException("Could not read from rrdcached");
        }
        */

        return $result;
    }
}