<!doctype html>
<html lang="{'cultureKey'|config}">
<head>
    {block "title"}<title>{$e->title|default:"Error {$e->code}"}</title>{/block}
	<base href="{'site_url'|config}" />
	<meta charset="{'modx_charset'|config}" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    {block "styles"}
		<style>
			body {
				background-color: #f7f7f7;
				box-sizing: border-box;
			}
			.container {
				width: 100%;
				position: relative;
				overflow: hidden;
				display: block;
				-webkit-tap-highlight-color: rgba(255, 255, 255, 0);
			}
			.container h1 {
				-webkit-tap-highlight-color: rgba(255, 255, 255, 0);
				font-family: "Ubuntu", sans-serif;
				text-transform: uppercase;
				text-align: center;
				font-size: 15vw;
				display: block;
				margin: 0;
				color: #519651;
				position: relative;
				z-index: 0;
				animation: colors 0.4s ease-in-out forwards;
				animation-delay: 0.3s;
			}
			.container h2 {
				font-family: "Cabin", sans-serif;
				color: #519651;
				font-size: 3vw;
				margin: 0;
				text-transform: uppercase;
				text-align: center;
				animation: colors 0.4s ease-in-out forwards;
				animation-delay: .5s;
				-webkit-tap-highlight-color: rgba(255, 255, 255, 0);
			}
			.container .error-message {
				font-family: "Cabin", sans-serif;
				font-size: 2vw;
				margin-top: 20px;
				text-align: center;
				opacity: 0;
				animation: show 2s ease-in-out forwards;
				color: #333;
				animation-delay: 0.7s;
				-webkit-tap-highlight-color: rgba(255, 255, 255, 0);
			}
			.container .error-message span {
				display: block;
			}
			.container .error-message .location {
				font-size: 0.5em;
			}
			@keyframes colors {
				50% {
					transform: scale(1.1);
				}
				100% {
					color: #ca303f;
				}
			}
			@keyframes show {
				100% {
					opacity: 1;
				}
			}
			.type-http-exception {
				display: none !important;
			}
			/**** table *****/
			.trace-errors {
				margin: 20px auto;
				width: 980px;
			}
			.trace-errors thead th.title {
				background-color: #ff8a8a;
			}
			.trace-errors thead th {
				background-color: #aeaeae;
			}
			.trace-errors tbody th {
				background-color: #eeeeec;
			}
		</style>
    {/block}
</head>
<body>
<div class="container">
    {block "content"}
        {$name = explode(':', $e->title)}
		<h1>{$e->code|default:404}</h1>
		<h2>{$name[1]|default:$e->title}</h2>
		<div class="error-message">
			<span>{$e->message|escape}</span>
			{if $showErrorDetails}
			<span class="location type-{$type}">({$e->file}:{$e->line})</span>
			{/if}
		</div>
        {if $showErrorDetails}
		<div class="type-{$type}">
			<table class="trace-errors" dir="ltr" cellspacing="0" cellpadding="1" border="1">
				<thead>
					<tr>
						<th class="title" colspan="3">Call Stack</th>
					</tr>
					<tr>
						<th>#</th>
						<th>Function</th>
						<th>Location</th>
					</tr>
				</thead>
				<tbody>
				{foreach $e->trace as $line}
					<tr>
						<td>{$line@iteration}</td>
						<td>{$line['class']}{$line['type']}{$line['function']}()</td>
						<td>{$line['file']}<b>:</b>{$line['line']}</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</div>
        {/if}
    {/block}
</div>
</body>
</html>
