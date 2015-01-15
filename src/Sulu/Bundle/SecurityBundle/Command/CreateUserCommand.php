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

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\RoleInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class CreateUserCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setName('sulu:security:user:create')
            ->setDescription('Create a user.')
            ->setDefinition(
                array(
                    new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                    new InputArgument('firstName', InputArgument::REQUIRED, 'The FirstName'),
                    new InputArgument('lastName', InputArgument::REQUIRED, 'The LastName'),
                    new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                    new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
                    new InputArgument('role', InputArgument::REQUIRED, 'The role'),
                    new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                )
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localizations = $this->getContainer()->get('sulu.core.localization_manager')->getLocalizations();
        $locales = array();

        foreach ($localizations as $localization) {
            $locales[] = $localization->getLocalization();
        }

        $username = $input->getArgument('username');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $emailText = $input->getArgument('email');
        $locale = $input->getArgument('locale');
        $roleName = $input->getArgument('role');
        $password = $input->getArgument('password');

        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();

        $emailTypes = $doctrine->getRepository('SuluContactBundle:EmailType')->findAll();

        if (!$emailTypes) {
            throw new \RuntimeException(
                'Cannot find any SuluContactBundle:EmailType entities in database, maybe you ' .
                'should load the fixtures?'
            );
        }

        $user = $doctrine->getRepository('SuluSecurityBundle:User')->findOneByUsername($username);

        if ($user) {
            $output->writeln(sprintf('<error>User "%s" already exists</error>', $username));
            return 1;
        }

        $email = new Email();
        $email->setEmail($emailText);
        $email->setEmailType($emailTypes[0]);

        $em->persist($email);
        $em->flush();

        $now = new DateTime();

        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->addEmail($email);
        $contact->setCreated($now);
        $contact->setChanged($now);
        $contact->setMainEmail($email->getEmail());

        $em->persist($contact);
        $em->flush();

        $user = new User();
        $user->setContact($contact);
        $user->setUsername($username);
        $user->setSalt($this->generateSalt());
        $user->setPassword($this->encodePassword($user, $password, $user->getSalt()));
        $user->setLocale($locale);

        $role = $doctrine->getRepository('SuluSecurityBundle:Role')->findOneBy(array('name' => $roleName));

        if (!$role) {
            throw new \InvalidArgumentException(sprintf(
                'Role "%s" not found. The following roles are registered: "%s"',
                $roleName,
                implode('", "', $this->getRoleNames())
            ));
        }

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale(json_encode($locales)); // set all locales
        $em->persist($userRole);

        $em->persist($user);
        $em->flush();

        $output->writeln(
            sprintf('Created user <comment>%s</comment> in role <comment>%s</comment>', $username, $roleName)
        );
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $doctrine = $this->getDoctrine();

        if (!$input->getArgument('username')) {
            $question = new Question('Please choose a username: ');
            $question->setValidator(
                function ($username) use ($doctrine) {
                    if (empty($username)) {
                        throw new \InvalidArgumentException('Username can not be empty');
                    }

                    $users = $doctrine->getRepository('SuluSecurityBundle:User')->findBy(
                        array('username' => $username)
                    );
                    if (count($users) > 0) {
                        throw new \InvalidArgumentException(sprintf('Username "%s" is not unique', $username));
                    }

                    return $username;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('username', $value);
        }

        if (!$input->getArgument('firstName')) {
            $question = new Question('Please choose a FirstName: ');
            $question->setValidator(
                function ($firstName) use ($doctrine) {
                    if (empty($firstName)) {
                        throw new \InvalidArgumentException('FirstName can not be empty');
                    }

                    return $firstName;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('firstName', $value);
        }

        if (!$input->getArgument('lastName')) {
            $question = new Question('Please choose a LastName: ');
            $question->setValidator(
                function ($lastName) use ($doctrine) {
                    if (empty($lastName)) {
                        throw new \InvalidArgumentException('LastName can not be empty');
                    }

                    return $lastName;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('lastName', $value);
        }

        if (!$input->getArgument('email')) {
            $question = new Question('Please choose a Email: ');
            $question->setValidator(
                function ($email) use ($doctrine) {
                    if (empty($email)) {
                        throw new \InvalidArgumentException('Email can not be empty');
                    }

                    return $email;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('email', $value);
        }

        if (!$input->getArgument('locale')) {
            $question = new Question('Please choose a locale: ');
            $question->setValidator(
                function ($locale) use ($doctrine) {
                    if (empty($locale)) {
                        throw new \InvalidArgumentException('Locale can not be empty');
                    }

                    return $locale;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('locale', $value);
        }

        if (!$input->getArgument('role')) {
            $roleNames = $this->getRoleNames();

            $question = new ChoiceQuestion(
                'Please choose a role: ',
                $roleNames,
                0
            );
            $value = $helper->ask($input, $output, $question);
            $input->setArgument('role', $value);
        }

        if (!$input->getArgument('password')) {
            $question = new Question('Please choose a Password: ');
            $question->setHidden(true);
            $question->setValidator(
                function ($password) use ($doctrine) {
                    if (empty($password)) {
                        throw new \InvalidArgumentException('Password can not be empty');
                    }

                    return $password;
                }
            );

            $value = $helper->ask($input, $output, $question);
            $input->setArgument('password', $value);
        }
    }

    /**
     * Generates a random salt for the password
     * @return string
     */
    private function generateSalt()
    {
        return $this->getContainer()->get('sulu_security.salt_generator')->getRandomSalt();
    }

    /**
     * Encodes the given password, for the given password, with he given salt and returns the result
     * @param $user
     * @param $password
     * @param $salt
     * @return mixed
     */
    private function encodePassword($user, $password, $salt)
    {
        $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder($user);

        return $encoder->encodePassword($password, $salt);
    }

    /**
     * Return the names of all the roles
     *
     * @return array
     * @throws RuntimeException If no roles exist
     */
    private function getRoleNames()
    {
        $roleNames = $this->getDoctrine()->getRepository('SuluSecurityBundle:Role')->getRoleNames();

        if (empty($roleNames)) {
            throw new \RuntimeException(sprintf(
                'The system currently has no roles. Use the "sulu:security:role:create" command to create roles.'
            ));
        }
    }

    /**
     * Return the doctrine service
     *
     * @return Doctrine\Common\Persistence\ManagerRegistry
     */
    private function getDoctrine()
    {
        return $this->getContainer()->get('doctrine');
    }
}
