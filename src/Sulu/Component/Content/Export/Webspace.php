<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Export;

use Symfony\Component\Templating\EngineInterface;

class Webspace implements WebspaceInterface
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var string[]
     */
    protected $formatFilePaths;

    /**
     * @param EngineInterface $templating
     * @param array $formatFilePaths
     */
    public function __construct(
        EngineInterface $templating,
        array $formatFilePaths
    ) {
        $this->templating = $templating;
        $this->formatFilePaths = $formatFilePaths;
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param null $uuid
     *
     * @return string
     *
     * @throws \Exception
     */
    public function export(
        $webspaceKey,
        $locale,
        $format = '1.2.xliff',
        $uuid = null
    )
    {
        if (!$webspaceKey || !$locale) {
            throw new \Exception(sprintf('Invalid parameters for export "%s (%s)"', $webspaceKey, $locale));
        }

        return $this->templating->render(
            $this->getTemplate($format),
            $this->getParameters($webspaceKey, $locale)
        );
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return array
     */
    protected function getParameters($webspaceKey, $locale)
    {
        return array(
            'webspaceKey' => $webspaceKey,
            'locale' => $locale
        );
    }

    /**
     * @param $format
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getTemplate($format)
    {
        if (!isset($this->formatFilePaths[$format])) {
            throw new \Exception(sprintf('No format "%s" configured for webspace export', $format));
        }

        $templatePath = $this->formatFilePaths[$format];

        if (!$this->templating->exists($templatePath)) {
            throw new \Exception(sprintf('No template file "%s" found for webspace export', $format));
        }

        return $templatePath;
    }
}
