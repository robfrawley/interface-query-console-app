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
use App\Utility\Reflection\ReflectionHelper;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Style extends SymfonyStyle
{
    /**
     * @var OutputInterface
     */
    private $unbufferedOutput;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        parent::__construct($input, $output);

        $this->unbufferedOutput = $output;
    }

    /**
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        return ReflectionHelper::propertyValue($this, 'input');
    }

    /**
     * @return OutputInterface|BufferedOutput
     */
    public function getOutput(): OutputInterface
    {
        return ReflectionHelper::propertyValue($this, 'bufferedOutput');
    }

    /**
     * @return OutputInterface
     */
    public function getUnbufferedOutput(): OutputInterface
    {
        return $this->unbufferedOutput;
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
        $this->prependBlock();
        $this->writeln($this->makeBoxedText(preg_replace(
            sprintf('/\s?(%s)/', preg_quote($command->getApplication()->c()->getName())),
            sprintf('$1 (%s)', $command->c()->getName()),
            $command->getApplication()->getLongVersion()
        )));
        $this->newLine();
    }

    /**
     * @return self
     */
    public function prependBlock(): self
    {
        ReflectionHelper::methodInvoke($this, 'autoPrependBlock');

        return $this;
    }

    /**
     * @return self
     */
    public function prependText(): self
    {
        ReflectionHelper::methodInvoke($this, 'autoPrependText');

        return $this;
    }

    /**
     * @param string $text
     * @param bool   $thick
     * @param bool   $trimText
     *
     * @return string[]
     */
    public function makeBoxedText(string $text, bool $thick = false, bool $trimText = true): array
    {
        $size = $this->stringLength($text);

        return [
            sprintf(' %s', $this->makeDarkGray($this->makeLine(
                $size + 3, $thick ? '┏' : '┌', $thick ? '┓' : '┐', $thick ? '━' : '─'
            ))),
            sprintf(
                ' %s %s %1$s', $this->makeDarkGray($thick ? '┃' : '│'), $trimText ? trim($text) : $text
            ),
            sprintf(' %s', $this->makeDarkGray($this->makeLine(
                $size + 3, $thick ? '┗' : '└', $thick ? '┛' : '┘', $thick ? '━' : '─'
            ))),
        ];
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function makeDarkGray(string $text): string
    {
        return sprintf('<fg=black;options=bold>%s</>', $text);
    }

    /**
     * @param int         $size
     * @param string|null $leftChar
     * @param string|null $rightChar
     * @param string|null $lineChar
     * @param string      $defaultChar
     *
     * @return string
     */
    public function makeLine(int $size, string $leftChar = null, string $rightChar = null, string $lineChar = null, string $defaultChar = '─'): string
    {
        return vsprintf('%s%s%s', [
            $leftChar ?? $defaultChar,
            str_repeat($lineChar ?? $defaultChar, $size - 2),
            $rightChar ?? $defaultChar,
        ]);
    }

    /**
     * @param string $text
     * @param bool   $countMarkup
     *
     * @return int
     */
    public function stringLength(string $text, bool $countMarkup = false): int
    {
        return $countMarkup
            ? Helper::strlen($text)
            : Helper::strlenWithoutDecoration($this->getFormatter(), $text);
    }
}
