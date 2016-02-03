<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\General;

use Metadata\PropertyMetadata as BasePropertyMetadata;

class PropertyMetadata extends BasePropertyMetadata
{
    /**
     * @var string
     */
    private $translation;

    /**
     * @var bool
     */
    private $disabled = false;

    /**
     * @var bool
     */
    private $default = false;

    /**
     * @var string
     */
    private $type = '';

    /**
     * @var string
     */
    private $with = '';

    /**
     * @var string
     */
    private $minWidth = '';

    /**
     * @var bool
     */
    private $sortable = true;

    /**
     * @var bool
     */
    private $editable = false;

    /**
     * @var string
     */
    private $cssClass = '';

    public function __construct($class, $name)
    {
        parent::__construct($class, $name);

        // default for translation can be overwritten by setter
        $this->translation = ucfirst($name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param string $translation
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;
    }

    /**
     * @return boolean
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param boolean $disabled
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }

    /**
     * @return boolean
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param boolean $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getWith()
    {
        return $this->with;
    }

    /**
     * @param int $with
     */
    public function setWith($with)
    {
        $this->with = $with;
    }

    /**
     * @return int
     */
    public function getMinWidth()
    {
        return $this->minWidth;
    }

    /**
     * @param int $minWidth
     */
    public function setMinWidth($minWidth)
    {
        $this->minWidth = $minWidth;
    }

    /**
     * @return boolean
     */
    public function isSortable()
    {
        return $this->sortable;
    }

    /**
     * @param boolean $sortable
     */
    public function setSortable($sortable)
    {
        $this->sortable = $sortable;
    }

    /**
     * @return boolean
     */
    public function isEditable()
    {
        return $this->editable;
    }

    /**
     * @param boolean $editable
     */
    public function setEditable($editable)
    {
        $this->editable = $editable;
    }

    /**
     * @return string
     */
    public function getCssClass()
    {
        return $this->cssClass;
    }

    /**
     * @param string $cssClass
     */
    public function setCssClass($cssClass)
    {
        $this->cssClass = $cssClass;
    }
}
