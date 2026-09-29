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

namespace Sulu\Page\Infrastructure\Sulu\Admin\MetadataLoader;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataLoaderInterface;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;

/**
 * Provides the forms configured with the `<webspace-settings-form>` tag of the webspaces as the types of the
 * "webspace_settings" form, so the content components treat a settings form like any other template.
 *
 * @internal no backwards compatibility promise is given for this class it could be removed or changed at any time
 */
final class WebspaceSettingsFormMetadataLoader implements FormMetadataLoaderInterface
{
    public function __construct(
        private WebspaceManagerInterface $webspaceManager,
        private FormMetadataLoaderInterface $formMetadataLoader,
    ) {
    }

    /**
     * @param array{webspace?: string} $metadataOptions
     */
    public function getMetadata(string $key, string $locale, array $metadataOptions): ?MetadataInterface
    {
        if (WebspaceSettingInterface::TEMPLATE_TYPE !== $key) {
            return null;
        }

        $webspaceKey = $metadataOptions['webspace'] ?? null;

        /** @var string[] $formKeys */
        $formKeys = [];
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $webspaceSettingsForm = $webspace->getWebspaceSettingsForm();

            if (null === $webspaceSettingsForm || (null !== $webspaceKey && $webspaceKey !== $webspace->getKey())) {
                continue;
            }

            if (!\in_array($webspaceSettingsForm, $formKeys, true)) {
                $formKeys[] = $webspaceSettingsForm;
            }
        }

        $typedFormMetadata = new TypedFormMetadata();

        foreach ($formKeys as $formKey) {
            $formMetadata = $this->formMetadataLoader->getMetadata($formKey, $locale, []);

            if (!$formMetadata instanceof FormMetadata) {
                throw new \RuntimeException(\sprintf(
                    'The settings form "%s" is not defined, create it in "config/forms" with the key "%1$s".',
                    $formKey,
                ));
            }

            $typedFormMetadata->addForm($formKey, $formMetadata);
        }

        $typedFormMetadata->setDefaultType($formKeys[0] ?? '');

        return $typedFormMetadata;
    }
}
