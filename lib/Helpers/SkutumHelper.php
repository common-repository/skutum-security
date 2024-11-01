<?php

class SkutumHelper{
    public static function recursiveChecker($maxtime,$start_time, $processor, $worker_file, $limit, $type){
        if ((time()- $start_time) > ($maxtime)){
            unlink($worker_file);
            $params['skutum'] = true;
            $params['type'] = $type;
            $params['action'] = 'skutum_queue_worker_async';
            $params['salt'] = 'AF81D3B6ABF0F19DFA288870EF1080DF';
            $params['rand'] = time();
            SkutumSocketGetter::post_async( plugins_url( 'skutum-wp/webhook/').time().'/'.md5(rand(0,999)).'/',$params);
            exit;
        }
        $queue_entry = $processor->dbFileWorker->getQueue($limit,$type);
        $request = self::packetFormer($queue_entry);
        if(count ($queue_entry)>0 && $request){
            switch ($type):
                case 'actions':
                    $result = $processor->actions($request);
                    break;
                case 'test-actions':
                    $result = $processor->actions($request);
                    break;
                case 'session':
                    $result = $processor->session($request);
                    break;
                case 'test-session':
                    $result = $processor->session($request);
                    break;
                default:
                    $result = $processor->pages($request);
                    break;
            endswitch;

            if (($type == 'page') || ($type == 'test-page') || $result){
                $processor->dbFileWorker->processQueue(count ($queue_entry),$type);
            }
            self::recursiveChecker($maxtime,$start_time,$processor,$worker_file,$limit,$type);
        } else {
            unlink($worker_file);
            exit;

        }
    }
    public static function packetFormer($result){
        $packet = array();
        foreach ($result as $item){
            if(isset($item["request"])){
                $data = stripslashes($item["request"]);
                $packet []= json_decode($data,true);
            }
        }
        return json_encode($packet);
    }
    public static function getIPv4(){
        $proxy_header = "HTTP_X_FORWARDED_FOR";
        if (array_key_exists ($proxy_header, $_SERVER)) {
            $proxy_list = explode (",", $_SERVER[$proxy_header]);
            $client_ip = false;
            foreach ($proxy_list as $item){
                $ip = trim ($item);
                if (filter_var ($ip, FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)) {
                    $client_ip = $ip;
                }
            }
            if ($client_ip) {
                return $client_ip;
            }
        }
        if (!isset ($_SERVER['REMOTE_ADDR'])) {
            return NULL;
        }
        return $_SERVER['REMOTE_ADDR'];
    }
    public static function validateXid($xid){
        $pattern = '/^[a-zA-Z\d]*$/';
        return (preg_match($pattern, $xid))?$xid:"nil";
    }
    public static function validateXat($xat){
        $pattern = '/^[\p{L}\p{Z}\p{M}\p{N}\p{C}\p{P}\p{S}]*$/';
        return (preg_match($pattern, $xat))?$xat:"nil";
    }
}