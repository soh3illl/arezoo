# 04 — Messages readable only by the Owner

**What to build:** A Contributor can attach an optional Message to their Contribution — something
personal for the Owner. Only the Wish's Owner ever reads it. It is not a public comment, and it is
not visible to other visitors at any time.

**The Message rule is independent of Visibility.** A Hidden Contributor can leave a Message, and the
Owner reads it without learning who sent it. This is the single most easily-confused part of the
feature: identity and Message are gated by two separate rules that happen to share a screen.

**Backend only.** Assert on Inertia props.

**Blocked by:** 02 — needs Contributions to exist before they can carry a Message.

**Status:** ready-for-agent

- [ ] The contribute request accepts an optional message and validates its length
- [ ] Omitting the message is valid and stores no message
- [ ] The Owner receives the Messages left on their Wish
- [ ] A visitor who is not the Owner never receives any Message, at any Visibility level
- [ ] The Owner receives a Hidden Contributor's Message, with no name or profile attached to it
- [ ] A Contributor receives their own Message back
- [ ] Feature tests cover the Message against all three Visibility levels and all three viewers
- [ ] `vendor/bin/pint --dirty --format agent` runs clean

## The rule, stated plainly

Only the Owner ever sees a Message. At every Visibility level. No exceptions for Public
Contributions, and no exception for Hidden ones.

Ticket 03's matrix governs the **name and profile**. This ticket's rule governs the **Message**. They
are orthogonal — a Hidden Contribution shows no name to the Owner and still shows its Message to the
Owner.

Can be worked in parallel with ticket 03; neither blocks the other. If 03 has already landed, the
Message rule slots alongside its redaction logic rather than inside it — resist the temptation to fold
the Message into the visibility matrix, because it does not fit there.

## Notes

Use `CONTEXT.md` vocabulary: a **Message** is an optional note a Contributor attaches, readable only
by the Owner.

Assert external behaviour only — what each viewer receives in the payload, never how the rule is
implemented.
