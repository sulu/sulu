<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Admin\Helper;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentSelect
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(WebspaceManagerInterface $webspaceManager, TranslatorInterface $translator)
    {
        $this->webspaceManager = $webspaceManager;
        $this->translator = $translator;
    }

    public function getValues(string $webspace): array
    {
        $values = [
            ['title' => $this->translator->trans('sulu_admin.none_selected', [], 'admin')],
        ];

        foreach ($this->webspaceManager->findWebspaceByKey($webspace)->getSegments() as $segment) {
            $values[] = [
                'name' => $segment->getKey(),
                'title' => $segment->getName(),
            ];
        }

        return $values;
    }
}
