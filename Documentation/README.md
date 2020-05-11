# Integration Configuration downloader
This folder contains some extra info on how to implement the Integration Configuration downloader.


## Downloading the Integration Configuration
The KnownUser library needs the Triggers and Actions to know which pages to protect and which queues to use. 
These Triggers and Actions are specified in the Go Queue-it self-service portal.

You should have a timer function, scheduled task, cron job or similar to download and cache the configuration for 5 - 10 minutes, so the configuration is ready when requests come in. You should NEVER download the configuration as part of the request handling.
You can find your configuration file here https://[your-customer-id].queue-it.net/status/integrationconfig/[your-customer-id] or via secure link (*) https://[your-customer-id].queue-it.net/status/integrationconfig/secure/[your-customer-id] after a successful publish. Remember, when you programmatically perform HTTP GET against the above endpoint then make sure you actually received the JSON data, i.e. only accept HTTP 200 with content-type application/json. 
Please contact Queue-it support through the Go Queue-it self-service portal to get further help on this.

### * How to download integration config with Api secrete Key:
Integration configuration contains valuable information like triggers and actions. Anyone can download the configuration by knowing the URL because it does not require any authentication. You can protect integration configurations by enabling the “**Secure integration config**” setting, so only legitimate systems can download it by providing a valid API key.

1. You need to enable “**Secure integration config**” setting in the Go Queue-it self-service portal.
2. You need to decorate the request by adding API key in the request header. You can get API key in the Go Queue-it self-service portal.

curl --request GET https://[your-customer-id].queue-it.net/status/integrationconfig/secure/[your-customer-id]' --header 'api-key: [Customer API-Key]'


![Configuration Provider flow](https://github.com/queueit/KnownUser.V3.PHP/blob/master/Documentation/ConfigProviderExample.png)




