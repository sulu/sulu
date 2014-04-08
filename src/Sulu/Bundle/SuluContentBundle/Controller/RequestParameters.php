<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * handles request paramters
 */
trait RequestParameters
{
    /**
     * returns
     * @param Request $request
     * @param string $name
     * @param bool $force
     * @param mixed $default
     * @throws MissingParameterException
     * @return string
     */
    protected function getRequestParameter(Request $request, $name, $force = false, $default = null)
    {
        $value = $request->get($name, $default);
        if ($force && $value === null) {
            throw new MissingParameterException(get_class($this), $name);
        }
        return $value;
    }

    /**
     * @param Request $request
     * @param string $name
     * @param bool $force
     * @param mixed $default
     * @throws MissingParameterException
     * @return boolean
     */
    protected function getBooleanRequestParameter($request, $name, $force = false, $default = null)
    {
        $value = $this->getRequestParameter($request, $name, $force, $default);
        if ($value === 'true') {
            $value = true;
        } elseif ($value === 'false') {
            $value = false;
        }
        return $value;
    }
} 
