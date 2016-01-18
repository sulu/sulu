<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document;

use Sulu\Bundle\ContentBundle\Document\PageDocument;

/**
 * Interface for custom-url data-class.
 */
interface CustomUrlBehavior
{
    /**
     * Returns title of custom-url.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Returns state of custom-url.
     *
     * @return string
     */
    public function isPublished();

    /**
     * Returns base domain of custom-url.
     *
     * @return string
     */
    public function getBaseDomain();

    /**
     * Returns domain parts of custom-url.
     *
     * @return array
     */
    public function getDomainParts();

    /**
     * Returns target for custom-url.
     *
     * @return PageDocument
     */
    public function getTarget();

    /**
     * Returns locale for target.
     *
     * @return string
     */
    public function getTargetLocale();

    /**
     * Returns true if multilingual is enabled.
     *
     * @return bool
     */
    public function isMultilingual();

    /**
     * Returns true if canonical is enabled.
     *
     * @return bool
     */
    public function isCanonical();

    /**
     * Returns true if redirect is enabled.
     *
     * @return bool
     */
    public function isRedirect();
}
