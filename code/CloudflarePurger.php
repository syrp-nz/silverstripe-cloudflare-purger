<?php

use Cloudflare\Zone;
use Cloudflare\Zone\Cache;

class CloudflarePurger extends Object
{


    public static function getEmail()
    {
        return self::getConfigValue('Email');
    }

    public static function getAuthKey()
    {
        return self::getConfigValue('AuthKey');
    }

    public static function getZoneIdentifier()
    {
        return self::getConfigValue('ZoneIdentifier');
    }

    public static function getPaths()
    {
        // Get the path value
        $paths = self::getConfigValue('Paths');

        // Make sure we're workign with an array
        if (!is_array($paths)) {
            $paths = explode("\n",$paths);
        }

        // Clean up all path and and make sure they have a trailing slash
        foreach ($paths as &$path) {
            $path = trim($path);
            if (substr("$path", -1) != '/') {
                $path .= '/';
            }
        }

        return $paths;
    }

    /**
     * Try to retrieve a config option from the YAML config. Otherwise, try to get it from the SiteConfig.
     * @param  string $valueName
     * @return mixed
     */
    protected static function getConfigValue($valueName)
    {
        $config = self::config();
        if ($config->$valueName) {
            return $config->$valueName;
        }

        $siteConfig = SiteConfig::current_site_config();
        $siteconfigValueName = 'Cloudflare' . $valueName;
        if ($siteConfig->$siteconfigValueName) {
            return $siteConfig->$siteconfigValueName;
        }

        return '';
    }

    /**
     * Fetch the list of Zones from CloudFlare.
     * @throws Exception If credentials are provided, but zones can not be retrieved from Cloudflare.
     * @return array Array of Zones where the key is the Zone Identifier in Cloudflare and the value is the domain name.
     */
    public static function getZones()
    {
        $zones = [];

        // Get Creds
        $email = self::getEmail();
        $authKey = self::getAuthKey();

        // If have credentials lets try to get some a values.
        if ($email && $authKey) {
            $zoneClient = new Zone($email, $authKey);
            $response = $zoneClient->zones();
            if (!$response->success || !isset($response->result)) {
                if ($error = $response->errors[0]) {
                    $message = $error->message;
                    $code = $error->code;
                }
                throw new Exception($message, $code);
            }
            foreach ($response->result as $zone) {
                $zones[$zone->id] = $zone->name;
            }
        }

        return $zones;
    }

    /**
     * Purge the provided links
     * @param  array $links
     */
    public static function purge($links)
    {
        if (!is_array($links)) {
            SS_Log::log("CloudflarePurger::purge() expects an array of links as parameter.", SS_Log::NOTICE);
            return;
        }

        // Get Creds
        $email = self::getEmail();
        $authKey = self::getAuthKey();
        $zoneId = self::getZoneIdentifier();

        if ($email && $authKey && $zoneId) {
            $zoneClient = new Cache($email, $authKey);
            $purgeList = self::buildPurgeList($links);
            var_dump($purgeList);die();
            $zoneClient->purge_files($zoneId, $purgeList);
        }

    }

    protected static function buildPurgeList($links) {
        $list = [];

        // Get Path to apply to links
        $paths = self::getPaths();
        if (empty($paths)) {
            $paths = [Director::absoluteBaseURL()];
        }

        foreach ($paths as $path) {
            foreach ($links as $link) {
                $list[] = $path . $link;
            }
        }

        return $list;

    }


}
