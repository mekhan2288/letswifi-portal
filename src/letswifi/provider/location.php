<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\provider;

use letswifi\configuration\Dictionary;

class Location
{
	public function __construct( public readonly float $lat, public readonly float $lon )
	{
	}

	public static function fromConfig( Dictionary $location ): self
	{
		$lat = $location->getFloat( 'lat' );
		$lon = $location->getFloat( 'lon' );

		return new self( lat: $lat, lon: $lon );
	}
}
