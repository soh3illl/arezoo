<?php

namespace App\Http\Controllers;

use App\Models\Wish;
use Inertia\Inertia;
use Inertia\Response;

class WishController extends Controller
{
    /**
     * Show a single wish.
     */
    public function show(Wish $wish): Response
    {
        $wish->load('owner');

        return Inertia::render('Wishes/Show', [
            'wish' => [
                'id' => $wish->id,
                'title' => $wish->title,
                'description' => $wish->description,
                'thumbnail' => $wish->thumbnail,
                'purchase_link' => $wish->purchase_link,
                'price' => $wish->price,
                'received_total' => $wish->receivedTotal(),
                'contributor_count' => $wish->contributorCount(),
                'owner' => [
                    'id' => $wish->owner->id,
                    'name' => $wish->owner->name,
                ],
            ],
        ]);
    }
}
