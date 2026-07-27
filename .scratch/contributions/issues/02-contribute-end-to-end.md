# 02 — Contribute to a Wish through the payment gateway

**What to build:** A logged-in User contributes an amount to a Wish. They are sent to the payment
gateway, and on returning successfully their Contribution counts towards the Wish's received total.
If the payment fails or is abandoned, nothing is counted against the Wish, but the attempt is still
recorded for auditing.

Every Contribution is treated as Public at this stage and carries no Message — tickets 03 and 04 add
those. The point of this ticket is the money path and the lifecycle invariant.

Amounts above the Wish's remaining price are **accepted**. A Wish's received total is allowed to
exceed its price, and nothing in validation or the schema may cap it.

**Backend only.** No forms, no progress bars. Assert against Inertia props, redirects, and database
state.

**Blocked by:** 01 — needs the Wish page to display a received total against.

**Status:** ready-for-agent

- [ ] Submitting a contribution creates a Contribution with status `pending` and a Payment with status `pending`, linked by `contributions.payment_id`
- [ ] The Contributor is redirected to the gateway, and the gateway's transaction id and driver name are stored on the Payment
- [ ] Successful verification sets the Contribution to `paid`, the Payment to `verified`, and records the reference id
- [ ] Failed verification **deletes** the Contribution while the Payment **survives** with status `failed`
- [ ] The Wish page's received total counts only `paid` Contributions — `pending` and deleted ones never appear
- [ ] The Contributor count on the Wish page reflects paid Contributions
- [ ] An amount greater than the Wish's remaining price is accepted, and the total is allowed to exceed the price
- [ ] Validation rejects zero, negative, and non-numeric amounts
- [ ] A guest attempting to contribute is rejected and nothing is written to the database
- [ ] Each lifecycle transition is atomic — a crash cannot leave a paid Contribution without a verified Payment, or vice versa
- [ ] Feature tests cover every branch above using the `Local` driver, with no real network calls
- [ ] `vendor/bin/pint --dirty --format agent` runs clean

## Schema

**`contributions`** — belongs to a Wish; belongs to a User via a **`contributor_id`** foreign key
exposed as a `contributor` relation. The column is deliberately not `user_id`: a Wish already has a
`user_id` meaning its Owner, and reusing the name would make the two roles indistinguishable. The FK
is required — Contributors must be logged-in Users, and there is no guest path. Also holds `amount`
(integer, same unit as `wishes.price`), a nullable `message`, a `visibility` enum
(`public` | `owner` | `hidden`), a `status` enum (`pending` | `paid`), and `payment_id`.

**`payments`** — holds `amount`, the gateway `transaction_id`, the `reference_id` returned on
verification, the `driver` name, and a `status` enum (`pending` | `verified` | `failed`).

Create the full `visibility` enum with all three cases now even though only `public` is exercised
here; ticket 03 gives the other two meaning.

## Ownership direction — read before touching the schema

Governed by **ADR-0001** (`docs/adr/0001-contributions-are-provisional-payments-are-permanent.md`),
which is binding.

The foreign key lives on `contributions.payment_id`, pointing at `payments` — **not** the reverse. A
Payment must be able to outlive the Contribution it was created alongside, so it cannot depend on it.
Inverting this to `payments.contribution_id` is the intuitive move and it breaks the audit trail: the
failure path deletes the Contribution, and a cascading FK would take the failed Payment with it.

Because a failed Contribution is deleted rather than marked failed, retrying means creating a fresh
Contribution and Payment pair. The previous rows are never reused.

## Payment gateway

`shetabit/payment` v6 is already installed. Publish its config so the driver and credentials are
configurable per environment.

The flow: build an `Invoice` with the amount, then `Payment::purchase($invoice, $callback)`. The
callback receives `$driver` and `$transactionId` — persist both onto the pending Payment. Set the
callback URL to the verification route. On return,
`Payment::amount(...)->transactionId(...)->verify()` yields a receipt exposing `getReferenceId()`.
A failed verification throws `InvalidPaymentException` — that is the branch that deletes the
Contribution.

`shetabit/multipay` ships a **`Local` driver** (`vendor/shetabit/multipay/src/Drivers/Local`). Point
the payment config at it in tests. No hand-rolled fake or Mockery double of the package is needed.

Hardening the callback against replay and amount tampering is ticket 05 — do not build it here, but
do not design something that makes it awkward either.

## Notes

Follow the existing `Wish` model's conventions: `protected $guarded = []`, full `@property` PHPDoc
blocks, and typed relation methods. Build `ContributionFactory` and `PaymentFactory` on the shape of
`WishFactory` — sane defaults with related models via `::factory()`, plus named state methods for the
meaningful variants (each status, and a paid-with-verified-payment pair) so later tickets read as
intent rather than setup.

`Wish` gains a `contributions` relation and a way to report its received total.

**Existing work in progress on disk.** Empty generated stubs already exist for the `Contribution` and
`Payment` models, their factories, and their migrations — finish them rather than recreating them.
`app/Enums/` holds a generated `ContributionVisibility` stub plus a **misplaced nested
`app/Enums/Enums/` directory** containing `ContributionStatus` and `PaymentStatus`, an artefact of a
bad `make:enum` argument. Delete the nested directory and regenerate those two enums at the correct
path.

**The application has no login route yet.** Standard `auth` middleware redirects to a route named
`login`, which would error. Reject guests in a way that does not depend on a login page existing, and
assert that behaviour rather than a redirect to a named login route.
