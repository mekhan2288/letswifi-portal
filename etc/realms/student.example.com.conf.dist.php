<?php return [
	// The short name of the realm; multi-language,
	// at least one language must be provided.
	// Will be used as title in choice menus, profile installation, etc.
	// REQUIRED: Array language => name
	'display_name' => [
		'en-GB' => 'Student',
		'nl-NL' => 'Student',
	],

	// Longer descriptive name of the realm; multi-language.
	// at least one language must be provided.
	// Will be used as title in choice menus, profile installation, etc.
	// OPTIONAL: Array language => name
	'description' => [
		'en-GB' => 'Network for students',
		'nl-NL' => 'Netwerk voor studenten',
	],

	// The client requires that the RADIUS server presents a certificate
	// containing at least one of these server names.
	// For old Android versions that don't support multiple server names,
	// the longest common suffix is used instead;
	// because of this it is recommend to keep all server names within the same domain.
	// REQUIRED: Array, list of server names, at least one
	'server_names' => ['radius.example.com'],

	// Signing certificate authority
	// This CA must have both certificate and private key available
	// REQUIRED: String, name of the signer CA
	'signer' => 'CN=example.com Let\'s Wi-Fi CA',

	// Trusted certificate authorities
	// This can be more than one; server certificate must be signed by one CA
	// Private key does not need to be present, but then the RADIUS server
	// must be provisioned another way, such as through an ACME provider
	// REQUIRED: Array, list of trusted CAs
	'trust' => ['C=US, O=Let\'s Encrypt, CN=R11'],

	// When signing the client certificate credential,
	// set the validity this many days in the future
	// REQUIRED: Integer, number of days of validity
	'validity' => 365,

	// Contact information for this realm
	// When the user has selected a realm and downloads a profile file,
	// this contact information may be present in the file.
	// When using an app, it may show this information prior to connecting,
	// and when re-launching the app after the profile has been configured.
	// REQUIRED: String, ID of contact
	'contact' => 'example.com',

	// List of Wi-Fi networks to configure on the clients
	// These can be SSID and HS20 networks.
	// Currently there is no way to let users opt-in or opt-out from networks,
	// you may use different realms for this if it is desireable.
	// The names of the networks must match the network ID in this configuration
	// REQUIRED: Array, list of network IDs
	'networks' => ['eduroam'],
];
