var toshiLaunched = false;
var timeSlotSelected = false;
var additionalSizes = [];
var addressFields = [];
var modal;

function launchToshi() {

  // Prevent user to go to payment step if Toshi is selected and no time slot selected
  jQuery(":submit.continue").click(function (event){
    if (jQuery("[value=toshi_toshi]").is(":checked") && !timeSlotSelected){
      event.preventDefault();
      showErrorMessage();
    }
  });

  modal = window.toshi.createBookingIntegration({
      api: {
        url: checkoutConfig.toshiUrl,
        key: checkoutConfig.toshiKey
      }
  });

  jQuery('#label_carrier_toshi_toshi').append('<div id="toshi-app"></div>');

  modal.mount(document.getElementById('toshi-app'));
  toshiLaunched = true;
  console.log('TOSHI Carrier Service added to DOM');

  // This is fired by the ecommerce integration when the customer attempts to
  // proceed without selecting a timeslot.
  window.showErrorMessage = () => {
    modal.setInlineErrorMessage(
      "Please select a time slot before proceeding"
    );
  };
  window.hideErrorMessage = () => {
    modal.setInlineErrorMessage(undefined);
  };

  modal.onOrderCreated(function() {
    timeSlotSelected = true;
    hideErrorMessage();
  });

  modal.setBrandCheckoutReference(checkoutConfig.quoteItemData[0].quote_id);

  modal.setOrderTotal({
    orderTotal: checkoutConfig.totalsData.base_row_total_incl_tax
  });

  setTimeout(() => {

    // Logged in customer with existing address
    if (isCustomerLoggedIn && customerData.addresses.length > 0) {
      setDetailsFromCustomerData();
    } else { // Logged in customer with no existing address or guest customer
      addressFields = ['input[name=street\\[0\\]]', 'input[name=street\\[1\\]]', 'input[name=city]', 'input[name=region]', 'input[name=postcode]', 'input[name=country_id]'];
      modal.setFirstName(jQuery('input[name=firstname]').val());
      modal.setLastName(jQuery('input[name=lastname]').val());
      modal.setEmail(getCustomerEmail());
      modal.setPhone(jQuery('input[name=telephone]').val());
      setAddress();

      jQuery(document).off('change', 'input[name=firstname]');
      jQuery(document).on('change', 'input[name=firstname]', function() {
        modal.setFirstName(jQuery('input[name=firstname]').val());
      });

      jQuery(document).off('change', 'input[name=lasttname]');
      jQuery(document).on('change', 'input[name=lastname]', function() {
        modal.setLastName(jQuery('input[name=lastname]').val());
      });

      jQuery(document).off('change', '#customer-email');
      jQuery(document).on('change', '#customer-email', function() {
        modal.setEmail(jQuery('#customer-email').val());
      });

      jQuery(document).off('change', 'input[name=telephone]');
      jQuery(document).on('change', 'input[name=telephone]', function() {
        modal.setPhone(jQuery('input[name=telephone]').val());
      });

      jQuery(document).off('change', addressFields);
      jQuery(document).on('change', addressFields, function() {
        setAddress();
      });

    }
  }, 750);

  function setAddress(){
    modal.setAddress({
      addressLine1: jQuery('input[name=street\\[0\\]]').val(),
      addressLine2: jQuery('input[name=street\\[1\\]]').val(),
      town: jQuery('input[name=city]').val(),
      province: jQuery('input[name=region]').val(),
      postcode: jQuery('input[name=postcode]').val(),
      country: jQuery('input[name=country_id]').val()
    });
  }

  const createProduct = (name, sku, qty, imageUrl, retailPrice, availabilityType, availabilityDate, size, colour, availableSizes) => {
    return {
      // Mandatory properties
      name: name,
      size: size,
      sku: sku,
      quantity: qty,
      imageUrl: imageUrl,
      retailPrice: retailPrice,
      finalPrice: retailPrice,

      // Optional properties
      colour: colour,
      availableSizes: availableSizes,
      availabilityType: availabilityType,
      availabilityDate: availabilityDate
    };
  };

  let products = [];
  checkoutConfig.quoteItemData.forEach(function (item, index) {
    availableSizes = checkoutConfig.toshiData.products[index].additionalSizes
    products.push(createProduct(item.name,
                                item.sku,
                                item.qty,
                                item.thumbnail,
                                item.base_price_incl_tax,
                                item.availability_type,
                                item.availability_date,
                                getAttribute(item, "Size"),
                                getAttribute(item, "Color"),
                                availableSizes));
  });

  modal.setProducts(products);

}

// Get item attributes according to label description
function getAttribute(item, attributeType){
  var attributeValue = '';
  item.options.forEach(function (option) {
    if (option['label'] == attributeType) {
      attributeValue = option['value'];
    }
  });
  return attributeValue;
}

// Returns email of logged in customer or guest
function getCustomerEmail() {
  if (isCustomerLoggedIn) {
    return customerData.email;
  } else {
    return jQuery('#customer-email').val();
  }
}

function setDetailsFromCustomerData() {
  var addressIndex = jQuery(".shipping-address-items").children(".selected-item").index();

  // Switching between addresses and none selected at this stage
  if (addressIndex < 0) {
    return false;
  }

  var cacheStorage = localStorage.getItem('mage-cache-storage');

  // New address added not yet present in customerData
  if ((addressIndex + 1) > jQuery(".shipping-address-item").length) {

    if (cacheStorage == null) {
      return false;
    }
    
    var newAddress = JSON.parse(cacheStorage)['checkout-data'].newCustomerShippingAddress

    modal.setFirstName(newAddress.firstname);
    modal.setLastName(newAddress.lastname);
    modal.setEmail(getCustomerEmail());
    modal.setPhone(newAddress.telephone);
    modal.setAddress({
      addressLine1: newAddress.street[0],
      addressLine2: newAddress.street[1],
      town: newAddress.city,
      province: newAddress.region,
      postcode: newAddress.postcode,
      country: newAddress.country_id
    });

  // Get the selected address details from customerData
  } else {
    modal.setFirstName(customerData.addresses[addressIndex].firstname);
    modal.setLastName(customerData.addresses[addressIndex].lastname);
    modal.setEmail(getCustomerEmail());
    modal.setPhone(customerData.addresses[addressIndex].telephone);
    modal.setAddress({
      addressLine1: customerData.addresses[addressIndex].street[0],
      addressLine2: customerData.addresses[addressIndex].street[1],
      town: customerData.addresses[addressIndex].city,
      province: customerData.addresses[addressIndex].region.region,
      postcode: customerData.addresses[addressIndex].postcode,
      country: customerData.addresses[addressIndex].country_id
    });
  }
}

// Get container
const getContainerElement = () =>
document.getElementById(
  'label_carrier_toshi_toshi'
);

MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
var obs = new MutationObserver(function(mutations, observer) {
  if (getContainerElement() && !toshiLaunched) {
      launchToshi();
  }

  if (getContainerElement() && typeof modal != 'undefined' && jQuery('#toshi-app').length === 0){
    jQuery('#label_carrier_toshi_toshi').append('<div id="toshi-app"></div>');
    modal.mount(document.getElementById('toshi-app'));
  }

  if (toshiLaunched && isCustomerLoggedIn && customerData.addresses.length > 0) {
    setDetailsFromCustomerData();
  }
});

obs.observe(document.body, {
    attributes: true,
    childList: true,
    characterData: false,
    subtree: true
});
