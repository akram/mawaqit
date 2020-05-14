<?php

namespace AppBundle\Controller\Backoffice;

use AppBundle\Entity\Mosque;
use AppBundle\Form\DstDatesType;
use AppBundle\Form\HijriAdjustmentType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/backoffice/superadmin")
 */
class ToolsController extends Controller
{

    /**
     * @Route(name="admin_index")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $parameter = $em->getRepository("AppBundle:Parameters")->findOneBy(['key' => 'hijri_adjustment']);
        $formHijriDate = $this->createForm(HijriAdjustmentType::class, null,
            ['action' => $this->generateUrl('update_hijri_date')]);
        $formDstDates = $this->createForm(DstDatesType::class, null,
            ['action' => $this->generateUrl('update_dst_date')]);
        $formHijriDate->get('hijriAdjustment')->setData($parameter->getValue());
        return $this->render('tools/index.html.twig', [
            'hijriForm' => $formHijriDate->createView(),
            'dstForm' => $formDstDates->createView()
        ]);
    }

    /**
     * @Route("/email-preview/{template}/{mosque}")
     */
    public function emailPreviewAction($template, Mosque $mosque)
    {
        return $this->render("email_templates/$template.html.twig", [
            'mosque' => $mosque
        ]);
    }

    /**
     * @Route("/force-update-all", name="mosque_force_update_all")
     */
    public function forceUpdateAllAction()
    {
        $em = $this->getDoctrine()->getManager();
        $em->getRepository("AppBundle:Mosque")->forceUpdateAll();
        $this->addFlash('success', $this->get("translator")->trans("mosque.force_update_all.success"));
        return $this->redirectToRoute('admin_index');
    }

    /**
     * Update hijri date for all mosques
     * @Route("/update-hijri-date", name="update_hijri_date")
     */
    public function updateHegerianDateAction(Request $request)
    {
        $form = $this->createForm(HijriAdjustmentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->getRepository("AppBundle:Configuration")->updateHijriDate($form->getData());
            $parameter = $em->getRepository("AppBundle:Parameters")->findOneBy(['key' => 'hijri_adjustment']);
            // if no country submitted, update the ijri_adjustment value in parameters
            $parameter->setValue($form->getData()["hijriAdjustment"]);
            $em->flush();

            $this->addFlash('success', "mosque.update_hijri_date.success");
            return $this->redirectToRoute('admin_index');
        }
    }

    /**
     * Update dst date for mosques of a country
     * @Route("/update-dst-date", name="update_dst_date")
     */
    public function updateDstDateAction(Request $request)
    {
        $form = $this->createForm(DstDatesType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->getRepository("AppBundle:Configuration")->updateDstDates($form->getData());
            $this->addFlash('success', "Mise à jour effectuée avec succès");
            return $this->redirectToRoute('admin_index');
        }
    }

    /**
     * @Route("/opcache", name="opcache")
     */
    public function opcache()
    {
        return $this->render("tools/opcache.html.php");
    }

}