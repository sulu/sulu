<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Mapper;

use Jackalope\Session;
use PHPCR\Util\NodeHelper;
use Sulu\Bundle\ContentBundle\Mapper\PhpcrContentMapper;
use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;

class PhpcrContentMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PhpcrContentMapper
     */
    protected $mapper;

    /**
     * @var Session
     */
    protected $session;

    public function setUp()
    {
        $this->mapper = new PhpcrContentMapper();

        $factoryclass = '\Jackalope\RepositoryFactoryJackrabbit';
        $parameters = array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server');
        $factory = new $factoryclass();
        $repository = $factory->getRepository($parameters);
        $credentials = new \PHPCR\SimpleCredentials('admin','admin');
        $this->session = $repository->login($credentials, 'default');

        NodeHelper::purgeWorkspace($this->session);
        $this->session->save();

        $cmf = $this->session->getRootNode()->addNode('cmf');
        $cmf->addNode('routes');
        $cmf->addNode('contents');

        $this->session->save();
    }

    public function testSave()
    {
        $data = array(
            'language' => 'de',
            'title' => 'Testtitle',
            'url' => '/de/test',
            'article' => 'Test'
        );

        $this->mapper->save($data);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/routes/de/test');

        $content = $route->getPropertyValue('content');

        $this->assertEquals($content->getProperty('title')->getString(), 'Testtitle');
        $this->assertEquals($content->getProperty('article')->getString(), 'Test');
    }
}
