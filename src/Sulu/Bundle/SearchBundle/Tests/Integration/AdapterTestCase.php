<?php

namespace Sulu\Bundle\SearchBundle\Tests\Integration;

use Symfony\Cmf\Component\Testing\Functional\BaseTestCase;
use Sulu\Bundle\SearchBundle\Search\Field;
use Sulu\Bundle\SearchBundle\Search\Document;

abstract class AdapterTestCase extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    protected function createDocument($title)
    {
        static $id = 0;
        $id++;

        $document = new Document();
        $document->setId($id);
        $document->addField(Field::create('title', $title, 'string'));
        $text = <<<EOT
This section is a brief introduction to reStructuredText (reST) concepts and syntax, intended to provide authors with enough information to author documents documentively. Since reST was designed to be a simple, unobtrusive markup language, this will not take too long.
EOT
        ;
        $document->addField(Field::create('body', $text, 'string'));

        return $document;
    }

    public function testIndexer()
    {
        $adapter = $this->getAdapter();

        $documents = array(
            $this->createDocument('Document One'),
            $this->createDocument('Document Two'),
        );

        foreach ($documents as $document) {
            $adapter->index($document, 'foobar');
        }

        $res = $adapter->search('One', array('foobar'));

        $this->assertCount(1, $res);
    }
}
