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
 * The implementing document will be able to have workflow stages
 * assigned to it, for example "test" or "published".
 */
interface WorkflowStageBehavior
{
    /**
     * Return the workflow stage.
     *
     * @return string|int
     */
    public function getWorkflowStage();

    /**
     * Set the workflow stage.
     *
     * @param string|int
     */
    public function setWorkflowStage($workflowStage);

    /**
     * Get the published date or return NULL if the
     * document has not yet been published.
     *
     * @return null|\DateTime
     */
    public function getPublished();
}
