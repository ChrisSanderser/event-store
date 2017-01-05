<?php
/**
 * This file is part of the prooph/event-store.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Iterator;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Projection\Projection;
use Prooph\EventStore\Projection\ProjectionFactory;
use Prooph\EventStore\Projection\ProjectionOptions;
use Prooph\EventStore\Projection\Query;
use Prooph\EventStore\Projection\QueryFactory;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\Projection\ReadModelProjectionFactory;

interface EventStore
{
    public function fetchStreamMetadata(StreamName $streamName): array;

    public function updateStreamMetadata(StreamName $streamName, array $newMetadata): void;

    public function hasStream(StreamName $streamName): bool;

    public function create(Stream $stream): void;

    public function appendTo(StreamName $streamName, Iterator $streamEvents): void;

    public function load(
        StreamName $streamName,
        int $fromNumber = 1,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Stream;

    public function loadReverse(
        StreamName $streamName,
        int $fromNumber = PHP_INT_MAX,
        int $count = null,
        MetadataMatcher $metadataMatcher = null
    ): Stream;

    public function delete(StreamName $streamName): void;

    public function createQuery(QueryFactory $factory = null): Query;

    public function createProjection(
        string $name,
        ProjectionOptions $options = null,
        ProjectionFactory $factory = null
    ): Projection;

    public function createReadModelProjection(
        string $name,
        ReadModel $readModel,
        ProjectionOptions $options = null,
        ReadModelProjectionFactory $factory = null
    ): ReadModelProjection;

    public function getDefaultQueryFactory(): QueryFactory;

    public function getDefaultProjectionFactory(): ProjectionFactory;

    public function getDefaultReadModelProjectionFactory(): ReadModelProjectionFactory;
}
