<?php
session_start();

$dir = __DIR__ . '/language'; // Directory that contains language files
$default = 'en'; // Default language file for comparison, without .php extension

$requestKey = @$_REQUEST['language'];

// Handle form submission
if (isset($_POST['lang'])) {
	$lang = $_POST['lang'];
	$name = $_POST['name'];

	// Generate content file
	$content = "<?php\n/**\n * " . $name . "\n */\nreturn [\n";
	foreach ($lang as $key => $value) {
		$content .= "\t'" . $key . "' => '" . addslashes($value) . "',\n";
	}
	$content .= "];\n";

	// Save content to file
	if (!@file_put_contents($file = $dir . '/' . $requestKey . '.php', $content)) {
		$error = 'Cannot save changes, permision denied';
		$requestSet = $lang;
	} else {
		// Reload on saved success
		$_SESSION['translator_message'] = 'Saved changes successfully';
		header('Location: ?language=' . $requestKey);
		exit;
	}
}

// Handle status if any
$status = '';
if (isset($_SESSION['translator_message'])) {
	$message = $_SESSION['translator_message'];
	$status = '<div class="status message">' . $message . '</div>';
	unset($_SESSION['translator_message']);
} else if (isset($error)) {
	$status = '<div class="status error">' . $error . '</div>';
}

// Generate language list
$languageList = [];

if (!$dh = opendir($dir)) {
	exit('Cannot open language directory');
}
while ($file = readdir($dh)) {
	$path = $dir . '/' . $file;
	$pathInfo = pathinfo($path);
	if ($pathInfo['extension'] == 'php') {
		$key = $pathInfo['filename'];
		$content = file_get_contents($path);
		if (preg_match('/\* .+/', $content, $matches)) {
			$name = substr($matches[0], 2);
		} else {
			$name = $key;
		}
		$languageList[$key] = [
			'name' => $name,
			'path' => $path
		];
	}
}
closedir($dh);

// Travel thru language list to make language options
$langOptions = '';
$counter = 0;
foreach ($languageList as $key => $value) {
	if ($key != $default) {
		// Initial requested key if not existed
		if (!isset($requestKey)) {
			$requestKey = $key;
		}
		$selected = ($key == $requestKey) ? 'selected' : '';
		$langOptions .= '<option value="' . $key . '"' . $selected . '>' . $value['name'] . '</option>';
	}
}

// Initial default language set
$defaultName = $languageList[$default]['name'];
$defaultSet = require $languageList[$default]['path'];

// Initial requested language set
$requestName = $languageList[$requestKey]['name'];
if (!isset($_POST['lang'])) {
	$requestSet = require $languageList[$requestKey]['path'];
}

// Travel thru language set to generate transtion field
$langRowList = '';
foreach ($defaultSet as $key => $value) {
	$requestValue = isset($requestSet[$key]) ? $requestSet[$key] : $value;
	$langRowList .= '<tr>
		<td>' . $value. '</td>
	<td><textarea rows="2" onfocus="focused(this)" onblur="blurred(this)" name="lang[' . $key . ']">' . $requestValue . '</textarea></td>
	</tr>';
}

// End of logical
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Translator</title>
	<style type="text/css">
	/**
	 * Create some style, make sure the page looks cool
	 */
	body {
		font: 13px/1.5 "Helvetica Neue", Helvetica, Arial, sans-serif;
		background: #F5F8F9;
		margin: 0;
	}
	.container {
		max-width: 940px;
		padding: 20px;
		margin: auto;
	}
	table {
		width: 100%;
		border-collapse: collapse;
		background: #FFF;
		box-shadow: 0 0 1px rgba(0, 0, 0, .2);
		margin: 20px 0;
	}
	th, td {
		padding: 12px;
		border-bottom: 1px solid #F1F1F1;
		width: 50%;
		vertical-align: top;
	}
	tr th {
		background-color: #E9ECF1;
		color: #57697E;
		font-weight: normal;
		text-align: left;
		border-bottom: 1px solid #D9E0EC;
	}
	textarea {
		border: 1px solid #FFF;
		font-size: inherit;
		padding: 4px;
		margin: 0;
		width: 100%;
		box-sizing: border-box;
		border-radius: 3px;
		resize: vertical;
	}
	textarea:focus, button:focus {
		outline: none;
		border-color: #419BF9;
		box-shadow: 0 0 0 3px rgba(70, 158, 250, .5);
	}
	button {
		min-width: 120px;
		padding: 10px;
		background: #fff;
		border: 1px solid #ddd;
		border: 1px solid #ddd;
		border-radius: 3px;
		font-size: 1.1em;
		cursor: pointer;
		margin: 0;
	}
	button.active {
		background: #419bf9 linear-gradient(to bottom, rgba(65, 155, 249, 1) 0%, rgba(26, 130, 251, 1) 100%);
		border-color: #2f84f8;
		color: #fff;
	}
	button:active {
		background: linear-gradient(to bottom, rgba(80, 154, 251, 1) 0%, rgba(20, 105, 224, 1) 100%);
	}
	.right {
		text-align: right;
	}
	.status {
		padding: 16px 0;
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		text-align: center;
		font-weight: bold;
		color: #FFF;
		transition: .25s;
	}
	.status.error {
		background: #F00;
	}
	.status.message {
		background: #09F;
	}
	.status::after {
		content: 'X';
		position: absolute;
		right: 16px;
	}
	</style>
</head>
<body>
	<div class="container">
		<?= $status ?>
		<form method="post" name="language_form">
			<input type="hidden" name="language" value="<?= $requestKey ?>">
			<input type="hidden" name="name" value="<?= $requestName ?>">
			<table>
				<tr>
					<th><?= $defaultName ?></th>
					<th>
						<select onchange="location.href = '?language=' + this.value">
							<?= $langOptions ?>
						</select>
					</th>
				</tr>
				<?= $langRowList ?>
			</table>
			<div class="right">
				<button class="active" onclick="formSubmit=true">Save</button>
				<button type="button" onclick="saveAs()">Save As...</button>
			</div>
		</form>
	</div>
	<script type="text/javascript">
		// Detect if any field has made change, by look for its focus & blur value
		var changed = false;
		var focusedText = '';
		var focused = function(field) {
			focusedText = field.value;
		};
		var blurred = function(field) {
			if (field.value != focusedText) {
				changed = true;
			}
		};

		// Handle save as function, prompt for new language key & name before submit
		var saveAs = function() {
			var form = document.forms.language_form;
			var key = prompt('New language key', form.language.value);
			if (key === '' || key === null) {
				return;
			}
			var name = prompt('New language name', form.name.value);
			if (name === '' || name === null) {
				return;
			}
			form.language.value = key;
			form.name.value = name;
			form.submit();
		};

		// Show warning message if user leave the page without saving changes
		window.addEventListener('beforeunload', function(e) {
			console.log(changed);
			if (typeof formSubmit == 'undefined' && changed === true) {
				return 'Your unsaved data will be lost';
			}
		});

		// Do some magically to show & hide status border-radius
		document.addEventListener('DOMContentLoaded', function(e) {
			var status = document.querySelector('.status');
			if (status) {
				status.addEventListener('click', function(e) {
					this.style.top = '-' + this.clientHeight + 'px';
				});
			}
		});
	</script>
</body>
</html>