# silverstripe-cloudflare-purger
Simple Silverstripe plugin to automatically purge pages from Cloudflare when a Page or a related DataObject is published/saved.

## Requirements
* jamesryanbell/cloudflare: ^1.7,
* silverstripe/cms: ^3.1

_NB_: The plugin has been tested against Silverstripe 3.4 but has not been toroughly tested against all versions of Silverstripe 3.x. It should in theory work with all sub-version of Silversrtipe 3. If you come accross an issue using an older version of Silverstripe 3, log an issue or do a PR and I'll look into it.

## Installation
```
composer require syrp-nz/silverstripe-cloudlfare-purger ""^0.0"
```

## Configuration
This plugin can be configured via a YML file or via the Site Configuration. The YML configuration will have precedence over the Site Configuration. You can use a combination of both if you want.

The following configuration options are available:
* `Email` which should be your Cloudflare account's email address
* `AuthKey` which is your Cloudflare account's AuthKey (can be obtain under _My Settings > API Key_)
* `ZoneIdentifier` which is the numeric identifier of the Zone to which your website domain belongs. There's not really any easy way of retrieving this in Cloudflare without using the API. This will appear as a user friendly drop down in your site configuration page.
* `Paths` which should be an array of absolute paths where your website can be accessed. If your website is only available under one path, you can leave this value blank.
* `HideSiteConfig` is an optional boolean value you can define, if you want to configure all Cloudflare settings on in the YML file. Setting this value to `true` will hide the Cloudflare tab from your site configuration.

### Sample YML file
```YML
CloudflarePurger:
  Email: webmaster@syrp.co.nz
  AuthKey: 9ab98deb4846762385d8cef7abc3fa34d3c8e
  ZoneIdentifier: 218b206ad809216aee9c589cf6c8afb1
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
The plugin defines a `CloudflarePurgerExtension` DataExtension. This extension can be applied to any DataObject.
