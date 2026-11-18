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
?>

<?php include AI1WM_TEMPLATES_PATH . '/common/stw-header.php'; ?>
<div class="ai1wm-container">
	<div class="ai1wm-row">
		<div class="ai1wm-left">
			<div class="ai1wm-holder">
				<h2 class="stw-panel-title">Upload backup</h2>


				<div class="stw-warning"><strong>This replaces the current site.</strong> Content, users, and settings will come from your backup. Keep a separate copy of this site before continuing. You’ll confirm before restoration begins.</div>
				<form action="" method="post" id="ai1wm-import-form" class="ai1wm-clear" enctype="multipart/form-data">

					<p>
						<?php esc_html_e( 'Choose an STW backup or a compatible legacy .wpress file.', AI1WM_PLUGIN_NAME ); ?><br />
					</p>

					<?php do_action( 'ai1wm_import_left_options' ); ?>

					<?php include AI1WM_TEMPLATES_PATH . '/import/import-buttons.php'; ?>

					<input type="hidden" name="ai1wm_manual_import" value="1" />

				</form>

				<?php do_action( 'ai1wm_import_left_end' ); ?>

			</div>
		</div>
	</div>
</div>
<?php include AI1WM_TEMPLATES_PATH . '/common/stw-footer.php'; ?>
