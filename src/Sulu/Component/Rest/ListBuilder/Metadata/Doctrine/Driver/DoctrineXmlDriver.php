<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Driver;

use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;
use Metadata\Driver\FileLocatorInterface;
use Metadata\MergeableClassMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\JoinMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\PropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\SingleType;
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
            $propertyMetadata = $this->getPropertyMetadata($xpath, $propertyNode, $class->getName());
            if ($propertyMetadata !== null) {
                $classMetadata->addPropertyMetadata($propertyMetadata);
            }
        }

        return $classMetadata;
    }

    /**
     * Extracts data from dom-node to create a new property-metadata object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     * @param string $className
     *
     * @return PropertyMetadata
     */
    protected function getPropertyMetadata(\DOMXPath $xpath, \DOMElement $propertyNode, $className)
    {
        if (($fieldName = XmlUtil::getValueFromXPath('orm:field-name', $xpath, $propertyNode)) === null
            || ($entityName = XmlUtil::getValueFromXPath('orm:entity-name', $xpath, $propertyNode)) === null
        ) {
            return;
        }

        $name = XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode);
        $field = new FieldMetadata($this->resolveParameter($fieldName), $this->resolveParameter($entityName));
        $propertyMetadata = new PropertyMetadata($className, $name, new SingleType($field));

        foreach ($xpath->query('orm:joins/orm:join', $propertyNode) as $joinNode) {
            $field->addJoin($this->getJoinMetadata($xpath, $joinNode));
        }

        return $propertyMetadata;
    }

    /**
     * Extracts data from dom-node to create a new join-metadata object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $joinNode
     *
     * @return JoinMetadata
     */
    protected function getJoinMetadata(\DOMXPath $xpath, \DOMElement $joinNode)
    {
        $joinMetadata = new JoinMetadata();

        if (($fieldName = XmlUtil::getValueFromXPath('orm:field-name', $xpath, $joinNode)) !== null) {
            $joinMetadata->setEntityField($this->resolveParameter($fieldName));
        }

        if (($entityName = XmlUtil::getValueFromXPath('orm:entity-name', $xpath, $joinNode)) !== null) {
            $joinMetadata->setEntityName($this->resolveParameter($entityName));
        }

        if (($condition = XmlUtil::getValueFromXPath('orm:condition', $xpath, $joinNode)) !== null) {
            $joinMetadata->setCondition($condition);
        }

        if (($conditionMethod = XmlUtil::getValueFromXPath('orm:condition-method', $xpath, $joinNode)) !== null) {
            $joinMetadata->setConditionMethod($conditionMethod);
        }

        if (($method = XmlUtil::getValueFromXPath('orm:method', $xpath, $joinNode)) !== null) {
            $joinMetadata->setMethod($method);
        }

        return $joinMetadata;
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
