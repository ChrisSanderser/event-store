<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 06/06/14 - 10:35 PM
 */

namespace Prooph\EventStore\Stream;

use Assert\Assertion;
use Prooph\Common\Messaging\Message;

/**
 * Class Stream
 *
 * @package Prooph\EventStore\Stream
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class Stream
{
    /**
     * @var StreamName
     */
    protected $streamName;

    /**
     * @var Message[]
     */
    protected $streamEvents;

    /**
     * @param StreamName $streamName
     * @param Message[] $streamEvents
     */
    public function __construct(StreamName $streamName, array $streamEvents)
    {
        foreach ($streamEvents as $streamEvent) {
            Assertion::isInstanceOf($streamEvent, Message::class);
        }

        $this->streamName = $streamName;

        $this->streamEvents = $streamEvents;
    }

    /**
     * @return StreamName
     */
    public function streamName()
    {
        return $this->streamName;
    }

    /**
     * @return Message[]
     */
    public function streamEvents()
    {
        return $this->streamEvents;
    }
}
