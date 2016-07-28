<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Contact\Model;

use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * Repository for the contacts, implementing some additional functions for querying objects.
 */
interface ContactRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a contact by id.
     *
     * @param int $id
     *
     * @return ContactInterface|null
     */
    public function findById($id);

    /**
     * Find a contacts by ids.
     *
     * @param int[] $ids
     *
     * @return ContactInterface[]
     */
    public function findByIds($ids);

    /**
     * Find a contact by id and load additional infos to delete referenced entities.
     *
     * @param int $id
     *
     * @return ContactInterface|null
     */
    public function findByIdAndDelete($id);

    /**
     * Searches Entities by where clauses, pagination and sorted.
     *
     * @param int|null $limit Page size for Pagination
     * @param int|null $offset Offset for Pagination
     * @param array|null $sorting Columns to sort
     * @param array|null $where Where clauses
     *
     * @return array
     */
    public function findGetAll($limit = null, $offset = null, $sorting = [], $where = []);

    /**
     * Searches for contacts with a specific account and the ability to exclude a certain contacts.
     *
     * @param int $accountId
     * @param null|int $excludeContactId
     * @param bool $arrayResult
     * @param bool $onlyFetchMainAccounts Defines if only main relations should be returned
     *
     * @return ContactInterface[]|array
     */
    public function findByAccountId(
        $accountId,
        $excludeContactId = null,
        $arrayResult = true,
        $onlyFetchMainAccounts = true
    );

    /**
     * Finds a contact based on criteria and one email and one phone
     * also joins account.
     *
     * @param array $where
     * @param string $email
     * @param string $phone
     *
     * @return ContactInterface|null
     */
    public function findByCriteriaEmailAndPhone($where, $email = null, $phone = null);

    /**
     * Find a contact by id.
     *
     * @param int $id
     *
     * @return ContactInterface|null
     */
    public function findContactWithAccountsById($id);
}
