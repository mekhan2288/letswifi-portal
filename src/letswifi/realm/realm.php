<?php declare(strict_types=1);

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: 2018-2020, Jørn Åne de Jong, Uninett AS <jorn.dejong@uninett.no>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\realm;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

use fyrkat\openssl\CSR;
use fyrkat\openssl\DN;
use fyrkat\openssl\OpenSSLConfig;
use fyrkat\openssl\PKCS12;
use fyrkat\openssl\PrivateKey;
use fyrkat\openssl\X509;

use letswifi\profile\auth\TlsAuth;

use letswifi\profile\EduroamProfileData;
use letswifi\profile\generator\Generator;

use letswifi\profile\IProfileData;

class Realm
{
	/** @var string */
	private $name;

	/** @var RealmManager */
	private $manager;

	/** @var ?array<string,string> */
	private $data;

	public function __construct( RealmManager $manager, string $name )
	{
		$this->manager = $manager;
		$this->name = $name;
	}

	/**
	 * @psalm-template T of Generator
	 *
	 * @psalm-param class-string<T> $generator The config generator to return
	 *
	 * @param string $generator The config generator class to return
	 * @param User   $user
	 * @psalm-suppress LessSpecificReturnStatement
	 * @psalm-suppress MoreSpecificReturnType
	 *
	 * @psalm-return T
	 */
	public function getConfigGenerator( string $generator, User $user, ?DateInterval $validity = null ): Generator
	{
		if ( null === $validity ) {
			$validity = $this->manager->getDefaultValidity( $this->name );
		}
		$expiry = (new DateTimeImmutable())->add( $validity );
		// TODO check that $expiry is not too far in the future,
		//	during some test we ended up with 88363-05-14 and MySQL didn't like
		// TODO more generic method to get an arbitrary generator
		$pkcs12 = $this->generateClientCertificate( $user, $expiry );
		$anonymousIdentity = $user->getAnonymousUsername();

		return new $generator( $this->getProfileData(), [$this->createAuthenticationMethod( $pkcs12, $anonymousIdentity )] );
	}

	/**
	 * @return array<X509>
	 */
	public function getTrustedCaCertificates(): array
	{
		/** @var array<X509> */
		$result = [];
		foreach ( $this->manager->getTrustedCas( $this->name ) as $ca ) {
			/** @var array<X509> */
			$subResult = [];
			do {
				$subResult[] = $ca->getX509();
				$ca = $ca->getIssuerCA();
			} while ( null !== $ca );
			// Reverse the certificates so we have the same order as CAT
			for ( $i = \count( $subResult ) - 1; $i >= 0; --$i ) {
				$result[] = $subResult[$i];
			}
		}

		return $result;
	}

	/**
	 * @param User              $requester  User requesting the certificate
	 * @param string            $commonName Common name of the server certificate
	 * @param DateTimeInterface $expiry     Expiry date
	 */
	public function generateServerCertificate( User $requester, string $commonName, DateTimeInterface $expiry ): PKCS12
	{
		$serverKey = new PrivateKey( new OpenSSLConfig( OpenSSLConfig::KEY_EC ) );
		$dn = new DN( ['CN' => $commonName] );
		$csr = CSR::generate( $dn, $serverKey );
		$caCert = $this->getSigningCACertificate();
		$serial = $this->logPreparedServerCredential( $caCert, $requester, $csr, $expiry );

		$caKey = $this->getSigningCAKey();
		$conf = new OpenSSLConfig( OpenSSLConfig::X509_SERVER );
		$serverCert = $csr->sign( $caCert, $caKey, $expiry, $conf, $serial );
		$this->logCompletedServerCredential( $requester, $serverCert );

		return new PKCS12( $serverCert, $serverKey, [$caCert] );
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	public function getTrustedServerNames(): array
	{
		return $this->manager->getServerNames( $this->name );
	}

	public function getProfileData(): IProfileData
	{
		// TODO add helpdesk info, logo and such
		return new EduroamProfileData( $this->getName() );
	}

	public function getSigningCACertificate(): X509
	{
		return $this->manager->getSignerCa( $this->name )->getX509();
	}

	public function getSecretKey(): string
	{
		return $this->manager->getCurrentOAuthKey( $this->name );
	}

	public function getSigningCAKey(): PrivateKey
	{
		return $this->manager->getSignerCa( $this->name )->getPrivateKey();
	}

	protected function createAuthenticationMethod( PKCS12 $pkcs12, string $anonymousIdentity ): TlsAuth
	{
		$caCertificates = $this->getTrustedCaCertificates();
		$serverNames = $this->getTrustedServerNames();
		$anonymousIdentity = \rawurldecode( \strstr( $anonymousIdentity, '@', true ) ?: $anonymousIdentity ) . '@' . \rawurldecode( $this->getName() );

		return new TlsAuth( $caCertificates, $serverNames, $anonymousIdentity, $pkcs12 );
	}

	protected function logPreparedUserCredential( X509 $caCert, User $requester, CSR $csr, DateTimeInterface $expiry ): int
	{
		return $this->manager->logPreparedCredential( $this->name, $caCert, $requester, $csr, $expiry, 'client' );
	}

	protected function logPreparedServerCredential( X509 $caCert, User $requester, CSR $csr, DateTimeInterface $expiry ): int
	{
		return $this->manager->logPreparedCredential( $this->name, $caCert, $requester, $csr, $expiry, 'server' );
	}

	protected function logCompletedUserCredential( User $user, X509 $userCert ): void
	{
		$this->manager->logCompletedCredential( $this->name, $user, $userCert, 'client' );
	}

	protected function logCompletedServerCredential( User $user, X509 $serverCert ): void
	{
		$this->manager->logCompletedCredential( $this->name, $user, $serverCert, 'server' );
	}

	protected function generateClientCertificate( User $user, DateTimeInterface $expiry ): PKCS12
	{
		$userKey = new PrivateKey( new OpenSSLConfig( OpenSSLConfig::KEY_EC ) );
		$commonName = static::createUUID() . '@' . \rawurlencode( $this->getName() );
		$dn = new DN( ['CN' => $commonName] );
		$csr = CSR::generate( $dn, $userKey );
		$caCert = $this->getSigningCACertificate();
		$serial = $this->logPreparedUserCredential( $caCert, $user, $csr, $expiry );

		$caKey = $this->getSigningCAKey();
		$conf = new OpenSSLConfig( OpenSSLConfig::X509_CLIENT );
		$userCert = $csr->sign( $caCert, $caKey, $expiry, $conf, $serial );
		$this->logCompletedUserCredential( $user, $userCert );

		return new PKCS12( $userCert, $userKey, [$caCert] );
	}

	private static function createUUID(): string
	{
		$bytes = \random_bytes( 16 );
		$bytes[6] = \chr( \ord( $bytes[6] ) & 0x0F | 0x40 );
		$bytes[8] = \chr( \ord( $bytes[8] ) & 0x3F | 0x40 );

		return \bin2hex( $bytes[0] . $bytes[1] . $bytes[2] . $bytes[3] )
			. '-' . \bin2hex( $bytes[4] . $bytes[5] )
			. '-' . \bin2hex( $bytes[6] . $bytes[7] )
			. '-' . \bin2hex( $bytes[8] . $bytes[9] )
			. '-' . \bin2hex( $bytes[10] . $bytes[11] . $bytes[12] . $bytes[13] . $bytes[14] . $bytes[15] )
			;
	}
}
