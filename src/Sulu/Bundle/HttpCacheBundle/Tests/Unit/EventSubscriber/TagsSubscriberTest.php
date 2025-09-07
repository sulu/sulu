<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\EventListener;

use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\EventSubscriber\TagsSubscriber;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

class TagsSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var TagsSubscriber
     */
    private $tagsSubscriber;

    /**
     * @var ObjectProphecy<ReferenceStorePoolInterface>
     */
    private $referenceStorePool;

    /**
     * @var ObjectProphecy<SymfonyResponseTagger>
     */
    private $symfonyResponseTagger;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ObjectProphecy<DimensionContentInterface>
     */
    private $dimensionContent; // @phpstan-ignore-line missingType.generics

    /**
     * @var array<ObjectProphecy<ReferenceStoreInterface>>
     */
    private $referenceStores = [];

    /**
     * @var string
     */
    private $uuid1;

    /**
     * @var string
     */
    private $uuid2;

    /**
     * @var string
     */
    private $contentId;

    public function setUp(): void
    {
        $this->uuid1 = Uuid::v7()->toRfc4122();
        $this->uuid2 = Uuid::v7()->toRfc4122();
        $this->contentId = Uuid::v7()->toRfc4122();

        $testReferenceStore = $this->prophesize(ReferenceStoreInterface::class);
        $testReferenceStore->getAll()->willReturn(['1', '2']);
        $this->referenceStores['test'] = $testReferenceStore;

        $testReferenceStore2 = $this->prophesize(ReferenceStoreInterface::class);
        $testReferenceStore2->getAll()->willReturn([$this->uuid1, $this->uuid2]);
        $this->referenceStores['test_uuid'] = $testReferenceStore2;

        $this->referenceStorePool = $this->prophesize(ReferenceStorePoolInterface::class);
        $this->referenceStorePool->getStores()->willReturn($this->referenceStores);

        $this->symfonyResponseTagger = $this->prophesize(SymfonyResponseTagger::class);

        $this->dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $contentRichEntityInterface = $this->prophesize(ContentRichEntityInterface::class);
        $this->dimensionContent->getResource()->willReturn($contentRichEntityInterface);
        $contentRichEntityInterface->getId()->willReturn($this->contentId);

        $this->request = new Request();
        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request);

        $this->tagsSubscriber = new TagsSubscriber(
            $this->referenceStorePool->reveal(),
            $this->symfonyResponseTagger->reveal(),
            $this->requestStack,
        );
    }

    public function testGet(): void
    {
        $this->request->attributes->set('object', $this->dimensionContent->reveal());

        $expectedTags = [
            'test-1',
            'test-2',
            $this->uuid1,
            $this->uuid2,
            $this->contentId,
        ];
        $this->symfonyResponseTagger->addTags($expectedTags)->shouldBeCalled();
        $this->tagsSubscriber->addTags();
    }

    public function testGetEmptyReferenceStore(): void
    {
        $this->request->attributes->set('object', $this->dimensionContent->reveal());

        $this->referenceStores['test_uuid']->getAll()->willReturn([]);
        $expectedTags = [
            'test-1',
            'test-2',
            $this->contentId,
        ];
        $this->symfonyResponseTagger->addTags($expectedTags)->shouldBeCalled();
        $this->tagsSubscriber->addTags();
    }

    public function testGetWithoutStructure(): void
    {
        $expectedTags = [
            'test-1',
            'test-2',
            $this->uuid1,
            $this->uuid2,
        ];
        $this->symfonyResponseTagger->addTags($expectedTags)->shouldBeCalled();
        $this->tagsSubscriber->addTags();
    }

    public function testGetWithWrongStructure(): void
    {
        $this->request->attributes->set('object', new \stdClass());
        $expectedTags = [
            'test-1',
            'test-2',
            $this->uuid1,
            $this->uuid2,
        ];
        $this->symfonyResponseTagger->addTags($expectedTags)->shouldBeCalled();
        $this->tagsSubscriber->addTags();
    }

    public function testGetWithoutRequest(): void
    {
        $this->requestStack->pop();
        $expectedTags = [
            'test-1',
            'test-2',
            $this->uuid1,
            $this->uuid2,
        ];
        $this->symfonyResponseTagger->addTags($expectedTags)->shouldBeCalled();
        $this->tagsSubscriber->addTags();
    }

    public function testEmptyReferenceStore(): void
    {
        $this->request->attributes->set('object', null);
        $this->referenceStores['test_uuid']->getAll()->willReturn([]);
        $this->referenceStores['test']->getAll()->willReturn([]);
        $this->symfonyResponseTagger->addTags(Argument::any())->shouldNotBeCalled();
        $this->tagsSubscriber->addTags();
    }
}
