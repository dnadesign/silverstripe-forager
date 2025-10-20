<?php

namespace SilverStripe\Forager\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\Forager\Service\Traits\ServiceAware;
use Symfony\Component\Console\Output\OutputInterface;
use SilverStripe\Forager\Interfaces\IndexingInterface;
use SilverStripe\Forager\Exception\IndexingServiceException;

/**
 * Syncs index settings to a search service.
 *
 * Note this runs on dev/build automatically but is provided separately for uses where dev/build is slow (e.g 100,000+
 * record tables)
 */
class SearchConfigure extends BuildTask
{

    use ServiceAware;

    protected string $title = 'Search Service Configure'; // phpcs:ignore SlevomatCodingStandard.TypeHints

    protected static string $description = 'Sync search index configuration'; // phpcs:ignore SlevomatCodingStandard.TypeHints

    private static $segment = 'SearchConfigure'; // phpcs:ignore SlevomatCodingStandard.TypeHints

    public function __construct(IndexingInterface $searchService)
    {
        parent::__construct();

        $this->setIndexService($searchService);
    }

    /**
     * @param HTTPRequest $request
     * @throws IndexingServiceException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int // phpcs:ignore SlevomatCodingStandard.TypeHints
    {
        $this->getIndexService()->configure();

        echo 'Done.';

        return Command::SUCCESS;
    }

}
