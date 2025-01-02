<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\format;

use InvalidArgumentException;
use fyrkat\multilang\Locale;
use fyrkat\multilang\MultiLanguageString;
use letswifi\credential\CertificateCredential;
use letswifi\credential\Credential;
use letswifi\provider\Contact;
use letswifi\provider\Location;
use letswifi\provider\Network;
use letswifi\provider\NetworkPasspoint;
use letswifi\provider\NetworkSSID;

class EapConfigFormat extends Format
{
	public function generate(): string
	{
		$contact = $this->credential->provider->getContact();
		$locales = \array_filter( \array_map( [$this, 'getLocale'], \array_filter( [
			$this->credential->realm->displayName,
			$this->credential->realm->description,
			$contact?->mail,
			$contact?->web,
			$contact?->phone,
		] ) ) );
		$locale = null;
		if ( \count( $locales ) > 0 ) {
			$locale = \reset( $locales )->is( ...$locales ) ? \reset( $locales ) : null;
		}
		$lang = null === $locale ? '' : "lang=\"{$locale}\" ";

		$result = '<?xml version="1.0" encoding="utf-8"?>';
		$result .= ''
			. "\r\n" . '<EAPIdentityProviderList xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="eap-metadata.xsd">'
			. "\r\n\t" . '<EAPIdentityProvider ID="' . $this->e( $this->credential->realm->realmId ) . "\" namespace=\"urn:RFC4282:realm\" {$lang}version=\"1\">";
		if ( null !== $expiry = $this->credential->getExpiry() ) {
			$result .= ''
				. "\r\n\t\t<ValidUntil>" . $this->e( \gmdate( 'Y-m-d\\TH:i:s\\Z', $expiry->getTimestamp() ) ) . '</ValidUntil>';
		}
		$result .= ''
			. "\r\n\t\t<AuthenticationMethods>";
		foreach ( [$this->credential] as $authentication ) {
			$result .= $this->generateAuthenticationMethodXml( $authentication );
		}
		$result .= ''
			. "\r\n\t\t</AuthenticationMethods>"
			. "\r\n\t\t<CredentialApplicability>";
		foreach ( $this->credential->realm->networks as $network ) {
			$result .= $this->generateNetworkXml( $network );
		}
		$result .= ''
			. "\r\n\t\t</CredentialApplicability>"
			. "\r\n\t\t<ProviderInfo>"
			. "\r\n\t\t\t<DisplayName>" . $this->e( $this->credential->realm->displayName ) . '</DisplayName>';
		if ( null !== $this->credential->realm->description ) {
			$result .= ''
				. "\r\n\t\t\t<Description>" . $this->e( $this->credential->realm->description ) . '</Description>';
		}
		if ( null !== $location = $this->credential->provider->getContact()?->location ) {
			$result .= $this->generateLocationXml( $location );
		}
		if ( null !== $logo = $this->credential->provider->getContact()?->logo ) {
			$result .= ''
				. "\r\n\t\t\t" . '<ProviderLogo mime="' . $this->e( $logo->contentType ) . '" encoding="base64">' . \base64_encode( $logo->getBytes() ) . '</ProviderLocation>';
		}
		/*
		if ( null !== $tos = $this->profileData->getTermsOfUse() ) {
			$result .= ''
				. "\r\n\t\t\t" . '<TermsOfUse>' . $this->e( $tos ) . '</TermsOfUse>';
		}
		 */
		if ( null !== $contact ) {
			$result .= $this->generateHelpdeskXml( $contact );
		}
		$result .= ''
			. "\r\n\t\t</ProviderInfo>"
			. "\r\n\t</EAPIdentityProvider>"
			. "\r\n</EAPIdentityProviderList>"
			. "\r\n";

		return $result;
	}

	public function getContentType(): string
	{
		// There is no reference to this, and no official registration,
		// but CAT uses application/eap-config.
		// Unregistered content types should use the x- prefix though.
		return 'application/x-eap-config';
	}

	public function getFileExtension(): string
	{
		return 'eap-config';
	}

	/**
	 * @suppress PhanPossiblyUndeclaredProperty Phan doesn't understand $obj->getFoo()?->prop
	 */
	private function getLocale( MultiLanguageString $s ): ?Locale
	{
		$locale = null;
		$this->translator->translate( $s, $locale );

		return $locale;
	}

	private function generateNetworkXml( Network $network ): string
	{
		if ( $network instanceof NetworkPasspoint ) {
			return $this->generateHS20NetworkXml( $network );
		}
		if ( $network instanceof NetworkSSID ) {
			return $this->generateSSIDNetworkXml( $network );
		}

		throw new InvalidArgumentException( 'Unsupported network: ' . $network::class );
	}

	private function generateHS20NetworkXml( NetworkPasspoint $network ): string
	{
		$result = ''
			. "\r\n\t\t\t<IEEE80211>";
		foreach ( $network->oids as $oid ) {
			$result .= ''
			. "\r\n\t\t\t\t<ConsortiumOID>" . $this->e( $oid ) . '</ConsortiumOID>';
		}
		$result .= ''
			. "\r\n\t\t\t</IEEE80211>";

		return $result;
	}

