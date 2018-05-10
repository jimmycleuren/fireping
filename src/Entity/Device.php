<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Device
 *
 * @ORM\Table(name="device")
 * @ORM\Entity(repositoryClass="App\Repository\DeviceRepository")
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"device"}},
 *     "denormalization_context"={"groups"={"device"}}
 * },
 * itemOperations={
 *     "get",
 *     "put",
 *     "delete",
 *     "status"={"route_name"="api_devices_status"},
 * })
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
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
     * @var Domain|null
     *
     * @ORM\ManyToOne(targetEntity="Domain", inversedBy="devices")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @ORM\ManyToMany(targetEntity="SlaveGroup", inversedBy="devices", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="device_slavegroups",
     *      joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="slavegroup_id", referencedColumnName="id")}
     *      )
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     * @Groups({"device"})
     */
    private $slavegroups;

    /**
     * @ORM\ManyToMany(targetEntity="Probe", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="device_probes",
     *      joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="probe_id", referencedColumnName="id")}
     *      )
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     * @Groups({"device"})
     */
    private $probes;

    /**
     * @ORM\ManyToMany(targetEntity="AlertRule", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="device_alert_rules",
     *      joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="alert_rule_id", referencedColumnName="id")}
     *      )
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     * @Groups({"device"})
     */
    private $alertRules;

    /**
     * @ORM\ManyToMany(targetEntity="AlertDestination", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="device_alert_destinations",
     *      joinColumns={@ORM\JoinColumn(name="device_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="alert_destination_id", referencedColumnName="id")}
     *      )
     * @Groups({"device"})
     */
    private $alertDestinations;

    /**
     * @ORM\OneToMany(targetEntity="Alert", mappedBy="device", fetch="EXTRA_LAZY")
     */
    private $alerts;


    /**
     * Set id
     *
     * @param string $id
     *
     * @return Device
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
        $this->slavegroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->probes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->alertRules = new \Doctrine\Common\Collections\ArrayCollection();
        $this->alertDestinations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->alerts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set domain
     *
     * @param \App\Entity\Domain $domain
     *
     * @return Device
     * @Groups({"device"})
     */
    public function setDomain(\App\Entity\Domain $domain = null)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return \App\Entity\Domain|null
     * @Groups({"device"})
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Add slavegroup
     *
     * @param \App\Entity\SlaveGroup $slavegroup
     *
     * @return Device
     */
    public function addSlaveGroup(\App\Entity\SlaveGroup $slavegroup)
    {
        $this->slavegroups[] = $slavegroup;

        return $this;
    }

    /**
     * Remove slavegroup
     *
     * @param \App\Entity\SlaveGroup $slavegroup
     */
    public function removeSlaveGroup(\App\Entity\SlaveGroup $slavegroup)
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
     * Get active slavegroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActiveSlaveGroups()
    {
        if ($this->slavegroups->count() > 0) {
            return $this->slavegroups;
        } else {
            $parent = $this->getDomain();
            while ($parent != null) {
                if ($parent->getSlaveGroups()->count() > 0) {
                    return $parent->getSlaveGroups();
                }
                $parent = $parent->getParent();
            }
        }
        return new ArrayCollection();
    }

    /**
     * Add probe
     *
     * @param \App\Entity\Probe $probe
     *
     * @return Device
     */
    public function addProbe(\App\Entity\Probe $probe)
    {
        $this->probes[] = $probe;

        return $this;
    }

    /**
     * Remove probe
     *
     * @param \App\Entity\Probe $probe
     */
    public function removeProbe(\App\Entity\Probe $probe)
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
     * Get active probes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActiveProbes()
    {
        if ($this->probes->count() > 0) {
            return $this->probes;
        } else {
            $parent = $this->getDomain();
            while ($parent != null) {
                if ($parent->getProbes()->count() > 0) {
                    return $parent->getProbes();
                }
                $parent = $parent->getParent();
            }
        }
        return new ArrayCollection();
    }

    /**
     * Add alert
     *
     * @param \App\Entity\AlertRule $alertRule
     *
     * @return Device
     */
    public function addAlertRule(\App\Entity\AlertRule $alertRule)
    {
        $this->alertRules[] = $alertRule;

        return $this;
    }

    /**
     * Remove alert
     *
     * @param \App\Entity\AlertRule $alertRule
     */
    public function removeAlertRule(\App\Entity\AlertRule $alertRule)
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
     * Get active alert rules. Alert rules can be overridden on every level, so only the lowest level with alert rules configured will be used
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActiveAlertRules()
    {
        if ($this->alertRules->count() > 0) {
            return $this->alertRules;
        } else {
            $parent = $this->getDomain();
            while ($parent != null) {
                if ($parent->getAlertRules()->count() > 0) {
                    return $parent->getAlertRules();
                }
                $parent = $parent->getParent();
            }
        }
        return new ArrayCollection();
    }

    /**
     * Add alert destination
     *
     * @param \App\Entity\AlertDestination $alertDestination
     *
     * @return Domain
     */
    public function addAlertDestination(\App\Entity\AlertDestination $alertDestination)
    {
        $this->alertDestinations[] = $alertDestination;

        return $this;
    }

    /**
     * Remove alert destination
     *
     * @param \App\Entity\AlertDestination $alertDestination
     */
    public function removeAlertDestination(\App\Entity\AlertDestination $alertDestination)
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
     * Get active alert destinations. Alert destinations can be overridden on every level, so only the lowest level with alert destinations configured will be used
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActiveAlertDestinations()
    {
        if ($this->alertDestinations->count() > 0) {
            return $this->alertDestinations;
        } else {
            $parent = $this->getDomain();
            while ($parent != null) {
                if ($parent->getAlertDestinations()->count() > 0) {
                    return $parent->getAlertDestinations();
                }
                $parent = $parent->getParent();
            }
        }
        return new ArrayCollection();
    }

    public function getActiveAlerts()
    {
        /*
        $criteria = Criteria::create()->where(Criteria::expr()->eq("active", 1));

        return $this->getAlerts()->matching($criteria);
        */
        $result = new ArrayCollection();

        $alerts = $this->getAlerts();
        foreach ($alerts as $alert) {
            if($alert->getActive()) {
                $result->add($alert);
            }
        }
        return $result;
    }

    public function getAlerts()
    {
        return $this->alerts;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Add alert
     *
     * @param \App\Entity\Alert $alert
     *
     * @return Device
     */
    public function addAlert(\App\Entity\Alert $alert)
    {
        $this->alerts[] = $alert;

        return $this;
    }

    /**
     * Remove alert
     *
     * @param \App\Entity\Alert $alert
     */
    public function removeAlert(\App\Entity\Alert $alert)
    {
        $this->alerts->removeElement($alert);
    }

    /**
     * Get root domain
     */
    public function getRootDomain()
    {
        $domain = $this->getDomain();
        while ($domain->getParent() != null) {
            $domain = $domain->getParent();
        }
        return $domain;
    }
}
