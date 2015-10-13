<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Twig;

use Sulu\Component\Content\Export\ContentExportManagerInterface;

/**
 * Class ExportTwigExtension
 * @package Sulu\Bundle\ContentBundle\Twig
 */
class ExportTwigExtension extends \Twig_Extension
{
    /**
     * @var ContentExportManagerInterface
     */
    private $contentExportManager;

    /**
     * @param ContentExportManagerInterface $contentExportManager
     */
    public function __construct(
        ContentExportManagerInterface $contentExportManager
    ) {
        $this->contentExportManager = $contentExportManager;
    }

    /**
     * Returns an array of possible function in this extension.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_content_type_export', [$this->contentExportManager, 'export']),
            new \Twig_SimpleFunction('sulu_content_type_has_export', [$this->contentExportManager, 'hasExport']),
            new \Twig_SimpleFunction('sulu_content_type_export_options', [$this->contentExportManager, 'getOptions']),
        ];
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'content_export';
    }
}
