<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Snippet;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManager;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetNotFoundException;
use Sulu\Bundle\SnippetBundle\Snippet\WrongSnippetTypeException;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\Webspace\Settings\SettingsManagerInterface;

class DefaultSnippetManagerTest extends \PHPUnit_Framework_TestCase
{
    public function saveDataProvider()
    {
        return [
            ['sulu_io', 'de', 'test', '123-123-123'],
            ['sulu_io', 'de', 'test', '123-123-123', false],
            ['sulu_io', 'de', 'test', '123-123-123', true, false],
            ['sulu_io', 'de', 'test', '123-123-123', false, false],
        ];
    }

    /**
     * @dataProvider saveDataProvider
     */
    public function testSave($webspaceKey, $locale, $type, $uuid, $exists = true, $sameType = true)
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(), $documentManager->reveal(), $registry->reveal()
        );

        $document = null;
        if ($exists) {
            if (!$sameType) {
                $this->setExpectedException(WrongSnippetTypeException::class);
            }

            $document = $this->prophesize(SnippetDocument::class);
            $document->getStructureType()->willReturn($sameType ? $type : strrev($type));

            $document = $document->reveal();

            $node = $this->prophesize(NodeInterface::class);
            $registry->getNodeForDocument($document)->willReturn($node->reveal());
        } else {
            $this->setExpectedException(SnippetNotFoundException::class);
        }

        $documentManager->find($uuid, $locale, Argument::any())->shouldBeCalledTimes(1)->willReturn($document);

        $settingsManager->save($webspaceKey, 'snippets-' . $type, Argument::type(NodeInterface::class))
            ->shouldBeCalledTimes($exists && $sameType ? 1 : 0);

        $result = $manager->save($webspaceKey, $type, $uuid, $locale);

        $this->assertEquals($result, $document);
    }

    public function testRemove()
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(), $documentManager->reveal(), $registry->reveal()
        );

        $settingsManager->remove('sulu_io', 'snippets-test')->shouldBeCalledTimes(1);

        $manager->remove('sulu_io', 'test');
    }

    public function loadDataProvider()
    {
        return [
            ['sulu_io', 'de', 'test', '123-123-123'],
            ['sulu_io', 'de', 'test', '123-123-123', false],
            ['sulu_io', 'de', 'test', '123-123-123', true, false],
            ['sulu_io', 'de', 'test', '123-123-123', false, false],
        ];
    }

    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad($webspaceKey, $locale, $type, $uuid, $exists = true, $sameType = true)
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(), $documentManager->reveal(), $registry->reveal()
        );

        $document = null;
        $node = null;
        if ($exists) {
            if (!$sameType) {
                $this->setExpectedException(WrongSnippetTypeException::class);
            }

            $document = $this->prophesize(SnippetDocument::class);
            $document->getStructureType()->willReturn($sameType ? $type : strrev($type));

            $document = $document->reveal();
            $node = $this->prophesize(NodeInterface::class);
            $node->getIdentifier()->willReturn($uuid);
            $node = $node->reveal();

            $registry->getNodeForDocument($document)->willReturn($node);
        }

        $documentManager->find($uuid, $locale, Argument::any())
            ->shouldBeCalledTimes($exists ? 1 : 0)->willReturn($document);

        $settingsManager->load($webspaceKey, 'snippets-' . $type)
            ->shouldBeCalledTimes(1)->willReturn($exists ? $node : null);

        $result = $manager->load($webspaceKey, $type, $locale);

        $this->assertEquals($result, $document);
    }

    public function testLoadIdentifier()
    {
        $settingsManager = $this->prophesize(SettingsManagerInterface::class);
        $documentManager = $this->prophesize(DocumentManagerInterface::class);
        $registry = $this->prophesize(DocumentRegistry::class);

        $manager = new DefaultSnippetManager(
            $settingsManager->reveal(), $documentManager->reveal(), $registry->reveal()
        );

        $settingsManager->loadString('sulu_io', 'snippets-test')
            ->shouldBeCalledTimes(1)->willReturn('123-123-123');

        $uuid = $manager->loadIdentifier('sulu_io', 'test');

        $this->assertEquals('123-123-123', $uuid);
    }
}
