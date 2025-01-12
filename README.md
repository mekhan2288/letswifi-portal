# Let's Wifi Certificate Authority

This is the reference CA for geteduroam.  It is intended to be used with an app such as [ionic-app](https://github.com/geteduroam/ionic-app).  The process is as follows:

* The app sends the user to /oauth/authorize/ with additional GET parameters
* The user is asked to log in or redirected to an SSO service
* After logging in, the user is redirected to a callback URL from the app
* The app has obtained an authorization_code, which it uses to retrieve an access_code
* The access_code is used to generate an [eap-config](https://tools.ietf.org/html/draft-winter-opsawg-eap-metadata-02)† file containing user credentials
* The app installs the eap-config file
* The server logs the public key material generated, private key material is discarded

† The draft is expired and the [actual format used by CAT and letswifi](https://github.com/GEANT/CAT/raw/refs/heads/master/devices/eap_config/eap-metadata.xsd) is slightly different than the document.


## Install dependencies

In order to automatically install dependencies, run:

	make vendor


## Running a development server

	rm -rf etc/provider.conf.php var
	make dev


### Testing manually

There is a [shell script to initiate an OAuth flow](https://github.com/geteduroam/geteduroam-sh)

	./geteduroam.sh 'http://[::1]:1080' example.com >test.eap-config

* If everything went fine, you get an eap-config XML payload in test.eap-config
* You will see the public key material logged in the `realm_signing_log` SQL table


## Getting up and running quick 'n dirty

Upload this whole project to a webserver, and make `www/` accessible as the top level directory.

This quick'n'dirty guide assumes you'll be using SimpleSAMLphp (the only authentication method supported for production)

	make SIMPLESAMLPHP_VERSION=2.1.1 simplesamlphp

Initialize the SQLite database (MySQL is also supported, this should be straightforward from the config file)

	mkdir var
	sqlite3 var/letswifi-dev.sqlite <sql/letswifi-dev.sqlite.sql

Copy etc/provider.conf.dist.php etc/provider.conf.php and change the file to match your setup.

	cp etc/provider.conf.dist.php etc/provider.conf.php
	$EDITOR etc/provider.conf.php

Write metadata of your SAML IdP to simplesamlphp/metadata/saml20-idp-remote.php

Navigate to https://example.com/simplesaml/module.php/saml/sp/metadata.php/default-sp?output=xhtml to get the metadata of the service, and register it in your IdP


## Running from a subdirectory

For some deployments it can make sense to run this project from a subdirectory.
All paths inside the application are relative, so this should work without any issues.


## Contributing

Before committing, please run

	make camera-ready

And only commit if there are no errors.
