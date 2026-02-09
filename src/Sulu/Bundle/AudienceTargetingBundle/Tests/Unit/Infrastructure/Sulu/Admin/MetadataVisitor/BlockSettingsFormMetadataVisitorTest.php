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

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Infrastructure\Sulu\Admin\MetadataVisitor;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\XmlFormMetadataLoader;
use Sulu\Bundle\AudienceTargetingBundle\Infrastructure\Sulu\Admin\MetadataVisitor\BlockSettingsFormMetadataVisitor;

class BlockSettingsFormMetadataVisitorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<XmlFormMetadataLoader>
     */
    private $xmlFormMetadataLoader;

    private BlockSettingsFormMetadataVisitor $blockSettingsFormMetadataVisitor;

    protected function setUp(): void
    {
        $this->xmlFormMetadataLoader = $this->prophesize(XmlFormMetadataLoader::class);
        $this->blockSettingsFormMetadataVisitor = new BlockSettingsFormMetadataVisitor($this->xmlFormMetadataLoader->reveal());
    }

    public function testVisitFormMetadata(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_block_settings');
        $targetGroupsFormMetadata = new FormMetadata();
        $targetGroupsMetadata = new SectionMetadata('target_groups');
        $targetGroupsFormMetadata->addItem($targetGroupsMetadata);

        $this->xmlFormMetadataLoader->getMetadata('content_block_settings_target_groups', 'en', [])->willReturn(
            $targetGroupsFormMetadata
        );

        $this->blockSettingsFormMetadataVisitor->visitFormMetadata($formMetadata, 'en', []);

        $this->assertSame(
            [
                'target_groups' => [
                    'name' => 'target_groups',
                    'type' => 'section',
                ],
            ],
            \array_map(function(ItemMetadata $metadata): array {
                return [
                    'name' => $metadata->getName(),
                    'type' => $metadata->getType(),
                ];
            }, $formMetadata->getItems()),
        );
    }

    public function testVisitFormMetadataAlreadyExist(): void
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey('content_block_settings');
        $targetGroupsMetadata = new SectionMetadata('target_groups');
        $formMetadata->addItem($targetGroupsMetadata);

        $this->xmlFormMetadataLoader->getMetadata('content_block_settings_target_groups', 'en', [])->shouldNotBeCalled();
        $this->blockSettingsFormMetadataVisitor->visitFormMetadata($formMetadata, 'en', []);

        $this->assertSame(
            [
                'target_groups' => [
                    'name' => 'target_groups',
                    'type' => 'section',
                ],
            ],
            \array_map(function(ItemMetadata $metadata): array {
                return [
                    'name' => $metadata->getName(),
                    'type' => $metadata->getType(),
                ];
            }, $formMetadata->getItems()),
        );
    }
}
