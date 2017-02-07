<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource\Exception;

/**
 * This exception is thrown if a required dependency for creating a new
 * filter is not given.
 */
class FilterDependencyNotFoundException extends FilterException
{
    /**
     * The name of the object not found.
     *
     * @var string
     */
    private $entityName;
    /**
     * The id of the object not found.
     *
     * @var int
     */
    private $id;

    public function __construct($entityName, $id)
    {
        $this->entityName = $entityName;
        $this->id = $id;
        parent::__construct(
            'The filter dependency "' . $this->entityName .
            ' with the id "' . $this->id .
            '" was not found.',
            0
        );
    }

    /**
     * Returns the name of the entityname of the dependency not found.
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Returns the id of the object not found.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
