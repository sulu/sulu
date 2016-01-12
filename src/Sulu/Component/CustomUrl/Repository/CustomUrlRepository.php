<?php

namespace Sulu\Component\CustomUrl\Repository;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Query\RowInterface;
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

    public function findList($path)
    {
        // TODO pagination

        $session = $this->sessionManager->getSession();
        $queryManager = $session->getWorkspace()->getQueryManager();

        $qomFactory = $queryManager->getQOMFactory();
        $queryBuilder = new QueryBuilder($qomFactory);

        $queryBuilder->select('a', 'jcr:uuid', 'uuid');
        $queryBuilder->addSelect('a', 'title', 'title');

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

        return array_map(
            function (RowInterface $row) {
                return $row->getValues();
            },
            iterator_to_array($result->getRows())
        );
    }
}
