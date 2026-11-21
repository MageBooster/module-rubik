# Changelog

All notable changes to this package are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the package uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.0] - 2026-11-21

### Changed
- The licence check follows MageBooster License 1.1: without a valid serial number (missing, invalid, expired, other domain or other product) the module keeps working for 30 days, then the Rubikpara payment method is hidden at checkout (isAvailable = false). Callbacks, refunds and existing orders keep working. Development hosts (localhost, *.localhost, *.test, *.local, 127.0.0.1) always pass the domain check. The admin shows the state on every page; nothing is shown on the storefront and nothing is sent anywhere.
- Requires magebooster/module-license ^1.1. The rubik-hyva serial number is accepted too.

## [1.0.0] - 2026-11-20

First public release.

### Added
- Generic one-page checkout hand-off: `order_place_redirect_url` is set, so MageBooster Checkout (and any checkout that honours it) sends the customer to the Rubikpara start page after "Place order".
- The method is hidden when the cart currency has to be converted to TRY and the store has no rate for it, instead of failing after the order is placed.
- Composer metadata for the public repository (homepage, authors, support, keywords), README header, guides in `docs/`, `.gitignore`.

### Changed
- PHP 8.0 compatibility: readonly properties removed, `php: >=8.0` (was `^8.3`).
- Commercial MageBooster licence (WebWorks 101 OÜ) replaces the previous licence; `LICENSE.txt`, composer `license: proprietary`, copyright and licence notice in every source file.

### Security
- Return, callback and result URLs carrying an order number with an invalid signature now go to the cart. Before, a freshly signed result URL was built for any order number, so the thank-you page (order details) of any order could be opened by guessing its number.

## [0.1.0] - 2026-10-21

### Added
- Initial Rubikpara payment module for Magento 2.
