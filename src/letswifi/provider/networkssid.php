<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

use fyrkat\multilang\MultiLanguageString;
use letswifi\Config;

class NetworkSSID extends Network
{
	public function __construct(
		string $networkId,
		MultiLanguageString $displayName,
		public readonly string $ssid )
	{
		parent::__construct( networkId: $networkId, displayName: $displayName );
	}

	public static function fromConfig( Config $networkConfig ): self
	{
		return new self(
			networkId: $networkConfig->getParentKey(),
			displayName: $networkConfig->getMultiLanguageString( 'display_name' ),
			ssid: $networkConfig->getString( 'ssid' ),
		);
	}
}
