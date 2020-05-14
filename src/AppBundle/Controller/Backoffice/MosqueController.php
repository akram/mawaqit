<?php

namespace AppBundle\Controller\Backoffice;

use AppBundle\Entity\Message;
use AppBundle\Entity\Mosque;
use AppBundle\Entity\User;
use AppBundle\Exception\GooglePositionException;
use AppBundle\Form\ConfigurationType;
use AppBundle\Form\MosqueSearchType;
use AppBundle\Form\MosqueSyncType;
use AppBundle\Form\MosqueType;
use AppBundle\Service\Calendar;
use AppBundle\Service\Statistic;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @Route("/backoffice/mosque")
 */
class MosqueController extends Controller
{

    /**
     * @Route(name="mosque_index")
     */
    public function indexAction(Request $request, EntityManagerInterface $em, Statistic $statistic)
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $mosqueRepository = $em->getRepository("AppBundle:Mosque");
        $nbByStatus = [];
        $isAdmin = $this->isGranted("ROLE_ADMIN");
        if ($isAdmin) {
            $nbByStatus = $mosqueRepository->getNumberByStatus();
        }

        $form = $this->createForm(MosqueSearchType::class, null, ['method' => 'GET']);
        $form->handleRequest($request);

        $filter = array_merge($request->query->all(), (array)$form->getData());
        $qb = $mosqueRepository->search($user, $filter, $isAdmin);

        $paginator = $this->get('knp_paginator');
        $mosques = $paginator->paginate($qb, $request->query->getInt('page', 1), 10);

        if ($this->isGranted("ROLE_SUPER_ADMIN")) {
            foreach ($mosques as $mosque) {
                $mosqueStatistic = $statistic->get($mosque);
                if ($mosqueStatistic && isset($mosqueStatistic->_source->mobileFavoriteCounter)) {
                    $mosque->setMobileFavoriteCounter($mosqueStatistic->_source->mobileFavoriteCounter);
                }

                if ($mosque->isNew()) {
                    $mosque->setSimilar($this->get("app.mosque_service")->getSimilarByLocalization($mosque));
                }
            }
        }

        $result = [
            "form" => $form->createView(),
            "mosques" => $mosques,
            "nbByStatus" => $nbByStatus,
            "languages" => $this->getParameter('languages')
        ];

