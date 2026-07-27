<?php

namespace App\Http\Controllers;

use App\Enums\ContributionStatus;
use App\Enums\ContributionVisibility;
use App\Enums\PaymentStatus;
use App\Http\Requests\StoreContributionRequest;
use App\Models\Contribution;
use App\Models\Payment;
use App\Models\Wish;
use Illuminate\Support\Facades\DB;
use Shetabit\Multipay\Abstracts\Driver;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment as Gateway;

class ContributionController extends Controller
{
    /**
     * Start a contribution towards a wish and send the contributor to the gateway.
     */
    public function store(StoreContributionRequest $request, Wish $wish): string
    {
        $amount = (int) $request->validated('amount');

        $contribution = $this->createPendingContribution($request, $wish, $amount);

        $invoice = (new Invoice)->amount($amount)->via(config('payment.default'));

        return Gateway::callbackUrl(route('payments.callback'))
            ->purchase(
                $invoice,
                fn (Driver $driver, string|int $transactionId) => $contribution->payment->update([
                    'transaction_id' => (string) $transactionId,
                    'driver' => $driver->getInvoice()->getDriver(),
                ]),
            )
            ->pay()
            ->render();
    }

    /**
     * Record the pending contribution and the pending payment that will carry it.
     *
     * The pair is written in one transaction so a crash can never leave a
     * contribution without the payment it depends on.
     */
    protected function createPendingContribution(StoreContributionRequest $request, Wish $wish, int $amount): Contribution
    {
        return DB::transaction(function () use ($request, $wish, $amount): Contribution {
            $payment = Payment::create([
                'amount' => $amount,
                'driver' => config('payment.default'),
                'status' => PaymentStatus::Pending,
            ]);

            return $payment->contribution()->create([
                'wish_id' => $wish->id,
                'contributor_id' => $request->user()->id,
                'amount' => $amount,
                'visibility' => ContributionVisibility::Public,
                'status' => ContributionStatus::Pending,
            ]);
        });
    }
}
