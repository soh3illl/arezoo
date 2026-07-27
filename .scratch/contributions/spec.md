# Contributions and Payments

Status: ready-for-agent

## Problem Statement

A Wish currently states what someone wants and what it costs, but there is no way for anyone to
actually put money towards it. A visitor who wants to help has nothing to click. An Owner has no
idea whether anyone intends to fund their Wish, no way to see how close it is to being covered, and
no way to thank the people who chipped in.

There is also a privacy problem standing in the way. Some Contributors are happy to be named
publicly, some want the Owner alone to know it was them, and some want to give without anyone —
Owner included — knowing who they are. Without control over this, a meaningful number of people
simply will not give.

## Solution

A logged-in User can contribute money to any Wish. They choose an amount, optionally attach a
Message for the Owner, and pick how visible they want to be. They are sent to a payment gateway,
and on returning successfully their Contribution counts towards the Wish.

Contributors choose one of three Visibility levels for their name and profile:

- **Public** — anyone viewing the Wish sees who they are
- **Owner-only** — only the Wish's Owner sees who they are
- **Hidden** — nobody, not even the Owner, sees who they are

The Message is separate from all of this. It is always readable by the Owner and never by anyone
else, at every Visibility level — so a Hidden Contributor can still leave the Owner a note, and the
Owner reads it without learning who sent it.

Contributions are not capped by the Wish's price. If a Wish costs 500,000 and it has already
received 480,000, someone may still contribute 100,000, and the Wish ends up over-funded. This is
intentional and must not be treated as an error.

## User Stories

### Making a Contribution

1. As a logged-in User, I want to contribute money to a Wish, so that I can help someone get the thing they want.
2. As a Contributor, I want to choose exactly how much I give, so that I can give what I can afford rather than a fixed amount.
3. As a Contributor, I want to be taken to a payment gateway to pay, so that my money actually moves.
4. As a Contributor, I want to be told clearly when my payment succeeded, so that I know my Contribution counted.
5. As a Contributor, I want to be told clearly when my payment failed, so that I am not left wondering whether I was charged.
6. As a Contributor, I want to retry after a failed payment, so that a gateway problem does not stop me from giving.
7. As a Contributor, I want to abandon the gateway without paying and have nothing recorded against the Wish, so that a change of mind leaves no trace.
8. As a Contributor, I want to contribute more than the Wish's remaining price, so that I am not blocked by an arbitrary limit when I want to be generous.
9. As a Contributor, I want to contribute to a Wish that is already fully funded, so that I can still show support for something already covered.
10. As a Contributor, I want to contribute to the same Wish more than once, so that I can give again later.
11. As a visitor who is not logged in, I want to be prompted to log in before contributing, so that my Contribution is attached to my account.

### Messages

12. As a Contributor, I want to attach a Message to my Contribution, so that I can say something personal to the Owner.
13. As a Contributor, I want the Message to be optional, so that I can give without writing anything.
14. As a Contributor, I want my Message read only by the Owner, so that I can write something private rather than a public comment.
15. As a Hidden Contributor, I want to leave a Message the Owner can read, so that I can say something meaningful while staying anonymous.
16. As an Owner, I want to read the Messages left on my Wish, so that I know what people wanted to tell me.
17. As a visitor, I want to never see other people's Messages, so that the Owner's notes stay private to them.

### Visibility

18. As a Contributor, I want to pick my Visibility level when I contribute, so that I control what is revealed about me.
19. As a Public Contributor, I want my name and profile shown to everyone, so that I get credit for helping.
20. As an Owner-only Contributor, I want only the Owner to know it was me, so that I can be recognised by the person who matters without it being public.
21. As a Hidden Contributor, I want nobody — including the Owner — to know it was me, so that I can give completely anonymously.
22. As an Owner, I want to see the names of Public and Owner-only Contributors on my Wish, so that I can thank the people who helped.
23. As an Owner, I want Hidden Contributors to stay anonymous to me too, so that the promise made to them is actually kept.
24. As a visitor, I want to see the names of Public Contributors only, so that I can see who supported a Wish without violating anyone's privacy.
25. As a Contributor, I want to always see my own Contribution on the Wish regardless of the Visibility level I chose, so that I can confirm my money landed.

### Viewing a Wish

26. As a visitor, I want to see how much a Wish has received in total, so that I know whether it still needs help.
27. As a visitor, I want to see the total even when it exceeds the Wish's price, so that over-funding is visible rather than hidden.
28. As a visitor, I want to see the number of Contributors, so that I can gauge how much support a Wish has, even when individual names are not visible to me.
29. As an Owner, I want to see every Contribution on my Wish including anonymous ones, so that the amounts always add up to the total.

### Payment records

30. As an operator, I want every gateway attempt recorded, so that I can investigate a Contributor's payment dispute.
31. As an operator, I want failed and abandoned attempts kept, so that I can spot a broken gateway rather than only seeing successes.
32. As an operator, I want the gateway's transaction and reference identifiers stored, so that I can reconcile our records against the bank's.
33. As an operator, I want a failed attempt to leave nothing counted against the Wish, so that displayed totals only ever reflect money actually received.

