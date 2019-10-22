<?php

declare(strict_types=1);

namespace Iswai\DevOps\Git;

use Assert\Assertion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use function array_filter;
use function explode;
use function sprintf;
use function strpos;

class SetUpstreamCommand extends Command
{
    public function __construct(
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Set upstream url')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'The url to the original repository'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $url = $input->getArgument('url');

        Assertion::startsWith($url, 'git@github.com:');

        $output->writeln('<info>Setting upstream</info>');

        try {
            $getRemotes = new Process(['git', 'remote', '-v']);
            $remotes    = array_filter(explode("\n", $getRemotes->mustRun()->getOutput()));

            $hasUpstream = false;
            foreach ($remotes as $remote) {
                if (strpos($remote, 'upstream') === 0) {
                    $hasUpstream = true;
                }
            }

            if (! $hasUpstream) {
                // git remote add upstream <url>
                $setUpstream = new Process(['git', 'remote', 'add', 'upstream', $url]);
            } else {
                // git remote set-url upstream <url>
                $setUpstream = new Process(['git', 'remote', 'set-url', 'upstream', $url]);
            }
            $setUpstream->mustRun();

            // Track upstream
            // git config branch.master.remote upstream
            $trackUpstream = new Process(['git', 'config', 'branch.master.remote', 'upstream']);
            $trackUpstream->mustRun();
        } catch (ProcessFailedException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
        }

        $output->writeln(sprintf('<info>Upstream set to %s</info>', $url));
    }
}
