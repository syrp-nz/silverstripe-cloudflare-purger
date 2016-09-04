<?php

/**
 * This Extension can be applied to the SiteConfig to specify the CLoudflare settings. You may also specifiy the Cloudflare settings via a YML file as config element on the CloudflarePurge class.
 *
 * The following properties can be defined: CloudflareEmail, CloudflareAuthKey, CloudflareZoneIdentifier,
 * CloudflarePaths.
 *
 * The YML file config has precedence over the site config. When a value is speciifed via YML, it will be displayed as a
 * readonly value in the site config.
 */
class CloudflareSiteConfigExtension extends DataExtension
{

    /**
     * New DB field for the Cloudflare field.
     * @var [type]
     */
    private static $db = [
        'CloudflareEmail' => 'VarChar(255)',
        'CloudflareAuthKey' => 'VarChar(255)',
        'CloudflareZoneIdentifier' => 'VarChar(255)',
        'CloudflarePaths' => 'Text',
    ];

    /**
     * Update the CMS Fields with our Custom CLoudflare fields.
     * @param FieldList $fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        // Get the CloudflarePurger config
        $config = CloudflarePurger::config();

        // If HideSiteConfig flag is set hide all the Cloudflare tab from the site config.
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
            // If Zones can not be retrieved, show an error message
            $zone->setError('Could not fetch the Zone list from Cloudflare.', 'error');
        }

        if ($zoneList) {
            // Set the source of the Zone Field.
            $zone->setSource($zoneList)->setEmptyString('(Choose the Zone where the cache will be purge)');
        } else {
            // If we got an empty ZOne list, we probably don't have complete or valid credentials.
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

        // Add all our fields to our Cloudflare Tab
        $fields->addFieldsToTab('Root.CloudflarePurger', [
            $email,
            $key,
            $zone,
            $paths
        ]);

        return $fields;
    }


}
