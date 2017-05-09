<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Slave
 *
 * @ORM\Table(name="slave")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SlaveRepository")
 * @ApiResource(attributes={"normalization_context"={"groups"={"slave"}}})
 */
class Slave
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Assert\NotBlank
     * @Groups({"slave"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="secret", type="string", length=255)
     * @Assert\NotBlank
     */
    private $secret;

    /**
     * @ORM\ManyToMany(targetEntity="Device", mappedBy="slaves")
     */
    private $devices;

    /**
     * @ORM\ManyToMany(targetEntity="Domain", mappedBy="slaves")
     */
    private $domains;


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
     * @return Slave
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
     * Set secret
     *
     * @param string $secret
     *
     * @return Slave
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get secret
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->devices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->domains = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add device
     *
     * @param \AppBundle\Entity\Device $device
     *
     * @return Slave
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
     * Add domain
     *
     * @param \AppBundle\Entity\Domain $domain
     *
     * @return Slave
     */
    public function addDomain(\AppBundle\Entity\Domain $domain)
    {
        $this->domains[] = $domain;

        return $this;
    }

    /**
     * Remove domain
     *
     * @param \AppBundle\Entity\Domain $domain
     */
    public function removeDomain(\AppBundle\Entity\Domain $domain)
    {
        $this->domains->removeElement($domain);
    }

    /**
     * Get domains
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @return array
     * @Groups({"slave"})
     */
    public function getConfig()
    {
        $result = array();

        foreach($this->domains as $domain) {
            foreach ($domain->getDevices() as $device) {
                $result[$device->getName()] = array(
                    'ip' => $device->getIp(),
                    'probes' => $this->getDeviceProbes($device),
                );
            }
        }

        foreach($this->devices as $device) {
            $result[$device->getName()] = array(
                'ip' => $device->getIp(),
                'probes' => $this->getDeviceProbes($device),
            );
        }
        return $result;
    }

    private function getDeviceProbes($device)
    {
        $result = array();

        foreach($device->getProbes() as $probe) {
            $result[] = array(
                'type' => $probe->getType(),
                'step' => $probe->getStep(),
                'samples' => $probe->getSamples(),
            );
            $parent = $device->getDomain();
            while($parent != null) {
                foreach($parent->getProbes() as $probe) {
                    $result[] = array(
                        'type' => $probe->getType(),
                        'step' => $probe->getStep(),
                        'samples' => $probe->getSamples(),
                    );
                }
                $parent = $parent->getParent();
            }
        }

        return $result;
    }
}
