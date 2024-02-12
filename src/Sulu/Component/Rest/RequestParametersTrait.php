<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string $name
     * @param bool $force TRUE if value is mandatory
     * @param mixed $default value if parameter not exists
     *
     * @return string
     *
     * @throws MissingParameterException parameter is mandatory but does not exists
     */
    protected function getRequestParameter(Request $request, $name, $force = false, $default = null)
    {
        $value = $request->get($name, $default);
        if ($force && null === $value) {
            throw new MissingParameterException(\get_class($this), $name);
        }

        return $value;
    }

    /**
     * returns request parameter as boolean 'true' => true , 'false' => false.
     *
     * @template T of bool|null
     *
     * @param Request $request
     * @param string $name
     * @param bool $force TRUE if value is mandatory
     * @param T $default value if parameter not exists
     *
     * @return bool|T
     *
     * @throws MissingParameterException parameter is mandatory but does not exists
     * @throws ParameterDataTypeException parameter hast the wrong data type
     */
    protected function getBooleanRequestParameter($request, $name, $force = false, $default = null)
    {
        $value = $this->getRequestParameter($request, $name, $force, $default);
        if ('true' === $value || true === $value) {
            $value = true;
        } elseif ('false' === $value || false === $value) {
            $value = false;
        } elseif ($force && true !== $value && false !== $value) {
            throw new ParameterDataTypeException(\get_class($this), $name);
        } else {
            $value = $default;
        }

        return $value;
    }
}
