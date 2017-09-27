<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Device
 *
 * @ORM\Table(name="device")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DeviceRepository")
 * @ApiResource
 */
class Device
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
     * @var domain
     *
     * @ORM\ManyToOne(targetEntity="Domain")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id")
     * @Assert\NotBlank
     */
    private $domain;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255)
     * @Assert\NotBlank
     */
    private $ip;

    /**
     * @ORM\ManyToMany(targetEntity="Slave", inversedBy="devices")
     * @ORM\JoinTable(name="device_slaves",
     *      joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="slave_id", referencedColumnName="id")}
     *      )
     */
    private $slaves;

    /**
     * @ORM\ManyToMany(targetEntity="Probe")
     * @ORM\JoinTable(name="device_probes",
     *      joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="probe_id", referencedColumnName="id")}
     *      )
     */
    private $probes;

    /**
     * @ORM\ManyToMany(targetEntity="Alert")
     * @ORM\JoinTable(name="device_alerts",
     *      joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="alert_id", referencedColumnName="id")}
     *      )
     */
    private $alerts;


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
     * Set name
     *
     * @param string $name
     *
     * @return Device
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return Device
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slaves = new \Doctrine\Common\Collections\ArrayCollection();
        $this->probes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->alerts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set domain
     *
     * @param \AppBundle\Entity\Domain $domain
     *
     * @return Device
     */
    public function setDomain(\AppBundle\Entity\Domain $domain = null)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return \AppBundle\Entity\Domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Add slave
     *
     * @param \AppBundle\Entity\Slave $slave
     *
     * @return Device
     */
    public function addSlave(\AppBundle\Entity\Slave $slave)
    {
        $this->slaves[] = $slave;

        return $this;
    }

    /**
     * Remove slave
     *
     * @param \AppBundle\Entity\Slave $slave
     */
    public function removeSlave(\AppBundle\Entity\Slave $slave)
    {
        $this->slaves->removeElement($slave);
    }

    /**
     * Get slaves
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSlaves()
    {
        return $this->slaves;
    }

    /**
     * Add probe
     *
     * @param \AppBundle\Entity\Probe $probe
     *
     * @return Device
     */
    public function addProbe(\AppBundle\Entity\Probe $probe)
    {
        $this->probes[] = $probe;

        return $this;
    }

    /**
     * Remove probe
     *
     * @param \AppBundle\Entity\Probe $probe
     */
    public function removeProbe(\AppBundle\Entity\Probe $probe)
    {
        $this->probes->removeElement($probe);
    }

    /**
     * Get probes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProbes()
    {
        return $this->probes;
    }

    /**
     * Add alert
     *
     * @param \AppBundle\Entity\Alert $alert
     *
     * @return Device
     */
    public function addAlert(\AppBundle\Entity\Alert $alert)
    {
        $this->alerts[] = $alert;

        return $this;
    }

    /**
     * Remove alert
     *
     * @param \AppBundle\Entity\Alert $alert
     */
    public function removeAlert(\AppBundle\Entity\Alert $alert)
    {
        $this->alerts->removeElement($alert);
    }

    /**
     * Get alerts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAlerts()
    {
        return $this->alerts;
    }
}
