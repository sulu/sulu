<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Domain\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Domain\Model\AuditableInterface;
use Sulu\Content\Domain\Model\AuditableTrait;

class AuditableTraitTest extends TestCase
{
    use ProphecyTrait;

    protected function getAuditableInstance(): AuditableInterface
    {
        return new class() implements AuditableInterface {
            use AuditableTrait;
        };
    }

    public function testGetSetCreated(): void
    {
        $model = $this->getAuditableInstance();
        $created = new \DateTimeImmutable('2020-05-08T00:00:00+00:00');

        $model->setCreated($created);
        $this->assertSame($created, $model->getCreated());
    }

    public function testGetSetChanged(): void
    {
        $model = $this->getAuditableInstance();
        $changed = new \DateTimeImmutable('2020-05-09T00:00:00+00:00');

        $model->setChanged($changed);
        $this->assertSame($changed, $model->getChanged());
    }

    public function testGetSetCreator(): void
    {
        $model = $this->getAuditableInstance();
        $creator = $this->prophesize(UserInterface::class);

        $this->assertNull($model->getCreator());
        $model->setCreator($creator->reveal());
        $this->assertSame($creator->reveal(), $model->getCreator());
    }

    public function testGetSetChanger(): void
    {
        $model = $this->getAuditableInstance();
        $changer = $this->prophesize(UserInterface::class);

        $this->assertNull($model->getChanger());
        $model->setChanger($changer->reveal());
        $this->assertSame($changer->reveal(), $model->getChanger());
    }

    public function testSetCreatorNull(): void
    {
        $model = $this->getAuditableInstance();
        $creator = $this->prophesize(UserInterface::class);

        $model->setCreator($creator->reveal());
        $this->assertSame($creator->reveal(), $model->getCreator());

        $model->setCreator(null);
        $this->assertNull($model->getCreator());
    }

    public function testSetChangerNull(): void
    {
        $model = $this->getAuditableInstance();
        $changer = $this->prophesize(UserInterface::class);

        $model->setChanger($changer->reveal());
        $this->assertSame($changer->reveal(), $model->getChanger());

        $model->setChanger(null);
        $this->assertNull($model->getChanger());
    }

    public function testSetCreatedReturnsInstance(): void
    {
        $model = $this->getAuditableInstance();
        $created = new \DateTimeImmutable('2020-05-08T00:00:00+00:00');

        $result = $model->setCreated($created);
        $this->assertSame($model, $result);
    }

    public function testSetChangedReturnsInstance(): void
    {
        $model = $this->getAuditableInstance();
        $changed = new \DateTimeImmutable('2020-05-08T00:00:00+00:00');

        $result = $model->setChanged($changed);
        $this->assertSame($model, $result);
    }

    public function testSetCreatorReturnsInstance(): void
    {
        $model = $this->getAuditableInstance();
        $creator = $this->prophesize(UserInterface::class);

        $result = $model->setCreator($creator->reveal());
        $this->assertSame($model, $result);
    }

    public function testSetChangerReturnsInstance(): void
    {
        $model = $this->getAuditableInstance();
        $changer = $this->prophesize(UserInterface::class);

        $result = $model->setChanger($changer->reveal());
        $this->assertSame($model, $result);
    }
}
