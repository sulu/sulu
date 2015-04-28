<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

use Sulu\Component\Util\ArrayableInterface;

class Theme implements ArrayableInterface
{
    /**
     * The key of the theme
     * @var string
     */
    private $key;

    /**
     * A list of excluded templates
     * @var array
     */
    private $excludedTemplates;

    /**
     * A list of exception templates
     * @var array
     */
    private $errorTemplates;

    /**
     * Sets the key of the theme
     * @param string $key The key of the theme
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Returns the key of the theme
     * @return string The key of the theme
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Adds an exluded template to this theme instance
     * @param $excludedTemplate string The template to exclude
     */
    public function addExcludedTemplate($excludedTemplate)
    {
        $this->excludedTemplates[] = $excludedTemplate;
    }

    /**
     * Sets the excluded templates
     * @param array $excludedTemplates
     */
    public function setExcludedTemplates($excludedTemplates)
    {
        $this->excludedTemplates = $excludedTemplates;
    }

    /**
     * Returns an array of the excluded templates
     * @return array The excluded templates
     */
    public function getExcludedTemplates()
    {
        return $this->excludedTemplates;
    }

    /**
     * Add a new error template for given code
     * @param string $code
     * @param string $template
     */
    public function addErrorTemplate($code, $template)
    {
        $this->errorTemplates[$code] = $template;
    }

    /**
     * Returns a error template for given code
     * @param string $code
     * @return string|null
     */
    public function getErrorTemplate($code)
    {
        if (array_key_exists($code, $this->errorTemplates)) {
            return $this->errorTemplates[$code];
        }

        if (array_key_exists('default', $this->errorTemplates)) {
            return $this->errorTemplates['default'];
        }

        return null;
    }

    /**
     * Returns a array of error template
     * @return string[]
     */
    public function getErrorTemplates()
    {
        return $this->errorTemplates;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray($depth = null)
    {
        return array(
            'key' => $this->getKey(),
            'excludedTemplates' => $this->getExcludedTemplates(),
            'errorTemplates' => $this->getErrorTemplates(),
        );
    }
}
