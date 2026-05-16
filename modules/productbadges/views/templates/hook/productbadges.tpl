{**
 * Bloque de etiquetas en ficha de producto (hook displayProductAdditionalInfo).
 *}
{if isset($product_badges) && $product_badges|@count > 0}
  <div class="productbadges-block" data-module="productbadges">
    {foreach from=$product_badges item=badge}
      <span class="badge badge-secondary productbadges-badge" style="background-color:{$badge.bg_color|escape:'html':'UTF-8'};color:{$badge.text_color|escape:'html':'UTF-8'};margin-right:4px;margin-bottom:4px;display:inline-block;padding:4px 8px;border-radius:2px;">
        {$badge.name|escape:'html':'UTF-8'}
      </span>
    {/foreach}
  </div>
{/if}
