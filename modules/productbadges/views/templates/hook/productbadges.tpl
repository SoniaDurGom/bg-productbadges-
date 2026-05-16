{**
 * Badges en ficha de producto.
 *}
{if (isset($product_badges_left) && $product_badges_left|@count > 0) || (isset($product_badges_right) && $product_badges_right|@count > 0)}
  {if isset($productbadges_on_cover) && $productbadges_on_cover}
    <link rel="stylesheet" href="{$productbadges_css|escape:'html':'UTF-8'}" type="text/css" media="all" />
    <div class="productbadges-overlay" data-module="productbadges" style="position:absolute;top:0;left:0;width:100%;height:0;overflow:visible;z-index:20;pointer-events:none;">
      {if isset($product_badges_left) && $product_badges_left|@count > 0}
        <div class="productbadges-corner badge-left" style="position:absolute;top:10px;left:10px;display:flex;flex-direction:column;gap:6px;max-width:calc(50% - 16px);align-items:flex-start;">
          {foreach from=$product_badges_left item=badge}
            <span class="productbadges-badge badge-left" style="display:inline-block;padding:4px 10px;border-radius:3px;font-size:12px;font-weight:600;line-height:1.2;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,0.2);background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
              {$badge.name|escape:'html':'UTF-8'}
            </span>
          {/foreach}
        </div>
      {/if}
      {if isset($product_badges_right) && $product_badges_right|@count > 0}
        <div class="productbadges-corner badge-right" style="position:absolute;top:10px;right:10px;display:flex;flex-direction:column;gap:6px;max-width:calc(50% - 16px);align-items:flex-end;">
          {foreach from=$product_badges_right item=badge}
            <span class="productbadges-badge badge-right" style="display:inline-block;padding:4px 10px;border-radius:3px;font-size:12px;font-weight:600;line-height:1.2;white-space:nowrap;box-shadow:0 1px 3px rgba(0,0,0,0.2);background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};">
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
