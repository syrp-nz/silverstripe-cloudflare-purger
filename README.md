# silverstripe-cloudflare-purger
Simple Silverstripe plugin to automatically purge pages from Cloudflare when a Page or a related DataObject is published/saved.

## Requirements
```
jamesryanbell/cloudflare: ^1.7
silverstripe/cms: ^3.1
```

_NB_: The plugin has been tested against Silverstripe 3.4 but has not been toroughly tested against all versions of Silverstripe 3.x. It should in theory work with all sub-version of Silversrtipe 3. If you come accross an issue using an older version of Silverstripe 3, log an issue or do a PR and I'll look into it.

## Installation
```
composer require syrp-nz/silverstripe-cloudlfare-purger "^0.0"
```

## Configuration
This plugin can be configured via a YML file or via the Site Configuration page. The YML configuration will have precedence over the Site Configuration. You can use a combination of both if you want.

The following configuration options are available:
* `Email` which should be your Cloudflare account's email address.
* `AuthKey` which is your Cloudflare account's AuthKey (can be obtain under _My Settings > API Key_)
* `ZoneIdentifier` which is the numeric identifier of the Zone to which your website domain belongs. There's not really any easy way of retrieving this in Cloudflare without using the API. This will appear as a user friendly drop down in your site configuration page.
* `Paths` which should be an array of absolute paths where your website can be accessed. If your website is only available under one path, you can leave this value blank.
* `HideSiteConfig` is an optional boolean value you can define, if you want to configure all Cloudflare settings in a YML file. Setting this value to `true` will hide the Cloudflare tab from your site configuration.

### Sample YML file
```YML
CloudflarePurger:
  Email: you@example.com
  AuthKey: yourhexadecimalauthkey
  ZoneIdentifier: yourhexadecimalzoneid
  HideSiteConfig: true
  Paths:
  - "https://example.com/"
  - "http://example.com/"
  - "https://www.example.com/"
  - "http://www.example.com/"
```

### Using the site config
All the parameters can be defined in your site configuration page without the need to edit any YML file.

If some values are defined via the YML file and the `HideSiteConfig` flag is undefined, those values will appear as readonly fields in your Site Configuration.

If your concerned about your `AuthKey` key being visible in the site configuration, make sure to set the `HideSiteConfig` flag to true.

## How does the plugin work
The plugin defines a `CloudflarePurgerExtension` DataExtension.This extension can be applied to any DataObject class. It will automatically be applied to the `SiteTree` class.

If the DataObject supports versionning, a call will be made to the Cloudflare API to attempt to purge the the URLs associated to this object. If the DataObject doesn't support versionning, the purge call will occur after each write.

The DataObject can expose a `CloudflarePurgeLinks()` method to specify which URLs should be purged. This method can return either a single URL or array of URLs. The URLs should be relative to the site root.

Otherwise, the `Link()` method will be called on your DataObject.

### Applying `CloudflarePurgerExtension` to a DataObject
If you have a Page type that relies on child DataObject for their content or if your DataObject is accessible via it's own URL, you should make sure to implement the `CloudflarePurgerExtension` on them.

You can do so via a YML file.

```YML
PromoDataObject:
  extensions:
    - CloudFlarePurgerExtension
```

Here's how you could customise the URLs that get purge when the `PromoDataObject` get saved.

```php
class PromoDataObject extends DataObject {

    private static $db = [
        'SomeContent' => 'HTMLText'
    ];

    private static $has_one = [
        'Parent' => 'PromoHolderPage'
    ];

    public function Link()
    {
        // This DO can be access as a sub-action on the controller of its parent page through its ID.
        // If `CloudflarePurgeLinks` wasn't define, the individual URL of this DO would be purge, but not its parent.
        return $this->Parent()->Link($this->ID);
    }

    public function CloudflarePurgeLinks()
    {
        // The content of this DO is used when rendering the parent page. So when this DO is save, we want to purge the parent's page URL as well.
        return [$this->Link(), $this->Parent()->Link()];
    }

}

```
