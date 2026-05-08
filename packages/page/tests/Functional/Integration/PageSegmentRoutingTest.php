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

namespace Sulu\Page\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Segment;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[CoversNothing]
class PageSegmentRoutingTest extends SuluTestCase
{
    use CreatePageTrait;

    private const WEBSPACE_KEY = 'sulu-segments-io';
    private const HOST = 'sulu-segments.io';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createWebsiteClient();
        self::purgeDatabase();
    }

    public function testNoCookiePageWithoutSegmentUsesDefaultSegment(): void
    {
        self::createPage(
            [
                'en' => [
                    'live' => [
                        'title' => 'Plain Page',
                        'url' => '/plain-page',
                        'template' => 'default',
                    ],
                ],
            ],
            self::WEBSPACE_KEY,
        );

        self::getEntityManager()->clear();

        $this->client->request('GET', 'http://' . self::HOST . '/en/plain-page');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSegmentKey('business');
    }

    public function testNoCookiePageAssignedToNonDefaultSegmentSwitchesSegment(): void
    {
        self::createPage(
            [
                'en' => [
                    'live' => [
                        'title' => 'Private Page',
                        'url' => '/private-page',
                        'template' => 'default',
                        'excerptSegment' => 'private',
                    ],
                ],
            ],
            self::WEBSPACE_KEY,
        );

        self::getEntityManager()->clear();

        $this->client->request('GET', 'http://' . self::HOST . '/en/private-page');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSegmentKey('private');
        $this->assertResponseSetsSegmentCookie('private');
    }

    public function testCookieMismatchPageAssignedToDifferentSegmentOverridesCookie(): void
    {
        self::createPage(
            [
                'en' => [
                    'live' => [
                        'title' => 'Private Page',
                        'url' => '/private-page',
                        'template' => 'default',
                        'excerptSegment' => 'private',
                    ],
                ],
            ],
            self::WEBSPACE_KEY,
        );

        self::getEntityManager()->clear();

        $this->client->getCookieJar()->set(
            new \Symfony\Component\BrowserKit\Cookie('_ss', 'business', null, '/', self::HOST)
        );

        $this->client->request('GET', 'http://' . self::HOST . '/en/private-page');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSegmentKey('private');
        $this->assertResponseSetsSegmentCookie('private');
    }

    public function testCookieMatchesPageSegmentNoCookieRewrite(): void
    {
        self::createPage(
            [
                'en' => [
                    'live' => [
                        'title' => 'Private Page',
                        'url' => '/private-page',
                        'template' => 'default',
                        'excerptSegment' => 'private',
                    ],
                ],
            ],
            self::WEBSPACE_KEY,
        );

        self::getEntityManager()->clear();

        $this->client->getCookieJar()->set(
            new \Symfony\Component\BrowserKit\Cookie('_ss', 'private', null, '/', self::HOST)
        );

        $this->client->request('GET', 'http://' . self::HOST . '/en/private-page');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertSegmentKey('private');
        $this->assertNoSegmentCookieRewrite();
    }

    private function assertSegmentKey(string $expectedSegmentKey): void
    {
        $request = $this->client->getRequest();
        $suluAttributes = $request->attributes->get('_sulu');

        $this->assertInstanceOf(
            RequestAttributes::class,
            $suluAttributes,
            'Expected the request to carry the _sulu RequestAttributes set by the RequestAnalyzer.',
        );

        $segment = $suluAttributes->getAttribute('segment');

        $this->assertInstanceOf(
            Segment::class,
            $segment,
            \sprintf('Expected the request to be associated with the "%s" segment, got null.', $expectedSegmentKey),
        );

        $this->assertSame(
            $expectedSegmentKey,
            $segment->getKey(),
            \sprintf(
                'Expected RequestAnalyzer->getSegment() to return "%s" because the matched page is assigned to that segment, but got "%s".',
                $expectedSegmentKey,
                $segment->getKey(),
            ),
        );
    }

    private function assertResponseSetsSegmentCookie(string $expectedValue): void
    {
        $cookies = $this->client->getResponse()->headers->getCookies();

        foreach ($cookies as $cookie) {
            if ('_ss' === $cookie->getName()) {
                $this->assertSame(
                    $expectedValue,
                    $cookie->getValue(),
                    'Expected response to set the segment cookie to the page-assigned segment.',
                );

                return;
            }
        }

        $this->fail(\sprintf(
            'Expected the response to set a "_ss" cookie with value "%s" so that subsequent requests use the page-assigned segment, but no such cookie was set.',
            $expectedValue,
        ));
    }

    private function assertNoSegmentCookieRewrite(): void
    {
        $cookies = $this->client->getResponse()->headers->getCookies();

        foreach ($cookies as $cookie) {
            if ('_ss' === $cookie->getName()) {
                $this->fail(\sprintf(
                    'Expected no "_ss" cookie rewrite when the existing cookie already matches the page-assigned segment, but the response set "_ss=%s".',
                    (string) $cookie->getValue(),
                ));
            }
        }
    }
}
