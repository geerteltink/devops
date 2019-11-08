<?php

declare(strict_types=1);

namespace Xtreamwayz\DevOps\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use function sprintf;

class SyncUpstreamCommand extends Command
{
    public function __construct(
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Sync upstream branches')
            ->addOption(
                'origin',
                null,
                InputOption::VALUE_NONE,
                'Sync upstream branches to origin?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $pushToOrigin = $input->getOption('origin') ? true : false;

        try {
            // git fetch upstream
            $output->writeln('<info>Fetching upstream...</info>');
            (new Process(['git', 'fetch', 'upstream']))->mustRun();

            // sync master branch
            $this->syncBranch($output, 'master', $pushToOrigin);

            // check if develop branch exists
            // git ls-remote --exit-code --heads upstream develop
            $hasDevelopBranch = (new Process(['git', 'ls-remote', '--exit-code', '--heads', 'upstream', 'develop']))
                ->run();

            if ($hasDevelopBranch !== 0) {
                return;
            }

            // sync develop branch
            $this->syncBranch($output, 'develop', $pushToOrigin);
        } catch (ProcessFailedException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
        }
    }

    private function syncBranch(OutputInterface $output, string $branch, bool $pushToOrigin): void
    {
        // git checkout <branch>
        $output->writeln("<info>Checking out $branch...</info>");
        (new Process(['git', 'checkout', $branch]))->mustRun();

        // git rebase upstream/<branch>
        $output->writeln("<info>Rebasing onto $branch...</info>");
        (new Process(['git', 'rebase', "upstream/$branch"]))->mustRun();

        if ($pushToOrigin === true) {
            // git push origin master:master
            $output->writeln("<info>Updating $branch branch on remote origin...</info>");
            (new Process(['git', 'push', 'origin', "$branch:$branch"]))->mustRun();
        }
    }
}
