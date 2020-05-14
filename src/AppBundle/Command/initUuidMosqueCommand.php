<?php

namespace AppBundle\Command;

use AppBundle\Entity\Mosque;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Obsolete
 * Class initUuidMosqueCommand
 * @package AppBundle\Command
 */
class initUuidMosqueCommand extends Command
{

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:init-uuid-mosque');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set("memory_limit", 1024);
        $mosques = $this->em->getRepository(Mosque::class)->findAll();
        foreach ($mosques as $key => $mosque) {
            if ($mosque->getUuid() instanceof UuidInterface)
            {
                continue;
            }
            $mosque->setUuid(Uuid::uuid4());
            if ($key % 20 === 0) {
                $this->em->flush();
            }
        }
        $this->em->flush();
    }

}