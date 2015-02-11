/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License BY-NC-ND.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material,
 * you may not distribute the modified material.
 * See the full license at http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * See http://mventory.com/legal/licensing/ for other licensing options.
 *
 * @package MVentory/API
 * @copyright Copyright (c) 2014 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

jQuery(document).ready(function ($) {
  var new_rule = getNewRule();

  var $rules = $('#mventory-rules');
  var $rule_template = $rules.children('.mventory-rule-template');
  var $new_rule = $('#mventory-rule-new').children('.mventory-inner');
  var $new_attr = $new_rule.children('.mventory-rule-new-attr');
  var $categories_wrapper = $('#mventory-categories-wrapper');

  var $save_rule_button = $('#mventory-rule-save');
  var $magento_category = $('#mventory-categories');

  $new_attr
    .find('> .mventory-rule-new-attr-column .mventory-rule-new-attr-name')
    .on('change', function () {
      var $this = $(this);
      var attr_id = $this.val();

      var attr = mventory_attrs[attr_id];

      var $parent = $this.parents('.mventory-rule-new-attr');

      if (!$parent.next().length)
        $new_rule.append(reset_attr(clone_attr()));

      var $values = $parent
                      .removeClass('mventory-state-not-completed')
                      .find('.mventory-rule-new-attr-value')
                      .empty();

      for (var i = 0, value; value = attr.values[i++];)
        $values.append($('<option>', {
          value: value.id,
          text: value.label,
          class: attr.used_values[value.id] ? 'mventory-state-used-value' : ''
        }));

      $values.change();
    });

  $new_attr
    .find('> .mventory-rule-new-attr-column .mventory-rule-new-attr-value')
    .on('change', function () {
      new_rule.attrs = get_attrs();
      update_save_rule_button_state();
    });

  $new_attr
    .find('.mventory-rule-new-attr-buttons > .mventory-rule-remove')
    .on('click', function () {
      var $parent = $(this).parents('.mventory-rule-new-attr');

      if ($parent.hasClass('mventory-state-not-completed'))
        return false;

      $parent.remove();

      new_rule.attrs = get_attrs();
      update_save_rule_button_state();

      return false;
    });

  $save_rule_button.on('click', function () {
    if ($save_rule_button.hasClass('disabled'))
      return;

    new_rule.id = new_rule.attrs.length
                    ? 'rule' + new Date().getTime()
                      : MVENTORY_RULE_DEFAULT_ID;

    var $default_rule = $('#' + MVENTORY_RULE_DEFAULT_ID);

    if (new_rule.id == MVENTORY_RULE_DEFAULT_ID && $default_rule.length) {
      update_categories_names($default_rule);
      var $rule = $default_rule;
    } else {
      var $rule = $rule_template
                    .clone(true)
                    .removeClass('mventory-rule-template')
                    .attr('id', new_rule.id);

      var $list = $rule.find('.mventory-rule-attrs > .mventory-inner');
      var $attr_template = $list.find('> :first-child');

      update_categories_names($rule);

      if (new_rule.id == MVENTORY_RULE_DEFAULT_ID)
        $attr_template
          .clone()
          .html(MVENTORY_RULE_DEFAULT_TITLE)
          .appendTo($list);
      else
        for (var i = 0; i < new_rule.attrs.length; i++) {
          var attr = new_rule.attrs[i];
          var attr_data = mventory_attrs[attr.id];

          var value = [];

          for (var n = 0, valueId; valueId = attr.value[n++];)
            for (var m = 0, attrVal; attrVal = attr_data.values[m++];)
              if (valueId == attrVal.id) {
                value[value.length] = attrVal.label;
                break;
              }

          var $values = $attr_template.clone();

          $values
            .children('.mventory-rule-attr-name')
            .html(attr_data.label);

          $values
            .children('.mventory-rule-attr-value')
            .html(value.join(', '));

          $list.append($values);
        }

      $attr_template.remove();

      setRuleState($rule, 'processing');

      if ($default_rule.length)
        $default_rule.before($rule)
      else
        $rules.append($rule);
    }

    submit_rule(new_rule, $rule);

    resetCurrentRule();
    update_save_rule_button_state();
  });

  $('#mventory-rule-reset').on('click', function () {
    resetCurrentRule()
    update_save_rule_button_state();
  });

  $rules
    .find('> .mventory-rule .mventory-rule-remove')
    .on('click', function () {
      var $rule = $(this).parents('.mventory-rule');

      remove_rule($rule.attr('id'), $rule);

      return false;
    });

  $rules.sortable({
    items: '[id^="rule"]',
    placeholder: 'mventory-rule-placeholder box',
    forcePlaceholderSize: true,
    axis: 'y',
    containment: 'parent',
    revert: 200,
    tolerance: 'pointer',
    update: function () {
      reorder_rules($rules.sortable('toArray'));
    }
  });

  $magento_category.on('click', function () {
    $categories_wrapper.toggle();

    return false;
  });

  function clone_attr () {
    return $new_rule
             .find('> .mventory-rule-new-attr')
             .last()
             .clone(true);
  }

  function reset_attr ($attr) {
    return $attr
             .children('.mventory-rule-new-attr-name')
               .val('-1')
             .end();
  }

  function get_attrs () {
    var attrs = [];

    $new_rule
      .find('> .mventory-rule-new-attr')
      .each(function () {
        var attr = get_attr($(this));

        if (!(attr.id == '-1' || attr.value == null))
          attrs.push(attr);
      });

    return attrs;
  }

  function get_attr ($attrs) {
    return {
      id: $attrs.find('.mventory-rule-new-attr-name').val(),
      value: $attrs.find('.mventory-rule-new-attr-value').val()
    }
  }

  function submit_rule (rule, $rule) {
    var data = { rule: JSON.stringify(rule) },
        onSuccess,
        onError;

    onSuccess = function () {
      if ($rules.children('.mventory-rule').filter(':visible').length > 2)
        $('#mventory-matching-messages').hide();

      for (var i = 0, attr; attr = rule.attrs[i++];) {
        $new_rule
          .find('> .mventory-rule-new-attr')
          .last()
          .find('.mventory-rule-new-attr-name > [value="' + attr.id + '"]')
          .addClass('mventory-state-used-attr');

        $.map($.makeArray(attr.value), function (value, index) {
          mventory_attrs[attr.id]['used_values'][value * 1] = true;
        });
      }

      setRuleState($rule, 'success');
      setTimeout(function () { setRuleState($rule); }, 3500);
    };

    onError = function (msg) {
      $rule
        .children('.mventory-rule-err-msg')
        .html(msg);

      setRuleState($rule, 'error');
    }

    action('addrule', data, onSuccess, onError);
  }

  function remove_rule (ruleId, $rule) {
    var data = { rule_id: ruleId },
        onSuccess,
        onError;

    setRuleState($rule, 'processing');

    onSuccess = function () {
      $rule.remove();
    };

    onError = function (msg) {
      $rule
        .children('.mventory-rule-err-msg')
        .html(msg);

      setRuleState($rule, 'error');
    };

    action('remove', data, onSuccess, onError);
  }

  function reorder_rules (ids) {
    var data = { ids: ids },
        $rules,
        onSuccess,
        onError;

    $rules = $($.map(ids, function (id) { return '#' + id; }).join(','))
    setRuleState($rules, 'processing');

    onSuccess = function () {
      setRuleState($rules, 'success');
      setTimeout(function () { setRuleState($rules); }, 3500);
    };

    onError = function (msg) {
      $rules
        .children('.mventory-rule-err-msg')
        .html(msg);

      setRuleState($rules, 'error');
    }

    action('reorder', data, onSuccess, onError);
  }

  function action (action, data, onSuccess, onError) {
    request(
      mventory_urls[action],
      data,
      function (msg, data) {
        if (typeof onSuccess === 'function')
          onSuccess();
      },
      function (msg, data) {
        msg = __('Error') + ': ' + msg;

        if (data.exception)
          msg += '\n\n'
                 + 'Exception: ' + data.exception.message + '\n'
                 + data.exception.trace;

        if (typeof onError === 'function')
          onError(msg);
      }
    );
  }

  function request (url, data, onSuccess, onError) {
    data['form_key'] = FORM_KEY;

    $.ajax({
      url: url,
      type: 'POST',
      dataType: 'json',
      data: data,
      success: function (response, textStatus, xhr) {
        var msg = response.message,
            data = response.data;

        response.success ? onSuccess(msg, data) : onError(msg, data);
      },
      error: function (xhr, textStatus, errorThrown) {
        alert(__('Error') + ': ' + textStatus);
      },
    });
  }

  function update_save_rule_button_state () {
    if (new_rule.categories.length)
      $save_rule_button.removeClass('disabled');
    else
      $save_rule_button.addClass('disabled');
  }

  function update_categories_names ($rule) {
    $category = $rule
      .find('.mventory-rule-categories .mventory-rule-category')
      .text(
        window.mventory_categories_get_names(new_rule.categories).join(', ')
      );

    if (!new_rule.categories.length)
      $category.addClass('mventory-state-no-category');
  }

  function select_category (id) {
    if (new_rule.categories.indexOf(id) == -1)
      new_rule.categories.push(id);

    update_save_rule_button_state();
  }

  function unselect_category (id) {
    var pos = new_rule.categories.indexOf(id);

    if (pos > -1)
      new_rule.categories.splice(pos, 1);

    update_save_rule_button_state();
  }

  function getNewRule () {
    return {
      'id': null,
      'categories': [],
      'attrs' : []
    };
  }

  function resetCurrentRule () {
    var $attr = clone_attr();

    $new_rule
      .find('> .mventory-rule-new-attr')
      .remove();

    reset_attr($attr)
      .appendTo($new_rule);

    window.mventory_categories_reset();

    new_rule = getNewRule();
  }

  function setRuleState ($rule, state) {
    $rule.removeClass(
      'mventory-state-success mventory-state-error mventory-state-processing'
    );

    if (state)
      $rule.addClass('mventory-state-' + state);
  }

  function __ (text) {
    return Translator.translate(text);
  }

  window.mventory_select_category = select_category;
  window.mventory_unselect_category = unselect_category;
});
