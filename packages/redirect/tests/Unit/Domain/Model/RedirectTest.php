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

namespace Sulu\Redirect\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Redirect\Domain\Exception\InvalidRedirectException;
use Sulu\Redirect\Domain\Model\Redirect;
use Symfony\Component\Uid\Uuid;

#[CoversClass(Redirect::class)]
class RedirectTest extends TestCase
{
    public function testDefaults(): void
    {
        $redirect = new Redirect();

        $this->assertTrue(Uuid::isValid($redirect->getId()));
        $this->assertTrue($redirect->isEnabled());
    }

    public function testModifyNormalises(): void
    {
        $redirect = new Redirect('id-1');
        $redirect->modify(302, 'Foo/Bar', ' Example.COM ', ' /target ');

        $this->assertSame(['id-1', 302, '/foo/bar', 'example.com', '/target'], [
            $redirect->getId(),
            $redirect->getStatusCode(),
            $redirect->getSource(),
            $redirect->getSourceHost(),
            $redirect->getTarget(),
        ]);
    }

    public function testEmptyHostBecomesNull(): void
    {
        $redirect = new Redirect();
        $redirect->modify(301, '/a', '', '/b');

        $this->assertNull($redirect->getSourceHost());
    }

    public function testGoneDropsTarget(): void
    {
        $redirect = new Redirect();
        $redirect->modify(410, '/a', null, '/ignored');

        $this->assertSame('', $redirect->getTarget());
    }

    public function testSourceIsNotDecoded(): void
    {
        $redirect = new Redirect();
        $redirect->modify(301, '/Foo%20Bar', null, '/b');

        $this->assertSame('/foo%20bar', $redirect->getSource());
    }

    public function testAbsoluteHttpsTargetAllowed(): void
    {
        $redirect = new Redirect();
        $redirect->modify(301, '/a', null, 'https://example.org/x?y=1');

        $this->assertSame('https://example.org/x?y=1', $redirect->getTarget());
    }

    /**
     * @return iterable<string, array{int, string, string|null, string|null, string, string}>
     */
    public static function provideInvalid(): iterable
    {
        yield 'status 307' => [307, '/a', null, '/b', 'statusCode', 'unsupported_status_code'];
        yield 'empty source' => [301, '/', null, '/b', 'source', 'empty'];
        yield 'query in source' => [301, '/a?x=1', null, '/b', 'source', 'invalid_character'];
        yield 'fragment in source' => [301, '/a#x', null, '/b', 'source', 'invalid_character'];
        yield 'newline in source' => [301, "/a\nb", null, '/b', 'source', 'invalid_character'];
        yield 'long source' => [301, '/' . \str_repeat('a', 191), null, '/b', 'source', 'too_long'];
        yield 'host with path' => [301, '/a', 'example.com/x', '/b', 'sourceHost', 'invalid_host'];
        yield 'missing target' => [301, '/a', null, '', 'target', 'empty'];
        yield 'scheme relative' => [301, '/a', null, '//evil.example', 'target', 'invalid_target'];
        yield 'backslash scheme relative' => [301, '/a', null, '/\\evil.example', 'target', 'invalid_target'];
        yield 'javascript' => [302, '/a', null, 'javascript:alert(1)', 'target', 'invalid_target'];
        yield 'ftp' => [302, '/a', null, 'ftp://example.org', 'target', 'invalid_target'];
        yield 'header injection' => [301, '/a', null, "/b\r\nSet-Cookie: x", 'target', 'invalid_character'];
        yield 'relative without slash' => [301, '/a', null, 'foo', 'target', 'invalid_target'];
    }

    #[DataProvider('provideInvalid')]
    public function testInvalid(int $statusCode, string $source, ?string $host, ?string $target, string $field, string $reason): void
    {
        $this->expectException(InvalidRedirectException::class);
        $this->expectExceptionMessage(\sprintf('Invalid redirect "%s": %s', $field, $reason));

        (new Redirect())->modify($statusCode, $source, $host, $target);
    }
}
