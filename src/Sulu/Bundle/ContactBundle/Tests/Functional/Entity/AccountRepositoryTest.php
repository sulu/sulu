<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Entity;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AccountRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Account[]
     */
    private $accounts = [];

    /**
     * @var Tag[]
     */
    private $tags = [];

    /**
     * @var array
     */
    private $tagData = [
        'Tag-0',
        'Tag-1',
        'Tag-2',
        'Tag-3',
    ];

    /**
     * @var array
     */
    private $accountData = [
        ['Apple', [0, 1, 2]],
        ['Google', [0, 1, 3]],
        ['Amazon', [0, 1]],
        ['Massive Art', [0, 1, 2]],
        ['Facebook', [0]],
        ['Sulu', [0]],
        ['Github', [0]],
        ['SensioLabs', []],
    ];

    public function setUp()
    {
        $this->em = $this->db('ORM')->getOm();
        $this->initOrm();
    }

    private function initOrm()
    {
        $this->purgeDatabase();

        foreach ($this->tagData as $data) {
            $this->tags[] = $this->createTag($data);
        }

        foreach ($this->accountData as $data) {
            $this->accounts[] = $this->createAccount($data[0], $data[1]);
        }

        $this->em->flush();
    }

    private function createTag($name)
    {
        $tag = new Tag();
        $tag->setName($name);

        $this->em->persist($tag);
        $this->em->flush();

        return $tag;
    }

    private function createAccount($name, $tags = [])
    {
        $account = new Account();
        $account->setName($name);

        foreach ($tags as $tag) {
            $account->addTag($this->tags[$tag]);
        }

        $this->em->persist($account);

        return $account;
    }

    public function findByProvider()
    {
        // when pagination is active the result count is pageSize + 1 to determine has next page

        return [
            // no pagination
            [[], null, 0, null, $this->accountData],
            // page 1, no limit
            [[], 1, 3, null, array_slice($this->accountData, 0, 4)],
            // page 2, no limit
            [[], 2, 3, null, array_slice($this->accountData, 3, 4)],
            // page 3, no limit
            [[], 3, 3, null, array_slice($this->accountData, 6, 2)],
            // no pagination, limit 3
            [[], null, 0, 3, array_slice($this->accountData, 0, 3)],
            // page 1, limit 5
            [[], 1, 3, 5, array_slice($this->accountData, 0, 4)],
            // page 2, limit 5
            [[], 2, 3, 5, array_slice($this->accountData, 3, 2)],
            // page 3, limit 5
            [[], 3, 3, 5, []],
            // no pagination, tag 0
            [['tags' => [0], 'tagOperator' => 'or'], null, 0, null, array_slice($this->accountData, 0, 7), [0]],
            // no pagination, tag 0 or 1
            [['tags' => [0, 1], 'tagOperator' => 'or'], null, 0, null, array_slice($this->accountData, 0, 7)],
            // no pagination, tag 0 and 1
            [['tags' => [0, 1], 'tagOperator' => 'and'], null, 0, null, array_slice($this->accountData, 0, 4), [0, 1]],
            // no pagination, tag 0 and 3
            [['tags' => [0, 3], 'tagOperator' => 'and'], null, 0, null, [$this->accountData[1]], [0, 3]],
            // page 1, no limit, tag 0
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                1,
                3,
                null,
                array_slice($this->accountData, 0, 4),
                [0],
            ],
            // page 2, no limit, tag 0
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                2,
                3,
                null,
                array_slice($this->accountData, 3, 4),
                [0],
            ],
            // page 3, no limit, tag 0
            [
                ['tags' => [0], 'tagOperator' => 'or'],
                3,
                3,
                null,
                array_slice($this->accountData, 6, 1),
                [0],
            ],
        ];
    }

    /**
     * @dataProvider findByProvider
     *
     * @param array $filters
     * @param int $page
     * @param int $pageSize
     * @param int $limit
     * @param array $expected
     * @param int[] $tags
     */
    public function testFindBy($filters, $page, $pageSize, $limit, $expected, $tags = [])
    {
        $repository = $this->em->getRepository(Account::class);

        // if tags isset replace the array indexes with database id
        if (array_key_exists('tags', $filters)) {
            $filters['tags'] = array_map(
                function ($tag) {
                    return $this->tags[$tag]->getId();
                },
                $filters['tags']
            );
        }

        $result = $repository->findByFilters($filters, $page, $pageSize, $limit);

        $length = count($expected);
        $this->assertCount($length, $result);

        for ($i = 0; $i < $length; ++$i) {
            $this->assertEquals($expected[$i][0], $result[$i]->getName(), $i);

            foreach ($tags as $tag) {
                $this->assertTrue($result[$i]->getTags()->contains($this->tags[$tag]));
            }
        }
    }

    public function findByIdsProvider()
    {
        return [
            [[0, 1, 2], array_slice($this->accountData, 0, 3)],
            [[], []],
            [[15, 99], []],
        ];
    }

    /**
     * @dataProvider findByIdsProvider
     *
     * @param array $ids
     * @param array $expected
     */
    public function testFindByIds($ids, $expected)
    {
        for ($i = 0; $i < count($ids); ++$i) {
            if (isset($this->accounts[$ids[$i]])) {
                $ids[$i] = $this->accounts[$ids[$i]]->getId();
            }
        }

        $repository = $this->em->getRepository(Account::class);

        $result = $repository->findByIds($ids);

        for ($i = 0; $i < count($expected); ++$i) {
            $this->assertEquals($ids[$i], $result[$i]->getId());
            $this->assertEquals($expected[$i][0], $result[$i]->getName());
        }
    }
}
