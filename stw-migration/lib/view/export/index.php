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
 */
?>

<?php include AI1WM_TEMPLATES_PATH . '/common/stw-header.php'; ?>
<div class="ai1wm-container">
	<div class="ai1wm-row">
		<div class="ai1wm-left">
			<div class="ai1wm-holder">
				<h2 class="stw-panel-title">Backup contents</h2>


				<form action="" method="post" id="ai1wm-export-form" class="ai1wm-clear">

					<ul class="stw-backup-scope" aria-label="Default backup contents">
						<li>Database</li><li>Media</li><li>Themes</li><li>Plugins</li><li>.stwm archive</li>
					</ul>
					<p class="stw-summary">Includes your complete site. Use the options below to exclude content or replace text.</p>
					<details class="stw-details"><summary>Find and replace text <span>Optional</span></summary>
					<?php include AI1WM_TEMPLATES_PATH . '/export/find-replace.php'; ?>
					</details>

					<?php do_action( 'ai1wm_export_left_options' ); ?>

					<details class="stw-details"><summary>Backup options <span>Customize what’s included</span></summary>
					<?php include AI1WM_TEMPLATES_PATH . '/export/advanced-settings.php'; ?>
					</details>

					<?php include AI1WM_TEMPLATES_PATH . '/export/export-buttons.php'; ?>

					<input type="hidden" name="ai1wm_manual_export" value="1" />

				</form>

				<?php do_action( 'ai1wm_export_left_end' ); ?>

			</div>
		</div>
	</div>
</div>
<?php include AI1WM_TEMPLATES_PATH . '/common/stw-footer.php'; ?>
