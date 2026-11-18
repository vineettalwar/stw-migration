<?php
/** STW Migration, GPL-3.0-or-later. Modified 2026-11-18. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Shared filename policy for both formats; content verification happens separately. */
function stw_is_archive_name( $name ) {
	return is_string( $name )
		&& preg_match( '/^[^\\\\\/\x00-\x1f:]+\.(?:stwm|wpress)$/iD', $name ) === 1
		&& $name !== '.' && $name !== '..';
}

/** Reject traversal, stream wrappers, and symlinks before writing or deleting. */
function stw_assert_safe_path( $root, $relative ) {
	if ( ! is_string( $relative ) || $relative === '' || preg_match( '/[\\\\\x00-\x1f:]/', $relative ) || $relative[0] === '/' ) {
		throw new Ai1wm_Archive_Exception( 'Unsafe migration path.' );
	}
	$path = rtrim( $root, '/\\' );
	if ( is_link( $path ) ) {
		throw new Ai1wm_Archive_Exception( 'Symlink migration roots are not supported.' );
	}
	foreach ( explode( '/', $relative ) as $part ) {
		if ( $part === '' || $part === '.' || $part === '..' ) {
			throw new Ai1wm_Archive_Exception( 'Unsafe migration path component.' );
		}
		$path .= DIRECTORY_SEPARATOR . $part;
		if ( is_link( $path ) ) {
			throw new Ai1wm_Archive_Exception( 'Migration cannot follow symbolic links.' );
		}
	}
	return $path;
}

/** Rewrite serialized strings without instantiating archive-supplied PHP classes. */
function stw_rewrite_serialized( $input, $from, $to, $depth = 0 ) {
	if ( $depth > 64 ) {
		throw new Ai1wm_Import_Exception( 'Serialized data exceeds the supported nesting depth.' );
	}
	$output = '';
	$length = strlen( $input );
	for ( $offset = 0; $offset < $length; ) {
		if ( preg_match( '/\G(s|O|C|E):([0-9]+):"/', $input, $match, 0, $offset ) ) {
			$type  = $match[1];
			$size  = (int) $match[2];
			$start = $offset + strlen( $match[0] );
			if ( $size >= $length - $start || $input[ $start + $size ] !== '"' ) {
				throw new Ai1wm_Import_Exception( 'Invalid serialized string length.' );
			}
			$text   = substr( $input, $start, $size );
			$offset = $start + $size + 1;
			if ( $type === 's' ) {
				if ( is_serialized( $text ) ) {
					$text = stw_rewrite_serialized( $text, $from, $to, $depth + 1 );
				} else {
					$text = Ai1wm_Database_Utility::replace_values( $from, $to, $text );
				}
				$output .= 's:' . strlen( $text ) . ':"' . $text . '"';
			} else {
				// Class names and enum identities are never rewritten.
				$output .= $match[0] . $text . '"';
				if ( $type === 'C' ) {
					if ( ! preg_match( '/\G:([0-9]+):\{/', $input, $custom, 0, $offset ) ) {
						throw new Ai1wm_Import_Exception( 'Invalid custom serialized object.' );
					}
					$size  = (int) $custom[1];
					$start = $offset + strlen( $custom[0] );
					if ( $size >= $length - $start || $input[ $start + $size ] !== '}' ) {
						throw new Ai1wm_Import_Exception( 'Truncated custom serialized object.' );
					}
					$output .= $custom[0] . substr( $input, $start, $size ) . '}';
					$offset  = $start + $size + 1;
				}
			}
		} else {
			$output .= $input[ $offset++ ];
		}
	}
	return $output;
}

/** Fail before database replacement if archive metadata is missing or malformed. */
function stw_validate_package( $path ) {
	if ( ! is_file( $path ) || filesize( $path ) > 4 * 1024 * 1024 ) {
		throw new Ai1wm_Import_Exception( 'Missing or oversized archive metadata.' );
	}
	try {
		$package = json_decode( file_get_contents( $path ), true, 64, JSON_THROW_ON_ERROR );
	} catch ( Throwable $error ) {
		throw new Ai1wm_Import_Exception( 'Archive metadata is not valid JSON.' );
	}
	if (
		! is_array( $package )
		|| ! isset( $package['Plugin']['Version'], $package['WordPress']['Content'] )
		|| ! is_string( $package['Plugin']['Version'] )
		|| ! is_string( $package['WordPress']['Content'] )
	) {
		throw new Ai1wm_Import_Exception( 'Archive metadata is missing required fields.' );
	}
	foreach ( array( 'SiteURL', 'HomeURL' ) as $field ) {
		if (
			! isset( $package[ $field ] )
			|| ! is_string( $package[ $field ] )
			|| ! in_array( wp_parse_url( $package[ $field ], PHP_URL_SCHEME ), array( 'http', 'https' ), true )
			|| ! wp_parse_url( $package[ $field ], PHP_URL_HOST )
		) {
			throw new Ai1wm_Import_Exception( 'Archive metadata contains an invalid site URL.' );
		}
	}
	return $package;
}
