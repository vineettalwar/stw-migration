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

<?php if ( is_readable( AI1WM_STORAGE_PATH ) && is_writable( AI1WM_STORAGE_PATH ) ) : ?>
<div class="ai1wm-import-messages" role="status" aria-live="polite"></div>
<div class="ai1wm-import-form"><div class="hide-if-no-js"><div class="ai1wm-drag-drop-area" id="ai1wm-drag-drop-area">
<div id="ai1wm-import-init"><span class="stw-upload-icon" aria-hidden="true">↥</span><h3>Drop your backup here</h3><p>STW .stwm and legacy .wpress files are supported.</p>
<label id="ai1wm-import-file" class="stw-primary" for="ai1wm-select-file">Choose a backup<input type="file" id="ai1wm-select-file" accept=".stwm,.wpress" aria-label="Choose a .stwm or .wpress backup" /></label>
</div></div></div></div>
<p class="stw-caption">Uploads in small chunks. Available server space and hosting limits apply.</p>
<?php else : ?>
<div class="stw-warning" role="alert"><strong>Storage isn’t writable.</strong> Ask your administrator to grant this plugin write access to its storage directory, then reload this page.</div>
<?php endif; ?>
