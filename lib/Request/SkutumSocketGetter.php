<?php

class SkutumSocketGetter {
    public static function post_async($url, $params, $port = 80)
    {
        foreach ($params as $key => &$val) {
            if (is_array($val)) $val = implode(',', $val);
            $post_params[] = $key.'='.urlencode($val);
        }
        if (substr($url, -1) !== '/'){
            $url .= '/';
        }
        $post_string = implode('&', $post_params);
        $parts = parse_url($url);
        $host = $parts['host'];
        if (strpos($url,'https://')>-1){
            $fp = fsockopen("ssl://".$parts['host'],
                443,
                $errno, $errstr, 1);
        } else {
            $fp = fsockopen($parts['host'],
                80,
                $errno, $errstr, 1);
        }
        $out = "POST ".$parts['path']." HTTP/1.1\r\n";
        $out.= "Host: ".$host."\r\n";
        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out.= "Content-Length: ".strlen($post_string)."\r\n";
        $out.= "Connection: Close\r\n\r\n";
        if (isset($post_string)) $out.= $post_string;
        if ($fp){
            stream_set_blocking ( $fp, false );
            stream_set_timeout($fp, 1);
            fwrite($fp, $out);
            fclose($fp);
        }
        $fp = null;
    }
}