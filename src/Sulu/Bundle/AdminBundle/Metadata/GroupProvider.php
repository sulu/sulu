<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class GroupProvider implements GroupProviderInterface
{
    public function __construct(
        private MetadataProviderRegistry $metadataProviderRegistry,
        private TranslatorInterface $translator,
    ) {
    }

    public function getGroups(string $key): array
    {
        /** @var TypedFormMetadata $metadata */
        $metadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($key, '', []);

        $groups = [];

        foreach ($metadata->getForms() as $form) {
            $group = $form->getGroup() ?: GroupProviderInterface::DEFAULT_GROUP;

            if (!\array_key_exists($group, $groups)) {
                $groups[$group] = new FormGroup($group, $this->getGroupTitle($group));
            }
            $groups[$group] = $groups[$group]->withTemplate($form->getKey());
        }

        return $groups;
    }

    private function getGroupTitle(string $groupIdentifier): string
    {
        $translationKey = 'sulu_admin.template_group.' . $groupIdentifier;
        $translated = $this->translator->trans($translationKey, [], 'admin');

        // If translation key doesn't exist, translator returns the key itself
        // In that case, fall back to ucfirst of the group identifier
        if ($translated === $translationKey) {
            return \ucfirst($groupIdentifier);
        }

        return $translated;
    }
}
