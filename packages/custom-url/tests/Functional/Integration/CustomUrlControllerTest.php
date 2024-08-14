<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\CustomUrl\UserInterface\Controller\Admin\CustomUrlController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Uid\Uuid;

#[CoversClass(CustomUrlController::class)]
class CustomUrlControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;
    use HandleTrait;

    protected KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = self::createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );
        self::purgeDatabase();
    }

    /**
     * @param array<string> $domainParts
     *
     * @return array<string,mixed>
     */
    private static function createCustomUrlData(
        string $title,
        string $baseDomain = '*.sulu.io/*',
        array $domainParts = ['test-11', 'test-21'],
        bool $published = true,
        ?Uuid $targetDocument = null,
        string $targetLocale = 'en',
        bool $redirect = true,
    ): array {
        return [
            'title' => $title,
            'published' => $published,
            'baseDomain' => $baseDomain,
            'domainParts' => $domainParts,
            'targetDocument' => $targetDocument ?? Uuid::v4(),
            'targetLocale' => $targetLocale,
            'canonical' => true,
            'redirect' => $redirect,
            'noFollow' => true,
            'noIndex' => true,
        ];
    }

    public function testPost(): void
    {
        $data = self::createCustomUrlData(
            title: 'Test',
            baseDomain: '*.sulu.io/*',
            domainParts: ['test-1', 'test-2'],
            targetDocument: Uuid::fromString('5cc1f411-e1ee-4dcc-8f07-6af4aa1d24cf')
        );

        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_post.json', $response, Response::HTTP_OK);
    }

    public function testPostWithoutTargetDocument(): void
    {
        $data = self::createCustomUrlData(
            title: 'Without target document',
            baseDomain: '*.sulu.io/*',
            domainParts: ['foo', 'bar']
        );
        unset($data['targetDocument']);

        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_post_without_target_document.json', $response, Response::HTTP_OK);
    }

    public function testPostConflictingPaths(): void
    {
        $data = self::createCustomUrlData(
            title: 'Test',
            baseDomain: '*.sulu.io/*',
            domainParts: ['test-1', 'test-2'],
        );
        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $data = self::createCustomUrlData(
            title: 'Conflicting paths',
            baseDomain: '*.sulu.io/*',
            domainParts: ['test-1', 'test-2'],
        );
        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_post_conflicting_paths.json', $response, Response::HTTP_CONFLICT);
    }

    public function testPostWithTooLittleDomainParts(): void
    {
        $data = self::createCustomUrlData(
            title: 'Too little domain parts',
            baseDomain: '*.sulu.io/*',
            domainParts: ['test-1']
        );

        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_post_with_too_little_domain_parts.json', $response, Response::HTTP_BAD_REQUEST);
    }

    public function testPostWithAlreadyExistingTitle(): void
    {
        $data = self::createCustomUrlData(title: 'Test');
        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);
        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot(
            'custom_url_post_with_already_existing_title.json',
            $response,
            Response::HTTP_BAD_REQUEST,
        );
    }

    /**
     * @return \Generator<string,array{array<string,mixed>, int}>
     */
    public static function putProvider(): \Generator
    {
        yield 'Normal document update' => [
            self::createCustomUrlData(
                title: 'Updated Custom URL',
                baseDomain: '*.sulu.io/*',
                domainParts: ['one', 'two'],
            ),
            Response::HTTP_OK,
            'custom_url_put_update.json',
        ];

        yield 'too few domain parts' => [
            self::createCustomUrlData(
                title: 'Too little domain parts',
                baseDomain: '*.sulu.io/*',
                domainParts: ['test-1'],
            ),
            Response::HTTP_BAD_REQUEST,
            'custom_url_put_update_with_too_little_domain_parts.json',
        ];

        yield 'Already existing title' => [
            self::createCustomUrlData(
                title: 'Test',
                published: true,
                baseDomain: '*.sulu.io/*',
                domainParts: ['foo', 'bar'],
            ),
            Response::HTTP_BAD_REQUEST,
            'custom_url_put_update_with_already_existing_title.json',
        ];

        yield 'Already existing path' => [
            self::createCustomUrlData(title: 'Test-1'),
            Response::HTTP_CONFLICT,
            'custom_url_put_update_with_conflicting_path.json',
        ];
    }

    /**
     * @param array{array<string,mixed>, int} $data
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('putProvider')]
    public function testPut(array $data, int $statusCode, string $snapshotFile): void
    {
        // Creating a default custom url to test update conflicts
        $this->createCustomUrl();

        // Creating a custom URL to modify
        $id = $this->createCustomUrl(
            self::createCustomUrlData(
                title: 'Update me',
                published: true,
                baseDomain: '*.sulu.io/*',
                domainParts: ['a', 'b'],
            )
        );

        $this->client->jsonRequest('PUT', '/admin/api/webspaces/sulu_io/custom-urls/' . $id, $data);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot($snapshotFile, $response, $statusCode);
    }

    public function testGet(): void
    {
        $id = $this->createCustomUrl();
        $this->client->jsonRequest('GET', '/admin/api/webspaces/sulu_io/custom-urls/' . $id);

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_get.json', $response, Response::HTTP_OK);
    }

    public function testCGet(): void
    {
        $this->client->jsonRequest('GET', '/admin/api/webspaces/sulu_io/custom-urls');

        $response = $this->client->getResponse();
        $this->assertResponseSnapshot('custom_url_cget.json', $response, Response::HTTP_OK);
    }

    public function testDelete(): void
    {
        $id = $this->createCustomUrl();

        $this->client->jsonRequest('DELETE', '/admin/api/webspaces/sulu_io/custom-urls/' . $id);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->client->jsonRequest('GET', '/admin/api/webspaces/sulu_io/custom-urls/' . $id);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function createCustomUrl(array $data = []): string
    {
        if ([] === $data) {
            $data = self::createCustomUrlData(title: 'Test');
        }
        $this->client->jsonRequest('POST', '/admin/api/webspaces/sulu_io/custom-urls', $data);
        $response = $this->client->getResponse();

        /** @var array{id: string} $responseData */
        $responseData = \json_decode((string) $response->getContent(), true);

        return $responseData['id'];
    }

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}
