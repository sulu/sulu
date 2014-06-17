<?php
namespace Sulu\Bundle\CoreBundle\Entity;


class ApiEntityWrapper extends ApiEntity {

    /**
     * the entity which is wrapped by this class
     * @var object
     * @Exclude
     */
    protected $entity;

    public function getEntity() {
        return $this->entity;
    }
} 
