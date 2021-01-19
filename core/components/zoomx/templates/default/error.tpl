<!doctype html>
<html lang="{'cultureKey'|config}">
<head>
    {block "title"}<title>{$title|default:"Error $code"}</title>{/block}
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
			body .container {
				width: 100%;
				position: relative;
				overflow: hidden;
				display: block;
				-webkit-tap-highlight-color: rgba(255, 255, 255, 0);
			}
			body .container h1 {
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
			body .container h2 {
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
			body .container .error-message {
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
		</style>
    {/block}
</head>
<body>
<div class="container">
    {block "content"}
        {$name = explode(':', $title)}
		<h1>{$status|default:404}</h1>
		<h2>{$name[1]|default:$title}</h2>
		<p class="error-message">{$detail}</p>
    {/block}
</div>
</body>
</html>
