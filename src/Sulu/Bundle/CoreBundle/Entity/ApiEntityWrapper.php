<?php
namespace Sulu\Bundle\CoreBundle\Entity;

use JMS\Serializer\Annotation\Exclude;

class ApiEntityWrapper extends ApiEntity {

    /**
     * the entity which is wrapped by this class
     * @var object
     * @Exclude
     */
    protected $entity;

    /**
     * the locale in which the wrapped entity should be expressed
     * @var string
     */
    protected $locale;

    public function getEntity() {
        return $this->entity;
    }
} 
