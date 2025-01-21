<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2022, Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: 2020-2022, Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

require \implode(\DIRECTORY_SEPARATOR, [\dirname(__DIR__, 1), 'src', '_autoload.php']);
$basePath = '.';

$app = new letswifi\LetsWifiApp();
$app->registerExceptionHandler();

$vhost = \array_key_exists( 'HTTP_HOST', $_SERVER ) ? $_SERVER['HTTP_HOST'] : null;
$path = \strstr( $_SERVER['REQUEST_URI'] ?? '', '?', true ) ?: $_SERVER['REQUEST_URI'] ?? '';
$issuer = \is_string( $vhost ) ? "https://${vhost}${path}" : null;
$apiConfiguration = \is_string( $issuer ) ? [
	'authorization_endpoint' => "${issuer}oauth/authorize/",
	'token_endpoint' => "${issuer}oauth/token/",
	'eapconfig_endpoint' => "${issuer}api/eap-config/",
	'mobileconfig_endpoint' => "${issuer}api/eap-config/?format=mobileconfig",
] : null;

$app->render( [
	'href' => "${basePath}/",
	'http://letswifi.app/api#v2' => $apiConfiguration,
	'apps' => [
		'android' => [
			'url' => 'https://play.google.com/store/apps/details?id=app.eduroam.geteduroam',
			'name' => 'Android',
		],
		'ios' => [
			'url' => 'https://apps.apple.com/app/geteduroam/id1504076137',
			'name' => 'iOS',
		],
		'windows' => [
			'url' => 'https://dl.eduroam.app/windows/x86_64/geteduroam.exe',
			'name' => 'Windows',
		],
		'huawei' => [
			'url' => 'https://appgallery.huawei.com/app/C104231893',
			'name' => 'Huawei',
		],
	],
	'os_config' => [
		'mobileconfig' => [
			'url' => "${basePath}/profiles/mac/",
			'name' => 'macOS',
		],
		'onc' => [
			'url' => "${basePath}/profiles/onc/",
			'name' => 'ChromeOS',
		],
	],
	'manual' => [
		'url' => "${basePath}/profiles/new/",
	],
], 'app', $basePath );
