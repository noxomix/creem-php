# Architecture Diff: Creem PHP vs Mollie Reference

Last Updated: 2026-02-09

This document captures intentional differences between `noxomix/creem-php` and the benchmark reference `references/mollie-api-php`.

## Adopted Patterns

- Root client as stable SDK entry (`CreemClient`).
- Typed exception mapping from HTTP status + error envelope.
- Transport abstraction that keeps HTTP internals outside domain surface.
- Mock-first unit tests for transport/retry/error behavior.

## Intentional Differences (V1)

- No magic-property endpoint API (`$client->payments`) in V1.
- No broad trait graph for client composition.
- No large endpoint surface before V1 revenue-critical scope is complete.
- Retry policy remains explicit in SDK code, not optional middleware wiring.
- Webhook duplicate handling is modeled as a tiny atomic `claim`/`release` contract instead of adding locking frameworks or queue-specific integrations.

## Rationale

- Keep implementation understandable and auditable for first production release.
- Reduce hidden behavior and integration ambiguity.
- Preserve room for extension only when there is evidence-driven need.