## Implementation Decisions

### New domain models

Two new Eloquent models, `Contribution` and `Payment`, are added alongside the existing `Wish`.

**Contribution** — money a Contributor has put towards a Wish:

- Belongs to a Wish
- Belongs to a User via a **`contributor_id`** foreign key, exposed as a `contributor` relation. The
  column is deliberately not called `user_id`; a Wish already has a `user_id` meaning its Owner, and
  reusing that name would make the two roles indistinguishable. The FK is **required** — Contributors
  must be logged-in Users, and there is no guest path.
- Holds `amount`, stored as an integer in the same unit as `wishes.price`
- Holds an optional `message`
- Holds a `visibility` enum: `public`, `owner`, `hidden`
- Holds a `status` enum: `pending`, `paid`
- Holds a `payment_id` foreign key to its Payment

**Payment** — one attempt to move money through a gateway:

- Holds `amount`, the gateway `transaction_id`, the `reference_id` returned on verification, and the
  `driver` name
- Holds a `status` enum: `pending`, `verified`, `failed`
- Holds **no** foreign key to Contribution — see the ownership direction below

Both enums-per-model are backed string enums under `App\Enums`, cast on the models. Follow the
existing `Wish` model's conventions: `protected $guarded = []`, full `@property` PHPDoc blocks, and
typed relation methods returning `BelongsTo`/`HasMany`.

`Wish` gains a `contributions` relation and a way to report its received total. The total must only
count Contributions with `status = paid`.

### Lifecycle and ownership direction

This is governed by **ADR-0001 (`docs/adr/0001-contributions-are-provisional-payments-are-permanent.md`)**
and must be implemented as written:

1. On submitting the donation form, a `Contribution` (`status = pending`) and a `Payment`
   (`status = pending`) are created together, linked by `contributions.payment_id`.
2. The Contributor is sent to the gateway.
3. On successful verification: `Contribution.status → paid`, `Payment.status → verified`, and the
   `reference_id` is recorded.
4. On failure or abandonment: the `Contribution` row is **deleted**, while the `Payment` row is
   **kept** with `status = failed`.

The foreign key therefore lives on `contributions`, pointing at `payments` — not the reverse. A
Payment must outlive the Contribution it was created alongside, so it cannot depend on it. An
implementer who "corrects" this to `payments.contribution_id` breaks the audit trail; the ADR exists
to prevent exactly that.

Because a failed Contribution is deleted rather than marked failed, retrying means creating a fresh
Contribution and Payment pair. There is no reuse of the previous rows.

The create-then-delete pair and the verify-then-flip pair must each be atomic, so a crash cannot
leave a paid Contribution without a verified Payment or vice versa.

### Visibility rules

Visibility governs the Contributor's **name and profile only**. It never governs the Message.

Resolving what a given viewer sees for a given Contribution:

| Contribution visibility | Visitor        | Owner          | The Contributor |
| ----------------------- | -------------- | -------------- | --------------- |
| `public`                | name shown     | name shown     | name shown      |
| `owner`                 | name hidden    | name shown     | name shown      |
| `hidden`                | name hidden    | name hidden    | name shown      |

The Message is orthogonal to that table: **only the Owner ever sees a Message**, at every Visibility
level, and no other viewer ever does. A Contributor viewing their own Contribution sees their own
Message.

Redaction must happen server-side. A Contribution whose identity is not visible to the current viewer
must not have the Contributor's name, profile, or user id present in the payload sent to the browser
— omitting it from the UI while shipping it in the Inertia props is not acceptable, since it is
trivially readable in the page source.

Amounts are never hidden. A Hidden Contribution still contributes to the displayed total and to the
Contributor count; only the identity is withheld.

### Over-funding

Nothing in validation, the schema, or the display path may cap a Contribution at the Wish's remaining
price. A Wish's received total is allowed to exceed `wishes.price`, and progress display must handle
the over-100% case without breaking.

### Payment gateway

`shetabit/payment` v6 is already installed. Its flow, and how it maps onto our models:

- Build an `Invoice` with the amount, then `Payment::purchase($invoice, callback)`. The callback
  receives `$driver` and `$transactionId` — persist these onto the pending `Payment`.
- Set the callback URL to our verification route.
- On return, `Payment::amount(...)->transactionId(...)->verify()` yields a receipt exposing
  `getReferenceId()`. A failed verification throws `InvalidPaymentException` — this is the failure
  path that deletes the Contribution.

The verification route must be safe to hit twice with the same transaction (gateways retry, and users
refresh). A second call must not double-count a Contribution or resurrect a deleted one.

The verification route must confirm the returned transaction belongs to a Payment we actually created,
and must verify against **our stored** amount rather than any amount supplied in the request, so a
tampered callback cannot mark a small payment as a large one.

