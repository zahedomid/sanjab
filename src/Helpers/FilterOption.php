<?php

namespace Sanjab\Helpers;

/**
 * @method $this title (string $value)   title of filter option.
 * @method $this query (callable $value) filter option query callback.
 */
class FilterOption extends PropertiesHolder
{
    /**
     * create new Filter option.
     *
     * @param string $title
     * @return static
     */
    public static function create($title = null)
    {
        $out = new static;
        if ($title) {
            $out->title($title);
        }

        return $out;
    }
}
