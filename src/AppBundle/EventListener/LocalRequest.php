<?php

namespace AppBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LocalRequest
{

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(TokenStorage $tokenStorage, EntityManagerInterface $em)
    {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequest()->getHost() !== 'mawaqit.local') {
            return;
        }

        $user = $this->em->getRepository('AppBundle:User')->find(1);
        $providerKey = 'main';
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());
        $this->tokenStorage->setToken($token);
    }
}
