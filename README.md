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
1. configure toshi-magento/toshi/Shipping/view/frontend/requirejs-config.js to point to the correct CDN for the environment. ('https://integration-cdn.toshi.co/3.0/main.min.js) for production (https://integration-sandbox-cdn.toshi.co/3.0/main.min.js) for staging. 
2. Login the the Magento backend
3. Go to "System" -> "Cache Management"
4. Under the "Additional Cache Management" section, click "Flush Javascript/Css Cache"
5. Go to "Stores" -> "Configuration"
6. Expand the "Sales" section and click "Shipping Methods"
7. Expand the "Toshi Concierge Delivery" shipping option and enable it (if necessary)
8. Update the "Minimum Basket Amount" value (if necessary)
9. Set the "Toshi Endpoint URL" to "https://www.toshi.co" for production. For staging "https://staging.toshi.co" (notice the lack of www).
10. Set the "Toshi Client API Key" to the key provided to you
11. Set the "Toshi Server API Key" to the key provided to you
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
