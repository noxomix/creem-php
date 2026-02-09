# Endpoint Matrix

Status: Verified
Last Updated: 2026-02-09

This matrix maps canonical Creem endpoints to current SDK surface.

| Method | Path | Domain | V1 Scope | SDK Method |
| ------ | ---- | ------ | -------- | ---------- |
| POST | /v1/checkouts | Checkouts | Yes | checkouts()->create(...) |
| GET | /v1/checkouts | Checkouts | Yes | checkouts()->retrieve(checkoutId) |
| POST | /v1/products | Products | No | products()->create(...) |
| GET | /v1/products | Products | No | products()->retrieve(productId) |
| GET | /v1/products/search | Products | No | products()->search(pageNumber, pageSize, query) |
| GET | /v1/customers | Customers | Yes | customers()->retrieve(customerId) |
| GET | /v1/customers | Customers | Yes | customers()->retrieveByEmail(email) |
| GET | /v1/customers/list | Customers | Yes | customers()->list(...) |
| GET | /v1/subscriptions | Subscriptions | Yes | subscriptions()->retrieve(subscriptionId) |
| POST | /v1/subscriptions/{id} | Subscriptions | Yes | subscriptions()->update(id, ...) |
| POST | /v1/subscriptions/{id}/upgrade | Subscriptions | Yes | subscriptions()->upgrade(id, ...) |
| POST | /v1/subscriptions/{id}/cancel | Subscriptions | Yes | subscriptions()->cancel(id, ...) |
| POST | /v1/subscriptions/{id}/pause | Subscriptions | Yes | subscriptions()->pause(id, ...) |
| POST | /v1/subscriptions/{id}/resume | Subscriptions | Yes | subscriptions()->resume(id, ...) |
| GET | /v1/discounts | Discounts | No | discounts()->retrieve(discountId: ...) |
| GET | /v1/discounts | Discounts | No | discounts()->retrieve(discountCode: ...) |
| POST | /v1/discounts | Discounts | No | discounts()->create(...) |
| DELETE | /v1/discounts/{id}/delete | Discounts | No | discounts()->delete(id) |
| GET | /v1/transactions | Transactions | Yes | transactions()->retrieve(transactionId) |
| GET | /v1/transactions/search | Transactions | Yes | transactions()->search(...) |
| POST | /v1/customers/billing | Customers | Yes | customers()->createBillingLink(...) |
| POST | /v1/licenses/activate | Licenses | No | licenses()->activate(...) |
| POST | /v1/licenses/validate | Licenses | No | licenses()->validate(...) |
| POST | /v1/licenses/deactivate | Licenses | No | licenses()->deactivate(...) |

Notes:
- `GET /v1/checkouts` is modeled as retrieve-by-query in V1, not as a generic list endpoint.
- Endpoint behavior was re-checked against `llms-full.txt` canonical endpoint summary sections on 2026-02-09.
