<?php

namespace BestIt\ContentfulBundle;

/**
 * Storage for the event names.
 * @author lange <lange@bestit-online.de>
 * @package BestIt\ContentfulBundle
 * @version $id$
 */
final class ClientEvents
{
    /**
     * Event for loading an entry list
     * @var string
     */
    const LOAD_CONTENTFUL_ENTRIES = 'best_it_contentful.load.entries';

    /**
     * The event name for loading a contentful entry.
     * @var string
     */
    const LOAD_CONTENTFUL_ENTRY = 'best_it_contentful.load.entry';
}
