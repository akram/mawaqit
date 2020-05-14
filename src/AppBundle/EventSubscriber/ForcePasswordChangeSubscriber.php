<?php

namespace AppBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class ForcePasswordChangeSubscriber implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var string
     */
    private $passwordPattern;

    public function __construct(RouterInterface $router, EventDispatcherInterface $dispatcher, $passwordPattern)
    {
        $this->router = $router;
        $this->dispatcher = $dispatcher;
        $this->passwordPattern = $passwordPattern;
    }

    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => ['onLogin'],
        ];
    }

    public function onLogin(InteractiveLoginEvent $event)
    {
        $password = $event->getRequest()->request->get("_password");

        if (!empty($password) && !preg_match("/{$this->passwordPattern}/", $password)) {
            $this->dispatcher->addListener(KernelEvents::RESPONSE, array($this, 'onKernelResponse'));
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $event->setResponse(
            new RedirectResponse($this->router->generate("fos_user_change_password", ['security-check' => 'yes']))
        );
    }

}