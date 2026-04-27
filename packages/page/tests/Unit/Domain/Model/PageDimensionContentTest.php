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

namespace Sulu\Page\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Route\Domain\Model\Route;

class PageDimensionContentTest extends TestCase
{
    use ProphecyTrait;

    private PageDimensionContent $pageDimensionContent;
    /** @var ObjectProphecy<PageInterface> */
    private ObjectProphecy $page;

    protected function setUp(): void
    {
        $this->page = $this->prophesize(PageInterface::class);
        $this->page->getWebspaceKey()->willReturn('sulu-io');

        $this->pageDimensionContent = new PageDimensionContent($this->page->reveal());
    }

    public function testConstruct(): void
    {
        $page = $this->prophesize(PageInterface::class);
        $pageDimensionContent = new PageDimensionContent($page->reveal());

        $this->assertSame($page->reveal(), $pageDimensionContent->getResource());
        $this->assertSame([], $pageDimensionContent->getNavigationContexts());
    }

    public function testGetResource(): void
    {
        $this->assertSame($this->page->reveal(), $this->pageDimensionContent->getResource());
    }

    public function testGetTitleAfterTemplateDataInit(): void
    {
        // Initialize title through setTemplateData with a string title to ensure property initialization
        $this->pageDimensionContent->setTemplateData(['title' => 'Test']);
        $this->assertSame('Test', $this->pageDimensionContent->getTitle());

        // Then test that we can set it to a different value
        $this->pageDimensionContent->setTemplateData(['title' => null]); // This won't change title since null is not string
        $this->assertSame('Test', $this->pageDimensionContent->getTitle()); // Should remain unchanged
    }

    public function testSetTemplateDataWithTitle(): void
    {
        $templateData = [
            'title' => 'Test Title',
            'description' => 'Test Description',
            'content' => 'Test Content',
        ];

        $this->pageDimensionContent->setTemplateData($templateData);

        $this->assertSame('Test Title', $this->pageDimensionContent->getTitle());
        $this->assertSame($templateData, $this->pageDimensionContent->getTemplateData());
    }

    public function testSetTemplateDataWithoutTitle(): void
    {
        // First initialize title to ensure property is initialized
        $this->pageDimensionContent->setTemplateData(['title' => 'Initial']);

        $templateData = [
            'description' => 'Test Description',
            'content' => 'Test Content',
        ];

        // This will not change the title since no title key is provided
        $this->pageDimensionContent->setTemplateData($templateData);

        $this->assertSame('Initial', $this->pageDimensionContent->getTitle()); // title should remain unchanged
        $this->assertSame($templateData, $this->pageDimensionContent->getTemplateData());
    }

    public function testSetTemplateDataWithNonStringTitle(): void
    {
        // First initialize title
        $this->pageDimensionContent->setTemplateData(['title' => 'Initial']);

        $templateData = [
            'title' => 123, // non-string title
            'description' => 'Test Description',
        ];

        // This will not change the title since title is not a string
        $this->pageDimensionContent->setTemplateData($templateData);

        $this->assertSame('Initial', $this->pageDimensionContent->getTitle()); // should remain unchanged
        $this->assertSame($templateData, $this->pageDimensionContent->getTemplateData());
    }

    public function testSetTemplateDataWithNullTitle(): void
    {
        // First set a title
        $this->pageDimensionContent->setTemplateData(['title' => 'Initial Title']);
        $this->assertSame('Initial Title', $this->pageDimensionContent->getTitle());

        // Then set data with null title
        $templateData = [
            'title' => null,
            'description' => 'Test Description',
        ];

        $this->pageDimensionContent->setTemplateData($templateData);

        // Title should remain unchanged since null is not a string
        $this->assertSame('Initial Title', $this->pageDimensionContent->getTitle());
        $this->assertSame($templateData, $this->pageDimensionContent->getTemplateData());
    }

    public function testSetRoute(): void
    {
        $route = $this->prophesize(Route::class);
        $route->setWebspace('sulu-io')->shouldBeCalled()->willReturn($route->reveal());

        $this->pageDimensionContent->setRoute($route->reveal());

        $this->assertSame($route->reveal(), $this->pageDimensionContent->getRoute());
    }

    public function testGetTemplateType(): void
    {
        $this->assertSame(PageInterface::TEMPLATE_TYPE, PageDimensionContent::getTemplateType());
    }

    public function testGetResourceKey(): void
    {
        $this->assertSame(PageInterface::RESOURCE_KEY, PageDimensionContent::getResourceKey());
    }

    public function testGetNavigationContextsEmpty(): void
    {
        $this->assertSame([], $this->pageDimensionContent->getNavigationContexts());
    }

    public function testSetNavigationContexts(): void
    {
        $navigationContexts = ['main', 'footer', 'sidebar'];

        $result = $this->pageDimensionContent->setNavigationContexts($navigationContexts);

        $this->assertSame($this->pageDimensionContent, $result);
        $this->assertSame($navigationContexts, $this->pageDimensionContent->getNavigationContexts());
    }

    public function testSetNavigationContextsReplaceExisting(): void
    {
        // First set some contexts
        $this->pageDimensionContent->setNavigationContexts(['main', 'footer']);
        $this->assertSame(['main', 'footer'], $this->pageDimensionContent->getNavigationContexts());

        // Now replace with new contexts
        $newContexts = ['sidebar', 'header'];
        $this->pageDimensionContent->setNavigationContexts($newContexts);

        // Check that order matches and contexts are correctly replaced
        $actualContexts = $this->pageDimensionContent->getNavigationContexts();
        $this->assertContains('sidebar', $actualContexts);
        $this->assertContains('header', $actualContexts);
        $this->assertNotContains('main', $actualContexts);
        $this->assertNotContains('footer', $actualContexts);
    }

