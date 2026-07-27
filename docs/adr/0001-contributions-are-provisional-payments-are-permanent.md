# Contributions are provisional, Payments are permanent

A Contribution row is created *before* its Payment is verified, so we have something to hold the
Contributor's amount, message, and visibility choice across the gateway redirect. If the gateway
attempt fails or is abandoned, the Contribution row is **deleted** while its Payment row is **kept**
and marked failed. This is deliberate: a Contribution means "money actually landed on this Wish", so
a failed attempt must not linger and inflate a Wish's total, while every gateway attempt stays
auditable in `payments` regardless.

The consequence is an ownership direction that looks backwards at first glance: `contributions`
holds the `payment_id` foreign key, not the other way round. A Payment must be able to outlive the
Contribution it was created alongside, so it cannot depend on it.

## Considered Options

We rejected creating the Contribution only on successful verification. That would have left the
donation's amount, message, and visibility with nowhere to live during the redirect — they would
have to survive in session state or in gateway invoice metadata, both of which are lossy and put
user-authored content outside our database.
