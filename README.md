# CakePHP QueueScheduler plugin

[![CI](https://github.com/dereuromark/cakephp-queue-scheduler/actions/workflows/ci.yml/badge.svg)](https://github.com/dereuromark/cakephp-queue-scheduler/actions/workflows/ci.yml)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-queue-scheduler/master.svg)](https://codecov.io/github/dereuromark/cakephp-queue-scheduler/branch/master)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-queue-scheduler/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-queue-scheduler)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)](https://php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![License](https://poser.pugx.org/dereuromark/cakephp-queue-scheduler/license.svg)](LICENSE)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-queue-scheduler/d/total)](https://packagist.org/packages/dereuromark/cakephp-queue-scheduler)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-purple.svg?style=flat-square)](https://github.com/php-fig-rectified/fig-rectified-standards)

A scheduler addon for [Queue plugin](https://github.com/dereuromark/cakephp-queue).

Manage recurring Queue jobs in a database-backed admin. The dispatcher queues due tasks; Queue workers execute them separately.

Schedules support cron expressions, relative intervals, time windows, and manual runs. Use one scheduler dispatcher per database; workers can run on several hosts.

[Documentation](https://dereuromark.github.io/cakephp-queue-scheduler/) · [Installation](https://dereuromark.github.io/cakephp-queue-scheduler/guide/installation) · [Getting started](https://dereuromark.github.io/cakephp-queue-scheduler/guide/getting-started)

![Scheduler admin](docs/screenshot.png)

## Credit where credit is due
This plugin is heavily inspired by [LordSimal Scheduler plugin](https://github.com/LordSimal/cakephp-scheduler)
and [Laravel Task Scheduling Feature](https://laravel.com/docs/10.x/scheduling).

Use this Scheduler plugin if you prefer configuring them file-based and purely in crontab style.
