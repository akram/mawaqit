<?php

namespace AppBundle\Controller\API\V2;

use AppBundle\Service\YamlLoader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route(options={"i18n"="false"})
 */
class SupportController extends Controller
{

    /**
     * @Route("/api/2.0/support")
     * @Method("GET")
     * @param Request    $request
     * @param YamlLoader $yamlLoader
     *
     * @return Response
     */
    public function support(Request $request, YamlLoader $yamlLoader)
    {
        $country = $request->query->get("country", "OC");

        $support = $yamlLoader->getSupport();

        if (!isset($support[$country])) {
            $country = "OC";
        }

        $phone = substr(array_rand($support[$country]), 2);

        return new JsonResponse(
            [
                "mobile" => [
                    "whatsapp" => "whatsapp://send?phone=$phone",
                ],
                "web" => [
                    "whatsapp" => "https://wa.me/$phone",
                ]
            ]
        );
    }

    /**
     * @Route("/support/web/{country}", name="web_support")
     * @Method("GET")
     * @param YamlLoader $yamlLoader
     * @param string $country
     *
     * @return Response
     */
    public function supportWeb(YamlLoader $yamlLoader, $country)
    {
        $support = $yamlLoader->getSupport();

        if (!isset($support[$country])) {
            $country = "OC";
        }

        $phone = substr(array_rand($support[$country]), 2);

        return new RedirectResponse("https://wa.me/$phone");
    }

}
