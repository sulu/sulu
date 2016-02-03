<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Driver;

use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;
use Metadata\Driver\FileLocatorInterface;
use Metadata\MergeableClassMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\DoctrinePropertyMetadata;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DoctrineXmlDriver extends AbstractFileDriver implements DriverInterface
{
    const SCHEME_PATH = '/../../Resources/schema/metadata/general-1.0.xsd';

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(FileLocatorInterface $locator, ParameterBagInterface $parameterBag)
    {
        parent::__construct($locator);

        $this->parameterBag = $parameterBag;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        $classMetadata = new MergeableClassMetadata($class->getName());

        // load xml file
        // TODO xsd validation
        $xmlDoc = XmlUtils::loadFile($file);
        $xpath = new \DOMXPath($xmlDoc);
        $xpath->registerNamespace('x', 'http://schemas.sulu.io/class/general');
        $xpath->registerNamespace('orm', 'http://schemas.sulu.io/class/doctrine');

        foreach ($xpath->query('/x:class/x:properties/x:*') as $propertyNode) {
            $classMetadata->addPropertyMetadata($this->getPropertyMetadata($xpath, $propertyNode, $class->getName()));
        }

        return $classMetadata;
    }

    /**
     * Extracts attributes from dom-node to create a new property-metadata object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     * @param string $className
     *
     * @return DoctrinePropertyMetadata
     */
    protected function getPropertyMetadata(\DOMXPath $xpath, \DOMElement $propertyNode, $className)
    {
        $name = XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode);
        $propertyMetadata = new DoctrinePropertyMetadata($className, $name);

        $fieldNameList = $propertyNode->getElementsByTagName('field-name');
        if ($fieldNameList->length > 0) {
            $propertyMetadata->setFieldName($this->resolveParameter($fieldNameList->item(0)->nodeValue));
        }

        $entityNameList = $propertyNode->getElementsByTagName('entity-name');
        if ($entityNameList->length > 0) {
            $propertyMetadata->setEntityName($this->resolveParameter($entityNameList->item(0)->nodeValue));
        }

        return $propertyMetadata;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function resolveParameter($value)
    {
        return $this->parameterBag->resolveValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension()
    {
        return 'xml';
    }
}
