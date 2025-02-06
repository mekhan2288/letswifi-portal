<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\tenant;

use RuntimeException;

class AppConfigLoader
{
	private array $configData = [];

	public function __construct( string $filePath )
	{
		$this->loadConfigFile( $filePath );
	}

	public function getConfigData(): array
	{
		return $this->configData;
	}

	private function loadConfigFile ( string $filePath ): void
	{
		if ( !\file_exists( $filePath ) ) {
			throw new RuntimeException( "Configuration file not found: {$filePath}" );
		}

		$config = require $filePath;

		if ( !\is_array( $config ) ) {
			throw new RuntimeException( 'Config file must return an array' );
		}
		$this->configData = $config;
	}
}
