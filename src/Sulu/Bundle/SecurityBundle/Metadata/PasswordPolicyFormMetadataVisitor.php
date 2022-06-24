<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataVisitorInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class PasswordPolicyFormMetadataVisitor implements FormMetadataVisitorInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string|null
     */
    private $passwordPattern;

    /**
     * @var string|null
     */
    private $passwordInformationTranslationKey;

    public function __construct(
        TranslatorInterface $translator,
        ?string $passwordPattern = null,
        ?string $passwordInformationTranslationKey = null
    ) {
        $this->translator = $translator;
        $this->passwordPattern = $passwordPattern;
        $this->passwordInformationTranslationKey = $passwordInformationTranslationKey;
    }

    public function visitFormMetadata(FormMetadata $formMetadata, string $locale, array $metadataOptions = []): void
    {
        if (null === $this->passwordPattern
            || null === $this->passwordInformationTranslationKey
            || ('user_details' !== $formMetadata->getKey() && 'profile_details' !== $formMetadata->getKey())
        ) {
            return;
        }

        /** @var FieldMetadata $passwordField */
        $passwordField = $formMetadata->getItems()['password'];
        $passwordField->setDescription($this->translator->trans($this->passwordInformationTranslationKey, [], 'admin'));

        $schema = new SchemaMetadata(
            [new PropertyMetadata('password', false, new StringMetadata(null, null, $this->passwordPattern))]
        );
        $formMetadata->setSchema($formMetadata->getSchema()->merge($schema));
    }
}
