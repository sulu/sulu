<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Metadata\FormMetadata\Validation;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\TextEditorFieldMetadataValidator;
use Sulu\Component\Content\Exception\InvalidTextEditorConfigException;

class TextEditorFieldMetadataValidatorTest extends TestCase
{
    private TextEditorFieldMetadataValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new TextEditorFieldMetadataValidator(['default', 'mini']);
    }

    /**
     * @param OptionMetadata[]|bool|int|string|null $configValue
     */
    private function createField(
        string $type,
        array|bool|int|string|null $configValue = null,
        ?string $paramName = 'config',
    ): FieldMetadata {
        $field = new FieldMetadata('teaser');
        $field->setType($type);

        if (null !== $configValue && null !== $paramName) {
            $option = new OptionMetadata();
            $option->setName($paramName);
            $option->setValue($configValue);
            $field->addOption($option);
        }

        return $field;
    }

    public function testKnownConfigPasses(): void
    {
        $this->validator->validate($this->createField('text_editor', 'mini'), 'page_default');

        $this->expectNotToPerformAssertions();
    }

    public function testUnknownConfigThrows(): void
    {
        $this->expectException(InvalidTextEditorConfigException::class);
        $this->expectExceptionMessageMatches('/does_not_exist/');
        $this->expectExceptionMessageMatches('/default, mini/');
        $this->expectExceptionMessageMatches('/page_default/');

        $this->validator->validate($this->createField('text_editor', 'does_not_exist'), 'page_default');
    }

    /**
     * The parser types XML attributes, so a numeric or boolean looking config name never arrives as a string.
     * Skipping those instead of comparing them let the administration interface blank the whole form.
     */
    public function testNonStringConfigIsComparedRatherThanSkipped(): void
    {
        $this->expectException(InvalidTextEditorConfigException::class);
        $this->expectExceptionMessageMatches('/2024/');

        $this->validator->validate($this->createField('text_editor', 2024), 'page_default');
    }

    public function testNumericConfigNameThatExistsPasses(): void
    {
        $validator = new TextEditorFieldMetadataValidator(['default', '2024']);
        $validator->validate($this->createField('text_editor', 2024), 'page_default');

        $this->expectNotToPerformAssertions();
    }

    public function testOtherFieldTypesAreIgnored(): void
    {
        $this->validator->validate($this->createField('text_line', 'does_not_exist'), 'page_default');

        $this->expectNotToPerformAssertions();
    }

    public function testPropertyWithoutConfigParamPasses(): void
    {
        $this->validator->validate($this->createField('text_editor'), 'page_default');

        $this->expectNotToPerformAssertions();
    }

    /**
     * @group legacy
     */
    public function testDeprecatedFormatsParamTriggersDeprecation(): void
    {
        $this->expectUserDeprecationMessageMatches('/"formats" param of the "text_editor" property "teaser"/');

        $this->validator->validate($this->createField('text_editor', [], 'formats'), 'page_default');
    }

    /**
     * @group legacy
     */
    public function testDeprecatedEnterModeParamTriggersDeprecation(): void
    {
        $this->expectUserDeprecationMessageMatches('/"enter_mode" param of the "text_editor" property "teaser"/');

        $this->validator->validate($this->createField('text_editor', 'br', 'enter_mode'), 'page_default');
    }
}
