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

use Prooph\EventStore\Adapter\InMemoryAdapter;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\PersistenceEvent\PostCommitEvent;
use Prooph\EventStore\Stream\EventId;
use Prooph\EventStore\Stream\EventName;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamEvent;
use Prooph\EventStore\Stream\StreamName;
use Zend\EventManager\Event;

/**
 * Class EventStoreTest
 *
 * @package Prooph\EventStoreTest
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class EventStoreTest extends TestCase
{
    /**
     * @var EventStore
     */
    private $eventStore;

    protected function setUp()
    {
        $inMemoryAdapter = new InMemoryAdapter();

        $config = new Configuration();

        $config->setAdapter($inMemoryAdapter);

        $this->eventStore = new EventStore($config);
    }

    /**
     * @test
     */
    public function it_creates_a_new_stream_and_records_the_stream_events()
    {
        $recordedEvents = array();

        $this->eventStore->getPersistenceEvents()->attach('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $stream = $this->eventStore->load(new StreamName('user'));

        $this->assertEquals('user', $stream->streamName()->toString());

        $this->assertEquals(1, count($stream->streamEvents()));

        $this->assertEquals(1, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_stops_stream_creation_when_listener_stops_propagation()
    {
        $recordedEvents = array();

        $this->eventStore->getPersistenceEvents()->attach('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->getPersistenceEvents()->attach('create.pre', function (Event $event) {
            $event->stopPropagation(true);
        });

        $this->eventStore->beginTransaction();

        $stream = $this->getTestStream();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->assertEquals(0, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_breaks_stream_creation_when_it_is_not_in_transaction()
    {
        $this->setExpectedException('RuntimeException');

        $this->eventStore->create($this->getTestStream());
    }

    /**
     * @test
     */
    public function it_appends_events_to_stream_and_records_them()
    {
        $recordedEvents = array();

        $this->eventStore->getPersistenceEvents()->attach('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $this->eventStore->create($this->getTestStream());

        $this->eventStore->commit();

        $secondStreamEvent = new StreamEvent(
            EventId::generate(),
            new EventName('UsernameChanged'),
            array('new_name' => 'John Doe'),
            2,
            new \DateTime()
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo(new StreamName('user'), array($secondStreamEvent));

        $this->eventStore->commit();

        $this->assertEquals(2, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_does_not_append_events_when_listener_stops_propagation()
    {
        $recordedEvents = array();

        $this->eventStore->getPersistenceEvents()->attach('commit.post', function (PostCommitEvent $event) use (&$recordedEvents) {
            foreach ($event->getRecordedEvents() as $recordedEvent) {
                $recordedEvents[] = $recordedEvent;
            }
        });

        $this->eventStore->beginTransaction();

        $this->eventStore->create($this->getTestStream());

        $this->eventStore->commit();

        $this->eventStore->getPersistenceEvents()->attach('appendTo.pre', function (Event $event) {
            $event->stopPropagation(true);
        });

        $secondStreamEvent = new StreamEvent(
            EventId::generate(),
            new EventName('UsernameChanged'),
            array('new_name' => 'John Doe'),
            2,
            new \DateTime()
        );

        $this->eventStore->beginTransaction();

        $this->eventStore->appendTo(new StreamName('user'), array($secondStreamEvent));

        $this->eventStore->commit();

        $this->assertEquals(1, count($recordedEvents));
    }

    /**
     * @test
     */
    public function it_breaks_appending_events_when_it_is_not_in_active_transaction()
    {
        $stream = $this->getTestStream();

        $this->eventStore->beginTransaction();

        $this->eventStore->create($stream);

        $this->eventStore->commit();

        $this->setExpectedException('RuntimeException');

        $this->eventStore->appendTo($stream->streamName(), $stream->streamEvents());
    }

    /**
     * @return Stream
     */
    private function getTestStream()
    {
        $streamEvent = new StreamEvent(
            EventId::generate(),
            new EventName('UserCreated'),
            array('name' => 'Alex', 'email' => 'contact@prooph.de'),
            1,
            new \DateTime()
        );

        return new Stream(new StreamName('user'), array($streamEvent));
    }
}
 