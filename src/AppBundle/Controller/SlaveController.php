<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 23/05/2017
 * Time: 14:33
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Slave;
use AppBundle\Exception\WrongTimestampRrdException;
use AppBundle\Storage\RrdStorage;
use Nette\Utils\Json;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
     * @Route("/slaves", name="slave_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $slaves = $em->getRepository('AppBundle:Slave')->findAll();

        return $this->render('slave/index.html.twig', array(
            'slaves' => $slaves,
            'active_menu' => 'slave',
        ));
    }

    /**
     * @param string $id
     * @return JsonResponse
     *
     * @Method("GET")
     * @Route("/api/slaves/{id}/config")
     */
    public function configAction($id, Request $request)
    {
        $this->em = $this->container->get('doctrine')->getManager();
        $slave = $this->em->getRepository("AppBundle:Slave")->findOneById($id);

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

            $query = $this->em->createQuery("SELECT d, p FROM AppBundle:Device d LEFT JOIN d.probes p WHERE d in (:devices)")
                ->setParameter("devices", $slave->getSlaveGroup()->getDevices())
                ->useQueryCache(true);

            $devices = array_merge($devices, $query->getResult());

            //remove devices that were selected, but the current slavegroup is not active for the device
            foreach ($devices as $key => $device) {
                $found = false;
                foreach ($device->getActiveSlaveGroups() as $slavegroup) {
                    if ($slavegroup->getId() == $slave->getSlaveGroup()->getId()) {
                        $found = true;
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
     * @return JsonResponse
     *
     * @Method("POST")
     * @Route("/api/slaves/{id}/result")
     * @ParamConverter("slave", class="AppBundle:Slave")
     *
     * Process new results from a slave
     */
    public function resultAction(Slave $slave, Request $request)
    {
        try {
            $this->em = $this->container->get('doctrine')->getManager();
            $this->logger = $this->container->get('logger');

            $slave->setLastContact(new \DateTime());
            $this->em->persist($slave);
            $this->em->flush();

            $probeRepository = $this->em->getRepository("AppBundle:Probe");
            $deviceRepository = $this->em->getRepository("AppBundle:Device");

            $probes = json_decode($request->getContent());

            foreach ($probes as $probeId => $probeData) {
                if (!isset($probeData->timestamp)) {
                    $this->logger->warning("Incorrect data received from slave");
                    return new JsonResponse(array('code' => 400, 'message' => "No timestamp found in probe data"), 400);
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
                            $this->container->get('processor.ping')->storeResult($device, $probe, $slave->getSlaveGroup(), $timestamp, $targetData);
                            break;
                        case "traceroute":
                            $this->container->get('processor.traceroute')->storeResult($device, $probe, $slave->getSlaveGroup(), $timestamp, $targetData);
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
            $this->logger->error($e->getMessage());
            return new JsonResponse(array('code' => 500, 'message' => $e->getMessage()), 500);
        }

        return new JsonResponse(array("code" => 200, "message" => "Results saved"));
    }

    /**
     * @param Slave $slave
     * @return JsonResponse
     *
     * @Method("POST")
     * @Route("/api/slaves/{id}/error")
     * @ParamConverter("slave", class="AppBundle:Slave")
     *
     * Process errors from a slave
     */
    public function errorAction($slave, Request $request)
    {
        $this->logger = $this->container->get('logger');

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

        $query = $this->em->createQuery("SELECT d, p FROM AppBundle:Device d LEFT JOIN d.probes p WHERE d in (:devices)")
            ->setParameter("devices", $domain->getDevices())
            ->useQueryCache(true)
        ;
        $devices = $devices = array_merge($devices, $query->getResult());

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