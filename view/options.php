<?php
//must check that the user has the required capability
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// variables for the field and option names
$sg_uname = 'sg_uname';
$sg_pass = 'sg_pass';
$sg_api = 'sg_api';
$sg_port = 'sg_port';

$options = get_sendgrid_options();
if (!empty($_POST)) :

    foreach($_POST as $key => $value){
        if(key_exists($key, $options)){
            $options[$key] = strip_tags(trim($_POST[$key]));
        }
    }
    update_site_option('sendgrid_options', $options);
    ?>
    <div class="updated"><p><strong>Settings Updated</strong></p></div>
<?php endif; ?>

<div class="wrap">
    <div id="icon-options-general" class="icon32"><br></div>
    <h2>Big Mailer - SendGrid Settings</h2>

    <form id="bigmailer_settings" method="post">
        <h3>SendGrid Options</h3>
        <table class="form-table">
            <tbody><tr valign="top">
                    <th scope="row"><label for="<?php echo $sg_uname; ?>">Username:</label><br><br><span class="description"></span></th>
                    <td><input type="text" name="<?php echo $sg_uname; ?>" id="<?php echo $sg_uname; ?>" class="regular-text" value="<?php echo $options[$sg_uname]; ?>"></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="<?php echo $sg_pass; ?>">Password:</label><br><br><span class="description"></span></th>
                    <td><input type="password" name="<?php echo $sg_pass; ?>" id="<?php echo $sg_pass; ?>" class="regular-text" value="<?php echo $options[$sg_pass]; ?>" ></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label>Mail Transfer Protocol:</label><br><br><span class="description"></span></th>
                    <td>
                        <label for="sg-smtp"><input type="radio" id="sg-smtp" name="<?php echo $sg_api; ?>" value="SMTP" <?php checked($options[$sg_api], 'SMTP') ?>> SMTP <span class="description">(Recommended)</span></label>&nbsp;
                        <label for="sg-web"><input type="radio" id="sg-web" name="<?php echo $sg_api; ?>" value="WEB"  <?php checked($options[$sg_api], 'WEB') ?>> WEB</label>
                    </td>
                </tr>
                    <tr valign="top" id="bigmailer_port" <?php echo ($options[$sg_api] != 'SMTP')?'style="display:none;"':'';?>>
                        <th scope="row"><label>Port:</label><br><br><span class="description"></span></th>
                        <td>
                            <label for="sg-tls"><input type="radio" id="sg-tls" name="<?php echo $sg_port; ?>" id="sg-smtp"  value="TLS" <?php checked($options[$sg_port], 'TLS') ?>> TLS <span class="description">(port 587)</span></label>&nbsp;
                            <label for="sg-tls-alt"><input type="radio" id="sg-tls-alt" name="<?php echo $sg_port; ?>" id="sg-web"  value="TLS_ALTERNATIVE"  <?php checked($options[$sg_port], 'TLS_ALTERNATIVE') ?>> TLS Alternative <span class="description"> (port 20)</span></label>&nbsp;
                            <label for="sg-ssl"><input type="radio" id="sg-ssl" name="<?php echo $sg_port; ?>" id="sg-web"  value="SSL"  <?php checked($options[$sg_port], 'SSL') ?>> SSL <span class="description"> (port 465)</span></label>
                        </td>
                    </tr>

            </tbody></table>
        <p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
    </form>
</div>
