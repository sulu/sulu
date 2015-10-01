<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Twig;

use Doctrine\Common\Cache\ArrayCache;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Prophecy\Argument;
use Sulu\Bundle\CategoryBundle\Api\Category as ApiCategory;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\Category as EntityCategory;
use Sulu\Bundle\CategoryBundle\Twig\CategoryTwigExtension;
use Sulu\Component\Cache\Memoize;
use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Category\Request\CategoryRequestHandler;
use Sulu\Component\Category\Request\CategoryRequestHandlerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CategoryTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Returns memoize cache instance.
     *
     * @return MemoizeInterface
     */
    private function getMemoizeCache()
    {
        return new Memoize(new ArrayCache(), 0);
    }

    /**
     * Returns ApiCategory with given Data (id, name).
     *
     * @param array $data
     *
     * @return ApiCategory
     */
    private function createCategoryEntity(array $data)
    {
        $category = $this->prophesize(ApiCategory::class);
        $category->getid()->willReturn($data['id']);
        $category->getName()->willReturn($data['name']);

        return $category->reveal();
    }

    private function createCategoryApi($data)
    {
        $category = $this->prophesize(EntityCategory::class);
        $category->getid()->willReturn($data['id']);

        return $category->reveal();
    }

    public function getProvider()
    {
        return [
            [[]],
            [[['id' => 1, 'name' => 'sulu']]],
            [[['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core']]],
            [[['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core'], ['id' => 3, 'name' => 'massive']]],
            [[], 'de', 5],
            [[['id' => 1, 'name' => 'sulu']], 'de', 5],
            [[['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core']], 'de', 5],
            [[['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core'], ['id' => 3, 'name' => 'massive']], 'de', 5],
            [[], 'de', 5, 1],
            [[['id' => 1, 'name' => 'sulu']], 'de', 5, 1],
            [[['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core']], 'de', 5, 1],
            [
                [['id' => 1, 'name' => 'sulu'], ['id' => 2, 'name' => 'core'], ['id' => 3, 'name' => 'massive']],
                'de',
                5,
                1,
            ],
        ];
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet($categoryData, $locale = 'en', $parent = null, $depth = null)
    {
        $categoryEntities = [];
        $categoryApis = [];
        foreach ($categoryData as $category) {
            $categoryEntities[] = $this->createCategoryEntity($category);
            $categoryApis[] = $this->createCategoryApi($category);
        }

        $manager = $this->prophesize(CategoryManagerInterface::class);
        if (null === $parent) {
            $manager->find(null, null)->shouldBeCalled()->willReturn($categoryEntities);
        } else {
            $manager->findChildren($parent)->shouldBeCalled()->willReturn($categoryEntities);
        }
        $manager->getApiObjects($categoryEntities, $locale)->shouldBeCalled()->willReturn($categoryApis);

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->serialize($categoryApis, 'array', Argument::type(SerializationContext::class))
            ->shouldBeCalled()->willReturn($categoryData);

        $requestHandler = $this->prophesize(CategoryRequestHandlerInterface::class);
        $extension = new CategoryTwigExtension(
            $manager->reveal(),
            $requestHandler->reveal(),
            $serializer->reveal(),
            $this->getMemoizeCache()
        );

        $this->assertEquals($categoryData, $extension->getCategoriesFunction($locale, $parent, $depth));
    }

    public function appendProvider()
    {
        return [
            ['c', '/test', '1,2', '1,2,3'],
            ['categories', '/asdf', '1,2', '1,2,3'],
            ['c', '/asdf', '1,2', '1,2,3'],
            ['c', '/asdf', '2,1', '2,1,3'],
            ['categories', '/test', '1,2', '1,2,3'],
            ['categories', '/test', '1,3', '1,3'],
            ['categories', '/test', '', '3'],
        ];
    }

    /**
     * @dataProvider appendProvider
     */
    public function testAppendUrl($parameter, $url, $string, $expected)
    {
        $category = ['id' => 3, 'name' => 'test'];

        $manager = $this->prophesize(CategoryManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$parameter => $string]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($string);
        $request->getPathInfo()->willReturn($url);

        $serializer = $this->prophesize(SerializerInterface::class);
        $requestHandler = new CategoryRequestHandler($requestStack->reveal());

        $extension = new CategoryTwigExtension(
            $manager->reveal(),
            $requestHandler,
            $serializer->reveal(),
            $this->getMemoizeCache()
        );
        $result = $extension->appendCategoryUrlFunction($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . urlencode($expected), $result);
    }

    public function setProvider()
    {
        return [
            ['c', '/test', '1,2', '3'],
            ['categories', '/asdf', '1,2', '3'],
            ['c', '/asdf', '1,2', '3'],
            ['c', '/asdf', '2,1', '3'],
            ['categories', '/test', '2,1', '3'],
            ['categories', '/test', '1,2', '3'],
            ['categories', '/test', '1,2', '3'],
            ['categories', '/test', '', '3'],
        ];
    }

    /**
     * @dataProvider setProvider
     */
    public function testSetUrl($parameter, $url, $string, $expected)
    {
        $category = ['id' => 3, 'name' => 'test'];

        $manager = $this->prophesize(CategoryManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$parameter => $string]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($string);
        $request->getPathInfo()->willReturn($url);

        $serializer = $this->prophesize(SerializerInterface::class);
        $requestHandler = new CategoryRequestHandler($requestStack->reveal());

        $extension = new CategoryTwigExtension(
            $manager->reveal(),
            $requestHandler,
            $serializer->reveal(),
            $this->getMemoizeCache()
        );
        $result = $extension->setCategoryUrlFunction($category, $parameter);

        $this->assertEquals($url . '?' . $parameter . '=' . urlencode($expected), $result);
    }

    public function clearProvider()
    {
        return [
            ['c', '/test', '1,2'],
            ['c', '/asdf', '1,2'],
            ['categories', '/asdf', '1,2'],
            ['categories', '/test', '1,2'],
            ['categories', '/test', '1,2'],
            ['categories', '/test', ''],
        ];
    }

    /**
     * @dataProvider clearProvider
     */
    public function testClearUrl($parameter, $url, $string)
    {
        $manager = $this->prophesize(CategoryManagerInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $request = $this->prophesize(Request::class);

        $requestReveal = $request->reveal();
        $requestReveal->query = new ParameterBag([$parameter => $string]);
        $requestStack->getCurrentRequest()->willReturn($requestReveal);
        $request->get($parameter, '')->willReturn($string);
        $request->getPathInfo()->willReturn($url);

        $serializer = $this->prophesize(SerializerInterface::class);
        $requestHandler = new CategoryRequestHandler($requestStack->reveal());

        $tagExtension = new CategoryTwigExtension(
            $manager->reveal(),
            $requestHandler,
            $serializer->reveal(),
            $this->getMemoizeCache()
        );
        $result = $tagExtension->clearCategoryUrlFunction($parameter);

        $this->assertEquals($url, $result);
    }
}
