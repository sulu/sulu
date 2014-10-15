<?php

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\PHPCR\SessionManager\SessionManager;
use PHPCR\Util\QOM\QueryBuilder;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;

/**
 * Repository class for snippets
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
     * Return snippets
     *
     * If $type is given then only return the snippets of that type.
     *
     * @param string $languageCode
     * @param string $type Optional snippet type
     * @param integer $offset Optional offset
     * @param integer $max Optional max
     *
     * @return Snippet[]
     */
    public function getSnippets($languageCode, $type = null, $offset = null, $max = null)
    {
        $snippetNode = $this->sessionManager->getSnippetNode($type);
        $workspace = $this->sessionManager->getSession()->getWorkspace();
        $queryManager = $workspace->getQueryManager();

        $qb = new QueryBuilder($queryManager->getQOMFactory());

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

        // Title is a mandatory property for snippets
        // NOTE: Prefixing the language code and namespace here is bad. But the solution is
        //       refactoring (i.e. a node property name translator service).
        $qb->orderBy($qb->qomf()->propertyValue('a', 'i18n:' . $languageCode . '-title'), 'ASC');

        $query = $qb->getQuery();

        return $this->contentMapper->loadByQuery($query, $languageCode);
    }
}
