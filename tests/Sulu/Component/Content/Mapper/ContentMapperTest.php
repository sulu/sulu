<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use ReflectionMethod;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyType;
use Sulu\Component\Content\BreadcrumbItemInterface;
use Sulu\Component\Content\ContentEvents;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Section\SectionProperty;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureExtension\StructureExtension;
use Sulu\Component\Content\StructureExtension\StructureExtensionInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Navigation;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Webspace;

/**
 * tests content mapper with tree strategy and phpcr mapper
 * TODO: REFACTOR THIS NOW - make it an integration test.
 */
class ContentMapperTest extends PhpcrTestCase
{
    /**
     * @var StructureExtensionInterface[]
     */
    private $extensions = array();

    public function setUp()
    {
        $this->extensions = array(new TestExtension('test1'), new TestExtension('test2', 'test2'));

        $this->prepareMapper();
    }

    public function structureCallback()
    {
        $args = func_get_args();
        $structureKey = $args[0];

        if ($structureKey == 'overview') {
            return $this->getPageMock(1);
        } elseif ($structureKey == 'default') {
            return $this->getPageMock(2);
        } elseif ($structureKey == 'complex') {
            return $this->getPageMock(3);
        } elseif ($structureKey == 'mandatory') {
            return $this->getPageMock(4);
        } elseif ($structureKey == 'section') {
            return $this->getPageMock(5);
        } elseif ($structureKey == 'extension') {
            return $this->getPageMock(6);
        } elseif ($structureKey == 'internal-link') {
            return $this->getPageMock(7, false);
        } elseif ($structureKey == 'external-link') {
            return $this->getPageMock(8, false);
        } elseif ($structureKey == 'with_snipplet') {
            return $this->getPageMock(9);
        } elseif ($structureKey == 'default_snippet') {
            return $this->getPageMock(10, false);
        }

        return null;
    }

