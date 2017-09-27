<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Domain
 *
 * @ORM\Table(name="domain")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DomainRepository")
 * @ApiResource
 */
class Domain
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
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    private $parent;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="Slave", inversedBy="domains")
     * @ORM\JoinTable(name="domain_slaves",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="slave_id", referencedColumnName="id")}
     *      )
     */
    private $slaves;

    /**
     * @ORM\ManyToMany(targetEntity="Probe")
     * @ORM\JoinTable(name="domain_probes",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="probe_id", referencedColumnName="id")}
     *      )
     */
    private $probes;

    /**
     * @ORM\ManyToMany(targetEntity="Alert")
     * @ORM\JoinTable(name="domain_alerts",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="alert_id", referencedColumnName="id")}
     *      )
     */
    private $alerts;

    /**
     * @var device
     * @ORM\OneToMany(targetEntity="Device", mappedBy="domain")
     */
    private $devices;

    /**
     * @var domain
     * @ORM\OneToMany(targetEntity="Domain", mappedBy="parent")
     * @ORM\OrderBy({"name" = "asc"})
     */
    private $subdomains;


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
     * @return Domain
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
     * Constructor
     */
    public function __construct()
    {
        $this->slaves = new \Doctrine\Common\Collections\ArrayCollection();
        $this->probes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->alerts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set parent
     *
     * @param \AppBundle\Entity\Domain $parent
     *
     * @return Domain
     */
    public function setParent(\AppBundle\Entity\Domain $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AppBundle\Entity\Domain
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add slave
     *
     * @param \AppBundle\Entity\Slave $slave
     *
     * @return Domain
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
     * @return Domain
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
     * @return Domain
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

    /**
     * Add device
     *
     * @param \AppBundle\Entity\Device $device
     *
     * @return Domain
     */
    public function addDevice(\AppBundle\Entity\Device $device)
    {
        $this->devices[] = $device;

        return $this;
    }

    /**
     * Remove device
     *
     * @param \AppBundle\Entity\Device $device
     */
    public function removeDevice(\AppBundle\Entity\Device $device)
    {
        $this->devices->removeElement($device);
    }

    /**
     * Get devices
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * Add subdomain
     *
     * @param \AppBundle\Entity\Domain $subdomain
     *
     * @return Domain
     */
    public function addSubdomain(\AppBundle\Entity\Domain $subdomain)
    {
        $this->subdomains[] = $subdomain;

        return $this;
    }

    /**
     * Remove subdomain
     *
     * @param \AppBundle\Entity\Domain $subdomain
     */
    public function removeSubdomain(\AppBundle\Entity\Domain $subdomain)
    {
        $this->subdomains->removeElement($subdomain);
    }

    /**
     * Get subdomains
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubdomains()
    {
        return $this->subdomains;
    }
}
