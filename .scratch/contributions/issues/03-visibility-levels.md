# 03 — Visibility levels for Contributors

**What to build:** A Contributor chooses how visible they want to be when contributing, and the Wish
page reveals or withholds their identity accordingly for whoever is looking. Some Contributors want
public credit, some want the Owner alone to know, and some want to give with nobody — Owner included
— knowing who they are.

Visibility governs the Contributor's **name and profile only**. It never governs the Message (ticket
04), and it never hides amounts.

**Backend only.** Assert on what is present in and absent from the Inertia props.

**Blocked by:** 02 — needs Contributions to exist before they can be redacted.

**Status:** ready-for-agent

- [ ] The contribute request accepts a visibility choice and rejects values outside the three levels
- [ ] The full viewer × level matrix below holds
- [ ] Redaction happens server-side: when a viewer may not see an identity, the Contributor's name, profile, and user id are **absent from the response payload** entirely
- [ ] Hidden and Owner-only Contributions still count towards the received total and the Contributor count
- [ ] A Contributor always sees their own Contribution, at every level, including Hidden
- [ ] Feature tests exercise all three viewers against all three levels
- [ ] `vendor/bin/pint --dirty --format agent` runs clean

## The matrix

| Contribution visibility | Visitor     | Owner       | The Contributor |
| ----------------------- | ----------- | ----------- | --------------- |
| `public`                | name shown  | name shown  | name shown      |
| `owner`                 | name hidden | name shown  | name shown      |
| `hidden`                | name hidden | name hidden | name shown      |

`hidden` genuinely means hidden from the Owner too. That is the promise made to the Contributor, and
it is the row most likely to be implemented as "hidden from everyone except the Owner" by mistake.

A Contributor seeing their own Contribution at Hidden is how they confirm their money landed — without
it, an anonymous giver gets no feedback at all.

## Why server-side

Omitting a name from the UI while shipping it in the Inertia props is **not** acceptable. It satisfies
a naive reading of the requirement and still leaks every hidden identity to anyone who opens the page
source. Since this ticket is backend-only there is no UI to hide anything in, which makes the point
moot in practice — but the tests must assert absence from the payload, so the guarantee survives once
the frontend is built.

Amounts are never hidden. A Hidden Contribution contributes to the displayed total and to the
Contributor count exactly like a Public one; only the identity is withheld.

## Notes

Use `CONTEXT.md` vocabulary: **Public**, **Owner-only**, **Hidden**.

Tests must assert external behaviour only — what a given viewer receives. Do not reach into a
redaction helper or assert on a model method's return value. The rules should be reimplementable as
resource classes, model accessors, or query-level filtering without a single test changing.
