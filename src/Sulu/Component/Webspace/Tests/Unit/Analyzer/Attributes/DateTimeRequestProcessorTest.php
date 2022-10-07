<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer\Attributes;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Webspace\Analyzer\Attributes\DateTimeRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\Request;

class DateTimeRequestProcessorTest extends TestCase
{
    /**
     * @var DateTimeRequestProcessor
     */
    private $dateTimeRequestProcessor;

    public function setUp(): void
    {
        $this->dateTimeRequestProcessor = new DateTimeRequestProcessor();
    }

    public function testProcess(): void
    {
        $attributes = $this->dateTimeRequestProcessor->process(new Request(), new RequestAttributes());
        $this->assertEqualsWithDelta($attributes->getAttribute('dateTime'), new \DateTime(), 1);
    }
}
