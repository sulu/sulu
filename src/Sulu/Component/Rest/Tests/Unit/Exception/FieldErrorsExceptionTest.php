<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Rest\Exception\FieldErrorsException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class FieldErrorsExceptionTest extends TestCase
{
    public function testIsAnUnprocessableRequest(): void
    {
        $exception = new FieldErrorsException([['path' => 'title', 'title' => 'Too short.']]);

        $this->assertSame(422, $exception->getStatusCode());
        $this->assertSame(1109, $exception->getCode());
        $this->assertSame([['path' => 'title', 'title' => 'Too short.']], $exception->getFieldErrors());
    }

    public function testFromConstraintViolationListConvertsThePropertyPaths(): void
    {
        $exception = FieldErrorsException::fromConstraintViolationList(new ConstraintViolationList([
            new ConstraintViolation('Username can not contain spaces.', null, [], null, 'username', null),
            new ConstraintViolation('Password needs a number.', null, [], null, 'password', null),
            new ConstraintViolation('Password needs a special character.', null, [], null, 'password', null),
            new ConstraintViolation('Key is not active.', null, [], null, 'blocks[0].key', null),
            new ConstraintViolation('Not a number.', null, [], null, '[2].amount', null),
            new ConstraintViolation('Invalid.', null, [], null, '', null),
        ]));

        $this->assertSame(
            [
                ['path' => 'username', 'title' => 'Username can not contain spaces.'],
                ['path' => 'password', 'title' => 'Password needs a number.'],
                ['path' => 'password', 'title' => 'Password needs a special character.'],
                ['path' => 'blocks/0/key', 'title' => 'Key is not active.'],
                ['path' => '2/amount', 'title' => 'Not a number.'],
                ['path' => '', 'title' => 'Invalid.'],
            ],
            $exception->getFieldErrors()
        );
    }
}
