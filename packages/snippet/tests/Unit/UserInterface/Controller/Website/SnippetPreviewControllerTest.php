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

namespace Sulu\Snippet\Tests\Unit\UserInterface\Controller\Website;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Snippet\UserInterface\Controller\Website\SnippetPreviewController;
use Symfony\Component\HttpFoundation\Request;

class SnippetPreviewControllerTest extends TestCase
{
    /**
     * Preview::removeContent() splits the document on the replacer and wants what sits after the
     * second one, so a full render has to write it on both sides of the content.
     */
    public function testFullRenderWritesTheContentReplacerTwice(): void
    {
        $request = new Request([], [], ['templateKey' => 'header-menu', 'partial' => false]);

        $content = (new SnippetPreviewController())->indexAction($request)->getContent();

        $this->assertIsString($content);
        $this->assertSame(2, \substr_count($content, Preview::CONTENT_REPLACER));
        $this->assertStringContainsString('header-menu', $content);
    }

    public function testPartialRenderWritesTheNoticeAlone(): void
    {
        $request = new Request([], [], ['templateKey' => 'header-menu', 'partial' => true]);

        $content = (new SnippetPreviewController())->indexAction($request)->getContent();

        $this->assertIsString($content);
        $this->assertStringNotContainsString(Preview::CONTENT_REPLACER, $content);
        $this->assertStringContainsString('header-menu', $content);
    }

    public function testAMissingTemplateKeyDoesNotBreakTheNotice(): void
    {
        $content = (new SnippetPreviewController())->indexAction(new Request())->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('unknown', $content);
    }
}
