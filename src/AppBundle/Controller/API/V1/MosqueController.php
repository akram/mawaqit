<?php

namespace AppBundle\Controller\API\V1;

use AppBundle\Entity\Mosque;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @Route("/api/1.0.0", options={"i18n"="false"})
 */
class MosqueController extends Controller
{
    /**
     * @Route("/mosque/search")
     * @Method("GET")
     * @param Request $request
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $word = $request->query->get('word');
        $lat = $request->query->get('lat');
        $lon = $request->query->get('lon');
        $page = (int)$request->query->get('page', 1);
        $mosques = $this->get('app.mosque_service')->searchV1($word, $lat, $lon, $page);
        return new JsonResponse($mosques);
    }

    /**
     * Get pray times and other info of the mosque by ID
     * @Route("/mosque/{id}/prayer-times")
     * @Method("GET")
     *
     * @param Request $request
     * @param Mosque  $mosque
     *
     * @return Response
     */
    public function prayTimesAction(Request $request, Mosque $mosque)
    {
        return $this->forward("AppBundle:API\V2\Mosque:prayTimes", [
            "uuid" => $mosque->getUuid(),
            "request" => $request,
        ]);
    }

    /**
     * Get all data of mosque
     * @Route("/mosque/{id}", name="mosque_data")
     * @Method("GET")
     *
     * @param Mosque $mosque
     *
     * @return Response
     * @deprecated to be removed after uploding the new image system
     */
    public function dataAction(Mosque $mosque)
    {
        if (!$mosque->isValidated()) {
            throw new NotFoundHttpException();
        }

        $normalizer = new ObjectNormalizer();
        $normalizer->setIgnoredAttributes([
            'user',
            'id',
            'uuid',
            'created',
            'updated',
            'image1',
            'image2',
            'image3',
            'localisation',
            'justificatory',
            'location',
            "conf",
            "enabledMessages",
            "comments",
            'nbOfEnabledMessages',
            'calendarCompleted',
            'gpsCoordinates',
            'title',
            'types',
            'synchronized',
            'slug',
            'locale',
            'status',
            'url',
            'flashMessage',
            'messages',
        ]);

        $normalizer->setCircularReferenceHandler(function ($mosque) {
            return $mosque->getId();
        });

        $serializer = new Serializer([new DateTimeNormalizer(), $normalizer], [new JsonEncoder()]);
        $result = $serializer->serialize($mosque, 'json');
        return new Response($result, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

}
