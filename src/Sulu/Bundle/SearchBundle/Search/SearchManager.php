<?php

namespace Sulu\Bundle\SearchBundle\Search;

use Sulu\Bundle\SearchBundle\Search\AdapterInterface;
use Sulu\Bundle\SearchBundle\Search\Document;
use Metadata\MetadataFactory;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Sulu\Bundle\SearchBundle\Search\Field;

class SearchManager
{
    protected $adapter;
    protected $metadataFactory;

    public function __construct(AdapterInterface $adapter, MetadataFactory $metadataFactory)
    {
        $this->adapter = $adapter;
        $this->metadataFactory = $metadataFactory;
    }

    protected function getMetadata($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException(sprintf(
                'You must pass an object to the %s method, you passed: %s',
                __METHOD__,
                var_export($object, true)
            ));
        }

        $objectClass = get_class($object);
        $metadata = $this->metadataFactory->getMetadataForClass($objectClass);

        if (null === $metadata) {
            throw new \RuntimeException(sprintf(
                'There is no search mapping for class "%s"',
                $objectClass
            ));
        }

        return $metadata->getOutsideClassMetadata();
    }

    /**
     * Attempt to index the given object
     *
     * @param object $object
     */
    public function index($object)
    {
        $metadata = $this->getMetadata($object);

        $indexName = $metadata->getIndexName();
        $fields = $metadata->getFieldMapping();

        $document = new Document();
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($fields as $fieldName => $fieldMapping) {
            $document->addField(Field::create($fieldName, $accessor->getValue($object, $fieldName), $fieldMapping['type']));
        }

        $this->adapter->index($document, $indexName);
    }

    /**
     * Search with the given query string
     */
    public function search($string, $indexNames = null)
    {
        if (null === $indexNames) {
            throw new \Exception('Not implemented yet');
        }

        $indexNames = (array) $indexNames;

        return $this->adapter->search($string, $indexNames);
    }
}
