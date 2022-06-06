<?php
class OmnivaLt_Admin_Html
{
  public static function buildPriceType($params = array())
  {
    $field_id = (isset($params['field_id'])) ? $params['field_id'] : '';
    $field_title = (isset($params['title'])) ? $params['title'] : __('Price type','omnivalt');
    $field_name = (isset($params['field_name'])) ? $params['field_name'] : '';
    $field_value = (isset($params['field_value'])) ? $params['field_value'] : '';
    $add_select_options = (isset($params['add_select_options'])) ? $params['add_select_options'] : array();
    
    $select_options = array(
      'simple' => __('Simple','omnivalt'),
      'weight' => __('By cart weight','omnivalt'),
      'amount' => __('By cart amount','omnivalt'),
    );
    $select_options = array_merge($select_options, $add_select_options);

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
    $col_1_text = (isset($params['c1_text'])) ? $params['c1_text'] : array();
    $allow_add = (isset($params['allow_add'])) ? $params['allow_add'] : true;
    $values = (isset($params['values']) && is_object($params['values'])) ? $params['values'] : array();
    $description = (isset($params['desc'])) ? $params['desc'] : '';

    $row_actions = array();
    if ($allow_add) {
      $row_actions[] = 'remove';
    }

    $prev_value = 0;

    ob_start();
    ?>
    <div class="prices-table table-<?php echo $type; ?>">
      <label><?php echo $title; ?>:</label>
      <table data-id="<?php echo $field_id; ?>" data-name="<?php echo $field_name; ?>" data-step1="<?php echo $col_1_step; ?>" data-step2="<?php echo $col_2_step; ?>">
        <tr class="row-title">
          <th class="column-value"><?php echo $col_1_title; ?></th>
          <th class="column-price"><?php echo $col_2_title; ?></th>
          <?php if (!empty($row_actions)) : ?>
            <th class="column-actions"><?php echo $col_3_title; ?></th>
          <?php endif; ?>
        </tr>
        <?php $i = 1; ?>
        <?php foreach ($values as $value) : ?>
          <tr class="row-values">
            <td class="column-value">
              <?php if (!empty($col_1_text)) : ?>
                <input class="input-text regular-input" type="hidden" name="<?php echo $field_name . '[' . $i . '][value]'; ?>" id="<?php echo $field_id . '_value_' . $i; ?>" <?php if (isset($value->value)) : ?>value="<?php echo $value->value; ?>"<?php endif;?>>
                <span class="row-from"><?php echo $col_1_text[$value->value]; ?></span>
              <?php else : ?>
                <span class="row-from"><span class="value-from" data-step="<?php echo $col_1_step; ?>"><?php echo ($prev_value == 0) ? number_format((float)$prev_value, $col_1_decimal, '.', '') : number_format((float)$prev_value + (float)$params['c1_step'], $col_1_decimal, '.', ''); ?></span> - </span>
                <input class="input-text regular-input" type="number" name="<?php echo $field_name . '[' . $i . '][value]'; ?>" id="<?php echo $field_id . '_value_' . $i; ?>" <?php if (isset($value->value)) : ?>value="<?php echo $value->value; ?>"<?php endif;?> step="<?php echo $col_1_step; ?>" min="0" placeholder="...">
              <?php endif; ?>
            </td>
            <td class="column-price">
              <input class="input-text regular-input" type="number" name="<?php echo $field_name . '[' . $i . '][price]'; ?>" id="<?php echo $field_id . '_price_' . $i; ?>" value="<?php echo $value->price; ?>" step="<?php echo $col_2_step; ?>" min="0">
            </td>
            <?php if (!empty($row_actions)) : ?>
              <td class="column-actions">
                <?php if (in_array('remove', $row_actions)) : ?>
                  <div class="omniva-fake-btn" data-action="remove_prices_table_row">X</div>
                <?php endif; ?>
              </td>
            <?php endif; ?>
          </tr>
          <?php
          $prev_value = $value->value;
          $i++;
          ?>
        <?php endforeach; ?>
        <tr class="row-footer">
          <?php if ($allow_add) : ?>
            <td class="column-add" colspan="3">
              <div class="omniva-fake-btn" data-action="add_prices_table_row"><?php echo __('Add row','omnivalt'); ?></div>
            </td>
          <?php endif; ?>
        </tr>
      </table>
      <?php if (!empty($description)) : ?>
        <p class="description"><?php echo $description; ?></p>
      <?php endif; ?>
    </div>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }

  public static function buildSwitcher($params = array())
  {
    $label = (isset($params['label'])) ? $params['label'] : '';
    $title = (isset($params['title'])) ? $params['title'] : '';
    $id = (isset($params['id'])) ? $params['id'] : '';
    $name = (isset($params['name'])) ? $params['name'] : '';
    $class = (isset($params['class'])) ? $params['class'] : '';
    $checked = (isset($params['checked'])) ? $params['checked'] : false;
    $value = (isset($params['value'])) ? $params['value'] : 1;

    $field_checked = ($checked) ? 'checked' : '';

    ob_start();
    ?>
    <?php if (!empty($label)) : ?>
      <label for="<?php echo $id; ?>"><?php echo $label; ?></label>
    <?php endif; ?>
    <div class="switcher" title="<?php echo $title; ?>">
      <label class="switch">
        <input type="checkbox" class="<?php echo $class; ?>" id="<?php echo $id; ?>" name="<?php echo $name; ?>" <?php echo $field_checked; ?> value="<?php echo $value; ?>">
        <span class="slider round"></span>
      </label>
    </div>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }

