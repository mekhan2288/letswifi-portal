<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\auth;

use DateTimeImmutable;
use DomainException;
use JsonSerializable;
use PDO;
use fyrkat\oauth\Client;
use fyrkat\oauth\OAuth;
use fyrkat\oauth\exception\BearerException;
use fyrkat\oauth\sealer\JWTSealer;
use fyrkat\oauth\sealer\PDOSealer;
use fyrkat\oauth\token\AccessToken;
use fyrkat\oauth\token\AuthorizationCode;
use fyrkat\oauth\token\Grant;
use fyrkat\oauth\token\RefreshToken;
use letswifi\auth\browser\BrowserAuthInterface;
use letswifi\configuration\Dictionary;
use letswifi\tenant\Provider;
use letswifi\tenant\Realm;

class AuthenticationContext implements JsonSerializable
{
	public readonly BrowserAuthInterface $browserAuth;

	public readonly OAuth $oauth;

	/**
	 * @param array<string,mixed>                                                                                               $authServiceParams
	 * @param array<array{clientId:string,redirectUris?:array<string>,scopes:array<string>,refresh?:bool,clientSecret?:string}> $oauthClients
	 */
	public function __construct(
		public readonly string $authService,
		array $authServiceParams,
		string $oauthSecret,
		array $oauthClients,
		Dictionary $pdoData,
		protected readonly DateTimeImmutable $now = new DateTimeImmutable(),
	) {
		if ( !\preg_match( '/^[A-Z][A-Za-z0-9]+$/', $authService ) ) {
			throw new DomainException( 'Illegal auth.service specified in config' );
		}
		$authService = "letswifi\\auth\\browser\\{$authService}";
		$browserAuth = new $authService( ...$authServiceParams );
		\assert( $browserAuth instanceof BrowserAuthInterface );
		$this->browserAuth = $browserAuth;

		$pdo = new PDO(
			$pdoData->getString( 'dsn' ),
			$pdoData->getStringOrNull( 'username' ),
			$pdoData->getStringOrNull( 'password' ),
		);
		$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		if ( \strlen( $oauthSecret ) === 44 || \strlen( $oauthSecret ) === 43 ) {
			$oauthSecret = \base64_decode( \strtr( $oauthSecret, '_-', '/+' ), true );
		}
		$accessTokenSealer = new JWTSealer( AccessToken::class, $oauthSecret );
		$authorizationCodeSealer = new JWTSealer( AuthorizationCode::class, $oauthSecret );
		$refreshTokenSealer = new PDOSealer( RefreshToken::class, $pdo );

		/** @psalm-suppress ArgumentTypeCoercion */
		$this->oauth = new OAuth(
			$accessTokenSealer,
			$authorizationCodeSealer,
			$refreshTokenSealer,
		);

		foreach ( $oauthClients as $client ) {
			$this->oauth->registerClient( new Client(
				$client['clientId'],
				$client['redirectUris'] ?? [],
				$client['scopes'],
				$client['refresh'] ?? false,
				$client['clientSecret'] ?? null,
			) );
		}
	}

	public function jsonSerialize(): array
	{
		return ['authService' => $this->authService, 'now' => $this->now];
	}

	public function getAuthenticatedUser( Provider $provider, ?string $scope = null, bool $force = false ): ?User
	{
		if ( null !== $scope ) {
			try {
				$token = $this->oauth->getAccessTokenFromRequest( $scope );
				$grant = $token->getGrant();

				return $this->getAuthenticatedUserFromGrant( $provider, $grant );
			} catch ( BearerException $e ) {
				return $force ? throw $e : null;
			}
		} else {
			$userId = $force ? $this->browserAuth->getUserId() : $this->browserAuth->requireAuth();

			return null !== $userId ? $this->constructAuthenticatedUser( $provider, $userId ) : null;
		}

		return null;
	}

	public function requireAuth( Provider $provider, ?string $scope = null ): User
	{
		if ( $user = $this->getAuthenticatedUser( provider: $provider, scope: $scope, force: true ) ) {
			return $user;
		}

		throw new DomainException( 'Exhausted all available authentication methods without success' );
	}

	private function getAuthenticatedUserFromGrant( Provider $provider, Grant $grant ): User
	{
		$sub = $grant->getSub();
		if ( null === $grant->realm ) {
			throw new DomainException( "User {$sub} has no realm" );
		}

		$realm = $provider->getRealm( $grant->realm );
		if ( null === $realm ) {
			throw new DomainException( "Realm {$grant->realm} is not available at this provider" );
		}
		$affiliations = \explode( ',', $grant->__get( 'affiliations' ) ?? '' );

		return $this->constructAuthenticatedUser(
			provider: $provider,
			userId: $sub,
			clientId: $grant->clientId,
			affiliations: $affiliations,
			realm: $realm,
		);
	}

	private function constructAuthenticatedUser( Provider $provider, string $userId, ?string $clientId = null, ?array $affiliations = null, ?Realm $realm = null ): User
	{
		return new User(
			userId: $userId,
			realms: null === $realm
				? $provider->getRealmsByAffiliations( $this->browserAuth->getAffiliations() )
				: [$realm->realmId => $realm],
			affiliations: $affiliations ?? $this->browserAuth->getAffiliations(),
			clientId: $clientId,
			ip: $_SERVER['REMOTE_ADDR'] ?? null,
			userAgent: $_SERVER['HTTP_USER_AGENT'] ?? null,
		);
	}
}
