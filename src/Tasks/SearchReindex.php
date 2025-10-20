<?php

namespace SilverStripe\Forager\Tasks;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Forager\Interfaces\BatchDocumentInterface;
use SilverStripe\Forager\Interfaces\IndexingInterface;
use SilverStripe\Forager\Jobs\ReindexJob;
use SilverStripe\Forager\Service\IndexConfiguration;
use SilverStripe\Forager\Service\SyncJobRunner;
use SilverStripe\Forager\Service\Traits\BatchProcessorAware;
use SilverStripe\Forager\Service\Traits\ConfigurationAware;
use SilverStripe\Forager\Service\Traits\ServiceAware;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class SearchReindex extends BuildTask
{
    use ServiceAware;
    use ConfigurationAware;
    use BatchProcessorAware;

    protected string $title = 'Search Service Reindex'; // phpcs:ignore SlevomatCodingStandard.TypeHints
    protected static string $description = 'Search Service Reindex'; // phpcs:ignore SlevomatCodingStandard.TypeHints
    private static $segment = 'SearchReindex'; // phpcs:ignore SlevomatCodingStandard.TypeHints

    public function __construct(
        IndexingInterface $searchService,
        IndexConfiguration $config,
        BatchDocumentInterface $batchProcessor
    ) {
        parent::__construct();
        $this->setIndexService($searchService);
        $this->setConfiguration($config);
        $this->setBatchProcessor($batchProcessor);
    }

    protected function configure(): void
    {
        $this
            ->addOption('onlyClass', null, InputOption::VALUE_REQUIRED, 'Only reindex this fully-qualified class name')
            ->addOption('onlyIndex', null, InputOption::VALUE_REQUIRED, 'Only reindex this index key (e.g. "main")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Environment::increaseMemoryLimitTo();
        Environment::increaseTimeLimitTo();

        $indexConfiguration = IndexConfiguration::singleton();

        $onlyClass = $input->getOption('onlyClass');
        $onlyIndex = $input->getOption('onlyIndex');

        if ($onlyIndex) {
            $indexConfiguration->setOnlyIndexes([$onlyIndex]);
        }

        foreach (array_keys($indexConfiguration->getIndexes()) as $index) {
            $classes = $onlyClass ? [$onlyClass] : $indexConfiguration->getClassesForIndex($index);

            foreach ($classes as $class) {
                $batchSize = $indexConfiguration->getLowestBatchSizeForClass($class, $index);
                $job = ReindexJob::create([$class], [$index], $batchSize);

                if ($this->getConfiguration()->shouldUseSyncJobs()) {
                    SyncJobRunner::singleton()->runJob($job, false);
                } else {
                    QueuedJobService::singleton()->queueJob($job);
                }
            }
        }

        return Command::SUCCESS;
    }
}
