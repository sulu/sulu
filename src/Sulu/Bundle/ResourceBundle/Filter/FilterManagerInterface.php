<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Filter;

/**
 * Interface FilterManagerInterface
 * @package Sulu\Bundle\ResourceBundle\Filter
 */
interface FilterManagerInterface {

    public function getFieldDescriptors($getLocale);

    public function findByIdAndLocale($id, $locale);

    public function findAllByLocale($getLocale);
}
