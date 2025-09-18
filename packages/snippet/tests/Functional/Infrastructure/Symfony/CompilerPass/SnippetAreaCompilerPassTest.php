<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Symfony\CompilerPass;

use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Snippet\Infrastructure\Symfony\CompilerPass\SnippetAreaCompilerPass;

class SnippetAreaCompilerPassTest extends KernelTestCase
{
    public function testWithoutAreas(): void
    {
        //$this->container->setParameter(
            //'sulu_snippet.areas',
            //[
                //'test' => [
                    //'key' => 'test',
                    //'template' => 'test',
                    //'cache-invalidation' => 'false',
                    //'title' => [
                        //'de' => 'Test DE',
                        //'en' => 'Test EN',
                    //],
                //],
                //'hotel' => [
                    //'key' => 'hotel',
                    //'template' => 'hotel',
                    //'cache-invalidation' => 'false',
                    //'title' => [
                        //'de' => 'Hotel DE',
                        //'en' => 'Hotel EN',
                    //],
                //],
            //]
        //)->shouldBeCalled();

        //$this->compiler->process($this->container->reveal());

        dd(self::getContainer()->getParameter(SnippetAreaCompilerPass::SNIPPET_AREA_PARAM));
    }
}
