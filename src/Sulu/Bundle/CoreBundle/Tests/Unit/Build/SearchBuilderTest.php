<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Unit\Build;

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use Massive\Bundle\BuildBundle\Build\BuilderContext;
use Massive\Bundle\BuildBundle\Console\MassiveOutputFormatter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CoreBundle\Build\SearchBuilder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class SearchBuilderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EngineInterface>
     */
    private $engine;

    private BufferedOutput $output;

    /**
     * @var string[]
     */
    private array $commands = [];

    protected function setUp(): void
    {
        $this->engine = $this->prophesize(EngineInterface::class);
        $this->output = new BufferedOutput();
        $this->output->setFormatter(new MassiveOutputFormatter(true));
        $this->commands = [];
    }

    public function testBuildCreatesMissingIndex(): void
    {
        $this->engine->existIndex('page')->willReturn(false);

        $this->createBuilder(false)->build();

        $this->assertSame(['cmsig:seal:index-create --index=page'], $this->commands);
    }

    public function testBuildSkipsExistingIndex(): void
    {
        $this->engine->existIndex('page')->willReturn(true);

        $this->createBuilder(false)->build();

        $this->assertSame([], $this->commands);
        $this->assertStringContainsString('Found existing search index page, skipping', $this->output->fetch());
    }

    public function testBuildWithDestroyRecreatesExistingIndex(): void
    {
        $this->engine->existIndex('page')->willReturn(true);

        $this->createBuilder(true)->build();

        $this->assertSame([
            'cmsig:seal:index-drop default page --force',
            'cmsig:seal:index-create --index=page',
        ], $this->commands);
    }

    public function testBuildWithDestroyDoesNotDropMissingIndex(): void
    {
        $this->engine->existIndex('page')->willReturn(false);

        $this->createBuilder(true)->build();

        $this->assertSame(['cmsig:seal:index-create --index=page'], $this->commands);
    }

    private function createBuilder(bool $destroy): SearchBuilder
    {
        $builder = new SearchBuilder(
            $this->engine->reveal(),
            new Schema(['page' => new Index('page', [])]),
        );
        $input = new ArrayInput(
            ['--destroy' => $destroy],
            new InputDefinition([new InputOption('destroy', null, InputOption::VALUE_NONE)]),
        );
        $builder->setContext(new BuilderContext($input, $this->output, $this->createApplication()));

        return $builder;
    }

    private function createApplication(): Application
    {
        $application = new Application();
        $application->setAutoExit(false);

        $create = new Command('cmsig:seal:index-create');
        $create->addOption('index', null, InputOption::VALUE_REQUIRED);
        $create->setCode(function(InputInterface $input, OutputInterface $output): int {
            /** @var string $index */
            $index = $input->getOption('index');
            $this->commands[] = 'cmsig:seal:index-create --index=' . $index;

            return Command::SUCCESS;
        });

        $drop = new Command('cmsig:seal:index-drop');
        $drop->addArgument('engine', InputArgument::OPTIONAL);
        $drop->addArgument('index', InputArgument::OPTIONAL);
        $drop->addOption('force', 'f', InputOption::VALUE_NONE);
        $drop->setCode(function(InputInterface $input, OutputInterface $output): int {
            /** @var string $engine */
            $engine = $input->getArgument('engine');
            /** @var string $index */
            $index = $input->getArgument('index');
            $this->commands[] = \sprintf(
                'cmsig:seal:index-drop %s %s%s',
                $engine,
                $index,
                $input->getOption('force') ? ' --force' : '',
            );

            return Command::SUCCESS;
        });

        $application->setCommandLoader(new FactoryCommandLoader([
            'cmsig:seal:index-create' => static fn (): Command => $create,
            'cmsig:seal:index-drop' => static fn (): Command => $drop,
        ]));

        return $application;
    }
}
