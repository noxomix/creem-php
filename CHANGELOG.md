# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Constitution and TODO baseline for a framework-agnostic Creem PHP SDK.
- Local package governance rules for scope, quality gates, and release behavior.
- New domain services for post-V1 endpoints: `products`, `discounts`, and `licenses`.
- Request DTOs for product, discount, and license operations.
- Resource wrappers for product, discount, and license responses.
- Retry and error-mapping contract matrix tests for core transport behavior.
- Security redaction tests for API key and webhook secret non-disclosure paths.
- New subscription request DTOs for cancel/pause/resume write flows.
- Expanded typed accessor coverage on V1 read resources (`checkout`, `customer`, `subscription`, `transaction`).

### Changed
- Package focus fixed on `creem-php` core SDK; framework adapters are out of core scope.
- Webhook idempotency flow now uses atomic `claim`/`release` semantics to prevent duplicate dispatch races under concurrent delivery.
- Constitution updated with explicit implementation learnings for production webhook idempotency storage and `RequestOptions`-first raw API safety.
- Core contract compatibility improved for `customer_portal_link`, customer lookup by email, alternate pagination shapes, and `unpaid` subscription status handling.
- Network exception handling now redacts sensitive token material in logs and exception messages.
- Write methods now use DTO-first service signatures instead of raw-array payload unions.
- Public namespace changed to `Noxomix\\CreemPhp\\` and examples/autoload mappings were aligned.
- Endpoint matrix re-validated against `llms-full.txt` canonical endpoint summary sections.
- `UpdateSubscriptionRequest::withUpdateBehavior(...)` now validates canonical Creem values from `llms-full.txt`.
- README/tests now use canonical `update_behavior` values and constitution wording no longer treats `unpaid` as a webhook event.

## [0.1.0] - 2026-02-09

### Added
- Initial package scaffold (`composer.json`, base config/client classes, PHPUnit setup).
- Initial unit tests for configuration and endpoint composition.
