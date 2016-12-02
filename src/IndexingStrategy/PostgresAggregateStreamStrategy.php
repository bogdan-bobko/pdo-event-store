<?php
/**
 * This file is part of the prooph/pdo-event-store.
 * (c) 2016-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2016-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\PDO\IndexingStrategy;

use Prooph\Common\Messaging\Message;
use Prooph\EventStore\PDO\Exception;
use Prooph\EventStore\PDO\IndexingStrategy;

final class PostgresAggregateStreamStrategy implements IndexingStrategy
{
    /**
     * @param string $tableName
     * @return string[]
     */
    public function createSchema(string $tableName): array
    {
        $statement = <<<EOT
CREATE TABLE $tableName (
    no SERIAL,
    event_id CHAR(36) NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    payload JSONB NOT NULL,
    metadata JSONB NOT NULL,
    created_at CHAR(26) NOT NULL,
    PRIMARY KEY (no),
    UNIQUE (event_id)
);
EOT;

        return [$statement];
    }

    public function columnNames(): array
    {
        return [
            'event_id',
            'event_name',
            'payload',
            'metadata',
            'created_at',
        ];
    }

    public function prepareData(Message $message, array &$data): void
    {
        if (! isset($message->metadata()['_aggregate_version'])) {
            throw new Exception\RuntimeException('_aggregate_version is missing in metadata');
        }

        $data[] = $message->metadata()['_aggregate_version'];
        $data[] = $message->uuid()->toString();
        $data[] = $message->messageName();
        $data[] = json_encode($message->payload());
        $data[] = json_encode($message->metadata());
        $data[] = $message->createdAt()->format('Y-m-d\TH:i:s.u');
    }

    /**
     * @return string[]
     */
    public function uniqueViolationErrorCodes(): array
    {
        return ['23000', '23505'];
    }
}
