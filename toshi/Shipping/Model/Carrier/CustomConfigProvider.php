<?php
namespace Toshi\Shipping\Model\Carrier;

use Magento\Checkout\Model\ConfigProviderInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

class CustomConfigProvider implements ConfigProviderInterface
{
    protected $checkoutSession;
    private $_logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ScopeConfig $scopeConfig
    ) {
        $this->_logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    public function log($message) {
        if( is_object( $message ) || is_array( $message ) ) {
            $message = print_r( $message, true );
        }
        $this->_logger->debug( $message );
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

            // Get the availability type and date for basket item (TW)
//             $originalAvailabilityType = $product->getData('availability_type');
//             $originalAvailabilityDate = $product->getData('availability_date');
            $originalAvailabilityType = $item->getAvailabilityType();
            $originalAvailabilityDate = $item->getAvailabilityDate();

            $orderItemObj = (object) [];
            $orderItemObj->name = $item->getName();
            $orderItemObj->sku = $item->getSku();
            $orderItemObj->qty = $item->getQty();
            $orderItemObj->description = $product->getDescription();
            $orderItemObj->additionalSizes = $this->getSizes($item->getProductId(), $item->getSku(), $originalAvailabilityType, $originalAvailabilityDate);
            $orderObj->products[] = $orderItemObj;
        }

        $toshi_data['toshiData'] = $orderObj;

        return $toshi_data;
    }

    function getSizes($id, $selectedSku, $originalAvailabilityType, $originalAvailabilityDate){
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
                    $size->isAvailable = $this->isAvailable($subproduct, $originalAvailabilityType, $originalAvailabilityDate);
                    $sizes[] = $size;
                }
            }
        }
        return $sizes;
    }

    // Check if size options are available depending on original item availability type and availability date
    function isAvailable($subproduct, $originalAvailabilityType, $originalAvailabilityDate){
      if (!isset($originalAvailabilityType) || $subproduct->getData('availability_type') == 'immediate'){
        return true;
      }

      if ($originalAvailabilityType == 'immediate'){
        return false;
      }

      if (isset($originalAvailabilityDate) && $subproduct->getData('availability_date') == $originalAvailabilityDate){
        return true;
      } 

      return false;
    }
}
