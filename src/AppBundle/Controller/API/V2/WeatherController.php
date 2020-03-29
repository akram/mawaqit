<?php

namespace AppBundle\Controller\API\V2;

use AppBundle\Entity\Mosque;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/api/2.0/mosque", options={"i18n"="false"})
 */
class WeatherController extends Controller
{
    /**
     * get weather of the mosque city
     *
     * @param $mosque Mosque
     *
     * @Route("/{uuid}/weather", name="weather")
     *
     * @ParamConverter("mosque", options={"mapping": {"uuid": "uuid"}})
     *
     * @return JsonResponse
     */
    public function getTemperatureAjaxAction(Mosque $mosque)
    {
        return new JsonResponse($this->get("app.weather_service")->getWeather($mosque));
    }

}
