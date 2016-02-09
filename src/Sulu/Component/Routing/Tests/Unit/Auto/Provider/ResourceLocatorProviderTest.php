<?php

namespace Sulu\Component\Routing\Tests\Auto\Provider;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Cmf\Component\RoutingAuto\UriContext;
use Sulu\Component\Routing\Auto\Provider\ResourceLocatorProvider;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;

class ResourceLocatorProviderTest extends \PHPUnit_Framework_TestCase
{
    private $manager;
    private $inspector;

    public function setUp()
    {
        $this->manager = $this->prophesize(DocumentManager::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);

        $this->provider = new ResourceLocatorProvider(
            $this->manager->reveal(),
            $this->inspector->reveal()
        );

        $this->optionsResolver = new OptionsResolver();
        $this->notPage = new \stdClass;
        $this->document = $this->prophesize(ResourceSegmentBehavior::class);
        $this->parentDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $this->uriContext = $this->prophesize(UriContext::class);

        $this->inspector->getUuid($this->document->reveal())->willReturn('1234');
        $this->inspector->getUuid($this->parentDocument->reveal())->willReturn('1234');
        $this->uriContext->getLocale()->willReturn('de');
    }

    /**
     * It should throw an exception if a non-resource-segment implementing object
     * is given.
     *
     * @expectedException InvalidArgumentException
     */
    public function testNotPage()
    {
        $this->uriContext->getSubjectObject()->willReturn($this->notPage);
        $result = $this->provideValue(array());
        $this->assertEquals('', $result);
    }

    /**
     * Can return an empty string if segment is empty
     */
    public function testProviderEmptySegment()
    {
        $this->uriContext->getSubjectObject()->willReturn($this->document->reveal());
        $this->document->getResourceSegment()->willReturn(null);
        $this->inspector->getParent($this->document->reveal())->willReturn($this->parentDocument->reveal());
        $this->inspector->getParent($this->parentDocument->reveal())->willReturn(null);

        $this->provideValue(array());
    }

    /**
     * Will return segment for document with no parents implementing PageInterface
     */
    public function testProviderSingleSegment()
    {
        $this->uriContext->getSubjectObject()->willReturn($this->document->reveal());

        $this->inspector->getParent($this->document->reveal())->willReturn(new \stdClass());
        $this->document->getResourceSegment()->willReturn('hello');
        $result = $this->provideValue(array());
        $this->assertEquals('hello', $result);
    }

    /**
     * Will concatenate all parent page elements
     */
    public function testProviderMultipleSegment()
    {
        $this->uriContext->getSubjectObject()->willReturn($this->document->reveal());

        $this->parentDocument->getResourceSegment()->willReturn('hello');

        $this->inspector->getParent($this->document->reveal())->willReturn($this->parentDocument->reveal());
        $this->inspector->getParent($this->parentDocument->reveal())->willReturn(new \stdClass());

        $this->document->getResourceSegment()->willReturn('goodbye');

        $result = $this->provideValue(array());
        $this->assertEquals('hello/goodbye', $result);
    }

    /**
     * Will collapse empty values
     */
    public function testProviderMultipleSegmentCollapse()
    {
        $this->uriContext->getSubjectObject()->willReturn($this->document->reveal());
        $this->parentDocument->getResourceSegment()->willReturn('');

        $this->inspector->getParent($this->document->reveal())->willReturn($this->parentDocument->reveal());
        $this->inspector->getParent($this->parentDocument->reveal())->willReturn(new \stdClass());

        $this->document->getResourceSegment()->willReturn('goodbye');

        $result = $this->provideValue(array());
        $this->assertEquals('goodbye', $result);
    }

    /**
     * Can specify "parent" option to generate resource locator for the parent document
     */
    public function testProviderFromParent()
    {
        $this->uriContext->getSubjectObject()->willReturn($this->document->reveal());

        $this->parentDocument->getResourceSegment()->willReturn('hello');

        $this->inspector->getParent($this->document->reveal())->willReturn($this->parentDocument->reveal());
        $this->inspector->getParent($this->parentDocument->reveal())->willReturn(new \stdClass());

        $this->document->getResourceSegment()->shouldNotBeCalled();

        $result = $this->provideValue(array('parent' => true));
        $this->assertEquals('hello', $result);
    }

    /**
     * It should throw an exception if parent option is true and the document has no parent.
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage no parent when trying to provide parent 
     */
    public function testParentNoParent()
    {
        $this->uriContext->getSubjectObject()->willReturn($this->document->reveal());

        $this->inspector->getParent($this->document->reveal())->willReturn(null);

        $this->provideValue(array('parent' => true));
    }

    private function provideValue($options)
    {
        $this->provider->configureOptions($this->optionsResolver);
        $options = $this->optionsResolver->resolve($options);

        return $this->provider->provideValue(
            $this->uriContext->reveal(),
            $options
        );
    }
}

