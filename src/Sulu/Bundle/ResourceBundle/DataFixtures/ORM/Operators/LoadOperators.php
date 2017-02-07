<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\DataFixtures\ORM\Operators;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use DOMNode;
use Sulu\Bundle\ResourceBundle\Entity\Operator;
use Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation;
use Sulu\Bundle\ResourceBundle\Entity\OperatorValue;
use Sulu\Bundle\ResourceBundle\Entity\OperatorValueTranslation;
use Sulu\Bundle\ResourceBundle\Resource\DataTypes;

/**
 * Loads fixtures for operators
 * Class LoadOperators.
 */
class LoadOperators implements FixtureInterface, OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $metadata = $manager->getClassMetaData(get_class(new Operator()));
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        $i = 1;
        $file = dirname(__FILE__) . '/../../operators.xml';
        $doc = new \DOMDocument();
        $doc->load($file);
        $xpath = new \DOMXpath($doc);
        $elements = $xpath->query('/operators/operator');
        if (!is_null($elements)) {
            /** @var $element DOMNode */
            foreach ($elements as $element) {
                $operator = new Operator();
                $operator->setId($i);
                $operator->setType($this->getTypeForString($element->getAttribute('type')));
                $operator->setOperator($element->getAttribute('operator'));
                $operator->setInputType($element->getAttribute('inputType'));

                // translations
                $translations = $xpath->query('translations/translation', $element);
                $this->processTranslations($manager, $operator, $translations);

                // values
                $values = $xpath->query('values/value', $element);
                $this->processValues($manager, $xpath, $operator, $values);

                $manager->persist($operator);
                ++$i;
            }
        }
        $manager->flush();
    }

    /**
     * Process translations of an operator.
     *
     * @param ObjectManager $manager
     * @param Operator $operator
     * @param \DOMNodeList $translations
     */
    protected function processTranslations($manager, $operator, $translations)
    {
        /** @var $node DOMNode */
        foreach ($translations as $node) {
            $translation = new OperatorTranslation();
            $translation->setLocale($node->getAttribute('locale'));
            $translation->setName($node->nodeValue);

            $translation->setOperator($operator);
            $operator->addTranslation($translation);
            $manager->persist($translation);
        }
    }

    /**
     * Process translations of an operator.
     *
     * @param ObjectManager $manager
     * @param $xpath \DOMXpath
     * @param Operator $operator
     * @param $values \DOMNodeList
     */
    protected function processValues($manager, $xpath, $operator, $values)
    {
        /** @var $node DOMNode */
        foreach ($values as $node) {
            $value = new OperatorValue();
            $value->setValue($node->getAttribute('value'));

            $translations = $xpath->query('translations/translation', $node);
            /** @var $trans DOMNode */
            foreach ($translations as $trans) {
                $translation = new OperatorValueTranslation();
                $translation->setLocale($trans->getAttribute('locale'));
                $translation->setName($trans->nodeValue);

                $translation->setOperatorValue($value);
                $value->addTranslation($translation);
                $manager->persist($translation);
            }

            $value->setOperator($operator);
            $operator->addValue($value);
            $manager->persist($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * Returns integer for string type.
     *
     * @param string $type
     * @returns integer
     */
    protected function getTypeForString($type)
    {
        switch ($type) {
            case 'string':
                return DataTypes::STRING_TYPE;
            case 'number':
                return DataTypes::NUMBER_TYPE;
            case 'date':
            case 'datetime':
                return DataTypes::DATETIME_TYPE;
            case 'boolean':
                return DataTypes::BOOLEAN_TYPE;
            case 'tags':
                return DataTypes::TAGS_TYPE;
            case 'auto-complete':
                return DataTypes::AUTO_COMPLETE_TYPE;
            default:
                return DataTypes::UNDEFINED_TYPE;
        }
    }
}
