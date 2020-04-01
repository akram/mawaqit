<?php

namespace AppBundle\Service;

use AppBundle\Entity\Mosque;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class Cloudflare
{
    /**
     * @var Client
     */
    private $cloudflareClient;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $zoneId;

    /**
     * @var string
     */
    private $site;
    /**
     * @var array
     */
    private $languages;

    public function __construct(Client $cloudflareClient, LoggerInterface $logger, $zoneId, $site, array $languages)
    {
        $this->cloudflareClient = $cloudflareClient;
        $this->logger = $logger;
        $this->zoneId = $zoneId;
        $this->site = $site;
        $this->languages = $languages;
    }

    /**
     * @param Mosque    $mosque
     * @param \DateTime $updatedDate
     */
    public function purgeCache(Mosque $mosque, \DateTime $updatedDate)
    {
        if(!$mosque->isMosque()){
            return;
        }

        if(!$mosque->isFullyValidated()){
            return;
        }

        $files = [];

        foreach ($this->languages as $language) {
            //https://mawaqit.net/fr/m/grande-mosquee-de-grigny
            $files[] = sprintf("%s/%s/m/%s", $this->site, $language, $mosque->getSlug());
        }

        // https://mawaqit.net/api/1.0.0/mosque/3175/prayer-times
        $files[] = sprintf("%s/api/1.0.0/mosque/%s/prayer-times", $this->site, $mosque->getId());
        // https://mawaqit.net/api/1.0.0/mosque/3175/prayer-times?calendar&updatedAt=1585396469
        $files[] = sprintf("%s/api/1.0.0/mosque/%s/prayer-times?calendar&updatedAt=%s", $this->site, $mosque->getId(), $updatedDate->getTimestamp());
        // https://mawaqit.net/api/2.0/mosque/7c0554c4-219b-4955-993c-1ac20941e0d2/prayer-times?calendar&updatedAt=1585396469
        $files[] = sprintf("%s/api/2.0/mosque/%s/prayer-times?calendar&updatedAt=%s", $this->site, $mosque->getUuid(), $updatedDate->getTimestamp());
        // https://mawaqit.net/api/2.0/mosque/7c0554c4-219b-4955-993c-1ac20941e0d2/prayer-times
        $files[] = sprintf("%s/api/2.0/mosque/%s/prayer-times", $this->site, $mosque->getUuid());

        try {
            $this->cloudflareClient->post("v4/zones/{$this->zoneId}/purge_cache", [
                "json" => [
                    "files" => $files
                ]
            ]);
        } catch (\Exception $exception) {
            $this->logger->error("Can't purge cloudflare cache", [
                "exception" => $exception->getMessage(),
                "mosque" => $mosque->getId(),
            ]);
        }
    }
}
