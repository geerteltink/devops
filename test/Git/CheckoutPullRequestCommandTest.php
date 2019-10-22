<?php

declare(strict_types=1);

namespace IswaiTest\DevOps\Git;

use Iswai\DevOps\Git\CheckoutPullRequestCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class CheckoutPullRequestCommandTest extends TestCase
{
    public function testCommandNeedsInput(): void
    {
        $command       = new CheckoutPullRequestCommand();
        $commandTester = new CommandTester($command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "type, pr").');
        $commandTester->execute([]);
    }
}
