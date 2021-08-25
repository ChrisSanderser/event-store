<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore;

use Prooph\EventStore\Exception\InvalidArgumentException;

/** @psalm-immutable */
class PersistentSubscriptionCreateStatus
{
    public const OPTIONS = [
        'Success' => 0,
        'AlreadyExists' => 1,
        'Failure' => 2,
    ];

    public const SUCCESS = 0;
    public const ALREADY_EXISTS = 1;
    public const FAILURE = 2;

    private string $name;
    private int $value;

    private function __construct(string $name)
    {
        $this->name = $name;
        $this->value = self::OPTIONS[$name];
    }

    public static function success(): self
    {
        return new self('Success');
    }

    public static function alreadyExists(): self
    {
        return new self('AlreadyExists');
    }

    public static function failure(): self
    {
        return new self('Failure');
    }

    public static function byName(string $value): self
    {
        if (! isset(self::OPTIONS[$value])) {
            throw new InvalidArgumentException('Unknown enum name given');
        }

        return new self($value);
    }

    public static function byValue(int $value): self
    {
        foreach (self::OPTIONS as $name => $v) {
            if ($v === $value) {
                return new self($name);
            }
        }

        throw new InvalidArgumentException('Unknown enum value given');
    }

    /** @psalm-mutation-free */
    public function equals(PersistentSubscriptionCreateStatus $other): bool
    {
        return \get_class($this) === \get_class($other) && $this->name === $other->name;
    }

    /** @psalm-mutation-free */
    public function name(): string
    {
        return $this->name;
    }

    /** @psalm-mutation-free */
    public function value(): int
    {
        return $this->value;
    }

    /** @psalm-mutation-free */
    public function __toString(): string
    {
        return $this->name;
    }
}
