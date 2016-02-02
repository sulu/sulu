<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Entity;

use JMS\Serializer\Annotation\Exclude;
use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * Translation.
 */
class Translation extends ApiEntity
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var \Sulu\Bundle\TranslateBundle\Entity\Code
     */
    private $code;

    /**
     * @var \Sulu\Bundle\TranslateBundle\Entity\Catalogue
     * @Exclude
     */
    private $catalogue;

    /**
     * Set value.
     *
     * @param string $value
     *
     * @return Translation
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set code.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Code $code
     *
     * @return Translation
     */
    public function setCode(\Sulu\Bundle\TranslateBundle\Entity\Code $code = null)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return \Sulu\Bundle\TranslateBundle\Entity\Code
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set catalogue.
     *
     * @param \Sulu\Bundle\TranslateBundle\Entity\Catalogue $catalogue
     *
     * @return Translation
     */
    public function setCatalogue(\Sulu\Bundle\TranslateBundle\Entity\Catalogue $catalogue = null)
    {
        $this->catalogue = $catalogue;

        return $this;
    }

    /**
     * Get catalogue.
     *
     * @return \Sulu\Bundle\TranslateBundle\Entity\Catalogue
     */
    public function getCatalogue()
    {
        return $this->catalogue;
    }
}
