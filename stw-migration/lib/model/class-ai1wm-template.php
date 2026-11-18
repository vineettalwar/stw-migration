<?php
// STW Migration modified 2026-11-18. See CHANGELOG.md.
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

class Ai1wm_Template extends Bandar {

	/**
	 * Renders a file and returns its contents
	 *
	 * @param  string $file View to render
	 * @param  array  $args Set of arguments
	 *
	 * @return string       Rendered view
	 */
	public static function render( $view, $args = array(), $path = false ) {
		parent::render( $view, $args, $path );
	}

	/**
	 * Returns link to an asset file
	 *
	 * @param  string $asset Asset file
	 *
	 * @return string        Asset URL
	 */
	public static function asset_link( $asset, $prefix = 'AI1WM' ) {
		$version = $prefix === 'AI1WM' ? STW_MIGRATION_VERSION : constant( $prefix . '_VERSION' );
		$file = AI1WM_TEMPLATES_PATH . '/assets/' . $asset;
		if ( $prefix === 'AI1WM' && is_file( $file ) ) {
			$version .= '.' . filemtime( $file );
		}
		return constant( $prefix . '_URL' ) . '/lib/view/assets/' . $asset . '?v=' . rawurlencode( $version );
	}

	public static function get_content( $template, $args = array(), $path = false ) {
		return parent::getTemplateContent( $template, $args, $path );
	}
}
