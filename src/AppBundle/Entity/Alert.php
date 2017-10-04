<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Alert
 *
 * @ORM\Table(name="alert")
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
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="Device", inversedBy="alerts")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="id")
     */
    private $device;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="AlertRule")
     * @ORM\JoinColumn(name="alert_rule_id", referencedColumnName="id")
     */
    private $alertRule;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="SlaveGroup")
     * @ORM\JoinColumn(name="slave_group_id", referencedColumnName="id")
     */
    private $slaveGroup;

    /**
     * @var int
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
     * @param integer $device
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
     * @return int
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Set alertRule
     *
     * @param integer $alertRule
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
     * @return int
     */
    public function getAlertRule()
    {
        return $this->alertRule;
    }

    /**
     * Set slave group
     *
     * @param integer $slaveGroup
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
     * @return int
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
     * @return int
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
}
