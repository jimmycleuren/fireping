<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 30/05/2017
 * Time: 15:33
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Device;
use AppBundle\Entity\Probe;
use AppBundle\Entity\SlaveGroup;
use AppBundle\Exception\RrdException;
use AppBundle\Graph\PingGraph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class GraphController extends Controller
{
    /**
     * @param Device $device
     * @param PingGraph $pingGraph
     * @return Response
     *
     * @Method("GET")
     * @Route("/api/graphs/summary/{id}")
     * @ParamConverter("device", class="AppBundle:Device")
     */
    public function summaryAction(Device $device, PingGraph $pingGraph)
    {
        $probes = array();

        foreach($device->getProbes() as $probe) {
            $probes[] = $probe;
        }

        $current = $device->getDomain();

        do {
            foreach($current->getProbes() as $probe) {
                $probes[] = $probe;
            }

            $current = $current->getParent();
        } while($current != null);

        foreach ($probes as $probe) {
            if ($probe->getType() == "ping") {
                $filename = $pingGraph->getSummaryGraph($device, $probe);
                $response = new Response(file_get_contents($filename), 200);
                $response->headers->set('Content-Type', 'image/png');

                return $response;
            }
        }

        return new Response();
    }

    /**
     * @param Device $device
     * @param Probe $probe
     * @param SlaveGroup $slavegroup
     * @param Request $request
     * @param PingGraph $pingGraph
     * @return Response
     *
     * @Method("GET")
     * @Route("/api/graphs/detail/{device_id}/{probe_id}/{slavegroup_id}")
     * @ParamConverter("device", class="AppBundle:Device", options={"id" = "device_id"})
     * @ParamConverter("probe", class="AppBundle:Probe", options={"id" = "probe_id"})
     * @ParamConverter("slavegroup", class="AppBundle:SlaveGroup", options={"id" = "slavegroup_id"})
     */
    public function detailAction(Device $device, Probe $probe, SlaveGroup $slavegroup, Request $request, PingGraph $pingGraph)
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $debug = $this->container->get('session')->get('debug');

        if ($probe->getType() == "ping") {
            $filename = $pingGraph->getDetailGraph($device, $probe, $slavegroup, $start, $end, $debug);
            $response = new Response(file_get_contents($filename), 200);
            $response->headers->set('Content-Type', 'image/png');

            return $response;
        }

        return new Response();
    }
}