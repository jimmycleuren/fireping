<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Probe;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Probe controller.
 *
 * @Route("probes")
 */
class ProbeController extends Controller
{
    /**
     * Lists all probe entities.
     *
     * @Route("/", name="probe_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $probes = $em->getRepository('AppBundle:Probe')->findAll();

        return $this->render('probe/index.html.twig', array(
            'probes' => $probes,
            'active_menu' => 'probe',
        ));
    }

    /**
     * Creates a new probe entity.
     *
     * @Route("/new", name="probe_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $probe = new Probe();
        $form = $this->createForm('AppBundle\Form\ProbeType', $probe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($probe);
            $em->flush();

            return $this->redirectToRoute('probe_show', array('id' => $probe->getId()));
        }

        return $this->render('probe/new.html.twig', array(
            'probe' => $probe,
            'form' => $form->createView(),
            'active_menu' => 'probe',
        ));
    }

    /**
     * Finds and displays a probe entity.
     *
     * @Route("/{id}", name="probe_show")
     * @Method("GET")
     */
    public function showAction(Probe $probe)
    {
        $deleteForm = $this->createDeleteForm($probe);

        return $this->render('probe/show.html.twig', array(
            'probe' => $probe,
            'delete_form' => $deleteForm->createView(),
            'active_menu' => 'probe',
        ));
    }

    /**
     * Displays a form to edit an existing probe entity.
     *
     * @Route("/{id}/edit", name="probe_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Probe $probe)
    {
        $deleteForm = $this->createDeleteForm($probe);
        $editForm = $this->createForm('AppBundle\Form\ProbeType', $probe);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('probe_edit', array('id' => $probe->getId()));
        }

        return $this->render('probe/edit.html.twig', array(
            'probe' => $probe,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'active_menu' => 'probe',
        ));
    }

    /**
     * Deletes a probe entity.
     *
     * @Route("/{id}", name="probe_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Probe $probe)
    {
        $form = $this->createDeleteForm($probe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($probe);
            $em->flush();
        }

        return $this->redirectToRoute('probe_index');
    }

    /**
     * Creates a form to delete a probe entity.
     *
     * @param Probe $probe The probe entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Probe $probe)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('probe_delete', array('id' => $probe->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
