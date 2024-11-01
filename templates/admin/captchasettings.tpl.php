<div class="wrap scutum-admin-page">
<h2 class="btnd-title">Skutum Captcha Settings</h2>
<div class="skutum-settings">
    <?php
    $need_to_check = !!!get_option('skutum_gr_is_valid');
    ?>
    <?php if($need_to_check){?>
    <p>
        You have to <a href="https://www.google.com/recaptcha/admin" rel="external">register your domain</a> first, get required keys (reCAPTCHA V2) from Google and save them bellow.</p>

    </p>
    <?php }?>
    <form method="post" action="options.php">
        <?php settings_fields( 'skutum-captcha-settings-group' ); ?>
        <?php do_settings_sections( 'skutum-captcha-settings-group' ); ?>
        <div class="skutum-settings__input-wrapper">
            <label class="skutum-settings__label" for="skutum_gr_sitekey">Google ReCaptcha Site Key</label>
            <input class="skutum-settings__input" type="text" id="skutum_gr_sitekey" name="skutum_gr_sitekey" value="<?php echo esc_attr( get_option('skutum_gr_sitekey') ); ?>" />
        </div>
        <div class="skutum-settings__input-wrapper">
            <label class="skutum-settings__label" for="skutum_gr_secretkey">Google ReCaptcha Secret Key</label>
            <input class="skutum-settings__input"  type="text" id="skutum_gr_secretkey" name="skutum_gr_secretkey" value="<?php echo esc_attr( get_option('skutum_gr_secretkey') ); ?>" />
        </div>
        <div class="skutum-settings__submit_button">
            <?php submit_button(); ?>
        </div>
    </form>
    <?php if($need_to_check){?>
    <p>Entered keys were not checked or didn't pass the test. Please, be sure , captcha below displays correctly. To validate keys you need to pass the captha on this page. </p>
    <div>

        <form action="" method="post" id="captcha-form">
            <input type="hidden" name="scutum-action" value="admin-capthca-validation">
            <div class="g-recaptcha" data-sitekey="<?php echo get_option('skutum_gr_sitekey');?>" data-callback="skutum_captcha_callback"></div>
        </form>
        <script  type="text/javascript">
            var skutum_captcha_callback = function () {
                jQuery("#captcha-form").submit();
            }
        </script>

        <script src="https://www.google.com/recaptcha/api.js?hl=en"></script>

    </div>
    <?php } else {?>
        <p>Your capthca keys are valid. You can use drop mode. </p>
    <?php } ?>

</div>

</div>