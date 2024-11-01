<div class="wrap scutum-admin-page">
<h2 class="btnd-title">Skutum Settings</h2>
    <div class="skutum-settings__submit_button skutum-test-btn">
        <p class="submit">
            <input type="submit" name="submit_test" id="submit_test" class="button button-primary" value="Start Compatibility Testing">
        </p>
    </div>
<div class="skutum-settings">
    <form method="post" action="options.php">
        <?php settings_fields( 'skutum-settings-group' ); ?>
        <?php do_settings_sections( 'skutum-settings-group' ); ?>
        <div class="skutum-settings__input-wrapper">
            <label class="skutum-settings__label" for="skutum_site_key">Skutum Api Key</label>
            <input class="skutum-settings__input" type="text" id="skutum_site_key" name="skutum_site_key" value="<?php echo esc_attr( get_option('skutum_site_key') ); ?>" />
        </div>
        <div class="skutum-settings__submit_button">
            <?php submit_button(); ?>
        </div>
    </form>
</div>

</div>