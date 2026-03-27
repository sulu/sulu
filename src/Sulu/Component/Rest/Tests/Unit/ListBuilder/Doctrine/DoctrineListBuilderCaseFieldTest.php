<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Doctrine;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineCaseFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeRegistry;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DoctrineListBuilderCaseFieldTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateInExpressionForCaseFieldUsesRegisteredFieldDescriptor(): void
    {
        $entityManager = $this->prophesize(EntityManager::class);
        $filterTypeRegistry = $this->prophesize(FilterTypeRegistry::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $systemStore = $this->prophesize(SystemStoreInterface::class);
        $systemStore->getSystem()->willReturn('Sulu');

        $doctrineListBuilder = new DoctrineListBuilder(
            $entityManager->reveal(),
            'Sulu\Bundle\CoreBundle\Entity\Example', // @phpstan-ignore argument.type
            $filterTypeRegistry->reveal(),
            $eventDispatcher->reveal(),
            [PermissionTypes::VIEW => 64],
            new AccessControlQueryEnhancer($systemStore->reveal(), $entityManager->reveal())
        );

        $templateKeyFieldDescriptor = new DoctrineCaseFieldDescriptor(
            'templateKey',
            new DoctrineDescriptor(
                'dimensionContent',
                'templateKey',
                [
                    'dimensionContent' => new DoctrineJoinDescriptor(
                        'dimensionContent',
                        'Sulu\Bundle\CoreBundle\Entity\Example.dimensionContents'
                    ),
                ]
            ),
            new DoctrineDescriptor(
                'ghostDimensionContent',
                'templateKey',
                [
                    'ghostDimensionContent' => new DoctrineJoinDescriptor(
                        'ghostDimensionContent',
                        'Sulu\Bundle\CoreBundle\Entity\Example.dimensionContents'
                    ),
                ]
            )
        );

        $doctrineListBuilder->setFieldDescriptors([
            'templateKey' => $templateKeyFieldDescriptor,
        ]);

        $expression = $doctrineListBuilder->createInExpression($templateKeyFieldDescriptor, ['article']);

        $getUniqueExpressionFieldDescriptors = (new \ReflectionClass($doctrineListBuilder))
            ->getMethod('getUniqueExpressionFieldDescriptors');
        $getUniqueExpressionFieldDescriptors->setAccessible(true);

        $expressionFieldDescriptors = $getUniqueExpressionFieldDescriptors->invoke(
            $doctrineListBuilder,
            [$expression]
        );

        $this->assertSame([$templateKeyFieldDescriptor], $expressionFieldDescriptors);
    }
}
