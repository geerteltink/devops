<?php

declare(strict_types=1);

namespace Xtreamwayz\DevOps\Git;

use Assert\Assertion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use function explode;
use function sprintf;
use function trim;

class MergePullRequestCommand extends Command
{
    public function __construct(
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Merge pull request');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        try {
            // git rev-parse --abbrev-ref HEAD
            $output->writeln('<info>Detecting pr and type...</info>');
            $prBranch = trim((new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD']))->mustRun()->getOutput());

            // detect pr from branch name
            // detect hotfix/feature from branch name
            [$type, $pr] = explode('/', $prBranch, 2);

            Assertion::inArray($type, ['hotfix', 'feature']);
            Assertion::numeric((int) $pr);

            $pr = (int) $pr;

            $output->writeln("<info>Detected $type #$pr</info>");

            // check if develop branch exists
            // git ls-remote --exit-code --heads upstream develop
            $hasDevelopBranch = (new Process([
                'git',
                'ls-remote',
                '--exit-code',
                '--heads',
                'upstream',
                'develop',
            ]))->run() === 0;
            if ($hasDevelopBranch) {
                $output->writeln("<info>Develop branch detected</info>");
            }

            if ($type === 'hotfix') {
                $this->merge($output, $prBranch, $pr, $type, 'master', 'Close');
                if ($hasDevelopBranch) {
                    $this->merge($output, $prBranch, $pr, $type, 'develop', 'Forward port');
                }
            }

            if ($type === 'feature') {
                $targetBranch = $hasDevelopBranch ? 'develop' : 'master';
                $this->merge($output, $prBranch, $pr, $type, $targetBranch, 'Close');
            }

            // git push upstream
            $output->writeln('<info>Push changes to upstream master</info>');
            (new Process(['git', 'push', 'upstream', 'master:master']))->mustRun();
            if ($hasDevelopBranch) {
                $output->writeln('<info>Push changes to upstream develop</info>');
                (new Process(['git', 'push', 'upstream', 'develop:develop']))->mustRun();
            }

            // git checkout master
            $output->writeln('<info>Checking out master...</info>');
            (new Process(['git', 'checkout', 'master']))->mustRun();
            // git branch -d hotfix/1
            $output->writeln('<info>Removing pr branch...</info>');
            (new Process(['git', 'branch', '-D', $prBranch]))->mustRun();
        } catch (ProcessFailedException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
        }
    }

    private function merge(
        OutputInterface $output,
        string $prBranch,
        int $pr,
        string $type,
        string $targetBranch,
        string $action
    ): void {
        // git checkout develop
        $output->writeln("<info>Checking out $targetBranch...</info>");
        (new Process(['git', 'checkout', $targetBranch]))->mustRun();

        // git merge --no-ff hotfix/1 -m "merge: hotfix #1" -m "Forward port #1"
        $output->writeln("<info>Merging $prBranch into $targetBranch...</info>");
        (new Process([
            'git',
            'merge',
            '--no-ff',
            $prBranch,
            '-m',
            "merge: $type #$pr",
            '-m',
            "$action #$pr",
        ]))->mustRun();
    }
}
