<?php

use App\Enums\ContributionStatus;
use App\Enums\PaymentStatus;
use App\Models\Contribution;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wish;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Start a real contribution so the callback runs against a genuine gateway attempt.
 *
 * @return array{0: Wish, 1: Payment}
 */
function startContributionAttempt(int $amount = 30_000): array
{
    $wish = Wish::factory()->create();

    test()->actingAs(User::factory()->create())
        ->post(route('wishes.contributions.store', $wish), ['amount' => $amount])
        ->assertSuccessful();

    return [$wish, Payment::sole()];
}

it('marks the contribution paid and the payment verified when the gateway confirms', function () {
    [$wish, $payment] = startContributionAttempt();

    $this->get(route('payments.callback', ['transactionId' => $payment->transaction_id]))
        ->assertRedirect(route('wishes.show', $wish));

    expect(Contribution::sole()->status)->toBe(ContributionStatus::Paid)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Verified)
        ->and($payment->reference_id)->not->toBeEmpty();
});

it('deletes the contribution but keeps the failed payment when the gateway rejects', function () {
    [$wish, $payment] = startContributionAttempt();

    $this->get(route('payments.callback', [
        'transactionId' => $payment->transaction_id,
        'cancel' => 'true',
    ]))->assertRedirect(route('wishes.show', $wish));

    expect(Contribution::count())->toBe(0)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Failed)
        ->and($payment->reference_id)->toBeNull()
        ->and($payment->amount)->toBe(30_000);
});

it('counts the contribution towards the wish once the gateway confirms', function () {
    [$wish, $payment] = startContributionAttempt(40_000);

    $this->get(route('payments.callback', ['transactionId' => $payment->transaction_id]));

    $this->get(route('wishes.show', $wish))
        ->assertInertia(fn (Assert $page) => $page
            ->where('wish.received_total', 40_000)
            ->where('wish.contributor_count', 1)
            ->etc()
        );
});

it('leaves the wish untouched after an abandoned attempt', function () {
    [$wish, $payment] = startContributionAttempt(40_000);

    $this->get(route('payments.callback', [
        'transactionId' => $payment->transaction_id,
        'cancel' => 'true',
    ]));

    $this->get(route('wishes.show', $wish))
        ->assertInertia(fn (Assert $page) => $page
            ->where('wish.received_total', 0)
            ->where('wish.contributor_count', 0)
            ->etc()
        );
});

it('ignores a callback that names no transaction at all', function () {
    [, $payment] = startContributionAttempt();
    $payment->update(['transaction_id' => null]);

    $this->get(route('payments.callback'))->assertNotFound();

    expect(Contribution::sole()->status)->toBe(ContributionStatus::Pending)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Pending);
});

it('leaves the payment pending when the contribution cannot be marked paid', function () {
    [, $payment] = startContributionAttempt();

    Contribution::updating(fn () => throw new RuntimeException('the database went away'));

    $this->get(route('payments.callback', ['transactionId' => $payment->transaction_id]))
        ->assertServerError();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending)
        ->and($payment->reference_id)->toBeNull()
        ->and(Contribution::sole()->status)->toBe(ContributionStatus::Pending);
});

it('keeps the contribution when the payment cannot be marked failed', function () {
    [, $payment] = startContributionAttempt();

    Payment::updating(fn () => throw new RuntimeException('the database went away'));

    $this->get(route('payments.callback', [
        'transactionId' => $payment->transaction_id,
        'cancel' => 'true',
    ]))->assertServerError();

    expect(Contribution::sole()->status)->toBe(ContributionStatus::Pending)
        ->and($payment->refresh()->status)->toBe(PaymentStatus::Pending);
});
