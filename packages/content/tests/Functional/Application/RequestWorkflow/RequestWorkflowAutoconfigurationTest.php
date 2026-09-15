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

namespace Sulu\Content\Tests\Functional\Application\RequestWorkflow;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\RequestWorkflow\PreValidator\SeoRequiredPreValidator;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowRegistryInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\RequestWorkflow\UnpublishedExampleReferencesValidator;

/**
 * The test bundle registers its validators without any tag, so this fails as soon as
 * autoconfiguration or the locator's `getKey()` indexing stops working.
 */
#[CoversNothing]
class RequestWorkflowAutoconfigurationTest extends SuluTestCase
{
    public function testUntaggedValidatorIsFoundByTheKeyItsClassReturns(): void
    {
        $registry = static::getContainer()->get(RequestWorkflowRegistryInterface::class);

        $validators = $registry->get('references')->validators;

        $this->assertArrayHasKey(UnpublishedExampleReferencesValidator::getKey(), $validators);
        $this->assertInstanceOf(
            UnpublishedExampleReferencesValidator::class,
            $validators[UnpublishedExampleReferencesValidator::getKey()]['validator'],
        );
    }

    public function testBuiltinPreValidatorIsFoundByTheKeyItsClassReturns(): void
    {
        $registry = static::getContainer()->get(RequestWorkflowRegistryInterface::class);

        $preValidators = $registry->get('seo')->preValidators;

        $this->assertInstanceOf(
            SeoRequiredPreValidator::class,
            $preValidators[SeoRequiredPreValidator::getKey()]['pre_validator'],
        );
    }
}
