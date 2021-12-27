<?php

namespace Zoomx\Commands;


class Cache extends Command
{
    public function clear(array $partitions = [])
    {
        try {
            $result = zoomx()->getCacheManager()->refresh($partitions);
        } catch (\Exception $e) {
            echo $e->getMessage();
            die();
        }

        return $result;
    }
}