<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Functional\Application\ContentLocalizationsResolver;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentLocalizationsResolver\ContentLocalizationsResolverInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class ContentLocalizationsResolverTest extends SuluTestCase
{
    /** The compiled container picks the resolver by its tag; `ExampleControllerTest` covers the default. */
    public function testAResolverTaggedWithTheResourceKeyResolvesItsContent(): void
    {
        $dimensionContent = new class(new Example()) extends ExampleDimensionContent {
            public static function getResourceKey(): string
            {
                return 'static_localizations';
            }
        };

        $this->assertSame(
            ['en' => ['url' => '/en/static', 'locale' => 'en', 'alternate' => true]],
            $this->getContentLocalizationsResolver()->resolve($dimensionContent, 'sulu-io'),
        );
    }

    private function getContentLocalizationsResolver(): ContentLocalizationsResolverInterface
    {
        /** @var ContentLocalizationsResolverInterface $resolver */
        $resolver = self::getContainer()->get('sulu_content.content_localizations_resolver');

        return $resolver;
    }
}
