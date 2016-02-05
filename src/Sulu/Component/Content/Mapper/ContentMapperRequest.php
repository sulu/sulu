<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

/**
 * Request class for content mapping.
 *
 * This is either the best long term solution or a temporary
 * short-term solution to fix the argument problem on the content
 * mapper ->save() method, which currently has 11 arguments.
 *
 * @deprecated This class is only used by the ContentMapper, which will be replaced by the DocumentManager
 */
class ContentMapperRequest
{
    /**
     * @var string
     */
    protected $type = 'page';

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $templateKey;

    /**
     * @var string
     */
    protected $webspaceKey;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $parentUuid;

    /**
     * @var string
     */
    protected $state = null;

    /**
     * @var bool
     */
    protected $isShadow;

    /**
     * @var string
     */
    protected $shadowBaseLanguage;

    /**
     * @var bool
     */
    protected $partialUpdate = true;

    /**
     * Create a new structure data object.
     *
     * @param string $type        e.g. page or structure
     * @param string $templateKey Name of template to use
     * @param array  $data        Data which the content mapper should map to the resolved structure
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Return the structure type (page, snippet).
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type.
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Return the template key.
     *
     * @return string
     */
    public function getTemplateKey()
    {
        return $this->templateKey;
    }

    /**
     * Set the template key.
     */
    public function setTemplateKey($templateKey)
    {
        $this->templateKey = $templateKey;

        return $this;
    }

    /**
     * Return the data to map to the resolved structure.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data.
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Return the webspace key.
     *
     * @return string
     */
    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    /**
     * Set the webspace key.
     *
     * @param string $webspaceKey
     */
    public function setWebspaceKey($webspaceKey)
    {
        $this->webspaceKey = $webspaceKey;

        return $this;
    }

    /**
     * Return the user ID.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the user ID.
     *
     * @param int
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Set the UUID of an existing PHPCR node to which
     * this data should be mapped.
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set the UUID.
     *
     * @param string
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Map the structure with this state (published / test).
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Map the structure with given state.
     *
     * @param int $state
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get the desired shadow status.
     *
     * @return bool
     */
    public function getIsShadow()
    {
        return $this->isShadow;
    }

    /**
     * Map the structure with this shadow status.
     *
     * @param bool $isShadow
     */
    public function setIsShadow($isShadow)
    {
        $this->isShadow = $isShadow;

        return $this;
    }

    /**
     * Return the shadow base language.
     *
     * @return string
     */
    public function getShadowBaseLanguage()
    {
        return $this->shadowBaseLanguage;
    }

    /**
     * Map the given shadow base language.
     *
     * @param string
     */
    public function setShadowBaseLanguage($shadowBaseLanguage)
    {
        $this->shadowBaseLanguage = $shadowBaseLanguage;

        return $this;
    }

    /**
     * Return if the content mapper should perform a partial update.
     *
     * @return bool
     */
    public function getPartialUpdate()
    {
        return $this->partialUpdate;
    }

    /**
     * Set if the content mapper should perform a partial update.
     *
     * @param bool $partialUpdate
     */
    public function setPartialUpdate($partialUpdate)
    {
        $this->partialUpdate = $partialUpdate;

        return $this;
    }

    /**
     * Return the UUID of the parent UUID, if any.
     *
     * @return string
     */
    public function getParentUuid()
    {
        return $this->parentUuid;
    }

    /**
     * If given the content mapper should add a node as a child
     * of the PHPCR node referenced by the given UUID.
     *
     * @param string $parentUuid
     */
    public function setParentUuid($parentUuid)
    {
        $this->parentUuid = $parentUuid;

        return $this;
    }

    /**
     * Return the locale that this request relates to.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Map the request in this locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
}
