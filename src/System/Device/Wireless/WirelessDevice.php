<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\System\Device\Wireless;

use App\System\Device\AbstractDevice;

final class WirelessDevice extends AbstractDevice
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string[]
     */
    private $data;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name;
    }

    /**
     * @param string         $name
     * @param InputInterface $input
     *
     * @return array|null
     */
    protected function getInterfaceData(string $name, InputInterface $input): ?array
    {
        $device = new self($name);
        if (!$this->getInterfaceRealFilterClosure()($name)) {
            $this->style()->warning(
                sprintf('Specified interface name is invalid: "%s"', $name)
            );

            return null;
        }

        $raw = $this->getInterfaceRawData($name);
        $row = [];

        if ($input->hasOption('signal-dbm')) {
            $row[] = $this->extractSignalLevel($raw);
        }

        return $row;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    private function getInterfaceRawData(string $name): ?string
    {
        $process = self::makeProcess('iwconfig', $name);

        try {
            $process->run();
        } catch (RuntimeException $exception) {
            $this->style()->warning(sprintf('Failed to call "iwconfig" to resolve interface details: "%s"', $name));
        } finally {
            return $process->getOutput();
        }
    }

    /**
     * @param string $data
     *
     * @return int|null
     */
    private function extractSignalLevel(string $data): ?int
    {
        if (preg_match('/Signal\slevel=(?<level>-?[0-9]+)/', $data, $match)) {
            return $match['level'];
        }

        return null;
    }
}
