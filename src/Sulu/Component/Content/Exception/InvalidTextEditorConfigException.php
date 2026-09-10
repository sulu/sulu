<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

class InvalidTextEditorConfigException extends InvalidFieldMetadataException
{
    /**
     * @param string[] $availableConfigs
     */
    public function __construct(
        string $formKey,
        private string $propertyName,
        private string $configName,
        array $availableConfigs,
    ) {
        parent::__construct($formKey, \sprintf(
            'The "text_editor" property "%s" of the form "%s" uses the text editor config "%s", which is not configured. Configure it under "sulu_admin.text_editor.configs" or use one of: %s',
            $propertyName,
            $formKey,
            $configName,
            \implode(', ', $availableConfigs)
        ));
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getConfigName(): string
    {
        return $this->configName;
    }
}
