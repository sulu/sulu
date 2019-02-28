<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

/**
 * Interface for DataItems offering publishing information.
 */
interface PublishInterface extends ResourceItemInterface
{
    /**
     * Returns the date at which the content was published.
     *
     * @return \DateTime
     */
    public function getPublished();

    /**
     * Returns true iff the latest version of the content is published.
     *
     * @return bool
     */
    public function getPublishedState();
}
