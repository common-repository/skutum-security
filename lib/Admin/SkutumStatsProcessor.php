<?php

class SkutumStatsProcessor
{

    public $apiClient;
    public $availableRanges = array('month','week','day','halfday','now');
    public $availableLines = array('alltraffic','seo-count','parser-count','human-count','captcha-count');
    public $availablePies = array('parser-seo-human','parser-by-countries','parser-by-organizations');

    public function __construct()
    {
        $this->apiClient = new SkutumApiClient();
    }
    public function processStatsRequest($post){

        $range  = (isset($post['range'])&& ctype_alpha($post['range']))? sanitize_text_field( $post['range'] ):'month';
        if (!in_array($range,$this->availableRanges)){
            $range = 'month';
        }
        $line  = isset($post['line'])?sanitize_text_field( $post['line'] ):'alltraffic';
        if (!in_array($line,$this->availableLines)){
            $line = 'alltraffic';
        }
        $pie  = isset($post['pie'])?sanitize_text_field( $post['pie'] ):'parser-seo-human';
        if (!in_array($pie,$this->availablePies)){
            $pie = 'parser-seo-human';
        }
        $pieData = array();
        $listData = array();
        $cacheTime = ($range=='now')?1:300;
        $pieData = $this->processPieChart($pie,$range,$cacheTime);
        $lineData = $this->processLineChart($line,$range,$cacheTime);
        $listData = $this->processList($range,$cacheTime);


        $result = array(
            'pie'=> $pieData,
            'line' => $lineData,
            'list' => $listData,
            'range'=>$range,
        );

        return json_encode($result);

    }
    public function processStatsRequestPie($post){
        $range  = (isset($post['range'])&& ctype_alpha($post['range']))?sanitize_text_field( $post['range'] ):'month';
        if (!in_array($range,$this->availableRanges)){
            $range = 'month';
        }
        $pie  = isset($post['pie'])?sanitize_text_field( $post['pie'] ):'parser-seo-human';
        if (!in_array($pie,$this->availablePies)){
            $pie = 'parser-seo-human';
        }
        $cacheTime = ($range=='now')?1:300;
        $pieData = $this->processPieChart($pie,$range,$cacheTime);
        $result = array(
            'pie'=> $pieData,
            'range'=>$range,
        );

        return json_encode($result);

    }
    public function processStatsRequestLine($post){

        $range  = (isset($post['range'])&& ctype_alpha($post['range']))?sanitize_text_field( $post['range'] ):'month';
        if (!in_array($range,$this->availableRanges)){
            $range = 'month';
        }
        $line  = isset($post['line'])?sanitize_text_field( $post['line'] ):'alltraffic';
        if (!in_array($line,$this->availableLines)){
            $line = 'alltraffic';
        }
        $cacheTime = ($range=='now')?1:300;
        $lineData = $this->processLineChart($line,$range,$cacheTime);
        $result = array(
            'line' => $lineData,
            'range'=>$range,
        );
        return json_encode($result);
    }


    public function processLineChart($line,$range,$cacheTime)
    {
        $cache = new SkutumCache();
        $logger = new SkutumErrorLogger();
        $pointLength = 25920;
        switch ($range):
            case "month":
                $pointLength = 25920;
                break;
            case "week":
                $pointLength = 6000;
                break;
            case "day":
                $pointLength = 864;
                break;
            case "halfday":
                $pointLength = 432;
                break;
            case "now":
                $pointLength = 3;
                break;
            default:
                $pointLength = 25920;
                break;
        endswitch;
        if(!$cache->get($line.'-'.$range, $lineData))
        {
            $lineDataresult = $this->apiClient->statsLinechart($line,$range);
            $arrResult = json_decode($lineDataresult,true);

            $lineData = array();
            if(isset($arrResult['status'])&& $arrResult['status']=='ok'){
                if (isset($arrResult['data'])){

                    $data = $arrResult['data'];
                    $timeLast = isset($data['generatedTime'])?$data['generatedTime']:time();
                    $points = isset($data['values'])?$data['values']:'';
                    $tmp_time = $timeLast;
                    foreach ($points as $point){

                        $lineData[]=array(
                            't' => $tmp_time * 1000,
                            'y' => $point
                        );
                        $tmp_time = $tmp_time - $pointLength;
                    }

                    $cache->set($line.'-'.$range, $lineData, $cacheTime);

                } else if(isset($arrResult['errmessage'])){
                    $logger->log('Invalid statistics linechart response (errormessage) : ' . $arrResult['errmessage'] );
                } else {
                    $logger->log('Invalid statistics linechart response (no data or errormessage found) : ' . $lineDataresult );
                }
            } else {
                $logger->log('Invalid statistics linechart response : ' . $lineDataresult );
            }

        }

        $cache->saveCache();

        return $lineData;

    }

    public function processPieChart($pie,$range,$cacheTime)
    {
        $cache = new SkutumCache();
        $logger = new SkutumErrorLogger();
        if (!$cache->get($pie . '-' . $range, $pieData)) {
            $pieDataresult = $this->apiClient->statsPiechart($pie, $range);
            $arrResult = json_decode($pieDataresult, true);

            $pieData = array(
                'lables'=>array(),
                'values'=>array()
            );

            if (isset($arrResult['status']) && $arrResult['status'] == 'ok') {
                if (isset($arrResult['data'])) {

                    $data = json_decode($arrResult['data'],true);
                    foreach ($data as $point){
                        $pieData['lables'][] = $point['name'];
                        $pieData['values'][] = $point['count'];
                    }

                    $cache->set($pie . '-' . $range, $pieData, $cacheTime);

                } else if (isset($arrResult['errmessage'])) {
                    $logger->log('Invalid statistics piechart response (errormessage) : ' . $arrResult['errmessage']);
                } else {
                    $logger->log('Invalid statistics piechart response (no data or errormessage found) : ' . $pieDataresult);
                }
            } else {
                $logger->log('Invalid statistics piechart response : ' . $pieDataresult);
            }

        }

        $cache->saveCache();

        return $pieData;

    }
    public function processList($range,$cacheTime)
    {
        $cache = new SkutumCache();
        $logger = new SkutumErrorLogger();
        if (!$cache->get('ip-list' . '-' . $range, $listData)) {
            $listDataresult = $this->apiClient->statsListParserTopIps( $range);
            $arrResult = json_decode($listDataresult, true);

            $listData = array();

            if (isset($arrResult['status']) && $arrResult['status'] == 'ok') {
                if (isset($arrResult['data'])) {

                    $data = $arrResult['data'];
                    $listData = $data;

                    $cache->set('ip-list' . '-' . $range, $listData, $cacheTime);

                } else if (isset($arrResult['errmessage'])) {
                    $logger->log('Invalid statistics list response (errormessage) : ' . $arrResult['errmessage']);
                } else {
                    $logger->log('Invalid statistics list response (no data or errormessage found) : ' . $listDataresult);
                }
            } else {
                $logger->log('Invalid statistics list response : ' . $listDataresult);
            }

        }

        $cache->saveCache();

        return $listData;

    }

}