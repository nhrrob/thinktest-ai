<?php

namespace App\Http\Controllers;

use App\Models\CreditPackage;
use App\Services\CreditService;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    private StripePaymentService $stripeService;
    private CreditService $creditService;

    public function __construct(StripePaymentService $stripeService, CreditService $creditService)
    {
        $this->stripeService = $stripeService;
        $this->creditService = $creditService;
        $this->middleware('auth');
    }

    /**
     * Show credit packages for purchase.
     */
    public function index()
    {
        $packages = $this->creditService->getAvailablePackages();
        $userCreditStatus = $this->creditService->getUserCreditStatus(Auth::id());
        $recommendedPackage = $this->creditService->getRecommendedPackage(Auth::id());

        return inertia('Credits/Index', [
            'packages' => $packages,
            'creditStatus' => $userCreditStatus,
            'recommendedPackage' => $recommendedPackage,
            'stripePublishableKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Create a payment intent for credit purchase.
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:credit_packages,id',
        ]);

        $package = CreditPackage::findOrFail($request->package_id);
        
        if (!$package->is_active) {
            throw ValidationException::withMessages([
                'package_id' => 'This credit package is no longer available.',
            ]);
        }

        $user = Auth::user();
        $result = $this->stripeService->createPaymentIntent($user, $package);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'client_secret' => $result['client_secret'],
            'payment_intent_id' => $result['payment_intent_id'],
            'package' => $result['package'],
        ]);
    }

    /**
     * Handle Stripe webhooks.
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (!$signature) {
            Log::warning('Stripe webhook received without signature');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        $result = $this->stripeService->handleWebhook($payload, $signature);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get payment intent status.
     */
    public function paymentStatus(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $result = $this->stripeService->getPaymentIntentStatus($request->payment_intent_id);

        return response()->json($result);
    }

    /**
     * Show payment success page.
     */
    public function success(Request $request)
    {
        $paymentIntentId = $request->query('payment_intent');
        
        if (!$paymentIntentId) {
            return redirect()->route('credits.index')
                ->with('error', 'Invalid payment confirmation.');
        }

        $status = $this->stripeService->getPaymentIntentStatus($paymentIntentId);
        
        if (!$status['success'] || $status['status'] !== 'succeeded') {
            return redirect()->route('credits.index')
                ->with('error', 'Payment was not successful.');
        }

        $userCreditStatus = $this->creditService->getUserCreditStatus(Auth::id());

        return inertia('Credits/Success', [
            'paymentIntentId' => $paymentIntentId,
            'amount' => $status['amount'],
            'creditStatus' => $userCreditStatus,
        ]);
    }

    /**
     * Show payment canceled page.
     */
    public function canceled()
    {
        return inertia('Credits/Canceled');
    }

    /**
     * Show transaction history.
     */
    public function transactions()
    {
        $user = Auth::user();
        $transactions = $user->creditTransactions()
            ->with('paymentIntent.creditPackage')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $creditStatus = $this->creditService->getUserCreditStatus($user->id);

        return inertia('Credits/Transactions', [
            'transactions' => $transactions,
            'creditStatus' => $creditStatus,
        ]);
    }

    /**
     * Download transaction receipt (for completed purchases).
     */
    public function receipt(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:credit_transactions,id',
        ]);

        $transaction = Auth::user()->creditTransactions()
            ->with('paymentIntent.creditPackage')
            ->findOrFail($request->transaction_id);

        if ($transaction->type !== 'purchase' || !$transaction->payment_intent_id) {
            return response()->json(['error' => 'Receipt not available for this transaction'], 400);
        }

        // Generate PDF receipt or redirect to Stripe receipt
        // For now, we'll return transaction details as JSON
        return response()->json([
            'transaction' => $transaction,
            'package' => $transaction->paymentIntent?->creditPackage,
            'receipt_url' => "https://dashboard.stripe.com/receipts/{$transaction->payment_intent_id}",
        ]);
    }

    /**
     * Admin: Refund a payment.
     */
    public function refund(Request $request)
    {
        // This should be protected by admin middleware
        if (!Auth::user()->can('manage-payments')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'payment_intent_id' => 'required|string',
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->stripeService->refundPayment(
            $request->payment_intent_id,
            $request->amount
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 400);
        }

        // Log the refund for audit purposes
        Log::info('Payment refunded by admin', [
            'admin_user_id' => Auth::id(),
            'payment_intent_id' => $request->payment_intent_id,
            'refund_amount' => $result['amount'],
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'refund_id' => $result['refund_id'],
            'amount' => $result['amount'],
        ]);
    }
}
