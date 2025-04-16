<?php defined('_RUNKEY') or die; ?>

<!DOCTYPE html>
<html lang="{{app.default_locale}}"></html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={{app.default_charset}}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="Description" content="{{app.description}}">
    <title>{{app.app_short_name}}</title>
    <link rel="shortcut icon" href="{{app.favicon}}" type="image/png"/>
	{{insert.css}}
	{{inject}}
	{{insert.js}}
</head>
<body>
{{insert.styles}}
	<div class="overall">
		<div class="wrapper menu-holder">
			<nav class="topnav" id="mainMenu">
				<form name="mainmenu" class="menu-container" id="m-container">
					{{insert.mainmenu}}
				</form>
				<div class="menu-container flex-right">
					<a class="command menu-item icon" title="Log in" id="login">
						<span><i class="fas fa-right-to-bracket"></i></span>
					</a>
				</div>
			</nav>
			<div class="switch-container flex-vertical" style="border-radius: 15px;">
				<span id="light"><i class="fas fa-sun"></i></span>
				<label class="switch">
					<input type="checkbox" class="command switch-toggle hidden" id="theme-toggle">
					<span class="slider round"></span>
				</label>
				<span id="dark"><i class="fas fa-moon"></i></span>
			</div>
		</div>
		<div class="content">
		{{content}}
		</div>
		<div class="wrapper bottom menu-holder">
			{{bottom}}
		</div>
	</div>
</body>
</html>
