<?php

namespace Sulu\Component\DocumentManager;

use PHPCR\Util\UUIDHelper;
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use PHPCR\RepositoryException;

/**
 * The node manager is responsible for talking to the PHPCR
 * implementation.
 */
class NodeManager
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @param string $id UUID or path
     * @return NodeInterface
     * @throws DocumentNotFoundException
     */
    public function find($id)
    {
        try {
            if (UUIDHelper::isUUID($id)) {
                return $this->session->getNodeByIdentifier($id);
            }

            return $this->session->getNode($id);
        } catch (RepositoryException $e) {
            throw new DocumentNotFoundException(sprintf(
                'Could not find document with ID or path "%s"', $id
            ), null, $e);
        }
    }

    /**
     * @param string $id ID or path
     */
    public function remove($id)
    {
        $id = $this->normalizeToPath($id);
        $this->session->removeItem($id);
    }

    public function move($srcId, $destId)
    {
        $srcId = $this->normalizeToPath($srcId);
        $destId = $this->normalizeToPath($destId);

        $this->session->move($srcId, $destId);
    }

    public function copy($srcId, $destId)
    {
        $workspace = $this->session->getWorkspace();
        $srcId = $this->normalizeToPath($srcId);
        $destId = $this->normalizeToPath($destId);

        $workspace->copy($srcId, $destId);
    }

    public function save()
    {
        $this->session->save();
    }

    public function clear()
    {
        $this->session->refresh(false);
    }

    private function normalizeToPath($id)
    {
        if (UUIDHelper::isUUID($id)) {
            $id = $this->session->getNodeByIdentifier($id)->getPath();
        }

        return $id;
    }
}
