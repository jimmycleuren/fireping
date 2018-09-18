<?php
/**
 * Created by PhpStorm.
 * User: jimmyc
 * Date: 30/05/2017
 * Time: 15:33
 */

namespace App\Controller;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\SlaveGroup;
use App\Exception\RrdException;
use App\Graph\HttpGraph;
use App\Graph\PingGraph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class GraphController extends Controller
{
    /**
     * @param Device $device
     * @param PingGraph $pingGraph
     * @return Response
     *
     * @Route("/api/graphs/summary/{id}", methods={"GET"})
     * @ParamConverter("device", class="App:Device")
     */
    public function summaryAction(Device $device, PingGraph $pingGraph, HttpGraph $httpGraph)
    {
        $probes = $device->getActiveProbes();

        foreach ($probes as $probe) {
            if ($probe->getType() == "ping") {
                $graph = $pingGraph->getSummaryGraph($device, $probe);
                $response = new Response($graph, 200);
                $response->headers->set('Content-Type', 'image/png');

                return $response;
            }
        }
        foreach ($probes as $probe) {
            if ($probe->getType() == "http") {
                $graph = $httpGraph->getSummaryGraph($device, $probe);
                $response = new Response($graph, 200);
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
     * @param SessionInterface $session
     * @return Response
     *
     * @Route("/api/graphs/detail/{device_id}/{probe_id}/{slavegroup_id}", methods={"GET"})
     * @ParamConverter("device", class="App:Device", options={"id" = "device_id"})
     * @ParamConverter("probe", class="App:Probe", options={"id" = "probe_id"})
     * @ParamConverter("slavegroup", class="App:SlaveGroup", options={"id" = "slavegroup_id"})
     */
    public function detailAction(Device $device = null, Probe $probe = null, SlaveGroup $slavegroup = null, Request $request, PingGraph $pingGraph, HttpGraph $httpGraph, SessionInterface $session)
    {
        $start = $request->get('start') ?: -3600;
        $end = $request->get('end') ?: date("U");
        $debug = $session->get('debug');

        if ($probe->getType() == "ping") {
            $graph = $pingGraph->getDetailGraph($device, $probe, $slavegroup, $start, $end, $debug);
            $response = new Response($graph, 200);
            $response->headers->set('Content-Type', 'image/png');

            return $response;
        }
        if ($probe->getType() == "http") {
            $graph = $httpGraph->getDetailGraph($device, $probe, $slavegroup, $start, $end, $debug);
            $response = new Response($graph, 200);
            $response->headers->set('Content-Type', 'image/png');

            return $response;
        }

        return new Response();
    }
}