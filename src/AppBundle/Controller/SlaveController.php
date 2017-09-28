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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SlaveController extends Controller
{
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
     * Displays a form to edit an existing slaveGroup entity.
     *
     * @Route("/slaves/{id}/edit", name="slave_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Slave $slave)
    {
        $deleteForm = $this->createDeleteForm($slave);
        $editForm = $this->createForm('AppBundle\Form\SlaveType', $slave);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('slave_edit', array('id' => $slave->getId()));
        }

        return $this->render('slave/edit.html.twig', array(
            'slaveGroup' => $slave,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'active_menu' => 'slave',
        ));
    }

    /**
     * Deletes a slave entity.
     *
     * @Route("/slaves/{id}", name="slave_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Slave $slave)
    {
        $form = $this->createDeleteForm($slave);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($slave);
            $em->flush();
        }

        return $this->redirectToRoute('slave_index');
    }

    /**
     * Creates a form to delete a slave entity.
     *
     * @param Slave $slave The slave entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Slave $slave)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('slave_delete', array('id' => $slave->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * @param $id
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

        if ($slave->getSlaveGroup()) {
            foreach ($slave->getSlaveGroup()->getDomains() as $domain) {
                $this->getDomainDevices($domain, $config);
            }

            foreach ($slave->getSlaveGroup()->getDevices() as $device) {
                $result[$device->getName()] = array(
                    'ip' => $device->getIp(),
                    'probes' => $this->getDeviceProbes($device, $config),
                );
            }
        }

        $response = new JsonResponse($config);
        $response->setEtag(md5(json_encode($config)));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @param $id
     * @return array
     *
     * @Method("POST")
     * @Route("/api/slaves/{id}/result")
     * @ParamConverter("slave", class="AppBundle:Slave")
     *
     * Process new results from a slave
     */
    public function resultAction($slave, Request $request)
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
                            $this->container->get('processor.ping')->storeResult($device, $probe, $timestamp, $targetData);
                            break;
                    }
                }
            }
        } catch (WrongTimestampRrdException $e) {
            return new JsonResponse(array('code' => 409, 'message' => $e->getMessage()), 409);
        } catch (\Exception $e) {
            return new JsonResponse(array('code' => 500, 'message' => $e->getMessage()), 500);
        }

        return new JsonResponse(array("code" => 200, "message" => "Results saved"));
    }

    private function getDomainDevices($domain, &$config)
    {
        foreach ($domain->getSubDomains() as $subdomain) {
            $this->getDomainDevices($subdomain, $config);
        }

        foreach ($domain->getDevices() as $device) {
            $this->getDeviceProbes($device, $config);
        }
    }

    private function getDeviceProbes($device, &$config)
    {
        foreach($device->getProbes() as $probe) {
            $config[$probe->getId()]['type'] = $probe->getType();
            $config[$probe->getId()]['step'] = $probe->getStep();
            $config[$probe->getId()]['samples'] = $probe->getSamples();
            $config[$probe->getId()]['targets'][$device->getId()] = $device->getIp();
        }

        $parent = $device->getDomain();
        while($parent != null) {
            foreach($parent->getProbes() as $probe) {
                $config[$probe->getId()]['type'] = $probe->getType();
                $config[$probe->getId()]['step'] = $probe->getStep();
                $config[$probe->getId()]['samples'] = $probe->getSamples();
                $config[$probe->getId()]['targets'][$device->getId()] = $device->getIp();
            }
            $parent = $parent->getParent();
        }
    }
}