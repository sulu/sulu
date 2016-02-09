<?php

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Routing;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;

class RouteSubscriberTest extends SuluTestCase
{
    private $manager;

    public function setUp()
    {
        $this->manager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->initPhpcr();
    }

    /**
     * It should generate auto route when a document is saved.
     */
    public function testGenerate()
    {
        $document = $this->createPage(['name' => 'hello'], ['de']);
        $this->manager->flush();
        $this->manager->clear();

        $route = $this->manager->find('/cmf/sulu_io/routes/de/hello');
        $this->assertInstanceOf('Sulu\Bundle\ContentBundle\Document\RouteDocument', $route);
        $this->assertSame($document->getPath(), $route->getTargetDocument()->getPath());
        $this->assertEquals('de', $route->getAutoRouteTag());
        $this->assertFalse($route->isRedirect());
    }

    /**
     * It should generate a route for each locale when a document is saved.
     */
    public function testGenereateMultipleLocales()
    {
        $locales = array('en', 'de', 'de_at');

        $this->createPage(['name' => 'hello'], $locales);
        $this->manager->flush();
        $this->manager->clear();

        foreach ($locales as $locale) {
            $this->assertAutoRouteExists($locale . '/hello');
        }
    }

    /**
     * It should remove auto routes when the document is removed.
     */
    public function testAutoRouteRemove()
    {
        $locales = array('en', 'de');
        $document1 = $this->createPage(['name' => 'foo1'], $locales);
        $this->createPage(['name' => 'foo2'], $locales);
        $this->manager->flush();

        foreach ($locales as $locale) {
            $this->assertAutoRouteExists($locale. '/foo1');
        }

        $this->manager->remove($document1);

        foreach ($locales as $locale) {
            $this->assertAutoRouteNotExists($locale . '/foo1');
        }
    }

    /**
     * It should migrate children when a route path is changed.
     */
    public function testMigrate()
    {
        $locales = array('en');
        $page = $this->createPage(['name' => 'one'], $locales);
        $this->createPage(['path' => 'one/', 'name' => 'two'], $locales);
        $this->createPage(['path' => 'one/two/', 'name' => 'three'], $locales);
        $this->createPage(['path' => 'one/two/three/', 'name' => 'four'], $locales);
        $this->manager->flush();

        $this->assertAutoRouteExists('en/one');
        $this->assertAutoRouteExists('en/one/two');

        $page->setResourceSegment('something-new');
        $this->manager->persist($page, 'en');
        $this->manager->flush();

        $this->assertAutoRouteExists('en/something-new');
        $this->assertAutoRouteExists('en/something-new/two');
        $this->assertAutoRouteExists('en/something-new/two/three');

        $route = $this->findAutoRoute('en/something-new');
        $this->assertFalse($route->isRedirect());
    }

    /**
     * It should convert defunct routes into redirects to the new content.
     */
    public function testDefunctRedirect()
    {
        $locales = array('en');
        $page = $this->createPage(['name' => 'one'], $locales);
        $this->createPage(['path' => 'one/', 'name' => 'two'], $locales);
        $this->createPage(['path' => 'one/two/', 'name' => 'three'], $locales);
        $this->manager->flush();

        $this->assertAutoRouteExists('en/one/two');

        $page->setResourceSegment('something-new');
        $this->manager->persist($page, 'en');
        $this->manager->flush();

        $this->assertAutoRouteExists('en/one');
        $route = $this->findAutoRoute('en/one');
        $this->assertEquals($page->getUuid(), $route->getTargetDocument()->getUUid());
        $this->assertTrue($route->isRedirect());

        // See https://github.com/symfony-cmf/RoutingAuto/issues/67
        $this->markTestSkipped('TODO: Bug in RoutingAuto component, see link above.');

        $this->assertAutoRouteExists('en/one/two');
        $route = $this->findAutoRoute('en/one/two');
        $this->assertTrue($route->isRedirect());
    }

    /**
     * It should handle conflicting names.
     */
    public function testHandleConflictingName()
    {
        $locales = array('en');
        $this->createPage(['name' => 'one', 'resource_segment' => 'hello'], $locales);
        $this->createPage(['name' => 'two', 'resource_segment' => 'hello'], $locales);
        $this->createPage(['name' => 'three', 'resource_segment' => 'hello'], $locales);
        $this->manager->flush();

        $this->assertAutoRouteExists('en/hello');
        $this->assertAutoRouteExists('en/hello-1');
        $this->assertAutoRouteExists('en/hello-2');

    }

    private function createPage(array $options = array(), $locales = ['de'])
    {
        $options = array_merge(array(
            'title' => null,
            'name' => 'one',
            'resource_segment' => null,
            'path' => '',
        ), $options);

        if (null === $options['title']) {
            $options['title'] = $options['name'];
        }

        if (null === $options['resource_segment']) {
            $options['resource_segment'] = $options['name'];
        }

        $document = new PageDocument();
        $document->setStructureType('default');
        $document->setTitle($options['title']);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setResourceSegment($options['resource_segment']);

        foreach ($locales as $locale) {
            $this->manager->persist(
                $document,
                $locale,
                [
                    'path' => '/cmf/sulu_io/contents/' . $options['path'] . $options['name']
                ]
            );
        }

        return $document;
    }

    private function assertAutoRouteExists($route)
    {
        try {
            $route = $this->manager->find('/cmf/sulu_io/routes/' . $route);
            $this->assertInstanceOf('Sulu\Bundle\ContentBundle\Document\RouteDocument', $route);
        } catch (DocumentNotFoundException $e) {
            $this->fail(sprintf('Route "%s" does not exist.', $route));
        }
    }

    private function assertAutoRouteNotExists($route)
    {
        try {
            $this->manager->find('/cmf/sulu_io/routes/' . $route);
            $this->fail(sprintf('Route "%s" still exists', $route));
        } catch (DocumentNotFoundException $e) {
            // the route was removed.
        }
    }

    private function findAutoRoute($route)
    {
        return $this->manager->find('/cmf/sulu_io/routes/' . $route);
    }
}
