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
