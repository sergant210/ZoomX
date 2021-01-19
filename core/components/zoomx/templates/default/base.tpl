<!doctype html>
<html lang="{'cultureKey'|config}">
<head>
	{block "title"}<title>{'pagetitle'|resource} - {'site_name'|config}</title>{/block}
	<base href="{'site_url'|config}" />
	<meta charset="{'modx_charset'|config}" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
	{block "styles"}{/block}
</head>
<body>
<div class="container">
	{block "content"}{/block}
</div>
{block "scripts"}{/block}
</body>
</html>
