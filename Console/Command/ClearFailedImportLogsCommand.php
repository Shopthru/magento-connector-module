<?php

namespace Shopthru\Connector\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Shopthru\Connector\Model\Service\ClearFailedImportLogs;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearFailedImportLogsCommand extends Command
{
    /**
     * @param ClearFailedImportLogs $clearFailedImportLogs
     * @param State $state
     */
    public function __construct(
        private readonly ClearFailedImportLogs $clearFailedImportLogs,
        private readonly State $state
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('shopthru:clear-failed-import-logs');
        $this->setDescription('Clears all import logs with failed status');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Set area code to avoid area code not set errors
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

            $output->writeln('<info>Starting to clear failed import logs...</info>');

            // Execute the service to clear failed logs
            $deletedCount = $this->clearFailedImportLogs->execute();

            if ($deletedCount === 0) {
                $output->writeln('<info>No failed import logs found.</info>');
            } else {
                $output->writeln(
                    sprintf(
                        '<info>Successfully deleted %d failed import log(s).</info>',
                        $deletedCount
                    )
                );
            }

            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }
}
