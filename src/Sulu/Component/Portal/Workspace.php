<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;

/**
 * Container for a workspace definition
 * @package Sulu\Component\Workspace
 */
class Workspace
{
    /**
     * The name of the workspace
     * @var string
     */
    private $name;

    /**
     * The key of the workspace
     * @var string
     */
    private $key;

    /**
     * The localizations defined for this workspace
     * @var Localization[]
     */
    private $localizations;

    /**
     * The segments defined for this workspace
     * @var Segment[]
     */
    private $segments;

    /**
     * The portals defined for this workspace
     * @var Portal[]
     */
    private $portals;

    /**
     * Sets the key of the workspace
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Returns the key of the workspace
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Adds a localization to the workspace
     * @param Localization $localization
     */
    public function addLocalization(Localization $localization)
    {
        $this->localizations[] = $localization;
    }

    /**
     * Returns the localizations of this workspace
     * @param \Sulu\Component\Portal\Localization[] $localizations
     */
    public function setLocalizations($localizations)
    {
        $this->localizations = $localizations;
    }

    /**
     * Returns the localizations of this workspace
     * @return \Sulu\Component\Portal\Localization[]
     */
    public function getLocalizations()
    {
        return $this->localizations;
    }

    /**
     * Sets the name of the workspace
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the workspace
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a portal to the workspace
     * @param Portal $portal
     */
    public function addPortals(Portal $portal)
    {
        $this->portals[] = $portal;
    }

    /**
     * Sets the portals of this workspace
     * @param \Sulu\Component\Portal\Portal[] $portals
     */
    public function setPortals($portals)
    {
        $this->portals = $portals;
    }

    /**
     * Returns the portals of this workspace
     * @return \Sulu\Component\Portal\Portal[]
     */
    public function getPortals()
    {
        return $this->portals;
    }

    /**
     * Adds a segment to the workspace
     * @param Segment $segment
     */
    public function addSegments(Segment $segment)
    {
        $this->segments[] = $segment;
    }

    /**
     * Sets the segments of this workspace
     * @param \Sulu\Component\Portal\Segment[] $segments
     */
    public function setSegments($segments)
    {
        $this->segments = $segments;
    }

    /**
     * Returns the segments of this workspace
     * @return \Sulu\Component\Portal\Segment[]
     */
    public function getSegments()
    {
        return $this->segments;
    }
}
