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

namespace ProophTest\EventStore\Plugin;

use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\EventStore\ActionEventEmitterEventStoreTestCase;
use ProophTest\EventStore\Mock\EventLoggerPlugin;
use ProophTest\EventStore\Mock\UserCreated;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class PluginManagerTest extends ActionEventEmitterEventStoreTestCase
{
    /**
     * @test
     */
    public function an_invokable_plugin_is_loaded_by_plugin_manager_and_attached_to_event_store_by_configuration(): void
    {
        $pluginManager = new ServiceManager([
            'invokables' => [
                'eventlogger' => EventLoggerPlugin::class,
            ],
        ]);

        $logger = $pluginManager->get('eventlogger');
        $logger->attachToEventStore($this->eventStore);

        $this->eventStore->create(
            new Stream(
                new StreamName('user'),
                new \ArrayIterator([
                    UserCreated::with(
                        [
                            'name' => 'Alex',
                            'email' => 'contact@prooph.de',
                        ],
                        1
                    ),
                ])
            )
        );

        $loggedStreamEvents = $pluginManager->get('eventlogger')->getLoggedStreamEvents();

        $this->assertEquals(1, count($loggedStreamEvents));
    }
}
