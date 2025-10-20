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

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class GroupProvider implements GroupProviderInterface
{
    public function __construct(
        private MetadataProviderInterface $metadataProvider,
        private TranslatorInterface $translator,
    ) {
    }

    public function getGroups(): array
    {
        /** @var TypedFormMetadata $metadata */
        $metadata = $this->metadataProvider->getMetadata(ArticleInterface::TEMPLATE_TYPE, '', []);

        $groups = [];

        foreach ($metadata->getForms() as $articleForm) {
            $group = $articleForm->getGroup() ?: 'default';

            if (!\array_key_exists($group, $groups)) {
                $groups[$group] = new FormGroup($group, $this->getGroupTitle($group));
            }
            $groups[$group] = $groups[$group]->withTemplate($articleForm->getKey());
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
