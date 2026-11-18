<?php
/**
 * Copyright (C) 2014-2018 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

class Ai1wm_Export_Download {

	public static function execute( $params ) {

		$lock = stw_lock_archive_stage( $params, 'export' );
		if ( ! $lock ) {
			usleep( 250000 );
			$params['completed'] = false;
			return $params;
		}
		try {
			// Set progress
			Ai1wm_Status::info( __( 'Renaming exported file...', AI1WM_PLUGIN_NAME ) );

			$destination = ai1wm_backup_path( $params );
			if ( ! is_file( $destination ) ) {
				$source = ai1wm_archive_path( $params );
				$reader = new Ai1wm_Extractor( $source );
				$complete = false;
				try {
					foreach ( $reader->entries() as $entry ) { /* Validate record boundaries, not trailing zero bytes alone. */ }
					$complete = true;
				} catch ( Ai1wm_Archive_Exception $error ) {
					$complete = false;
				}
				$reader->close();
				if ( ! $complete ) {
					$archive = new Ai1wm_Compressor( $source );
					$archive->close( true );
				}
				Ai1wm_Status::info( 'Building STW archive and file checksums…' );
				$temporary = stw_assert_safe_path( ai1wm_storage_path( $params ), 'export.partial' );
				@unlink( $temporary );
				Stw_Archive::create( $source, $temporary );
				if ( ! rename( $temporary, $destination ) ) {
					@unlink( $temporary );
					throw new Ai1wm_Archive_Exception( 'Cannot save the completed STW backup.' );
				}
			}

			$blog_id = null;

			// Get subsite Blog ID
			if ( isset( $params['options']['sites'] ) && ( $sites = $params['options']['sites'] ) ) {
				if ( count( $sites ) === 1 ) {
					$blog_id = array_shift( $sites );
				}
			}

			// Set archive details
			$link = esc_url( ai1wm_backup_url( $params ) );
			$size = esc_html( ai1wm_backup_size( $params ) );
			$name = esc_html( ai1wm_site_name( $blog_id ) );

			// Set progress
			Ai1wm_Status::download(
				sprintf(
					__(
						'<a href="%s" class="ai1wm-button-green ai1wm-emphasize">' .
						'<span>Download %s</span>' .
						'<em>Size: %s</em>' .
						'</a>',
						AI1WM_PLUGIN_NAME
					),
					$link,
					$name,
					$size
				)
			);

			unset( $params['completed'] );
		} finally {
			stw_release_database_lock( $lock );
		}

		return $params;
	}
}
