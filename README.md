# Clean install:
Upload the "Toshi" folder to the following directory: "public_html/app/code"
SSH into the server and navigate to the "public_html" directory

Run the following commands in the terminal:

```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
php bin/magento cache:clean
```

1. Login the the Magento backend
2. Go to "System" -> "Cache Management"
3. Under the "Additional Cache Management" section, click "Flush Javascript/Css Cache"
4. Go to "Stores" -> "Configuration"
5. Expand the "Sales" section and click "Shipping Methods"
6. Expand the "Toshi Concierge Delivery" shipping option and enable it (if necessary)
7. Update the "Minimum Basket Amount" value (if necessary)
8. Set the "Toshi Endpoint URL" to "https://www.toshi.co" for production. For staging "https://staging.toshi.co" (notice the lack of www).
9. Set the "Toshi Client API Key" to the key provided to you
10. Set the "Toshi Server API Key" to the key provided to you
Click "Save Config"


# Upgrade:
1. Upload the "Toshi" folder to the following directory: "public_html/app/code"
SSH into the server and navigate to the "public_html" directory
2. Run the following commands in the terminal:
```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
php bin/magento cache:clean
```
