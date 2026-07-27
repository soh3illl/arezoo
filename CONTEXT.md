# Arezoo

A wishlist platform where people publish items they want, and others chip in money towards them.

## Language

### Wishes

**Wish**:
An item a User wants, published with a price and a purchase link.
_Avoid_: Item, gift, product

**Owner**:
The User a Wish belongs to.
_Avoid_: Creator, recipient, beneficiary

### Contributions

**Contribution**:
Money a Contributor has successfully put towards a Wish. Contributions are not capped by the Wish's price — the total may exceed it.
_Avoid_: Donation, pledge, gift, chip-in

**Contributor**:
The User who makes a Contribution. Contributors must be registered — there is no anonymous-guest path, only hidden identity.
_Avoid_: Donor, backer, supporter, giver

**Message**:
An optional note a Contributor attaches to their Contribution. Only ever readable by the Wish's Owner, at every Visibility level.
_Avoid_: Note, comment, dedication

**Visibility**:
How much of a Contributor's identity is revealed on a Contribution. Governs the name and profile only — never the Message.
_Avoid_: Privacy, anonymity level

**Public**:
Visibility level where anyone can see the Contributor's name and profile.

**Owner-only**:
Visibility level where only the Wish's Owner can see the Contributor's name and profile.
_Avoid_: Private, semi-private

**Hidden**:
Visibility level where nobody, including the Wish's Owner, can see the Contributor's name or profile. The Owner still reads the Message, unattributed.
_Avoid_: Anonymous, incognito

### Payments

**Payment**:
A single attempt to move money through a payment gateway. Every attempt is recorded and kept, whether it succeeds or not.
_Avoid_: Transaction, charge, order

**Verified**:
A Payment the gateway has confirmed as settled. Only a verified Payment produces a Contribution.
_Avoid_: Completed, successful, confirmed
