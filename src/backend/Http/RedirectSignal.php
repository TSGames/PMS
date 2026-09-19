<?php

namespace Pms\Backend\Http;

/**
 * Signal für eine Weiterleitung aus einem Controller heraus.
 *
 * Controller geben HTML zurück und beenden das Skript nicht selbst. Um nach
 * einem erfolgreichen Speichern trotzdem weiterleiten zu können, werfen sie
 * dieses Signal; die Route-Hülle macht daraus eine 302-Antwort.
 */
final class RedirectSignal extends \RuntimeException
{
    public function __construct(private readonly string $target, private readonly int $status = 302)
    {
        parent::__construct('Weiterleitung nach ' . $target);
    }

    public function target(): string
    {
        return $this->target;
    }

    public function status(): int
    {
        return $this->status;
    }
}
