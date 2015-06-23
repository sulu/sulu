<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper\LocalizationFinder;

use PHPCR\NodeInterface;

/**
 * Chain finder delegates to the first Finder which supports the request.
 */
class ChainFinder implements LocalizationFinderInterface
{
    /**
     * @var LocalizationFinderInterface[]
     */
    private $finders;

    /**
     * Add a finder to the chain.
     *
     * @param LocalizationFinderInterface $finder
     */
    public function addFinder(LocalizationFinderInterface $finder)
    {
        $this->finders[] = $finder;
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableLocalization(NodeInterface $contentNode, $localizationCode, $webspaceKey = null)
    {
        foreach ($this->finders as $finder) {
            if ($finder->supports($contentNode, $localizationCode, $webspaceKey)) {
                return $finder->getAvailableLocalization($contentNode, $localizationCode, $webspaceKey);
            }
        }

        return;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(NodeInterface $contentNode, $localizationCode, $webspaceKey = null)
    {
        foreach ($this->finders as $finder) {
            $supports = $finder->supports($contentNode, $localizationCode, $webspaceKey);

            if (true === $supports) {
                return true;
            }
        }

        return false;
    }
}
