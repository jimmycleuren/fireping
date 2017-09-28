<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Device
 *
 * @ORM\Table(name="device")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DeviceRepository")
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"device"}},
 *     "denormalization_context"={"groups"={"device"}}
 * })
 */
class Device
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"device"})
     */
    private $id;

    /**
     * @var domain
     *
     * @ORM\ManyToOne(targetEntity="Domain")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id")
     * @Assert\NotBlank
     * @Groups({"device"})
     */
    private $domain;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank
     * @Groups({"device"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=255)
     * @Assert\NotBlank
     * @Groups({"device"})
     */
    private $ip;

    /**
     * @ORM\ManyToMany(targetEntity="Slave", inversedBy="devices")
     * @ORM\JoinTable(name="device_slaves",
     *      joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="slave_id", referencedColumnName="id")}
     *      )
     * @Groups({"device"})
     */
    private $slaves;

    /**
     * @ORM\ManyToMany(targetEntity="Probe")
     * @ORM\JoinTable(name="device_probes",
     *      joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="probe_id", referencedColumnName="id")}
     *      )
     * @Groups({"device"})
     */
    private $probes;

    /**
     * @ORM\ManyToMany(targetEntity="Alert")
     * @ORM\JoinTable(name="device_alerts",
     *      joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="alert_id", referencedColumnName="id")}
     *      )
     * @Groups({"device"})
     */
    private $alerts;


    /**
     * Get id
     *
     * @return int
     * @Groups({"device"})
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
     * @Groups({"device"})
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
     * @Groups({"device"})
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
     * @Groups({"device"})
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
     * @Groups({"device"})
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
     * @Groups({"device"})
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
     * @Groups({"device"})
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
     * @Groups({"device"})
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
     * @Groups({"device"})
     */
    public function getProbes()
    {
        return $this->probes;
    }

    /**
     * Get probes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAllProbes()
    {
        $result = new ArrayCollection();
        foreach ($this->probes as $probe) {
            $result->add($probe);
        }
        $parent = $this->getDomain();
        while ($parent != null) {
              foreach ($parent->getProbes() as $probe) {
                      $result->add($probe);
                  }
            $parent = $parent->getParent();
        }
        return $result;
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
     * @Groups({"device"})
     */
    public function getAlerts()
    {
        return $this->alerts;
    }
}
