<?php


namespace Sulu\Bundle\AuditBundle\Service;


use Sulu\Bundle\AuditBundle\Entity\Trail;
use Sulu\Bundle\AuditBundle\Repository\TrailRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TrailService implements TrailServiceInterface
{
    /**
     * @var string|\Stringable|\Symfony\Component\Security\Core\User\UserInterface
     */
    private $loginUser;

    /**
     * @var TrailRepository
     */
    private $trailRepository;


    public function __construct(
        TokenStorageInterface $tokenStorage,
        TrailRepository $trailRepository
    )
    {
        $this->trailRepository = $trailRepository;
        if ($tokenStorage->getToken()) {
            $this->loginUser = $tokenStorage->getToken()->getUser();
        }
    }

    public function createTrailByObject($object)
    {
        /** @var Trail $trail */
        $trail = $this->trailRepository->createNew();

        $trail->setTriggerId($this->loginUser->getId());
        $trail->setTriggerClass(get_class($this->loginUser));
        $trail->setTargetId($object->getId());
        $trail->setTargetClass(get_class($object->getChanger()));
    }

    public function createTrailByNameAndChanges($id, string $name, array $changes,string $event)
    {
        /** @var Trail $trail */
        $trail = $this->trailRepository->createNew();

        if($this->loginUser){
            $trail->setTriggerId($this->loginUser->getId());
        }

        $trail->setTriggerClass(get_class($this->loginUser));
        $trail->setTargetId($id);
        $trail->setTargetClass($name);
        $trail->setEvent($event);
        $trail->setChanges($changes);

        return $trail;
    }
}
