# Integration Configuration downloader
This folder contains some extra info on how to implement the Integration Configuration downloader.


## Downloading the Integration Configuration
The KnownUser library needs the Triggers and Actions to know which pages to protect and which queues to use. 
These Triggers and Actions are specified in the Go Queue-it self-service portal.

It is recommended to make a timer function that can download and cache the configuration for e.g. 5-10 minutes.
You can find your configuration file here https://[your-customer-id].queue-it.net/status/integrationconfig/[your-customer-id]?qr=[time-based-query-string] after a succesful publish.
Please contact Queue-it support through the Go Queue-it self-service portal to get further help on this.

![Configuration Provider flow](https://github.com/queueit/KnownUser.V3.PHP/blob/master/Documentation/ConfigurationProviderExample.PNG)




