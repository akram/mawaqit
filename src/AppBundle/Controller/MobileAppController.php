<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Mosque;
use AppBundle\Entity\Parameters;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class MobileAppController
 * @package AppBundle\Controller
 * @Route(options={"i18n"="false"})
 */
class MobileAppController extends Controller
{
    /**
     * @Route("/static/mobile/android/{mosque}/manifest", name="manifest")
     * @return JsonResponse
     */
    public function androidManifestAction(Mosque $mosque)
    {
        $manifest = [
            "short_name" => "Mawaqit",
            "name" => "Mawaqit",
            "icons" => [
                [
                    "src" => "/android-chrome-512x512.png",
                    "type" => "image/png",
                    "sizes" => "512x512"
                ],
                [
                    "src" => "/android-chrome-192x192.png",
                    "type" => "image/png",
                    "sizes" => "192x192"
                ]
            ],
            "start_url" => $this->generateUrl('mosque', ['slug' => $mosque->getSlug()]),
            "background_color" => "#286029",
            "theme_color" => "#286029",
            "display" => "standalone",
            "prefer_related_applications" => true,
            "related_applications" => [
                [
                    "platform" => "play",
                    "id" => "com.kanout.mawaqit"
                ]
            ],
        ];

        return new JsonResponse($manifest);
    }

    /**
     * @Route("/mobile/store-url", name="store_url")
     * @return Response
     */
    public function getStoreUrlAction(\Mobile_Detect $mobileDretect)
    {
        $url = $this->getParameter("app_google_play_url");

        if ($mobileDretect->is('iOs')) {
            $url = $this->getParameter("app_apple_store_url");
        }

        return $this->redirect($url);
    }

    /**
     * @Route("/api/2.0/mobile/version")
     * @Method("GET")
     * @param EntityManagerInterface $em
     *
     * @return JsonResponse
     */
    public function version(EntityManagerInterface $em)
    {
        $param = $em->getRepository(Parameters::class)->findOneBy(["key" => "mobile_version"]);
        $version = null;
        if ($param instanceof Parameters) {
            $version = $param->getValue();
        }

        return new JsonResponse(["version" => $version]);
    }

}
