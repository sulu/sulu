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

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Sulu\Admin\Provider;

use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadata;
use Sulu\Bundle\AdminBundle\Metadata\ListMetadata\ListMetadataProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SnippetTemplateSelectProviderTest extends SuluTestCase
{
    private ListMetadataProvider $listMetadataProvider;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->listMetadataProvider = self::getContainer()->get('sulu_admin.list_metadata_provider');
        $this->requestStack = self::getContainer()->get('request_stack');
    }

    public function testGetFilterValuesWithoutTemplatesParamReturnsAllTemplates(): void
    {
        // The "templates" query parameter is absent whenever this list metadata is requested outside a
        // grouped snippet list view, e.g. from a generic snippet_selection overlay. Regression test for a
        // crash on symfony/expression-language versions that do not support "??" on undefined variables
        // (the XML expression used to read "templates ?? null").
        $options = $this->getTemplateKeyFilterOptions(new Request());

        // Sorted here, not asserted in registration order: that order comes from reading the template
        // directory, which differs between filesystems.
        $keys = \array_keys($options);
        \sort($keys);

        $this->assertSame(['review', 'snippet', 'snippet-alternate'], $keys);
    }

    public function testGetFilterValuesWithTemplatesParamNarrowsOptions(): void
    {
        $options = $this->getTemplateKeyFilterOptions(new Request(['templates' => 'snippet']));

        $this->assertSame(['snippet'], \array_keys($options));
    }

    /**
     * @return array<string, string>
     */
    private function getTemplateKeyFilterOptions(Request $request): array
    {
        $this->requestStack->push($request);

        try {
            /** @var ListMetadata $metadata */
            $metadata = $this->listMetadataProvider->getMetadata('snippets', 'en', []);
        } finally {
            $this->requestStack->pop();
        }

        foreach ($metadata->getFields() as $field) {
            if ('templateKey' === $field->getName()) {
                /** @var array<string, string> $options */
                $options = $field->getFilterTypeParameters()['options'] ?? [];

                return $options;
            }
        }

        $this->fail('Expected a "templateKey" field in the list metadata.');
    }
}