The package's config needs publishing so the driver and credentials are configurable per environment.

### HTTP surface

Three route groups, all Inertia:

- **Start a contribution** — POST against a Wish. Requires authentication. Validates amount
  (positive, no upper bound tied to the Wish price), the optional message, and the visibility enum.
  Creates the pending pair and redirects to the gateway.
- **Payment callback** — the gateway's return URL. Verifies, then applies the success or failure
  branch, and redirects back to the Wish with a flash result.
- **View a Wish** — returns the Wish with its received total, Contributor count, and the
  visibility-resolved Contribution list for the current viewer.

Use named routes and Wayfinder-generated actions on the frontend, per the project's existing setup.

## Testing Decisions

### What makes a good test here

Tests assert what a **viewer actually receives** and what **ends up in the database**, never how the
code arrives there. A test must not reach into a redaction helper, assert on a model method's return
value, or name an internal class. The visibility rules in particular should be reimplementable —
resource classes, model accessors, query-level filtering, whatever — without a single test changing.

The strongest assertion available for the privacy stories is the negative one: assert the Contributor's
name is **absent from the response payload**, not merely absent from the rendered list.

### Seam

A **single HTTP seam**. All tests are Pest feature tests hitting real routes. `tests/Pest.php` already
applies `RefreshDatabase` to everything in `Feature`, so no per-test setup is needed.

There are no unit tests for this feature. No test instantiates a Contribution, Payment, or visibility
rule directly to assert against it.

### Faking the gateway

`shetabit/multipay` ships a **`Local` driver** (`vendor/shetabit/multipay/src/Drivers/Local`). Tests
point the payment config at it rather than a real bank. No hand-rolled fake or Mockery double of the
package is needed, and no test may make a real network call.

### Coverage

Through the start-a-contribution route:

- A pending Contribution and pending Payment are created and linked
- Guests are redirected to login and nothing is written
- Validation rejects zero, negative, and non-numeric amounts, and invalid visibility values
- An amount exceeding the Wish's remaining price is **accepted**
- The message is genuinely optional

Through the callback route:

- Success flips the Contribution to `paid` and the Payment to `verified`, and records the reference id
- Failure **deletes** the Contribution while the Payment **survives** as `failed` — the ADR-0001
  invariant, and the single most important test in the feature
- Hitting the callback twice does not double-count or resurrect
- A callback for an unknown transaction is rejected

Through the view-a-Wish route, as each of the three viewers (visitor, Owner, Contributor) against
each of the three Visibility levels:

- The name-visibility table above holds in full
- Messages reach the Owner only, including for Hidden Contributions
- Totals and Contributor counts include Hidden Contributions
- Pending and failed Contributions are excluded from the total
- The over-funded case reports a total above the Wish's price

### Prior art

There is none — `tests/` currently contains only `Pest.php` and `TestCase.php`, and the app has no
controllers or feature tests yet. This feature establishes the pattern. Use `WishFactory` as the model
for the new `ContributionFactory` and `PaymentFactory`: `definition()` returning sane defaults with
related models via `User::factory()`, plus named state methods for the meaningful variants. States
worth having include each visibility level, each status, and a paid-with-verified-payment pair, so
tests read as intent rather than setup.

## Out of Scope

- **Refunds and payouts.** No path for returning money to a Contributor, and no mechanism for an Owner
  to withdraw what their Wish received.
- **Guest contributions.** Contributors must be logged-in Users. Anonymity is achieved through the
  Hidden Visibility level, not through unauthenticated giving.
- **Editing or deleting a Contribution after payment.** Including changing the Visibility level or
  Message after the fact.
- **Notifying the Owner** when a Contribution arrives. No mail, no in-app notification.
- **Marking a Wish as funded or closed**, and any behaviour that changes once the price is reached.
  Over-funding is explicitly allowed and a fully funded Wish keeps accepting Contributions.
- **Choosing a gateway per Wish or per Contribution.** A single configured driver for the whole app.
- **Multi-currency.** Amounts stay integers in the same single unit as `wishes.price`.
- **Owner moderation of Messages**, hiding or reporting a Contribution, and any admin dashboard.

## Further Notes

Work in progress on disk from the grilling session, which the implementer should expect to find and
finish rather than recreate:

- `CONTEXT.md` at the repo root — the glossary. Use its vocabulary. Notably **Contribution** (not
  donation or pledge) and **Contributor** (not donor or backer).
- `docs/adr/0001-contributions-are-provisional-payments-are-permanent.md` — binding on the lifecycle.
- Empty generated stubs for the `Contribution` and `Payment` models, their factories, and their
  migrations.
- `app/Enums/` contains a generated `ContributionVisibility` stub, plus a **misplaced nested
  `app/Enums/Enums/` directory** holding `ContributionStatus` and `PaymentStatus` — an artefact of a
  bad `make:enum` argument. Delete the nested directory and regenerate those two enums at the correct
  path.

Run `vendor/bin/pint --dirty --format agent` before finalising, per the project guidelines.
