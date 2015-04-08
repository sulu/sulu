<?php

namespace DTL\Component\Content\Routing\Auto\Provider;

use Symfony\Component\OptionsResolver\OptionsResolver;
use DTL\Component\Content\Routing\Auto\Provider\SuluResourceLocatorProvider;
use Symfony\Cmf\Component\RoutingAuto\UriContext;
use DTL\Component\Content\Document\PageInterface;
use Doctrine\ODM\PHPCR\DocumentManager;

class SuluResourceLocatorProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManager::class);
        $this->provider = new SuluResourceLocatorProvider(
            $this->documentManager->reveal()
        );
        $this->optionsResolver = new OptionsResolver();
        $this->notPage = new \stdClass;
        $this->document = $this->prophesize(PageInterface::class);
        $this->parentDocument = $this->prophesize(PageInterface::class);
        $this->uriContext = $this->prophesize(UriContext::class);

        $this->document->getUuid()->willReturn('1234');
        $this->parentDocument->getUuid()->willReturn('1234');
        $this->uriContext->getLocale()->willReturn('de');
    }

    /**
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
        $this->document->getParent()->willReturn(new \stdClass);

        $this->provideValue(array());
    }

    /**
     * Will return segment for document with no parents implementing PageInterface
     */
    public function testProviderSingleSegment()
    {
        $this->uriContext->getSubjectObject()->willReturn($this->document->reveal());
        $this->document->getParent()->willReturn(new \stdClass);
        $this->document->getResourceSegment()->willReturn('hello');
        $this->document->getParent()->willReturn(new \stdClass);
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
        $this->parentDocument->getParent()->willReturn(new \stdClass);
        $this->document->getParent()->willReturn($this->parentDocument->reveal());
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
        $this->parentDocument->getParent()->willReturn(new \stdClass);
        $this->document->getParent()->willReturn($this->parentDocument->reveal());
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
        $this->parentDocument->getParent()->willReturn(new \stdClass);
        $this->document->getParent()->willReturn($this->parentDocument->reveal());
        $this->document->getResourceSegment()->shouldNotBeCalled();

        $result = $this->provideValue(array('parent' => true));
        $this->assertEquals('hello', $result);
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
