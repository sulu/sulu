<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\MediaBundle\DependencyInjection\SuluMediaExtension;

class SuluMediaExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return array(
            new SuluMediaExtension()
        );
    }

    public function testLoad()
    {
        $this->load();

        $this->assertContainerBuilderHasService('sulu_media.media_manager');
        $this->assertContainerBuilderHasParameter('sulu_media.image.formats', array(
            '170x170' => array(
                'name' => '170x170',
                'commands' => array(
                    array(
                        'action' => 'scale',
                        'parameters' => array(
                            'x' => '170',
                            'y' => '170',
                        )
                    )
                )
            ),
            '50x50' => array(
                'name' => '50x50',
                'commands' => array(
                    array(
                        'action' => 'scale',
                        'parameters' => array(
                            'x' => '50',
                            'y' => '50',
                        )
                    )
                )
            ),
            '150x100' => array(
                'name' => '150x100',
                'commands' => array(
                    array(
                        'action' => 'scale',
                        'parameters' => array(
                            'x' => '150',
                            'y' => '100',
                        )
                    )
                )
            ),
        ));
    }
}