    public function getPageMock($type = 1, $url = true)
    {
        $name = array(
            null, // index 0 not used
            'overview',
            'overview',
            'overview',
            'overview',
            'section',
            'extensions',
            'overview',
            'overview',
            'overview',
            'overview'
        );

        if ($type == 10) {
            $structureMock = $this->getMockForAbstractClass(
                '\Sulu\Component\Content\Structure\Snippet',
                array($name[$type], 'asdf', 'asdf', 2400)
            );
        } else {
            $structureMock = $this->getMockForAbstractClass(
                '\Sulu\Component\Content\Structure\Page',
                array($name[$type], 'asdf', 'asdf', 2400)
            );
        }

        $method = new ReflectionMethod(
            get_class($structureMock), 'addChild'
        );

        $method->setAccessible(true);
        $method->invokeArgs(
            $structureMock,
            array(
                new Property(
                    'title',
                    '',
                    'text_line',
                    false,
                    true,
                    1,
                    1,
                    array()
                )
            )
        );

        if ($url) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property(
                        'url', '', 'resource_locator', false, true, 1, 1, array(), array(new PropertyTag('sulu.rlp', 1))
                    )
                )
            );
        }

        if ($type == 1) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('tags', '', 'text_line', false, true, 2, 10)
                )
            );

            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('article', '', 'text_area', false, true)
                )
            );
        } elseif ($type == 2) {
            // not translated
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('blog', '', 'text_area', false, false)
                )
            );
        } elseif ($type == 3) {
            $blockProperty = new BlockProperty('block1', '', 'default', false, true, 2, 10);
            $type = new BlockPropertyType('default', '');
            $type->addChild(new Property('title', '', 'text_line', false, true));
            $type->addChild(new Property('article', '', 'text_area', false, true));
            $blockProperty->addType($type);

            $method->invokeArgs(
                $structureMock,
                array(
                    $blockProperty
                )
            );
        } elseif ($type == 4) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('blog', '', 'text_line', true, true)
                )
            );
        } elseif ($type == 5) {
            $section = new SectionProperty('test', array(), '6');
            $section->addChild(
                new Property(
                    'blog',
                    '',
                    'text_line',
                    true,
                    true,
                    1,
                    1,
                    array()
                )
            );

            $method->invokeArgs(
                $structureMock,
                array(
                    $section
                )
            );
        } elseif ($type == 6) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('blog', '', 'text_line', true, true)
                )
            );
        } elseif ($type == 7) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property(
                        'internal',
                        '',
                        'text_line',
                        true,
                        true,
                        1,
                        1,
                        array(),
                        array(new PropertyTag('sulu.rlp', 1))
                    )
                )
            );
        } elseif ($type == 8) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property(
                        'external',
                        '',
                        'text_line',
                        true,
                        true,
                        1,
                        1,
                        array(),
                        array(new PropertyTag('sulu.rlp', 1))
                    )
                )
            );
        } elseif ($type == 9) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property(
                        'internal', // same as internal link to test content type switch
                        '',
                        'snippet'
                    )
                )
            );
        } elseif ($type == 10) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property('article', '', 'text_area', false, true)
                )
            );
        }

        return $structureMock;
    }

    public function getExtensionsCallback()
    {
        $args = func_get_args();
        $structureKey = $args[0];

        if ($structureKey === 'extensions') {
            return $this->extensions;
        }

        return array();
    }

    /**
     * default get extension callback returns null
     * @return array
     */
    public function getExtensionCallback()
    {
        $args = func_get_args();
        $structureKey = $args[0];
        $extensionName = $args[1];

        if ($structureKey === 'extensions' && $extensionName === 'test1') {
            return $this->extensions[0];
        }
        if ($structureKey === 'extensions' && $extensionName === 'test2') {
            return $this->extensions[1];
        }

        return null;
    }

    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager === null) {
            $webspace = new Webspace();
            $en = new Localization();
            $en->setLanguage('en');
            $en_us = new Localization();
            $en_us->setLanguage('en');
            $en_us->setCountry('us');
            $en_us->setParent($en);
            $en->addChild($en_us);

            $de = new Localization();
            $de->setLanguage('de');
            $de_at = new Localization();
            $de_at->setLanguage('de');
            $de_at->setCountry('at');
            $de_at->setParent($de);
            $de->addChild($de_at);

            $es = new Localization();
            $es->setLanguage('es');

            $webspace->addLocalization($en);
            $webspace->addLocalization($de);
            $webspace->addLocalization($es);

            $webspace->setNavigation(
                new Navigation(array(new NavigationContext('main', array()), new NavigationContext('footer', array())))
            );

            $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
            $this->webspaceManager->expects($this->any())
                ->method('findWebspaceByKey')
                ->will($this->returnValue($webspace));
        }
    }

    public function testSave()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_PRE_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_POST_SAVE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeEvent')
            );

        $result = $this->mapper->saveRequest(
            ContentMapperRequest::create()
                ->setWebspaceKey('default')
                ->setTemplateKey('overview')
                ->setLocale('de')
                ->setUserId(1)
                ->setData($data)
            );

        $this->assertEquals('Testname', $result->getPropertyValue('title'));
        $this->assertEquals(
            array(
                'tag1',
                'tag2'
            ),
            $result->getPropertyValue('tags')
        );
        $this->assertEquals('/news/test', $result->getPropertyValue('url'));
        $this->assertEquals('default', $result->getPropertyValue('article'));
        $this->assertEmpty($result->getNavContexts());

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals('default', $content->getProperty($this->languageNamespace . ':de-article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );

        // no navigationContext saved
        $this->assertEquals(false, $content->hasProperty($this->languageNamespace . ':de-navContexts'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function provideSaveShadow()
    {
        return array(
            array(
                array(
                    'is_shadow' => false,
                    'language' => 'de',
                    'shadow_base_language' => 'fr'
                ),
                null,
                array()
            ),
            array(
                array(
                    'is_shadow' => true,
                    'language' => 'de',
                    'shadow_base_language' => 'de'
                ),
                null,
                array(
                    'exception' => 'shadow of itself',
                )
            ),
            array(
                array(
                    'is_shadow' => false,
                    'language' => 'de',
                    'shadow_base_language' => null,
                ),
                array(
                    'is_shadow' => true,
                    'language' => 'en',
                    'shadow_base_language' => 'de_at'
                ),
                array(
                    'exception' => 'Attempting to make language "en" a shadow of a non-concrete language "de_at". Concrete languages are "de"'
                ),
            ),
            array(
                array(
                    'is_shadow' => false,
                    'language' => 'de_at',
                    'shadow_base_language' => 'de'
                ),
                array(
                    'is_shadow' => true,
                    'language' => 'en_us',
                    'shadow_base_language' => 'de_at'
                ),
                array(),
            ),
            array(
                array(
                    'is_shadow' => false,
                    'language' => 'de_at',
                    'shadow_base_language' => 'en_us'
                ),
                array(
                    'is_shadow' => true,
                    'language' => 'en_us',
                    'shadow_base_language' => 'de_at'
                ),
                array()
            ),
            array(
                array(
                    'is_shadow' => false,
                    'language' => 'de_at',
                    'shadow_base_language' => 'en_us'
                ),
                array(
                    'is_shadow' => true,
                    'language' => 'en_us',
                    'shadow_base_language' => 'de_at',
                    'url' => null,
                ),
                array()
            ),
        );
    }

    /**
     * @dataProvider provideSaveShadow
     */
    public function testSaveShadow(
        $node1,
        $node2,
        $expectations
    ) {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        if (isset($expectations['exception'])) {
            $this->setExpectedException('\RuntimeException', $expectations['exception']);
        }

        $nodes = array($node1);
        if ($node2) {
            $nodes[] = $node2;
        }

        $structures = array();
        foreach ($nodes as $i => $node) {
            if (array_key_exists('url', $node)) {
                $data['url'] = $node['url'];
            }

            $structures[$i] = $this->mapper->save(
                $data,
                'overview',
                'default',
                $node['language'],
                1,
                true,
                isset($structures[0]) ? $structures[0]->getUUid() : null,
                null,
                null,
                $node['is_shadow'],
                $node['shadow_base_language']
            );
        }

        $this->assertFalse($structures[0]->getIsShadow());

        if (isset($structures[1]) && $nodes[1]['is_shadow']) {
            $this->assertTrue($structures[1]->getIsShadow());

            $node = $this->session->getNode('/cmf/default/routes/' . $node['language'] . '/news/test');
        }
    }

    public function testLoad()
    {
        $data = ContentMapperRequest::create('page')
            ->setLocale('de')
            ->setTemplateKey('overview')
            ->setData(array(
                'title' => 'Testname',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test',
                'article' => 'default'
            ))
            ->setWebspaceKey('default')
            ->setUserId(1);

        $structure = $this->mapper->saveRequest($data);

        $content = $this->mapper->load($structure->getUuid(), 'default', 'de');

        $this->assertNotNull($content->getUuid());
        $this->assertEquals('/testname', $content->getPath());
        $this->assertEquals('default', $content->getWebspaceKey());
        $this->assertEquals('de', $content->getLanguageCode());
        $this->assertEquals('overview', $content->getKey());
        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEmpty($content->getNavContexts());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);
    }

    public function testNewProperty()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $contentBefore = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');
        /** @var NodeInterface $contentNode */
        $contentNode = $route->getPropertyValue('sulu:content');
        // simulate new property article, by deleting the property
        /** @var PropertyInterface $articleProperty */
        $articleProperty = $contentNode->getProperty($this->languageNamespace . ':de-article');
        $this->session->removeItem($articleProperty->getPath());
        $this->session->save();

        // simulates a new request
        $this->mapper = null;
        $this->session = null;
        $this->sessionManager = null;
        $this->structureValueMap = array(
            'overview' => $this->getPageMock(1),
            'default' => $this->getPageMock(2)
        );
        $this->prepareMapper();

        /** @var StructureInterface $content */
        $content = $this->mapper->load($contentBefore->getUuid(), 'default', 'de');
        // test values
        $this->assertEquals('Testname', $content->title);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);
    }

    public function testLoadByRL()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);
    }

    public function testUpdate()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['tags'][] = 'tag3';
        $data['tags'][0] = 'thats cool';
        $data['article'] = 'thats a new test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('thats a new test', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('thats cool', 'tag2', 'tag3'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals(
            'thats a new test',
            $content->getProperty($this->languageNamespace . ':de-article')->getString()
        );
        $this->assertEquals(
            array('thats cool', 'tag2', 'tag3'),
            $content->getPropertyValue($this->languageNamespace . ':de-tags')
        );
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testPartialUpdate()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['tags'][] = 'tag3';
        unset($data['tags'][0]);
        unset($data['article']);

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag2', 'tag3'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals('default', $content->getProperty($this->languageNamespace . ':de-article')->getString());
        $this->assertEquals(array('tag2', 'tag3'), $content->getPropertyValue($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testNonPartialUpdate()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['tags'][] = 'tag3';
        unset($data['tags'][0]);
        unset($data['article']);

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, false, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(array('tag2', 'tag3'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals(false, $content->hasProperty($this->languageNamespace . ':de-article'));
        $this->assertEquals(array('tag2', 'tag3'), $content->getPropertyValue($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testUpdateNullValue()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['tags'] = null;
        $data['article'] = null;

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, false, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(null, $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals(false, $content->hasProperty($this->languageNamespace . ':de-article'));
        $this->assertEquals(false, $content->hasProperty($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testUpdateTemplate()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data = array(
            'title' => 'Testname',
            'blog' => 'this is a blog test'
        );

        // update content
        $this->mapper->save($data, 'default', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');

        // old properties not exists in structure
        $this->assertEquals(false, $content->hasProperty('article'));
        $this->assertEquals(false, $content->hasProperty('tags'));

        // old properties are right
        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // new property is set
        $this->assertEquals('this is a blog test', $content->blog);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');
        $content = $route->getPropertyValue('sulu:content');

        // old properties exists in node
        $this->assertEquals('default', $content->getPropertyValue($this->languageNamespace . ':de-article'));
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue($this->languageNamespace . ':de-tags'));

        // property of new structure exists
        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals('this is a blog test', $content->getPropertyValue('blog'));
        $this->assertEquals('default', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testUpdateURL()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['url'] = '/news/test/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test/test/test', 'default', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/test/test/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test/test/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals('default', $content->getProperty($this->languageNamespace . ':de-article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));

        // old resource locator is not a route (has property sulu:content), it is a history (has property sulu:route)
        $oldRoute = $root->getNode('cmf/default/routes/de/news/test');
        $this->assertTrue($oldRoute->hasProperty('sulu:content'));
        $this->assertTrue($oldRoute->hasProperty('sulu:history'));
        $this->assertTrue($oldRoute->getPropertyValue('sulu:history'));

        // history should reference to new route
        $history = $oldRoute->getPropertyValue('sulu:content');
        $this->assertEquals($route->getIdentifier(), $history->getIdentifier());
    }

    public function testNameUpdate()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['title'] = 'test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // TODO works after this issue is fixed? but its not necessary
//        // check read
//        $content = $this->mapper->loadByResourceLocator('/news/test', 'default', 'de');
//
//        $this->assertEquals('default', $content->title);
//        $this->assertEquals('default', $content->article);
//        $this->assertEquals('/news/test', $content->url);
//        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
//        $this->assertEquals(1, $content->creator);
//        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $content = $root->getNode('cmf/default/contents/test');

        $this->assertEquals('test', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals('default', $content->getProperty($this->languageNamespace . ':de-article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testUpdateUrlTwice()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );

        // save content
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // change simple content
        $data['url'] = '/news/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, null, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test/test', 'default', 'de');
        $this->assertEquals('Testname', $content->title);

        // change simple content
        $data['url'] = '/news/asdf/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/asdf/test/test', 'default', 'de');
        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('default', $content->article);
        $this->assertEquals('/news/asdf/test/test', $content->url);
        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->creator);
        $this->assertEquals(1, $content->changer);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/asdf/test/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals('default', $content->getProperty($this->languageNamespace . ':de-article')->getString());
        $this->assertEquals(array('tag1', 'tag2'), $content->getPropertyValue($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));

        $oldRoute = $root->getNode('cmf/default/routes/de/news/test');
        $this->assertTrue($oldRoute->hasProperty('sulu:content'));
        $this->assertTrue($oldRoute->hasProperty('sulu:history'));
        $this->assertTrue($oldRoute->getPropertyValue('sulu:history'));

        // history should reference to new route
        $history = $oldRoute->getPropertyValue('sulu:content');
        $this->assertEquals($route->getIdentifier(), $history->getIdentifier());
    }

    public function testContentTree()
    {
        $data = array(
            array(
                'title' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news',
                'article' => 'asdfasdfasdf'
            ),
            array(
                'title' => 'Testnews-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2/test-1',
                'article' => 'default'
            )
        );

        // save root content
        $root = $this->mapper->save($data[0], 'overview', 'default', 'de', 1);

        // add a child content
        $this->mapper->save($data[1], 'overview', 'default', 'de', 1, true, null, $root->getUuid());
        $child = $this->mapper->save($data[2], 'overview', 'default', 'de', 1, true, null, $root->getUuid());
        $this->mapper->save($data[3], 'overview', 'default', 'de', 1, true, null, $child->getUuid());

        // check nodes
        $content = $this->mapper->loadByResourceLocator('/news', 'default', 'de');
        $this->assertEquals('News', $content->title);
        $this->assertTrue($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-1', 'default', 'de');
        $this->assertEquals('Testnews-1', $content->title);
        $this->assertFalse($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-2', 'default', 'de');
        $this->assertEquals('Testnews-2', $content->title);
        $this->assertTrue($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-2/test-1', 'default', 'de');
        $this->assertEquals('Testnews-2-1', $content->title);
        $this->assertFalse($content->getHasChildren());

        // check content repository
        $root = $this->session->getRootNode();
        $contentRootNode = $root->getNode('cmf/default/contents');

        $newsNode = $contentRootNode->getNode('news');
        $this->assertEquals(2, sizeof($newsNode->getNodes()));
        $this->assertEquals('News', $newsNode->getPropertyValue($this->languageNamespace . ':de-title'));

        $testNewsNode = $newsNode->getNode('testnews-1');
        $this->assertEquals('Testnews-1', $testNewsNode->getPropertyValue($this->languageNamespace . ':de-title'));

        $testNewsNode = $newsNode->getNode('testnews-2');
        $this->assertEquals(1, sizeof($testNewsNode->getNodes()));
        $this->assertEquals('Testnews-2', $testNewsNode->getPropertyValue($this->languageNamespace . ':de-title'));

        $subTestNewsNode = $testNewsNode->getNode('testnews-2-1');
        $this->assertEquals('Testnews-2-1', $subTestNewsNode->getPropertyValue($this->languageNamespace . ':de-title'));
    }

    private function prepareTreeTestData()
    {
        $data = array(
            array(
                'title' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news',
                'article' => 'asdfasdfasdf'
            ),
            array(
                'title' => 'Testnews-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2/test-1',
                'article' => 'default'
            )
        );

        $this->mapper->saveStartPage(array('title' => 'Start Page'), 'overview', 'default', 'de', 1);

        // save root content
        $result['root'] = $this->mapper->save($data[0], 'overview', 'default', 'de', 1);

        // add a child content
        $this->mapper->save($data[1], 'overview', 'default', 'de', 1, true, null, $result['root']->getUuid());
        $result['child'] = $this->mapper->save(
            $data[2],
            'overview',
            'default',
            'de',
            1,
            true,
            null,
            $result['root']->getUuid()
        );
        $result['subchild'] = $this->mapper->save(
            $data[3],
            'overview',
            'default',
            'de',
            1,
            true,
            null,
            $result['child']->getUuid()
        );

        return $result;
    }

    public function testLoadByParent()
    {
        $data = $this->prepareTreeTestData();
        /** @var StructureInterface $root */
        $root = $data['root'];
        /** @var StructureInterface $child */
        $child = $data['child'];

        // get root children
        $children = $this->mapper->loadByParent(null, 'default', 'de');
        $this->assertEquals(1, sizeof($children));

        $this->assertEquals('News', $children[0]->title);

        // get children from 'News'
        $rootChildren = $this->mapper->loadByParent($root->getUuid(), 'default', 'de');
        $this->assertEquals(2, sizeof($rootChildren));

        $this->assertEquals('Testnews-1', $rootChildren[0]->title);
        $this->assertEquals('Testnews-2', $rootChildren[1]->title);

        $testNewsChildren = $this->mapper->loadByParent($child->getUuid(), 'default', 'de');
        $this->assertEquals(1, sizeof($testNewsChildren));

        $this->assertEquals('Testnews-2-1', $testNewsChildren[0]->title);

        $nodes = $this->mapper->loadByParent($root->getUuid(), 'default', 'de', null);
        $this->assertEquals(3, sizeof($nodes));
    }

    public function testLoadByParentFlat()
    {
        $data = $this->prepareTreeTestData();
        /** @var StructureInterface $root */
        $root = $data['root'];
        /** @var StructureInterface $child */
        $child = $data['child'];

        $children = $this->mapper->loadByParent(null, 'default', 'de', 2, true);
        $this->assertEquals(3, sizeof($children));
        $this->assertEquals('News', $children[0]->title);
        $this->assertEquals('Testnews-1', $children[1]->title);
        $this->assertEquals('Testnews-2', $children[2]->title);

        $children = $this->mapper->loadByParent(null, 'default', 'de', 3, true);
        $this->assertEquals(4, sizeof($children));
        $this->assertEquals('News', $children[0]->title);
        $this->assertEquals('Testnews-1', $children[1]->title);
        $this->assertEquals('Testnews-2', $children[2]->title);
        $this->assertEquals('Testnews-2-1', $children[3]->title);

        $children = $this->mapper->loadByParent($child->getUuid(), 'default', 'de', 3, true);
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('Testnews-2-1', $children[0]->title);
    }

    public function testLoadByParentTree()
    {
        $data = $this->prepareTreeTestData();
        /** @var StructureInterface $root */
        $root = $data['root'];
        /** @var StructureInterface $child */
        $child = $data['child'];

        $children = $this->mapper->loadByParent(null, 'default', 'de', 2, false);
        // /News
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('News', $children[0]->title);
        $this->assertEquals('/news', $children[0]->path);

        // /News/Testnews-1
        $tmp = $children[0]->getChildren()[0];
        $this->assertEquals(0, sizeof($tmp->getChildren()));
        $this->assertEquals('Testnews-1', $tmp->title);
        $this->assertEquals('/news/testnews-1', $tmp->path);

        // /News/Testnews-2
        $tmp = $children[0]->getChildren()[1];
        $this->assertEquals(null, $tmp->getChildren());
        $this->assertTrue($tmp->getHasChildren());
        $this->assertEquals('Testnews-2', $tmp->title);
        $this->assertEquals('/news/testnews-2', $tmp->path);

        $children = $this->mapper->loadByParent(null, 'default', 'de', 3, false);
        // /News
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('News', $children[0]->title);
        $this->assertEquals('/news', $children[0]->path);

        // /News/Testnews-1
        $tmp = $children[0]->getChildren()[0];
        $this->assertEquals(0, sizeof($tmp->getChildren()));
        $this->assertEquals('Testnews-1', $tmp->title);
        $this->assertEquals('/news/testnews-1', $tmp->path);

        // /News/Testnews-2
        $tmp = $children[0]->getChildren()[1];
        $this->assertEquals(1, sizeof($tmp->getChildren()));
        $this->assertEquals('Testnews-2', $tmp->title);
        $this->assertEquals('/news/testnews-2', $tmp->path);

        // /News/Testnews-2/Testnews-2-1
        $tmp = $children[0]->getChildren()[1]->getChildren()[0];
        $this->assertEquals(null, $tmp->getChildren());
        $this->assertFalse($tmp->getHasChildren());
        $this->assertEquals('Testnews-2-1', $tmp->title);
        $this->assertEquals('/news/testnews-2/testnews-2-1', $tmp->path);

        $children = $this->mapper->loadByParent($child->getUuid(), 'default', 'de', 3, false);
        $this->assertEquals(1, sizeof($children));
        $this->assertEquals('Testnews-2-1', $children[0]->title);
        $this->assertEquals('/news/testnews-2/testnews-2-1', $children[0]->path);
    }

    public function testStartPage()
    {
        $data = array(
            'title' => 'startpage',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/',
            'article' => 'article'
        );

        $this->mapper->saveStartPage($data, 'overview', 'default', 'en', 1, false);

        $startPage = $this->mapper->loadStartPage('default', 'en');
        $this->assertEquals('startpage', $startPage->title);
        $this->assertEquals('/', $startPage->url);

        $data['title'] = 'new-startpage';

        $this->mapper->saveStartPage($data, 'overview', 'default', 'en', 1, false);

        $startPage = $this->mapper->loadStartPage('default', 'en');
        $this->assertEquals('new-startpage', $startPage->title);
        $this->assertEquals('/', $startPage->url);

        $startPage = $this->mapper->loadByResourceLocator('/', 'default', 'en');
        $this->assertEquals('new-startpage', $startPage->title);
        $this->assertEquals('/', $startPage->url);
    }

    public function testDelete()
    {
        $data = array(
            array(
                'title' => 'News',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news',
                'article' => 'asdfasdfasdf'
            ),
            array(
                'title' => 'Testnews-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-1',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2',
                'article' => 'default'
            ),
            array(
                'title' => 'Testnews-2-1',
                'tags' => array(
                    'tag1',
                    'tag2'
                ),
                'url' => '/news/test-2/test-1',
                'article' => 'default'
            )
        );

        // save root content
        $root = $this->mapper->save($data[0], 'overview', 'default', 'de', 1);

        // add a child content
        $this->mapper->save($data[1], 'overview', 'default', 'de', 1, true, null, $root->getUuid());
        $child = $this->mapper->save($data[2], 'overview', 'default', 'de', 1, true, null, $root->getUuid());
        $subChild = $this->mapper->save($data[3], 'overview', 'default', 'de', 1, true, null, $child->getUuid());

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_PRE_DELETE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeDeleteEvent')
            );
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(
                $this->equalTo(ContentEvents::NODE_POST_DELETE),
                $this->isInstanceOf('Sulu\Component\Content\Event\ContentNodeDeleteEvent')
            );
        // delete /news/test-2/test-1
        $this->mapper->delete($child->getUuid(), 'default');

        // check
        try {
            $this->mapper->load($child->getUuid(), 'default', 'de');
            $this->assertTrue(false, 'Node should not exists');
        } catch (ItemNotFoundException $ex) {
        }

        try {
            $this->mapper->load($subChild->getUuid(), 'default', 'de');
            $this->assertTrue(false, 'Node should not exists');
        } catch (ItemNotFoundException $ex) {
        }

        $result = $this->mapper->loadByParent($root->getUuid(), 'default', 'de');
        $this->assertEquals(1, sizeof($result));
    }

    public function testCleanUp()
    {
        $data = array(
            'title' => 'ä   ü ö   Ä Ü Ö',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/',
            'article' => 'article'
        );

        $structure = $this->mapper->save($data, 'overview', 'default', 'en', 1);

        $node = $this->session->getNodeByIdentifier($structure->getUuid());

        $this->assertEquals($node->getName(), 'ae-ue-oe-ae-ue-oe');
        $this->assertEquals($node->getPath(), '/cmf/default/contents/ae-ue-oe-ae-ue-oe');
    }

    public function testStateTransition()
    {
        // default state TEST
        $data1 = array(
            'title' => 't1'
        );
        $data1 = $this->mapper->save($data1, 'overview', 'default', 'de', 1);
        $this->assertEquals(StructureInterface::STATE_TEST, $data1->getNodeState());
        $this->assertNull($data1->getPublished());
        $this->assertFalse($data1->getPublishedState());

        // save with state PUBLISHED
        $data2 = array(
            'title' => 't2'
        );
        $data2 = $this->mapper->save($data2, 'overview', 'default', 'de', 1, true, null, null, 2);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $data2->getNodeState());
        $this->assertNotNull($data2->getPublished());
        $this->assertTrue($data2->getPublishedState());

        sleep(1);
        // change state from TEST to PUBLISHED
        $data3 = array(
            'title' => 't1'
        );
        $data3 = $this->mapper->save($data3, 'overview', 'default', 'de', 1, true, $data1->getUuid(), null, 2);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $data3->getNodeState());
        $this->assertNotNull($data3->getPublished());
        $this->assertTrue($data3->getPublishedState());
        $this->assertTrue($data3->getPublished() > $data2->getPublished());

        // change state from PUBLISHED to TEST (exception)
        $data4 = array(
            'title' => 't2'
        );
        $data4 = $this->mapper->save($data4, 'overview', 'default', 'de', 1, true, $data2->getUuid(), null, 1);
        $this->assertEquals(StructureInterface::STATE_TEST, $data4->getNodeState());
        $this->assertNull($data4->getPublished());
        $this->assertFalse($data4->getPublishedState());
    }

    public function testNavigationContext()
    {
        $navContexts = array('main', 'footer');
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default',
            'navContexts' => $navContexts
        );

        $result = $this->mapper->save($data, 'overview', 'default', 'de', 1);
        $content = $this->mapper->load($result->getUuid(), 'default', 'de');

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');
        $node = $route->getPropertyValue('sulu:content');

        $this->assertEquals($navContexts, $node->getPropertyValue($this->languageNamespace . ':de-navContexts'));
        $this->assertEquals($navContexts, $result->getNavContexts());
        $this->assertEquals($navContexts, $content->getNavContexts());

        $result = $this->mapper->save(
            $data,
            'overview',
            'default',
            'de',
            1,
            true,
            $result->getUuid(),
            null,
            null,
            false
        );
        $content = $this->mapper->load($result->getUuid(), 'default', 'de');
        $this->assertEquals($navContexts, $result->getNavContexts());
        $this->assertEquals($navContexts, $content->getNavContexts());

        $result = $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $result->getUuid());
        $content = $this->mapper->load($result->getUuid(), 'default', 'de');
        $this->assertEquals($navContexts, $result->getNavContexts());
        $this->assertEquals($navContexts, $content->getNavContexts());

        $result = $this->mapper->save(
            $data,
            'overview',
            'default',
            'de',
            1,
            true,
            $result->getUuid()
        );
        $content = $this->mapper->load($result->getUuid(), 'default', 'de');
        $this->assertEquals($navContexts, $result->getNavContexts());
        $this->assertEquals($navContexts, $content->getNavContexts());
    }

    public function testLoadBySql2()
    {
        $this->prepareTreeTestData();

        $result = $this->mapper->loadBySql2('SELECT * FROM [sulu:content]', 'de', 'default');

        $this->assertEquals(5, sizeof($result));

        $result = $this->mapper->loadBySql2('SELECT * FROM [sulu:content]', 'de', 'default', 2);

        $this->assertEquals(2, sizeof($result));
    }

    public function testSameName()
    {
        $data = array(
            'title' => 'Test',
            'tags' => array('tag1'),
            'url' => '/test-1',
            'article' => 'default'
        );

        $d1 = $this->mapper->save($data, 'overview', 'default', 'de', 1);
        $data['url'] = '/test-2';
        $data['tags'] = array('tag2');
        $d2 = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        $this->assertEquals('Test', $d1->title);
        $this->assertEquals(array('tag1'), $d1->tags);
        $this->assertEquals('Test', $d2->title);
        $this->assertEquals(array('tag2'), $d2->tags);

        $this->assertNotNull($this->session->getNode('/cmf/default/contents/test'));
        $this->assertNotNull($this->session->getNode('/cmf/default/contents/test-1'));

        $d1 = $this->mapper->load($d1->getUuid(), 'default', 'de');
        $d2 = $this->mapper->load($d2->getUuid(), 'default', 'de');

        $this->assertEquals('Test', $d1->title);
        $this->assertEquals(array('tag1'), $d1->tags);
        $this->assertEquals('Test', $d2->title);
        $this->assertEquals(array('tag2'), $d2->tags);
    }

    public function testBreadcrumb()
    {
        /** @var StructureInterface[] $data */
        $data = $this->prepareTreeTestData();

        /** @var BreadcrumbItemInterface[] $result */
        $result = $this->mapper->loadBreadcrumb($data['subchild']->getUuid(), 'de', 'default');

        $this->assertEquals(3, sizeof($result));
        $this->assertEquals(0, $result[0]->getDepth());
        $this->assertEquals('Start Page', $result[0]->getTitle());
        $this->assertEquals($this->sessionManager->getContentNode('default')->getIdentifier(), $result[0]->getUuid());

        $this->assertEquals(1, $result[1]->getDepth());
        $this->assertEquals('News', $result[1]->getTitle());
        $this->assertEquals($data['root']->getUuid(), $result[1]->getUuid());

        $this->assertEquals(2, $result[2]->getDepth());
        $this->assertEquals('Testnews-2', $result[2]->getTitle());
        $this->assertEquals($data['child']->getUuid(), $result[2]->getUuid());
    }

    private function prepareGhostTestData()
    {
        $data = array(
            array(
                'title' => 'News-EN',
                'url' => '/news'
            ),
            array(
                'title' => 'News-DE_AT',
                'url' => '/news'
            ),
            array(
                'title' => 'Products-EN',
                'url' => '/products'
            ),
            array(
                'title' => 'Products-DE',
                'url' => '/products'
            ),
            array(
                'title' => 'Team-DE',
                'url' => '/team-de'
            )
        );

        $this->mapper->saveStartPage(array('title' => 'Start Page'), 'overview', 'default', 'de', 1);

        // save root content
        $result['news-en'] = $this->mapper->save($data[0], 'overview', 'default', 'en', 1);
        $result['news-de_at'] = $this->mapper->save(
            $data[1],
            'overview',
            'default',
            'de_at',
            1,
            true,
            $result['news-en']->getUuid()
        );

        $result['products-en'] = $this->mapper->save(
            $data[2],
            'overview',
            'default',
            'en',
            1,
            true
        );

        $result['products-de'] = $this->mapper->save(
            $data[3],
            'overview',
            'default',
            'de',
            1,
            true,
            $result['products-en']->getUuid()
        );

        $result['team-de'] = $this->mapper->save(
            $data[4],
            'overview',
            'default',
            'de',
            1,
            true
        );

        return $result;
    }

    public function testGhost()
    {
        /** @var StructureInterface[] $data */
        $data = $this->prepareGhostTestData();

        // both pages exists in en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'en', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('en', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('title'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('en', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('title'));
        $this->assertNull($result[1]->getType());
        $this->assertEquals('en', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // both pages exists in en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'en', 1, true, false, true);
        $this->assertEquals(2, sizeof($result));
        $this->assertEquals('en', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('title'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('en', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('title'));
        $this->assertNull($result[1]->getType());

        // both pages are ghosts in en_us from en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'en_us', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('en_us', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('en', $result[0]->getType()->getValue());
        $this->assertEquals('en_us', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[1]->getType()->getName());
        $this->assertEquals('en', $result[1]->getType()->getValue());
        $this->assertEquals('en_us', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // no page exists in en_us without ghosts
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'en_us', 1, true, false, true);
        $this->assertEquals(0, sizeof($result));

        // one page not exists in de (ghost from de_at), other exists in de
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'de', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('de', $result[0]->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result[0]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('de_at', $result[0]->getType()->getValue());
        $this->assertEquals('de', $result[1]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[1]->getPropertyValue('title'));
        $this->assertNull($result[1]->getType());
        $this->assertEquals('de', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertNull($result[2]->getType());

        // one page exists in de (without ghosts)
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'de', 1, true, false, true);
        $this->assertEquals(2, sizeof($result));
        $this->assertEquals('de', $result[0]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[0]->getPropertyValue('title'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('de', $result[1]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[1]->getPropertyValue('title'));
        $this->assertNull($result[1]->getType());

        // one page not exists in de_at (ghost from de), other exists in de_at
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'de', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('de', $result[0]->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result[0]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('de_at', $result[0]->getType()->getValue());
        $this->assertEquals('de', $result[1]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[1]->getPropertyValue('title'));
        $this->assertNull($result[1]->getType());
        $this->assertEquals('de', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertNull($result[2]->getType());

        // one page not exists in de_at (ghost from de), other exists in de_at
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'de_at', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('de_at', $result[0]->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result[0]->getPropertyValue('title'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('de_at', $result[1]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[1]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[1]->getType()->getName());
        $this->assertEquals('de', $result[1]->getType()->getValue());
        $this->assertEquals('de_at', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // both pages are ghosts in es from en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'es', 1, true, false, false);
        $this->assertEquals(3, sizeof($result));
        $this->assertEquals('es', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('en', $result[0]->getType()->getValue());
        $this->assertEquals('es', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[1]->getType()->getName());
        $this->assertEquals('en', $result[1]->getType()->getValue());
        $this->assertEquals('es', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // no page exists in en_us without ghosts
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'default', 'es', 1, true, false, true);
        $this->assertEquals(0, sizeof($result));

        // load content as de -> no ghost content
        $result = $this->mapper->load($data['news-de_at']->getUuid(), 'default', 'de', false);
        $this->assertEquals('de', $result->getLanguageCode());
        $this->assertEquals('', $result->getPropertyValue('title'));
        $this->assertNull($result->getType());

        // load content as de -> load ghost content
        $result = $this->mapper->load($data['news-de_at']->getUuid(), 'default', 'de', true);
        $this->assertEquals('de', $result->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result->getPropertyValue('title'));
        $this->assertEquals('ghost', $result->getType()->getName());
        $this->assertEquals('de_at', $result->getType()->getValue());

        // load only in german available page in english
        $result = $this->mapper->load($data['team-de']->getUuid(), 'default', 'en', true);
        $this->assertEquals('en', $result->getLanguageCode());
        $this->assertEquals('Team-DE', $result->getPropertyValue('title'));
        $this->assertEquals('ghost', $result->getType()->getName());
        $this->assertEquals('de', $result->getType()->getValue());
    }

    public function prepareLoadShadowData()
    {
        $data = array(
            array(
                'title' => 'hello',
                'article' => 'German',
                'shadow' => false,
                'language' => 'de',
                'is_shadow' => false,
                'shadow_base_language' => null,
            ),
            array(
                'title' => 'hello',
                'article' => 'Austrian',
                'shadow' => true,
                'language' => 'de_at',
                'is_shadow' => true,
                'shadow_base_language' => 'de',
            ),
            array(
                'title' => 'random',
                'article' => 'Auslander',
                'shadow' => true,
                'language' => 'de_at',
                'is_shadow' => false,
                'shadow_base_language' => 'de',
            ),
        );

        $result = array();
        foreach ($data as $dataItem) {
            $result[$dataItem['title']][$dataItem['language']] = $this->mapper->save(
                array(
                    'title' => $dataItem['title'],
                    'url' => '/' . $dataItem['title'],
                    'article' => $dataItem['article'],
                ),
                'overview',
                'default',
                $dataItem['language'],
                1,
                true,
                isset($result[$dataItem['title']]['de']) ? $result[$dataItem['title']]['de']->getUuid() : null,
                null,
                null,
                $dataItem['is_shadow'],
                $dataItem['shadow_base_language']
            );
        }

        return $result;
    }

    public function testLoadShadow()
    {
        $result = $this->prepareLoadShadowData();

        $uuid = $result['hello']['de']->getUuid();

        $structure = $this->mapper->load($uuid, 'default', 'de');
        $this->assertFalse($structure->getIsShadow());
        $this->assertEquals('German', $structure->getProperty('article')->getValue());

        $structure = $this->mapper->load($uuid, 'default', 'de_at', false);
        $this->assertTrue($structure->getIsShadow());
        $this->assertEquals('de', $structure->getShadowBaseLanguage());
        $this->assertEquals('de_at', $structure->getLanguageCode());

        // this is a shadow, so it should be "German" not "Austrian"
        $this->assertEquals('German', $structure->getProperty('article')->getValue());
        $this->assertEquals(array('de' => 'de_at'), $structure->getEnabledShadowLanguages());

        // the node has only one concrete language
        $this->assertEquals(array('de'), $structure->getConcreteLanguages());
    }

    public function testTranslatedResourceLocator()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test',
            'article' => 'default'
        );
        $structure = $this->mapper->save($data, 'overview', 'default', 'en', 1);
        $content = $this->mapper->load($structure->getUuid(), 'default', 'en');
        $contentDE = $this->mapper->load($structure->getUuid(), 'default', 'de');
        $nodeEN = $this->session->getNode('/cmf/default/routes/en/news/test');
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals('', $contentDE->url);
        $this->assertNotNull($nodeEN);
        $this->assertFalse($nodeEN->getPropertyValue('sulu:history'));
        $this->assertFalse($this->session->getNode('/cmf/default/routes/de')->hasNode('news/test'));
        $this->assertNotNull($this->languageRoutes['en']->getNode('news/test'));

        $data = array(
            'title' => 'Testname',
            'url' => '/neuigkeiten/test'
        );
        $structure = $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $structure->getUuid());
        $content = $this->mapper->load($structure->getUuid(), 'default', 'de');
        $contentEN = $this->mapper->load($structure->getUuid(), 'default', 'en');
        $nodeDE = $this->session->getNode('/cmf/default/routes/de/neuigkeiten/test');
        $this->assertEquals('/neuigkeiten/test', $content->url);
        $this->assertEquals('/news/test', $contentEN->url);
        $this->assertNotNull($nodeDE);
        $this->assertFalse($nodeDE->getPropertyValue('sulu:history'));
        $this->assertTrue($this->session->getNode('/cmf/default/routes/de')->hasNode('neuigkeiten/test'));
        $this->assertFalse($this->session->getNode('/cmf/default/routes/de')->hasNode('news/test'));
        $this->assertFalse($this->session->getNode('/cmf/default/routes/en')->hasNode('neuigkeiten/test'));
        $this->assertTrue($this->session->getNode('/cmf/default/routes/en')->hasNode('news/test'));
        $this->assertNotNull($this->languageRoutes['de']->getNode('neuigkeiten/test'));
    }

    public function testBlock()
    {
        $data = array(
            'title' => 'Test-name',
            'url' => '/test',
            'block1' => array(
                array(
                    'type' => 'default',
                    'title' => 'Block-name-1',
                    'article' => 'Block-Article-1'
                ),
                array(
                    'type' => 'default',
                    'title' => 'Block-name-2',
                    'article' => 'Block-Article-2'
                )
            )
        );

        // check save
        $structure = $this->mapper->save($data, 'complex', 'default', 'de', 1);
        $result = $structure->toArray();
        $this->assertEquals(
            $data,
            array(
                'title' => $result['title'],
                'url' => $result['url'],
                'block1' => $result['block1']
            )
        );

        // change sorting
        $tmp = $data['block1'][0];
        $data['block1'][0] = $data['block1'][1];
        $data['block1'][1] = $tmp;
        $structure = $this->mapper->save($data, 'complex', 'default', 'de', 1, true, $structure->getUuid());
        $result = $structure->toArray();
        $this->assertEquals(
            $data,
            array(
                'title' => $result['title'],
                'url' => $result['url'],
                'block1' => $result['block1']
            )
        );

        // check load
        $structure = $this->mapper->load($structure->getUuid(), 'default', 'de');
        $result = $structure->toArray();
        $this->assertEquals(
            $data,
            array(
                'title' => $result['title'],
                'url' => $result['url'],
                'block1' => $result['block1']
            )
        );
    }

    public function testMultilingual()
    {
        // change simple content
        $dataDe = array(
            'title' => 'Testname-DE',
            'blog' => 'German',
            'url' => '/news/test'
        );

        // update content
        $structureDe = $this->mapper->save($dataDe, 'default', 'default', 'de', 1);

        $dataEn = array(
            'title' => 'Testname-EN',
            'blog' => 'English'
        );
        $structureEn = $this->mapper->save($dataEn, 'default', 'default', 'en', 1, true, $structureDe->getUuid());
        $structureDe = $this->mapper->load($structureDe->getUuid(), 'default', 'de');

        // check data
        $this->assertNotEquals($structureDe->getPropertyValue('title'), $structureEn->getPropertyValue('title'));
        $this->assertEquals($structureDe->getPropertyValue('blog'), $structureEn->getPropertyValue('blog'));

        $this->assertEquals($dataEn['title'], $structureEn->getPropertyValue('title'));
        $this->assertEquals($dataEn['blog'], $structureEn->getPropertyValue('blog'));

        $this->assertEquals($dataDe['title'], $structureDe->getPropertyValue('title'));
        // En has overritten german content
        $this->assertEquals($dataEn['blog'], $structureDe->getPropertyValue('blog'));

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/default/routes/de/news/test');
        /** @var NodeInterface $content */
        $content = $route->getPropertyValue('sulu:content');
        $this->assertEquals($dataDe['title'], $content->getPropertyValue($this->languageNamespace . ':de-title'));
        $this->assertNotEquals($dataDe['blog'], $content->getPropertyValue('blog'));
        $this->assertEquals($dataEn['title'], $content->getPropertyValue($this->languageNamespace . ':en-title'));
        $this->assertEquals($dataEn['blog'], $content->getPropertyValue('blog'));

        $this->assertFalse($content->hasProperty($this->languageNamespace . ':de-blog'));
        $this->assertFalse($content->hasProperty($this->languageNamespace . ':en-blog'));
        $this->assertFalse($content->hasProperty('title'));
    }

    public function testMandatory()
    {
        $data = array(
            'title' => 'Testname',
            'url' => '/news/test'
        );

        $this->setExpectedException(
            '\Sulu\Component\Content\Exception\MandatoryPropertyException',
            'Data for mandatory property blog in template mandatory not found'
        );
        $this->mapper->save($data, 'mandatory', 'default', 'de', 1);
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareBigTreeTestData()
    {
        $data = array(
            array(
                'data' => array(
                    'title' => 'Products',
                    'url' => '/products'
                ),
                'children' => array(
                    array(
                        'data' => array(
                            'title' => 'Products1',
                            'url' => '/products/products-1'
                        ),
                        'children' => array()
                    )
                )
            ),
            array(
                'data' => array(
                    'title' => 'News',
                    'url' => '/news'
                ),
                'children' => array(
                    array(
                        'data' => array(
                            'title' => 'News-1',
                            'url' => '/news/news-1'
                        ),
                        'children' => array(
                            array(
                                'data' => array(
                                    'title' => 'SubNews-1',
                                    'url' => '/news/news-1/subnews-1'
                                ),
                                'children' => array()
                            ),
                            array(
                                'data' => array(
                                    'title' => 'SubNews-2',
                                    'url' => '/news/news-1/subnews-2'
                                ),
                                'children' => array()
                            ),
                            array(
                                'data' => array(
                                    'title' => 'SubNews-3',
                                    'url' => '/news/news-1/subnews-3'
                                ),
                                'children' => array(
                                    array(
                                        'data' => array(
                                            'title' => 'SubSubNews-1',
                                            'url' => '/news/news-1/subnews-3/subsubnews-1'
                                        ),
                                        'children' => array()
                                    ),
                                    array(
                                        'data' => array(
                                            'title' => 'SubSubNews-2',
                                            'url' => '/news/news-1/subnews-3/subsubnews-2'
                                        ),
                                        'children' => array()
                                    ),
                                    array(
                                        'data' => array(
                                            'title' => 'SubSubNews-3',
                                            'url' => '/news/news-1/subnews-3/subsubnews-3'
                                        ),
                                        'children' => array()
                                    )
                                )
                            )
                        )
                    ),
                    array(
                        'data' => array(
                            'title' => 'News-2',
                            'url' => '/news/news-2'
                        ),
                        'children' => array()
                    ),
                    array(
                        'data' => array(
                            'title' => 'News-3',
                            'url' => '/news/news-3'
                        ),
                        'children' => array()
                    ),
                )
            ),
            array(
                'data' => array(
                    'title' => 'About Us',
                    'url' => '/about-us'
                ),
                'children' => array()
            ),
        );

        return $this->saveData($data);
    }

    private function saveData($data, $uuid = null)
    {
        $result = array();
        foreach ($data as $item) {
            $itemStructure = $this->mapper->save($item['data'], 'overview', 'default', 'de', 1, true, null, $uuid);
            $itemStructure->setChildren($this->saveData($item['children'], $itemStructure->getUuid()));

            $result[] = $itemStructure;
        }

        return $result;
    }

    public function testLoadTree()
    {
        $data = $this->prepareBigTreeTestData();
        $child = $data[1]->getChildren()[0]->getChildren()[2]->getChildren()[1];

        $result = $this->mapper->loadTreeByPath($child->getPath(), 'de', 'default');
        $this->checkTreeResult($result);

        $result = $this->mapper->loadTreeByUuid($child->getUuid(), 'de', 'default');
        $this->checkTreeResult($result);
    }

    public function testLanguageCopy()
    {
        $data = $this->prepareSinglePageTestData();

        $this->mapper->copyLanguage($data->getUuid(), 1, 'default', 'de', 'en');

        $result = $this->mapper->load($data->getUuid(), 'default', 'en');

        $this->assertEquals('Page-1', $result->title);
        $this->assertEquals('/page-1', $result->url);
    }

    public function testMultipleLanguagesCopy()
    {
        $data = $this->prepareSinglePageTestData();

        $this->mapper->copyLanguage($data->getUuid(), 1, 'default', 'de', array('en', 'de_at'));

        $result = $this->mapper->load($data->getUuid(), 'default', 'en');

        $this->assertEquals('Page-1', $result->title);
        $this->assertEquals('/page-1', $result->url);

        $result = $this->mapper->load($data->getUuid(), 'default', 'de_at');

        $this->assertEquals('Page-1', $result->title);
        $this->assertEquals('/page-1', $result->url);
    }

    private function checkTreeResult($result)
    {
        // layer 0
        $this->assertEquals(3, sizeof($result));

        // layer 1
        $layer1 = $result[1]->getChildren();
        $this->assertEquals(0, sizeof($result[0]->getChildren()));
        $this->assertEquals('Products', $result[0]->title);
        $this->assertTrue($result[0]->getHasChildren());

        $this->assertEquals(3, sizeof($result[1]->getChildren()));
        $this->assertEquals('News', $result[1]->title);
        $this->assertTrue($result[1]->getHasChildren());

        $this->assertEquals(0, sizeof($result[2]->getChildren()));
        $this->assertEquals('About Us', $result[2]->title);
        $this->assertFalse($result[2]->getHasChildren());

        // layer 2
        $layer2 = $layer1[0]->getChildren();
        $this->assertEquals(3, sizeof($layer1[0]->getChildren()));
        $this->assertEquals('News-1', $layer1[0]->title);
        $this->assertTrue($layer1[0]->getHasChildren());

        $this->assertEquals(0, sizeof($layer1[1]->getChildren()));
        $this->assertEquals('News-2', $layer1[1]->title);
        $this->assertFalse($layer1[1]->getHasChildren());

        $this->assertEquals(0, sizeof($layer1[2]->getChildren()));
        $this->assertEquals('News-3', $layer1[2]->title);
        $this->assertFalse($layer1[2]->getHasChildren());

        // layer 3
        $layer3 = $layer2[2]->getChildren();
        $this->assertEquals(0, sizeof($layer2[0]->getChildren()));
        $this->assertEquals('SubNews-1', $layer2[0]->title);
        $this->assertFalse($layer2[0]->getHasChildren());

        $this->assertEquals(0, sizeof($layer2[1]->getChildren()));
        $this->assertEquals('SubNews-2', $layer2[1]->title);
        $this->assertFalse($layer2[1]->getHasChildren());

        $this->assertEquals(3, sizeof($layer2[2]->getChildren()));
        $this->assertEquals('SubNews-3', $layer2[2]->title);
        $this->assertTrue($layer2[2]->getHasChildren());

        // layer 4
        $this->assertEquals(0, sizeof($layer3[0]->getChildren()));
        $this->assertEquals('SubSubNews-1', $layer3[0]->title);
        $this->assertFalse($layer3[0]->getHasChildren());

        $this->assertEquals(0, sizeof($layer3[1]->getChildren()));
        $this->assertEquals('SubSubNews-2', $layer3[1]->title);
        $this->assertFalse($layer3[1]->getHasChildren());

        $this->assertEquals(0, sizeof($layer3[2]->getChildren()));
        $this->assertEquals('SubSubNews-3', $layer3[2]->title);
        $this->assertFalse($layer3[2]->getHasChildren());
    }

    public function testLoadEmptyTreeExcludedGhosts()
    {
        $data = $this->prepareBigTreeTestData();
        $child = $data[1]->getChildren()[0]->getChildren()[2]->getChildren()[1];

        $result = $this->mapper->loadTreeByUuid($child->getUuid(), 'en', 'default', true, true);

        $this->assertCount(0, $result);
    }

    public function testSection()
    {
        $data = array(
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test'
        );

        $structure = $this->mapper->save($data, 'section', 'default', 'en', 1);
        $resultSave = $structure->toArray();

        $this->assertEquals('/test', $resultSave['path']);
        $this->assertEquals('section', $resultSave['template']);
        $this->assertEquals('Test', $resultSave['title']);
        $this->assertEquals('Thats a good test', $resultSave['blog']);
        $this->assertEquals('/test/test', $resultSave['url']);

        $structure = $this->mapper->load($structure->getUuid(), 'default', 'en');
        $resultLoad = $structure->toArray();

        $this->assertEquals('/test', $resultLoad['path']);
        $this->assertEquals('section', $resultLoad['template']);
        $this->assertEquals('Test', $resultLoad['title']);
        $this->assertEquals('Thats a good test', $resultLoad['blog']);
        $this->assertEquals('/test/test', $resultLoad['url']);
    }

    public function testCompleteExtensions()
    {
        $data = array(
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test',
            'ext' => array(
                'test1' => array(
                    'a' => 'That´s a test',
                    'b' => 'That´s a second test'
                )
            )
        );

        $structure = $structure = $this->mapper->save($data, 'extension', 'default', 'en', 1);
        $result = $structure->toArray();

        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals($data['ext']['test1'], $result['ext']['test1']);
        $this->assertEquals(
            array(
                'a' => '',
                'b' => ''
            ),
            $result['ext']['test2']
        );

        $data = array(
            'title' => 'Test',
            'blog' => 'Thats a good test',
            'ext' => array(
                'test2' => array(
                    'a' => 'a',
                    'b' => 'b'
                )
            )
        );

        $structure = $this->mapper->save(
            $data,
            'extension',
            'default',
            'en',
            1,
            true,
            $structure->getUuid()
        );
        $result = $structure->toArray();

        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(
            array(
                'a' => 'That´s a test',
                'b' => 'That´s a second test'
            ),
            $result['ext']['test1']
        );
        $this->assertEquals(
            array(
                'a' => 'a',
                'b' => 'b'
            ),
            $result['ext']['test2']
        );

        $structure = $this->mapper->load($structure->getUuid(), 'default', 'en');
        $result = $structure->toArray();

        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(
            array(
                'a' => 'That´s a test',
                'b' => 'That´s a second test'
            ),
            $result['ext']['test1']
        );
        $this->assertEquals(
            array(
                'a' => 'a',
                'b' => 'b'
            ),
            $result['ext']['test2']
        );
    }

    public function testExtensionsLocalized()
    {
        $data = array(
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test',
            'ext' => array(
                'test1' => array(
                    'a' => 'That´s a test',
                    'b' => 'That´s a second test'
                ),
                'test2' => array(
                    'a' => 'That´s a test',
                    'b' => 'That´s a second test'
                )
            )
        );

        $structure = $structure = $this->mapper->save($data, 'extension', 'default', 'en', 1);
        $result = $structure->toArray();

        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(
            array(
                'a' => 'That´s a test',
                'b' => 'That´s a second test'
            ),
            $result['ext']['test1']
        );
        $this->assertEquals(
            array(
                'a' => 'That´s a test',
                'b' => 'That´s a second test'
            ),
            $result['ext']['test2']
        );

        $data = array(
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Das ist ein guter Test',
            'ext' => array(
                'test1' => array(
                    'a' => 'Das ist ein Test',
                    'b' => 'Das ist ein zweiter Test'
                ),
                'test2' => array(
                    'a' => 'Das ist ein Test',
                    'b' => 'Das ist ein zweiter Test'
                )
            )
        );

        $structure = $structure = $this->mapper->save(
            $data,
            'extension',
            'default',
            'de',
            1,
            true,
            $structure->getUuid()
        );
        $result = $structure->toArray();

        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(
            array(
                'a' => 'Das ist ein Test',
                'b' => 'Das ist ein zweiter Test'
            ),
            $result['ext']['test1']
        );
        $this->assertEquals(
            array(
                'a' => 'Das ist ein Test',
                'b' => 'Das ist ein zweiter Test'
            ),
            $result['ext']['test2']
        );

        $resultDE = $this->mapper->load($structure->getUuid(), 'default', 'de')->toArray();
        $this->assertEquals('Test', $resultDE['title']);
        $this->assertEquals('Das ist ein guter Test', $resultDE['blog']);
        $this->assertEquals(
            array(
                'a' => 'Das ist ein Test',
                'b' => 'Das ist ein zweiter Test'
            ),
            $resultDE['ext']['test1']
        );
        $this->assertEquals(
            array(
                'a' => 'Das ist ein Test',
                'b' => 'Das ist ein zweiter Test'
            ),
            $resultDE['ext']['test2']
        );

        $resultEN = $this->mapper->load($structure->getUuid(), 'default', 'en')->toArray();
        $this->assertEquals('Test', $resultEN['title']);
        $this->assertEquals('Thats a good test', $resultEN['blog']);
        $this->assertEquals(
            array(
                'a' => 'That´s a test',
                'b' => 'That´s a second test'
            ),
            $resultEN['ext']['test1']
        );
        $this->assertEquals(
            array(
                'a' => 'That´s a test',
                'b' => 'That´s a second test'
            ),
            $resultEN['ext']['test2']
        );
    }

    public function testExtensions()
    {
        $data = array(
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test'
        );

        $structure = $this->mapper->save($data, 'extension', 'default', 'en', 1);
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Thats a good test', $result['blog']);

        $this->assertEquals(array('a' => '', 'b' => ''), $result['ext']['test1']);
        $this->assertEquals(array('a' => '', 'b' => ''), $result['ext']['test2']);

        $dataTest1EN = array(
            'a' => 'en test1 a',
            'b' => 'en test1 b'
        );

        $structure = $this->mapper->saveExtension($structure->getUuid(), $dataTest1EN, 'test1', 'default', 'en', 1);
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Thats a good test', $result['blog']);

        $this->assertEquals($dataTest1EN, $result['ext']['test1']);
        $this->assertEquals(array('a' => '', 'b' => ''), $result['ext']['test2']);

        $structure = $this->mapper->load($structure->getUuid(), 'default', 'en');
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Thats a good test', $result['blog']);

        $this->assertEquals($dataTest1EN, $result['ext']['test1']);
        $this->assertEquals(array('a' => '', 'b' => ''), $result['ext']['test2']);

        $dataTest2EN = array(
            'a' => 'en test2 a',
            'b' => 'en test2 b'
        );

        $structure = $this->mapper->saveExtension($structure->getUuid(), $dataTest2EN, 'test2', 'default', 'en', 1);
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Thats a good test', $result['blog']);

        $this->assertEquals($dataTest1EN, $result['ext']['test1']);
        $this->assertEquals($dataTest2EN, $result['ext']['test2']);

        $structure = $this->mapper->load($structure->getUuid(), 'default', 'en');
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Thats a good test', $result['blog']);

        $this->assertEquals($dataTest1EN, $result['ext']['test1']);
        $this->assertEquals($dataTest2EN, $result['ext']['test2']);

        $data = array(
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Das ist ein guter Test'
        );

        $structure = $this->mapper->save($data, 'extension', 'default', 'de', 1, true, $structure->getUuid());
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Das ist ein guter Test', $result['blog']);

        $this->assertEquals(array('a' => '', 'b' => ''), $result['ext']['test1']);
        $this->assertEquals(array('a' => '', 'b' => ''), $result['ext']['test2']);

        $dataTest2DE = array(
            'a' => 'de test2 a',
            'b' => 'de test2 b'
        );

        $structure = $this->mapper->saveExtension($structure->getUuid(), $dataTest2DE, 'test2', 'default', 'de', 1);
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Das ist ein guter Test', $result['blog']);

        $this->assertEquals(array('a' => '', 'b' => ''), $result['ext']['test1']);
        $this->assertEquals($dataTest2DE, $result['ext']['test2']);

        $structure = $this->mapper->load($structure->getUuid(), 'default', 'de');
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Das ist ein guter Test', $result['blog']);

        $this->assertEquals(array('a' => '', 'b' => ''), $result['ext']['test1']);
        $this->assertEquals($dataTest2DE, $result['ext']['test2']);
    }

    public function testTranslatedNodeNotFound()
    {
        $data = array(
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test'
        );

        $structure = $this->mapper->save($data, 'extension', 'default', 'en', 1);
        $dataTest2DE = array(
            'a' => 'de test2 a',
            'b' => 'de test2 b'
        );

        $this->setExpectedException(
            'Sulu\Component\Content\Exception\TranslatedNodeNotFoundException',
            'Node "' . $structure->getUuid() . '" not found in localization "de"'
        );

        $this->mapper->saveExtension($structure->getUuid(), $dataTest2DE, 'test2', 'default', 'de', 1);
    }

    public function testGetRlAndName()
    {
        $data1 = array(
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test'
        );
        $structure1 = $this->mapper->save($data1, 'extension', 'default', 'en', 1);

        $data2 = array(
            'title' => 'Test',
            'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
            'internal' => $structure1->getUuid()
        );
        $structure2 = $this->mapper->save($data2, 'internal-link', 'default', 'en', 1);

        $this->assertEquals(Structure::NODE_TYPE_INTERNAL_LINK, $structure2->getNodeType());
        $this->assertEquals($structure1->getUuid(), $structure2->getInternalLinkContent()->getUuid());

        $this->assertEquals($structure1->getResourceLocator(), $structure2->getResourceLocator());
        $this->assertEquals($structure1->getNodeName(), $structure2->getNodeName());

        $data3 = array(
            'title' => 'Test',
            'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK,
            'external' => 'www.google.at'
        );
        $structure3 = $this->mapper->save($data3, 'external-link', 'default', 'en', 1);

        $this->assertEquals(Structure::NODE_TYPE_EXTERNAL_LINK, $structure3->getNodeType());

        $this->assertEquals('http://www.google.at', $structure3->getResourceLocator());
        $this->assertEquals('Test', $structure3->getNodeName());
    }

    private function prepareSinglePageTestData()
    {
        $this->mapper->saveStartPage(array('title' => 'Start Page'), 'overview', 'default', 'de', 1);
        $this->mapper->saveStartPage(array('title' => 'Start Page'), 'overview', 'default', 'en', 1);

        $data = array(
            'title' => 'Page-1',
            'url' => '/page-1'
        );

        $data = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        return $data;
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareCopyMoveTestData()
    {
        $data = array(
            array(
                'title' => 'Page-1',
                'url' => '/page-1'
            ),
            array(
                'title' => 'Sub',
                'url' => '/page-1/sub'
            ),
            array(
                'title' => 'Sub',
                'url' => '/page-1/sub-1'
            ),
            array(
                'title' => 'Page-2',
                'url' => '/page-2'
            ),
            array(
                'title' => 'Sub',
                'url' => '/page-2/sub'
            ),
            array(
                'title' => 'Sub',
                'url' => '/page-2/sub-1'
            ),
            array(
                'title' => 'SubPage',
                'url' => '/page-2/subpage'
            ),
            array(
                'title' => 'SubSubPage',
                'url' => '/page-2/subpage/subpage'
            ),
            array(
                'title' => 'SubSubSubPage',
                'url' => '/page-2/subpage/subpage/subpage'
            ),
            array(
                'title' => 'SubPage',
                'url' => '/page-2/sub-1/subpage'
            ),
            array(
                'title' => 'SubSubPage',
                'url' => '/page-2/sub-1/subpage/subpage'
            )
        );

        $this->mapper->saveStartPage(array('title' => 'Start Page'), 'overview', 'default', 'de', 1);

        // save content
        $data[0] = $this->mapper->save($data[0], 'overview', 'default', 'de', 1);
        $data[1] = $this->mapper->save($data[1], 'overview', 'default', 'de', 1, true, null, $data[0]->getUuid());
        $data[2] = $this->mapper->save($data[2], 'overview', 'default', 'de', 1, true, null, $data[0]->getUuid());
        $data[3] = $this->mapper->save($data[3], 'overview', 'default', 'de', 1);
        $data[4] = $this->mapper->save($data[4], 'overview', 'default', 'de', 1, true, null, $data[3]->getUuid());
        $data[5] = $this->mapper->save($data[5], 'overview', 'default', 'de', 1, true, null, $data[3]->getUuid());
        $data[6] = $this->mapper->save($data[6], 'overview', 'default', 'de', 1, true, null, $data[3]->getUuid());
        $data[7] = $this->mapper->save($data[7], 'overview', 'default', 'de', 1, true, null, $data[6]->getUuid());
        $data[8] = $this->mapper->save($data[8], 'overview', 'default', 'de', 1, true, null, $data[7]->getUuid());
        $data[9] = $this->mapper->save($data[9], 'overview', 'default', 'de', 1, true, null, $data[5]->getUuid());
        $data[10] = $this->mapper->save($data[10], 'overview', 'default', 'de', 1, true, null, $data[9]->getUuid());

        return $data;
    }

    public function testMove()
    {
        $data = $this->prepareCopyMoveTestData();

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'default', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'default', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'default', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);

        $result = $this->mapper->move($data[6]->getUuid(), $data[0]->getUuid(), 2, 'default', 'de');

        $this->assertEquals($data[6]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/subpage', $result->getPath());
        $this->assertEquals('/page-1/subpage', $result->url);
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(3, sizeof($test));

        $test = $this->mapper->loadByParent($data[6]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(1, sizeof($test));

        $test = $this->mapper->loadByParent($data[7]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(1, sizeof($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(2, sizeof($test));

        $test = $this->mapper->load($data[6]->getUuid(), 'default', 'de', 4);
        $this->assertEquals('/page-1/subpage', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'default', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'default', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'default', 'de');
        $this->assertEquals('/page-1/subpage', $page2Sub->url);
        $this->assertEquals('/page-1/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-1/subpage/subpage/subpage', $page2SubSubSub->url);
    }

    public function testRenameRlp()
    {
        $data = $this->prepareCopyMoveTestData();

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'default', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'default', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'default', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);

        $uuid = $data[6]->getUuid();
        $data[6] = array(
            'title' => 'SubPage',
            'url' => '/page-2/test'
        );
        $result = $data[6] = $this->mapper->save($data[6], 'overview', 'default', 'de', 2, true, $uuid);

        $this->assertEquals($data[6]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-2/subpage', $result->getPath());
        $this->assertEquals('/page-2/test', $result->url);
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(2, sizeof($test));

        $test = $this->mapper->loadByParent($data[6]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(1, sizeof($test));

        $test = $this->mapper->loadByParent($data[7]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(1, sizeof($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(3, sizeof($test));

        $test = $this->mapper->load($data[6]->getUuid(), 'default', 'de', 4);
        $this->assertEquals('/page-2/test', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'default', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'default', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'default', 'de');
        $this->assertEquals('/page-2/test', $page2Sub->url);
        $this->assertEquals('/page-2/test/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/test/subpage/subpage', $page2SubSubSub->url);
    }

    public function testChangeSnippetTemplate()
    {
        $data = $this->prepareCopyMoveTestData();

        $result = $this->mapper->move($data[6]->getUuid(), $data[0]->getUuid(), 2, 'default', 'de');

        $this->assertEquals($data[6]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/subpage', $result->getPath());
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(3, sizeof($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(2, sizeof($test));

        $test = $this->mapper->load($data[6]->getUuid(), 'default', 'de', 4);
        $this->assertEquals('/page-1/subpage', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());
    }

    public function testMoveExistingName()
    {
        $data = $this->prepareCopyMoveTestData();

        $result = $this->mapper->move($data[5]->getUuid(), $data[0]->getUuid(), 2, 'default', 'de');

        $this->assertEquals($data[5]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/sub-2', $result->getPath());
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(3, sizeof($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(2, sizeof($test));

        $test = $this->mapper->load($data[5]->getUuid(), 'default', 'de', 4);
        $this->assertEquals('/page-1/sub-1-1', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());
    }

    public function testMoveGhostPage()
    {
        $data = $this->prepareCopyMoveTestData();

        $result = $this->mapper->move($data[5]->getUuid(), $data[0]->getUuid(), 2, 'default', 'en');

        $this->assertEquals($data[5]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/sub-2', $result->getPath());
        $this->assertEquals(2, $result->getChanger());

        $result = $this->mapper->load($result->getUuid(), 'default', 'en', true);

        $this->assertEquals($data[5]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/sub-2', $result->getPath());
        $this->assertEquals(1, $result->getChanger());
        $this->assertEquals('ghost', $result->getType()->getName());
        $this->assertEquals('de', $result->getType()->getValue());

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(3, sizeof($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(2, sizeof($test));

        $test = $this->mapper->load($data[5]->getUuid(), 'default', 'de', 4);
        $this->assertEquals('/page-1/sub-1-1', $test->getResourceLocator());
        $this->assertEquals(1, $test->getChanger());
    }

    public function testCopy()
    {
        $data = $this->prepareCopyMoveTestData();

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'default', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'default', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'default', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);

        $result = $this->mapper->copy($data[6]->getUuid(), $data[0]->getUuid(), 2, 'default', 'de');

        $this->assertNotEquals($data[6]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/subpage', $result->getPath());
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($result->getUuid(), 'default', 'de', 2);
        $this->assertCount(2, $test);
        $this->assertEquals('/page-1/subpage/subsubpage', $test[0]->url);
        $this->assertEquals('/page-1/subpage/subsubpage/subsubsubpage', $test[1]->url);

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(3, sizeof($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(3, sizeof($test));

        $test = $this->mapper->load($data[6]->getUuid(), 'default', 'de', 4);
        $this->assertEquals('/page-2/subpage', $test->getResourceLocator());
        $this->assertEquals(1, $test->getChanger());

        $test = $this->mapper->load($result->getUuid(), 'default', 'de', 4);
        $this->assertEquals('/page-1/subpage', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'default', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'default', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'default', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);
    }

    public function testCopyExistingName()
    {
        $data = $this->prepareCopyMoveTestData();

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'default', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'default', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'default', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);

        $result = $this->mapper->copy($data[5]->getUuid(), $data[0]->getUuid(), 2, 'default', 'de');

        $this->assertNotEquals($data[5]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/sub-1-1', $result->url);
        $this->assertEquals('/page-1/sub-2', $result->getPath());
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($result->getUuid(), 'default', 'de', 2);
        $this->assertCount(2, $test);
        $this->assertEquals('/page-1/sub-1-1/subpage', $test[0]->url);
        $this->assertEquals('/page-1/sub-1-1/subpage/subsubpage', $test[1]->url);

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(3, sizeof($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'default', 'de', 4, false);
        $this->assertEquals(3, sizeof($test));

        $test = $this->mapper->load($data[5]->getUuid(), 'default', 'de', 4);
        $this->assertEquals('/page-2/sub-1', $test->getResourceLocator());
        $this->assertEquals(1, $test->getChanger());

        $test = $this->mapper->load($result->getUuid(), 'default', 'de', 4);
        $this->assertEquals('/page-1/sub-1-1', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'default', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'default', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'default', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);
    }

    public function testOrderBefore()
    {
        $data = $this->prepareCopyMoveTestData();

        $result = $this->mapper->orderBefore($data[6]->getUuid(), $data[4]->getUuid(), 4, 'default', 'en');

        $this->assertEquals($data[6]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-2/subpage', $result->getPath());
        $this->assertEquals(4, $result->getChanger());

        $result = $this->mapper->loadByParent($data[3]->getUuid(), 'default', 'en');
        $this->assertEquals('/page-2/subpage', $result[0]->getPath());
        $this->assertEquals('/page-2/sub', $result[1]->getPath());
        $this->assertEquals('/page-2/sub-1', $result[2]->getPath());
    }

    public function testNewExternalLink()
    {
        $data = array(
            'title' => 'Page-1',
            'external' => 'www.google.at',
            'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK
        );

        $saveResult = $this->mapper->save($data, 'overview', 'default', 'de', 1);
        $loadResult = $this->mapper->load($saveResult->getUuid(), 'default', 'de');

        // check save result
        $this->assertEquals('Page-1', $saveResult->title);
        $this->assertEquals('Page-1', $saveResult->getNodeName());
        $this->assertEquals('www.google.at', $saveResult->external);
        $this->assertEquals('http://www.google.at', $saveResult->getResourceLocator());

        // check load result
        $this->assertEquals('Page-1', $loadResult->title);
        $this->assertEquals('Page-1', $loadResult->getNodeName());
        $this->assertEquals('www.google.at', $loadResult->external);
        $this->assertEquals('http://www.google.at', $loadResult->getResourceLocator());
    }

    public function testChangeToExternalLink()
    {
        // prepare a page
        $data = array(
            'title' => 'Page-1',
            'url' => '/page-1'
        );
        $result = $this->mapper->save($data, 'overview', 'default', 'de', 1);

        // turn it into a external link
        $data = array(
            'title' => 'External',
            'external' => 'www.google.at',
            'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK
        );
        $saveResult = $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $result->getUuid());
        $loadResult = $this->mapper->load($saveResult->getUuid(), 'default', 'de');

        // check save result
        $this->assertEquals('External', $saveResult->title);
        $this->assertEquals('External', $saveResult->getNodeName());
        $this->assertEquals('www.google.at', $saveResult->external);
        $this->assertEquals('http://www.google.at', $saveResult->getResourceLocator());
        $this->assertEquals('overview', $saveResult->getOriginTemplate());

        // check load result
        $this->assertEquals('External', $loadResult->title);
        $this->assertEquals('External', $loadResult->getNodeName());
        $this->assertEquals('www.google.at', $loadResult->external);
        $this->assertEquals('http://www.google.at', $loadResult->getResourceLocator());
        $this->assertEquals('overview', $loadResult->getOriginTemplate());

        // back to content type
        $data = array(
            'title' => 'Page-1',
            'nodeType' => Structure::NODE_TYPE_CONTENT
        );
        $saveResult = $this->mapper->save($data, 'overview', 'default', 'de', 1, true, $result->getUuid());
        $loadResult = $this->mapper->load($saveResult->getUuid(), 'default', 'de');

        // check load result
        $this->assertEquals('Page-1', $loadResult->title);
        $this->assertEquals('Page-1', $loadResult->getNodeName());
        $this->assertEquals('/page-1', $loadResult->url);
        $this->assertEquals('/page-1', $loadResult->getResourceLocator());
    }

    public function testIgnoreMandatoryFlag()
    {
        $data = array(
            'title' => 'News'
        );

        $this->mapper->setIgnoreMandatoryFlag(true)->save($data, 'external-link', 'default', 'en', 1);
        $this->mapper->setIgnoreMandatoryFlag(false);

        $data = array('title' => 'Google', 'external' => 'www.google.at');
        $this->mapper->setIgnoreMandatoryFlag(true)->save($data, 'external-link', 'default', 'en', 1);
    }

    public function testNoRenamingFlag()
    {
        $data = array(
            'title' => 'News',
            'external' => 'www.news.world'
        );

        $page = $this->mapper->save($data, 'external-link', 'default', 'de', 1);
        $this->assertTrue($this->sessionManager->getSession()->nodeExists('/cmf/default/contents/news'));

        $data = array('title' => 'Google', 'external' => 'www.google.at');
        $this->mapper->setNoRenamingFlag(true)->save(
            $data,
            'external-link',
            'default',
            'de',
            1,
            true,
            $page->getUuid()
        );

        $this->assertTrue($this->sessionManager->getSession()->nodeExists('/cmf/default/contents/news'));
        $this->assertFalse($this->sessionManager->getSession()->nodeExists('/cmf/default/contents/google'));

        $data = array('title' => 'Test', 'external' => 'www.test.at');
        $this->mapper->setNoRenamingFlag(false)->save(
            $data,
            'external-link',
            'default',
            'de',
            1,
            true,
            $page->getUuid()
        );

        $this->assertFalse($this->sessionManager->getSession()->nodeExists('/cmf/default/contents/news'));
        $this->assertFalse($this->sessionManager->getSession()->nodeExists('/cmf/default/contents/google'));
        $this->assertTrue($this->sessionManager->getSession()->nodeExists('/cmf/default/contents/test'));
    }

    public function testSaveInvalidResourceLocator()
    {
        $data = array(
            'title' => 'Testname',
            'tags' => array(
                'tag1',
                'tag2'
            ),
            'url' => '/news/test.xml',
            'article' => 'default'
        );

        $this->setExpectedException(
            'Sulu\Component\Content\Exception\ResourceLocatorNotValidException',
            "ResourceLocator '/news/test.xml' is not valid"
        );
        $this->mapper->save($data, 'overview', 'default', 'de', 1);
    }

    public function testSaveSlash()
    {
        $result = $this->mapper->save(
            array('title' => 'My / Your nice test', 'url' => '/my-your-nice-test'),
            'overview',
            'default',
            'de',
            1
        );

        $this->assertEquals('/my-your-nice-test', $result->getPath());
        $this->assertEquals('/my-your-nice-test', $result->getPropertyValue('url'));
        $this->assertEquals('My / Your nice test', $result->getPropertyValue('title'));
    }

    public function testGetResourceLocators()
    {
        $data = array(
            array('title' => 'Beschreibung', 'url' => '/beschreibung'),
            array('title' => 'Description', 'url' => '/description'),
        );

        $data[0] = $this->mapper->save(
            $data[0],
            'overview',
            'default',
            'de',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $urls = $data[0]->getUrls();

        $this->assertArrayNotHasKey('en', $urls);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);

        $data[0] = $this->mapper->load($data[0]->getUuid(), 'default', 'de');
        $urls = $data[0]->getUrls();

        $this->assertArrayNotHasKey('en', $urls);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);

        $data[0] = $this->mapper->load($data[0]->getUuid(), 'default', 'en', true);
        $urls = $data[0]->getUrls();

        $this->assertArrayNotHasKey('en', $urls);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);

        $data[1] = $this->mapper->save($data[1], 'overview', 'default', 'en', 1, true, $data[0]->getUuid(), null, Structure::STATE_PUBLISHED);
        $urls = $data[1]->getUrls();

        $this->assertEquals('/description', $urls['en']);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);

        $data[1] = $this->mapper->load($data[1]->getUuid(), 'default', 'en');
        $urls = $data[1]->getUrls();

        $this->assertEquals('/description', $urls['en']);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);

        $data[1] = $this->mapper->load($data[1]->getUuid(), 'default', 'de', true);
        $urls = $data[1]->getUrls();

        $this->assertEquals('/description', $urls['en']);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);
    }

    public function testContentTypeSwitch()
    {
        try {
            // REF
            $internalLinkData = array(
                'title' => 'Test',
                'url' => '/test/test',
                'blog' => 'Thats a good test'
            );
            $internalLink = $this->mapper->save($internalLinkData, 'extension', 'default', 'en', 1);

            // REF
            $snippetData = array(
                'title' => 'Test',
                'url' => '/test/test',
                'blog' => 'Thats a good test'
            );
            $snippet = $this->mapper->save($snippetData, 'default_snippet', 'default', 'en', 1, true, null, null,null,null,null, Structure::TYPE_SNIPPET);


            // Internal Link with String Type
            $testSiteData = array(
                'title' => 'Test',
                'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
                'internal' => $internalLink->getUuid()
            );
            $testSiteStructure = $this->mapper->save($testSiteData, 'internal-link', 'default', 'en', 1);

            $uuid = $testSiteStructure->getUuid();

            // Change to Snippet Array
            $testSiteData['internal'] = array(
                $snippet->getUuid(),
                $snippet->getUuid()
            );
            $testSiteData['nodeType'] = Structure::NODE_TYPE_CONTENT;

            $this->mapper->save($testSiteData, 'with_snipplet', 'default', 'en', 1, true, $uuid);

            // Change to Internal Link String
            $testSiteData['internal'] = $internalLink->getUuid();
            $testSiteData['nodeType'] = Structure::NODE_TYPE_INTERNAL_LINK;
            $this->mapper->save($testSiteData, 'internal-link', 'default', 'en', 1, true, $uuid);
        } catch (\Exception $e) {
            $this->fail('Exception thrown(' . get_class($e) . '): ' . $e->getMessage() . PHP_EOL . $e->getFile() . ':' . $e->getLine() . PHP_EOL . PHP_EOL . $e->getTraceAsString());
        }
    }
}

class TestExtension extends StructureExtension
{
    protected $properties = array(
        'a',
        'b'
    );

    public function __construct($name, $additionalPrefix = null)
    {
        $this->name = $name;
        $this->additionalPrefix = $additionalPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function save(NodeInterface $node, $data, $webspaceKey, $languageCode)
    {
        $node->setProperty($this->getPropertyName('a'), $data['a']);
        $node->setProperty($this->getPropertyName('b'), $data['b']);
    }

    /**
     * {@inheritdoc}
     */
    public function load(NodeInterface $node, $webspaceKey, $languageCode)
    {
        return array(
            'a' => $node->getPropertyValueWithDefault($this->getPropertyName('a'), ''),
            'b' => $node->getPropertyValueWithDefault($this->getPropertyName('b'), '')
        );
    }
}