        return $this->render('mosque/index.html.twig', $result);
    }


    /**
     * @Route("/ajax-search", name="mosque_search_calendar")
     * @Method("GET")
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function searchCalendarAction(Request $request, EntityManagerInterface $em)
    {
        $query = $request->query->get('query');
        $result = $em->getRepository("AppBundle:Mosque")->searchMosquesWithCalendar($query);
        return new JsonResponse($result);
    }

    /**
     * Restet Sync flag
     * This is useful for raspberry env
     * @Route("/sync/reset/{id}", name="mosque_reset_sync")
     */
    public function resetSyncAction(Mosque $mosque)
    {
        $em = $this->getDoctrine()->getManager();
        $mosque->setSynchronized(false);
        $em->flush();

        return $this->redirectToRoute('mosque', ['slug' => $mosque->getSlug()]);
    }

    /**
     * Sync mosque data, Only for raspberry env
     * @Route("/sync/{id}", name="mosque_sync")
     *
     * @param Request                $request
     * @param Client                 $client
     * @param Mosque                 $mosque
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $em
     *
     * @return Response
     */
    public function syncAction(
        Request $request,
        Client $client,
        Mosque $mosque,
        LoggerInterface $logger,
        EntityManagerInterface $em
    ) {
        $form = $this->createForm(MosqueSyncType::class);
        $form->handleRequest($request);
        $mosque->setSynchronized(false);
        $em->flush();

        if ($form->isSubmitted() && $form->isValid()) {
            if ($request->request->has('validate')) {
                try {
                    // pomulate mosque from online
                    $res = $client->get(
                        sprintf("/api/2.0/mosque/%s/data", $form->getData()['id']),
                        ['auth' => [$form->getData()['login'], $form->getData()['password']]]
                    );
                    $normalizer = new ObjectNormalizer(null, null, null, new ReflectionExtractor());
                    $serializer = new Serializer(
                        [new DateTimeNormalizer(), new ArrayDenormalizer(), $normalizer],
                        [new JsonEncoder()]
                    );
                    $json = json_decode($res->getBody()->getContents(), true);
                    $messages = $json["messages"];
                    unset($json["messages"]);

                    // poplulate messages
                    $serializer->denormalize(
                        $json,
                        Mosque::class,
                        'json',
                        ['object_to_populate' => $mosque, 'disable_type_enforcement' => true]
                    );

                    $mosque->setLocale($form->getData()['language']);

                    $serializer = new Serializer(
                        [new GetSetMethodNormalizer(), new ArrayDenormalizer()],
                        [new JsonEncoder()]
                    );
                    $messages = $serializer->denormalize($messages, Message::class . '[]', 'json');
                    $mosque->setMessages($messages);
                    $this->syncDownloadImages($mosque);
                    $this->syncSetUrls($mosque, $form);
                    $mosque->setSynchronized(true);
                    $em->flush();
                } catch (ConnectException $e) {
                    $this->addFlash("danger", "mosqueScreen.noInternetConnection");
                    $logger->critical($e->getMessage());
                } catch (ClientException $e) {
                    if ($e->getResponse()->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
                        $this->addFlash("danger", "mosqueScreen.badCredentials");
                    } elseif ($e->getResponse()->getStatusCode() === Response::HTTP_FORBIDDEN) {
                        $this->addFlash("danger", "mosqueScreen.wrongMosque");
                    } elseif ($e->getResponse()->getStatusCode() === Response::HTTP_NOT_FOUND) {
                        $this->addFlash("danger", "mosqueScreen.noMosqueFound");
                    } else {
                        $this->addFlash("danger", "mosqueScreen.otherPb");
                    }
                    $logger->critical($e->getMessage());
                } catch (\Exception $e) {
                    $logger->critical($e->getMessage());
                    $this->addFlash("danger", "mosqueScreen.otherPb");
                }
            }

            if ($form->getData()['screen'] === 'messages') {
                return $this->redirectToRoute(
                    'messages_id_index',
                    [
                        'id' => $mosque->getId(),
                        '_locale' => $mosque->getLocale()
                    ]
                );
            }
        }

        return $this->redirectToRoute(
            'mosque',
            [
                'slug' => $mosque->getSlug(),
                '_locale' => $mosque->getLocale()
            ]
        );
    }

    /**
     * @Route("/create", name="mosque_create")
     */
    public function createAction(Request $request)
    {
        if ($this->get('app.request_service')->isLocal()) {
            throw new AccessDeniedHttpException();
        }

        $mosque = new Mosque();
        $form = $this->createForm(MosqueType::class, $mosque);

        try {
            $form->handleRequest($request);
        } catch (GooglePositionException $exc) {
            $form->addError(
                new FormError(
                    $this->get("translator")->trans(
                        "form.configure.geocode_error",
                        [
                            "%address%" => $mosque->getLocalisation()
                        ]
                    )
                )
            );
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $mosque->setUser($this->getUser());
            $hijriAdjutment = $em->getRepository('AppBundle:Parameters')->findOneBy(['key' => 'hijri_adjustment']);
            $mosque->getConfiguration()->setHijriAdjustment((int)$hijriAdjutment->getValue());
            $em->persist($mosque);
            $em->flush();

            // send mail if mosque
            if ($mosque->isMosque()) {
                $this->get("app.mail_service")->mosqueCreated($mosque);
            }

            $this->addFlash('success', "form.create.success");
            return $this->redirectToRoute('mosque_index');
        }


        return $this->render(
            'mosque/create.html.twig',
            [
                'form' => $form->createView(),
                "google_api_key" => $this->getParameter('google_api_key')
            ]
        );
    }

    /**
     * @Route("/edit/{id}", name="mosque_edit")
     */
    public function editAction(Request $request, Mosque $mosque)
    {
        if ($this->get('app.request_service')->isLocal()) {
            throw new AccessDeniedHttpException();
        }

        $user = $this->getUser();
        if (!$this->isGranted("ROLE_ADMIN") && ($user !== $mosque->getUser() || !$mosque->isEditAllowed())) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(MosqueType::class, $mosque);

        try {
            $form->handleRequest($request);
        } catch (GooglePositionException $exc) {
            $form->addError(
                new FormError(
                    $this->get("translator")->trans(
                        "form.configure.geocode_error",
                        [
                            "%address%" => $mosque->getLocalisation()
                        ]
                    )
                )
            );
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            $this->addFlash('success', "form.edit.success");

            return $this->redirectToRoute('mosque_index');
        }
        return $this->render(
            'mosque/edit.html.twig',
            [
                'mosque' => $mosque,
                'form' => $form->createView(),
                "google_api_key" => $this->getParameter('google_api_key')
            ]
        );
    }

    /**
     * @Route("/delete/{id}", name="mosque_delete")
     */
    public function deleteAction(Mosque $mosque)
    {
        $user = $this->getUser();

        if ($mosque->isMosque()) {
            if (!$this->isGranted("ROLE_ADMIN")) {
                if ($user !== $mosque->getUser()) {
                    throw new AccessDeniedException;
                }

                if (!$mosque->isNew()) {
                    throw new AccessDeniedException;
                }
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($mosque);
        $em->flush();
        $this->addFlash('success', "form.delete.success");
        return $this->redirectToRoute('mosque_index');
    }

    /**
     * Force refresh page by updating updated_at
     * @Route("/refresh/{id}", name="mosque_refresh")
     */
    public function refreshAction(Mosque $mosque)
    {
        $em = $this->getDoctrine()->getManager();
        $mosque->setUpdated(new \Datetime());
        $em->flush();
        return new Response();
    }

    /**
     * @Route("/{id}/configure", name="mosque_configure")
     */
    public function configureAction(Request $request, Mosque $mosque)
    {
        if ($this->get('app.request_service')->isLocal()) {
            throw new AccessDeniedHttpException();
        }

        $user = $this->getUser();
        if (!$this->isGranted("ROLE_ADMIN") && $user !== $mosque->getUser()) {
            throw new AccessDeniedException();
        }
        $em = $this->getDoctrine()->getManager();

        $configuration = $mosque->getConfiguration();

        $form = $this->createForm(ConfigurationType::class, $configuration);

        // A hack due to sf 3.4.21 BC break, array are know considered as not valid data if not compound
        // no way to do this with form event listeners
        if ($request->getMethod() === 'POST') {
            $requestedConf = $request->request->get("configuration");
            $requestedConf["calendar"] = json_encode($requestedConf["calendar"]);
            $requestedConf["iqamaCalendar"] = json_encode($requestedConf["iqamaCalendar"]);
            $request->request->set("configuration", $requestedConf);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute(
                'mosque',
                [
                    'slug' => $mosque->getSlug()
                ]
            );
        }

        return $this->render(
            'mosque/configure.html.twig',
            [
                'months' => Calendar::MONTHS,
                'mosque' => $mosque,
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @Route("/qrcode/{id}", name="mosque_qr_code")
     */
    public function qrCodeAction(Mosque $mosque)
    {
        if (!$this->isGranted("ROLE_ADMIN")) {
            if (!$mosque->isFullyValidated()) {
                throw new AccessDeniedException;
            }
        }

        return $this->render(
            'mosque/qrcode.html.twig',
            [
                'mosque' => $mosque
            ]
        );
    }

    /**
     * @Route("/copy-conf/mosque/{currentMosque}/from/{selectedMosque}", name="copy_conf")
     * @return Response
     */
    public function copyConfAction(Mosque $currentMosque, Mosque $selectedMosque, EntityManagerInterface $em)
    {
        $selectedConf = clone $selectedMosque->getConf();
        $selectedConf->setId(null);
        $selectedConf->setBackgroundMotif("1");
        $currentMosque->setConfiguration($selectedConf);
        $em->persist($currentMosque);
        $em->flush();

        return $this->redirectToRoute(
            "mosque_configure",
            [
                'id' => $currentMosque->getId()
            ]
        );
    }

    /**
     * @param Mosque        $mosque
     * @param FormInterface $form
     */
    private function syncSetUrls(Mosque $mosque, FormInterface $form)
    {
        $rootDir = $this->getParameter("kernel.root_dir");
        $onlineSite = $this->getParameter("site");
        $offlineSite = "http://mawaqit.local";
        $urlPatternPrayerTimes = "%s/%s/id/%s";
        $urlPatternMessages = "%s/%s/messages/id/%s";
        $onlineUrl = sprintf($urlPatternPrayerTimes, $onlineSite, $form->getData()['language'], $form->getData()['id']);
        $offlineUrl = sprintf($urlPatternPrayerTimes, $offlineSite, $form->getData()['language'], 1);

        if ($form->getData()['screen'] === 'messages') {
            $onlineUrl = sprintf(
                $urlPatternMessages,
                $onlineSite,
                $form->getData()['language'],
                $form->getData()['id']
            );
            $offlineUrl = sprintf(
                $urlPatternMessages,
                $offlineSite,
                $form->getData()['language'],
                1
            );
        }

        file_put_contents("$rootDir/../docker/data/online_url.txt", $onlineUrl);
        file_put_contents("$rootDir/../docker/data/offline_url.txt", $offlineUrl);
    }

    /**
     * Download images when synching
     *
     * @param Mosque $mosque
     */
    private function syncDownloadImages(Mosque $mosque)
    {
        $site = $this->getParameter("site");
        $rootDir = $this->getParameter("kernel.root_dir");
        // download mosque and messages photos
        $uploadDir = "$rootDir/../web/upload";
        array_map(
            'unlink',
            array_filter(
                (array)glob("$uploadDir/*"),
                function ($file) {
                    return is_file($file);
                }
            )
        );

        if ($mosque->getImage1()) {
            @file_put_contents(
                "$uploadDir/{$mosque->getImage1()}",
                @fopen("$site/upload/{$mosque->getImage1()}", 'r')
            );
        }

        if ($mosque->getImage2()) {
            @file_put_contents(
                "$uploadDir/{$mosque->getImage2()}",
                @fopen("$site/upload/{$mosque->getImage2()}", 'r')
            );
        }

        foreach ($mosque->getMessages() as $message) {
            if ($message->getImage()) {
                @file_put_contents(
                    "$uploadDir/{$message->getImage()}",
                    @fopen("$site/upload/{$message->getImage()}", 'r')
                );
            }
        }
    }
}
