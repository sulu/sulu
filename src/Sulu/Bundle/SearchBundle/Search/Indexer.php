<?php

namespace Sulu\Bundle\SearchBundle\Search;

class Indexer
{
    protected $metadataFactory;

    public function metadataFactory()
    {
    }

    public function index($object)
    {
        $meta = $this->metadataFactory->getMetadataForClass($object);

        if (!$meta) {
            throw new \InvalidArgumentException(sprintf(
                'Object "%s" has no search metadata', $objectClass
            ));
        }

        $indexName = $meta->getIndexName();

        $indexEntry = new IndexEntry($meta);
        $indexEntry->setObject($object);

        $this->adapter->index($indexEntry);
    }
}