	private function generateSSIDNetworkXml( NetworkSSID $network ): string
	{
		return ''
			. "\r\n\t\t\t<IEEE80211>"
			. "\r\n\t\t\t\t<SSID>" . $this->e( $network->ssid ) . '</SSID>'
			. "\r\n\t\t\t\t<MinRSNProto>CCMP</MinRSNProto>"
			. "\r\n\t\t\t</IEEE80211>";
	}

	private function generateAuthenticationMethodXml( Credential $authenticationMethod ): string
	{
		if ( $authenticationMethod instanceof CertificateCredential ) {
			return $this->generateClientCertAuthenticationMethodXml( $authenticationMethod );
		}

		throw new InvalidArgumentException( 'Unsupported authentication method: ' . $authenticationMethod::class );
	}

	/**
	 * Generate EAP config data for EAP-TLS authentication
	 *
	 * @return string XML portion for wifi and certificates, to be used in a EAP config file
	 */
	private function generateClientCertAuthenticationMethodXml( CertificateCredential $authenticationMethod ): string
	{
		$identity = $authenticationMethod->getIdentity();
		\assert( $this->credential instanceof CertificateCredential );
		$pkcs12 = $this->credential->getPKCS12( ca: true, des: true );

		$defaultPassphrase = 'pkcs12';
		$result = '';
		$result .= ''
			. "\r\n\t\t\t<AuthenticationMethod>"
			. "\r\n\t\t\t\t<EAPMethod>"
			. "\r\n\t\t\t\t\t<Type>13</Type>"
			. "\r\n\t\t\t\t</EAPMethod>"
			. "\r\n\t\t\t\t<ServerSideCredential>";
		foreach ( $this->credential->realm->trust as $ca ) {
			$result .= ''
				. "\r\n\t\t\t\t\t" . '<CA format="X.509" encoding="base64">' . \base64_encode( $ca->getX509Der() ) . '</CA>';
		}
		foreach ( $this->credential->realm->serverNames as $serverName ) {
			$result .= ''
				. "\r\n\t\t\t\t\t<ServerID>" . $this->e( $serverName ) . '</ServerID>';
		}
		$result .= ''
			. "\r\n\t\t\t\t</ServerSideCredential>";
		$result .= ''
			. "\r\n\t\t\t\t<ClientSideCredential>";
		if ( null !== $identity ) {
			// https://github.com/GEANT/CAT/blob/v2.0.3/devices/xml/eap-metadata.xsd
			// The schema specifies <OuterIdentity>
			// https://tools.ietf.org/html/draft-winter-opsawg-eap-metadata-02
			// Expired draft specifices <AnonymousIdentity>
			// cat.eduroam.org uses <OuterIdentity>, so we do too
			$result .= ''
				. "\r\n\t\t\t\t\t<OuterIdentity>" . $this->e( $identity ) . '</OuterIdentity>';
		}
		$result .= ''
			. "\r\n\t\t\t\t" . '<ClientCertificate format="PKCS12" encoding="base64">' . \base64_encode( $pkcs12->getPKCS12Bytes( $this->passphrase ?: $defaultPassphrase ) ) . '</ClientCertificate>';
		if ( !$this->passphrase ) {
			$result .= ''
				. "\r\n\t\t\t\t<Passphrase>" . $this->e( $defaultPassphrase ) . '</Passphrase>';
		}
		$result .= ''
			. "\r\n\t\t\t\t</ClientSideCredential>";
		$result .= ''
				. "\r\n\t\t\t</AuthenticationMethod>";

		return $result;
	}

	private function generateHelpdeskXml( Contact $helpdesk ): string
	{
		$result = "\r\n\t\t\t<Helpdesk>";
		if ( null !== $helpdesk->mail ) {
			$result .= "\r\n\t\t\t\t<EmailAddress>" . $this->e( $helpdesk->mail ) . '</EmailAddress>';
		}
		if ( null !== $helpdesk->web ) {
			$result .= "\r\n\t\t\t\t<WebAddress>" . $this->e( $helpdesk->web ) . '</WebAddress>';
		}
		if ( null !== $helpdesk->phone ) {
			$result .= "\r\n\t\t\t\t<Phone>" . $this->e( $helpdesk->phone ) . '</Phone>';
		}
		$result .= "\r\n\t\t\t</Helpdesk>";

		return $result;
	}

	private static function generateLocationXml( Location $location ): string
	{
		return ''
			. "\r\n\t\t\t<ProviderLocation>"
			. "\r\n\t\t\t\t<Latitude>{$location->lat}</Latitude>"
			. "\r\n\t\t\t\t<Longitude>{$location->lon}</Longitude>"
			. "\r\n\t\t\t</ProviderLocation>";
	}
}
