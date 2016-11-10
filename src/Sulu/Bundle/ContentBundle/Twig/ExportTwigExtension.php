<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Twig;

use Sulu\Component\Content\Export\ContentExportManagerInterface;

/**
 * Twig extensions for the Webspace export.
 */
class ExportTwigExtension extends \Twig_Extension
{
    /**
     * @var ContentExportManagerInterface
     */
    private $contentExportManager;

    /**
     * @var int
     */
    private $counter = 0;

    /**
     * @param ContentExportManagerInterface $contentExportManager
     */
    public function __construct(ContentExportManagerInterface $contentExportManager)
    {
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
            new \Twig_SimpleFunction('sulu_content_type_export_escape', [$this, 'escapeXmlContent']),
            new \Twig_SimpleFunction('sulu_content_type_export_counter', [$this, 'counter']),
        ];
    }

    /**
     * @return int
     */
    public function counter()
    {
        return $this->counter++;
    }

    /**
     * @param $content
     *
     * @return string
     */
    public function escapeXmlContent($content)
    {
        if (is_object($content) || is_array($content)) {
            if (method_exists($content, 'getUuid')) {
                return $content->getUuid();
            }

            return 'ERROR: wrong data';
        }

        if (preg_match('/[<>{}"&]/', $content)) {
            $content = '<![CDATA[' . $content . ']]>';
        }

        return $content;
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
