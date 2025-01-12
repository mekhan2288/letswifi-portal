<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 3 ), 'src', '_autoload.php'] );
$basePath = '../..';

$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();
$provider = $app->getProvider();
$profileInfo = (array)$provider->getContact();
$profileInfo['display_name'] = $provider->displayName;
$profileInfo['description'] = $provider->description;
if ( null !== $profileInfo['logo'] ) {
	$vhost = \array_key_exists( 'HTTP_HOST', $_SERVER ) ? $_SERVER['HTTP_HOST'] : null;
	$path = \strstr( $_SERVER['REQUEST_URI'] ?? '', '?', true ) ?: $_SERVER['REQUEST_URI'] ?? '';
	$issuer = \is_string( $vhost ) ? "https://{$vhost}{$path}" : null;
	$indexUri = \dirname( "{$issuer}x" );

	// Override the logo object with an URL to the logo
	unset( $profileInfo['logo'] );
	$profileInfo['logo_endpoint'] = "{$indexUri}/logo.php";
	\ksort( $profileInfo );
}
$app->render(
	[
		'href' => "{$basePath}/profiles/info/",
		'http://letswifi.app/profile#v2' => $profileInfo,
	], null, $basePath );
