<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.11.17
 * Time: 13:19
 */

namespace UnitedCMS\CoreBundle\Collection;

class CollectionSettings
{
    public function __construct(array $settings = [])
    {
        foreach($settings as $key => $value) {
            $this->$key = $value;
        }
    }
}