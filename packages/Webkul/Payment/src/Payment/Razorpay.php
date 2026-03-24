<?php

namespace Webkul\Payment\Payment;

use Illuminate\Support\Facades\Storage;

class Razorpay extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'razorpay';

    /**
     * Get redirect url.
     */
    public function getRedirectUrl()
    {
        return route('razorpay.process');
    }

    /**
     * Is available.
     */
    public function isAvailable()
    {
        if (! $this->cart) {
            $this->setCart();
        }

        return $this->getConfigData('active')
            && $this->cart?->haveStockableItems()
            && ! empty($this->getConfigData('key_id'))
            && ! empty($this->getConfigData('secret'));
    }

    /**
     * Get payment method image.
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : '';
    }
}
