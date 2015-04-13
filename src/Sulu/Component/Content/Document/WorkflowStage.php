<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document;

use Sulu\Component\Content\Document\WorkflowStage;

/**
 * Simple constants class for representing workflow stages.
 *
 * TODO: Workflow should be dynamic
 */
final class WorkflowStage
{
    /**
     * Document is published
     */
    const PUBLISHED = 2;

    /**
     * Document is not published
     */
    const TEST = 1;
}
