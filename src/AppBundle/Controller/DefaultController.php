<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Mosque;
use AppBundle\Entity\Parameters;
use AppBundle\Service\YamlLoader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{

    /**
     * @Route("", name="homepage")
     * @Cache(public=true, maxage="86400", smaxage="86400", expires="+86400 sec")
     * @param Request                $request
     * @param EntityManagerInterface $em
     * @param YamlLoader             $yamlLoader
     *
     * @return Response
     * @throws NonUniqueResultException
     */
    public function indexAction(Request $request, EntityManagerInterface $em, YamlLoader $yamlLoader)
    {
        if ($this->get('app.request_service')->isLocal()) {
            throw new NotFoundHttpException();
        }

        $mosqueRepo = $em->getRepository("AppBundle:Mosque");
        $paginator = $this->get('knp_paginator');
        $page = $request->query->getInt('page', 1);
        $page = $page > 0 ? $page : 1;
        $mosquesWithImage = $paginator->paginate($mosqueRepo->getMosquesWithImageQb(), $page, 9);
        $totalMosquesCount = $mosqueRepo->getCount();
        $mosqueNumberByCountry = $mosqueRepo->getNumberByCountry();
        $countries = $yamlLoader->getCountries();
        foreach ($mosqueNumberByCountry as $key => $value) {
            $mosqueNumberByCountry[$key]["coordinates"] = null;
            if (isset($countries[$value['country']])) {
                $mosqueNumberByCountry[$key]["coordinates"] = $countries[$value['country']];
            }
        }

        return $this->render('default/index.html.twig', [
            "totalMosquesCount" => $totalMosquesCount,
            "mosquesWithImage" => $mosquesWithImage,
            "mosqueNumberByCountry" => $mosqueNumberByCountry,
            "nextPage" => ++$page,
            "mawaqitApiAccessToken" => $this->getParameter("mawaqit_api_access_token"),
            "faqs" => $em->getRepository('AppBundle:Faq')->getPublicFaq()
        ]);
    }


    /**
     * @Route("/manual", name="manual")
     */
    public function manualAction(EntityManagerInterface $em)
    {
        if ($this->get('app.request_service')->isLocal()) {
            throw new NotFoundHttpException();
        }

        $parametersRepo = $em->getRepository(Parameters::class);
        $systemImageLink = $parametersRepo->findOneBy(["key" => "system_image_link"]);
        $raspberry3Link = $parametersRepo->findOneBy(["key" => "raspberry_3_link"]);
        $raspberry4Link = $parametersRepo->findOneBy(["key" => "raspberry_4_link"]);
        $rtcLink = $parametersRepo->findOneBy(["key" => "rtc_link"]);

        return $this->render('default/manual.html.twig', [
            "system_image_link" => $systemImageLink instanceof Parameters ? $systemImageLink->getValue() : "#",
            "raspberry_3_link" => $raspberry3Link instanceof Parameters ? $raspberry3Link->getValue() : "#",
            "raspberry_4_link" => $raspberry4Link instanceof Parameters ? $raspberry4Link->getValue() : "#",
            "rtc_link" => $rtcLink instanceof Parameters ? $rtcLink->getValue() : "#",
        ]);
    }

    /**
     * @Route("legal-notice", name="legal_notice")
     * @Cache(public=true, maxage="86400", smaxage="86400", expires="+86400 sec")
     */
    public function legalNoticeAction()
    {
        return $this->render('default/legal_notice.html.twig');
    }

    /**
     * @Route("/map/mosques/{countryCode}", name="mosques_map_ajax", options={"i18n"="false"})
     * @Cache(public=true, maxage="86400", smaxage="86400", expires="+86400 sec")
     * @param string                 $countryCode
     * @param EntityManagerInterface $em
     *
     * @return JsonResponse
     */
    public function getMosquesForMapAction($countryCode, EntityManagerInterface $em)
    {
        $mosquesForMap = $em->getRepository(Mosque::class)->getMosquesForMap($countryCode);
        return new JsonResponse($mosquesForMap);
    }

    /**
     * @Route("/mawaqit-team-message", name="mawaqit_team_message_ajax", options={"i18n"="false"})
     * @Cache(public=true, maxage="1800", smaxage="1800", expires="+1800 sec")
     * @param EntityManagerInterface $em
     *
     * @return JsonResponse
     */
    public function getMawaqitTeamMessageAction(EntityManagerInterface $em)
    {
        $parameter = $em->getRepository(Parameters::class)->findOneBy(['key' => 'mawaqit_message']);
        $message = null;
        if($parameter instanceof Parameters && !empty($parameter->getValue()))
        {
            $message = $parameter->getValue();
        }
        return new JsonResponse(['message'=> $message]);
    }

    /**
     * @param Request $request
     * @Route("/search-ajax")
     *
     * @return Response
     * @deprecated
     */
    public function searchAjaxAction(Request $request)
    {
        $request->query->set("word", $request->query->get("term"));
        return $this->forward("AppBundle:API\V2\Mosque:search", [
            "request" => $request
        ]);
    }
}
