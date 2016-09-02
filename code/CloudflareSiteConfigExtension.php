<?php

class CloudflareSiteConfigExtension extends DataExtension
{

    private static $db = [
        'CloudflareEmail' => 'VarChar(255)',
        'CloudflareAuthKey' => 'VarChar(255)',
        'CloudflareZoneIdentifier' => 'VarChar(255)',
        'CloudflarePaths' => 'Text',
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        // Get the config
        $config = CloudflarePurger::config();
        if ($config->HideSiteConfig) {
            return;
        }

        // Get the parent object
        $owner = $this->owner;

        // Set up the Email field
        $email = EmailField::create('CloudflareEmail', 'Cloudflare Email');
        if ($config->Email) {
            $email = $email->performReadonlyTransformation();
            $owner->CloudflareEmail = $config->Email;
        }

        // Set up the Auth Key field
        $key = TextField::create('CloudflareAuthKey', 'Cloudflare Auth Key');
        if ($config->AuthKey) {
            $key = $key->performReadonlyTransformation();
            $owner->CloudflareAuthKey = $config->AuthKey;
        }

        // Set up the Zone identifier field
        $zone = DropdownField::create('CloudflareZoneIdentifier', 'Cloudflare Zone');
        $zoneList = [];
        try {
            $zoneList = CloudflarePurger::getZones();
        } catch (Exception $ex) {
            $zone->setError('Could not fetch the Zone list from Cloudflare.', 'error');
        }
        if ($zoneList) {
            $zone->setSource($zoneList)->setEmptyString('(Choose the Zone where the cache will be purge)');
        } else {
            $zone->setEmptyString('(You must provide valid credentials to select a Zone.)');
        }
        if ($config->ZoneIdentifier) {
            $zone = $zone->performReadonlyTransformation();
            $owner->CloudflareZoneIdentifier = $config->ZoneIdentifier;
        }

        // Set up the Paths Field.
        $paths = TextareaField::create('CloudflarePaths', 'Cloudflare Paths');
        if ($config->Paths) {
            $paths = $paths->performReadonlyTransformation();
            $owner->CloudflarePaths = implode(", ", $config->Paths);
        } else {
            $paths->setRightTitle('If this site is available under multiple paths, you can specify each one on a different line. If you only use one path to reach the site, you can leave this field blank.');
        }




        $fields->addFieldsToTab('Root.CloudflarePurger', [
            $email,
            $key,
            $zone,
            $paths
        ]);

        return $fields;
    }


}
