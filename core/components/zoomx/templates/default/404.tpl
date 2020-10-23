{extends "base.tpl"}

{block "content"}
	<h1>404 Page not found</h1>
	<div><span>Шаблон: {$smarty.template}.</span></div>
	<h3>{'longtitle'|resource:'pagetitle'}</h3>
{/block}
{block "footer"}
	<p>[^t^]</p>
	<p>[^q^]</p>
	<p>[^m^]</p>
{/block}