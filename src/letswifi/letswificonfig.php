<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi;

class LetsWifiConfig
{
	public function __construct( protected readonly configuration\Dictionary $config )
	{
	}

	public function getProviderData( string $httpHost ): configuration\Dictionary
	{
		$providers = $this->config->getDictionary( 'provider' );

		return $providers->getDictionaryOrNull( $httpHost ) ?? $providers->getDictionary( '*' );
	}

	public function getContactData( string $contactId ): configuration\Dictionary
	{
		return $this->config->getDictionary( 'contact' )->getDictionary( "{$contactId}" );
	}

	public function getRealmData( string $realmId ): configuration\Dictionary
	{
		return $this->config->getDictionary( 'realm' )->getDictionary( $realmId );
	}

	public function getCertificateData( string $sub ): configuration\Dictionary
	{
		return $this->config->getDictionary( 'certificate' )->getDictionary( $sub );
	}

	public function getNetworkData( string $network ): configuration\Dictionary
	{
		return $this->config->getDictionary( 'network' )->getDictionary( $network );
	}
}
