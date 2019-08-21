<?php
namespace Toshi\Shipping\Model\Carrier;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Config;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Psr\Log\LoggerInterface;
use \Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\AddressRepositoryInterface;
class Shipping extends AbstractCarrier implements CarrierInterface
{
  protected $_code = 'toshi';
  protected $_isFixed = true;
  protected $_rateResultFactory;
  protected $_rateMethodFactory;
  public function __construct(
  ScopeConfigInterface $scopeConfig,
  ErrorFactory $rateErrorFactory,
  LoggerInterface $logger,
  ResultFactory $rateResultFactory,
  MethodFactory $rateMethodFactory,
  Session $session,
  AddressRepositoryInterface $addressRepositoryInterface,
  array $data = []
  )
  {
    $this->_rateResultFactory = $rateResultFactory;
    $this->_rateMethodFactory = $rateMethodFactory;
    $this->_scopeConfig = $scopeConfig;
    $this->_session = $session;
    $this->_addressRepository = $addressRepositoryInterface;
      parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
  }
  public function getAllowedMethods()
  {
    return [$this->getCarrierCode() => __($this->getConfigData('name'))];
  }
  public function collectRates(RateRequest $request)
  {
    $toshiMinBasketAmount = $this->_scopeConfig->getValue('carriers/toshi/toshi_min_basket_amount');
    $taxDefaultPostcode = $this->_scopeConfig->getValue('tax/defaults/postcode');
    $post = json_decode(file_get_contents('php://input'));

    // Minimum fields required check
    if (!$this->fieldsCheck($post)) {
        return false;
    }

    // Does the default tax code match destination postcode
    if ($taxDefaultPostcode == $request->getDestPostcode()) {
        return false;
    }

    // Does the basket value meet the minimum requirement
    if ($request->getPackageValue() < intval($toshiMinBasketAmount)) {
        return false;
    }

    $toshiApiKey = $this->_scopeConfig->getValue('carriers/toshi/toshi_server_api_key');
    $toshiUrl = $this->_scopeConfig->getValue('carriers/toshi/toshi_endpoint_url');

    if (!$this->isActive() || !$this->addressEligible($request, $toshiApiKey, $toshiUrl))
    {
        return false;
    }

    $result = $this->_rateResultFactory->create();
    $shippingPrice = $this->getConfigData('price');

    $method = $this->_rateMethodFactory->create();

    $method->setCarrier($this->getCarrierCode());
    $method->setCarrierTitle($this->getConfigData('title'));

    $method->setMethod($this->getCarrierCode());
    $method->setMethodTitle($this->getConfigData('name'));

    $method->setPrice($shippingPrice);
    $method->setCost($shippingPrice);

    $result->append($method);
    return $result;
  }

  private function addressEligible($request, $toshiApiKey, $toshiUrl)
  {
      $postcodeObject = (object) [];
      $postcodeObject->postcode = $request->getDestPostcode();
      $postcodeObject->address_line_1 = $request->getDestStreet();
      $postcodeObject->address_line_2 = 'N/A';
      $postcodeObject->town = $request->getDestCity();
      $postcodeObject->country = $request->getDestCountryId();
      $url = $toshiUrl.'/api/v2/address/eligible';
      $request = curl_init($url);
      $json = json_encode($postcodeObject);


      curl_setopt($request, CURLOPT_POST, 1);
      curl_setopt($request, CURLOPT_RETURNTRANSFER,1);
      curl_setopt($request, CURLOPT_POSTFIELDS, $json);
      curl_setopt($request, CURLOPT_HTTPHEADER, array("X-Toshi-Server-Api-Key: {$toshiApiKey}", "Content-Type: application/json"));

      $response =  json_decode(curl_exec($request));

      if (array_key_exists("eligible", $response)) {
          return $response->eligible;
      }
      else {
          return false;
      }
  }

  private function fieldsCheck($post)
  {

      // On payment option selection, we can return true
      if (isset($post->paymentMethod))
      {
          return true;
      }

      // Logged in customer with existing address
      if (isset($post->addressId))
      {
          $addressObject = $this->_addressRepository->getById($post->addressId);
          return $addressObject->getTelephone() != '';
      }

      // Telephone number available?
      if (isset($post->address->telephone))
      {
          return $post->address->telephone != '';
      }

      if (isset($post->addressInformation->shipping_address->telephone))
      {
          return $post->addressInformation->shipping_address->telephone != '';
      }

      return false;
  }
}
