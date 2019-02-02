<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Component\Console\Style;

use App\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Style extends SymfonyStyle
{
    /**
     * @var InputInterface
     */
    private $originalI;

    /**
     * @var OutputInterface
     */
    private $originalO;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->originalI = $input;
        $this->originalO = $output;
        parent::__construct($input, $output);
    }

    /**
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        return $this->originalI;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->originalO;
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function markupBold(string $text = ''): string
    {
        return sprintf('<options=bold>%s</>', $text);
    }

    /**
     * @param \Closure $closure
     *
     * @return self
     */
    public function ifVerbose(\Closure $closure): self
    {
        if ($this->isVerbose()) {
            $closure($this);
        }

        return $this;
    }

    /**
     * @param \Closure $closure
     *
     * @return self
     */
    public function ifVeryVerbose(\Closure $closure): self
    {
        if ($this->isVeryVerbose()) {
            $closure($this);
        }

        return $this;
    }

    /**
     * @param \Closure $closure
     *
     * @return self
     */
    public function ifDebug(\Closure $closure): self
    {
        if ($this->isDebug()) {
            $closure($this);
        }

        return $this;
    }

    /**
     * @param \Closure $closure
     *
     * @return self
     */
    public function ifQuiet(\Closure $closure): self
    {
        if ($this->isQuiet()) {
            $closure($this);
        }

        return $this;
    }

    /**
     * @param \Closure $closure
     *
     * @return self
     */
    public function ifNotQuiet(\Closure $closure): self
    {
        if (!$this->isQuiet()) {
            $closure($this);
        }

        return $this;
    }

    /**
     * @param AbstractCommand $command
     */
    public function applicationTitle(AbstractCommand $command)
    {
        try {
            $prependBlock = (new \ReflectionObject($this))->getMethod('autoPrependBlock');
            $prependBlock->setAccessible(true);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Failed to resolve "autoPrependBlock" private parent method.');
        }

        $prependBlock->invoke($this);
        $this->writeln(preg_replace(sprintf('/(%s)/', preg_quote($command->getApplication()->c()->getName())), sprintf('%s $1', $command->c()->getName()), $command->getApplication()->getLongVersion()));
        $this->newLine();
    }
}
