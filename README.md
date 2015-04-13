The Eventgallery-SparkBooth-Connector
=================================

## About
It's a controller-plugin for the [Eventgallery Component](http://www.svenbluege.de/joomla-event-gallery) for Joomla!
You can then "connect" the [Sparkbooth Photobooth DSLR Software](http://sparkbooth.com/dslr-photobooth/) running on a Windows PC by using the "Custom Upload" Feature. This will automatically create a new (unpublished) Event in Eventgallery where all newly taken Pictures, created during the Photobooth Event, are uploaded.

## Requirements
* Joomla! 3+ with Eventgallery 3.3 installed
* Sparkbooth DSLR 4+
* this Connector

## How-to

First of all you need to get the file **SparkboothConnector.php** from this repository and place it into the folder **/components/com_eventgallery/controllers** of your Joomla! installation. 
After that you need to need to create **a new Joomla! user** which can create new events in the Eventgallery component (so *Editor* or *Publisher* may be ok, but not just *Registered*).
Then configure your Sparkbooth Software to send the photos to your Joomla! installation using the username and password of the new user.
In the section "Send to Account" switch to "Custom Upload" and enter the following URL http[s]://[yourwebsite]/index.php?option=com_eventgallery&view=Sparkboothconnector&task=display&tmpl=raw


## License
This Software is available under the terms of the GNU General Public License version 2 or later.
