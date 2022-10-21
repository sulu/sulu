<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Twig;

use Sulu\Component\Export\Manager\ExportManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extensions for the Webspace export.
 */
class ExportTwigExtension extends AbstractExtension
{
    /**
     * @var ExportManagerInterface
     */
    private $exportManager;

    /**
     * @var int
     */
    private $counter = 0;

    public function __construct(ExportManagerInterface $exportManager)
    {
        $this->exportManager = $exportManager;
    }

    /**
     * Returns an array of possible function in this extension.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_content_type_export_escape', [$this, 'escapeXmlContent']),
            new TwigFunction('sulu_content_type_export_counter', [$this, 'counter']),
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
     * @return string
     */
    public function escapeXmlContent($content)
    {
        if (\is_object($content) || \is_array($content)) {
            if (\method_exists($content, 'getUuid')) {
                return $content->getUuid();
            }

            return 'ERROR: wrong data';
        }

        if (\preg_match('/[<>{}"&]/', $content)) {
            $content = '<![CDATA[' . $content . ']]>';
        }

        return $content;
    }
}
