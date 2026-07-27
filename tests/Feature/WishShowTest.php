<?php

use App\Models\Contribution;
use App\Models\User;
use App\Models\Wish;
use Inertia\Testing\AssertableInertia as Assert;

it('shows a wish to a visitor', function () {
    $wish = Wish::factory()->create();

    $this->get(route('wishes.show', $wish))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Wishes/Show')
            ->has('wish', fn (Assert $page) => $page
                ->where('id', $wish->id)
                ->where('title', $wish->title)
                ->where('description', $wish->description)
                ->where('thumbnail', $wish->thumbnail)
                ->where('purchase_link', $wish->purchase_link)
                ->where('price', $wish->price)
                ->etc()
            )
        );
});

it('shows the owner of the wish', function () {
    $owner = User::factory()->create();
    $wish = Wish::factory()->forOwner($owner)->create();

    $this->get(route('wishes.show', $wish))
        ->assertInertia(fn (Assert $page) => $page
            ->has('wish.owner', fn (Assert $page) => $page
                ->where('id', $owner->id)
                ->where('name', $owner->name)
                ->etc()
            )
        );
});

it('returns not found for a wish that does not exist', function () {
    $this->get(route('wishes.show', 999))->assertNotFound();
});

it('reports no contributions yet', function () {
    $wish = Wish::factory()->create();

    $this->get(route('wishes.show', $wish))
        ->assertInertia(fn (Assert $page) => $page
            ->where('wish.received_total', 0)
            ->where('wish.contributor_count', 0)
            ->etc()
        );
});

it('counts only paid contributions towards the received total', function () {
    $wish = Wish::factory()->create();

    Contribution::factory()->forWish($wish)->paid()->create(['amount' => 20_000]);
    Contribution::factory()->forWish($wish)->paid()->create(['amount' => 5_000]);
    Contribution::factory()->forWish($wish)->pending()->create(['amount' => 100_000]);
    Contribution::factory()->paid()->create(['amount' => 70_000]);

    $this->get(route('wishes.show', $wish))
        ->assertInertia(fn (Assert $page) => $page
            ->where('wish.received_total', 25_000)
            ->where('wish.contributor_count', 2)
            ->etc()
        );
});

it('counts a contributor once however often they give', function () {
    $wish = Wish::factory()->create();
    $contributor = User::factory()->create();

    Contribution::factory()->forWish($wish)->forContributor($contributor)->paid()->create(['amount' => 10_000]);
    Contribution::factory()->forWish($wish)->forContributor($contributor)->paid()->create(['amount' => 15_000]);

    $this->get(route('wishes.show', $wish))
        ->assertInertia(fn (Assert $page) => $page
            ->where('wish.received_total', 25_000)
            ->where('wish.contributor_count', 1)
            ->etc()
        );
});

it('reports a received total that exceeds the price of the wish', function () {
    $wish = Wish::factory()->create(['price' => 100_000]);

    Contribution::factory()->forWish($wish)->paid()->create(['amount' => 150_000]);

    $this->get(route('wishes.show', $wish))
        ->assertInertia(fn (Assert $page) => $page
            ->where('wish.price', 100_000)
            ->where('wish.received_total', 150_000)
            ->etc()
        );
});

it('does not expose the private details of the owner', function () {
    $wish = Wish::factory()->create();

    $this->get(route('wishes.show', $wish))
        ->assertInertia(fn (Assert $page) => $page
            ->has('wish.owner', 2)
            ->has('wish.owner.id')
            ->has('wish.owner.name')
        );
});
