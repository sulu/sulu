<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

/**
 * Interface ContentTypeValuesServiceInterface
 * @package Sulu\Component\Content
 */
interface ContentTypeValuesServiceInterface
{
    /**
     * returns the values for property
     * @param array $params
     * @return array
     */
    public function getValues($params = array());
} 
