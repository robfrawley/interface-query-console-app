<?php

namespace App\Component\Configuration;

final class AppConfiguration extends Configuration
{
    /**
     * @param string|null $file
     */
    public function __construct(string $file = null)
    {
        parent::__construct($file ?? 'application.yaml', '{self.conf}');
        $this->load();
    }
}
