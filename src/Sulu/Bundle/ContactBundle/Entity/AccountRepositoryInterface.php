<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Entity;

use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<AccountInterface>
 */
interface AccountRepositoryInterface extends RepositoryInterface
{
    public function findById(int $id): ?AccountInterface;

    /**
     * Searches for accounts with a specific contact.
     */
    public function findOneByContactId(int $contactId): AccountInterface;

    public function findAccountOnly(int $id): ?AccountInterface;

    public function findAccountById(int $id, bool $contacts = false): ?AccountInterface;

    /**
     * @param int[] $ids
     *
     * @return AccountInterface[]
     */
    public function findByIds(array $ids): array;

    /**
     * Find all accounts but only selects given fields.
     */
    public function findAllSelect(array $fields = []): array;

    /**
     * Get account by id to delete.
     */
    public function findAccountByIdAndDelete(int $id): ?AccountInterface;

    /**
     * Distinct count account's children and contacts.
     */
    public function countDistinctAccountChildrenAndContacts(int $id): array;

    /**
     * Distinct count account's children and contacts.
     */
    public function findChildrenAndContacts(int $id): ?AccountInterface;

    public function verify();

    public function recover();
}
