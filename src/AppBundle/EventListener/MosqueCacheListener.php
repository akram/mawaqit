<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Configuration;
use AppBundle\Entity\Mosque;
use AppBundle\Service\Cloudflare;
use AppBundle\Service\RequestService;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class MosqueCacheListener implements EventSubscriber
{
    /**
     * @var Cloudflare
     */
    private $cloudflare;

    /**
     * @var RequestService
     */
    private $requestService;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(Cloudflare $cloudflare, RequestService $requestService, EntityManagerInterface $em)
    {
        $this->cloudflare = $cloudflare;
        $this->requestService = $requestService;
        $this->em = $em;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postRemove,
            Events::preUpdate,
        ];
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof Mosque) {
            return;
        }

        $this->purgeCloudFlareCache($entity, $entity->getUpdated());
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof Mosque && !$entity instanceof Configuration) {
            return;
        }

        if ($entity instanceof Configuration) {
            $entity = $this->em->getRepository(Mosque::class)->findOneBy([
                'configuration' => $entity
            ]);

            if (!$entity instanceof Mosque) {
                return;
            }
        }

        $updated = $args->getOldValue("updated");
        $this->purgeCloudFlareCache($entity, $updated);
    }

    /**
     * @param Mosque    $mosque
     * @param \DateTime $updated
     */
    private function purgeCloudFlareCache(Mosque $mosque, \DateTime $updated)
    {
        if ($this->requestService->isLocal()) {
            return;
        }

        $this->cloudflare->purgeCache($mosque, $updated);
    }

}
