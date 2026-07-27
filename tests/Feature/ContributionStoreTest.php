<?php

use App\Enums\ContributionStatus;
use App\Enums\ContributionVisibility;
use App\Enums\PaymentStatus;
use App\Models\Contribution;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wish;

it('creates a pending contribution linked to a pending payment', function () {
    $contributor = User::factory()->create();
    $wish = Wish::factory()->create();

    $this->actingAs($contributor)
        ->post(route('wishes.contributions.store', $wish), ['amount' => 50_000])
        ->assertSuccessful();

    $contribution = Contribution::sole();
    $payment = Payment::sole();

    expect($contribution->wish_id)->toBe($wish->id)
        ->and($contribution->contributor_id)->toBe($contributor->id)
        ->and($contribution->amount)->toBe(50_000)
        ->and($contribution->message)->toBeNull()
        ->and($contribution->visibility)->toBe(ContributionVisibility::Public)
        ->and($contribution->status)->toBe(ContributionStatus::Pending)
        ->and($contribution->payment_id)->toBe($payment->id)
        ->and($payment->amount)->toBe(50_000)
        ->and($payment->status)->toBe(PaymentStatus::Pending);
});

it('sends the contributor to the gateway and records the attempt against the payment', function () {
    $wish = Wish::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->post(route('wishes.contributions.store', $wish), ['amount' => 25_000]);

    $payment = Payment::sole();

    expect($payment->transaction_id)->not->toBeEmpty()
        ->and($payment->driver)->toBe('local');

    $response->assertSee(
        route('payments.callback').'?transactionId='.$payment->transaction_id,
        escape: false,
    );
});

it('accepts an amount larger than the price the wish still needs', function () {
    $wish = Wish::factory()->create(['price' => 100_000]);
    Contribution::factory()->forWish($wish)->paid()->create(['amount' => 90_000]);

    $this->actingAs(User::factory()->create())
        ->post(route('wishes.contributions.store', $wish), ['amount' => 500_000])
        ->assertSuccessful();

    expect(Contribution::latest('id')->first()->amount)->toBe(500_000);
});

it('rejects an amount that is not a positive number', function (mixed $amount) {
    $wish = Wish::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('wishes.contributions.store', $wish), ['amount' => $amount])
        ->assertInvalid('amount');

    expect(Contribution::count())->toBe(0)
        ->and(Payment::count())->toBe(0);
})->with([
    'zero' => 0,
    'negative' => -5_000,
    'not a number' => 'ten thousand',
    'missing' => null,
]);

it('writes no payment when the contribution cannot be created', function () {
    $wish = Wish::factory()->create();

    Contribution::creating(fn () => throw new RuntimeException('the database went away'));

    $this->actingAs(User::factory()->create())
        ->post(route('wishes.contributions.store', $wish), ['amount' => 50_000])
        ->assertServerError();

    expect(Contribution::count())->toBe(0)
        ->and(Payment::count())->toBe(0);
});

it('rejects a guest without writing anything', function () {
    $wish = Wish::factory()->create();

    $this->post(route('wishes.contributions.store', $wish), ['amount' => 50_000])
        ->assertForbidden();

    expect(Contribution::count())->toBe(0)
        ->and(Payment::count())->toBe(0);
});
