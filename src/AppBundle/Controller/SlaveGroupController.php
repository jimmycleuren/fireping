<?php

namespace AppBundle\Controller;

use AppBundle\Entity\SlaveGroup;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Slavegroup controller.
 *
 * @Route("slavegroups")
 */
class SlaveGroupController extends Controller
{
    /**
     * Lists all slaveGroup entities.
     *
     * @Route("/", name="slavegroup_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $slaveGroups = $em->getRepository('AppBundle:SlaveGroup')->findAll();

        return $this->render('slavegroup/index.html.twig', array(
            'slaveGroups' => $slaveGroups,
            'active_menu' => 'slavegroup',
        ));
    }

    /**
     * Creates a new slaveGroup entity.
     *
     * @Route("/new", name="slavegroup_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $slaveGroup = new Slavegroup();
        $form = $this->createForm('AppBundle\Form\SlaveGroupType', $slaveGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($slaveGroup);
            $em->flush();

            return $this->redirectToRoute('slavegroup_show', array('id' => $slaveGroup->getId()));
        }

        return $this->render('slavegroup/new.html.twig', array(
            'slaveGroup' => $slaveGroup,
            'form' => $form->createView(),
            'active_menu' => 'slavegroup',
        ));
    }

    /**
     * Finds and displays a slaveGroup entity.
     *
     * @Route("/{id}", name="slavegroup_show")
     * @Method("GET")
     */
    public function showAction(SlaveGroup $slaveGroup)
    {
        $deleteForm = $this->createDeleteForm($slaveGroup);

        return $this->render('slavegroup/show.html.twig', array(
            'slaveGroup' => $slaveGroup,
            'delete_form' => $deleteForm->createView(),
            'active_menu' => 'slavegroup',
        ));
    }

    /**
     * Displays a form to edit an existing slaveGroup entity.
     *
     * @Route("/{id}/edit", name="slavegroup_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, SlaveGroup $slaveGroup)
    {
        $deleteForm = $this->createDeleteForm($slaveGroup);
        $editForm = $this->createForm('AppBundle\Form\SlaveGroupType', $slaveGroup);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('slavegroup_edit', array('id' => $slaveGroup->getId()));
        }

        return $this->render('slavegroup/edit.html.twig', array(
            'slaveGroup' => $slaveGroup,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'active_menu' => 'slavegroup',
        ));
    }

    /**
     * Deletes a slaveGroup entity.
     *
     * @Route("/{id}", name="slavegroup_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, SlaveGroup $slaveGroup)
    {
        $form = $this->createDeleteForm($slaveGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($slaveGroup);
            $em->flush();
        }

        return $this->redirectToRoute('slavegroup_index');
    }

    /**
     * Creates a form to delete a slaveGroup entity.
     *
     * @param SlaveGroup $slaveGroup The slaveGroup entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(SlaveGroup $slaveGroup)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('slavegroup_delete', array('id' => $slaveGroup->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
