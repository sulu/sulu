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

class PreviewRendererTest extends KernelTestCase
{
    /**
     * @var PreviewRenderer
     */
    private $previewRenderer;

    public function setUp()
    {
        $this->previewRenderer = $this->getContainer()->get('sulu_preview.preview.renderer');
    }

    public function testTargetGroupProperty()
    {
        $this->assertAttributeEquals('X-Sulu-Target-Group', 'targetGroupHeader', $this->previewRenderer);
    }
}
