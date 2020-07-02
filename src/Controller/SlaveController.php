<?php

namespace App\Controller;

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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SlaveController extends AbstractController
{
    private $em = null;
    private $logger = null;

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
        $slaves = $slaveRepository->findAll();

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
        if (extension_loaded('newrelic')) {
            newrelic_name_transaction('api_slaves_config');
        }

        $slave = $slaveRepository->findOneById($id);
        if (!$slave) {
            $slave = new Slave();
            $slave->setId($id);
        }

        $slave->setLastContact(new \DateTime());
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
            $devices = $deviceRepository->findByDomain($domains);
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

    /**
     * @return JsonResponse
     *
     * @Route("/api/slaves/{id}/result", methods={"POST"})
     * @ParamConverter("slave", class="App:Slave")
     *
     * Process new results from a slave
     */
    public function resultAction(Slave $slave, Request $request, ProcessorFactory $processorFactory, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        if (extension_loaded('newrelic')) {
            newrelic_name_transaction('api_slaves_result');
        }

        $this->em = $entityManager;
        $this->logger = $logger;

        $slave->setLastContact(new \DateTime());
        $this->em->persist($slave);
        $this->em->flush();

        $probeRepository = $this->em->getRepository(Probe::class);
        $deviceRepository = $this->em->getRepository(Device::class);

        $probes = json_decode($request->getContent());

        if (null === $probes || (is_array($probes) && 0 == count($probes))) {
            return new JsonResponse(['code' => 400, 'message' => 'Invalid json input'], 400);
        }

        try {
            foreach ($probes as $probeId => $probeData) {
                if (!isset($probeData->timestamp)) {
                    $this->logger->warning('Incorrect data received from slave');

                    return new JsonResponse(['code' => 400, 'message' => 'No timestamp found in probe data'], 400);
                }
                if (!isset($probeData->targets)) {
                    $this->logger->warning('Incorrect data received from slave');

                    return new JsonResponse(['code' => 400, 'message' => 'No targets found in probe data'], 400);
                }
                $probe = $probeRepository->findOneById($probeId);
                $timestamp = $probeData->timestamp;
                $targets = $probeData->targets;

                if (!$probe) {
                    continue;
                }

                foreach ($targets as $targetId => $targetData) {
                    $device = $deviceRepository->findOneById($targetId);
                    if (!$device) {
                        $this->logger->error("Slave sends data for device '$targetId' but it does not exist");
                        continue;
                    }
                    $this->logger->debug('Updating data for probe '.$probe->getType().' on '.$device->getName());
                    $processor = $processorFactory->create($probe->getType());
                    $processor->storeResult($device, $probe, $slave->getSlaveGroup(), $timestamp, $targetData);
                }
            }

            //execute 1 flush at the end, not for every datapoint
            $this->em->flush();
        } catch (WrongTimestampRrdException | DirtyInputException $e) {
            $this->logger->warning($e->getMessage());

            return new JsonResponse(['code' => 409, 'message' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage().' '.$e->getFile().':'.$e->getLine());

            return new JsonResponse(['code' => 500, 'message' => $e->getMessage()], 500);
        }

        return new JsonResponse(['code' => 200, 'message' => 'Results saved']);
    }

    /**
     * @return JsonResponse
     *
     * @Route("/api/slaves/{id}/error", methods={"POST"})
     * @ParamConverter("slave", class="App:Slave")
     *
     * Process errors from a slave
     */
    public function errorAction(Slave $slave, Request $request, LoggerInterface $logger)
    {
        $this->logger = $logger;

        //TODO: implement slave error handling
        $this->logger->info("Error received from $slave");

        return new JsonResponse(['code' => 200]);
    }

    /**
     * @return JsonResponse
     * @Route("/api/slaves/{id}/stats", methods={"POST"})
     */
    public function statsAction(Slave $slave, Request $request, EntityManagerInterface $entityManager, SlaveStatsRrdStorage $storage, LoggerInterface $logger)
    {
        $data = json_decode($request->getContent());

        $slave->setIp($data->ip);
        $slave->setLastContact(new \DateTime());
        $entityManager->persist($slave);
        $entityManager->flush();

        if (isset($data->workers)) {
            foreach ($data->workers as $timestamp => $workerData) {
                $storage->store($slave, "workers", $timestamp, $workerData);
            }
        }

        if (isset($data->queues)) {
            foreach ($data->queues as $timestamp => $queues) {
                $result = [];
                foreach ($queues as $id => $items) {
                    $result['queue' . $id] = $items;
                }
                $storage->store($slave, "queues", $timestamp, $result);
            }
        }

        $storage->store($slave, 'posts', date('U'), [
            'successful' => $data->posts->success,
            'failed' => $data->posts->failed,
            'discarded' => $data->posts->discarded,
        ]);

        $storage->store($slave, 'load', date('U'), [
            'load1' => $data->load[0],
            'load5' => $data->load[1],
            'load15' => $data->load[2],
        ]);

        $storage->store($slave, 'memory', date('U'), [
            'total' => $data->memory[0],
            'used' => $data->memory[1],
            'free' => $data->memory[2],
            'shared' => $data->memory[3],
            'buffer' => $data->memory[4],
            'available' => $data->memory[5],
        ]);

        return new JsonResponse(['code' => 200]);
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
