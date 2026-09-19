<?php

namespace Pms\Backend\Controller;

use Pms\Backend\Support\Auth;
use Pms\Backend\Support\Csrf;
use Pms\Backend\Support\Flash;
use Pms\Backend\Support\Html;
use Pms\Backend\Support\Request;

/**
 * Basis aller Backend-Bereiche.
 *
 * Ein Controller verarbeitet zuerst die Eingaben (POST) und liefert
 * anschließend das HTML des Inhaltsbereichs zurück. Ausgabe und
 * Verarbeitung sind getrennt: handle() gibt nichts aus, sondern liefert
 * eine Zeichenkette.
 */
abstract class Controller
{
    /** Die Aktion, unter der dieser Bereich erreichbar ist. */
    abstract public function action(): string;

    /** Erzeugt das HTML des Inhaltsbereichs. */
    abstract public function handle(): string;

    /** Mindestens erforderliche Benutzerstufe. */
    protected function requiredLevel(): int
    {
        return Auth::TYPE_ADMIN;
    }

    /**
     * Prüft die Berechtigung. Liefert eine Fehlermeldung, wenn sie fehlt.
     */
    public function permissionError(): ?string
    {
        if (Auth::atLeast($this->requiredLevel())) {
            return null;
        }
        return '<table class="info_error"><tr><td>Ihre Berechtigungen sind zu niedrig, um diesen Bereich anzuzeigen!</td></tr></table>';
    }

    /** Adresse innerhalb des eigenen Bereichs. */
    protected function url(array $params = []): string
    {
        return Html::url($this->action(), $params);
    }

    /**
     * Weiterleitung nach erfolgreicher Verarbeitung (Post/Redirect/Get),
     * damit ein Neuladen die Aktion nicht wiederholt.
     */
    protected function redirect(array $params = []): never
    {
        throw new \Pms\Backend\Http\RedirectSignal($this->url($params));
    }

    /** Prüft das CSRF-Token einer verändernden Anfrage. */
    protected function checkToken(): bool
    {
        return Csrf::verify();
    }

    /**
     * Standard-Rückfrage vor dem Löschen.
     *
     * @param string $question Vollständiger Fragetext
     * @param string $hint     Zusätzlicher Warnhinweis
     */
    protected function confirmDelete(int $id, string $question, string $hint = ''): string
    {
        $html = Html::formOpen($this->action())
            . Html::heading('Löschen bestätigen')
            . Html::hidden('delete_id', $id)
            . '<p>' . Html::e($question) . '</p>';

        if ($hint !== '') {
            $html .= '<div class="example">' . Html::e($hint) . '</div>';
        }

        return $html
            . '<div class="action-section">'
            . '<input type="submit" name="confirm_delete" class="danger" value="Löschen">'
            . ' ' . Html::button('Abbrechen', $this->url(), 'button button-secondary')
            . '</div>'
            . Html::formClose();
    }

    /** Wurde das Löschen bestätigt? Liefert die ID oder 0. */
    protected function confirmedDeleteId(): int
    {
        if (!Request::submitted('confirm_delete')) {
            return 0;
        }
        if (!$this->checkToken()) {
            return 0;
        }
        return Request::int('delete_id');
    }

    /** Link "Bearbeiten" für Übersichten. */
    protected function editLink(int $id, string $label = 'Bearbeiten'): string
    {
        return '<a href="' . Html::e($this->url(['edit' => $id])) . '">' . Html::e($label) . '</a>';
    }

    /** Link "Löschen" für Übersichten (führt zur Rückfrage). */
    protected function deleteLink(int $id, string $label = 'Löschen'): string
    {
        return '<a href="' . Html::e($this->url(['delete' => $id])) . '">' . Html::e($label) . '</a>';
    }

    /** Meldung über einen erfolgreichen Vorgang. */
    protected function saved(string $message): void
    {
        Flash::success($message);
    }
}
