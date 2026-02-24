<?php

namespace Webkul\Shipping\Carriers;

use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Checkout\Facades\Cart;

class WeightBased extends AbstractShipping
{
    /**
     * Shipping method carrier code.
     *
     * @var string
     */
    protected $code = 'weightbased';

    /**
     * Shipping method code.
     *
     * @var string
     */
    protected $method = 'weightbased_weightbased';

    /**
     * Calculate rate for free shipping.
     *
     * @return CartShippingRate|false
     */
    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return $this->getRate();
    }

    /**
     * Get rate.
     */
    public function getRate(): CartShippingRate
    {
        $cart = Cart::getCart();
        $state = $cart->shipping_address['state'] ?? null;
        $country = $cart->shipping_address['country'] ?? null;

        $totalWeight = $cart->items->sum(function ($item) {
            return $item->weight * $item->quantity;
        });

        $subtotal = $cart->base_sub_total; // subtotal for free shipping check

        $shippingChargeInsideTamilNaduPerKG = $this->getConfigData('per_kg_inside');
        $shippingChargeOutSideTamilNaduPerKG = $this->getConfigData('per_kg_outside');
        $shippingChargePerticularStatePerKG = $this->getConfigData('per_kg_perticular_state');
        $otherCountryPerKG = $this->getConfigData('per_kg_other_country');
        $minimumShippingCharge = $this->getConfigData('min_charge');
        $maximumShippingCharge = $this->getConfigData('max_charge');
        $isFreeShipping = $this->getConfigData('enable_free_shipping');
        $freeShippingAbove = $this->getConfigData('free_shipping_above');

        // Start with 1kg minimum weight and round up
        $weight = ceil(max($totalWeight, 1));

        // Calculate base shipping charge
        // if ($state === 'TN') {
        //     $shippingCharge = $weight * $shippingChargeInsideTamilNaduPerKG;
        // } else {
        //     $shippingCharge = $weight * $shippingChargeOutSideTamilNaduPerKG;
        // }
        
     
        if ($state == 'TN' || $state == 'PY') {
            $shippingCharge = $weight * $shippingChargeInsideTamilNaduPerKG;
        }
        else if ($state == 'KA' || $state == 'AP' || $state == 'TG' || $state == 'KL') {
            $shippingCharge = $weight * $shippingChargePerticularStatePerKG;
        } else {
            $shippingCharge = $weight * $shippingChargeOutSideTamilNaduPerKG;
        }

        if($country != 'IN'){
            $shippingCharge = $weight * $otherCountryPerKG;
        }

        // Apply minimum charge
        if ($shippingCharge < $minimumShippingCharge) {
            $shippingCharge = $minimumShippingCharge;
        }

        // Apply maximum charge (if configured)
        if (!empty($maximumShippingCharge) && $shippingCharge > $maximumShippingCharge) {
            $shippingCharge = $maximumShippingCharge;
        }

        // Apply free shipping condition
        if ($isFreeShipping && $subtotal >= $freeShippingAbove) {
            $shippingCharge = 0;
        }

        $cartShippingRate = new CartShippingRate;

        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = $this->getConfigData('title');
        $cartShippingRate->method = $this->getMethod();
        $cartShippingRate->method_title = $this->getConfigData('title');
        $cartShippingRate->method_description = $this->getConfigData('description');
        $cartShippingRate->price = $shippingCharge; // ✅ Set final shipping price
        $cartShippingRate->base_price = $shippingCharge;

        return $cartShippingRate;
    }
}
