<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Functional\UserInterface\Controller;

use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Uid\Uuid;

class PreviewLinkControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var string
     */
    private $resourceKey = 'examples';

    /**
     * @var string
     */
    private $webspaceKey = 'sulu_io';

    /**
     * @var string
     */
    private $locale = 'en';

    public function setUp(): void
    {
        $this->client = static::createAuthenticatedClient();
    }

    public function testGetAction(): void
    {
        $resourceId = Uuid::v4()->toRfc4122();

        $this->createPreviewLink($this->resourceKey, $resourceId, $this->locale, $this->webspaceKey);

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/preview-links/%s?resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            )
        );

        static::assertHttpStatusCode(200, $this->client->getResponse());
        $json = \json_decode((string) $this->client->getResponse()->getContent(), true);

        static::assertEquals($this->resourceKey, $json['resourceKey']);
        static::assertEquals($resourceId, $json['resourceId']);
        static::assertEquals($this->locale, $json['locale']);
        static::assertEquals(['webspaceKey' => $this->webspaceKey], $json['options']);
        $token = $json['token'] ?? null;
        static::assertIsString($token);
        static::assertNotNull($json['lastVisit']);
        static::assertSame(1, $json['visitCount']);

        static::ensureKernelShutdown();
        $this->client->request('GET', '/p/' . $token . '/render');
        $previewResponse = $this->client->getResponse();
        static::assertHttpStatusCode(200, $previewResponse);
        $this->assertStringContainsString('ID: ' . $resourceId, $previewResponse->getContent() ?: '');
        $this->assertStringContainsString('Locale: ' . $this->locale, $previewResponse->getContent() ?: '');
    }

    public function testGetActionNotFound(): void
    {
        $resourceId = Uuid::v4()->toRfc4122();

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/preview-links/%s?resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            )
        );

        static::assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGenerate(): void
    {
        $resourceId = Uuid::v4()->toRfc4122();

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=generate&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            )
        );

        static::assertHttpStatusCode(201, $this->client->getResponse());
        $json = \json_decode((string) $this->client->getResponse()->getContent(), true);

        static::assertEquals($this->resourceKey, $json['resourceKey']);
        static::assertEquals($resourceId, $json['resourceId']);
        static::assertEquals($this->locale, $json['locale']);
        static::assertEquals(['webspaceKey' => $this->webspaceKey], $json['options']);
        static::assertIsString($json['token']);
        static::assertNull($json['lastVisit']);
        static::assertSame(0, $json['visitCount']);
    }

    public function testRevoke(): void
    {
        $resourceId = Uuid::v4()->toRfc4122();

        $this->createPreviewLink($this->resourceKey, $resourceId, $this->locale, $this->webspaceKey);

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=revoke&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $this->resourceKey,
                $this->locale,
                $this->webspaceKey
            )
        );

        static::assertHttpStatusCode(204, $this->client->getResponse());
    }

    protected function createPreviewLink(
        string $resourceKey,
        string $resourceId,
        string $locale,
        string $webspaceKey
    ): PreviewLinkInterface {
        $repository = static::getContainer()->get('sulu_preview.preview_link_repository');
        $previewLink = $repository->create($resourceKey, $resourceId, $locale, ['webspaceKey' => $webspaceKey]);
        $previewLink->increaseVisitCount();
        $repository->add($previewLink);
        $repository->commit();

        return $previewLink;
    }
}
