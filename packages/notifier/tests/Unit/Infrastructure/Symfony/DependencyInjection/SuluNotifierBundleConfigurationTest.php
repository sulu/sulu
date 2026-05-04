<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Notifier\Tests\Unit\Infrastructure\Symfony\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Notifier\Infrastructure\Symfony\DependencyInjection\Configuration;

class SuluNotifierBundleConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    public function testEmptyConfigIsValid(): void
    {
        $this->assertConfigurationIsValid([[]]);
    }

    public function testValidChannelMappingProcesses(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[
                'channels' => [
                    'chat/slack' => [DomainEvent::class],
                ],
            ]],
            [
                'channels' => [
                    'chat/slack' => [DomainEvent::class],
                ],
            ],
        );
    }

    public function testNonExistentClassIsRejected(): void
    {
        $this->assertConfigurationIsInvalid(
            [[
                'channels' => [
                    'chat/slack' => ['Sulu\Does\Not\Exist'],
                ],
            ]],
            'does not exist or is not autoloadable',
        );
    }
}