  public static function buildSimpleField($params = array())
  {
    $label = (isset($params['label'])) ? $params['label'] : '';
    $id = (isset($params['id'])) ? $params['id'] : '';
    $type = (isset($params['type'])) ? $params['type'] : 'text';
    $name = (isset($params['name'])) ? $params['name'] : '';
    $value = (isset($params['value'])) ? $params['value'] : '';
    $placeholder = (isset($params['placeholder'])) ? $params['placeholder'] : '';
    $step = (isset($params['step'])) ? $params['step'] : 1;
    $min = (isset($params['min'])) ? $params['min'] : '';
    $max = (isset($params['max'])) ? $params['max'] : '';
    $class = (isset($params['class'])) ? $params['class'] : 'input-text regular-input';

    $html = '';
    if (!empty($label)) {
      $html .= '<label for="' . $id . '">' . $label . '</label> ';
    }
    $html .= '<input class="' . $class . '" type="' . $type . '" name="' . $name . '" id="' . $id . '" value="' . $value . '"';
    if ($type === 'number') {
      $html .= ' step="' . $step . '" min="' . $min . '" max="' . $max . '"';
    }
    if (!empty($placeholder)) {
      $html .= ' placeholder="' . $placeholder . '"';
    }
    $html .= '>';

    return $html;
  }

  public static function buildCheckbox($params = array())
  {
    $label = (isset($params['label'])) ? $params['label'] : '';
    $label_position = (isset($params['label_position'])) ? $params['label_position'] : 'after'; //left, right, before, after
    $id = (isset($params['id'])) ? $params['id'] : '';
    $class = (isset($params['class'])) ? $params['class'] : '';
    $name = (isset($params['name'])) ? $params['name'] : '';
    $checked = (isset($params['checked'])) ? $params['checked'] : false;
    $value = (isset($params['value'])) ? $params['value'] : 1;

    $html = '';
    if (!empty($label)) {
      switch ($label_position) {
        case 'left':
          $html .= '<label for="' . $id . '">' . $label . '</label>';
          break;
        case 'before':
          $html .= '<label>' . $label;
          break;
        case 'after':
          $html .= '<label>';
          break;
      }
    }
    $html .= '<input type="checkbox" class="' . $class . '" id="' . $id . '" name="' . $name . '" value="' . $value . '"';
    if ($checked) {
      $html .= ' checked';
    }
    $html .= '>';
    if (!empty($label)) {
      switch ($label_position) {
        case 'right':
          $html .= '<label for="' . $id . '">' . $label . '</label>';
          break;
        case 'before':
          $html .= '</label>';
          break;
        case 'after':
          $html .= $label . '</label>';
          break;
      }
    }
    
    return $html;
  }

  public static function buildSelectField($params = array())
  {
    $label = (isset($params['label'])) ? $params['label'] : '';
    $id = (isset($params['id'])) ? $params['id'] : '';
    $class = (isset($params['class'])) ? $params['class'] : '';
    $name = (isset($params['name'])) ? $params['name'] : '';
    $options = (isset($params['options'])) ? $params['options'] : array();
    $default = (isset($params['default'])) ? $params['default'] : '';
    $default_title = (isset($params['default_title'])) ? $params['default_title'] : '-';
    $default_value = (isset($params['default_value'])) ? $params['default_value'] : '';
    $current = (!empty($params['selected'])) ? $params['selected'] : $default;
    $has_first = (isset($params['has_first'])) ? $params['has_first'] : true;

    $html = '';
    if (!empty($label)) {
      $html .= '<label for="' . $id . '">' . $label . '</label> ';
    }
    $html .= '<select id="' . $id . '" name="' . $name . '" class="select ' . $class . '">';
    $selected = ($current === '') ? 'selected' : '';
    $html .= '<option value="' . $default_value . '" ' . $selected . '>' . $default_title . '</option>';
    foreach ($options as $option) {
      $selected = ($current == $option['value']) ? 'selected' : '';
      $html .= '<option value="' . $option['value'] . '" ' . $selected . '>' . $option['title'] . '</option>';
    }
    $html .= '</select>';

    return $html;
  }

  public static function buildTextareaField($params = array())
  {
    $label = (isset($params['label'])) ? $params['label'] : '';
    $id = (isset($params['id'])) ? $params['id'] : '';
    $name = (isset($params['name'])) ? $params['name'] : '';
    $value = (isset($params['value'])) ? $params['value'] : '';
    $class = (isset($params['class'])) ? $params['class'] : '';

    $html = '';
    if (!empty($label)) {
      $html .= '<label for="' . $id . '">' . $label . '</label> ';
    }
    $html .= '<textarea class="' . $class . '" name="' . $name . '" id="' . $id . '">' . $value . '</textarea>';

    return $html;
  }
}
?>
