<?php

namespace JustBetter\Sentry\Plugin;

use JustBetter\Sentry\Helper\Data;
use JustBetter\Sentry\Model\SentryLog;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Logger\Monolog;
use Monolog\JsonSerializableDateTimeImmutable;
use Monolog\Level;

class MonologPlugin extends Monolog
{
    /**
     * @psalm-param array<callable(array): array> $processors
     *
     * @param string                              $name             The logging channel, a simple descriptive name that is attached to all log records
     * @param Data                                $sentryHelper
     * @param SentryLog                           $sentryLog
     * @param DeploymentConfig                    $deploymentConfig
     * @param \Monolog\Handler\HandlerInterface[] $handlers         Optional stack of handlers, the first one in the array is called first, etc.
     * @param callable[]                          $processors       Optional array of processors
     */
    public function __construct(
        $name,
        protected Data $sentryHelper,
        protected SentryLog $sentryLog,
        protected DeploymentConfig $deploymentConfig,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Adds a log record to Sentry.
     *
     * @param Level|int $level The logging level
     * @param string $message The log message
     * @param array $context The log context
     * @param JsonSerializableDateTimeImmutable|null $datetime Datetime of log
     *
     * @return bool Whether the record has been processed
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function addRecord(
        \Monolog\Level|int $level,
        string $message,
        array $context = [],
        ?\Monolog\JsonSerializableDateTimeImmutable $datetime = null
    ): bool {
        if ($this->deploymentConfig->isAvailable() && $this->sentryHelper->isActive()) {
            $this->sentryLog->send(
                $message,
                is_int($level) ? $level : $level->value,
                $context
            );
        }

        // @phpstan-ignore argument.type
        return parent::addRecord($level, $message, $context, $datetime);
    }
}
