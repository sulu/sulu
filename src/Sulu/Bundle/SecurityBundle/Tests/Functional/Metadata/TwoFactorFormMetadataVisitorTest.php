<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Metadata;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TwoFactorFormMetadataVisitorTest extends SuluTestCase
{
    public function testUsesSetupMethods(): void
    {
        $visitor = static::getContainer()->get('sulu_security.two_factor_form_metadata_visitor');

        $twoFactorMethods = (new \ReflectionProperty($visitor, 'twoFactorMethods'))->getValue($visitor);

        $this->assertSame(
            static::getContainer()->getParameter('sulu_security.two_factor_setup_methods'),
            $twoFactorMethods
        );
    }
}
