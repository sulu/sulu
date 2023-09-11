<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Structure;

class SnippetBridge extends StructureBridge
{
    /**
     * @var bool
     */
    private $isShadow;

    /**
     * @var string
     */
    private $shadowBaseLanguage;

    public function getIsShadow()
    {
        return $this->isShadow;
    }

    /**
     * @param bool $isShadow
     */
    public function setIsShadow($isShadow)
    {
        $this->isShadow = $isShadow;
    }

    public function getShadowBaseLanguage()
    {
        return $this->shadowBaseLanguage;
    }

    /**
     * @param string $shadowBaseLanguage
     */
    public function setShadowBaseLanguage($shadowBaseLanguage)
    {
        $this->shadowBaseLanguage = $shadowBaseLanguage;
    }

    public function getLanguageCode()
    {
        if (null === $this->document) {
            return $this->locale;
        }

        // return original locale for shadow or ghost pages
        if ($this->getIsShadow() || (null !== $this->getType() && 'ghost' === $this->getType()->getName())) {
            return $this->inspector->getOriginalLocale($this->getDocument());
        }

        return parent::getLanguageCode();
    }
}
