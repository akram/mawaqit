<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Mosque;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Route("/messages")
 */
class MessageController extends Controller
{

    /**
     * @Route("/{slug}", name="messages")
     * @ParamConverter("mosque", options={"mapping": {"slug": "slug"}})
     */
    public function indexAction(Mosque $mosque)
    {
        if (!$mosque->isAccessible()) {
            throw new NotFoundHttpException();
        }

        return $this->render("message/show_message_slider.html.twig", ['mosque' => $mosque]);
    }

    /**
     * @Route("/id/{id}", name="messages_id_index")
     */
    public function indexByIdAction(Mosque $mosque)
    {
        return $this->forward("AppBundle:Message:index", ['slug' => $mosque->getSlug()]);
    }

}
