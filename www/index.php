<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;
use letswifi\configuration\DictionaryFile;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 1 ), 'src', '_autoload.php'] );
$basePath = '.';

$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();
$provider = $app->getProvider();

// TODO: Temporary read file directly, add facility in Provider for this later
$installProfiles = new DictionaryFile( \dirname( __DIR__, 1 ) . \DIRECTORY_SEPARATOR . 'etc' . \DIRECTORY_SEPARATOR . 'userinstallers.conf.php' );

// TODO: Make platform class that handles this, move this code out of the view
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$platforms = $installProfiles->getDictionaryList( 'platforms' );
$apps = $installProfiles->getRawArray( 'apps' );
$profiles = $installProfiles->getRawArray( 'profiles' );
$matchedPlatform = null;

foreach ( $platforms as $key => $platform ) {
	$pattern = \str_replace( '@', '\\@', $platform->getString( 'match' ) );
	\assert( \is_string( $pattern ) );

	if ( \preg_match( "@{$pattern}@", $userAgent ) ) {
		$matchedPlatform = $installProfiles->getDictionary( 'platforms' )->getRawArray( $key );

		// Set "apps" and "profiles" for the platform to the actual apps and profiles,
		// instead of just references.
		$matchedPlatform['apps'] = \array_combine(
			$platform['apps'] ?? [],
			\array_map( static fn ( string $appName ): array => $apps[$appName], $platform['apps'] ?? [] ),
		);
		$matchedPlatform['profiles'] = \array_combine(
			$platform['profiles'] ?? [],
			\array_map(
				static fn ( string $profileName ): array => $profiles[$profileName] + ['href' => "{$basePath}/profiles/new/{$profileName}/"],
				$platform['profiles'] ?? [],
			),
		);
	}
}

$baseUrl = $app->getBaseUrl();
$apiConfiguration = [
	'authorization_endpoint' => "{$baseUrl}oauth/authorize/",
	'token_endpoint' => "{$baseUrl}oauth/token/",
	'eapconfig_endpoint' => "{$baseUrl}profiles/new/?format=eap-config",
	'mobileconfig_endpoint' => "{$baseUrl}profiles/new/?format=apple-mobileconfig",
	'profile_info_endpoint' => "{$baseUrl}profiles/info/",
];

$app->render( [
	'platform' => $matchedPlatform,
	'provider' => $provider,
	'all_platforms_href' => "{$basePath}/app/",
	'advanced_href' => "{$basePath}/profiles/new/",
	'http://letswifi.app/api#v2' => $apiConfiguration,
], 'start', $basePath );
