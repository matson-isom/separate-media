<?php

add_action('admin_menu', 'sm_add_admin_menu');
add_action('admin_init', 'sm_settings_init');

function sm_add_admin_menu() {
  $option_page = add_options_page('Separate Media', 'Separate Media', 'manage_options', 'separate_media', 'sm_options_page');
  if ($option_page) {
    add_action('load-'.$option_page, 'sm_options_page_help_tabs');
  }
}

function sm_settings_init() {
  register_setting('pluginPage', 'sm_separation_method');

  add_settings_section(
    'sm_pluginPage_section',
    __('How will media be separated?', 'wordpress'),
    'sm_settings_section_callback',
    'pluginPage'
  );

  add_settings_field(
    'sm_radio_field_0',
    __('Separate media by', 'wordpress'),
    'sm_radio_field_0_render',
    'pluginPage',
    'sm_pluginPage_section'
  );
}

function sm_radio_field_0_render() {
  $options = get_option('sm_separation_method');
  $separation_methods = array(
    array(
      'name' => 'User',
      'description' => 'Users see their files and no one elses.',
    ),
    array(
      'name' => 'Role: Union',
      'description' => "Users must share at least one role to see each others uploads.",
    ),
    array(
      'name' => 'Role: Loose Intersect',
      'description' => "Users must share at least all of the current user's roles.",
    ),
    array(
      'name' => 'Role: Strict Intersect',
      'description' => "Users must have exactly the same roles to see each others uploads.",
    ),
  );
  ?>
  <fieldset>
  <?php
  $first = true;
  foreach ($separation_methods as $method) {
    // ref: http://buildamodule.com/forum/post/creating-machine-readable-names-from-human-readable#comment-134
    $safe_name = strtolower($method['name']);
    $safe_name = preg_replace('@[^a-z0-9_]+@', '_', $safe_name);

    if ($first) {
      $first = false;
    }
    else {
      echo '<br>';
    }
  ?>
  <label title="<?php echo $method['name']; ?>">
    <input type='radio' name='sm_separation_method[sm_radio_field_0]' <?php checked($options['sm_radio_field_0'], $safe_name); ?> value='<?php echo $safe_name; ?>'> <strong><?php echo $method['name']; ?></strong><br><?php echo $method['description']; ?>
  </label>
  <?php
  }
  ?>
  </fieldset>
  <?php
}

function sm_settings_section_callback() {
  echo '<p>'.__('Compatible with').' <a href="https://wordpress.org/plugins/user-role-editor/">User Role Editor</a>.</p>'.
    '<table class="wp-list-table widefat fixed striped">'.
      '<tbody>'.
        '<tr>'.
          '<th>'.__('User').'</th>'.
          '<th>'.__('Role(s)').'</th>'.
        '</tr>'.
        '<tr>'.
          '<td>'.__('user_a').'</td>'.
          '<td>'.__('Editor').'</td>'.
        '</tr>'.
        '<tr>'.
          '<td>'.__('user_b').'</td>'.
          '<td>'.__('Editor, Author').'</td>'.
        '</tr>'.
        '<tr>'.
          '<td>'.__('user_c').'</td>'.
          '<td>'.__('Editor, Author').'</td>'.
        '</tr>'.
        '<tr>'.
          '<td>'.__('user_d').'</td>'.
          '<td>'.__('Editor, Author, Subscriber').'</td>'.
        '</tr>'.
        '<tr>'.
          '<td>'.__('user_e').'</td>'.
          '<td>'.__('Subscriber').'</td>'.
        '</tr>'.
      '</tbody>'.
    '</table>'.
    '<br>'.
    '<table class="wp-list-table widefat fixed striped">'.
      '<tbody>'.
        '<tr>'.
          '<th>'.__('Separation Method').'</th>'.
          '<th>'.__('Users Who Share Media').'</th>'.
        '</tr>'.
        '<tr>'.
          '<td>'.__('User').'</td>'.
          '<td>'.
            '<ul>'.
              '<li><strong>'.__('user_a:').'</strong> '.__('user_a').'</li>'.
              '<li><strong>'.__('user_b:').'</strong> '.__('user_b').'</li>'.
              '<li><strong>'.__('user_c:').'</strong> '.__('user_c').'</li>'.
              '<li><strong>'.__('user_d:').'</strong> '.__('user_d').'</li>'.
              '<li><strong>'.__('user_e:').'</strong> '.__('user_e').'</li>'.
            '</ul>'.
          '</td>'.
        '</tr>'.
        '<tr>'.
          '<td>'.__('Role: Union').'</td>'.
          '<td>'.
            '<ul>'.
              '<li><strong>'.__('user_a:').'</strong> '.__('user_a, user_b, user_c, user_d').'</li>'.
              '<li><strong>'.__('user_b:').'</strong> '.__('user_a, user_b, user_c, user_d').'</li>'.
              '<li><strong>'.__('user_c:').'</strong> '.__('user_a, user_b, user_c, user_d').'</li>'.
              '<li><strong>'.__('user_d:').'</strong> '.__('user_a, user_b, user_c, user_d').'</li>'.
              '<li><strong>'.__('user_e:').'</strong> '.__('user_d, user_e').'</li>'.
            '</ul>'.
          '</td>'.
        '</tr>'.
        '<tr>'.
          '<td>'.__('Role: Loose Intersect').'</td>'.
          '<td>'.
            '<ul>'.
              '<li><strong>'.__('user_a:').'</strong> '.__('user_a, user_b, user_c, user_d').'</li>'.
              '<li><strong>'.__('user_b:').'</strong> '.__('user_b, user_c, user_d').'</li>'.
              '<li><strong>'.__('user_c:').'</strong> '.__('user_b, user_c, user_d').'</li>'.
              '<li><strong>'.__('user_d:').'</strong> '.__('user_d').'</li>'.
              '<li><strong>'.__('user_e:').'</strong> '.__('user_d, user_e').'</li>'.
            '</ul>'.
          '</td>'.
        '</tr>'.
        '<tr>'.
          '<td>'.__('Role: Strict Intersect').'</td>'.
          '<td>'.
            '<ul>'.
              '<li><strong>'.__('user_a:').'</strong> '.__('user_a').'</li>'.
              '<li><strong>'.__('user_b:').'</strong> '.__('user_b, user_c').'</li>'.
              '<li><strong>'.__('user_c:').'</strong> '.__('user_b, user_c').'</li>'.
              '<li><strong>'.__('user_d:').'</strong> '.__('user_d').'</li>'.
              '<li><strong>'.__('user_e:').'</strong> '.__('user_e').'</li>'.
            '</ul>'.
          '</td>'.
        '</tr>'.
      '</tbody>'.
    '</table>';
}

function sm_options_page() {
  ?>
  <div class="wrap">
    <h1>Separate Media</h1>
    <form action='options.php' method='post'>

      <?php
      settings_fields('pluginPage');
      do_settings_sections('pluginPage');
      submit_button();
      ?>

    </form>
  </div>
  <?php
}

?>
