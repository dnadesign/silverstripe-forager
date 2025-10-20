<?php

namespace SilverStripe\Forager\Tasks;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Forager\Interfaces\BatchDocumentInterface;
use SilverStripe\Forager\Interfaces\IndexingInterface;
use SilverStripe\Forager\Jobs\ClearIndexJob;
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

class SearchClearIndex extends BuildTask
{
    use ServiceAware;
    use ConfigurationAware;
    use BatchProcessorAware;

    protected string $title = 'Search Service Clear Index'; // phpcs:ignore SlevomatCodingStandard.TypeHints
    protected static string $description = 'Search Service Clear Index'; // phpcs:ignore SlevomatCodingStandard.TypeHints
    private static $segment = 'SearchClearIndex'; // phpcs:ignore SlevomatCodingStandard.TypeHints

    private ?BatchDocumentInterface $batchProcessor = null;

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
        // keep title/description as-is; add an --index option (replacing $request->getVar('index'))
        $this->addOption('index', null, InputOption::VALUE_REQUIRED, 'Target index to clear (e.g. "main")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Environment::increaseMemoryLimitTo();
        Environment::increaseTimeLimitTo();

        $targetIndex = $input->getOption('index');

        if (!$targetIndex) {
            $output->writeln(
                '<error>Must specify an index via --index=main (or similar).</error>'
            );
            return Command::FAILURE;
        }

        $job = ClearIndexJob::create($targetIndex);

        if ($this->getConfiguration()->shouldUseSyncJobs()) {
            SyncJobRunner::singleton()->runJob($job, false);
            $output->writeln(sprintf('<info>Cleared index "%s" (sync)</info>', $targetIndex));
        } else {
            QueuedJobService::singleton()->queueJob($job);
            $output->writeln(sprintf('<info>Queued clear for index "%s"</info>', $targetIndex));
        }

        return Command::SUCCESS;
    }
}
