<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Serializer;

use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;

/**
 * Enables serialization to an array with the JMSSerializer.
 */
class ArraySerializationVisitor implements SerializationVisitorInterface
{
    /**
     * @var JsonSerializationVisitor
     */
    private $jsonSerializationVisitor;

    public function __construct()
    {
        $this->jsonSerializationVisitor = new JsonSerializationVisitor();
    }

    public function setNavigator(GraphNavigatorInterface $navigator): void
    {
        $this->jsonSerializationVisitor->setNavigator($navigator);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($data)
    {
        return $this->jsonSerializationVisitor->prepare($data);
    }

    /**
     * {@inheritdoc}
     */
    public function visitNull($data, array $type)
    {
        return $this->jsonSerializationVisitor->visitNull($data, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function visitString(string $data, array $type)
    {
        return $this->jsonSerializationVisitor->visitString($data, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function visitBoolean(bool $data, array $type)
    {
        return $this->jsonSerializationVisitor->visitInteger($data, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function visitInteger(int $data, array $type)
    {
        return $this->jsonSerializationVisitor->visitInteger($data, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function visitDouble(float $data, array $type)
    {
        return $this->jsonSerializationVisitor->visitDouble($data, $type);
    }

    /**
     * @param array $data
     * @param array $type
     *
     * @return array|\ArrayObject
     */
    public function visitArray(array $data, array $type)
    {
        return $this->jsonSerializationVisitor->visitArray($data, $type);
    }

    public function startVisitingObject(ClassMetadata $metadata, object $data, array $type): void
    {
        $this->jsonSerializationVisitor->startVisitingObject($metadata, $data, $type);
    }

    /**
     * @return array|\ArrayObject
     */
    public function endVisitingObject(ClassMetadata $metadata, object $data, array $type)
    {
        $this->jsonSerializationVisitor->endVisitingObject($metadata, $data, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function visitProperty(PropertyMetadata $metadata, $v): void
    {
        $this->jsonSerializationVisitor->visitProperty($metadata, $v);
    }

    /**
     * @deprecated Will be removed in 3.0
     *
     * Checks if some data key exists.
     */
    public function hasData(string $key): bool
    {
        return $this->jsonSerializationVisitor->hasData($key);
    }

    /**
     * @deprecated use `::visitProperty(new StaticPropertyMetadata('', 'name', 'value'), 'value')` instead
     *
     * Allows you to replace existing data on the current object element
     *
     * @param mixed $value This value must either be a regular scalar, or an array.
     *                     It must not contain any objects anymore.
     */
    public function setData(string $key, $value): void
    {
        $this->jsonSerializationVisitor->setData($key, $value);
    }

    /**
     * Returns the visited data as array.
     *
     * @return array
     */
    public function getResult($data)
    {
        return $data;
    }
}
