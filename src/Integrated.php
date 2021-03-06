<?php

namespace LCache;

use LCache\l1\L1;
use LCache\l2\L2;

final class Integrated
{
    protected $l1;
    protected $l2;
    protected $overhead_threshold;
    protected $created_time;

    public function __construct(L1 $l1, L2 $l2, int $created_time, $overhead_threshold = null)
    {
        $this->l1 = $l1;
        $this->l2 = $l2;
        $this->overhead_threshold = $overhead_threshold;
        $this->created_time = $created_time;
        $l1->setCreatedTime($this->created_time);
        $l2->setCreatedTime($this->created_time);
    }

    public function set(Address $address, $value, $ttl_or_expiration = null, array $tags = [])
    {
        $expiration = null;
        if (!is_null($ttl_or_expiration)) {
            if ($ttl_or_expiration < $this->created_time) {
                $expiration = $this->created_time + $ttl_or_expiration;
            } else {
                $expiration = $ttl_or_expiration;
            }
        }

        if (!is_null($this->overhead_threshold)) {
            $key_overhead = $this->l1->getKeyOverhead($address);

            // Check if this key is known to have excessive overhead.
            $excess = $key_overhead - $this->overhead_threshold;
            if ($excess >= 0) {
                // If there's already an L1 negative cache entry, simply skip the write.
                if ($this->l1->isNegativeCache($address)) {
                    return null;
                }

                // Otherwise, delete the item in L2.
                $event_id = $this->l2->delete($this->l1->getPool(), $address);

                // If the L2 deletion succeeded, retain a negative cache item
                // in L1 for a number of minutes equivalent to the number of
                // excessive sets over the threshold, plus one minute.
                if (!is_null($event_id)) {
                    $expiration = $this->created_time + ($excess + 1) * 60;
                    $this->l1->setWithExpiration($event_id, $address, null, $this->created_time, $expiration);
                }
                return $event_id;
            }
        }

        $event_id = $this->l2->set($this->l1->getPool(), $address, $value, $expiration, $tags);
        if (!is_null($event_id)) {
            $this->l1->set($event_id, $address, $value, $expiration);
        }
        return $event_id;
    }

    protected function getEntryOrTombstone(Address $address)
    {
        $entry = $this->l1->getEntry($address);
        if (!is_null($entry)) {
            return $entry;
        }
        $entry = $this->l2->getEntry($address);
        if (is_null($entry)) {
            // On an L2 miss, construct a negative cache entry that will be
            // overwritten on any update.
            $entry = new Entry(0, $this->l1->getPool(), $address, null, $this->created_time, null);
        }
        $this->l1->setWithExpiration($entry->event_id, $address, $entry->value, $entry->created, $entry->expiration);
        return $entry;
    }

    public function getEntry(Address $address, $return_tombstone = false)
    {
        $entry = $this->getEntryOrTombstone($address);
        if (!is_null($entry) && (!is_null($entry->value) || $return_tombstone)) {
            return $entry;
        }
        return null;
    }

    public function get(Address $address)
    {
        $entry = $this->getEntry($address);
        if (is_null($entry)) {
            return null;
        }
        return $entry->value;
    }

    public function exists(Address $address)
    {
        $exists = $this->l1->exists($address);
        if ($exists) {
            return true;
        }
        return $this->l2->exists($address);
    }

    public function delete(Address $address)
    {
        $event_id = $this->l2->delete($this->l1->getPool(), $address);
        if (!is_null($event_id)) {
            $this->l1->delete($event_id, $address);
        }
        return $event_id;
    }

    public function deleteTag($tag)
    {
        $event_id = $this->l2->deleteTag($this->l1, $tag);
        return $event_id;
    }

    public function synchronize()
    {
        return $this->l2->applyEvents($this->l1);
    }

    public function getHitsL1()
    {
        return $this->l1->getHits();
    }

    public function getHitsL2()
    {
        return $this->l2->getHits();
    }

    public function getMisses()
    {
        return $this->l2->getMisses();
    }

    public function getLastAppliedEventID()
    {
        return $this->l1->getLastAppliedEventID();
    }

    public function getPool()
    {
        return $this->l1->getPool();
    }

    public function collectGarbage($item_limit = null)
    {
        return $this->l2->collectGarbage($item_limit);
    }

    public function getCreatedTime(): int
    {
        return $this->created_time;
    }
}
