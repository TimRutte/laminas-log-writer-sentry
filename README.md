# laminas-log-writer-sentry

## Installation

Run the following to install this library:

```bash
$ composer require timrutte/laminas-log-writer-sentry
```

## Usage

```php
$writer = new \TimRutte\Laminas\Log\Writer\Sentry();
$writer->configureSentry(
    '<SENTRY_DSN>', 
    '<MIN_LOG_LEVEL>', 
);
$formatter = new \Laminas\Log\Formatter\Json();
$writer->setFormatter($formatter);
$logger = new \Laminas\Log\Logger();
$logger->addWriter($writer);
```

## Support

- [Issues](https://github.com/timrutte/laminas-log-writer-sentry/issues/)

