<?php
/**
 * Created by PhpStorm.
 * User: kevinr
 * Date: 4/07/2017
 * Time: 14:02
 */

namespace App\Probe;


trait Lockable
{
    protected $lock;

    public function isLocked()
    {
        return $this->lock;
    }

    public function lock()
    {
        $this->lock = true;
    }

    public function release()
    {
        $this->lock = false;
    }
}