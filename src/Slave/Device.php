<?php

namespace App\Slave;

class Device
{
    protected $active;

    public function __construct(protected $id, protected $ip)
    {
        $this->active = true;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    public function setIp($ip): void
    {
        $this->ip = $ip;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active): void
    {
        $this->active = $active;
    }

    public function asArray()
    {
        return [
            'id' => $this->id,
            'ip' => $this->ip,
        ];
    }
}
