<?php

namespace AppBundle\Controller\API\V2;

use AppBundle\Entity\Mosque;
use AppBundle\Service\Statistic;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api/2.0/statistic", options={"i18n"="false"})
 */
class StatisticController extends Controller
{
    /**
     * @var Statistic
     */
    private $statistic;

    public function __construct(Statistic $statistic)
    {
        $this->statistic = $statistic;
    }

    /**
     * @param $mosque Mosque
     * @Route("/mosque/{uuid}/favorite")
     * @Method("POST")
     * @ParamConverter("mosque", options={"mapping": {"uuid": "uuid"}})
     *
     * @return Response
     */
    public function addToFavorites(Mosque $mosque)
    {
        $this->statistic->incrementFavoriteCounter($mosque);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param $mosque Mosque
     * @Route("/mosque/{uuid}/favorite")
     * @Method("DELETE")
     * @ParamConverter("mosque", options={"mapping": {"uuid": "uuid"}})
     *
     * @return Response
     */
    public function removeFromFavorites(Mosque $mosque)
    {
        $this->statistic->decrementFavoriteCounter($mosque);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

}
