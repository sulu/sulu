<?php

namespace Sulu\Component\Content\Mapper\LocalizationFinder;

use Sulu\Component\Content\Mapper\LocalizationFinder\LocalizationFinderInterface;
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
     * Add a finder to the chain
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

        return null;
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
