<?php

namespace AppBundle\Command;

use AppBundle\Entity\Mosque;
use AppBundle\Service\MosqueService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ElasticMosquePopulateCommand extends Command
{

    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var PaginatorInterface
     */
    private $paginator;
    /**
     * @var MosqueService
     */
    private $mosqueService;

    public function __construct(EntityManagerInterface $em, PaginatorInterface $paginator, MosqueService $mosqueService)
    {
        $this->em = $em;
        $this->paginator = $paginator;
        $this->mosqueService = $mosqueService;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:elastic-mosque-populate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set("memory_limit", 1024);
        $query = $this->em->getRepository(Mosque::class)
            ->createQueryBuilder('m')
            ->select()
            ->where("m.type = :type")
            ->andWhere("m.status IN (:status)")
            ->setParameter(":type", Mosque::TYPE_MOSQUE)
            ->setParameter(":status", [Mosque::STATUS_VALIDATED, Mosque::STATUS_CHECK]);

        $this->mosqueService->elasticDropIndex();
        $this->mosqueService->initElasticIndex();

        $limit = 50;
        $pagination = $this->paginator->paginate($query,1, $limit);
        $progressBar = new ProgressBar($output, $pagination->getTotalItemCount());
        for ($page = 1; $page <= $pagination->getPageCount(); $page++) {
            $mosques = $this->paginator->paginate($query, $page, $limit);
            $this->mosqueService->elasticBulkPopulate($mosques);
            $progressBar->advance(count($mosques));
        }

        $progressBar->finish();
        $output->write("\n");
    }
}