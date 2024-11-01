jQuery(document).ready(function($) {

    var SkutumDashboard = function(){
        var linechart = false;
        var piechart = false;
        var action = 'skutum_statistics';
        var linechartLabels = {
            'alltraffic' : "All Traffic",
            'seo-count' : "Seo Bots",
            'parser-count' : "Parsers",
            'human-count' : "Humans",
            'captcha-count' : "Captcha Hits"
        };
        var range = 'month';
        var linechartType = 'alltraffic';
        var piechartType = 'parser-seo-human';
        var timerId ;
        var wasNow = false;

        function addListeners() {
            $('.btnd-dasboard-tab').on('click',processControlsRange);

            $('body').on('click','.custom-select-wrapper', function() {
                this.querySelector('.custom-select').classList.toggle('open');
            });
            $('body').on('click', function(e) {
                if($('.custom-select')){
                    if (!$('.custom-select')[0].contains(e.target) && !$('.custom-select')[1].contains(e.target)) {
                        $('.custom-select').removeClass('open');
                    }
                }

            });

            $('.skutum-plugin-update-status').on('click',function () {
                makeRequestUpdateStatus();
            });

            $('.skutum-settings__submit_button.skutum-test-btn #submit_test').on('click',function () {
                makeRequestStartTesting();
            });

            $('#skutum-swith-dropmode').on('change',function () {
                var request = {
                    action: 'skutum_mode_switch',
                    mode: 3
                };
                if($('#skutum-swith-dropmode:checked').length >0){
                    request.mode = 0;
                }
                makeDropModeRequest(request);
            });

            $('#btnd-linechart-type .custom-option').on('click',function () {

                    if (!this.classList.contains('selected')) {
                        this.parentNode.querySelector('.custom-option.selected').classList.remove('selected');
                        this.classList.add('selected');
                        this.closest('.custom-select').querySelector('.custom-select__trigger span').textContent = this.textContent;
                        linechartType = $(this).data('value');
                        makeRequestLine(false);
                    }

            });

            $('#btnd-piechart-type .custom-option').on('click',function () {

                if (!this.classList.contains('selected')) {
                    this.parentNode.querySelector('.custom-option.selected').classList.remove('selected');
                    this.classList.add('selected');
                    this.closest('.custom-select').querySelector('.custom-select__trigger span').textContent = this.textContent;
                    piechartType = $(this).data('value');
                    makeRequestPie();
                }

            });

        }
        function showLoader() {
            $('.skutum-stats-loader').fadeIn('fast');
        }
        function hideLoader() {
            $('.skutum-stats-loader').fadeOut('fast');
        }
        function  makeDropModeRequest(request) {
            $.post( ajaxurl, request, function(response) {
                window.location.reload(true);
            });
        }
        function processControlsRange() {
            clearTimeout(timerId);
            range = $('input[name=range-tab]:checked').val();
            makeRequest();
        }

        function  makeRequestStartTesting() {
            var request = {
                action: 'skutum_start_testing',
            };
            $.post( ajaxurl, request, function(response) {
                alert('Request accepted. Compatibility of your current configuration will be tested by our system.');
                window.location.reload(true);
            });
        }
        function  makeRequestUpdateStatus() {
            var request = {
                action: 'skutum_update_status',
            };
            $.post( ajaxurl, request, function(response) {
                window.location.reload(true);

            });
        }
        function  makeRequestPie() {
            var request = {
                action: action+'_pie',
                pie: piechartType,
                range: range,
            };
            showLoader();
            $.post( ajaxurl, request, function(response) {
                var response_arr = JSON.parse(response);
                generatePieChart(response_arr['pie']);
                hideLoader();
            });
        }
        function  makeRequestLine(ping) {
            var request = {
                action: action+'_line',
                line: linechartType,
                range: range,
            };
            if( ping === false){showLoader()};
            $.post( ajaxurl, request, function(response) {
                var response_arr = JSON.parse(response);
                if (range == response_arr['range']){
                    generateLineChart(response_arr['line']);
                }
                if (range === 'now'){
                    timerId = setTimeout(function () {
                        makeRequestLine(true);
                    }, 2000);
                }
                hideLoader();
            });
        }
        function  makeRequest() {
            clearTimeout(timerId);
            var request = {
                action: action,
                line: linechartType,
                pie: piechartType,
                range: range,
            };
            showLoader();
            $.post( ajaxurl, request, function(response) {
                hideLoader();
                var response_arr = JSON.parse(response);
                if (range == response_arr['range']){
                    generateLineChart(response_arr['line']);
                    generatePieChart(response_arr['pie']);
                }
                drawIpList(response_arr['list']);
                if (range === 'now'){
                    timerId = setTimeout(function () {
                        makeRequestLine(true);
                    }, 2000);
                } else {
                    clearTimeout(timerId);
                }
                hideLoader();
            });
        }
        function drawIpList(data){
            data = JSON.parse(data);

            var list = '<table class="btnd-admin-table"><tr class="btnd-admin-table-heading"><td>Ip</td><td>Country</td><td>Organization</td><td>Count</td></tr>';

            $(data).each (function(){
                if (this['ipName'] !== undefined){
                    list +='<tr><td>';
                    list += this['ipName'];
                    list +='</td>';
                    list +='<td>';
                    list += this['country'];
                    list +='</td>';
                    list +='<td>';
                    list += this['org'];
                    list +='</td>';
                    list +='<td>';
                    list += this['count'];
                    list +='</td></tr>';
                }


            });
            list += '</table>';
            $("#btnd-list-content").html(list);
        }
        function generatePieChart(data) {
            var cfg = {
                type: 'pie',
                data: {
                    datasets: [{
                        data: data['values'],
                        backgroundColor: [
                            '#4049FF',
                            '#B140FF',
                            '#FF8743',
                            '#4393FF',
                            '#FF5DA0',
                            '#FFD56D',
                            '#1CCAB8',
                            '#FF9000',
                            '#6226EF',
                            '#FFBDD8',
                            '#080',
                            '#088',
                            '#808',
                            '#880',
                        ],
                        label: 'Dataset 1'
                    }],
                    labels: data['lables']
                },
                options: {
                    responsive: true,
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var allData = data.datasets[tooltipItem.datasetIndex].data;
                                var tooltipLabel = data.labels[tooltipItem.index];
                                var tooltipData = allData[tooltipItem.index];
                                var total = 0;
                                for (var i in allData) {
                                    total += allData[i];
                                }
                                var tooltipPercentage = Math.round((tooltipData / total) * 100);
                                return tooltipLabel + ': ' + tooltipData + ' (' + tooltipPercentage + '%)';
                            }
                        }
                    }
                }
            };

            var ctx = document.getElementById('pie-chart').getContext('2d');
            if (piechart) {
                piechart.destroy()
            }
            piechart = new Chart(ctx, cfg);

        }

        function generateLineChart(data) {
            var ctx = document.getElementById('line-chart').getContext('2d');
            ctx.canvas.width = 1000;
            ctx.canvas.height = 500;

            var color = Chart.helpers.color;
            var cfg = {
                data: {
                    datasets: [{
                        label: linechartLabels[linechartType],
                        backgroundColor: color('#4049FF').alpha(0.5).rgbString(),
                        borderColor: '#4049FF',
                        data: data,
                        type: 'line',
                        pointRadius: 0,
                        fill: false,
                        lineTension: 0,
                        borderWidth: 2
                    }]
                },
                options: {
                    animation: {
                        duration: 0
                    },
                    scales: {
                        xAxes: [{
                            type: 'time',
                            distribution: 'series',
                            offset: true,
                            ticks: {
                                major: {
                                    enabled: true,
                                    fontStyle: 'bold'
                                },
                                source: 'data',
                                autoSkip: true,
                                autoSkipPadding: 75,
                                maxRotation: 0,
                                sampleSize: 100
                            },
                            afterBuildTicks: function(scale, ticks) {
                                if (!ticks || ticks.length < 1){
                                    return ticks;
                                }
                                var majorUnit = scale._majorUnit;
                                var firstTick = ticks[0];
                                var i, ilen, val, tick, currMajor, lastMajor;

                                val = moment(ticks[0].value);
                                if ((majorUnit === 'minute' && val.second() === 0)
                                    || (majorUnit === 'hour' && val.minute() === 0)
                                    || (majorUnit === 'day' && val.hour() === 9)
                                    || (majorUnit === 'month' && val.date() <= 3 && val.isoWeekday() === 1)
                                    || (majorUnit === 'year' && val.month() === 0)) {
                                    firstTick.major = true;
                                } else {
                                    firstTick.major = false;
                                }
                                lastMajor = val.get(majorUnit);

                                for (i = 1, ilen = ticks.length; i < ilen; i++) {
                                    tick = ticks[i];
                                    val = moment(tick.value);
                                    currMajor = val.get(majorUnit);
                                    tick.major = currMajor !== lastMajor;
                                    lastMajor = currMajor;
                                }
                                return ticks;
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                drawBorder: false
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Hits'
                            }
                        }]
                    },
                    tooltips: {
                        intersect: false,
                        mode: 'index',
                        callbacks: {
                            label: function(tooltipItem, myData) {
                                var label = myData.datasets[tooltipItem.datasetIndex].label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += parseFloat(tooltipItem.value).toFixed(2);
                                return label;
                            }
                        }
                    }
                }
            };

            if (linechart) {
                linechart.destroy()
            }
            linechart = new Chart(ctx, cfg);

        }

        function init() {
            addListeners();
            processControlsRange();
        }
        init();

    };
    SkutumDashboard();
});