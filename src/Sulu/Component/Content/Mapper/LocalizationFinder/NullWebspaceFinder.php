<?php

namespace Sulu\Component\Content\Mapper\LocalizationFinder;

use Sulu\Component\Content\Mapper\LocalizationFinder\LocalizationFinderInterface;
use PHPCR\NodeInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Util\SuluNodeHelper;

/**
 * Localization finder that works when there is no webspace available.
 *
 * Currently this is quite dumb. It will just take the first available localization
 * instead of doing anything hierarchical based on all of the webspaces.
 */
class NullWebspaceFinder implements LocalizationFinderInterface
{
    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    public function __construct(SuluNodeHelper $nodeHelper)
    {
        $this->nodeHelper = $nodeHelper;
    }

    /**
     * Return the given localization if it exists, otherwise use the next available one
     * {@inheritDoc}
     */
    public function getAvailableLocalization(NodeInterface $node, $localizationCode, $webspaceKey = null)
    {
        $availableLocalizations = $this->nodeHelper->getLanguagesForNode($node);

        if (empty($availableLocalizations)) {
            return $localizationCode;
        }

        if (in_array($localizationCode, $availableLocalizations)) {
            return $localizationCode;
        }

        return reset($availableLocalizations);
    }

    /**
     * Return true if the webspace key is null
     * {@inheritDoc}
     */
    public function supports(NodeInterface $contentNode, $localizationCode, $webspaceKey = null)
    {
        return $webspaceKey === null ? true : false;
    }
}
