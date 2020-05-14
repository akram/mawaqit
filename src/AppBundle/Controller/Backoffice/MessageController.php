<?php

namespace AppBundle\Controller\Backoffice;

use AppBundle\Entity\Message;
use AppBundle\Entity\Mosque;
use AppBundle\Form\ConfigurationType;
use AppBundle\Form\MessageType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/backoffice")
 */
class MessageController extends Controller
{

    /**
     * @Route("/mosque/{mosque}/message", name="message_index")
     * @param Mosque $mosque
     *
     * @return Response
     */
    public function indexAction(Mosque $mosque)
    {

        $user = $this->getUser();
        if (!$this->isGranted("ROLE_ADMIN")) {
            if ($user !== $mosque->getUser()) {
                throw new AccessDeniedException;
            }

            if (!$mosque->isFullyValidated()) {
                throw new AccessDeniedException;
            }
        }

        $em = $this->getDoctrine()->getManager();
        $messages = $em->getRepository("AppBundle:Message")->getBySortableGroupsQuery(array('mosque' => $mosque))->getResult();
        $form = $this->createForm(ConfigurationType::class, $mosque->getConfiguration());

        return $this->render('message/index.html.twig', [
            "form" => $form->createView(),
            "mosque" => $mosque,
            "messages" => $messages
        ]);
    }

    /**
     * @Route("/mosque/{mosque}/message/update-time", name="ajax_message_update_time")
     */
    public function ajaxUpdateTimeAction(Request $request, Mosque $mosque)
    {

        $user = $this->getUser();
        if (!$this->isGranted("ROLE_ADMIN") && $user !== $mosque->getUser()) {
            throw new AccessDeniedException;
        }
        $em = $this->getDoctrine()->getManager();
        $conf = $mosque->getConfiguration();
        $conf->setTimeToDisplayMessage($request->request->get('timeToDisplayMessage'));

        $validator = $this->get('validator');
        $violations = $validator->validate($conf);

        if (count($violations) > 0) {
            $messageViolation = '';
            foreach ($violations as $violation) {
                $messageViolation .= $violation->getMessage() . '<br>';
            }
            return new Response($messageViolation, Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return new Response();
    }

    /**
     * @Route("/mosque/{mosque}/message/create", name="message_create")
     * @param Request                $request
     * @param EntityManagerInterface $em
     * @param Mosque                 $mosque
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request, EntityManagerInterface $em, Mosque $mosque)
    {

        $user = $this->getUser();
        if (!$this->isGranted("ROLE_ADMIN")) {
            if ($user !== $mosque->getUser()) {
                throw new AccessDeniedException;
            }
        }

        $message = new Message();
        $mosque->addMessage($message);
        $form = $this->createForm(MessageType::class, $message);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', "form.create.success");
            return $this->redirectToRoute('message_index', ['mosque' => $mosque->getId()]);
        }

        return $this->render('message/create.html.twig', [
            "mosque" => $mosque,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/mosque/{mosque}/message/{message}/edit", name="message_edit")
     * @param Request                $request
     * @param EntityManagerInterface $em
     * @param Mosque                 $mosque
     * @param Message                $message
     *
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request, EntityManagerInterface $em, Mosque $mosque, Message $message)
    {

        $user = $this->getUser();
        if (!$this->isGranted("ROLE_ADMIN")) {
            if ($user !== $mosque->getUser()) {
                throw new AccessDeniedException;
            }
        }

        $form = $this->createForm(MessageType::class, $message);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mosque->setUpdated(new DateTime());
            $em->flush();
            $this->addFlash('success', "form.edit.success");

            return $this->redirectToRoute('message_index', ['mosque' => $mosque->getId()]);
        }

        return $this->render('message/edit.html.twig', [
            "message" => $message,
            "mosque" => $mosque,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/message/{id}/delete", name="message_delete")
     * @param EntityManagerInterface $em
     * @param Message                $message
     *
     * @return RedirectResponse
     */
    public function deleteAction(EntityManagerInterface $em, Message $message)
    {
        $user = $this->getUser();
        if (!$this->isGranted("ROLE_ADMIN") && $user !== $message->getMosque()->getUser()) {
            throw new AccessDeniedException;
        }

        $mosque = $message->getMosque();
        $mosque->setUpdated(new DateTime());
        $em->remove($message);
        $em->flush();
        $this->addFlash('success', "form.delete.success");
        return $this->redirectToRoute('message_index', ['mosque' => $mosque->getId()]);
    }


    /**
     * Resorts an item using it's doctrine sortable property
     *
     * @param Request $request
     * @Route("/message/sort", name="message_sort")
     * @Method("PUT")
     *
     * @return JsonResponse
     */
    public function sortAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $message = $em->getRepository('AppBundle:Message')->find((int)$request->request->get("id"));
        $message->setPosition((int)$request->request->get("position"));
        $em->persist($message);
        $em->flush();

        return new JsonResponse();
    }


    /**
     * @Route("/mosque/{mosque}/message-bulk-change-status", name="message_bulk_change_status")
     * @Method("POST")
     * @param Request                $request
     * @param Mosque                 $mosque
     * @param EntityManagerInterface $em
     *
     * @return mixed
     * @throws Exception
     */
    public function bulkChangeStatusAction(Request $request, Mosque $mosque, EntityManagerInterface $em)
    {
        $messages = $request->request->get('status');
        if (is_array($messages)) {
            $repo = $em->getRepository('AppBundle:Message');
            $validator = $this->get('validator');
            foreach ($messages as $id => $status) {
                $message = $repo->find($id);
                if ($message instanceof Message) {
                    $message->setEnabled((bool)$status);
                    $errors = $validator->validate($message);
                    if (count($errors) > 0) {
                        $this->addFlash('danger', $errors[0]->getMessage());
                        return $this->redirectToRoute('message_index', ['mosque' => $mosque->getId()]);
                    }
                }
            }

            $mosque->setUpdated(new DateTime());
            $em->flush();
        }
        return $this->redirectToRoute('message_index', ['mosque' => $mosque->getId()]);
    }
}
