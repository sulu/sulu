<?php

namespace Sulu\Component\Content\Mapper;

/**
 * The content context holds configuration properties for the
 * current request. Note that these properties may be overridden
 * by the request. This is why this is a Context and not Config.
 */
class ContentContext
{
    protected $propertyPrefix;
    protected $languageNamespace;
    protected $languageDefault;
    protected $templateDefault;

    public function __construct(
        $languageDefault,
        $templateDefault,
        $propertyPrefix,
        $languageNamespace
    )
    {
        $this->propertyPrefix = $propertyPrefix;
        $this->languageNamespace = $languageNamespace;
        $this->languageDefault = $languageDefault;
        $this->templateDefault = $templateDefault;
    }

    public function getPropertyPrefix() 
    {
        return $this->propertyPrefix;
    }

    public function getLanguageNamespace() 
    {
        return $this->languageNamespace;
    }

    public function getLanguageDefault() 
    {
        return $this->languageDefault;
    }

    public function getTemplateDefault() 
    {
        return $this->templateDefault;
    }
}
