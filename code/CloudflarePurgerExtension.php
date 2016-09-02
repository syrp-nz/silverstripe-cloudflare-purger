<?php

class CloudFlarePurgerExtension extends DataExtension
{

    public function onBeforeVersionedPublish()
    {
        $this->purge();
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        if ($this->owner->hasMethod('CloudflarePurgeOnWrite') && $this->owner->CloudflarePurgeOnWrite()) {
            $this->purge();
        }
    }


    protected function purge()
    {
        $links = $this->getPurgeLinks();
        if ($links) {
            CloudflarePurger::purge($links);
        }
    }

    protected function getPurgeLinks()
    {
        if ($this->owner->hasMethod('CloudflarePurgeLinks')) {
            $links = $this->owner->CloudflarePurgeLinks();
            if (is_array($links)) {
                return $links;
            } else {
                return [$links];
            }
        } elseif ($this->owner->hasMethod('Link')) {
            return [$this->owner->Link()];
        }
    }

}
