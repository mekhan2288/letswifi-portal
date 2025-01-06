<?php declare( strict_types=1 );

/*
 * This file is part of letswifi; a system for easy eduroam device enrollment
 *
 * Copyright: Jørn Åne de Jong <jorn.dejong@letswifi.eu>
 * Copyright: Paul Dekkers, SURF <paul.dekkers@surf.nl>
 * SPDX-License-Identifier: BSD-3-Clause
 */

namespace letswifi\configuration;

use ArrayAccess;
use OutOfBoundsException;
use fyrkat\multilang\MultiLanguageString;

/**
 * @implements \ArrayAccess<string,mixed>
 */
class Dictionary implements ArrayAccess
{
	public const KEY_SEPARATOR = '->';

	/** @var array<string> */
	protected array $parentKeys = [];

	/**
	 * @param array<string,mixed> $data PHP file
	 */
	public function __construct( protected array $data )
	{
	}

	/** Prevent leaking data via var_dump*/
	public function __debugInfo(): array
	{
		return [];
	}

	public function getParentKey(): string
	{
		return \end( $this->parentKeys ) ?: throw new OutOfBoundsException( 'This object is the root configuration node' );
	}

	public function getConfigPath( ?string $offset = null ): string
	{
		return \implode( self::KEY_SEPARATOR, \array_filter( $this->parentKeys + [\PHP_INT_MAX => $offset] ) );
	}

	final public function has( string $key ): bool
	{
		return $this->offsetExists( $key );
	}

	public function offsetExists( mixed $offset ): bool
	{
		return \array_key_exists( $offset, $this->data ) && null !== $this->data[$offset];
	}

	public function offsetGet( mixed $offset ): mixed
	{
		return $this->data[$offset];
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		throw new ConfigurationException( $this->getConfigPath( $offset ) . ': Configuration is read-only' );
	}

	public function offsetUnset( mixed $offset ): void
	{
		throw new ConfigurationException( $this->getConfigPath( $offset ) . ': Configuration is read-only' );
	}

	public function getDictionaryOrNull( string $key ): ?self
	{
		return $this->has( $key ) ? $this->getDictionary( $key ) : null;
	}

	public function getDictionary( string $key ): self
	{
		$result = $this->get( $key, self::class );
		\assert( $result instanceof self );
		$result->parentKeys[] = $key;

		return $result;
	}

	public function getString( string $key ): string
	{
		return $this->get( $key, '' );
	}

	public function getMultiLanguageString( string $key ): MultiLanguageString
	{
		\class_exists( MultiLanguageString::class, true ); // Triggers autoloader
		$result = $this->get( $key, MultiLanguageString::class );
		\assert( $result instanceof MultiLanguageString );

		return $result;
	}

	public function getMultiLanguageStringOrNull( string $key ): ?MultiLanguageString
	{
		return $this->has( $key ) ? $this->getMultiLanguageString( $key ) : null;
	}

	public function getStringOrNull( string $key ): ?string
	{
		return $this->has( $key ) ? $this->getString( $key ) : null;
	}

	public function getRawArray( string $key ): array
	{
		return $this->get( $key, [] );
	}

	public function getInteger( string $key ): int
	{
		return $this->get( $key, 0 );
	}

	public function getFloat( string $key ): float
	{
		return $this->get( $key, 0.0 );
	}

	/**
	 * @template T
	 *
	 * @param                   $key The key to retrieve the value for
	 * @param class-string<T>|T $t   Type of the expected return value, either an object, classname, or falsey scalar or array (e.g. `''`, `[]`, `0`, `0.0`, `false`)
	 *
	 * @psalm-suppress InvalidReturnType
	 *
	 * @return T
	 */
	protected function get( string $key, mixed $t ): mixed
	{
		$result = $this->has( $key ) ? $this->offsetGet( $key ) : null;

		/** @psalm-suppress InvalidReturnStatement */
		if ( !$t ) { // type is falsey so we return if result is of same type
			if ( \is_string( $t ) && \is_string( $result ) ) {
				return $result;
			}
			if ( \is_array( $t ) && \is_array( $result ) ) {
				return $result;
			}
			if ( \is_int( $t ) && \is_int( $result ) ) {
				return $result;
			}
			if ( \is_float( $t ) && ( \is_float( $result ) || \is_int( $result ) ) ) {
				return (float)$result;
			}
			if ( \is_bool( $t ) && \is_bool( $result ) ) {
				return $result;
			}

			throw new ConfigurationException( $this->getConfigPath( $key ) . ': Expected value of type ' . \gettype( $t ) . ' but got ' . \gettype( $result ) );
		}
		$class = null;
		if ( \is_object( $t ) ) {
			$class = $t::class;
		} elseif ( !\is_string( $t ) ) {
			throw new ConfigurationException( $this->getConfigPath( $key ) . ': $t must either be object, class or falsey scalar or array, got truthy ' . \gettype( $t ) );
		}
		if ( null === $class && \class_exists( $t, false ) ) {
			$class = $t;
		}
		if ( null === $class ) {
			throw new ConfigurationException( $this->getConfigPath( $key ) . ': $t must either be object, class or falsey scalar or array, got ' . \var_export( $t, true ) );
		}

		/** @psalm-suppress InvalidReturnStatement */
		return new $class( $result );
	}
}
