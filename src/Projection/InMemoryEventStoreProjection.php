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

use Prooph\EventStore\InMemoryEventStore;

final class InMemoryEventStoreProjection extends AbstractProjection
{
    use InMemoryEventStoreQueryTrait;

    public function __construct(InMemoryEventStore $eventStore, string $name, bool $emitEnabled)
    {
        parent::__construct($eventStore, $name, $emitEnabled);

        $this->buildKnownStreams();
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        if ($deleteEmittedEvents) {
            $this->resetProjection();
        }
    }

    protected function load(): void
    {
        // InMemoryEventStoreProjection cannot load
    }

    protected function persist(): void
    {
        // InMemoryEventStoreProjection cannot persist
    }

    protected function resetProjection(): void
    {
        $reflectionProperty = new \ReflectionProperty(get_class($this->eventStore), 'streams');
        $reflectionProperty->setAccessible(true);

        $events = $reflectionProperty->getValue($this->eventStore);
        unset($events[$this->name]);
        $reflectionProperty->setValue($this->eventStore, $events);
    }
}
