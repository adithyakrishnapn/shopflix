<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Product\Helpers\Review;
use Webkul\Attribute\Repositories\AttributeRepository;

class ProductResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->reviewHelper = app(Review::class);

        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $productTypeInstance = $this->getTypeInstance();
        
        $product = $this->resource;
        $brand = null;

        $brandAttributeValue = $product->getAttribute('brand');
        if ($brandAttributeValue) {
            $attributeRepository = app(AttributeRepository::class);
            $brandAttribute = $attributeRepository->findOneWhere(['code' => 'brand']);

            if ($brandAttribute && $brandAttribute->options) {
            $brandOption = $brandAttribute->options->firstWhere('id', $brandAttributeValue);

            if ($brandOption) {
                $brand = [
                'code'  => 'brand',
                'value' => $brandOption->label,
                'label' => $brandAttribute->name ?? 'Brand',
                ];
            }
            }
        }

        return [
            'id'          => $this->id,
            'sku'         => $this->sku,
            'name'        => $this->name,
            'description' => $this->description,
            'url_key'     => $this->url_key,
            'base_image'  => product_image()->getProductBaseImage($this),
            'images'      => product_image()->getGalleryImages($this),
            'is_new'      => (bool) $this->new,
            'is_featured' => (bool) $this->featured,
            'on_sale'     => (bool) $productTypeInstance->haveDiscount(),
            'is_saleable' => (bool) $productTypeInstance->isSaleable(),
            'is_wishlist' => (bool) auth()->guard()->user()?->wishlist_items
                ->where('channel_id', core()->getCurrentChannel()->id)
                ->where('product_id', $this->id)->count(),
            'min_price'   => core()->formatPrice($productTypeInstance->getMinimalPrice()),
            'prices'      => $productTypeInstance->getProductPrices(),
            'price_html'  => $productTypeInstance->getPriceHtml(),
            'ratings'     => [
                'average' => $this->reviewHelper->getAverageRating($this),
                'total'   => $this->reviewHelper->getTotalRating($this),
            ],
            'reviews'     => [
                'total'   => $this->reviewHelper->getTotalReviews($this),
            ],
            'brand'       => !empty($brand) ? $brand['value'] : null,
        ];
    }
}
