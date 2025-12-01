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

namespace Sulu\Bundle\AdminBundle\Metadata;

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class GroupProvider implements GroupProviderInterface
{
    /**
     * @param array<string, array<string, array{order?: int, translation_key?: string}>> $templateGroupsConfig
     */
    public function __construct(
        private MetadataProviderRegistry $metadataProviderRegistry,
        private TranslatorInterface $translator,
        private array $templateGroupsConfig = [],
    ) {
    }

    /**
     * @return array<string, FormGroup>
     */
    public function getGroups(/* ?string $resourceKey = null, ?string $templateType = null */): array
    {
        $argv = \func_get_args();
        $resourceKey = $argv[0] ?? null;
        $templateType = $argv[1] ?? null;

        // Trigger deprecation warning when called without arguments
        if (0 === \func_num_args()) {
            @\trigger_error(
                'Calling "GroupProvider::getGroups()" without arguments is deprecated since Sulu 3.x and will be removed in 4.0. Pass the resourceKey and templateType as arguments.',
                \E_USER_DEPRECATED,
            );
        }

        // BC: Defaults to Article
        $templateType = $templateType ?? ArticleInterface::TEMPLATE_TYPE;
        $resourceKey = $resourceKey ?? $templateType;

        /** @var TypedFormMetadata $metadata */
        $metadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($templateType, '', []);

        $groupsConfig = $this->templateGroupsConfig[$resourceKey] ?? [];

        $groups = [];

        foreach ($metadata->getForms() as $articleForm) {
            $group = $articleForm->getGroup() ?: 'default';

            if (!\array_key_exists($group, $groups)) {
                $groupConfig = $groupsConfig[$group] ?? [];
                $order = $groupConfig['order'] ?? 9999;
                $translationKey = $groupConfig['translation_key'] ?? null;

                $groups[$group] = new FormGroup(
                    $group,
                    $this->getGroupTitle($group, $translationKey),
                    [],
                    $order,
                );
            }
            $groups[$group] = $groups[$group]->withTemplate($articleForm->getKey());
        }

        return $this->sortGroups($groups);
    }

    private function getGroupTitle(string $groupIdentifier, ?string $customTranslationKey = null): string
    {
        $translationKey = $customTranslationKey ?? 'sulu_admin.template_group.' . $groupIdentifier;
        $translated = $this->translator->trans($translationKey, [], 'admin');

        // If translation key doesn't exist, translator returns the key itself
        // In that case, fall back to ucfirst of the group identifier
        if ($translated === $translationKey) {
            return \ucfirst($groupIdentifier);
        }

        return $translated;
    }

    /**
     * @param array<string, FormGroup> $groups
     *
     * @return array<string, FormGroup>
     */
    private function sortGroups(array $groups): array
    {
        // Sort groups by order, then by identifier (alphabetically)
        \uasort($groups, function (FormGroup $a, FormGroup $b): int {
            if ($a->order === $b->order) {
                return $a->identifier <=> $b->identifier;
            }

            return $a->order <=> $b->order;
        });

        return $groups;
    }
}
