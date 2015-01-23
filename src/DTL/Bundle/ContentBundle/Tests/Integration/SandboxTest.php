<?php

namespace DTL\Bundle\ContentBundle\Tests\Integration;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use DTL\Bundle\ContentBundle\Document\Page;

class SandboxTest extends SuluTestCase
{
    public function setUp()
    {
        $this->documentManager = $this->getContainer()->get('doctrine_phpcr.odm.document_manager');
        $parent = $this->documentManager->find(null, '/cmf/sulu_io/contents');

        $page1 = new PageDocument();
        $page1->setName('page');
        $page1->setParent($parent);
        $page1->setTitle('Gastronomy');
        $page1->setTemplate('overview');
        $page1->setContentData(array(
            'some_number' => '1234',
            'animals' => array(
                'title' => 'Smart content',
                'sort_method' => 'asc',
            ),
            'news' => array(
                array(
                    'title' => 'Foobar',
                    'body' => 'Barfoo',
                ),
                array(
                    'title' => 'Barfoo',
                    'body' => 'Foobar',
                ),
            ),
        ));

        $this->page1 = $page1;
    }

    public function testPersist()
    {
        $this->documentManager->persist($this->page1);
        $this->documentManager->flush();
    }

}
