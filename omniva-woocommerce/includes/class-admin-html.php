<?php
class OmnivaLt_Admin_Html
{
  public static function buildPriceType($params = array())
  {
    $field_id = (isset($params['field_id'])) ? $params['field_id'] : '';
    $field_title = (isset($params['title'])) ? $params['title'] : __('Price type','omnivalt');
    $field_name = (isset($params['field_name'])) ? $params['field_name'] : '';
    $field_value = (isset($params['field_value'])) ? $params['field_value'] : '';
    
    $select_options = array(
      'simple' => __('Simple','omnivalt'),
      'weight' => __('By cart weight','omnivalt'),
      'amount' => __('By cart amount','omnivalt'),
    );
    ob_start();
    ?>
    <div class="prices-type">
      <label for="<?php echo $field_id; ?>"><?php echo $field_title; ?>:</label>
      <select id="<?php echo $field_id; ?>" class="select price_type" name="<?php echo $field_name; ?>">
        <?php foreach ($select_options as $opt_key => $opt_title) : ?>
          <?php $selected = ($field_value == $opt_key) ? 'selected' : ''; ?>
          <option value="<?php echo $opt_key; ?>" <?php echo $selected; ?>><?php echo $opt_title; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }

  public static function buildPricesTable($params = array())
  {
    $type = (isset($params['type'])) ? $params['type'] : '';
    $title = (isset($params['title'])) ? $params['title'] : __('Prices','omnivalt');
    $field_id = (isset($params['field_id'])) ? $params['field_id'] : '';
    $field_name = (isset($params['field_name'])) ? $params['field_name'] : '';
    $col_1_title = (isset($params['c1_title'])) ? $params['c1_title'] : '';
    $col_2_title = (isset($params['c2_title'])) ? $params['c2_title'] : __('Price','omnivalt');
    $col_3_title = (isset($params['c3_title'])) ? $params['c3_title'] : '';
    $col_1_step = (isset($params['c1_step'])) ? $params['c1_step'] : 0.01;
    $col_2_step = (isset($params['c2_step'])) ? $params['c2_step'] : 0.01;
    $col_1_decimal = strlen(substr(strrchr((float)$col_1_step, "."), 1));
    $col_2_decimal = strlen(substr(strrchr((float)$col_2_step, "."), 1));
    $values = (isset($params['values']) && is_object($params['values'])) ? $params['values'] : array();

    $prev_value = 0;

    ob_start();
    ?>
    <div class="prices-table table-<?php echo $type; ?>">
      <label><?php echo $title; ?>:</label>
      <table data-id="<?php echo $field_id; ?>" data-name="<?php echo $field_name; ?>" data-step1="<?php echo $col_1_step; ?>" data-step2="<?php echo $col_2_step; ?>">
        <tr class="row-title">
          <th class="column-value"><?php echo $col_1_title; ?></th>
          <th class="column-price"><?php echo $col_2_title; ?></th>
          <th class="column-actions"><?php echo $col_3_title; ?></th>
        </tr>
        <?php $i = 1; ?>
        <?php foreach ($values as $value) : ?>
          <tr class="row-values">
            <td class="column-value">
              <span class="row-from"><span class="value-from" data-step="<?php echo $col_1_step; ?>"><?php echo ($prev_value == 0) ? number_format((float)$prev_value, $col_1_decimal, '.', '') : number_format((float)$prev_value + (float)$params['c1_step'], $col_1_decimal, '.', ''); ?></span> - </span>
              <input class="input-text regular-input" type="number" name="<?php echo $field_name . '[' . $i . '][value]'; ?>" id="<?php echo $field_id . '_value_' . $i; ?>" <?php if (isset($value->value)) : ?>value="<?php echo $value->value; ?>"<?php endif;?> step="<?php echo $col_1_step; ?>" min="0" placeholder="...">
            </td>
            <td class="column-price">
              <input class="input-text regular-input" type="number" name="<?php echo $field_name . '[' . $i . '][price]'; ?>" id="<?php echo $field_id . '_price_' . $i; ?>" value="<?php echo $value->price; ?>" step="<?php echo $col_2_step; ?>" min="0">
            </td>
            <td class="column-actions">
              <div class="omniva-fake-btn" data-action="remove_prices_table_row">X</div>
            </td>
          </tr>
          <?php
          $prev_value = $value->value;
          $i++;
          ?>
        <?php endforeach; ?>
        <tr class="row-footer">
          <td class="column-add" colspan="3">
            <div class="omniva-fake-btn" data-action="add_prices_table_row"><?php echo __('Add row','omnivalt'); ?></div>
          </td>
        </tr>
      </table>
    </div>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }
}
