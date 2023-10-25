# CakePHP QueueScheduler plugin

[![CI](https://github.com/dereuromark/cakephp-queue-scheduler/actions/workflows/ci.yml/badge.svg)](https://github.com/dereuromark/cakephp-queue-scheduler/actions/workflows/ci.yml)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-queue-scheduler/master.svg)](https://codecov.io/github/dereuromark/cakephp-queue-scheduler/branch/master)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-queue-scheduler/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-queue-scheduler)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](https://php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![License](https://poser.pugx.org/dereuromark/cakephp-queue-scheduler/license)](https://packagist.org/packages/dereuromark/cakephp-queue-scheduler)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-queue-scheduler/d/total)](https://packagist.org/packages/dereuromark/cakephp-queue-scheduler)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A scheduler addon for [Queue plugin](https://github.com/dereuromark/cakephp-queue).

This branch is for use with **CakePHP 4.4+**. For details see [version map](https://github.com/dereuromark/cakephp-queue-scheduler/wiki#cakephp-version-map).

## Motivation
A DB and queue based cronjob system does not require a sys-admin to manually modify the crontab file on a server.
It also allows for easier multi-server (worker) environments.

Such a system provides easier input and "frequency" validation, as well as visual output of the schedule in the backend.
For smaller projects it can also be ported more easily between systems (e.g. dev/stage/prod) using migrations
instead of having to touch the server config.

Also logging and i18n customization can potentially be easier to get addressed this way.

Finally, with the Queue plugin integrating it can smoothly work alongside your existing Queue tasks.
The backend can provide buttons to also "trigger" any such schedule manually where needed.

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

```
composer require dereuromark/cakephp-queue-scheduler
```

## Installation and Usage
See [Documentation](docs/).
