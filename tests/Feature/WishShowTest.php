<?php

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

it('does not expose the private details of the owner', function () {
    $wish = Wish::factory()->create();

    $this->get(route('wishes.show', $wish))
        ->assertInertia(fn (Assert $page) => $page
            ->has('wish.owner', 2)
            ->has('wish.owner.id')
            ->has('wish.owner.name')
        );
});
