<?php

use Cloudflare\Zone;
use Cloudflare\Zone\Cache;

/**
 * Simple class to purge URL from CloudFlare
 */
class CloudflarePurger extends Object
{

    /**
     * Get the Cloudflare email address that will be used to call Cloudflare.
     *
     * This can define in the YML config on the site config.
     * @return string
     */
    public static function getEmail()
    {
        return self::getConfigValue('Email');
    }

    /**
     * Get the Cloudflare Auth Key that will be used to call Cloudflare.
     *
     * This can define in the YML config on the site config.
     * @return string
     */
    public static function getAuthKey()
    {
        return self::getConfigValue('AuthKey');
    }

    /**
     * Get the Zone Identifier that will be used to call Cloudflare.
     *
     * This can define in the YML config on the site config.
     * @return string
     */
    public static function getZoneIdentifier()
    {
        return self::getConfigValue('ZoneIdentifier');
    }

    /**
     * Get the Paths that will be cleared when a page is saved. If your site is available under many address (e.g.: _http://example.com, https://example.com, http://www.example.com) you should specified all of those to make sure
     * all versions get cleared.)
     *
     * If left blank, it will default to the URL currently being used to access the site.
     *
     * This can define in the YML config on the site config.
     * @return string[]
     */
    public static function getPaths()
    {
        // Get the path value
        $paths = self::getConfigValue('Paths');

        // We don't explicitely define a path, return an mepty array.
        if (!$paths) {
            return [];
        }

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
        // YML
        $config = self::config();
        if ($config->$valueName) {
            return $config->$valueName;
        }

        // Site Config
        if (!$config->HideSiteConfig) {
            $siteConfig = SiteConfig::current_site_config();
            $siteconfigValueName = 'Cloudflare' . $valueName;
            if ($siteConfig->$siteconfigValueName) {
                return $siteConfig->$siteconfigValueName;
            }
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

            // If Cloudflare send an error back to us.
            if (!$response->success || !isset($response->result)) {
                if ($error = $response->errors[0]) {
                    $message = $error->message;
                    $code = $error->code;
                }
                throw new Exception($message, $code);
            }

            // Store the zone in a ID => domain format
            foreach ($response->result as $zone) {
                $zones[$zone->id] = $zone->name;
            }
        }

        return $zones;
    }

    /**
     * Purge the provided links from the Cloudflare cache.
     * @param  array $links
     */
    public static function purge($links)
    {
        // $links should be an array.
        if (!is_array($links)) {
            SS_Log::log("CloudflarePurger::purge() expects an array of links as parameter.", SS_Log::WARNING);
            return;
        }

        // Get Creds
        $email = self::getEmail();
        $authKey = self::getAuthKey();
        $zoneId = self::getZoneIdentifier();

        // If we have creads
        if ($email && $authKey && $zoneId) {
            $zoneClient = new Cache($email, $authKey);

            // Build a absolute list of links.
            $purgeList = self::buildPurgeList($links);

            // Do the purging.
            $zoneClient->purge_files($zoneId, $purgeList);
        }

    }

    /**
     * Received a list of relative links to the site root. BUilds a list of absolute links that can be sent back to Cloudfalre.
     * @param  string[] $links
     * @return string[]
     */
    protected static function buildPurgeList($links) {
        $list = [];

        // Get Path to apply to links
        $paths = self::getPaths();

        if (empty($paths)) {
            // Default to the site's absolute path if our list is empty.
            $paths = [Director::absoluteBaseURL()];
        }

        // Loop over all the absolute site path
        foreach ($paths as $path) {
            // Loop over all the relative links.
            foreach ($links as $link) {
                $list[] = $path . ltrim($link, '/');
            }
        }

        return $list;
    }


}
