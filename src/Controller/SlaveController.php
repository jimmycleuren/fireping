<?php

namespace App\Controller;

use App\Common\Version\Version;
use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\Slave;
use App\Exception\DirtyInputException;
use App\Exception\WrongTimestampRrdException;
use App\Processor\ProcessorFactory;
use App\Repository\DeviceRepository;
use App\Repository\SlaveRepository;
use App\Storage\SlaveStatsRrdStorage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SlaveController extends AbstractController
{
    private $domainSlaveGroupCache = [];
    private $deviceSlaveGroupCache = [];
    private $domainProbeCache = [];
    private $deviceProbeCache = [];

    /**
     * Lists all slave entities.
     *
     * @Route("/slaves", name="slave_index", methods={"GET"})
     *
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager, DeviceRepository $deviceRepository, SlaveRepository $slaveRepository)
    {
        $slaves = $slaveRepository->findBy([], ['id' => 'ASC']);

        $targets = [];
        foreach ($slaves as $slave) {
            $count = 0;
            $data = $this->getSlaveConfig($slave, $deviceRepository, $entityManager);
            foreach ($data as $probe) {
                $count += count($probe['targets']);
            }
            $targets[$slave->getId()] = $count;
        }

        return $this->render('slave/index.html.twig', [
            'slaves' => $slaves,
            'targets' => $targets,
            'active_menu' => 'slave',
        ]);
    }

    /**
     * @Route("/slaves/{id}", name="slave_detail", methods={"GET"})
     *
     * @return Response
     */
    public function detailAction(Slave $slave, DeviceRepository $deviceRepository, EntityManagerInterface $entityManager)
    {
        $targets = 0;
        $data = $this->getSlaveConfig($slave, $deviceRepository, $entityManager);
        foreach ($data as $probe) {
            $targets += count($probe['targets']);
        }

        return $this->render('slave/detail.html.twig', [
            'slave' => $slave,
            'targets' => $targets,
            'control_sidebar_extra' => [
                'navigation' => [
                    'icon' => 'far fa-clock',
                    'controller' => 'App\Controller\SlaveController::sidebarAction',
                ],
            ],
        ]);
    }

    public function sidebarAction(Request $originalRequest)
    {
        return $this->render('slave/sidebar.html.twig', [
            'device' => $originalRequest->get('device'),
        ]);
    }

    /**
     * @param string $id
     *
     * @return JsonResponse
     *
     * @throws \Exception
     * @Route("/api/slaves/{id}/config", methods={"GET"})
     */
    public function configAction($id, Request $request, EntityManagerInterface $entityManager, SlaveRepository $slaveRepository, DeviceRepository $deviceRepository)
    {
        $slave = $slaveRepository->findOneBy(['id' => $id]);
        if (!$slave) {
            $slave = new Slave();
            $slave->setId($id);
        }

        $slave->setLastContact(new \DateTime());
        $slave->setIp($request->getClientIp());
        $entityManager->persist($slave);
        $entityManager->flush();

        $config = $this->getSlaveConfig($slave, $deviceRepository, $entityManager);

        $response = new JsonResponse($config);
        $response->setEtag(md5(json_encode($config)));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    private function getSlaveConfig(Slave $slave, DeviceRepository $deviceRepository, EntityManagerInterface $entityManager)
    {
        $this->prepareCache($entityManager);

        $config = [];

        $domains = [];
        if ($slave->getSlaveGroup()) {
            foreach ($slave->getSlaveGroup()->getDomains() as $domain) {
                $domains = array_merge($domains, $this->getDomains($domain));
            }
            $devices = $deviceRepository->findBy(['domain' => $domains]);
            $devices = array_merge($devices, $slave->getSlaveGroup()->getDevices()->toArray());

            //remove devices that were selected, but the current slavegroup is not active for the device
            foreach ($devices as $key => $device) {
                $found = false;
                $slavegroups = $this->getActiveSlaveGroups($device);
                foreach ($slavegroups as $slavegroup) {
                    if ($slavegroup->getId() == $slave->getSlaveGroup()->getId()) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    unset($devices[$key]);
                }
            }

            $slaves = $slave->getSlaveGroup()->getSlaves();
            foreach ($slaves as $key => $value) {
                if ($value->getLastContact() < new \DateTime('10 minutes ago')) {
                    unset($slaves[$key]);
                }
            }

            $slavePosition = 0;
            foreach ($slaves as $key => $temp) {
                if ($temp->getId() == $slave->getId()) {
                    $slavePosition = $key;
                }
            }

            $divider = max(1, count($slaves));
            $size = ceil(count($devices) / $divider);
            if ($size > 0) {
                $subset = array_chunk($devices, (int) $size)[$slavePosition];
            } else {
                $subset = [];
            }

            foreach ($subset as $device) {
                $this->getDeviceProbes($device, $config);
            }
        }

        return $config;
    }

    private function prepareCache(EntityManagerInterface $entityManager)
    {
        $devices = $entityManager->createQuery('SELECT d, s FROM App:Device d JOIN d.slavegroups s')->getResult();
        foreach ($devices as $device) {
            $this->deviceSlaveGroupCache[$device->getId()] = $device->getSlaveGroups();
        }
        $devices = $entityManager->createQuery('SELECT d, p FROM App:Device d JOIN d.probes p')->getResult();
        foreach ($devices as $device) {
            $this->deviceProbeCache[$device->getId()] = $device->getProbes();
        }

        $domains = $entityManager->createQuery('SELECT d, s FROM App:Domain d JOIN d.slavegroups s')->getResult();
        foreach ($domains as $domain) {
            $this->domainSlaveGroupCache[$domain->getId()] = $domain->getSlaveGroups();
        }
        $domains = $entityManager->createQuery('SELECT d, p FROM App:Domain d JOIN d.probes p')->getResult();
        foreach ($domains as $domain) {
            $this->domainProbeCache[$domain->getId()] = $domain->getProbes();
        }
    }

    private function getDomains($domain)
    {
        $domains = [$domain];

        foreach ($domain->getSubDomains() as $subdomain) {
            $domains = array_merge($domains, $this->getDomains($subdomain));
        }

        return $domains;
    }

    private function getDeviceProbes($device, &$config)
    {
        $probes = $this->getActiveProbes($device);
        foreach ($probes as $probe) {
            $config[$probe->getId()]['type'] = $probe->getType();
            $config[$probe->getId()]['step'] = $probe->getStep();
            $config[$probe->getId()]['samples'] = $probe->getSamples();
            $config[$probe->getId()]['args'] = $probe->getArguments()->asArray();
            $config[$probe->getId()]['targets'][$device->getId()] = $device->getIp();
        }
    }

    private function getActiveSlaveGroups(Device $device)
    {
        if (isset($this->deviceSlaveGroupCache[$device->getId()])) {
            return $this->deviceSlaveGroupCache[$device->getId()];
        } else {
            $parent = $device->getDomain();
            while (null != $parent) {
                if (isset($this->domainSlaveGroupCache[$parent->getId()])) {
                    return $this->domainSlaveGroupCache[$parent->getId()];
                }
                $parent = $parent->getParent();
            }
        }

        return new ArrayCollection();
    }

    /**
     * @return ArrayCollection|Probe[]
     */
    private function getActiveProbes(Device $device)
    {
        if (isset($this->deviceProbeCache[$device->getId()])) {
            return $this->deviceProbeCache[$device->getId()];
        } else {
            $parent = $device->getDomain();
            while (null != $parent) {
                if (isset($this->domainProbeCache[$parent->getId()])) {
                    return $this->domainProbeCache[$parent->getId()];
                }
                $parent = $parent->getParent();
            }
        }

        return new ArrayCollection();
    }
}
