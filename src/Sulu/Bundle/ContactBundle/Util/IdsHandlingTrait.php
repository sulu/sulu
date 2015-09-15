<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Util;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Provides function to sort array by ids array.
 */
trait IdsHandlingTrait
{
    /**
     * @var PropertyAccessor
     */
    private $accessor;

    /**
     * Returns accessor.
     *
     * @return PropertyAccessor
     */
    private function getAccesssor()
    {
        if (null === $this->accessor) {
            $this->accessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->accessor;
    }

    /**
     * Returns id of given object of array.
     *
     * @param object|array $objectOrArray
     *
     * @return int
     */
    private function getId($objectOrArray)
    {
        // path for object
        $path = 'id';
        if (is_array($objectOrArray)) {
            // path for array
            $path = sprintf('[%s]', $path);
        }

        return $this->getAccesssor()->getValue($objectOrArray, $path);
    }

    /**
     * Splits ids into types.
     *
     * @param array $ids
     * @param array $default
     *
     * @return array
     */
    protected function parseIds($ids, $default)
    {
        $result = $default;

        foreach ($ids as $id) {
            $type = substr($id, 0, 1);
            $value = substr($id, 1);

            if (!isset($result[$type])) {
                $result[$type] = [];
            }

            $result[$type][] = $value;
        }

        return $result;
    }

    /**
     * Sorts list-response by id array.
     *
     * @param array $ids
     * @param array $list
     *
     * @return array
     */
    protected function sortByIds($ids, array $list)
    {
        $result = [];
        for ($i = 0; $i < count($list); ++$i) {
            $id = $this->getId($list[$i]);

            if (false !== ($index = array_search($id, $ids))) {
                $result[$index] = $list[$i];
            }
        }
        ksort($result);

        return $result;
    }

    /**
     * Sorts entities by id array.
     *
     * @param array $ids
     * @param array $entities
     * @param callable $typeFunction
     *
     * @return array
     */
    protected function sortEntitiesByIds($ids, array $entities, callable $typeFunction)
    {
        $result = [];
        for ($i = 0; $i < count($entities); ++$i) {
            $id = $this->getId($entities[$i]);
            $type = $typeFunction($entities[$i]);

            if (false !== ($index = array_search($type . $id, $ids))) {
                $result[$index] = $entities[$i];
            }
        }
        ksort($result);

        return $result;
    }
}
