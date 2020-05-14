<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Mosque;
use AppBundle\Service\PrayerTime;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/calendar")
 */
class CalendarController extends Controller
{
    /**
     * @Route("/{id}", name="calendar")
     */
    public function calendarAction(Mosque $mosque, PrayerTime $prayerTime)
    {
        return $this->render('mosque/calendar.html.twig', [
            'mosque' => $mosque,
            'calendar' => $prayerTime->getCalendar($mosque),
        ]);
    }

    /**
     * @Route("/{id}/pdf", name="calendar_pdf")
     */
    public function calendarPdfAction(Request $request,Mosque $mosque, LoggerInterface $logger)
    {
        if (!$this->isGranted("ROLE_ADMIN")) {
            if (!$mosque->isFullyValidated()) {
                throw new AccessDeniedException;
            }
        }

        $shortMd5 = substr(md5($mosque->getConf()->getCalendar()), 0, 12);
        $fileName = $mosque->getSlug() . "-timetable-$shortMd5-{$request->getLocale()}.pdf";
        $cacheDir = $this->getParameter("kernel.root_dir") . "/../docker/data/calendar";
        $cachedFile = "$cacheDir/$fileName";

        $headers = [
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Type' => 'application/pdf'
        ];

        // if the file is previously saved we serve it
        if (is_file($cachedFile)) {
            if ($fileName === basename($cachedFile)) {
                return new BinaryFileResponse($cachedFile, Response::HTTP_OK, $headers);
            }
        }

        try {
            $response = $this->get("csa_guzzle.client.pdfshift")->post("/v2/convert", [
                'form_params' => [
                    "source" => $this->generateUrl("calendar", ["id" => $mosque->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            ]);

            // delete old files
            array_map('unlink', glob("$cacheDir/{$mosque->getSlug()}*.pdf"));
            // save new content
            file_put_contents($cachedFile, $response->getBody()->getContents());

            return new Response($response->getBody(), Response::HTTP_OK, $headers);

        } catch (ClientException $e) {
            $logger->critical($e->getMessage());
            if ($e->getResponse()->getStatusCode() === Response::HTTP_FORBIDDEN) {
                $json = json_decode($e->getResponse()->getBody()->getContents());
                if ($json->identifier === "A116") {
                    $this->addFlash("danger", "PDF download quota exeeded, please retry later");
                }
            }
        } catch (\Exception $e) {
            $this->addFlash("danger", "An error has occured, please retry later");
            $logger->critical($e->getMessage());
        }

        return $this->redirectToRoute("mosque_index");
    }

    /**
     * @Route("/{id}/csv", name="calendar_csv")
     */
    public function calendarCsvAction(Mosque $mosque)
    {
        if (!$this->isGranted("ROLE_ADMIN")) {
            if (!$mosque->isConfigurationAllowed()) {
                throw new AccessDeniedException;
            }
        }

        $zipFilePath = $this->get("app.prayer_times")->getFilesFromCalendar($mosque);
        if (is_file($zipFilePath)) {
            $fileName = $mosque->getTitle() . ".zip";
            $response = new BinaryFileResponse($zipFilePath, Response::HTTP_OK,
                ['Content-Disposition' => 'attachment; filename="' . $fileName . '"']);
            $response->deleteFileAfterSend(true);
            return $response;
        }

        return new Response("An error has occured", Response::HTTP_INTERNAL_SERVER_ERROR);
    }

}
