# payrexx-payment-gateway
Payrexx Payment Gateway Plugin for Gambio allows you to integrate your Gambio shop easily with Payrexx Payment system.

## System Requirements

- Gambio GX4 platform

## Guides
1. [User Guide](https://docs.google.com/document/d/1Cjqsv64RGwqucXJVnn0GQay0Z9Zq_aLhUd_we5JUqPs/edit)

### Installation

Follow these steps to install the plugin.

1. Download the plugin

2. Extract the zip file

3. Copy the extracted files and paste in root directory

4. Login to Admin Panel

5. Click on Toolbox

6. Clear Cache

7. Go To Modules -> Payment Systems

8. Click on "Miscellaneous" tab and find the Module.

9. Install the Module

11. Set Enabled to “Yes” and save configuration

### Payrexx Configuration
To Configure the webhook URL in Payrexx, Log in your Payrexx account.

1. Go to **Webhooks** -> **Add webhook** --> Find **Webhook URL**

2. Insert the URL to your shop and add /callback/payrexx/dispatcher.php (e.g. If your shop url is https://www.example.com, the Webhook URL will be https://www.example.com/callback/payrexx/dispatcher.php)

## Built With

* Gambio GX4 framework

## Versioning

### 1.0.0 (02-06-2021)

- A complete working plugin

### 1.2.0 (04-10-2021)

- Add ideal-pay.ch in platform providers list.

### 1.2.1 (20-10-2021)

- Add ideal-pay.ch in platform providers list.

## Authors

See also the list of [contributors](payrexx-payment-gateway/graphs/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
