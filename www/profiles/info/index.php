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
$profileInfo['displayName'] = $provider->displayName;
$profileInfo['description'] = $provider->description;
if ( null !== $profileInfo['logo'] ) {
	$vhost = \array_key_exists( 'HTTP_HOST', $_SERVER ) ? $_SERVER['HTTP_HOST'] : null;
	\assert( \is_string( $vhost ), 'HTTP_HOST should be string' );
	$issuer = "https://{$vhost}";
	$uri = \array_key_exists( 'REQUEST_URI', $_SERVER ) ? $_SERVER['REQUEST_URI'] : null;
	\assert( \is_string( $uri ), 'REQUEST_URI should be string' );
	$indexUri = \dirname( "{$uri}x" );
	$profileInfo['logo']->href = "{$issuer}{$indexUri}/logo.php";
}
$app->render(
	[
		'href' => "{$basePath}/profiles/info/",
		'http://letswifi.app/profile#v2' => $profileInfo,
	], null, $basePath );
