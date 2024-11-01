<?php

class SkutumConfig{
    public static $config = array(
        'apiKey'=>'',
        'apiUrl'=>'',
        'recordLiveTime'=>2419200,
        'globalDebug'=>false,
    );

    /**
     * @return mixed
     */
    public static function getConfigParam($offset,$default)
    {
        return isset(self::$config[$offset])?self::$config[$offset]:$default;
    }

}