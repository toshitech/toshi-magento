<?php
namespace Toshi\Shipping\Model\Carrier;

use Magento\Checkout\Model\ConfigProviderInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

class CustomConfigProvider implements ConfigProviderInterface
{
    protected $checkoutSession;

    public function __construct(ScopeConfig $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig()
    {        
        $toshi_data = [];

        // Get the Toshi key
        $toshi_data['toshiKey'] = $this->scopeConfig->getValue('carriers/toshi/toshi_client_api_key');
        $toshi_data['toshiUrl'] = $this->scopeConfig->getValue('carriers/toshi/toshi_endpoint_url');

        // Get up and down sizes for items in cart
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        // Get quote items collection
        //$itemsCollection = $cart->getQuote()->getItemsCollection();

        // get quote items array
        $items = $cart->getQuote()->getAllVisibleItems();

        $orderObj = (object) [];

        foreach( $items as $item )
        {
            // Get the configurable product
            $product = $objectManager->get('\Magento\Catalog\Model\Product')->load($item->getProductId());

            $orderItemObj = (object) [];
            $orderItemObj->name = $item->getName();
            $orderItemObj->sku = $item->getSku();
            $orderItemObj->qty = $item->getQty();
            $orderItemObj->description = $product->getDescription();
            $orderItemObj->additionalSizes = $this->getSizes($item->getProductId(), $item->getSku());
            $orderObj->products[] = $orderItemObj;
        }

        $toshi_data['toshiData'] = $orderObj;

        return $toshi_data;
    }

    function getSizes($id, $selectedSku){
        $sizes = array();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $prod = $objectManager->create('Magento\Catalog\Model\Product')->load($id);
        if($prod->getTypeId() === 'configurable'){
            $allProducts = $prod->getTypeInstance(true)->getUsedProducts($prod);
            foreach ($allProducts as $subproduct) {
                if ($subproduct->isSaleable() && $subproduct->getSku() != $selectedSku) {
                    $size = (object) [];
                    $size->variantSku = $subproduct->getSku();
                    $size->size = $subproduct->getAttributeText('size');
                    $size->isAvailable = true;
                    $sizes[] = $size;
                }
            }
        }
        return $sizes;
    }

}