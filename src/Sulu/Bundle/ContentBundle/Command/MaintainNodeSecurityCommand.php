<?php

namespace Sulu\Bundle\ContentBundle\Command;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates the cached security settings on the nodes.
 */
class MaintainNodeSecurityCommand extends ContainerAwareCommand
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('sulu:content:security:maintain')
            ->setDescription('Updates the security cache on the node base on the values in the database');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');

        /** @var Webspace $webspace */
        foreach ($webspaceManager->getWebspaceCollection() as $webspace) {
            $this->updateWebspace($webspace, $output);
        }

        $this->documentManager->flush();
    }

    /**
     * Updates the security information of an entire webspace.
     *
     * @param Webspace $webspace
     * @param OutputInterface $output
     */
    private function updateWebspace(Webspace $webspace, OutputInterface $output)
    {
        $output->writeln('<info>> Upgrade Webspace: ' . $webspace->getName() . '</info>');

        /** @var SessionManagerInterface $sessionManager */
        $sessionManager = $this->getContainer()->get('sulu.phpcr.session');

        $homepageDocument = $this->documentManager->find($sessionManager->getContentPath($webspace->getKey()));

        foreach ($homepageDocument->getChildren() as $childDocument) {
            $this->updateDocument($output, $childDocument, 0);
        }
    }

    /**
     * Updates the security information of a single document.
     *
     * @param OutputInterface $output
     * @param BasePageDocument $document
     * @param int $depth
     */
    private function updateDocument(OutputInterface $output, $document, $depth)
    {
        $output->write(str_repeat('-', $depth + 1) . '> ');
        $output->writeln($document->getTitle());

        /** @var AccessControlManagerInterface $accessControlManager */
        $accessControlManager = $this->getContainer()->get('sulu_security.access_control_manager');
        $permissions = $this->getAllowedPermissions(
            $accessControlManager->getPermissions(WebspaceBehavior::class, $document->getUuid())
        );

        $document->setPermissions($permissions);
        $this->documentManager->persist($document, 'en'); // TODO remove language as soon as possible

        foreach ($document->getChildren() as $childDocument) {
            $this->updateDocument($output, $childDocument, $depth + 1);
        }
    }

    /**
     * Extracts the keys of the allowed permissions into an own array.
     *
     * @param $rolePermissions
     *
     * @return array
     */
    private function getAllowedPermissions($rolePermissions)
    {
        $allowedPermissions = [];

        foreach ($rolePermissions as $role => $permissions) {
            $allowedPermissions[$role] = [];
            foreach ($permissions as $permission => $allowed) {
                if ($allowed) {
                    $allowedPermissions[$role][] = $permission;
                }
            }
        }

        return $allowedPermissions;
    }
}
