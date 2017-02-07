<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

/**
 * This behavior enables documents to apply a permission to them. The set permissions will be hydrated together with
 * the document, in order to enable users to display the permissions in some way.
 */
interface SecurityBehavior
{
    /**
     * Sets the permission for the document. The passed array consists of the role name as key and a boolean array with
     * the permissions as value.
     *
     * @param array $permissions
     */
    public function setPermissions(array $permissions);

    /**
     * Returns the permissions for the document, with the role name as key and a boolean array with the permissions as
     * value.
     *
     * @return array
     */
    public function getPermissions();
}
