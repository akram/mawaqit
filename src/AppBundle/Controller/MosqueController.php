<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Mosque;
use AppBundle\Form\MosqueSyncType;
use AppBundle\Service\RequestService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MosqueController extends Controller
{

    /**
     * @Route("/id/{id}", name="mosque_id")
     * @param Request $request
     * @param Mosque  $mosque
     *
     * @return Response
     */
    public function mosqueById(Request $request, Mosque $mosque)
    {
        return $this->forward(
            "AppBundle:Mosque:mosque",
            [
                "request" => $request,
                "slug" => $mosque->getSlug(),
            ]
        );
    }

    /**
     * @Route("/{slug}", options={"i18n"="false"})
     * @ParamConverter("mosque", options={"mapping": {"slug": "slug"}})
     * @param Mosque $mosque
     *
     * @return Response
     * @deprecated
     */
    public function mosqueBySlugWithoutLocale(Mosque $mosque)
    {
        $locale = 'en';
        $savedLocale = $mosque->getLocale();
        if ($savedLocale) {
            $locale = $savedLocale;
        }

        return $this->forward(
            "AppBundle:Mosque:mosque",
            [
                "slug" => $mosque->getSlug(),
                "_locale" => $locale
            ]
        );
    }

    /**
     * @Route("/{slug}", name="mosque")
     * @ParamConverter("mosque", options={"mapping": {"slug": "slug"}})
     * @param Request                $request
     * @param EntityManagerInterface $em
     * @param RequestService         $requestService
     * @param Mosque                 $mosque
     *
     * @return Response
     * @throws \Exception
     */
    public function mosqueAction(
        Request $request,
        EntityManagerInterface $em,
        RequestService $requestService,
        Mosque $mosque
    ) {
        if (!$mosque->isAccessible()) {
            throw new HttpException(
                404, null, null, [
                "Cache-Control" => "public, max-age=600"
            ], 0
            );
        }

        $mobileDetect = $this->get('mobile_detect.mobile_detector');
        $view = $request->query->get("view");

        // if mobile device request
        if ($view !== "desktop" && $mobileDetect->isMobile() && !$mobileDetect->isTablet()) {
            return $this->redirectToRoute(
                "mosque_mobile",
                ['slug' => $mosque->getSlug()],
                Response::HTTP_MOVED_PERMANENTLY
            );
        }

        // saving locale
        $savedLocale = $mosque->getLocale();
        if ($this->get('app.request_service')->isLocal() && $savedLocale !== $request->getLocale()) {
            $mosque->setLocale($request->getLocale());
            $em->flush();
        }

        $confData = $this->get('serializer')->normalize($mosque->getConfiguration(), 'json', ["groups" => ["screen"]]);

        $form = null;
        if ($requestService->isLocal()) {
            $form = $this->createForm(MosqueSyncType::class)->createView();
        }

        return $this->render(
            "mosque/mosque.html.twig",
            [
                'mosque' => $mosque,
                'confData' => array_merge($confData, $this->get('app.prayer_times')->prayTimes($mosque, true)),
                'languages' => $this->getParameter('languages'),
                'version' => $this->getParameter('version'),
                "support_email" => $this->getParameter("support_email"),
                "postmasterAddress" => $this->getParameter("postmaster_address"),
                "mawaqitApiAccessToken" => $this->getParameter("mawaqit_api_access_token"),
                'form' => $form,
            ],
            new Response(null, Response::HTTP_OK, ["X-Frame-Options" => "deny"])
        );
    }

    /**
     * @Route("/m/{slug}", name="mosque_mobile")
     * @ParamConverter("mosque", options={"mapping": {"slug": "slug"}})
     * @param EntityManagerInterface $em
     * @param Request                $request
     * @param Mosque                 $mosque
     *
     * @return Response
     * @throws \Exception
     */
    public function mosqueMobileAction(EntityManagerInterface $em, Request $request, Mosque $mosque)
    {
        if (!$mosque->isFullyValidated()) {
            return $this->forward("AppBundle:Mosque:blocked");
        }

        $response = new Response();
        $response->setLastModified($mosque->getUpdated());

        if ($response->isNotModified($request)) {
            return $response;
        }

        $confData = $this->get('serializer')->normalize($mosque->getConfiguration(), 'json', ["groups" => ["screen"]]);

        return $this->render(
            "mosque/mosque_mobile.html.twig",
            [
                'mosque' => $mosque,
                'confData' => array_merge($confData, $this->get('app.prayer_times')->prayTimes($mosque, true)),
                'version' => $this->getParameter('version'),
                "support_email" => $this->getParameter("support_email"),
                "postmasterAddress" => $this->getParameter("postmaster_address"),
                "mawaqitApiAccessToken" => $this->getParameter("mawaqit_api_access_token"),
                'messages' => $em->getRepository("AppBundle:Message")->getMessagesByMosque($mosque, null, true)
            ],
            $response
        );
    }

    /**
     * @Route("/w/{slug}", name="mosque_widget")
     * @ParamConverter("mosque", options={"mapping": {"slug": "slug"}})
     * @param Mosque $mosque
     *
     * @return Response
     */
    public function mosqueWidgetAction(Mosque $mosque)
    {
        if (!$mosque->isFullyValidated()) {
            return $this->forward("AppBundle:Mosque:blocked");
        }

        return $this->render(
            "mosque/widget.html.twig",
            [
                'mawaqitApiAccessToken' => $this->getParameter("mawaqit_api_access_token"),
                'mosque' => $mosque
            ]
        );
    }

    public function blockedAction()
    {
        return $this->render("mosque/blocked.html.twig");
    }

    /**
     * @Route("/{slug}/has-been-updated", name="mosque_has_been_updated", options={"i18n"="false"})
     * @ParamConverter("mosque", options={"mapping": {"slug": "slug"}})
     */
    public function hasBeenUpdatedAjaxAction(Request $request, Mosque $mosque)
    {
        $lastUpdatedDate = $request->query->get("lastUpdatedDate");
        if (empty($lastUpdatedDate)) {
            return new JsonResponse(["hasBeenUpdated" => false]);
        }

        $hasBeenUpdated = $this->get("app.prayer_times")->mosqueHasBeenUpdated($mosque, $lastUpdatedDate);
        return new JsonResponse(["hasBeenUpdated" => $hasBeenUpdated]);
    }

    /**
     * @Route("/mosque/{slug}/{_locale}", options={"i18n"="false"}, requirements={"_locale"= "en|fr|ar|tr"})
     * @ParamConverter("mosque", options={"mapping": {"slug": "slug"}})
     * @param Mosque $mosque
     *
     * @return Response
     */
    public function mosqueDeprected1Action(Mosque $mosque)
    {
        return $this->redirectToRoute("mosque", ["slug" => $mosque->getSlug()], Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * @Route("/{slug}/{_locale}", options={"i18n"="false"}, requirements={"_locale"= "en|fr|ar|tr"})
     * @param Mosque $mosque
     * @ParamConverter("mosque", options={"mapping": {"slug": "slug"}})
     *
     * @return Response
     */
    public function mosqueDeprected2Action(Mosque $mosque)
    {
        return $this->redirectToRoute("mosque", ["slug" => $mosque->getSlug()], Response::HTTP_MOVED_PERMANENTLY);
    }
}
