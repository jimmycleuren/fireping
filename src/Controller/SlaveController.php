<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 14:33
 */

namespace App\Controller;

use App\Entity\Slave;
use App\Exception\WrongTimestampRrdException;
use App\Processor\PingProcessor;
use App\Processor\TracerouteProcessor;
use App\Repository\SlaveRepository;
use App\Storage\RrdStorage;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Neon\Entity;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SlaveController extends Controller
{
    private $em = null;
    private $logger = null;

    /**
     * Lists all slave entities.
     *
     * @Route("/slaves", name="slave_index", methods={"GET"})
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $slaves = $em->getRepository('App:Slave')->findAll();

        return $this->render('slave/index.html.twig', array(
            'slaves' => $slaves,
            'active_menu' => 'slave',
        ));
    }

    /**
     * @param string $id
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SlaveRepository $slaveRepository
     * @return JsonResponse
     *
     * @Route("/api/slaves/{id}/config", methods={"GET"})
     */
    public function configAction($id, Request $request, EntityManagerInterface $entityManager, SlaveRepository $slaveRepository)
    {
        $this->em = $entityManager;
        $slave = $slaveRepository->findOneById($id);

        if (!$slave) {
            $slave = new Slave();
            $slave->setId($id);
        }

        $slave->setLastContact(new \DateTime());
        $this->em->persist($slave);
        $this->em->flush();

        $config = array();

        $devices = array();
        if ($slave->getSlaveGroup()) {
            foreach ($slave->getSlaveGroup()->getDomains() as $domain) {
                $devices = array_merge($devices, $this->getDomainDevices($domain));
            }

            $devices = array_merge($devices, $slave->getSlaveGroup()->getDevices()->toArray());

            //remove devices that were selected, but the current slavegroup is not active for the device
            foreach ($devices as $key => $device) {
                $found = false;
                foreach ($device->getActiveSlaveGroups() as $slavegroup) {
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
                if ($value->getLastContact() < new \DateTime("10 minutes ago")) {
                    unset($slaves[$key]);
                }
            }

            $slavePosition = 0;
            foreach ($slaves as $key => $temp) {
                if ($temp->getId() == $slave->getId()) {
                    $slavePosition = $key;
                }
            }

            $size = ceil(count($devices) / count($slaves));
            if ($size > 0) {
                $subset = array_chunk($devices, (int)$size)[$slavePosition];
            } else {
                $subset = array();
            }

            foreach ($subset as $device) {
                $this->getDeviceProbes($device, $config);
            }
        }

        $response = new JsonResponse($config);
        $response->setEtag(md5(json_encode($config)));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @param Slave $slave
     * @param Request $request
     * @param PingProcessor $pingProcessor
     * @param TracerouteProcessor $tracerouteProcessor
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     *
     * @Route("/api/slaves/{id}/result", methods={"POST"})
     * @ParamConverter("slave", class="App:Slave")
     *
     * Process new results from a slave
     */
    public function resultAction(Slave $slave, Request $request, PingProcessor $pingProcessor, TracerouteProcessor $tracerouteProcessor, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->logger = $logger;

        $slave->setLastContact(new \DateTime());
        $this->em->persist($slave);
        $this->em->flush();

        $probeRepository = $this->em->getRepository("App:Probe");
        $deviceRepository = $this->em->getRepository("App:Device");

        $probes = json_decode($request->getContent());

        if ($probes === null || (is_array($probes) && count($probes) == 0)) {
            return new JsonResponse(array('code' => 400, 'message' => 'Invalid json input'), 400);
        }

        try {
            foreach ($probes as $probeId => $probeData) {
                if (!isset($probeData->timestamp)) {
                    $this->logger->warning("Incorrect data received from slave");
                    return new JsonResponse(array('code' => 400, 'message' => "No timestamp found in probe data"), 400);
                }
                if (!isset($probeData->targets)) {
                    $this->logger->warning("Incorrect data received from slave");
                    return new JsonResponse(array('code' => 400, 'message' => "No targets found in probe data"), 400);
                }
                $probe = $probeRepository->findOneById($probeId);
                $timestamp = $probeData->timestamp;
                $targets = $probeData->targets;

                foreach ($targets as $targetId => $targetData) {
                    $device = $deviceRepository->findOneById($targetId);
                    if (!$device) {
                        $this->logger->error("Slave sends data for device '$targetId' but it does not exist");
                        continue;
                    }
                    $this->logger->debug("Updating data for probe " . $probe->getType() . " on " . $device->getName());
                    switch ($probe->getType()) {
                        case "ping":
                            $pingProcessor->storeResult($device, $probe, $slave->getSlaveGroup(), $timestamp, $targetData);
                            break;
                        case "traceroute":
                            $tracerouteProcessor->storeResult($device, $probe, $slave->getSlaveGroup(), $timestamp, $targetData);
                            break;
                    }
                }
            }

            //execute 1 flush at the end, not for every datapoint
            $this->em->flush();

        } catch (WrongTimestampRrdException $e) {
            $this->logger->warning($e->getMessage());
            return new JsonResponse(array('code' => 409, 'message' => $e->getMessage()), 409);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage()." ".$e->getFile().":".$e->getLine());
            return new JsonResponse(array('code' => 500, 'message' => $e->getMessage()), 500);
        }

        return new JsonResponse(array("code" => 200, "message" => "Results saved"));
    }

    /**
     * @param Slave $slave
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

        return new JsonResponse(array('code' => 200));
    }

    private function getDomainDevices($domain)
    {
        $devices = array();

        foreach ($domain->getSubDomains() as $subdomain) {
            $devices = array_merge($devices, $this->getDomainDevices($subdomain));
        }

        $devices = array_merge($devices, $domain->getDevices()->toArray());

        return $devices;
    }

    private function getDeviceProbes($device, &$config)
    {
        foreach($device->getProbes() as $probe) {
            $config[$probe->getId()]['type'] = $probe->getType();
            $config[$probe->getId()]['step'] = $probe->getStep();
            $config[$probe->getId()]['samples'] = $probe->getSamples();
            $config[$probe->getId()]['args'] = json_decode($probe->getArguments());
            $config[$probe->getId()]['targets'][$device->getId()] = $device->getIp();
        }

        $parent = $device->getDomain();
        while($parent != null) {
            foreach($parent->getProbes() as $probe) {
                $config[$probe->getId()]['type'] = $probe->getType();
                $config[$probe->getId()]['step'] = $probe->getStep();
                $config[$probe->getId()]['samples'] = $probe->getSamples();
                $config[$probe->getId()]['args'] = json_decode($probe->getArguments());
                $config[$probe->getId()]['targets'][$device->getId()] = $device->getIp();
            }
            $parent = $parent->getParent();
        }
    }
}