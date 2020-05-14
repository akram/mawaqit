<?php

namespace AppBundle\Controller\API\V2;

use AppBundle\Entity\Mosque;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @Route("/api/2.0/mosque", options={"i18n"="false"})
 */
class MosqueController extends Controller
{
    /**
     * @Route("/search")
     * @Method("GET")
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function searchAction(Request $request)
    {
        $word = $request->query->get('word');
        $lat = $request->query->get('lat');
        $lon = $request->query->get('lon');
        $page = (int)$request->query->get('page', 1);
        $mosques = $this->get('app.mosque_service')->searchV2($word, $lat, $lon, $page);
        return new JsonResponse($mosques);
    }

    /**
     * @Cache(public=true, maxage="86400", smaxage="86400", expires="+86400 sec")
     * @Route("/list-uuid")
     * @Method("GET")
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listUUIDAction(Request $request)
    {
        $page = (int)$request->query->get('page', 1);
        $mosques = $this->get('app.mosque_service')->listUUID($page);
        return new JsonResponse($mosques);
    }

    /**
     * Get pray times and other info of the mosque by uuid
     *
     * @Route("/{uuid}/prayer-times", name="app_api_mosque_praytimes")
     * @Method("GET")
     *
     * @ParamConverter("mosque", options={"mapping": {"uuid": "uuid"}})
     *
     * @param Request $request
     * @param Mosque  $mosque
     *
     * @return JsonResponse|Response
     *
     * @throws \Exception
     */
    public function prayTimesAction(Request $request, Mosque $mosque)
    {

        $response = new JsonResponse();

        $response->setPublic();

        if (!$mosque->isFullyValidated()) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            return $response;
        }

        /** Begin Deprecated */
        if ($updatedAt = $request->query->get('updatedAt')) {
            if (!is_numeric($updatedAt)) {
                throw new BadRequestHttpException();
            }

            if ($mosque->getUpdated()->getTimestamp() <= $updatedAt) {
                $response->setStatusCode(Response::HTTP_NOT_MODIFIED);
                return $response;
            }
        }
        /** End Deprecated */

        $response->setLastModified($mosque->getUpdated());
        if ($response->isNotModified($request)) {
            return $response;
        }

        $calendar = $request->query->has('calendar');
        $result = $this->get('app.prayer_times')->prayTimes($mosque, $calendar);

        $response->setData($result);

        return $response;
    }

    /**
     * Get all data of mosque
     * @Route("/{id}/data")
     * @Method("GET")
     *
     * @param Mosque $mosque
     *
     * @return Response
     */
    public function dataAction(Mosque $mosque)
    {
        if ($mosque->getUser() !== $this->getUser()) {
            throw new HttpException(Response::HTTP_FORBIDDEN);
        }

        if (!$mosque->isValidated()) {
            throw new NotFoundHttpException();
        }

        $normalizer = new ObjectNormalizer();
        $normalizer->setIgnoredAttributes([
            'user',
            'id',
            'uuid',
            'label',
            'created',
            'updated',
            'file1',
            'file2',
            'file3',
            'image3',
            'localisation',
            'justificatory',
            'location',
            "conf",
            "enabledMessages",
            "comments",
            'calendarCompleted',
            'decodedCalendar',
            'gpsCoordinates',
            'types',
            'synchronized',
            'emailScreenPhotoReminder',
            'reason',
            'image',
            'slug',
            'locale',
            'status',
            'url'
        ]);

        $normalizer->setCircularReferenceHandler(function ($mosque) {
            return $mosque->getId();
        });

        $serializer = new Serializer([new DateTimeNormalizer(), $normalizer], [new JsonEncoder()]);
        $result = $serializer->serialize($mosque, 'json');

        return new Response($result, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

}
