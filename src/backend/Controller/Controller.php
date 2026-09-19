<?php

namespace Pms\Backend\Controller;

use Pms\Backend\View\Components;
use Pms\Data\Db;
use Pms\Support\Auth;
use Pms\Support\Csrf;
use Pms\Support\Flash;
use Pms\Support\Html;
use Pms\Support\Request;

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
        return '<div class="notice notice-error">Ihre Berechtigungen sind zu niedrig, um diesen Bereich anzuzeigen!</div>';
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

    /**
     * Führt eine über die Pfeile angeforderte Verschiebung aus.
     *
     * @param string $table Tabelle ohne Präfix
     * @return bool true, wenn sortiert wurde
     */
    protected function handleSorting(string $table): bool
    {
        if (Request::string('sort') === '') {
            return false;
        }
        if (!Csrf::check()) {
            Flash::error('Die Sortierung konnte nicht übernommen werden (ungültiges Sicherheitstoken).');
            return false;
        }

        $id = Request::queryInt('id');
        if ($id <= 0) {
            return false;
        }

        return Db::update($table, $id, ['sort' => Request::queryInt('pos')]);
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
        $html = Components::pageHeader('Löschen bestätigen')
            . Html::formOpen($this->action())
            . Html::hidden('delete_id', $id)
            . '<div class="form-card"><div class="form-section">'
            . '<p>' . Html::e($question) . '</p>';

        if ($hint !== '') {
            $html .= '<div class="notice notice-warn">' . Html::e($hint) . '</div>';
        }

        return $html
            . '</div><div class="form-actions">'
            . '<input type="submit" name="confirm_delete" class="danger" value="Löschen">'
            . Html::button('Abbrechen', $this->url(), 'btn btn-secondary')
            . '</div></div>'
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

    /**
     * Aktionsspalte einer Übersicht: Bearbeiten und Löschen als Symbol,
     * davor optional weitere Aktionen des Bereichs.
     *
     * @param list<string> $extra Zusätzliche Aktionen, bereits fertiges HTML
     */
    protected function rowActions(int $id, array $extra = []): string
    {
        return Components::actions(...[
            ...$extra,
            Components::action('edit', $this->url(['edit' => $id]), 'Bearbeiten'),
            Components::action('trash', $this->url(['delete' => $id]), 'Löschen', 'danger'),
        ]);
    }

    /** Meldung über einen erfolgreichen Vorgang. */
    protected function saved(string $message): void
    {
        Flash::success($message);
    }
}
