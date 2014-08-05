<?php

namespace Sulu\Component\PHPCR\Model;

use Sulu\Component\PHPCR\Wrapper\Wrapped\Node as BaseNode;
use Sulu\Component\Content\ContentContextInterface;
use Sulu\Component\Content\ContentContextAwareInterface;

class SuluPhpcrNode extends BaseNode implements ContentContextAwareInterface
{
    protected $contentContext;
    protected $currentLocale;

    public function setLocale($locale)
    {
        $this->currentLocale = $locale;
    }

    public function setContentContext(ContentContextInterface $contentContext)
    {
        $this->contentContext = $contentContext;
    }

    private function translateName($name)
    {
        return sprintf(
            '%s:%s-%s',
            $this->contentContext->getLanguageNamespace(),
            $this->currentLocale,
            $name
        );
    }

    public function getTranslatedProperty($name)
    {
        return $this->getProperty($this->translateName($name));
    }

    public function getTranslatedPropertyValue($name, $defaultValue = null)
    {
        return $this->getPropertyValueWithDefault($this->translateName($name), $defaultValue);
    }

    public function hasTranslatedProperty($name)
    {
        return $this->hasProperty($this->translateName($name));
    }
}

