<?php

namespace TimRutte\Laminas\Log\Writer;

use Laminas\Log\Exception\InvalidArgumentException;
use Laminas\Log\Writer\AbstractWriter;
use Laminas\Log\Logger;
use Sentry\Severity;
use Sentry\Event;

class Sentry extends AbstractWriter
{
    /** @var string $sentryDsn Sentry DSN */
    private string $sentryDsn;

    /** @var null|string $environment Environment name of your application, e.g. production or staging */
    private ?string $environment;

    /** @var string $maxLogLevel Maximum log level to send logs to Sentry */
    private string $maxLogLevel;

    /**
     * Set Sentry settings
     *
     * @param string $sentryDsn
     * @param string|null $environment
     * @param int $maxLogLevel
     * @return void
     */
    public function configureSentry(string $sentryDsn, ?string $environment = null, int $maxLogLevel = Logger::WARN): void
    {
        $this->sentryDsn = $sentryDsn;
        $this->environment = $environment;
        $this->maxLogLevel = $maxLogLevel;
    }

    private function validateSentryConfiguration()
    {
        if (empty($this->sentryDsn)) {
            throw new InvalidArgumentException('Missing Sentry DSN');
        }

        if (!is_numeric($this->maxLogLevel)) {
            throw new InvalidArgumentException('Unknown maximum log level. Integer required.');
        }
    }
    
    public function doWrite(array $event)
    {
        $this->validateSentryConfiguration();

        switch ($event['priority']) {
            case Logger::DEBUG:
                $sentrySeverity = Severity::debug();
                break;
            case Logger::INFO:
                $sentrySeverity = Severity::info();
                break;
            case Logger::WARN:
                $sentrySeverity = Severity::warning();
                break;
            case Logger::ALERT:
            case Logger::CRIT:
                $sentrySeverity = Severity::fatal();
                break;
            case Logger::ERR:
            default:
                $sentrySeverity = Severity::error();
                break;
        }

        if ($event['priority'] > $this->maxLogLevel) {
            return;
        }

        $sentryEvent = Event::createEvent();
        $sentryEvent->setLevel($sentrySeverity);
        if ($this->environment !== null) {
            $sentryEvent->setEnvironment($this->environment);
        }
        $sentryEvent->setMessage($event['message']);

        if (is_array($event['extra'])) {
            if (count($event['extra']) > 0) {
                $sentryEvent->setContext('Data', $event['extra']);
            }
        }

        $sentryEvent->setServerName($_SERVER['HTTP_HOST'] ?? null);

        $server = [];
        if ($_SERVER) {
            $server = $_SERVER;
            if (isset($server['REMOTE_ADDR'])) unset($server['REMOTE_ADDR']);
            if (isset($server['HTTP_CLIENT_IP'])) unset($server['HTTP_CLIENT_IP']);
            if (isset($server['HTTP_X_FORWARDED_FOR'])) unset($server['HTTP_X_FORWARDED_FOR']);
            if (isset($server['HTTP_HOST'])) unset($server['HTTP_HOST']);
            if (isset($server['SERVER_ADDR'])) unset($server['SERVER_ADDR']);
        }
        $sentryEvent->setContext('Server', $server);

        \Sentry\captureEvent($sentryEvent);
    }
}