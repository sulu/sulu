<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Query;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;

class QueryBuilderFunctionalTest extends SuluTestCase
{
    public function setUp()
    {
        $this->manager = $this->getContainer()->get('sulu_document_manager.document_manager');
    }

    /**
     * It should be able to use a structures fields as criteria.
     */
    public function testStructureFieldsCriteria()
    {
        $builder = $this->manager->createQueryBuilder();
        $builder
            ->setLocale('de')
            ->useStructure('p', 'overview')
            ->from()->document('page', 'p')->end()
            ->where()->eq()->field('p.structure#article')->literal('hello');

        $query = $builder->getQuery();
        $this->assertEquals(
            'SELECT * FROM [nt:unstructured] AS p WHERE (p.[i18n:de-article] = \'hello\' AND p.[jcr:mixinTypes] = \'sulu:page\')',
            $query->getPhpcrQuery()->getStatement()
        );
    }

    /**
     * It should throw an exception if an unknown structure field is used.
     *
     * @expectedException InvalidArgumentException 
     * @expectedExceptionMessage Unknown model property "foobar", in structure "overview". Known model properties: "title"
     */
    public function testUnknownStructureField()
    {
        $builder = $this->manager->createQueryBuilder();
        $builder
            ->setLocale('de')
            ->useStructure('p', 'overview')
            ->from()->document('page', 'p')->end()
            ->where()->eq()->field('p.structure#foobar')->literal('hello');

        $builder->getQuery();
    }

    /**
     * The standard query builder should work as normal.
     */
    public function testNormalQueryBuilder()
    {
        $builder = $this->manager->createQueryBuilder();
        $builder
            ->setLocale('de')
            ->useStructure('p', 'overview')
            ->from()->document('page', 'p')->end()
            ->where()->eq()->field('p.workflowStage')->literal(WorkflowStage::PUBLISHED);

        $query = $builder->getQuery();
        $this->assertEquals(
            'SELECT * FROM [nt:unstructured] AS p WHERE (p.[i18n:de-state] = CAST(\'2\' AS LONG) AND p.[jcr:mixinTypes] = \'sulu:page\')',
            $query->getPhpcrQuery()->getStatement()
        );
    }
}
