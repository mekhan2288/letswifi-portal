<?php return [
	// List of providers supported by this server.
	// The key must either be '*', meaning any,
	// or the HTTP hostname used for the request.
	// It is neither supported or recommended to
	// use multiple hostnames for the same provider;
	// The wildcard is only provided for convenience.
	// If users will use different hostnames,
	// please set up redirects to the one canonical hostname.
	'providers' => [
		'*' => [
			// The short name of the provider; multi-language,
			// Apps can show this description when the user chooses the
			// institution in one of the geteduroam apps.
			// REQUIRED: Array language => name
			'display_name' => ['en-GB' => 'Default provider'],

			// Longer descriptive name of the provider; multi-language.
			// Apps can show this description when the user chooses the
			// institution in one of the geteduroam apps.
			// OPTIONAL: Array language => name
			'description' => ['en-GB' => 'The default provider'],

			// List of realms that are available to this provider.
			// Used for access control; left hand is affiliation,
			// as reported by authentication module, right hand is list
			// of accessible realms.
			// The * affiliation will always match.
			// If a user has multiple affiliations, they will have access to the
			// sum of realms available to these affiliations.
			// If a matching affilation has an empty list of realms,
			// further affiliations are no longer considered;
			// this can be used to lock out some affiliations.
			// If the user has access to multiple realms, they will be prompted
			// to select a realm when they attempt to generate a profile.
			// REQUIRED: Array affiliation => list of available realms
			'realm' => [
				'staff' => ['staff.example.com'],
				'student' => ['student.example.com'],
				'*' => ['example.com']
			],

			// Contact information for this provider
			// This may be shown to the user prior to selecting a realm,
			// and it will be available through public API.
			// After the user selects a realm,
			// the contact information from the realm is used.
			// REQUIRED: String, ID of contact
			'contact' => 'example.com',

			// Authentication configuration
			// Choose a authentication service and parameters.
			// The service must match one of the supported services,
			// param are the parameters provided to the service.
			// REQUIRED: Array service => string, param => array
			'auth' => [
				'service' => 'DevAuth',
				'param' => [],
			],

			// OAuth settings
			// We strongly recommend using different encryption keys per provider,
			// in order to prevent token confusion, where a user can copy their
			// oauth token to another provider.  There is some protection against
			// this because the realm has to match too, but using different keys
			// guarantees that tokens can't be moved between providers.
			// REQUIRED: Array <TODO description>
			'oauth' => [
				'clients' => (require __DIR__ . DIRECTORY_SEPARATOR . 'clients.php'),
				'pdo' => [
					'dsn' => 'sqlite:' . dirname( __DIR__ ) . '/var/letswifi-dev.sqlite',
					'username' => null,
					'password' => null,
				],
				'keys' => [
					'my_kid' => [
						'key' => 'N8Je0+zjMwQX8bkKXu7XyKUDRszsuRETDtYrKMtRlPU=',
						'iss' => 1676291040,
						'exp' => null,
					],
				],
			],
		],
	],
	// All realms managed by this server.
	// The realm is the part after the @ in the outer identity
	// The realm has information on how to identify the RADIUS server,
	// and how to generate a RADIUS credential.  Additionally,
	// it contains information on which network to connect to and helpdesk info.
	// Realms are accessible to users depending on the realm settings in the provider.
	'realm' => [
		// Example realm for staff
		'staff.example.com' => [
			// The short name of the realm; multi-language,
			// at least one language must be provided.
			// Will be used as title in choice menus, profile installation, etc.
			// REQUIRED: Array language => name
			'display_name' => ['en-GB' => 'Staff'],

			// Longer descriptive name of the realm; multi-language.
			// at least one language must be provided.
			// Will be used as title in choice menus, profile installation, etc.
			// OPTIONAL: Array language => name
			'description' => ['en-GB' => 'Network for staff'],

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
		],

		// Multiple example realms
		'student.example.com' => [
			'display_name' => ['en-GB' => 'Student'],
			'description' => ['en-GB' => 'Network for students'],
			'server_names' => ['radius.example.com'],
			'signer' => 'CN=example.com Let\'s Wi-Fi CA',
			'trust' => ['C=US, O=Let\'s Encrypt, CN=R11'],
			'validity' => 365,
			'contact' => 'example.com',
			'networks' => ['eduroam'],
		],
		'example.com' => [
			'display_name' => ['en-GB' => 'Example'],
			'description' => ['en-GB' => 'The example realm'],
			'server_names' => ['radius.example.com'],
			'signer' => 'CN=example.com Let\'s Wi-Fi CA',
			'trust' => ['C=US, O=Let\'s Encrypt, CN=R11'],
			'validity' => 365,
			'contact' => 'example.com',
			'networks' => ['eduroam'],
		],
	],

	// Wi-Fi network that the client must select
	'network' => [
		// Settings for the eduroam federated network
		'eduroam' => [
			// Name of the network, used for systems with named network profiles.
			// currently it's used for apple-mobileconfig and google-onc profiles.
			// If the profile has no localisation support, the current language when
			// the profile was generated decides the name being used in the profile.
			// REQUIRED: Array language => name
			'display_name' => ['en-GB' => 'eduroam'],

			// SSID to configure for the network profile on the client.
			// At least one SSID or OID must be provided.
			// OPTIONAL: String for a single SSID
			'ssid' => 'eduroam',

			// OID to configure for the network profile on the client.
			// At least one SSID or OID must be provided.
			// OPTIONAL: Array of strings, containing hexadecimal OID
			'oids' => ['5a03ba0800'],
		],
	],

	// Contact information for the helpdesk
	// PLEASE NOTE: Information entered here is publicly viewable
	// These are referenced by realms and providers, and provide users with
	// contact information to a helpdesk where they can get help with their
	// connection and their devices.  This contact information is publicly
	// available through the API, and will be shown in the apps and might
	// also be shown on the web portal.
	'contact' => [
		// Free-text key, must be referenced from provider or realm
		'example.com' => [
			// E-mail address for support
			// OPTIONAL: Array language => name
			'mail' => ['en-GB' => 'mailto:contact@example.com'],

			// Website address for support
			// OPTIONAL: Array language => name
			'web' => ['en-GB' => 'https://support.example.com'],

			// Website address for support
			// OPTIONAL: Array language => name
			'phone' => ['en-GB' => 'tel:+1555eduroam'],

			// Location of the venue in lat/lon
			// This data, if provided, is included in the API and in eap-config files,
			// but currently it's not being used for anything.
			// OPTIONAL: Array with lat: float, lon: float
			'location' => ['lat' => 52.0, 'lon' => 5.1], // Utrecht Centraal

			// Logo for the provider or realm
			// This will be displayed prominent in the apps
			// If no logo is to be set, omit the whole entry
			// OPTIONAL: Array with bytes: string and content_type: string
			'logo' => [
				// Bytes of the image file; it's recommended to instead use
				// bytes#file and refer to a file instead
				// REQUIRED: String
				'bytes#file' => 'logo.png',

				// Content type, also known as MIME type; typically image/{png,jpeg},
				// but image/svg+xml is also possible.
				// REQUIRED: String
				'content_type'=>'image/png'
			],
		],
	],

	'certificate' => [
		'CN=example.com Let\'s Wi-Fi CA' => [
			'x509' => '-----BEGIN CERTIFICATE-----
MIIB2TCCAX+gAwIBAgIIECklhCRm2HEwCgYIKoZIzj0EAwIwJTEjMCEGA1UEAwwa
ZXhhbXBsZS5jb20gTGV0J3MgV2ktRmkgQ0EwIBcNMjMwMjEzMTIyNDAwWhgPMjA3
MzAxMzExMjI0MDBaMCUxIzAhBgNVBAMMGmV4YW1wbGUuY29tIExldCdzIFdpLUZp
IENBMFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEtlsu05kmTP2NkvwsCfXCdvF2
jOO2PenAQzvzONEIPl2cHbzF1wfCfPrXtFDPQzzGJFEc2MUzv0ABcXKdq96exqOB
ljCBkzAdBgNVHQ4EFgQUSec9SZXUnMRKJGZxg560kLRuvzIwVAYDVR0jBE0wS4AU
Sec9SZXUnMRKJGZxg560kLRuvzKhKaQnMCUxIzAhBgNVBAMMGmV4YW1wbGUuY29t
IExldCdzIFdpLUZpIENBgggQKSWEJGbYcTAPBgNVHRMBAf8EBTADAQH/MAsGA1Ud
DwQEAwIBhjAKBggqhkjOPQQDAgNIADBFAiBqyg1tvjv8FlEeA8n70aMZ42Lfqctl
nLMxCOn18PVWtQIhAIC9Ui6updRWRrS2WbMLo/gMNo574xk6lYuzjebKPMDd
-----END CERTIFICATE-----
',
			'key' => '-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIE5FK2GdNHz7yHAXdH+aL5N6qwa3WJ98y6zEzFHByD7QoAoGCCqGSM49
AwEHoUQDQgAEtlsu05kmTP2NkvwsCfXCdvF2jOO2PenAQzvzONEIPl2cHbzF1wfC
fPrXtFDPQzzGJFEc2MUzv0ABcXKdq96exg==
-----END EC PRIVATE KEY-----
',
		],
		'C=US, O=Let\'s Encrypt, CN=R11' => [
			'x509' => '-----BEGIN CERTIFICATE-----
MIIFBjCCAu6gAwIBAgIRAIp9PhPWLzDvI4a9KQdrNPgwDQYJKoZIhvcNAQELBQAw
TzELMAkGA1UEBhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2Vh
cmNoIEdyb3VwMRUwEwYDVQQDEwxJU1JHIFJvb3QgWDEwHhcNMjQwMzEzMDAwMDAw
WhcNMjcwMzEyMjM1OTU5WjAzMQswCQYDVQQGEwJVUzEWMBQGA1UEChMNTGV0J3Mg
RW5jcnlwdDEMMAoGA1UEAxMDUjExMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIB
CgKCAQEAuoe8XBsAOcvKCs3UZxD5ATylTqVhyybKUvsVAbe5KPUoHu0nsyQYOWcJ
DAjs4DqwO3cOvfPlOVRBDE6uQdaZdN5R2+97/1i9qLcT9t4x1fJyyXJqC4N0lZxG
AGQUmfOx2SLZzaiSqhwmej/+71gFewiVgdtxD4774zEJuwm+UE1fj5F2PVqdnoPy
6cRms+EGZkNIGIBloDcYmpuEMpexsr3E+BUAnSeI++JjF5ZsmydnS8TbKF5pwnnw
SVzgJFDhxLyhBax7QG0AtMJBP6dYuC/FXJuluwme8f7rsIU5/agK70XEeOtlKsLP
Xzze41xNG/cLJyuqC0J3U095ah2H2QIDAQABo4H4MIH1MA4GA1UdDwEB/wQEAwIB
hjAdBgNVHSUEFjAUBggrBgEFBQcDAgYIKwYBBQUHAwEwEgYDVR0TAQH/BAgwBgEB
/wIBADAdBgNVHQ4EFgQUxc9GpOr0w8B6bJXELbBeki8m47kwHwYDVR0jBBgwFoAU
ebRZ5nu25eQBc4AIiMgaWPbpm24wMgYIKwYBBQUHAQEEJjAkMCIGCCsGAQUFBzAC
hhZodHRwOi8veDEuaS5sZW5jci5vcmcvMBMGA1UdIAQMMAowCAYGZ4EMAQIBMCcG
A1UdHwQgMB4wHKAaoBiGFmh0dHA6Ly94MS5jLmxlbmNyLm9yZy8wDQYJKoZIhvcN
AQELBQADggIBAE7iiV0KAxyQOND1H/lxXPjDj7I3iHpvsCUf7b632IYGjukJhM1y
v4Hz/MrPU0jtvfZpQtSlET41yBOykh0FX+ou1Nj4ScOt9ZmWnO8m2OG0JAtIIE38
01S0qcYhyOE2G/93ZCkXufBL713qzXnQv5C/viOykNpKqUgxdKlEC+Hi9i2DcaR1
e9KUwQUZRhy5j/PEdEglKg3l9dtD4tuTm7kZtB8v32oOjzHTYw+7KdzdZiw/sBtn
UfhBPORNuay4pJxmY/WrhSMdzFO2q3Gu3MUBcdo27goYKjL9CTF8j/Zz55yctUoV
aneCWs/ajUX+HypkBTA+c8LGDLnWO2NKq0YD/pnARkAnYGPfUDoHR9gVSp/qRx+Z
WghiDLZsMwhN1zjtSC0uBWiugF3vTNzYIEFfaPG7Ws3jDrAMMYebQ95JQ+HIBD/R
PBuHRTBpqKlyDnkSHDHYPiNX3adPoPAcgdF3H2/W0rmoswMWgTlLn1Wu0mrks7/q
pdWfS6PJ1jty80r2VKsM/Dj3YIDfbjXKdaFU5C+8bhfJGqU3taKauuz0wHVGT3eo
6FlWkWYtbt4pgdamlwVeZEW+LM7qZEJEsMNPrfC03APKmZsJgpWCDWOKZvkZcvjV
uYkQ4omYCTX5ohy+knMjdOmdH9c7SpqEWBDC86fiNex+O0XOMEZSa8DA
-----END CERTIFICATE-----',
			'issuer' => 'C=US, O=Internet Security Research Group, CN=ISRG Root X1',
		],
		'C=US, O=Internet Security Research Group, CN=ISRG Root X1' => [
			'x509' => '-----BEGIN CERTIFICATE-----
MIIFazCCA1OgAwIBAgIRAIIQz7DSQONZRGPgu2OCiwAwDQYJKoZIhvcNAQELBQAw
TzELMAkGA1UEBhMCVVMxKTAnBgNVBAoTIEludGVybmV0IFNlY3VyaXR5IFJlc2Vh
cmNoIEdyb3VwMRUwEwYDVQQDEwxJU1JHIFJvb3QgWDEwHhcNMTUwNjA0MTEwNDM4
WhcNMzUwNjA0MTEwNDM4WjBPMQswCQYDVQQGEwJVUzEpMCcGA1UEChMgSW50ZXJu
ZXQgU2VjdXJpdHkgUmVzZWFyY2ggR3JvdXAxFTATBgNVBAMTDElTUkcgUm9vdCBY
MTCCAiIwDQYJKoZIhvcNAQEBBQADggIPADCCAgoCggIBAK3oJHP0FDfzm54rVygc
h77ct984kIxuPOZXoHj3dcKi/vVqbvYATyjb3miGbESTtrFj/RQSa78f0uoxmyF+
0TM8ukj13Xnfs7j/EvEhmkvBioZxaUpmZmyPfjxwv60pIgbz5MDmgK7iS4+3mX6U
A5/TR5d8mUgjU+g4rk8Kb4Mu0UlXjIB0ttov0DiNewNwIRt18jA8+o+u3dpjq+sW
T8KOEUt+zwvo/7V3LvSye0rgTBIlDHCNAymg4VMk7BPZ7hm/ELNKjD+Jo2FR3qyH
B5T0Y3HsLuJvW5iB4YlcNHlsdu87kGJ55tukmi8mxdAQ4Q7e2RCOFvu396j3x+UC
B5iPNgiV5+I3lg02dZ77DnKxHZu8A/lJBdiB3QW0KtZB6awBdpUKD9jf1b0SHzUv
KBds0pjBqAlkd25HN7rOrFleaJ1/ctaJxQZBKT5ZPt0m9STJEadao0xAH0ahmbWn
OlFuhjuefXKnEgV4We0+UXgVCwOPjdAvBbI+e0ocS3MFEvzG6uBQE3xDk3SzynTn
jh8BCNAw1FtxNrQHusEwMFxIt4I7mKZ9YIqioymCzLq9gwQbooMDQaHWBfEbwrbw
qHyGO0aoSCqI3Haadr8faqU9GY/rOPNk3sgrDQoo//fb4hVC1CLQJ13hef4Y53CI
rU7m2Ys6xt0nUW7/vGT1M0NPAgMBAAGjQjBAMA4GA1UdDwEB/wQEAwIBBjAPBgNV
HRMBAf8EBTADAQH/MB0GA1UdDgQWBBR5tFnme7bl5AFzgAiIyBpY9umbbjANBgkq
hkiG9w0BAQsFAAOCAgEAVR9YqbyyqFDQDLHYGmkgJykIrGF1XIpu+ILlaS/V9lZL
ubhzEFnTIZd+50xx+7LSYK05qAvqFyFWhfFQDlnrzuBZ6brJFe+GnY+EgPbk6ZGQ
3BebYhtF8GaV0nxvwuo77x/Py9auJ/GpsMiu/X1+mvoiBOv/2X/qkSsisRcOj/KK
NFtY2PwByVS5uCbMiogziUwthDyC3+6WVwW6LLv3xLfHTjuCvjHIInNzktHCgKQ5
ORAzI4JMPJ+GslWYHb4phowim57iaztXOoJwTdwJx4nLCgdNbOhdjsnvzqvHu7Ur
TkXWStAmzOVyyghqpZXjFaH3pO3JLF+l+/+sKAIuvtd7u+Nxe5AW0wdeRlN8NwdC
jNPElpzVmbUq4JUagEiuTDkHzsxHpFKVK7q4+63SM1N95R1NbdWhscdCb+ZAJzVc
oyi3B43njTOQ5yOf+1CceWxG1bQVs5ZufpsMljq4Ui0/1lvh+wjChP4kqKOJ2qxq
4RgqsahDYVvTH9w7jXbyLeiNdd8XM2w9U/t7y0Ff/9yi0GE44Za4rF2LN9d11TPA
mRGunUHBcnWEvgJBQl9nJEiU0Zsnvgc/ubhPgXRR4Xq37Z0j4r7g1SgEEzwxA57d
emyPxgcYxn/eR44/KJ4EBs+lVDR3veyJm+kXQ99b21/+jh5Xos1AnX5iItreGCc=
-----END CERTIFICATE-----',
		],
	],
];
