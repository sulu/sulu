<?php

namespace DTL\Component\Content\Integration;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use DTL\Bundle\ContentBundle\Document\PageDocument;

class IntegrationTest extends SuluTestCase
{
    public function provideIntegration()
    {
    }

    public function testIntegration()
    {
        $document = new PageDocument();
        $document->setTitle('Gastronomy');
        $document->setChanger(1);
        $document->setCreator(1);
        $document->setCreated(new \DateTime());
        $document->setState('published');
        $document->setTemplate('overview');
        $document->setContentData(array(
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
    }
}
