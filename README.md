# Wanguard plugin dla LMS

List last network incidents reported by Wanguard.

![](wanguard-welcome.png?raw=true)
![](wanguard-node.png?raw=true)

## Requirements

Installed [LMS](https://lms.org.pl/) or [LMS-PLUS](https://lms-plus.org) (recommended).

## Installation

* Copy files to `<path-to-lms>/plugins/`
* Run `composer update` or `composer update --no-dev`
* Go to LMS website and activate it `Configuration => Plugins`

## Configuration

* Import `schema.sql` into your LMS database
* Import default settings `configexport-wanguard-wartoscglobalna.ini`
* Go to `<path-to-lms>/?m=configlist` and customize the settings for yourself if needed
* Put in `/etc/cron.d/` file `lms-wanguard-update-db.cron` and customize the settings for yourself if needed

## Donation

* Bitcoin (BTC): bc1qvwahntcrwjtdp0ntfd0l6kdvdr9d9h6atp6qrr
