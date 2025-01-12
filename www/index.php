<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

use letswifi\LetsWifiApp;

require \implode( \DIRECTORY_SEPARATOR, [\dirname( __DIR__, 1 ), 'src', '_autoload.php'] );
$basePath = '.';

$app = new LetsWifiApp( basePath: $basePath );
$app->registerExceptionHandler();

$baseUrl = $app->getBaseUrl();
$apiConfiguration = [
	'authorization_endpoint' => "{$baseUrl}oauth/authorize/",
	'token_endpoint' => "{$baseUrl}oauth/token/",
	'eapconfig_endpoint' => "{$baseUrl}profiles/new/?format=eap-config",
	'mobileconfig_endpoint' => "{$baseUrl}profiles/new/?format=apple-mobileconfig",
	'profile_info_endpoint' => "{$baseUrl}profiles/info/",
];

$app->render( [
	'http://letswifi.app/api#v2' => $apiConfiguration,
], 'info', $basePath );
