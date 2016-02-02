<?php

/*
 * This file is part of Sulu.
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
     * The key of the theme.
     *
     * @var string
     */
    private $key;

    /**
     * A list of exception templates.
     *
     * @var array
     */
    private $errorTemplates;

    /**
     * Template which is selected by default if no other template is chosen.
     *
     * @var string[]
     */
    private $defaultTemplates;

    /**
     * Theme constructor.
     */
    public function __construct()
    {
        $this->errorTemplates = [];
        $this->defaultTemplates = [];
    }

    /**
     * Sets the key of the theme.
     *
     * @param string $key The key of the theme
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Returns the key of the theme.
     *
     * @return string The key of the theme
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Add a new error template for given code.
     *
     * @param string $code
     * @param string $template
     */
    public function addErrorTemplate($code, $template)
    {
        $this->errorTemplates[$code] = $template;
    }

    /**
     * Returns a error template for given code.
     *
     * @param string $code
     *
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

        return;
    }

    /**
     * Returns a array of error template.
     *
     * @return string[]
     */
    public function getErrorTemplates()
    {
        return $this->errorTemplates;
    }

    /**
     * Add a new default template for given type.
     *
     * @param string $type
     * @param string $template
     */
    public function addDefaultTemplate($type, $template)
    {
        $this->defaultTemplates[$type] = $template;
    }

    /**
     * Returns a error template for given code.
     *
     * @param string $type
     *
     * @return string|null
     */
    public function getDefaultTemplate($type)
    {
        if (array_key_exists($type, $this->defaultTemplates)) {
            return $this->defaultTemplates[$type];
        }

        return;
    }

    /**
     * Returns a array of default template.
     *
     * @return string
     */
    public function getDefaultTemplates()
    {
        return $this->defaultTemplates;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        return [
            'key' => $this->getKey(),
            'defaultTemplates' => $this->getDefaultTemplates(),
            'errorTemplates' => $this->getErrorTemplates(),
        ];
    }
}
