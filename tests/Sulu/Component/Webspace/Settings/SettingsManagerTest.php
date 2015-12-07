<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Settings;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

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
}
