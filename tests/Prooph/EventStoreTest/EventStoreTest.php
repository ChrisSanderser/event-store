<?php
/*
 * This file is part of the prooph/event-store.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 19.04.14 - 21:27
 */

namespace Prooph\EventStoreTest;
use Prooph\EventStoreTest\Mock\User;

/**
 * Class EventStoreTest
 *
 * @package Prooph\EventStoreTest
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class EventStoreTest extends TestCase
{
    protected function setUp()
    {
        $this->getTestEventStore()->getAdapter()->createSchema(array('User'));
    }

    protected function tearDown()
    {
        $this->getTestEventStore()->getAdapter()->dropSchema(array('User'));
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_attaches_a_new_aggregate_and_fetches_it_from_identity_map()
    {
        $user = new User("Alex");

        $this->getTestEventStore()->attach($user);

        $sameUser = $this->getTestEventStore()->find(get_class($user), $user->id());

        $this->assertSame($user, $sameUser);
    }

    /**
     * @test
     */
    public function it_begins_transaction_and_saves_events_on_commit()
    {
        $this->getTestEventStore()->beginTransaction();

        $user = new User("Alex");

        $this->getTestEventStore()->attach($user);

        $user->changeName('Alexander');

        $this->getTestEventStore()->commit();

        $this->getTestEventStore()->clear();

        $equalUser = $this->getTestEventStore()->find(get_class($user), $user->id());

        $this->assertInstanceOf('Prooph\EventStoreTest\Mock\User', $equalUser);

        $this->assertNotSame($user, $equalUser);

        $this->assertEquals($user->id(), $equalUser->id());

        $this->assertEquals('Alexander', $equalUser->name());
    }

    /**
     * @test
     */
    public function it_returns_default_repository_for_unknown_aggregate_fqcn()
    {
        $repository = $this->getTestEventStore()->getRepository('Prooph\EventStoreTest\Mock\User');

        $this->assertInstanceOf('Prooph\EventStore\Repository\EventSourcingRepository', $repository);
    }
}
 