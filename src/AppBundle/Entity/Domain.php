<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Domain
 *
 * @ORM\Table(name="domain")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DomainRepository")
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"domain"}},
 *     "denormalization_context"={"groups"={"domain"}}
 * })
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class Domain
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"domain"})
     */
    private $id;

    /**
     * @var Domain|null
     *
     * @ORM\ManyToOne(targetEntity="Domain", inversedBy="subdomains", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     * @Groups({"domain"})
     */
    private $parent;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank
     * @Groups({"domain"})
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="SlaveGroup", inversedBy="domains", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="domain_slavegroups",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="slavegroup_id", referencedColumnName="id")}
     *      )
     * @Groups({"domain"})
     */
    private $slavegroups;

    /**
     * @ORM\ManyToMany(targetEntity="Probe", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="domain_probes",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="probe_id", referencedColumnName="id")}
     *      )
     * @Groups({"domain"})
     */
    private $probes;

    /**
     * @ORM\ManyToMany(targetEntity="AlertRule", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="domain_alert_rules",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="alert_rule_id", referencedColumnName="id")}
     *      )
     * @Groups({"domain"})
     */
    private $alertRules;

    /**
     * @ORM\ManyToMany(targetEntity="AlertDestination", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="domain_alert_destinations",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="alert_destination_id", referencedColumnName="id")}
     *      )
     * @Groups({"domain"})
     */
    private $alertDestinations;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Device", mappedBy="domain", fetch="EXTRA_LAZY")
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     * @Groups({"domain"})
     */
    private $devices;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Domain", mappedBy="parent", fetch="EXTRA_LAZY")
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     * @ORM\OrderBy({"name" = "asc"})
     * @Groups({"domain"})
     */
    private $subdomains;

    /**
     * Set id
     *
     * @param int $id
     *
     * @return Domain
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
        $this->slavegroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->probes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->alertRules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->alertDestinations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return \AppBundle\Entity\Domain|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add slavegroup
     *
     * @param \AppBundle\Entity\SlaveGroup $slavegroup
     *
     * @return Domain
     */
    public function addSlaveGroup(\AppBundle\Entity\SlaveGroup $slavegroup)
    {
        $this->slavegroups[] = $slavegroup;

        return $this;
    }

    /**
     * Remove slavegroup
     *
     * @param \AppBundle\Entity\SlaveGroup $slavegroup
     */
    public function removeSlaveGroup(\AppBundle\Entity\SlaveGroup $slavegroup)
    {
        $this->slavegroups->removeElement($slavegroup);
    }

    /**
     * Get slavegroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSlaveGroups()
    {
        return $this->slavegroups;
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
     * Add alert rule
     *
     * @param \AppBundle\Entity\AlertRule $alertRule
     *
     * @return Domain
     */
    public function addAlertRule(\AppBundle\Entity\AlertRule $alertRule)
    {
        $this->alertRules[] = $alertRule;

        return $this;
    }

    /**
     * Remove alert rule
     *
     * @param \AppBundle\Entity\AlertRule $alertRule
     */
    public function removeAlertRule(\AppBundle\Entity\AlertRule $alertRule)
    {
        $this->alertRules->removeElement($alertRule);
    }

    /**
     * Get alert rules
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAlertRules()
    {
        return $this->alertRules;
    }

    /**
     * Add alert destination
     *
     * @param \AppBundle\Entity\AlertDestination $alertDestination
     *
     * @return Domain
     */
    public function addAlertDestination(\AppBundle\Entity\AlertDestination $alertDestination)
    {
        $this->alertDestinations[] = $alertDestination;

        return $this;
    }

    /**
     * Remove alert destination
     *
     * @param \AppBundle\Entity\AlertDestination $alertDestination
     */
    public function removeAlertDestination(\AppBundle\Entity\AlertDestination $alertDestination)
    {
        $this->alertDestinations->removeElement($alertDestination);
    }

    /**
     * Get alert destinations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAlertDestinations()
    {
        return $this->alertDestinations;
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

    public function getActiveAlerts()
    {
        $activeAlerts = new ArrayCollection();

        foreach ($this->devices as $device) {
            foreach ($device->getActiveAlerts() as $alert) {
                $activeAlerts->add($alert);
            }
        }

        foreach ($this->subdomains as $subdomain) {
            foreach ($subdomain->getActiveAlerts() as $alert) {
                $activeAlerts->add($alert);
            }
        }

        return $activeAlerts;
    }

    public function __toString()
    {
        return $this->name;
    }
}
