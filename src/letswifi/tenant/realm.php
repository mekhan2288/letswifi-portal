<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\tenant;

use DateInterval;
use DomainException;
use JsonSerializable;
use fyrkat\multilang\MultiLanguageString;
use fyrkat\openssl\X509;
use letswifi\configuration\Dictionary;

class Realm implements JsonSerializable
{
	/**
	 * @param array<string>  $serverNames
	 * @param array<X509>    $trust
	 * @param array<Network> $networks
	 */
	public function __construct(
		private readonly TenantConfig $tenantConfig,
		public readonly string $realmId,
		public readonly MultiLanguageString $displayName,
		public readonly array $serverNames,
		public readonly array $trust,
		public readonly DateInterval $validity,
		public readonly array $networks,
		public readonly ?MultiLanguageString $description = null,
		public readonly ?string $contactId = null,
	) {
	}

	public static function fromConfig( TenantConfig $tenantConfig, Dictionary $realmData ): self
	{
		return new self(
			tenantConfig: $tenantConfig,
			realmId: $realmData->getParentKey(),
			displayName: $realmData->getMultiLanguageString( 'display_name' ),
			serverNames: $realmData->getRawArray( 'server_names' ),
			trust: $tenantConfig->getCertificatesWithChain( ...$realmData->getRawArray( 'trust' ) ),
			validity: static::getValidity( $realmData->getInteger( 'validity' ) ),
			networks: $tenantConfig->getNetworks( ...$realmData->getRawArray( 'networks' ) ),
			description: $realmData->getMultiLanguageStringOrNull( 'description' ),
			contactId: $realmData->getString( 'contact' ),
		);
	}

	public function jsonSerialize(): array
	{
		return [
			'realmId' => $this->realmId,
			'displayName' => $this->displayName,
			'description' => $this->description,
			'serverNames' => $this->serverNames,
			'trust' => $this->trust,
			'validity' => $this->validity->format( 'P%dD' ),
			'contact' => $this->getContact(),
		];
	}

	public function getContact(): ?Contact
	{
		return null === $this->contactId ? null : $this->tenantConfig->getContact( $this->contactId );
	}

	protected static function getValidity( int|float|string|DateInterval $in ): DateInterval
	{
		if ( $in instanceof DateInterval ) {
			return $in;
		}
		if ( \is_float( $in ) ) {
			$in = (int)\round( $in );
		}
		if ( \is_int( $in ) && 0 < $in ) {
			return new DateInterval( "P{$in}D" );
		}
		if ( \is_string( $in ) ) {
			return DateInterval::createFromDateString( $in );
		}

		throw new DomainException( 'Invalid validity ' . $in );
	}
}
