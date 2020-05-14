<?php

namespace AppBundle\Controller\Backoffice;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/backoffice/admin/stats")
 */
class StatsController extends Controller
{
    /**
     * @Route(name="stats")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $mosqueRepository = $em->getRepository("AppBundle:Mosque");
        $userRepository = $em->getRepository("AppBundle:User");
        $totalUsers = $userRepository->getCount();
        $totalUsersEnabled = $userRepository->getCount(true);

        $stats = [
            "items" => [
                "total" => $mosqueRepository->getCount(),
                "byType" => $mosqueRepository->getNumberByType(),
                "mosquesByCountry" => $mosqueRepository->getNumberByCountry(true),
                "allByCountry" => $mosqueRepository->getNumberByCountry(),
            ],
            "users" => [
                "total" => $totalUsers,
                "enabled" => $totalUsersEnabled,
                "disabled" => $totalUsers - $totalUsersEnabled,
            ]
        ];

        return $this->render('stats/index.html.twig', $stats);
    }

}
