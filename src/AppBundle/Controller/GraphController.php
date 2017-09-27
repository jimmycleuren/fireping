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
     * @param $id
     * @return Response
     *
     * @Method("GET")
     * @Route("/api/graphs/summary/{id}")
     * @ParamConverter("device", class="AppBundle:Device")
     */
    public function summaryAction($device)
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
                $filename = $this->get('graph.ping')->getSummaryGraph($device, $probe);;
                $response = new Response(file_get_contents($filename), 200);
                $response->headers->set('Content-Type', 'image/png');

                return $response;
            }
        }

        return new Response();
    }

    /**
     * @param $id
     * @return Response
     *
     * @Method("GET")
     * @Route("/api/graphs/detail/{device_id}/{probe_id}")
     * @ParamConverter("device", class="AppBundle:Device", options={"id" = "device_id"})
     * @ParamConverter("probe", class="AppBundle:Probe", options={"id" = "probe_id"})
     */
    public function detailAction(Device $device, Probe $probe, Request $request)
    {
        $start = $request->get('start');
        if ($probe->getType() == "ping") {
            $filename = $this->get('graph.ping')->getDetailGraph($device, $probe, $start);
            $response = new Response(file_get_contents($filename), 200);
            $response->headers->set('Content-Type', 'image/png');

            return $response;
        }

        return new Response();
    }
}