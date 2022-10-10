<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Settings;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Session\SessionManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface as DeprecatedSessionManagerInterface;
use Sulu\Component\Webspace\Settings\SettingsManager;

class SettingsManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SettingsManager
     */
    private $settingsManager;

    /**
     * @var ObjectProphecy<SessionManagerInterface>
     */
    private $sessionManager;

    /**
     * @var ObjectProphecy<DeprecatedSessionManagerInterface>
     */
    private $deprecatedSessionManager;

    public function setUp(): void
    {
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->deprecatedSessionManager = $this->prophesize(DeprecatedSessionManagerInterface::class);

        $this->settingsManager = new SettingsManager(
            $this->sessionManager->reveal(),
            $this->deprecatedSessionManager->reveal()
        );
    }

    public function dataProvider()
    {
        $node = $this->prophesize(NodeInterface::class);

        return [
            ['sulu_io', 'test-1', ['property1' => 'test1', 'property2' => 'test2']],
            ['sulu_io', 'test-2', null],
            ['sulu_io', 'test-3', $node->reveal()],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testSave($webspaceKey, $key, $data): void
    {
        $this->deprecatedSessionManager->getWebspacePath($webspaceKey)->willReturn('/cmf/' . $webspaceKey);

        $this->sessionManager->setNodeProperty(
            '/cmf/' . $webspaceKey,
            'settings:' . $key,
            !($data instanceof NodeInterface) ? \json_encode($data) : $data
        )->shouldBeCalled();

        $this->sessionManager->flush()->shouldBeCalled();

        $this->settingsManager->save($webspaceKey, $key, $data);
    }

    public function removeDataProvider()
    {
        return [
            ['sulu_io', 'test-1'],
        ];
    }

    /**
     * @dataProvider removeDataProvider
     */
    public function testRemove($webspaceKey, $key): void
    {
        $this->deprecatedSessionManager->getWebspacePath($webspaceKey)->willReturn('/cmf/' . $webspaceKey);

        $this->sessionManager->setNodeProperty('/cmf/' . $webspaceKey, 'settings:' . $key, null)->shouldBeCalled();

        $this->sessionManager->flush()->shouldBeCalled();

        $this->settingsManager->remove($webspaceKey, $key);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testLoad($webspaceKey, $key, $data): void
    {
        $node = $this->prophesize(NodeInterface::class);

        $this->deprecatedSessionManager->getWebspaceNode($webspaceKey)->willReturn($node->reveal());

        $node->getPropertyValueWithDefault('settings:' . $key, \json_encode(null))
            ->shouldBeCalledTimes(1)
            ->willReturn(!($data instanceof NodeInterface) ? \json_encode($data) : $data);

        $result = $this->settingsManager->load($webspaceKey, $key);

        $this->assertEquals($data, $result);
    }

    public function loadStringDataProvider()
    {
        return [
            ['sulu_io', 'test-1', '123-123-123', true],
            ['sulu_io', 'test-1', '123-123-123', false],
        ];
    }

    /**
     * @dataProvider loadStringDataProvider
     */
    public function testLoadString($webspaceKey, $key, $data, $exists): void
    {
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);

        $this->deprecatedSessionManager->getWebspaceNode($webspaceKey)->willReturn($node->reveal());

        $node->hasProperty('settings:' . $key)->willReturn($exists);
        $node->getProperty('settings:' . $key)
            ->shouldBeCalledTimes($exists ? 1 : 0)
            ->willReturn($exists ? $property->reveal() : null);

        $property->getString()->willReturn($data);

        $result = $this->settingsManager->loadString($webspaceKey, $key);

        $this->assertEquals($exists ? $data : null, $result);
    }

    public function testLoadByWildcard(): void
    {
        $referencedNode = $this->prophesize(NodeInterface::class);

        $node = $this->prophesize(NodeInterface::class);
        $property1 = $this->prophesize(PropertyInterface::class);
        $property1->getName()->willReturn('settings:test-1');
        $property1->getValue()->willReturn($referencedNode->reveal());
        $property2 = $this->prophesize(PropertyInterface::class);
        $property2->getName()->willReturn('settings:test-2');
        $property2->getValue()->willReturn(\json_encode(['test1' => 'test1']));

        $this->deprecatedSessionManager->getWebspaceNode('sulu_io')->willReturn($node->reveal());

        $node->getProperties('settings:test-*')->willReturn([$property1->reveal(), $property2->reveal()]);

        $settings = $this->settingsManager->loadByWildcard('sulu_io', 'test-*');

        $this->assertCount(2, $settings);
        $this->assertEquals($referencedNode->reveal(), $settings['test-1']);
        $this->assertEquals(['test1' => 'test1'], $settings['test-2']);
    }
}
