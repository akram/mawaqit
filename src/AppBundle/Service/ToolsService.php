<?php

namespace AppBundle\Service;

use AppBundle\Entity\Mosque;
use AppBundle\Exception\GooglePositionException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Intl;

class ToolsService
{


    /**
     * @var EntityManager
     */
    private $em;


    /**
     * @var GoogleService
     */
    private $googleService;

    public function __construct(ContainerInterface $container)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 120);
        $this->em = $container->get("doctrine.orm.entity_manager");
        $this->googleService = $container->get("app.google_service");
    }

    public static function getCountryNameByCode($countryCode, $locale = null)
    {
        return Intl::getRegionBundle()->getCountryName($countryCode, $locale);
    }

    public function updateLocations($offset = 0)
    {
        $mosques = $this->em
            ->getRepository("AppBundle:Mosque")
            ->createQueryBuilder("m")
            ->where("m.city IS NOT NULL")
            ->andWhere("m.zipcode IS NOT NULL")
            ->andWhere("m.address IS NOT NULL")
            ->andWhere("m.country IS NOT NULL")
            ->andWhere("m.type = 'mosque'")
            ->setFirstResult($offset)
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        /**
         * @var $mosque Mosque
         */

        $editedMosques = [];
        foreach ($mosques as $mosque) {
            $latBefore = $mosque->getLatitude();
            $lonBefore = $mosque->getLongitude();

            $status = "OK";
            try {
                $gps = $this->googleService->getPosition($mosque);
                $mosque->setLatitude($gps->lat);
                $mosque->setLongitude($gps->lng);
                $this->em->persist($mosque);
            } catch (GooglePositionException $e) {
                $status = "KO";
            }

            $editedMosques[] = $mosque->getId() . ',' . $mosque->getName() . ',' . $mosque->getCity(
                ) . ',' . $mosque->getCountry() . ',' . $latBefore . ',' . $lonBefore . ',' . $mosque->getLatitude(
                ) . ',' . $mosque->getLongitude() . ',' . $status;
        }

        file_put_contents("/tmp/rapport_gps_$offset.csv", implode("\t\n", $editedMosques));
        $this->em->flush();
    }

    public function fixTimetable($firstDayInMarch, $lastDayInMarch, $offsetHour, $id = null)
    {
        ini_set('memory_limit', '512M');
        $repo = $this->em->getRepository(Mosque::class);
        $mosques = [];
        if (null !== $id) {
            $mosques[] = $repo->find((int)$id);
        }

        if (null === $id) {
            $mosques = $repo
                ->createQueryBuilder("m")
                ->innerJoin("m.configuration", "c")
                ->where("m.type = 'mosque'")
                ->andWhere("c.timezoneName like 'Europe%'")
                ->andWhere("c.sourceCalcul = 'calendar'")
                ->andWhere("c.dst = 0")
                ->getQuery()
                ->getResult();
        }

        $editedMosques = [];
        foreach ($mosques as $mosque) {
            $cal = $mosque->getConfiguration()->getDecodedCalendar();
            if (!empty($cal) && is_array($cal)) {
                $editedMosques[] = $mosque->getId() . ',' . $mosque->getUser()->getEmail();
                for ($month = 2; $month <= 9; $month++) {
                    $firstDay = 1;
                    $lastDay = count($cal[$month]);

                    if ($month === 2) {
                        $firstDay = $firstDayInMarch;
                    }
                    if ($month === 9) {
                        $lastDay = $lastDayInMarch;
                    }

                    for ($day = (int)$firstDay; $day <= (int)$lastDay; $day++) {
                        for ($prayer = 1; $prayer <= count($cal[$month][$day]); $prayer++) {
                            if (!empty($cal[$month][$day][$prayer])) {
                                $cal[$month][$day][$prayer] = $this->fixHour($cal[$month][$day][$prayer], $offsetHour);
                            }
                        }
                    }
                }

                $mosque->getConfiguration()->setDst(2);
                $mosque->getConfiguration()->setCalendar(json_encode($cal));

                if ($mosque->isSuspended() && $mosque->getReason() === 'prayer_times_not_correct') {
                    $mosque->setStatus(Mosque::STATUS_VALIDATED);
                }
            }
        }

        file_put_contents("/application/docker/data/rapport.csv", implode("\t\n", $editedMosques));
        $this->em->flush();
        return count($mosques);
    }

    private function fixHour($time, $offsetHour)
    {
        try {
            return (new \DateTime("$time:00"))->modify("$offsetHour hours")->format("H:i");
        } catch (\Exception $e) {
        }
        return $time;
    }
}
