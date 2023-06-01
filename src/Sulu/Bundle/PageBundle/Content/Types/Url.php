<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Content\Types;

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for Url.
 */
class Url extends SimpleContentType
{
    public function __construct()
    {
        parent::__construct('Url', '');
    }

    public function getDefaultParams(?PropertyInterface $property = null)
    {
        return [
            'defaults' => new PropertyParameter(
                'defaults',
                [
                    'scheme' => new PropertyParameter('scheme', 'http://'),
                    'specific_part' => new PropertyParameter('specific_part', ''),
                ],
                'collection'
            ),
            'schemes' => new PropertyParameter(
                'schemes',
                [
                    'http://' => new PropertyParameter('http://', ''),
                    'https://' => new PropertyParameter('https://', ''),
                    'mailto:' => new PropertyParameter('mailto:', ''),
                    'tel:' => new PropertyParameter('tel:', ''),
                    'ftp://' => new PropertyParameter('ftp://', ''),
                    'ftps://' => new PropertyParameter('ftps://', ''),
                ],
                'collection'
            ),
        ];
    }
}
