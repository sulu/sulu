<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Search\Tests\Unit\Application\MessageHandler;

use CmsIg\Seal\Adapter\Memory\MemoryAdapter;
use CmsIg\Seal\Engine;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use PHPUnit\Framework\TestCase;
use Sulu\Search\Application\MessageHandler\ReindexMessageHandler;
use Webmozart\Assert\Assert;

class ReindexMessageHandlerTest extends TestCase
{
    private Engine $engine;

    /**
     * @var \ArrayObject<int|string, ReindexProviderInterface>
     */
    private \ArrayObject $reindexProviders;

    private ReindexMessageHandler $reindexMessageHandler;

    public function setUp(): void
    {
        $adminIndex = require \dirname(__DIR__, 4) . '/config/schemas/admin.php';
        Assert::isInstanceOf($adminIndex, Index::class);

        $this->engine = new Engine(
            new MemoryAdapter(),
            new Schema([
                'admin' => $adminIndex,
            ]),
        );
        $this->engine->createSchema();
        $this->reindexProviders = new \ArrayObject();

        $this->reindexMessageHandler = new ReindexMessageHandler(
            $this->engine,
            $this->reindexProviders,
        );
    }

    public function testInvoke(): void
    {
        $message = $this->createMessage('admin', [
            'example::f6a4bfa6-6737-4ffb-b03a-f9fe45352537::de',
            'example::f85a9552-c33a-4707-b1c2-8b79b32edabf::de',
        ]);

        $this->reindexProviders->append($this->createMinimalReindexProviderInterface());

        $this->reindexMessageHandler->__invoke($message);

        $this->assertSame(2, $this->engine->countDocuments('admin'));
    }

    /**
     * @param array<string> $identifiers
     */
    private function createMessage(string $index, array $identifiers): ReindexConfig
    {
        return ReindexConfig::create()
            ->withIndex($index)
            ->withIdentifiers($identifiers);
    }

    private function createMinimalReindexProviderInterface(): ReindexProviderInterface
    {
        return new class() implements ReindexProviderInterface {
            public function total(): ?int
            {
                return null;
            }

            public function provide(ReindexConfig $reindexConfig): \Generator
            {
                foreach ($reindexConfig->getIdentifiers() as $identifier) {
                    yield [
                        'id' => $identifier,
                        'title' => 'Test ' . $identifier,
                    ];
                }
            }

            public static function getIndex(): string
            {
                return 'admin';
            }
        };
    }
}
