# PSR-3 Dot Notation Processor

## Introduction

This package allows the use of array dot notation when using PSR-3 log message interpolation within Monolog. When using `PsrLogMessageProcessor` that ships as standard with Monolog, only the first level of `$record['context']` is parsed into the template. This processor allows you to access deeper levels of the `context` array to enrich your log messages with meaningful data that may be buried.

Example:

`$logger->info('Call time took {call_stats.transfer_time}ms.', ['call_stats' => ['transfer_time' => 587]]);`

Will translate to:

`Call time took 587ms.`

## Requirements

- Monolog 2

## Installation

`composer require griffolion/psr-dot-notation-processor`

