{if isset({$gadsense_estate}) && {$gadsense_estate}==1}
<a class="banner" href="{$gadsense_link}">
  {if isset($gadsense_img)}
  	<h1 style="margin-left:4px; margin-bottom: 1.5rem; text-transform: uppercase; color: #232323; font-weight: 700; cursor: default;">{$gadsense_title}</h1>
    <img src="{$gadsense_img}" alt="{$gadsense_desc}" title="{$gadsense_desc}"/>
	    <a style="text-decoration: none; padding: 10px;  font-weight: 600; font-size: 20px; color: #ffffff; background-color: #1883ba; border-radius: 6px; border: 2px solid #0016b0;" href="{$gadsense_cta}">CLICKEAME</a>
	    <span style="color: #232323; font-weight: 400; font-size: 1.1em; cursor: default;">{$gadsense_desc}</span>
  {else}
    <span>{$gadsense_desc}</span>
  {/if}
</a>
 {/if}