# Zeppto Instant Retry module for Magento 2.x

There is NO configuration to be done in the Magento interface, please contact us to enable the service on your instance.

Magento 2:
https://magento2dev.augmented.pw/admin_6hwcym

Generate package:
composer install --ignore-platform-reqs
zip -r zeppto_magento2-2.0.0.zip * -x 'README.md'

 zip -r zeppto_magento2-2.0.2.zip * -x 'README.md' -x 'vendor/*' -x 'composer.phar' -x 'zeppto_magento2*' -x Basic_payment_method.md

composer require zeppto/magento2
composer require zeppto/magento2:dev/master


composer require zeppto/magento2:dev-master

php bin/magento indexer:reindex && php bin/magento cache:clean

See logs:
ssh root@magento2dev.augmented.pw
cd .. cd /var/www/html/magento2
cd var/www/html/magento2/var/log/

Update Code :
rsync -av code/* root@magento2dev.augmented.pw:/var/www/html/magento2/app/code

php bin/magento module:status
php bin/magento module:enable Paywax_Wallet

php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:Deploy -f
chmod -R 777 var/ generated/ pub/

php bin/magento setup:upgrade && php bin/magento setup:di:compile && php bin/magento setup:static-content:Deploy -f && chmod -R 777 var/ generated/ pub/

Test with Code Sniffer:
php phpcs.phar Documents/paywax/magento-plugin/magento2/app/code/Paywax/Wallet/Api/Service.php  
