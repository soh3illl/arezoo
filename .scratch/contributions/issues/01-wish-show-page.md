# 01 — Show a Wish

**What to build:** A visitor can open a Wish's page and receive everything needed to describe it —
its title, description, thumbnail, price, purchase link, and Owner — alongside a received total of
zero and a Contributor count of zero. Those two totals are placeholders for now; ticket 02 makes them
real. This is the surface every later ticket hangs off: the application currently has no Wish route
at all.

**Backend only.** The Inertia page component is created as an empty stub — no markup, no styling.
Everything is asserted against the Inertia props, not rendered output. Frontend work is a later
effort.

**Blocked by:** None — can start immediately.

**Status:** ready-for-agent

- [ ] A named GET route resolves a single Wish and returns an Inertia response
- [ ] The response props carry the Wish's title, description, thumbnail, price, purchase link, and Owner
- [ ] The props include a received total and a Contributor count, both zero at this stage
- [ ] A Wish that does not exist returns 404
- [ ] An empty Vue page component exists at the conventional pages path so the Inertia response resolves
- [ ] A Pest feature test asserts the props via Inertia response assertions, using `WishFactory`
- [ ] `vendor/bin/pint --dirty --format agent` runs clean

## Notes

Read `CONTEXT.md` before starting and use its vocabulary — **Owner** is the User a Wish belongs to.

`tests/Pest.php` already applies `RefreshDatabase` to everything under `Feature`, so no per-test
database setup is needed. This is the repo's first controller and first feature test; the patterns it
sets will be copied by tickets 02–05.

Register the route by name and let Wayfinder generate the corresponding action, per the project's
existing setup.
