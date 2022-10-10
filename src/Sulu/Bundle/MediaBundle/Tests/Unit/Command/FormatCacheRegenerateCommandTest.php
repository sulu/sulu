<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Command\FormatCacheRegenerateCommand;
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class FormatCacheRegenerateCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<FormatManagerInterface>
     */
    private $formatManager;

    public function setUp(): void
    {
        $this->formatManager = $this->prophesize(FormatManagerInterface::class);
    }

    public function testExecute(): void
    {
        $this->formatManager->returnImage(1, '50x50', 'test.svg')
            ->shouldBeCalled();

        $this->formatManager->returnImage(2, '200x', 'test.svg')
            ->shouldBeCalled();

        $this->formatManager->returnImage(3, '400x400-inset', '2020-test-test.svg')
            ->shouldBeCalled();

        $this->executeCommand(__DIR__ . '/../../Fixtures/regenerate-formats');
    }

    private function executeCommand(string $localFormatCachePath): void
    {
        $fileSystem = new Filesystem();

        $application = new Application();
        $command = new FormatCacheRegenerateCommand(
            $fileSystem,
            $this->formatManager->reveal(),
            $localFormatCachePath
        );
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }
}
