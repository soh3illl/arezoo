<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Payment\Facade\Payment as Gateway;

class PaymentCallbackController extends Controller
{
    /**
     * Settle the attempt the gateway has sent the contributor back from.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $transactionId = $request->query('transactionId');

        abort_if(! is_string($transactionId) || $transactionId === '', 404);

        $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();

        $contribution = $payment->contribution()->firstOrFail();
        $contribution->setRelation('payment', $payment);

        try {
            $receipt = Gateway::via($payment->driver)
                ->amount($payment->amount)
                ->transactionId($payment->transaction_id)
                ->verify();

            $contribution->markPaid($receipt->getReferenceId());

            $result = 'paid';
        } catch (InvalidPaymentException) {
            $contribution->abandon();

            $result = 'failed';
        }

        return to_route('wishes.show', $contribution->wish_id)->with('contribution', $result);
    }
}
