<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Command;

use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class CreateRoleCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setName('sulu:security:role:create')
            ->setDescription('Create a role.')
            ->setDefinition(
                array(
                    new InputArgument('name', InputArgument::REQUIRED, 'Name of role'),
                    new InputArgument('system', InputArgument::REQUIRED, 'System where role should be valid')
                )
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $name = $input->getArgument('name');
        $system = $input->getArgument('system');

        $repository = $em->getRepository('SuluSecurityBundle:Role');

        $role = $repository->findOneByName($name);

        if ($role) {
            $output->writeln(sprintf(
                '<error>Role "%s" already exists</error>',
                $name
            ));

            return 1;
        }

        $role = new Role();
        $role->setName($name);
        $role->setSystem($system);

        $pool = $this->getContainer()->get('sulu_admin.admin_pool');
        $securityContexts = $pool->getSecurityContexts();

        // flatten contexts
        $securityContextsFlat = array();

        array_walk_recursive(
            $securityContexts['Sulu'],
            function ($value) use (&$securityContextsFlat) {
                $securityContextsFlat[] = $value;
            }
        );

        foreach ($securityContextsFlat as $securityContext) {
            $permission = new Permission();
            $permission->setRole($role);
            $permission->setContext($securityContext);
            $permission->setPermissions(120);
            $role->addPermission($permission);
        }

        $em->persist($role);
        $em->flush();

        $output->writeln(
            sprintf(
                'Created role <comment>%s</comment> in system <comment>%s</comment>',
                $role->getName(),
                $role->getSystem()
            )
        );
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $doctrine = $this->getContainer()->get('doctrine');

        $pool = $this->getContainer()->get('sulu_admin.admin_pool');
        $contexts = $pool->getSecurityContexts();
        $systems = array_keys($contexts);

        if (!$input->getArgument('name')) {
            $question = new Question('Please choose a rolename: ');
            $question->setValidator(
                function ($name) use ($doctrine) {
                    if (empty($name)) {
                        throw new \InvalidArgumentException('Rolename cannot be empty');
                    }

                    $roles = $doctrine->getRepository('SuluSecurityBundle:Role')->findBy(array('name' => $name));
                    if (count($roles) > 0) {
                        throw new \InvalidArgumentException(sprintf('Rolename "%s" is not unique', $name));
                    }

                    return $name;
                }
            );

            $name = $helper->ask($input, $output, $question);
            $input->setArgument('name', $name);
        }

        if (!$input->getArgument('system')) {
            $question = new ChoiceQuestion(
                'Please choose a system: ',
                $systems,
                0
            );

            $system = $helper->ask($input, $output, $question);
            $input->setArgument('system', $system);
        }
    }
}
