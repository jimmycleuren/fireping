<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Alert
 *
 * @ORM\Table(name="alert", indexes={@ORM\Index(name="slaveresult", columns={"device_id", "alert_rule_id", "slave_group_id", "active"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AlertRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class Alert
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Device
     *
     * @ORM\ManyToOne(targetEntity="Device", inversedBy="alerts")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $device;

    /**
     * @var AlertRule
     *
     * @ORM\ManyToOne(targetEntity="AlertRule")
     * @ORM\JoinColumn(name="alert_rule_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $alertRule;

    /**
     * @var SlaveGroup
     *
     * @ORM\ManyToOne(targetEntity="SlaveGroup")
     * @ORM\JoinColumn(name="slave_group_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $slaveGroup;

    /**
     * @var integer
     *
     * @ORM\Column(name="active", type="integer")
     */
    private $active;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="firstseen", type="datetime")
     */
    private $firstseen;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastseen", type="datetime")
     */
    private $lastseen;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set device
     *
     * @param Device $device
     *
     * @return Alert
     */
    public function setDevice($device)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Get device
     *
     * @return Device
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Set alertRule
     *
     * @param AlertRule $alertRule
     *
     * @return Alert
     */
    public function setAlertRule($alertRule)
    {
        $this->alertRule = $alertRule;

        return $this;
    }

    /**
     * Get alertRule
     *
     * @return AlertRule
     */
    public function getAlertRule()
    {
        return $this->alertRule;
    }

    /**
     * Set slave group
     *
     * @param SlaveGroup $slaveGroup
     *
     * @return Alert
     */
    public function setSlaveGroup($slaveGroup)
    {
        $this->slaveGroup = $slaveGroup;

        return $this;
    }

    /**
     * Get slave group
     *
     * @return SlaveGroup
     */
    public function getSlaveGroup()
    {
        return $this->slaveGroup;
    }

    /**
     * Set active
     *
     * @param integer $active
     *
     * @return Alert
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return integer
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set firstseen
     *
     * @param \DateTime $firstseen
     *
     * @return Alert
     */
    public function setFirstseen($firstseen)
    {
        $this->firstseen = $firstseen;

        return $this;
    }

    /**
     * Get firstseen
     *
     * @return \DateTime
     */
    public function getFirstseen()
    {
        return $this->firstseen;
    }

    /**
     * Set lastseen
     *
     * @param \DateTime $lastseen
     *
     * @return Alert
     */
    public function setLastseen($lastseen)
    {
        $this->lastseen = $lastseen;

        return $this;
    }

    /**
     * Get lastseen
     *
     * @return \DateTime
     */
    public function getLastseen()
    {
        return $this->lastseen;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->alertRule->getName()." on ".$this->device->getName()." from ".$this->slaveGroup->getName();
    }
}
