# Help and examples
This folder contains some extra helper functions and examples.


## Downloading the Integration Configuration
The KnownUser library needs the Triggers and Actions to know which pages to protect and which queues to use. 
These Triggers and Actions are specified in the Go Queue-it self-service portal.

It is recommended to make a timer function that can download and cache the configuration for e.g. 5-10 minutes.
Please contact Queue-it support through the Go Queue-it self-service portal to get further help on this.

![Configuration Provider flow](https://github.com/queueit/KnownUser.V3.PHP/blob/master/Documentation/ConfigurationProviderExample.PNG)


## Helper functions
The [QueueITHelpsers.php](https://github.com/queueit/KnownUser.V3.PHP/blob/master/Documentation/QueueITHelpers.php) file includes some helper function 
to make the reading of the `queueittoken` easier. 



