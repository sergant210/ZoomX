{extends "base.tpl"}

{block "content"}
	<h1>{'longtitle'|resource:'pagetitle'}</h1>
	<section>
		{'content'|resource}
	</section>
{/block}