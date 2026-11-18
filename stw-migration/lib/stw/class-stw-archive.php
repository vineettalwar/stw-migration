<?php
/** STW archive format v1: ZIP64, a versioned manifest, and SHA-256 entries. GPL-3.0-or-later. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stw_Archive {
	const FORMAT             = 'stw-migration';
	const VERSION            = 1;
	const MANIFEST           = 'manifest.json';
	const MAX_MANIFEST_BYTES = 16 * 1024 * 1024;
	const MAX_ENTRIES        = 100000;
	const MAX_ENTRY_BYTES    = 99999999999999;

	/** Package the tested pipeline's output without extracting a second copy of every file. */
	public static function create( $legacy_path, $destination ) {
		$zip    = self::open( $destination, true );
		$reader = null;
		$input  = null;
		try {
			$reader = new Ai1wm_Extractor( $legacy_path );
			$input  = fopen( $legacy_path, 'rb' );
			if ( ! $input ) {
				throw new Ai1wm_Archive_Exception( 'Cannot read the exported archive.' );
			}
			$entries = array();
			$seen    = array();
			foreach ( $reader->entries() as $entry ) {
				$path = 'site/' . str_replace( DIRECTORY_SEPARATOR, '/', $entry['filename'] );
				self::validate_path( $path );
				if ( isset( $seen[ $path ] ) || count( $entries ) >= self::MAX_ENTRIES ) {
					throw new Ai1wm_Archive_Exception( 'Duplicate entry or too many files for STW format v1.' );
				}
				$seen[ $path ] = true;
				if ( fseek( $input, $entry['offset'] ) !== 0 ) {
					throw new Ai1wm_Archive_Exception( 'Cannot seek to exported file data.' );
				}
				$hash = hash_init( 'sha256' );
				if ( hash_update_stream( $hash, $input, $entry['size'] ) !== $entry['size'] ) {
					throw new Ai1wm_Archive_Exception( 'Incomplete exported file data.' );
				}
				$added = $entry['size'] === 0
					? $zip->addFromString( $path, '' )
					: $zip->addFile( $legacy_path, $path, $entry['offset'], $entry['size'] );
				if ( ! $added || ! $zip->setCompressionName( $path, ZipArchive::CM_STORE ) ) {
					throw new Ai1wm_Archive_Exception( 'Cannot add a file to the STW archive.' );
				}
				$zip->setMtimeName( $path, $entry['mtime'] );
				$entries[] = array(
					'path'   => $path,
					'size'   => $entry['size'],
					'mtime'  => $entry['mtime'],
					'sha256' => hash_final( $hash ),
				);
			}
			$manifest = wp_json_encode(
				array(
					'format'     => self::FORMAT,
					'version'    => self::VERSION,
					'created_at' => gmdate( 'c' ),
					'generator'  => 'STW Migration ' . STW_MIGRATION_VERSION,
					'entries'    => $entries,
				),
				JSON_UNESCAPED_SLASHES
			);
			if ( ! is_string( $manifest ) || strlen( $manifest ) > self::MAX_MANIFEST_BYTES || ! $zip->addFromString( self::MANIFEST, $manifest ) ) {
				throw new Ai1wm_Archive_Exception( 'Cannot write STW archive manifest.' );
			}
			$required = strlen( $manifest ) + 4096;
			foreach ( $entries as $entry ) {
				$required += $entry['size'] + 2 * ( strlen( $entry['path'] ) + 128 );
			}
			self::require_disk_space( dirname( $destination ), $required );
			if ( ! $zip->close() ) {
				throw new Ai1wm_Archive_Exception( 'Cannot finalize STW archive. Check available disk space.' );
			}
		} catch ( Throwable $error ) {
			// Closing a failed libzip writer may itself fail; preserve the original error.
			try {
				$zip->close();
			} catch ( Throwable $ignored ) {
				// The writer may already be closed after a finalization failure.
			}
			@unlink( $destination );
			throw $error;
		} finally {
			if ( is_resource( $input ) ) {
				fclose( $input );
			}
			if ( $reader ) {
				$reader->close();
			}
		}
	}

	/** Verify every byte before handing a temporary stream to the legacy restore pipeline. */
	public static function to_legacy( $source, $destination ) {
		$zip     = self::open( $source, false );
		$output  = null;
		$created = false;
		try {
			$manifest = self::read_manifest( $zip );
			$required = 4377;
			foreach ( $manifest['entries'] as $entry ) {
				if ( $entry['size'] > PHP_INT_MAX - $required - 4377 ) {
					throw new Ai1wm_Archive_Exception( 'STW archive is too large for this PHP runtime.' );
				}
				$required += $entry['size'] + 4377;
			}
			self::require_disk_space( dirname( $destination ), $required );
			$output = @fopen( $destination, 'xb' );
			if ( ! $output ) {
				throw new Ai1wm_Archive_Exception( 'Cannot create verified restore staging file.' );
			}
			$created = true;
			foreach ( $manifest['entries'] as $entry ) {
				$relative = substr( $entry['path'], strlen( 'site/' ) );
				$header   = pack( 'a255a14a12a4096', basename( $relative ), $entry['size'], $entry['mtime'], dirname( $relative ) );
				ai1wm_write( $output, $header );
				$stream = $zip->getStream( $entry['path'] );
				if ( ! is_resource( $stream ) ) {
					throw new Ai1wm_Archive_Exception( 'Cannot read STW archive entry.' );
				}
				try {
					$hash      = hash_init( 'sha256' );
					$remaining = $entry['size'];
					while ( $remaining > 0 ) {
						$chunk = fread( $stream, min( 1024 * 1024, $remaining ) );
						if ( $chunk === false || $chunk === '' ) {
							throw new Ai1wm_Archive_Exception( 'Truncated STW archive entry.' );
						}
						hash_update( $hash, $chunk );
						ai1wm_write( $output, $chunk );
						$remaining -= strlen( $chunk );
					}
					if ( fread( $stream, 1 ) !== '' || ! hash_equals( $entry['sha256'], hash_final( $hash ) ) ) {
						throw new Ai1wm_Archive_Exception( 'STW archive integrity check failed.' );
					}
				} finally {
					fclose( $stream );
				}
			}
			ai1wm_write( $output, pack( 'a4377', '' ) );
		} catch ( Throwable $error ) {
			if ( is_resource( $output ) ) {
				fclose( $output );
				$output = null; }
			if ( $created ) {
				@unlink( $destination ); }
			throw $error;
		} finally {
			if ( is_resource( $output ) ) {
				fclose( $output ); }
			$zip->close();
		}
	}

	public static function prepare_import( $params ) {
		if ( strtolower( pathinfo( $params['archive'], PATHINFO_EXTENSION ) ) !== 'stwm' ) {
			return $params;
		}
		$lock = stw_lock_archive_stage( $params, 'import' );
		if ( ! $lock ) {
			usleep( 250000 );
			$params['completed'] = false;
			return $params;
		}
		try {
			$directory = ai1wm_storage_path( $params );
			$verified  = $directory . '/verified.wpress';
			stw_assert_safe_path( $directory, 'verified.wpress' );
			if ( ! is_file( $verified ) ) {
				$source = empty( $params['ai1wm_manual_restore'] ) ? $directory . '/' . $params['archive'] : ai1wm_backup_path( $params );
				Ai1wm_Status::info( 'Verifying STW archive integrity…' );
				$temporary = $directory . '/verified.partial';
				stw_assert_safe_path( $directory, 'verified.partial' );
				@unlink( $temporary );
				self::to_legacy( $source, $temporary );
				if ( ! rename( $temporary, $verified ) ) {
					throw new Ai1wm_Archive_Exception( 'Cannot finalize verified restore staging.' );
				}
			}
			unset( $params['completed'] );
		} finally {
			stw_release_database_lock( $lock );
		}
		return $params;
	}

	private static function require_disk_space( $directory, $bytes ) {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Some hosts disable disk probes; false is handled below.
		$free = @disk_free_space( $directory );
		if ( $free !== false && $free < $bytes + 1024 * 1024 ) {
			throw new Ai1wm_Archive_Exception( 'Not enough free disk space to stage this STW archive.' );
		}
	}

	public static function assert_supported() {
		if ( PHP_INT_SIZE < 8 ) {
			throw new Ai1wm_Archive_Exception( 'STW format v1 requires 64-bit PHP.' );
		}
		if ( ! class_exists( 'ZipArchive' ) ) {
			throw new Ai1wm_Archive_Exception( 'STW archives require the PHP ZIP extension. Enable it on your server.' );
		}
	}

	private static function open( $path, $write ) {
		self::assert_supported();
		$zip = new ZipArchive();
		if ( $zip->open( $path, $write ? ZipArchive::CREATE | ZipArchive::EXCL : ZipArchive::RDONLY ) !== true ) {
			throw new Ai1wm_Archive_Exception( 'Cannot open STW archive as a ZIP container.' );
		}
		return $zip;
	}

	private static function validate_path( $path ) {
		if (
			! is_string( $path )
			|| preg_match( '//u', $path ) !== 1
			|| ! str_starts_with( $path, 'site/' )
			|| strlen( basename( $path ) ) > 255
			|| strlen( dirname( substr( $path, 5 ) ) ) > 4096
		) {
			throw new Ai1wm_Archive_Exception( 'Invalid STW archive entry path.' );
		}
		// The destination does not exist: this is lexical validation only.
		stw_assert_safe_path( '/__stw_archive_validation__', $path );
	}

	private static function read_manifest( ZipArchive $zip ) {
		$stat = $zip->statName( self::MANIFEST );
		if ( ! $stat || $stat['size'] > self::MAX_MANIFEST_BYTES ) {
			throw new Ai1wm_Archive_Exception( 'Missing or oversized STW manifest.' );
		}
		try {
			$manifest = json_decode( $zip->getFromName( self::MANIFEST ), true, 64, JSON_THROW_ON_ERROR );
		} catch ( Throwable $error ) {
			throw new Ai1wm_Archive_Exception( 'Invalid STW manifest JSON.' );
		}
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native ZipArchive API.
		$file_count = $zip->numFiles;
		if (
			! is_array( $manifest )
			|| ( $manifest['format'] ?? null ) !== self::FORMAT
			|| ( $manifest['version'] ?? null ) !== self::VERSION
			|| ! isset( $manifest['entries'] )
			|| ! is_array( $manifest['entries'] )
			|| ! array_is_list( $manifest['entries'] )
			|| count( $manifest['entries'] ) > self::MAX_ENTRIES
			|| $file_count !== count( $manifest['entries'] ) + 1
		) {
			throw new Ai1wm_Archive_Exception( 'Unsupported STW format version or invalid entry inventory.' );
		}
		$zip_names = array();
		for ( $index = 0; $index < $file_count; $index++ ) {
			$name = $zip->getNameIndex( $index );
			if ( $name === false || isset( $zip_names[ $name ] ) ) {
				throw new Ai1wm_Archive_Exception( 'Duplicate or unreadable ZIP entry.' );
			}
			$zip_names[ $name ] = true;
		}
		$seen = array();
		foreach ( $manifest['entries'] as $entry ) {
			if (
				! is_array( $entry )
				|| ! isset( $entry['path'], $entry['size'], $entry['mtime'], $entry['sha256'] )
				|| ! is_int( $entry['size'] )
				|| $entry['size'] < 0
				|| $entry['size'] > self::MAX_ENTRY_BYTES
				|| ! is_int( $entry['mtime'] )
				|| $entry['mtime'] < 0
				|| $entry['mtime'] > 999999999999
				|| ! is_string( $entry['sha256'] )
				|| ! preg_match( '/^[a-f0-9]{64}$/D', $entry['sha256'] )
			) {
				throw new Ai1wm_Archive_Exception( 'Invalid STW manifest entry.' );
			}
			self::validate_path( $entry['path'] );
			if ( isset( $seen[ $entry['path'] ] ) ) {
				throw new Ai1wm_Archive_Exception( 'Duplicate STW manifest entry.' );
			}
			$seen[ $entry['path'] ] = true;
			$index                  = $zip->locateName( $entry['path'] );
			$stat                   = $index === false ? false : $zip->statIndex( $index );
			$opsys                  = 0;
			$attributes             = 0;
			if (
				! $stat
				|| $stat['size'] !== $entry['size']
				|| $stat['comp_method'] !== ZipArchive::CM_STORE
				|| ! $zip->getExternalAttributesIndex( $index, $opsys, $attributes )
				|| ( ( $attributes >> 16 ) & 0170000 ) === 0120000
				|| ( $stat['encryption_method'] ?? 0 ) !== 0
			) {
				throw new Ai1wm_Archive_Exception( 'STW manifest does not match its ZIP entries.' );
			}
		}
		return $manifest;
	}
}
