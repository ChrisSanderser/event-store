<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Projection;

use ArrayIterator;
use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Exception\StreamNotFound;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\Util\ArrayCache;

abstract class AbstractProjection extends AbstractQuery implements Projection
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var ArrayCache
     */
    protected $cachedStreamNames;

    /**
     * @var int
     */
    protected $eventCounter = 0;

    /**
     * @var int
     */
    protected $persistBlockSize;

    public function __construct(EventStore $eventStore, string $name, int $cacheSize, int $persistBlockSize)
    {
        if ($persistBlockSize <= 0) {
            throw new InvalidArgumentException('PersistBlockSize must be a positive integer');
        }

        parent::__construct($eventStore);

        $this->name = $name;
        $this->cachedStreamNames = new ArrayCache($cacheSize);
        $this->persistBlockSize = $persistBlockSize;
    }

    abstract protected function load(): void;

    abstract protected function persist(): void;

    protected function resetProjection(): void
    {
        $this->eventStore->delete(new StreamName($this->name));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function emit(Message $event): void
    {
        $this->linkTo($this->name, $event);
    }

    public function linkTo(string $streamName, Message $event): void
    {
        $sn = new StreamName($streamName);

        if ($this->cachedStreamNames->has($streamName)) {
            $append = true;
        } else {
            $this->cachedStreamNames->rollingAppend($streamName);
            $append = $this->eventStore->hasStream($sn);
        }

        if ($append) {
            $this->eventStore->appendTo($sn, new ArrayIterator([$event]));
        } else {
            $this->eventStore->create(new Stream($sn, new ArrayIterator([$event])));
        }
    }

    public function reset(): void
    {
        parent::reset();

        $this->resetProjection();
    }

    public function run(bool $keepRunning = true): void
    {
        if (null === $this->position
            || (null === $this->handler && empty($this->handlers))
        ) {
            throw new RuntimeException('No handlers configured');
        }

        do {
            $this->load();

            if (! $this->eventStore->hasStream(new StreamName($this->name))) {
                $this->eventStore->create(new Stream(new StreamName($this->name), new ArrayIterator()));
            }

            $singleHandler = null !== $this->handler;

            foreach ($this->position->streamPositions() as $streamName => $position) {
                try {
                    $stream = $this->eventStore->load(new StreamName($streamName), $position + 1);
                } catch (StreamNotFound $e) {
                    // no newer events found
                    continue;
                }

                if ($singleHandler) {
                    $this->handleStreamWithSingleHandler($streamName, $stream->streamEvents());
                } else {
                    $this->handleStreamWithHandlers($streamName, $stream->streamEvents());
                }

                if ($this->isStopped) {
                    break;
                }
            }

            if ($this->eventCounter > 0) {
                $this->persist();
                $this->eventCounter = 0;
            }
        } while ($keepRunning && ! $this->isStopped);
    }

    protected function handleStreamWithSingleHandler(string $streamName, Iterator $events): void
    {
        $this->currentStreamName = $streamName;
        $handler = $this->handler;

        foreach ($events as $event) {
            /* @var Message $event */
            $this->position->inc($streamName);
            $this->eventCounter++;

            $result = $handler($this->state, $event);

            if (is_array($result)) {
                $this->state = $result;
            }

            if ($this->eventCounter === $this->persistBlockSize) {
                $this->persist();
                $this->eventCounter = 0;
            }

            if ($this->isStopped) {
                break;
            }
        }
    }

    protected function handleStreamWithHandlers(string $streamName, Iterator $events): void
    {
        $this->currentStreamName = $streamName;

        foreach ($events as $event) {
            /* @var Message $event */
            $this->position->inc($streamName);
            $this->eventCounter++;

            if (! isset($this->handlers[$event->messageName()])) {
                continue;
            }

            $handler = $this->handlers[$event->messageName()];
            $result = $handler($this->state, $event);

            if (is_array($result)) {
                $this->state = $result;
            }

            if ($this->eventCounter === $this->persistBlockSize) {
                $this->persist();
                $this->eventCounter = 0;
            }

            if ($this->isStopped) {
                break;
            }
        }
    }

    protected function createHandlerContext(?string &$streamName)
    {
        return new class($this, $streamName) {
            /**
             * @var Projection
             */
            private $projection;

            /**
             * @var ?string
             */
            private $streamName;

            public function __construct(Projection $projection, ?string &$streamName)
            {
                $this->projection = $projection;
                $this->streamName = &$streamName;
            }

            public function stop(): void
            {
                $this->projection->stop();
            }

            public function linkTo(string $streamName, Message $event): void
            {
                $this->projection->linkTo($streamName, $event);
            }

            public function emit(Message $event): void
            {
                $this->projection->emit($event);
            }

            public function streamName(): ?string
            {
                return $this->streamName;
            }
        };
    }
}
