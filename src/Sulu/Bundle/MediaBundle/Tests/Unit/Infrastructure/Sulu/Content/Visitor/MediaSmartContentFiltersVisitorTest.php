<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Infrastructure\Sulu\Content\Visitor;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\Visitor\MediaSmartContentFiltersVisitor;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class MediaSmartContentFiltersVisitorTest extends TestCase
{
    private RequestStack $requestStack;
    private RequestAnalyzerInterface $requestAnalyzer;
    private MediaSmartContentFiltersVisitor $mediaSmartContentFiltersVisitor;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->requestAnalyzer = new RequestAnalyzer($this->requestStack, []);

        $this->mediaSmartContentFiltersVisitor = new MediaSmartContentFiltersVisitor(
            $this->requestAnalyzer,
            $this->requestStack,
        );
    }

    public function testVisitEmpty(): void
    {
        $this->assertSame([], $this->mediaSmartContentFiltersVisitor->visit([], [], []));
    }

    public function testVisitEmptyWithRequest(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $this->assertSame([], $this->mediaSmartContentFiltersVisitor->visit([], [], []));
    }

    public function testVisitKeepFilters(): void
    {
        $this->assertSame(['keep' => 'this'], $this->mediaSmartContentFiltersVisitor->visit([], ['keep' => 'this'], []));
    }

    public function testMimeType(): void
    {
        $parameters['mimetype_parameter'] = 'mime_type';
        $request = new Request();
        $request->query->set('mime_type', 'application/pdf');
        $this->requestStack->push($request);

        $this->assertSame(['mimetype' => 'application/pdf'], $this->mediaSmartContentFiltersVisitor->visit([], [], $parameters));
    }

    public function testType(): void
    {
        $parameters['type_parameter'] = 'media_type';
        $request = new Request();
        $request->query->set('media_type', 'image');
        $this->requestStack->push($request);

        $this->assertSame(['type' => 'image'], $this->mediaSmartContentFiltersVisitor->visit([], [], $parameters));
    }

    public function testWebspaceKey(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $webspace = new Webspace();
        $webspace->setKey('website');
        $suluAttribute = new RequestAttributes([
            'webspace' => $webspace,
        ]);
        $request->attributes->set(RequestAnalyzer::SULU_ATTRIBUTE, $suluAttribute);

        $this->assertSame(['webspaceKey' => 'website'], $this->mediaSmartContentFiltersVisitor->visit([], [], []));
    }

    public function testIgnoreWebspacesStringTrueSkipsWebspaceKey(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $webspace = new Webspace();
        $webspace->setKey('website');
        $suluAttribute = new RequestAttributes([
            'webspace' => $webspace,
        ]);
        $request->attributes->set(RequestAnalyzer::SULU_ATTRIBUTE, $suluAttribute);

        $result = $this->mediaSmartContentFiltersVisitor->visit([], [], ['ignoreWebspaces' => 'true']);

        $this->assertArrayNotHasKey('webspaceKey', $result);
    }

    public function testIgnoreWebspacesBoolTrueSkipsWebspaceKey(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $webspace = new Webspace();
        $webspace->setKey('website');
        $suluAttribute = new RequestAttributes([
            'webspace' => $webspace,
        ]);
        $request->attributes->set(RequestAnalyzer::SULU_ATTRIBUTE, $suluAttribute);

        $result = $this->mediaSmartContentFiltersVisitor->visit([], [], ['ignoreWebspaces' => true]);

        $this->assertArrayNotHasKey('webspaceKey', $result);
    }

    public function testIgnoreWebspacesStringFalseAddsWebspaceKey(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $webspace = new Webspace();
        $webspace->setKey('website');
        $suluAttribute = new RequestAttributes([
            'webspace' => $webspace,
        ]);
        $request->attributes->set(RequestAnalyzer::SULU_ATTRIBUTE, $suluAttribute);

        $result = $this->mediaSmartContentFiltersVisitor->visit([], [], ['ignoreWebspaces' => 'false']);

        $this->assertArrayHasKey('webspaceKey', $result);
        $this->assertSame('website', $result['webspaceKey']);
    }
}
