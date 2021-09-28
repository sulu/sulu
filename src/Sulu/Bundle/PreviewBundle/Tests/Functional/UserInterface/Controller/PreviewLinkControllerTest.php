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

use Ramsey\Uuid\Uuid;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\TestBundle\Kernel\SuluKernelBrowser;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class PreviewLinkControllerTest extends SuluTestCase
{
    /**
     * @var SuluKernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        /** @var SuluKernelBrowser $client */
        $client = static::createAuthenticatedClient();

        $this->client = $client;
    }

    public function testGetAction(): void
    {
        $resourceKey = 'pages';
        $resourceId = Uuid::uuid4()->toString();
        $locale = 'en';
        $webspaceKey = 'example';

        $this->createPreviewLink($resourceKey, $resourceId, $locale, $webspaceKey);

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/preview-links/%s?resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $resourceKey,
                $locale,
                $webspaceKey
            )
        );

        static::assertHttpStatusCode(200, $this->client->getResponse());
        $json = \json_decode((string) $this->client->getResponse()->getContent(), true);

        static::assertEquals($resourceKey, $json['resourceKey']);
        static::assertEquals($resourceId, $json['resourceId']);
        static::assertEquals($locale, $json['locale']);
        static::assertEquals(['webspaceKey' => $webspaceKey], $json['options']);
        static::assertIsString($json['token']);
        static::assertNotNull($json['lastVisit']);
        static::assertSame(1, $json['visitCount']);
    }

    public function testGetActionNotFound(): void
    {
        $resourceKey = 'pages';
        $resourceId = Uuid::uuid4()->toString();
        $locale = 'en';
        $webspaceKey = 'example';

        $this->client->jsonRequest(
            'GET',
            \sprintf(
                '/api/preview-links/%s?resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $resourceKey,
                $locale,
                $webspaceKey
            )
        );

        static::assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGenerate(): void
    {
        $resourceKey = 'pages';
        $resourceId = Uuid::uuid4()->toString();
        $locale = 'en';
        $webspaceKey = 'example';

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=generate&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $resourceKey,
                $locale,
                $webspaceKey
            )
        );

        static::assertHttpStatusCode(201, $this->client->getResponse());
        $json = \json_decode((string) $this->client->getResponse()->getContent(), true);

        static::assertEquals($resourceKey, $json['resourceKey']);
        static::assertEquals($resourceId, $json['resourceId']);
        static::assertEquals($locale, $json['locale']);
        static::assertEquals(['webspaceKey' => $webspaceKey], $json['options']);
        static::assertIsString($json['token']);
        static::assertNull($json['lastVisit']);
        static::assertSame(0, $json['visitCount']);
    }

    public function testRevoke(): void
    {
        $resourceKey = 'pages';
        $resourceId = Uuid::uuid4()->toString();
        $locale = 'en';
        $webspaceKey = 'example';

        $this->createPreviewLink($resourceKey, $resourceId, $locale, $webspaceKey);

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/preview-links/%s?action=revoke&resourceKey=%s&locale=%s&webspaceKey=%s',
                $resourceId,
                $resourceKey,
                $locale,
                $webspaceKey
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
        $repository = static::getContainer()->get('sulu.repository.preview_link');
        $previewLink = $repository->createNew($resourceKey, $resourceId, $locale, ['webspaceKey' => $webspaceKey]);
        $previewLink->increaseVisitCount();
        $repository->add($previewLink);
        $repository->commit();

        return $previewLink;
    }
}
