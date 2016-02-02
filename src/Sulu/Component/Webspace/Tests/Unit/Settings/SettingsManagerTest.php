<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Settings;

use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Settings\SettingsManager;

class SettingsManagerTest extends \PHPUnit_Framework_TestCase
{
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
    public function testSave($webspaceKey, $key, $data)
    {
        $sessionManager = $this->prophesize(SessionManagerInterface::class);
        $node = $this->prophesize(NodeInterface::class);
        $session = $this->prophesize(SessionInterface::class);

        $sessionManager->getWebspaceNode($webspaceKey)->willReturn($node->reveal());
        $sessionManager->getSession()->willReturn($session->reveal());

        $node->setProperty('settings:' . $key, (!($data instanceof NodeInterface) ? json_encode($data) : $data))
            ->shouldBeCalledTimes(1);

        $session->save()->shouldBeCalledTimes(1);

        $manager = new SettingsManager($sessionManager->reveal());

        $manager->save($webspaceKey, $key, $data);
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
    public function testRemove($webspaceKey, $key)
    {
        $sessionManager = $this->prophesize(SessionManagerInterface::class);
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);
        $session = $this->prophesize(SessionInterface::class);

        $sessionManager->getWebspaceNode($webspaceKey)->willReturn($node->reveal());
        $sessionManager->getSession()->willReturn($session->reveal());

        $node->hasProperty('settings:' . $key)->shouldBeCalledTimes(1)->willReturn(true);
        $node->getProperty('settings:' . $key)->shouldBeCalledTimes(1)->willReturn($property->reveal());
        $property->remove()->shouldBeCalledTimes(1);

        $session->save()->shouldBeCalledTimes(1);

        $manager = new SettingsManager($sessionManager->reveal());

        $manager->remove($webspaceKey, $key);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testLoad($webspaceKey, $key, $data)
    {
        $sessionManager = $this->prophesize(SessionManagerInterface::class);
        $node = $this->prophesize(NodeInterface::class);

        $sessionManager->getWebspaceNode($webspaceKey)->willReturn($node->reveal());

        $node->getPropertyValueWithDefault('settings:' . $key, json_encode(null))
            ->shouldBeCalledTimes(1)
            ->willReturn((!($data instanceof NodeInterface) ? json_encode($data) : $data));

        $manager = new SettingsManager($sessionManager->reveal());

        $result = $manager->load($webspaceKey, $key);

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
    public function testLoadString($webspaceKey, $key, $data, $exists)
    {
        $sessionManager = $this->prophesize(SessionManagerInterface::class);
        $node = $this->prophesize(NodeInterface::class);
        $property = $this->prophesize(PropertyInterface::class);

        $sessionManager->getWebspaceNode($webspaceKey)->willReturn($node->reveal());

        $node->hasProperty('settings:' . $key)->willReturn($exists);
        $node->getProperty('settings:' . $key)
            ->shouldBeCalledTimes($exists ? 1 : 0)
            ->willReturn($exists ? $property->reveal() : null);

        $property->getString()->willReturn($data);

        $manager = new SettingsManager($sessionManager->reveal());

        $result = $manager->loadString($webspaceKey, $key);

        $this->assertEquals($exists ? $data : null, $result);
    }

    public function testLoadByWildcard()
    {
        $referencedNode = $this->prophesize(NodeInterface::class);

        $sessionManager = $this->prophesize(SessionManagerInterface::class);
        $node = $this->prophesize(NodeInterface::class);
        $property1 = $this->prophesize(PropertyInterface::class);
        $property1->getName()->willReturn('settings:test-1');
        $property1->getValue()->willReturn($referencedNode->reveal());
        $property2 = $this->prophesize(PropertyInterface::class);
        $property2->getName()->willReturn('settings:test-2');
        $property2->getValue()->willReturn(json_encode(['test1' => 'test1']));

        $sessionManager->getWebspaceNode('sulu_io')->willReturn($node->reveal());

        $node->getProperties('settings:test-*')->willReturn([$property1->reveal(), $property2->reveal()]);

        $manager = new SettingsManager($sessionManager->reveal());

        $settings = $manager->loadByWildcard('sulu_io', 'test-*');

        $this->assertCount(2, $settings);
        $this->assertEquals($referencedNode->reveal(), $settings['test-1']);
        $this->assertEquals(['test1' => 'test1'], $settings['test-2']);
    }
}
