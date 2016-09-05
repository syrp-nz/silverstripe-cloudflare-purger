<?php

/**
 * This Extension can be applied to DataObjects that are accessible via a URL or that are associated to an object is
 * reachable via a URL.
 */
class CloudflarePurgerExtension extends DataExtension
{

    /**
     * This hook will be called on objects that have the `Versioned` extension applied on the them before a new version
     * is published.
     */
    public function onBeforeVersionedPublish()
    {
        $this->purge();
    }

    /**
     * This hook will be called after an object is saved. If the object doesn't implement the `Versioned` Extension, it
     * will try to purge URL from Cloudflare.
     */
    public function onAfterWrite()
    {
        if (!$this->owner->hasExtension('Versioned')) {
            $this->purge();
        }
    }

    /**
     * Try to purge URLs from Cloudflare for this DataObject.
     */
    protected function purge()
    {
        // Get the links to purge
        $links = $this->getPurgeLinks();

        // If we have URLs, proceed with purge.
        if ($links) {
            try {
                CloudflarePurger::purge($links);
            } catch (Exception $ex) {
                SS_Log::log($ex->getMessage(), SS_Log::NOTICE);
            }
        }
    }

    /**
     * Build a list of URLs to purge.
     *
     * The DataObject can implement a `CloudflarePurgeLinks` method to define what URLs should be purge. That Url can
     * return a simple string or an array of string. The Url return should be relative to the site root.
     *
     * If the parent DataObject doesn't have a `CloudflarePurgeLinks` method, we'll try to access the `Link()` method
     * instead.
     *
     * @return string[]
     */
    protected function getPurgeLinks()
    {

        $links = false;

        // Get links from parent.
        if ($this->owner->hasMethod('CloudflarePurgeLinks')) {
            $links = $this->owner->CloudflarePurgeLinks();

        } elseif ($this->owner->hasMethod('Link')) {
            $links = $this->owner->Link();
        }

        // Make sure we always return an array.
        if (!$links) {
            return [];
        } elseif (is_array($links)) {
            return $links;
        } else {
            return [$links];
        }
    }

}
