<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Preview;

use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PreviewBundle\Preview\PreviewCache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class PreviewCacheTest extends TestCase
{
    public function testPreviewCache()
    {
        $previewCache = new PreviewCache(new ArrayAdapter());

        $this->assertFalse($previewCache->hasItem('id'));
        $previewCache->save('id', 'test');
        $this->assertTrue($previewCache->hasItem('id'));
        $this->assertSame('test', $previewCache->fetch('id'));
        $previewCache->delete('id');
        $this->assertFalse($previewCache->hasItem('id'));
    }

    public function testLegacyPreviewCache()
    {
        $previewCache = new PreviewCache(new ArrayCache());

        $this->assertFalse($previewCache->hasItem('id'));
        $previewCache->save('id', 'test');
        $this->assertTrue($previewCache->hasItem('id'));
        $this->assertSame('test', $previewCache->fetch('id'));
        $previewCache->delete('id');
        $this->assertFalse($previewCache->hasItem('id'));
    }
}
