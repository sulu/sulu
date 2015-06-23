<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Jackalope\Query\Query;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Util\QOM\QueryBuilder;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\Content\Structure\Snippet;
use Sulu\Component\PHPCR\SessionManager\SessionManager;

/**
 * Repository class for snippets.
 *
 * Responsible for retrieving snippets from the content repository
 */
class SnippetRepository
{
    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * @var ContentMapper
     */
    private $contentMapper;

    /**
     * @param SessionManager $sessionManager
     * @param ContentMapper $contentMapper
     */
    public function __construct(SessionManager $sessionManager, ContentMapper $contentMapper)
    {
        $this->contentMapper = $contentMapper;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Return the nodes which refer to the structure with the
     * given UUID.
     *
     * @param string UUID
     *
     * @return \PHPCR\NodeInterface[]
     */
    public function getReferences($uuid)
    {
        $session = $this->sessionManager->getSession();
        $node = $session->getNodeByIdentifier($uuid);

        return iterator_to_array($node->getReferences());
    }

    /**
     * Return snippets identified by the given UUIDs.
     *
     * UUIDs which fail to resolve to a snippet will be ignored.
     *
     * @param array $uuids
     * @param string $languageCode
     *
     * @return Snippet[]
     */
    public function getSnippetsByUuids(array $uuids = array(), $languageCode)
    {
        $snippets = array();

        foreach ($uuids as $uuid) {
            try {
                $snippet = $this->contentMapper->load($uuid, null, $languageCode);
                $snippets[] = $snippet;
            } catch (\PHPCR\ItemNotFoundException $e) {
                // ignore not found items
            }
        }

        return $snippets;
    }

    /**
     * Return snippets.
     *
     * If $type is given then only return the snippets of that type.
     *
     * @param string $languageCode
     * @param string $type Optional snippet type
     * @param int $offset Optional offset
     * @param int $max Optional max
     * @param string $search
     * @param string $sortBy
     * @param string $sortOrder
     *
     * @throws \InvalidArgumentException
     *
     * @return Snippet[]
     */
    public function getSnippets(
        $languageCode,
        $type = null,
        $offset = null,
        $max = null,
        $search = null,
        $sortBy = null,
        $sortOrder = null
    ) {
        $query = $this->getSnippetsQuery($languageCode, $type, $offset, $max, $search, $sortBy, $sortOrder);

        return $this->contentMapper->loadByQuery($query, $languageCode, null, false, true);
    }

    /**
     * Return snippets amount.
     *
     * If $type is given then only return the snippets of that type.
     *
     * @param string $languageCode
     * @param string $type Optional snippet type
     * @param string $search
     * @param string $sortBy
     * @param string $sortOrder
     *
     * @throws \InvalidArgumentException
     *
     * @return Snippet[]
     */
    public function getSnippetsAmount(
        $languageCode,
        $type = null,
        $search = null,
        $sortBy = null,
        $sortOrder = null
    ) {
        $query = $this->getSnippetsQuery($languageCode, $type, null, null, $search, $sortBy, $sortOrder);
        $result = $query->execute();

        return count(iterator_to_array($result->getRows()));
    }

    /**
     * Return snippets load query.
     *
     * If $type is given then only return the snippets of that type.
     *
     * @param string $languageCode
     * @param string $type Optional snippet type
     * @param int $offset Optional offset
     * @param int $max Optional max
     * @param string $search
     * @param string $sortBy
     * @param string $sortOrder
     *
     * @return Query
     */
    private function getSnippetsQuery(
        $languageCode,
        $type = null,
        $offset = null,
        $max = null,
        $search = null,
        $sortBy = null,
        $sortOrder = null
    ) {
        $snippetNode = $this->sessionManager->getSnippetNode($type);
        $workspace = $this->sessionManager->getSession()->getWorkspace();
        $queryManager = $workspace->getQueryManager();

        $qf = $queryManager->getQOMFactory();
        $qb = new QueryBuilder($qf);

        $qb->from(
            $qb->qomf()->selector('a', 'nt:unstructured')
        );

        if (null === $type) {
            $qb->where(
                $qb->qomf()->descendantNode('a', $snippetNode->getPath())
            );
        } else {
            $qb->where(
                $qb->qomf()->childNode('a', $snippetNode->getPath())
            );
        }

        $qb->andWhere(
            $qb->qomf()->comparison(
                $qb->qomf()->propertyValue('a', 'jcr:mixinTypes'),
                QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                $qb->qomf()->literal('sulu:snippet')
            )
        );

        if (null !== $offset) {
            $qb->setFirstResult($offset);

            if (null === $max) {
                // we get zero results if no max specified
                throw new \InvalidArgumentException(
                    'If you specify an offset then you must also specify $max'
                );
            }

            $qb->setMaxResults($max);
        }

        if (null !== $search) {
            $searchConstraint = $qf->orConstraint(
                $qf->comparison(
                    $qf->propertyValue('a', 'i18n:' . $languageCode . '-title'),
                    QueryObjectModelConstantsInterface::JCR_OPERATOR_LIKE,
                    $qf->literal('%' . $search . '%')
                ),
                $qf->comparison(
                    $qf->propertyValue('a', 'template'),
                    QueryObjectModelConstantsInterface::JCR_OPERATOR_LIKE,
                    $qf->literal('%' . $search . '%')
                )
            );
            $qb->andWhere($searchConstraint);
        }

        // Title is a mandatory property for snippets
        // NOTE: Prefixing the language code and namespace here is bad. But the solution is
        //       refactoring (i.e. a node property name translator service).
        $sortOrder = ($sortOrder !== null ? strtoupper($sortOrder) : 'ASC');
        $sortBy = ($sortBy !== null ? $sortBy : 'title');

        $qb->orderBy(
            $qb->qomf()->propertyValue('a', 'i18n:' . $languageCode . '-' . $sortBy),
            $sortOrder !== null ? strtoupper($sortOrder) : 'ASC'
        );

        return $qb->getQuery();
    }
}
