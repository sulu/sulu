<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Component\Content\Exception\InvalidTextEditorConfigException;

/**
 * @internal
 */
final class TextEditorFieldMetadataValidator implements FieldMetadataValidatorInterface
{
    private const DEPRECATED_PARAMS = ['formats', 'enter_mode'];

    /**
     * @param string[] $textEditorConfigNames
     */
    public function __construct(
        private array $textEditorConfigNames,
    ) {
    }

    public function validate(FieldMetadata $fieldMetadata, string $formKey): void
    {
        if ('text_editor' !== $fieldMetadata->getType()) {
            return;
        }

        foreach ($fieldMetadata->getOptions() as $option) {
            $name = $option->getName();

            if (\in_array($name, self::DEPRECATED_PARAMS, true)) {
                @trigger_deprecation(
                    'sulu/sulu',
                    '3.1',
                    'The "%s" param of the "text_editor" property "%s" is deprecated and will be removed in 4.0. ' .
                    'Use the "config" param with a config from "sulu_admin.text_editor.configs" instead.',
                    $name,
                    $fieldMetadata->getName()
                );

                continue;
            }

            if ('config' === $name) {
                $this->validateConfigName($fieldMetadata, $formKey, $option->getValue());
            }
        }
    }

    /**
     * An XML attribute is typed by the parser, so a numeric or boolean looking config name does not arrive as a
     * string. It is compared as one rather than skipped, because the administration interface would otherwise throw
     * while rendering the form.
     */
    private function validateConfigName(FieldMetadata $fieldMetadata, string $formKey, mixed $configName): void
    {
        $configName = \is_scalar($configName) ? (string) $configName : '';

        if (\in_array($configName, $this->textEditorConfigNames, true)) {
            return;
        }

        $availableConfigs = $this->textEditorConfigNames;
        \sort($availableConfigs);

        throw new InvalidTextEditorConfigException(
            $formKey,
            $fieldMetadata->getName(),
            $configName,
            $availableConfigs
        );
    }
}