    public function testSetNavigationContextsPartialUpdate(): void
    {
        // First set some contexts
        $this->pageDimensionContent->setNavigationContexts(['main', 'footer', 'sidebar']);

        // Update to keep some, remove some, add new
        $newContexts = ['main', 'header']; // keep main, remove footer+sidebar, add header
        $this->pageDimensionContent->setNavigationContexts($newContexts);

        $actualContexts = $this->pageDimensionContent->getNavigationContexts();
        $this->assertSame($newContexts, $actualContexts);
    }

    public function testAddNavigationContext(): void
    {
        $result = $this->pageDimensionContent->addNavigationContext('main');

        $this->assertSame($this->pageDimensionContent, $result);
        $this->assertSame(['main'], $this->pageDimensionContent->getNavigationContexts());
    }

    public function testAddNavigationContextMultiple(): void
    {
        $this->pageDimensionContent->addNavigationContext('main');
        $this->pageDimensionContent->addNavigationContext('footer');
        $this->pageDimensionContent->addNavigationContext('sidebar');

        $contexts = $this->pageDimensionContent->getNavigationContexts();
        $this->assertCount(3, $contexts);
        $this->assertContains('main', $contexts);
        $this->assertContains('footer', $contexts);
        $this->assertContains('sidebar', $contexts);
    }

    public function testAddNavigationContextDuplicate(): void
    {
        $this->pageDimensionContent->addNavigationContext('main');
        $this->pageDimensionContent->addNavigationContext('main'); // duplicate

        $this->assertSame(['main'], $this->pageDimensionContent->getNavigationContexts());
    }

    public function testRemoveNavigationContext(): void
    {
        $this->pageDimensionContent->setNavigationContexts(['main', 'footer', 'sidebar']);

        $result = $this->pageDimensionContent->removeNavigationContext('footer');

        $this->assertSame($this->pageDimensionContent, $result);
        $contexts = $this->pageDimensionContent->getNavigationContexts();
        $this->assertSame(['main', 'sidebar'], $contexts);
    }

    public function testRemoveNavigationContextNonExisting(): void
    {
        $this->pageDimensionContent->setNavigationContexts(['main', 'footer']);

        $result = $this->pageDimensionContent->removeNavigationContext('nonexistent');

        $this->assertSame($this->pageDimensionContent, $result);
        $this->assertSame(['main', 'footer'], $this->pageDimensionContent->getNavigationContexts());
    }

    public function testRemoveNavigationContextEmpty(): void
    {
        $result = $this->pageDimensionContent->removeNavigationContext('main');

        $this->assertSame($this->pageDimensionContent, $result);
        $this->assertSame([], $this->pageDimensionContent->getNavigationContexts());
    }

    public function testHasNavigationContext(): void
    {
        $this->pageDimensionContent->setNavigationContexts(['main', 'footer']);

        $this->assertTrue($this->pageDimensionContent->hasNavigationContext('main'));
        $this->assertTrue($this->pageDimensionContent->hasNavigationContext('footer'));
        $this->assertFalse($this->pageDimensionContent->hasNavigationContext('sidebar'));
        $this->assertFalse($this->pageDimensionContent->hasNavigationContext('nonexistent'));
    }

    public function testHasNavigationContextEmpty(): void
    {
        $this->assertFalse($this->pageDimensionContent->hasNavigationContext('main'));
    }

    public function testWithRouteReturnsCloneWithRouteAttached(): void
    {
        $route = new Route('pages', 'uuid', 'en', '/foo', 'sulu-io');

        $clone = $this->pageDimensionContent->withRoute($route);

        $this->assertNotSame($this->pageDimensionContent, $clone);
        $this->assertSame($route, $clone->getRoute());
        $this->assertNull($this->pageDimensionContent->getRoute());
    }

    public function testWithRouteAcceptsNull(): void
    {
        $route = $this->prophesize(Route::class);
        $route->setWebspace('sulu-io')->shouldBeCalled()->willReturn($route->reveal());
        $this->pageDimensionContent->setRoute($route->reveal());

        $clone = $this->pageDimensionContent->withRoute(null);

        $this->assertNull($clone->getRoute());
        $this->assertSame($route->reveal(), $this->pageDimensionContent->getRoute());
    }

    public function testNavigationContextsWorkflow(): void
    {
        // Start empty
        $this->assertSame([], $this->pageDimensionContent->getNavigationContexts());
        $this->assertFalse($this->pageDimensionContent->hasNavigationContext('main'));

        // Add contexts
        $this->pageDimensionContent->addNavigationContext('main');
        $this->pageDimensionContent->addNavigationContext('footer');
        $this->assertTrue($this->pageDimensionContent->hasNavigationContext('main'));
        $this->assertTrue($this->pageDimensionContent->hasNavigationContext('footer'));

        // Set new contexts (should replace)
        $this->pageDimensionContent->setNavigationContexts(['sidebar', 'header']);
        $this->assertFalse($this->pageDimensionContent->hasNavigationContext('main'));
        $this->assertFalse($this->pageDimensionContent->hasNavigationContext('footer'));
        $this->assertTrue($this->pageDimensionContent->hasNavigationContext('sidebar'));
        $this->assertTrue($this->pageDimensionContent->hasNavigationContext('header'));

        // Remove one
        $this->pageDimensionContent->removeNavigationContext('sidebar');
        $this->assertFalse($this->pageDimensionContent->hasNavigationContext('sidebar'));
        $this->assertTrue($this->pageDimensionContent->hasNavigationContext('header'));

        // Final state
        $finalContexts = $this->pageDimensionContent->getNavigationContexts();
        $this->assertCount(1, $finalContexts);
        $this->assertContains('header', $finalContexts);
    }
}
