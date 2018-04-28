<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Twig;

use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Bundle\SnippetBundle\Twig\SnippetTypeTwigExtension;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class SnippetTypeTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SnippetRepository
     */
    private $snippetRepository;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function testLoadByType()
    {
        $this->snippetRepository = $this->prophesize(SnippetRepository::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $twigExtension = new SnippetTypeTwigExtension(
            $this->snippetRepository->reveal(),
            $this->requestAnalyzer->reveal()
        );

        $snippets = $twigExtension->loadByType('car', 'de');

//        $this->assertEquals(
//            ['title' => 'test-title'],
//            $snippets[0]['title']
//        );
    }

    public function testLoadByTypeNotExist()
    {
        $this->snippetRepository = $this->prophesize(SnippetRepository::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $twigExtension = new SnippetTypeTwigExtension(
            $this->snippetRepository->reveal(),
            $this->requestAnalyzer->reveal()
        );

        $this->assertEquals([], $twigExtension->loadByType('test', 'de'));
    }

    public function testLoadByTypeOtherLocale()
    {
        $this->snippetRepository = $this->prophesize(SnippetRepository::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $twigExtension = new SnippetTypeTwigExtension(
            $this->snippetRepository->reveal(),
            $this->requestAnalyzer->reveal()
        );

        $snippets = $twigExtension->loadByType('car', 'en');

//        $this->assertEquals(
//            ['title' => 'Test Snippet EN'],
//            $snippets[0]['title']
//        );
    }
}
