# Error Code Matrix

Status: Draft
Last Updated: 2026-02-09

This matrix defines baseline exception mapping. Vendor-specific `code` values from Creem must always be preserved on the thrown exception.

| Failure Category | Trigger | Exception Class (planned) | Retry | Notes |
| ---------------- | ------- | ------------------------- | ----- | ----- |
| Authentication | HTTP 401/403 | AuthenticationException | No | Invalid API key or missing auth |
| Validation | HTTP 400/422 | ValidationException | No | Input/schema errors |
| Not Found | HTTP 404 | NotFoundException | No | Resource absent |
| Conflict | HTTP 409 | ConflictException | No | State conflict/idempotency conflict |
| Rate Limit | HTTP 429 | RateLimitException | Yes | Retry with backoff and jitter |
| Server Error | HTTP 5xx | ServerException | Yes | Retry bounded by policy |
| Network | Transport/timeout errors | NetworkException | Yes | Retry bounded by policy |
| Unknown API Error | Unknown envelope/code | ApiException | Conditional | Preserve raw body + diagnostics |

Diagnostics requirements:
- Preserve `trace_id` when present.
- Preserve HTTP status.
- Preserve message array/text.
- Preserve vendor error `code` when present.
