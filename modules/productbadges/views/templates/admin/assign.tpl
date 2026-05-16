{**
 * Asignación de badges a un producto (MVP).
 *}
<div class="panel">
  <div class="panel-heading">
    <i class="icon-anchor"></i> {$lbl_heading|escape:'html':'UTF-8'}
  </div>
  <div class="panel-body">
    <form method="post" action="{$assign_action|escape:'html':'UTF-8'}" class="form-horizontal">
      <input type="hidden" name="token" value="{$controller_token|escape:'html':'UTF-8'}" />

      <div class="form-group">
        <label class="control-label col-lg-3">{$lbl_product_id|escape:'html':'UTF-8'}</label>
        <div class="col-lg-4">
          <input type="number" name="id_product" min="1" class="form-control" value="{$id_product|intval}" required="required" />
          {if $product_label}
            <p class="help-block">{$product_label|escape:'html':'UTF-8'}</p>
          {/if}
        </div>
        <div class="col-lg-3">
          <button type="submit" name="submitProductbadgesLoad" class="btn btn-default" value="1">
            {$lbl_load|escape:'html':'UTF-8'}
          </button>
        </div>
      </div>

      {if $id_product > 0}
        <hr />
        <p class="help-block">{$lbl_help_select|escape:'html':'UTF-8'}</p>
        <div class="form-group">
          <label class="control-label col-lg-3">{$lbl_badges|escape:'html':'UTF-8'}</label>
          <div class="col-lg-9">
            {foreach from=$badges_rows item=b}
              <div class="checkbox">
                <label>
                  <input type="checkbox" name="badges[]" value="{$b.id_badge|intval}"
                    {if isset($selected_badges[$b.id_badge])}checked="checked"{/if} />
                  {$b.name|escape:'html':'UTF-8'}
                  {if empty($b.active)}
                    <span class="text-muted">({$lbl_inactive|escape:'html':'UTF-8'})</span>
                  {/if}
                </label>
              </div>
            {foreachelse}
              <p class="alert alert-warning">{$lbl_no_badges|escape:'html':'UTF-8'}</p>
            {/foreach}
          </div>
        </div>
        <div class="panel-footer">
          <button type="submit" name="submitAssignproductbadges" class="btn btn-primary pull-right" value="1">
            {$lbl_save|escape:'html':'UTF-8'}
          </button>
        </div>
      {/if}
    </form>
  </div>
</div>
