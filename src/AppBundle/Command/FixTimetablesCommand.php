<?php

namespace AppBundle\Command;

use AppBundle\Service\ToolsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


class FixTimetablesCommand extends Command
{


    /**
     * @var ToolsService
     */
    private $tools;

    public function __construct(ToolsService $tools)
    {
        $this->tools = $tools;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:fix-timetable');
        $this->addArgument("mosqueId", InputArgument::OPTIONAL, '', null);
        $this->addArgument("firstDayInMarch", InputArgument::OPTIONAL, '', 29);
        $this->addArgument("lastDayInOctober", InputArgument::OPTIONAL, '', 26);
        $this->addArgument("offsetHour", InputArgument::OPTIONAL, '', -1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mosqueId = $input->getArgument("mosqueId");
        $firstDayInMarch = $input->getArgument("firstDayInMarch");
        $lastDayInOctober = $input->getArgument("lastDayInOctober");
        $offsetHour = $input->getArgument("offsetHour");

        if (null === $mosqueId) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue without specified id ? ', false);

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln("Nothing to do, bye");
                return;
            }
        }

        $count = $this->tools->fixTimetable($firstDayInMarch, $lastDayInOctober, $offsetHour, $mosqueId);
        $output->writeln("Done, $count mosques timetable modified");
    }

}