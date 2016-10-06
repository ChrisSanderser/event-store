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

namespace Prooph\EventStore\Exception;

/**
 * Class RuntimeException
 *
 * @package Prooph\EventStore\Exception
 * @author Alexander Miertsch <contact@prooph.de>
 */
class RuntimeException extends \RuntimeException implements EventStoreException
{
}
