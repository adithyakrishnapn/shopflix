<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class RazorpayController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository
    ) {}

    /**
     * Redirect customer to Razorpay checkout.
     */
    public function redirect(Request $request)
    {
        $cart = Cart::getCart();

        if (! $cart || ! $cart->billing_address) {
            session()->flash('error', 'Unable to start Razorpay checkout. Please verify your cart details.');

            return redirect()->route('shop.checkout.cart.index');
        }

        $keyId = core()->getConfigData('sales.payment_methods.razorpay.key_id');
        $secret = core()->getConfigData('sales.payment_methods.razorpay.secret');

        if (empty($keyId) || empty($secret)) {
            session()->flash('error', 'Razorpay is not configured. Please contact support.');

            return redirect()->route('shop.checkout.cart.index');
        }

        $amount = (int) round($cart->grand_total * 100);

        if ($amount <= 0) {
            session()->flash('error', 'Invalid order amount for payment.');

            return redirect()->route('shop.checkout.cart.index');
        }

        try {
            $api = new Api($keyId, $secret);

            $razorpayOrder = $api->order->create([
                'receipt'  => 'order_'.$cart->id,
                'amount'   => $amount,
                'currency' => 'INR',
                'notes'    => [
                    'order_id' => $cart->id,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', 'Unable to initialize Razorpay payment at the moment.');

            return redirect()->route('shop.checkout.cart.index');
        }

        // Store cart data temporarily for webhook processing (in case user navigates away)
        \DB::table('razorpay_orders')->insert([
            'razorpay_order_id' => $razorpayOrder['id'],
            'cart_data'         => json_encode((new OrderResource($cart))->jsonSerialize()),
            'user_id'           => auth()->id(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $request->session()->put('razorpay_order_id', $razorpayOrder['id']);

        $data = [
            'key'          => $keyId,
            'amount'       => $amount,
            'currency'     => 'INR',
            'name'         => $cart->billing_address->name,
            'description'  => 'Order #'.$cart->id,
            'order_id'     => $razorpayOrder['id'],
            'callback_url' => route('razorpay.callback'),
            'prefill'      => [
                'name'    => $cart->billing_address->name,
                'email'   => $cart->billing_address->email,
                'contact' => $cart->billing_address->phone,
            ],
            'theme'        => [
                'color' => '#0F172A',
            ],
        ];

        return view('razorpay.redirect', [
            'data' => $data,
        ]);
    }

    /**
     * Verify Razorpay signature and place order.
     */
    public function verify(Request $request)
    {
        $paymentId = $request->input('razorpay_payment_id');
        $signature = $request->input('razorpay_signature');
        $orderId = $request->session()->get('razorpay_order_id') ?: $request->input('razorpay_order_id');

        if (empty($paymentId) || empty($signature) || empty($orderId)) {
            session()->flash('error', 'Razorpay payment was cancelled or incomplete.');

            return redirect()->route('shop.checkout.cart.index');
        }

        $keyId = core()->getConfigData('sales.payment_methods.razorpay.key_id');
        $secret = core()->getConfigData('sales.payment_methods.razorpay.secret');

        try {
            $api = new Api($keyId, $secret);

            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature'  => $signature,
            ]);
        } catch (SignatureVerificationError $e) {
            report($e);

            session()->flash('error', 'Razorpay signature verification failed.');

            return redirect()->route('shop.checkout.cart.index');
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', 'Unable to verify Razorpay payment.');

            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        if (! $cart) {
            session()->flash('error', 'Cart not available to finalize the order.');

            return redirect()->route('shop.checkout.cart.index');
        }

        $order = $this->orderRepository->create((new OrderResource($cart))->jsonSerialize());

        $this->orderRepository->update(['status' => 'processing'], $order->id);

        if ($order->canInvoice()) {
            $this->invoiceRepository->create($this->prepareInvoiceData($order));
        }

        Cart::deActivateCart();

        $request->session()->forget('razorpay_order_id');
        session()->flash('order_id', $order->id);

        return redirect()->route('shop.checkout.onepage.success');
    }

    /**
     * Verify Razorpay signature and place order (AJAX flow).
     */
    public function verifyJson(Request $request): JsonResponse
    {
        $paymentId = $request->input('razorpay_payment_id');
        $signature = $request->input('razorpay_signature');
        $orderId = $request->session()->get('razorpay_order_id') ?: $request->input('razorpay_order_id');

        if (empty($paymentId) || empty($signature) || empty($orderId)) {
            return response()->json([
                'message' => 'Razorpay payment was cancelled or incomplete.',
            ], 422);
        }

        $keyId = core()->getConfigData('sales.payment_methods.razorpay.key_id');
        $secret = core()->getConfigData('sales.payment_methods.razorpay.secret');

        try {
            $api = new Api($keyId, $secret);

            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature'  => $signature,
            ]);
        } catch (SignatureVerificationError $e) {
            report($e);

            return response()->json([
                'message' => 'Razorpay signature verification failed.',
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to verify Razorpay payment.',
            ], 500);
        }

        $cart = Cart::getCart();

        if (! $cart) {
            return response()->json([
                'message' => 'Cart not available to finalize the order.',
            ], 422);
        }

        $order = $this->orderRepository->create((new OrderResource($cart))->jsonSerialize());

        $this->orderRepository->update(['status' => 'processing'], $order->id);

        if ($order->canInvoice()) {
            $this->invoiceRepository->create($this->prepareInvoiceData($order));
        }

        Cart::deActivateCart();

        $request->session()->forget('razorpay_order_id');
        session()->flash('order_id', $order->id);

        return response()->json([
            'redirect_url' => route('shop.checkout.onepage.success'),
        ]);
    }

    /**
     * Webhook handler for Razorpay payment events (server-to-server, independent of user browser).
     * This ensures DB is updated even if user navigates away during checkout.
     */
    public function webhook(Request $request): JsonResponse
    {
        $secret = core()->getConfigData('sales.payment_methods.razorpay.secret');
        $webhookSecret = core()->getConfigData('sales.payment_methods.razorpay.webhook_secret');

        if (empty($webhookSecret)) {
            \Log::warning('Razorpay webhook secret not configured');
            return response()->json(['success' => false], 400);
        }

        // Verify webhook signature
        $signature = $request->header('X-Razorpay-Signature');
        $body = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $body, $webhookSecret);

        if (!hash_equals($signature, $expectedSignature)) {
            \Log::warning('Razorpay webhook signature verification failed');
            return response()->json(['success' => false], 401);
        }

        $event = $request->input('event');
        $payload = $request->input('payload');

        \Log::info('Razorpay webhook received', ['event' => $event]);

        try {
            if ($event === 'payment.authorized') {
                $this->handlePaymentAuthorized($payload);
            } elseif ($event === 'payment.failed') {
                $this->handlePaymentFailed($payload);
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            \Log::error('Razorpay webhook error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Handle successful payment.
     */
    protected function handlePaymentAuthorized(array $payload): void
    {
        $paymentData = $payload['payment'] ?? [];
        $orderId = $paymentData['notes']['order_id'] ?? null;
        $paymentId = $paymentData['id'] ?? null;

        if (!$orderId || !$paymentId) {
            \Log::warning('Razorpay webhook: Missing order_id or payment_id in notes');
            return;
        }

        // Check if order already exists (idempotency)
        $existingOrder = $this->orderRepository->where('razorpay_payment_id', $paymentId)->first();
        if ($existingOrder) {
            \Log::info('Razorpay webhook: Order already created for payment ' . $paymentId);
            return;
        }

        // Get the stored Razorpay order info from database
        $razorpayOrderData = \DB::table('razorpay_orders')
            ->where('razorpay_order_id', $paymentData['order_id'] ?? null)
            ->first();

        if (!$razorpayOrderData) {
            \Log::warning('Razorpay webhook: No cart data found for order, creating manual order entry');
            return;
        }

        // Create order with stored cart data
        $cartData = json_decode($razorpayOrderData->cart_data, true);
        $order = $this->orderRepository->create($cartData);

        // Update with payment info
        $this->orderRepository->update([
            'status' => 'processing',
            'razorpay_payment_id' => $paymentId,
        ], $order->id);

        // Create invoice if possible
        if ($order->canInvoice()) {
            $this->invoiceRepository->create($this->prepareInvoiceData($order));
        }

        // Clean up temporary storage
        \DB::table('razorpay_orders')->where('razorpay_order_id', $paymentData['order_id'])->delete();

        \Log::info('Razorpay webhook: Order created via webhook', ['order_id' => $order->id, 'payment_id' => $paymentId]);
    }

    /**
     * Handle failed payment.
     */
    protected function handlePaymentFailed(array $payload): void
    {
        $paymentData = $payload['payment'] ?? [];
        $paymentId = $paymentData['id'] ?? null;
        $error = $paymentData['error_source'] ?? 'unknown';

        // Clean up temporary storage
        $razorpayOrderData = \DB::table('razorpay_orders')
            ->where('razorpay_order_id', $paymentData['order_id'] ?? null)
            ->first();

        if ($razorpayOrderData) {
            \DB::table('razorpay_orders')
                ->where('razorpay_order_id', $paymentData['order_id'])
                ->delete();
        }

        \Log::warning('Razorpay webhook: Payment failed', ['payment_id' => $paymentId, 'error' => $error]);
    }

    /**
     * Prepare invoice payload.
     */
    protected function prepareInvoiceData($order): array
    {
        $invoiceData = ['order_id' => $order->id];

        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        return $invoiceData;
    }
}
