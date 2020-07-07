<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Domain.
 *
 * @ORM\Table(name="domain", uniqueConstraints={@ORM\UniqueConstraint(name="unique_name_in_parent", columns={"parent_id", "name"})})
 * @ORM\Entity(repositoryClass="App\Repository\DomainRepository")
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"domain"}},
 *     "denormalization_context"={"groups"={"write"}}
 * },
 *     itemOperations={
 *     "get",
 *     "put",
 *     "delete",
 *     "alerts"={"route_name"="api_domains_alerts","method"="GET"},
 * })
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @UniqueEntity(fields={"parent", "name"}, errorPath="name", message="This name is already in use within this parent domain.", ignoreNull=false)
 */
class Domain
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"domain", "write"})
     */
    private $id;

    /**
     * @var Domain|null
     *
     * @ORM\ManyToOne(targetEntity="Domain", inversedBy="subdomains", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     * @ApiSubresource()
     * @Groups({"write"})
     */
    private $parent;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank
     * @Groups({"domain", "write"})
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="SlaveGroup", inversedBy="domains", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="domain_slavegroups",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="slavegroup_id", referencedColumnName="id")}
     *      )
     * @Groups({"domain", "write"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $slavegroups;

    /**
     * @ORM\ManyToMany(targetEntity="Probe", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="domain_probes",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="probe_id", referencedColumnName="id")}
     *      )
     * @Groups({"domain", "write"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $probes;

    /**
     * @ORM\ManyToMany(targetEntity="AlertRule", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="domain_alert_rules",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="alert_rule_id", referencedColumnName="id")}
     *      )
     * @Groups({"domain", "write"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $alertRules;

    /**
     * @ORM\ManyToMany(targetEntity="AlertDestination", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="domain_alert_destinations",
     *      joinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="alert_destination_id", referencedColumnName="id")}
     *      )
     * @Groups({"domain", "write"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $alertDestinations;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Device", mappedBy="domain", fetch="EXTRA_LAZY")
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     * @Groups({"domain", "write"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $devices;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Domain", mappedBy="parent", fetch="EXTRA_LAZY")
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     * @ORM\OrderBy({"name" = "asc"})
     * @Groups({"domain", "write"})
     */
    private $subdomains;

    /**
     * Set id.
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
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
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->slavegroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->probes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->alertRules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->alertDestinations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set parent.
     *
     * @param \App\Entity\Domain $parent
     *
     * @return Domain
     */
    public function setParent(\App\Entity\Domain $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \App\Entity\Domain|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add slavegroup.
     *
     * @return Domain
     */
    public function addSlaveGroup(\App\Entity\SlaveGroup $slavegroup)
    {
        $this->slavegroups[] = $slavegroup;

        return $this;
    }

    /**
     * Remove slavegroup.
     */
    public function removeSlaveGroup(\App\Entity\SlaveGroup $slavegroup)
    {
        $this->slavegroups->removeElement($slavegroup);
    }

    /**
     * Get slavegroups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSlaveGroups()
    {
        return $this->slavegroups;
    }

    /**
     * Add probe.
     *
     * @return Domain
     */
    public function addProbe(\App\Entity\Probe $probe)
    {
        $this->probes[] = $probe;

        return $this;
    }

    /**
     * Remove probe.
     */
    public function removeProbe(\App\Entity\Probe $probe)
    {
        $this->probes->removeElement($probe);
    }

    /**
     * Get probes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProbes()
    {
        return $this->probes;
    }

    /**
     * Add alert rule.
     *
     * @return Domain
     */
    public function addAlertRule(\App\Entity\AlertRule $alertRule)
    {
        $this->alertRules[] = $alertRule;

        return $this;
    }

    /**
     * Remove alert rule.
     */
    public function removeAlertRule(\App\Entity\AlertRule $alertRule)
    {
        $this->alertRules->removeElement($alertRule);
    }

    /**
     * Get alert rules.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAlertRules()
    {
        return $this->alertRules;
    }

    /**
     * Add alert destination.
     *
     * @return Domain
     */
    public function addAlertDestination(\App\Entity\AlertDestination $alertDestination)
    {
        $this->alertDestinations[] = $alertDestination;

        return $this;
    }

    /**
     * Remove alert destination.
     */
    public function removeAlertDestination(\App\Entity\AlertDestination $alertDestination)
    {
        $this->alertDestinations->removeElement($alertDestination);
    }

    /**
     * Get alert destinations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAlertDestinations()
    {
        return $this->alertDestinations;
    }

    /**
     * Add device.
     *
     * @return Domain
     */
    public function addDevice(\App\Entity\Device $device)
    {
        $this->devices[] = $device;

        return $this;
    }

    /**
     * Remove device.
     */
    public function removeDevice(\App\Entity\Device $device)
    {
        $this->devices->removeElement($device);
    }

    /**
     * Get devices.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * Add subdomain.
     *
     * @return Domain
     */
    public function addSubdomain(\App\Entity\Domain $subdomain)
    {
        $this->subdomains[] = $subdomain;

        return $this;
    }

    /**
     * Remove subdomain.
     */
    public function removeSubdomain(\App\Entity\Domain $subdomain)
    {
        $this->subdomains->removeElement($subdomain);
    }

    /**
     * Get subdomains.
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

    private function getFqdn(): string
    {
        if (null !== $this->getParent()) {
            return $this->getParent()->getFqdn().' > '.$this->getName();
        }

        return $this->getName();
    }

    public function getParentFqdn(): string
    {
        if (null === $this->getParent()) {
            return '';
        }

        return $this->getParent()->getFqdn();
    }
}
