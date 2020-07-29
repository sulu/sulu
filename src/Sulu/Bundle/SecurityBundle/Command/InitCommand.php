<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
final class InitCommand extends Command
{
    protected static $defaultName = 'sulu:security:init';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var AdminPool
     */
    private $adminPool;

    public function __construct(
        EntityManagerInterface $entityManager,
        RoleRepositoryInterface $roleRepository,
        AdminPool $adminPool
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->roleRepository = $roleRepository;
        $this->adminPool = $adminPool;
    }

    protected function configure()
    {
        $this->setName('sulu:security:init')
            ->setDescription('Create required sulu security entities.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $systems = \array_keys($this->adminPool->getSecurityContexts());
        $roles = $this->roleRepository->findAllRoles();
        $ui = new SymfonyStyle($input, $output);

        $existAnonymousRoles = [];
        foreach ($roles as $role) {
            if ($role->getAnonymous()) {
                $existAnonymousRoles[$role->getSystem()] = $role;
            }
        }

        $count = 0;
        foreach ($systems as $system) {
            if (Admin::SULU_ADMIN_SECURITY_SYSTEM === $system) {
                continue;
            }

            if (isset($existAnonymousRoles[$system])) {
                $ui->text(\sprintf(
                    '[x] Anonymous role exist in system "%s" as "%s".',
                    $system,
                    $existAnonymousRoles[$system]->getName()
                ));

                continue;
            }

            /** @var RoleInterface $role */
            $role = $this->roleRepository->createNew();
            $role->setName('Anonymous User ' . $system);
            $role->setAnonymous(true);
            $role->setSystem($system);

            $ui->text(\sprintf('[ ] Create anonymous role in system "%s" as "%s".', $system, $role->getName()));

            $this->entityManager->persist($role);

            ++$count;
        }

        if ($count) {
            $ui->success(\sprintf('Created "%s" new anonymous roles.', $count));
        }

        $this->entityManager->flush();

        return 0;
    }
}
