# Integration Configuration downloader
This folder contains some extra info on how to implement the Integration Configuration downloader.


## Downloading the Integration Configuration
The KnownUser library needs the Triggers and Actions to know which pages to protect and which queues to use. 
These Triggers and Actions are specified in the Go Queue-it self-service portal.

You should have a timer function, scheduled task, cron job or similar to download and cache the configuration for 5 - 10 minutes, so the configuration is ready when requests come in. You should NEVER download the configuration as part of the request handling.
You can find your configuration file here https://[your-customer-id].queue-it.net/status/integrationconfig/[your-customer-id] after a succesful publish.
Please contact Queue-it support through the Go Queue-it self-service portal to get further help on this.

![Configuration Provider flow](https://github.com/queueit/KnownUser.V3.PHP/blob/master/Documentation/ConfigProviderExample.png)




