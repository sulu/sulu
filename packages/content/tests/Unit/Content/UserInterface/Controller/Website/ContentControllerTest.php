<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\UserInterface\Controller\Website;

use PHPUnit\Framework\TestCase;
use Sulu\Content\UserInterface\Controller\Website\ContentController;

class ContentControllerTest extends TestCase
{
    public function testMethodShouldContainWordSuluToAvoidFutureConflicts(): void
    {
        $reflection = new \ReflectionClass(ContentController::class);
        $methods = $reflection->getMethods();

        $skippedMethods = [
            'indexAction',
            'getSubscribedServices',
        ];

        foreach ($methods as $method) {
            $methodName = $method->getName();

            if (ContentController::class !== $method->class) {
                continue;
            }

            if (\in_array($methodName, $skippedMethods)) {
                continue;
            }

            $this->assertStringContainsString('Sulu', $methodName, \sprintf(
                'Method "%s"" should contain "Sulu" to avoid future conflicts like we had with renderBlock in the past.',
                $methodName,
            ));
        }
    }
}
