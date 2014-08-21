<?php

namespace Sulu\Bundle\SearchBundle\Search\Adapter;

use ZendSearch\Lucene;
use Sulu\Bundle\SearchBundle\Search\AdapterInterface;
use Sulu\Bundle\SearchBundle\Search\Document;
use Sulu\Bundle\SearchBundle\Search\Field;
use Sulu\Bundle\SearchBundle\Search\QueryHit;

class ZendLuceneAdapter implements AdapterInterface
{
    const ID_FIELDNAME = '__id';

    protected $basePath;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    protected function getIndexPath($indexName)
    {
        return sprintf('%s/%s', $this->basePath, $indexName);
    }

    public function index(Document $document, $indexName)
    {
        $indexPath = $this->getIndexPath($indexName);

        if (!file_exists($indexPath)) {
            $index = Lucene\Lucene::create($indexPath);
        } else {
            $index = Lucene\Lucene::open($indexPath);

            // check to see if the subject already exists
            $this->removeExisting($index, $document);
        }

        $luceneDocument = new Lucene\Document();

        foreach ($document->getFields() as $field) {
            switch ($field->getType()) {
                case Field::TYPE_STRING:
                default:
                    $luceneDocument->addField(Lucene\Document\Field::Text($field->getName(), $field->getValue()));
                    break;
            }
        }

        $luceneDocument->addField(Lucene\Document\Field::Keyword(self::ID_FIELDNAME, $document->getId()));

        $index->addDocument($luceneDocument);
    }

    protected function removeExisting(Lucene\Index $index, Document $document)
    {
        $hits = $index->find(self::ID_FIELDNAME . ':' . $document->getId());

        foreach ($hits as $hit) {
            $index->delete($hit->id);
        }
    }

    public function search($queryString, array $indexNames = array())
    {
        $searcher = new Lucene\MultiSearcher();

        foreach ($indexNames as $indexName) {
            $searcher->addIndex(Lucene\Lucene::open($this->getIndexPath($indexName)));
        }

        $query = Lucene\Search\QueryParser::parse($queryString);

        $luceneHits = $searcher->find($query);

        $hits = array();

        foreach ($luceneHits as $luceneHit) {
            $hit = new QueryHit();
            $document = new Document();
            $hit->setDocument($document);
            $hit->setScore($luceneHit->score);

            $luceneDocument = $luceneHit->getDocument();

            foreach ($luceneDocument->getFieldNames() as $fieldName) {
                $document->addField(Field::create($fieldName, $luceneDocument->getFieldValue($fieldName)));
            }
            $hits[] = $hit;
        }

        return $hits;
    }
}
