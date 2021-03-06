<?php
/**
 * This file is part of the prooph/micro.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\MicroExample\Infrastructure\InMemoryEmailGuard;
use Prooph\MicroExample\Model\UniqueEmailGuard;
use Prooph\SnapshotStore\InMemorySnapshotStore;
use Prooph\SnapshotStore\SnapshotStore;

$factories = [
    'eventStore' => function (): \Prooph\EventStore\EventStore {
        static $eventStore = null;

        if (null === $eventStore) {
            $eventStore = new InMemoryEventStore();
        }

        return $eventStore;
    },
    'snapshotStore' => function (): SnapshotStore {
        static $snapshotStore = null;

        if (null === $snapshotStore) {
            $snapshotStore = new InMemorySnapshotStore();
        }

        return $snapshotStore;
    },
    'dummyProducer' => function (): callable {
        return function (Message $message): void {
        };
    },
    'amqpChannel' => function (): AMQPChannel {
        static $channel = null;

        if (null === $channel) {
            $connection = new AMQPConnection();
            $connection->connect();

            $channel = new AMQPChannel($connection);
        }

        return $channel;
    },
    'emailGuard' => function (): UniqueEmailGuard {
        static $emailGuard = null;

        if (null === $emailGuard) {
            $emailGuard = new InMemoryEmailGuard();
        }

        return $emailGuard;
    },
];

$factories['amqpProducer'] = function () use ($factories): callable {
    return \Prooph\Micro\AmqpPublisher\buildPublisher(
        $factories['amqpChannel'](),
        new NoOpMessageConverter(),
        'micro'
    );
};

$factories['startAmqpTransaction'] = function () use ($factories): callable {
    return function () use ($factories): void {
        $channel = $factories['amqpChannel']();
        $channel->startTransaction();
    };
};

$factories['commitAmqpTransaction'] = function () use ($factories): callable {
    return function () use ($factories): void {
        $channel = $factories['amqpChannel']();
        $result = $channel->commitTransaction();

        if (false === $result) {
            \Prooph\Micro\AmqpPublisher\throwCommitFailed();
        }
    };
};

return $factories;
