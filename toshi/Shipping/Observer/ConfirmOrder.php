<?php
namespace Toshi\Shipping\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use \Magento\Sales\Api\Data\OrderInterface as Order;

class ConfirmOrder implements ObserverInterface
{
    public function __construct(ScopeConfig $scopeConfig, Order $order) {
        $this->scopeConfig = $scopeConfig;
        $this->_order = $order;
    }

    /**
     * Below is the method that will fire whenever the event runs!
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {

        $orderids = $observer->getEvent()->getOrderIds();

        foreach($orderids as $orderid){
            $order = $this->_order->load($orderid);
        }

        $shippingMethod = $order->getShippingMethod();

        // No need to continue if Toshi is not the shipping method
        if ($shippingMethod != 'toshi_toshi') {
            return;
        }

        $toshiApiKey = $this->scopeConfig->getValue('carriers/toshi/toshi_server_api_key');
        $toshiUrl = $this->scopeConfig->getValue('carriers/toshi/toshi_endpoint_url');
        $storeCheckoutReference = $order->getQuoteId();
        $orderId = $order->getIncrementId();
        $customerShippingAddress = $order->getShippingAddress()->getData();
        $customerBillingAddress = $order->getBillingAddress()->getData();

        $url = $toshiUrl.'/api/v2/order/confirm_store_order';
      
        $request = curl_init($url);

        $lineItems = [];

        foreach( $order->getAllVisibleItems() as $item )
        {
            $colour = $this->getAttribute($item['product_options']['attributes_info'], 'Color');
            $size = $this->getAttribute($item['product_options']['attributes_info'], 'Size');

            $orderItemObj = (object) [];
            $orderItemObj->gender = 'N/A';
            $orderItemObj->name = $item->getName();
            $orderItemObj->season = 'N/A';
            $orderItemObj->product_category = 'N/A';
            $orderItemObj->product_subcategory = 'N/A';
            $orderItemObj->description = 'N/A';
            $orderItemObj->sku = $item->getSku();
            $orderItemObj->variant_sku = 'N/A';
            $orderItemObj->retail_price = intval($item->getPrice());
            $orderItemObj->promotion_price = 0;
            $orderItemObj->promotion_id = 'N/A';
            $orderItemObj->markdown_price = 0;
            $orderItemObj->final_price = intval($item->getPriceInclTax());
            $orderItemObj->availability_date = 'N/A';
            $orderItemObj->image_url = 'N/A';
            $orderItemObj->product_url = 'N/A';
            $orderItemObj->colour = $colour;
            $orderItemObj->size = $size;
            $orderItemObj->qty = intval($item->getQtyOrdered());
            array_push($lineItems, $orderItemObj);
        }
      
        $data = array(
            'line_items' => $lineItems,
            'customer' => array(
              'first_name' => $customerShippingAddress['firstname'],
              'surname' => $customerShippingAddress['lastname'],
              'email' => $customerShippingAddress['email'],
              'phone' => $customerShippingAddress['telephone']
            ),
            'billing_address' => array(
              'address_line_1' => $customerBillingAddress['street'],
              'town' => $customerBillingAddress['city'],
              'province' => 'N/A',
              'postcode' => $customerBillingAddress['postcode'],
              'country' => $customerBillingAddress['country_id']
            ),
            'shipping_address' => array(
                'address_line_1' => $customerShippingAddress['street'],
                'town' => $customerShippingAddress['city'],
                'province' => 'N/A',
                'postcode' => $customerShippingAddress['postcode'],
                'country' => $customerShippingAddress['country_id']
            ),
            'brand_checkout_reference' => $storeCheckoutReference,
            'brand_order_reference' => $orderId
        );
      
        $json = json_encode($data);
      
        curl_setopt($request, CURLOPT_POST, 1);
        curl_setopt($request, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($request, CURLOPT_POSTFIELDS, $json);
        curl_setopt($request, CURLOPT_HTTPHEADER, array("X-Toshi-Server-Api-Key: {$toshiApiKey}", "Content-Type: application/json"));
        curl_exec($request);
    }

    public function getAttribute($itemOptions, $descr)
    {
        $attribute = '';
        foreach( $itemOptions as $itemOption )
        {
            if ($itemOption['label'] == $descr) {
                $attribute = $itemOption['value'];
                break;
            }
        }
        return $attribute;
    }
}
