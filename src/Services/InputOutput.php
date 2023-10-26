<?php

namespace Wpmetcom\AutomatedTranslation\Services;

use Symfony\Component\Console\Style\SymfonyStyle;

class InputOutput extends SymfonyStyle
{
    /**
     * for custom styling.
     */
    public function isInteractive(): bool
    {
        return false;
    }
}
