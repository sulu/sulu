<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Bundle\ResourceBundle\Resource\Exception;

/**
 * Exception which is thrown when a filter context cannot be found
 * Class FilterContextNotFoundException
 */
class FilterContextNotFoundException extends FilterException
{
    /**
     * The name of the object not found
     * @var string
     */
    private $entityName;

    /**
     * The name of the context not found
     * @var string
     */
    private $name;

    public function __construct($id)
    {
        $this->entityName = 'SuluResourceBundle:Filter';
        $this->id = $id;
        parent::__construct('The filter context with the name "' . $this->name . '" was not found.', 0);
    }

    /**
     * Returns the name of the entityname of the dependency not found
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Returns the name of the context not found
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
