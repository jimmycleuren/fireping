<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\Probe;
use App\Entity\Slave;
use App\Entity\SlaveGroup;
use App\Graph\GraphFactory;
use App\Graph\SlaveGraph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class GraphController extends AbstractController
{
    /**
     * @param Device $device
     * @param GraphFactory $graphFactory
     * @return Response
     *
     * @Route("/api/graphs/summary/{id}", methods={"GET"})
     * @ParamConverter("device", class="App:Device")
     */
    public function summaryAction(Device $device, Request $request, GraphFactory $graphFactory)
    {
        $start = $request->get('start') ?? -43200;
        $end = $request->get('end');
        $width = $request->get('width') ?? 600;

        $probes = $device->getActiveProbes();
        $priority = ['ping', 'traceroute', 'http'];

        foreach ($priority as $type) {
            foreach ($probes as $probe) {
                if ($probe->getType() == $type) {
                    $graph = $graphFactory->create($type)->getSummaryGraph($device, $probe, $start, $end, $width);
                    $response = new Response($graph, 200);
                    $response->headers->set('Content-Type', 'image/png');

                    return $response;
                }
            }
        }

        return new Response();
    }

    /**
     * @param Device $device
     * @param Probe $probe
     * @param SlaveGroup $slavegroup
     * @param Request $request
     * @param GraphFactory $graphFactory
     * @param SessionInterface $session
     * @return Response
     *
     * @Route("/api/graphs/detail/{device_id}/{probe_id}/{slavegroup_id}", methods={"GET"})
     * @ParamConverter("device", class="App:Device", options={"id" = "device_id"})
     * @ParamConverter("probe", class="App:Probe", options={"id" = "probe_id"})
     * @ParamConverter("slavegroup", class="App:SlaveGroup", options={"id" = "slavegroup_id"})
     */
    public function detailAction(Device $device = null, Probe $probe = null, SlaveGroup $slavegroup = null, Request $request, GraphFactory $graphFactory, SessionInterface $session)
    {
        $start = $request->get('start') ?: -3600;
        $end = $request->get('end') ?: date("U");
        $type = $request->get('type') ?: "default";
        $debug = $session->get('debug');

        $graph = $graphFactory->create($probe->getType())->getDetailGraph($device, $probe, $slavegroup, $start, $end, $type, $debug);
        $response = new Response($graph, 200);
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }

    /**
     * @Route("/api/graphs/slaves/{slave}/{type}", methods={"GET"})
     *
     * @param Slave $slave
     * @param string $type
     * @param Request $request
     * @param SlaveGraph $slaveGraph
     * @return Response
     */
    public function slaveAction(Slave $slave, string $type, Request $request, SlaveGraph $slaveGraph)
    {
        $start = $request->get('start') ?: -3600;
        $end = $request->get('end') ?: date("U");

        $graph = $slaveGraph->getGraph($slave, $type, $start, $end);
        $response = new Response($graph, 200);
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }
}