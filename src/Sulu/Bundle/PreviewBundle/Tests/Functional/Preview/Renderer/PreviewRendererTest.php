<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Functional\Preview\Renderer;

use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRenderer;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;

class PreviewRendererTest extends KernelTestCase
{
    use ReadObjectAttributeTrait;

    /**
     * @var PreviewRenderer
     */
    private $previewRenderer;

    public function setUp(): void
    {
        self::bootKernel();
        $this->previewRenderer = self::getContainer()->get('sulu_preview_test.preview.renderer');
    }

    public function testTargetGroupProperty(): void
    {
        $this->assertSame(
            'X-Sulu-Target-Group',
            $this->readObjectAttribute($this->previewRenderer, 'targetGroupHeader')
        );
    }
}
