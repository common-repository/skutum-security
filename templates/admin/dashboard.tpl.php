<?php
    $skutumHelper = new SkutumHelper();
    $skutumRequestsTotal = get_option('skutum_total_requests', 0 );
    $skutumRequestsAvailable = get_option('skutum_available_requests', 10000 );
    if ($skutumRequestsAvailable == 0 || $skutumRequestsAvailable == '0'){
        $skutumRequestsAvailable = 'unlimited';
    }
?>
<div class="wrap scutum-admin-page">
    <div class="btnd-dashboard">
        <h2 class="btnd-title">Dashboard</h2>
        <div class="skutum-small-wrappers">
            <div class="skutum-small__wrapper">
                <h3 class="skutum-subtitle">Current plugin's work mode:
                    <span class="skutum-plugin-update-status">
                        <svg width="12" height="16" viewBox="0 0 12 16" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M6 3.75V0.75L2.25 4.5L6 8.25V5.25C8.4825 5.25 10.5 7.2675 10.5 9.75C10.5 12.2325 8.4825 14.25 6 14.25C3.5175 14.25 1.5 12.2325 1.5 9.75H0C0 13.065 2.685 15.75 6 15.75C9.315 15.75 12 13.065 12 9.75C12 6.435 9.315 3.75 6 3.75Z" fill="#5A8DFF"/>
</svg>
                    </span>
                </h3>
                <div class="skutum-small__wrapper__content">
                    <?php
                $status = get_option( SkutumMain::$_statusOptionName, 'false' );
                switch ($status){
                    case '3':
                        echo "<div class='skutum-mode__statistics'>statistics mode</div>";
                        break;
                    case '4':
                        echo "<div class='skutum-mode__learning'>learning mode</div>";
                        break;
                    case '0':
                        echo "<div class='skutum-mode__drop'>drop mode</div>";
                        break;
                    case '1':
                        echo "<div class='skutum-mode__incompatible'>no connection</div>";
                        break;
                    case '2':
                        echo "<div class='skutum-mode__incompatible'>limit reached</div>";
                        break;

                    case '5':
                        echo "<div class='skutum-mode__incompatible'>incompatible</div>";
                        break;
                    default:
                        echo "<div class='skutum-mode__statistics'>statistics mode</div>";
                        break;
                }
                ?>
                </div>
            </div>
            <?php if ($status == '3' || $status == '0'){?>
                <div class="skutum-small__wrapper">
                    <h3 class="skutum-subtitle"> Drop Mode:</h3>
                    <div class="skutum-small__wrapper__content">
                        <?php
                        $need_to_check = !!!get_option('skutum_gr_is_valid');
                            if (!$need_to_check){?>
                                <input type="checkbox" id="skutum-swith-dropmode" name="set-name" <?php if ( $status == '0'){?>checked<?php } ?> class="skutum-switch-input">
                                <label for="skutum-swith-dropmode" class="skutum-switch-label"></label>
                    <?php
                        } else {
                                ?>
                                <div class="skutum-warning">To use drop mode you need to validate your capthcha keys in <a href="<?php echo get_admin_url().'admin.php?page=skutum-captcha-settings';?>">capthca settings</a></div>
                            <?php }
                    ?>


                        </div>
                </div>
            <?php } ?>
            <div class="skutum-small__wrapper">
                <h3 class="skutum-subtitle"> Requests (total/available):</h3>
                <div class="skutum-small__wrapper__content">
                    <?php echo $skutumRequestsTotal;?>/<?php echo $skutumRequestsAvailable;?>
                </div>
            </div>

            <div class="skutum-small__wrapper">
                <h3 class="skutum-subtitle"> Current Plan:</h3>
                <div class="skutum-small__wrapper__content">
                    Testing Plan
                </div>
            </div>
        </div>




        <?php
            if (get_option( SkutumMain::$_statusOptionName, 'false' ) !== '4' && get_option( SkutumMain::$_statusOptionName, 'false' ) !== '5'){
        ?>
        <h2 class="btnd-title">Statistics</h2>
        <div class="btnd-tatistics__wrapper">
            <div class="btnd-dasboard-range-tabs">
                <input  class="btnd-dasboard-tab" type="radio" id="btnd-tab-now" value="now" name="range-tab">
                <label class="btnd-tab--first" for="btnd-tab-now">Now</label>
                <input  class="btnd-dasboard-tab" type="radio" id="btnd-tab-halfday" value="halfday" name="range-tab">
                <label for="btnd-tab-halfday">Half Day</label>
                <input  class="btnd-dasboard-tab" type="radio" id="btnd-tab-day" value="day" name="range-tab">
                <label for="btnd-tab-day">Day</label>
                <input  class="btnd-dasboard-tab" type="radio" id="btnd-tab-week" value="week" name="range-tab">
                <label for="btnd-tab-week">Week</label>
                <input  class="btnd-dasboard-tab" type="radio" id="btnd-tab-month" value="month" name="range-tab" checked>
                <label class="btnd-tab--last" for="btnd-tab-month">Month</label>
            </div>
            <div class="btnd-content-container">
                <div class="btnd-piechart">
                    <!--         <h3>Pie Chart</h3>-->
                    <div class="custom-select-wrapper" id="btnd-piechart-type">
                        <div class="custom-select">
                            <div class="custom-select__trigger"><span>Client Types</span>
                                <div class="custom-select__arrow"></div>
                            </div>
                            <div class="custom-options">
                                <span class="custom-option selected" data-value="parser-seo-human">Client Types</span>
                                <span class="custom-option" data-value="parser-by-countries">Parsers by Countries</span>
                                <span class="custom-option" data-value="parser-by-organizations">Parsers by Organizations</span>
                            </div>
                        </div>
                    </div>

                    <div class="graph-content piechart">
                        <canvas id="pie-chart"></canvas>
                    </div>
                </div>
                <div class="btnd-linechart">
                    <!--    <h3>Line Chart</h3>-->
                    <div class="custom-select-wrapper" id="btnd-linechart-type">
                        <div class="custom-select">
                            <div class="custom-select__trigger"><span>All Traffic</span>
                                <div class="custom-select__arrow"></div>
                            </div>
                            <div class="custom-options">
                                <span class="custom-option selected" data-value="alltraffic">All Traffic</span>
                                <span class="custom-option" data-value="seo-count">Seo Bots</span>
                                <span class="custom-option" data-value="parser-count">Parsers</span>
                                <span class="custom-option" data-value="human-count">Humans</span>
                                <span class="custom-option" data-value="captcha-count">Captha Hits</span>
                            </div>
                        </div>
                    </div>

                    <div class="graph-content">
                        <canvas id="line-chart"></canvas>
                    </div>
                </div>
            </div>
            <div class="skutum-stats-loader" style="display: none;">
                <div class="skutum-stats-loader-container">
                    <div class="skutum-spinner"></div>
                </div>

            </div>
        </div>
        <h2 class="btnd-title">Top Parsers IPs</h2>
        <div class="btnd-list">
            <div class="btnd-list-content" id="btnd-list-content">
            </div>
            <div class="skutum-stats-loader" style="display: none;">
                <div class="skutum-stats-loader-container">
                    <div class="skutum-spinner"></div>
                </div>

            </div>
        </div>
        <?php
           }
        ?>
    </div>
</div>
