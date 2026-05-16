{**
 * Badges sobre imagen (ficha y listados) o bloque fallback en ficha.
 *}
{if (isset($product_badges_left) && $product_badges_left|@count > 0) || (isset($product_badges_right) && $product_badges_right|@count > 0)}
  {if isset($productbadges_on_cover) && $productbadges_on_cover}
    <link rel="stylesheet" href="{$productbadges_css|escape:'html':'UTF-8'}" type="text/css" media="all" />
    <div class="productbadges-overlay productbadges-overlay--{$productbadges_placement|default:'product'|escape:'html':'UTF-8'}" data-module="productbadges">
      {if isset($product_badges_left) && $product_badges_left|@count > 0}
        <div class="productbadges-corner badge-left{if isset($productbadges_left_count) && $productbadges_left_count > 1} productbadges-corner--multi{/if}">
          {foreach from=$product_badges_left item=badge}
            <span class="productbadges-badge badge-left" style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
              {$badge.name|escape:'html':'UTF-8'}
            </span>
          {/foreach}
        </div>
      {/if}
      {if isset($product_badges_right) && $product_badges_right|@count > 0}
        <div class="productbadges-corner badge-right{if isset($productbadges_right_count) && $productbadges_right_count > 1} productbadges-corner--multi{/if}">
          {foreach from=$product_badges_right item=badge}
            <span class="productbadges-badge badge-right" style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
              {$badge.name|escape:'html':'UTF-8'}
            </span>
          {/foreach}
        </div>
      {/if}
    </div>
  {else}
    <div class="productbadges-block productbadges-block--fallback" data-module="productbadges">
      <div class="productbadges-fallback-side badge-left">
        {foreach from=$product_badges_left item=badge}
          <span class="productbadges-badge badge-left" style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
            {$badge.name|escape:'html':'UTF-8'}
          </span>
        {/foreach}
      </div>
      <div class="productbadges-fallback-side badge-right">
        {foreach from=$product_badges_right item=badge}
          <span class="productbadges-badge badge-right" style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
            {$badge.name|escape:'html':'UTF-8'}
          </span>
        {/foreach}
      </div>
    </div>
  {/if}
{/if}
