<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Repository;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Util\QOM\QueryBuilder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Repository enables direct access to custom-urls without document-manager.
 */
class CustomUrlRepository
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(SessionManagerInterface $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Returns list of custom-url data-arrays.
     *
     * @param string $path
     *
     * @return \Iterator
     */
    public function findList($path)
    {
        // TODO pagination

        $session = $this->sessionManager->getSession();
        $queryManager = $session->getWorkspace()->getQueryManager();

        $qomFactory = $queryManager->getQOMFactory();
        $queryBuilder = new QueryBuilder($qomFactory);

        $queryBuilder->select('a', 'jcr:uuid', 'uuid');
        $queryBuilder->addSelect('a', 'title', 'title');
        $queryBuilder->addSelect('a', 'published', 'published');

        $queryBuilder->from(
            $queryBuilder->qomf()->selector('a', 'nt:unstructured')
        );
        $queryBuilder->where(
            $queryBuilder->qomf()->comparison(
                $queryBuilder->qomf()->propertyValue('a', 'jcr:mixinTypes'),
                QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                $queryBuilder->qomf()->literal('sulu:custom-url')
            )
        );
        $queryBuilder->andWhere(
            $queryBuilder->qomf()->descendantNode('a', $path)
        );

        $query = $queryBuilder->getQuery();
        $result = $query->execute();

        return new RowsIterator($result->getRows(), $result->getColumnNames());
    }
}
