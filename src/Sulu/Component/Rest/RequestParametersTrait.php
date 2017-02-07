<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\Exception\ParameterDataTypeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * handles request parameters.
 */
trait RequestParametersTrait
{
    /**
     * returns request parameter with given name.
     *
     * @param Request $request
     * @param string  $name
     * @param bool    $force   TRUE if value is mandatory
     * @param mixed   $default value if parameter not exists
     *
     * @throws MissingParameterException parameter is mandatory but does not exists
     *
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
     * returns request parameter as boolean 'true' => true , 'false' => false.
     *
     * @param Request $request
     * @param string  $name
     * @param bool    $force   TRUE if value is mandatory
     * @param bool    $default value if parameter not exists
     *
     * @throws MissingParameterException  parameter is mandatory but does not exists
     * @throws ParameterDataTypeException parameter hast the wrong data type
     *
     * @return bool
     */
    protected function getBooleanRequestParameter($request, $name, $force = false, $default = null)
    {
        $value = $this->getRequestParameter($request, $name, $force, $default);
        if ($value === 'true' || $value === true) {
            $value = true;
        } elseif ($value === 'false' || $value === false) {
            $value = false;
        } elseif ($force && $value !== true && $value !== false) {
            throw new ParameterDataTypeException(get_class($this), $name);
        } else {
            $value = $default;
        }

        return $value;
    }
}
