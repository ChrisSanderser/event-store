<?php
/*
 * This file is part of the prooph/event-store.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 20.08.15 - 18:10
 */

namespace Prooph\EventStore\Container\Stream;

use Interop\Container\ContainerInterface;
use Prooph\EventStore\Stream\SingleStreamStrategy;

/**
 * Class SingleStreamStrategyFactory
 * @package Prooph\EventStore\Container\Stream
 */
final class SingleStreamStrategyFactory
{
    /**
     * @param ContainerInterface $container
     * @return SingleStreamStrategy
     */
    public function __invoke(ContainerInterface $container)
    {
        $singleStreamName = null;

        if ($container->has('config')) {
            $config = $container->get('config');

            if (isset($config['prooph']['event_store']['single_stream_name'])) {
                $singleStreamName = $config['prooph']['event_store']['single_stream_name'];
            }
        }

        return new SingleStreamStrategy($container->get('prooph.event_store'), $singleStreamName);
    }
}
