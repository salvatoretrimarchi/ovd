<?php
/**
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Gauvain Pocentek <gauvain@ulteo.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

/* This is an exemple for now, real implementation needs to be done */

require_once(dirname(__FILE__).'/../../../includes/core.inc.php');

class ReportCallback extends EventCallback {
    public function run () {
		/* don't register a new session if the user is resuming it */
		if (isset($this->ev->suspended) && $this->ev->suspended)
			return true;

		$rep = ServerReport::load();
		$rep->reportSessionStart($this->ev->server);
		$rep->save();

		return true;
    }
}

