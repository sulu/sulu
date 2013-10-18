<?php
/**
 * Created by IntelliJ IDEA.
 * User: danielrotter
 * Date: 14.10.13
 * Time: 11:29
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Mapper;

use Sulu\Bundle\ContentBundle\Mapper\PhpcrContentMapper;
use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;

class PhpcrContentMapperTest extends \PHPUnit_Framework_TestCase
{
    protected $mapper;

    public function __construct()
    {
        $this->mapper = new PhpcrContentMapper();
    }

    public function testSave()
    {
        $data = array(
            'title' => 'Testtitle',
            'url' => '/de/test',
            'article' => 'Test'
        );

        $this->mapper->save($data);


    }
}
