<?php

declare(strict_types=1);

namespace Iswai\DevOps\Git;

use Assert\Assertion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CheckoutPullRequestCommand extends Command
{
    public function __construct(
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Checkout pull request')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'The pull request type (hotfix|feature)'
            )
            ->addArgument(
                'pr',
                InputArgument::REQUIRED,
                'The pull request number'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $pr = (int) $input->getArgument('pr');
        $type = $input->getArgument('type');

        Assertion::inArray($type, ['hotfix', 'feature']);

        // check if develop branch exists
        // git ls-remote --exit-code --heads upstream develop
        $hasDevelopBranch = (new Process(['git', 'ls-remote', '--exit-code', '--heads', 'upstream', 'develop']))->run();

        $startPoint = 'master';
        if ($type === 'feature') {
            $startPoint = $hasDevelopBranch === 0 ? 'develop' : 'master';
        }
        $branch = "$type/$pr";

        $output->writeln("<info>Checking out PR #$pr into $branch from $startPoint...</info>");

        try {
            // git checkout -b <branch> [<start point>]
            (new Process(['git', 'checkout', '-b', $branch, $startPoint]))->mustRun();

            // git fetch upstream refs/pull/<pr>/head
            (new Process(['git', 'fetch', 'upstream', "refs/pull/$pr/head"]))->mustRun();

            // git merge FETCH_HEAD --no-ff -m "merge: pull request #<pr>"
            (new Process(['git', 'merge', 'FETCH_HEAD', '--no-ff', '-m', "merge: pull request #$pr"]))->mustRun();

            $output->writeln([
                '',
                sprintf('<comment>Run checks and commands</comment>'),
                sprintf('<comment> - $ composer check<comment>')
            ]);

            if ($type === 'hotfix') {
                $output->writeln([
                    sprintf('<comment> - $ keep-a-changelog entry:fixed --pr %d "fixes something to be Better."</comment>', $pr)
                ]);
            }

            if ($type === 'feature') {
                $output->writeln([
                    sprintf('<comment> - $ keep-a-changelog entry:added --pr %d "adds documentation."</comment>', $pr),
                    sprintf('<comment> - $ keep-a-changelog entry:changed --pr %d "changes something."</comment>', $pr),
                    sprintf('<comment> - $ keep-a-changelog entry:deprecated --pr %d "deprecated something to be removed in the next major release."</comment>', $pr),
                    sprintf('<comment> - $ keep-a-changelog entry:removed --pr %d "removes something."</comment>', $pr)
                ]);
            }

            $output->writeln([
                '',
                sprintf('<comment>Once ready merge the pull request</comment>'),
                sprintf('<comment> - $ devops pr:merge<comment>')
            ]);
        } catch (ProcessFailedException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
        }
    }
}
