{**
 * Badges en imagen (ficha / listados) o bloque fallback bajo ficha.
 *}
{if (isset($product_badges_left) && $product_badges_left|@count > 0) || (isset($product_badges_right) && $product_badges_right|@count > 0)}
  {if isset($productbadges_on_cover) && $productbadges_on_cover}
    <link rel="stylesheet" href="{$productbadges_css|escape:'html':'UTF-8'}" type="text/css" media="all" />
    <div class="productbadges-overlay productbadges-overlay--{$productbadges_placement|default:'product'|escape:'html':'UTF-8'}" data-module="productbadges">
      <div class="productbadges-wrapper">
        {if isset($product_badges_left) && $product_badges_left|@count > 0}
          <div class="productbadges-stack productbadges-stack--left">
            {foreach from=$product_badges_left item=badge}
              <span class="productbadges-badge productbadges-badge--left" style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
                {$badge.name|escape:'html':'UTF-8'}
              </span>
            {/foreach}
          </div>
        {/if}
        {if isset($product_badges_right) && $product_badges_right|@count > 0}
          <div class="productbadges-stack productbadges-stack--right">
            {foreach from=$product_badges_right item=badge}
              <span class="productbadges-badge productbadges-badge--right" style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
                {$badge.name|escape:'html':'UTF-8'}
              </span>
            {/foreach}
          </div>
        {/if}
      </div>
    </div>
  {else}
    <div class="productbadges-block productbadges-block--fallback" data-module="productbadges">
      <div class="productbadges-wrapper productbadges-wrapper--fallback">
        {if isset($product_badges_left) && $product_badges_left|@count > 0}
          <div class="productbadges-stack productbadges-stack--left">
            {foreach from=$product_badges_left item=badge}
              <span class="productbadges-badge productbadges-badge--left" style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
                {$badge.name|escape:'html':'UTF-8'}
              </span>
            {/foreach}
          </div>
        {/if}
        {if isset($product_badges_right) && $product_badges_right|@count > 0}
          <div class="productbadges-stack productbadges-stack--right">
            {foreach from=$product_badges_right item=badge}
              <span class="productbadges-badge productbadges-badge--right" style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
                {$badge.name|escape:'html':'UTF-8'}
              </span>
            {/foreach}
          </div>
        {/if}
      </div>
    </div>
  {/if}
{/if}
