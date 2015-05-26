<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\EventSubscriber\ORM;

use Prophecy\Argument;

class UserBlameSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->loadClassMetadataEvent = $this->prophesize('Doctrine\ORM\Event\LoadClassMetadataEventArgs');

        $this->onFlushEvent = $this->prophesize('Doctrine\ORM\Event\OnFlushEventArgs');

        $this->userBlameObject = $this->prophesize('\stdClass')
            ->willImplement('Sulu\Component\Persistence\Model\UserBlameInterface');
        $this->classMetadata = $this->prophesize('Doctrine\ORM\Mapping\ClassMetadata');
        $this->refl = $this->prophesize('\ReflectionClass');
        $this->entityManager = $this->prophesize('Doctrine\ORM\EntityManager');
        $this->unitOfWork = $this->prophesize('Doctrine\ORM\UnitOfWork');
        $this->user = $this->prophesize('Sulu\Component\Security\Authentication\UserInterface');
        $this->token = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->tokenStorage = $this->prophesize('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage');

        $this->subscriber = new UserBlameSubscriber($this->tokenStorage->reveal(), true);

        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->token->getUser()->willReturn($this->user->reveal());
        $this->onFlushEvent->getEntityManager()->willReturn($this->entityManager);
        $this->entityManager->getUnitOfWork()->willReturn($this->unitOfWork->reveal());
    }

    public function testLoadClassMetadata()
    {
        $this->loadClassMetadataEvent->getClassMetadata()->willReturn($this->classMetadata->reveal());
        $this->classMetadata->getReflectionClass()->willReturn($this->refl->reveal());
        $this->refl->implementsInterface('Sulu\Component\Persistence\Model\UserBlameInterface')->willReturn(true);

        $this->classMetadata->hasAssociation('creator')->shouldBeCalled();
        $this->classMetadata->hasAssociation('changer')->shouldBeCalled();
        $this->classMetadata->mapManyToOne(Argument::any())->shouldBeCalled();

        $this->subscriber->loadClassMetadata($this->loadClassMetadataEvent->reveal());
    }

    public function provideLifecycle()
    {
        return array(
            // changer not overridden, creator is not null
            // RESULT: Only set changer
            array(
                array(
                    'changer' => array(0 => 5, 1 => 5),
                    'creator' => array(0 => 1, 1 => null),
                ),
                array(
                    'changer',
                ),
            ),
            // changer is null, creator is null
            // RESULT: Set creator and changer
            array(
                array(
                    'changer' => array(0 => null, 1 => null),
                    'creator' => array(0 => null, 1 => null),
                ),
                array(
                    'creator',
                    'changer',
                ),
            ),
            // changer has been overridden, creator is null
            // RESULT: Set creator and changer
            array(
                array(
                    'changer' => array(0 => null, 1 => 3),
                    'creator' => array(0 => null, 1 => null),
                ),
                array(
                    'creator',
                ),
            ),
            // changer is has been changed, creator has been changed
            // RESULT: Do not set anything
            array(
                array(
                    'changer' => array(0 => 1, 1 => 2),
                    'creator' => array(0 => 1, 1 => 2),
                ),
                array(
                ),
            ),
        );
    }

    /**
     * @param $changeset The changeset for the entity
     * @param $expectedFields List of filds which should be updated/set
     *
     * @dataProvider provideLifecycle
     */
    public function testOnFlush($changeset, $expectedFields)
    {
        $entity = $this->userBlameObject->reveal();

        $this->unitOfWork->getScheduledEntityInsertions()->willReturn(array(
            $entity,
        ));
        $this->unitOfWork->getScheduledEntityUpdates()->willReturn(array());

        $this->entityManager->getClassMetadata(get_class($entity))->willReturn($this->classMetadata);
        $this->unitOfWork->getEntityChangeSet($this->userBlameObject->reveal())->willReturn($changeset);

        foreach (array('creator', 'changer') as $field) {
            $prophecy = $this->classMetadata->setFieldValue(
                $this->userBlameObject->reveal(),
                $field,
                $this->user->reveal()
            );

            if (in_array($field, $expectedFields)) {
                $prophecy->shouldBeCalled();
                continue;
            }

            $prophecy->shouldNotBeCalled();
        }

        if (count($expectedFields)) {
            $this->unitOfWork->recomputeSingleEntityChangeSet(
                $this->classMetadata->reveal(),
                $this->userBlameObject->reveal()
            )->shouldBeCalled();
        }

        $this->subscriber->onFlush($this->onFlushEvent->reveal());
    }
}
