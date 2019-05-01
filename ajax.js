/*
 * ViaThinkSoft LogViewer
 * Copyright 2018-2019 Daniel Marschall, ViaThinkSoft
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

function _solve(id) {
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function () {
		if (xhr.readyState === 4 /* DONE */) {
			if (xhr.status !== 200 /* OK */) {
				alert('Generic network failure. Please try again.');
				return;
			}
			try {
				obj = JSON.parse(xhr.responseText);
			} catch (e) {
				alert('Server side error!');
				return;
			}
			if (!obj.success) {
				alert(/* 'JSON server error: ' + */ obj.error);
				return;
			}
			if (document.getElementById('line'+obj.id).style.display != 'none') {
				document.getElementById('line'+obj.id).style.display = 'none';
				document.getElementById('count').innerHTML = document.getElementById('count').innerHTML - 1;
			}
		}
	};
	xhr.open('POST', 'ajax_cmd.php', true);
	xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xhr.send('cmd=solve&id='+id);
}
