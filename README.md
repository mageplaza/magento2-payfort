magento2-Payfort_Fort
======================

Payfort payment gateway Magento2 extension

Install
=======

1. Go to [Magento2 root folder]/app/code

2. Past Payfort folder to the previous path

3. Go to [Magento2 root folder] & Enter following commands to enable module:

    ```bash
    php bin/magento module:enable Payfort_Fort --clear-static-content
    php bin/magento setup:upgrade
    ```
4. Enable and configure Payfort in Magento Admin under Stores -> Configuration -> Sale -> Payment Methods -> PayFort Fort Payment Methods


