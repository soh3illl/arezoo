# 05 — Harden the payment callback

**What to build:** The payment verification route becomes safe to hit repeatedly and safe to hit with
hostile input. Gateways retry their callbacks, users refresh the return page, and the callback URL is
reachable by anyone who knows it. None of that may corrupt a Wish's totals or let someone settle a
payment they did not make.

**Backend only.**

**Blocked by:** 02 — hardens the verification route that 02 builds.

**Status:** ready-for-agent

- [ ] Hitting the callback twice with the same transaction does not count a Contribution twice
- [ ] Hitting the callback again after a failure does not resurrect the deleted Contribution
- [ ] A callback carrying an unknown transaction id is rejected, and nothing is created or modified
- [ ] Verification uses the amount **stored on our Payment record**, never an amount supplied in the request
- [ ] A callback for a Payment already `verified` or already `failed` is a no-op rather than a re-run of the lifecycle
- [ ] Feature tests cover each case above against the `Local` driver
- [ ] `vendor/bin/pint --dirty --format agent` runs clean

## Why each case matters

**Replay.** Gateways retry callbacks on timeout, and users refresh return pages. Without idempotency,
one payment inflates a Wish's total by however many times the URL is hit — and the totals are the
thing the whole feature exists to report.

**Resurrection.** The failure path deletes the Contribution while keeping the Payment. A later
callback for that same transaction must not recreate a Contribution from the surviving Payment row.

**Amount tampering.** If verification trusts an amount from the request, someone can start a tiny
Contribution and settle it as a large one, crediting a Wish with money that never moved. The stored
amount is the only trustworthy figure.

**Unknown transactions.** The callback URL is public. A request naming a transaction we never created
must be rejected outright rather than causing a lookup on a null record.

## Notes

This ticket deliberately follows 02 rather than being folded into it — 02 is already the heaviest
ticket in the set, and these cases are easier to reason about once the happy and failure paths exist
and are green.

The lifecycle invariant from **ADR-0001** still holds throughout: a Payment survives independently of
its Contribution, so a no-op path must leave the Payment row intact and untouched.
