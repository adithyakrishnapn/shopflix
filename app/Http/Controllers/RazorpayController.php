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
            ]);
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', 'Unable to initialize Razorpay payment at the moment.');

            return redirect()->route('shop.checkout.cart.index');
        }

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
