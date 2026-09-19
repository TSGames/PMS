<?php

namespace Pms\Support;

/**
 * Bilder, die zu einem Datensatz gehören (Unterkategorie, Inhalt, Benutzer).
 *
 * Die Dateien liegen unter images/<typ>/<id>.<endung>; die Endung steht in
 * der Spalte "image" des Datensatzes.
 */
final class EntityImage
{
    /**
     * Erlaubte Bildformate.
     *
     * @return list<string>
     */
    public static function supportedTypes(): array
    {
        /** @var list<string> $types */
        $types = $GLOBALS['supported_img'] ?? ['jpg', 'jpeg', 'gif', 'png'];
        return $types;
    }

    /**
     * Nimmt eine hochgeladene Datei an und legt sie skaliert ab.
     *
     * @return string|null Endung der gespeicherten Datei oder null
     */
    public static function store(string $type, int $id, string $field = 'image', int $width = 256, int $height = 256): ?string
    {
        if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $extension = strtolower((string)pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::supportedTypes(), true)) {
            Flash::error('Das Bildformat wird nicht unterstützt.');
            return null;
        }

        $target = self::path($type, $id, $extension);
        @mkdir(dirname($target), 0755, true);

        if (!@copy($_FILES[$field]['tmp_name'], $target)) {
            Flash::error('Das Bild konnte nicht gespeichert werden.');
            return null;
        }

        create_img($target, $width, $height);
        return $extension;
    }

    /** Entfernt das Bild eines Datensatzes. */
    public static function delete(string $type, int $id, ?string $extension): void
    {
        if ($extension === null || $extension === '') {
            return;
        }
        del_contentimg($type, $id, $extension);
    }

    /**
     * Sucht eine vorhandene Bilddatei, deren Endung nicht in der
     * Datenbank steht (Altbestand).
     */
    public static function detect(string $type, int $id): ?string
    {
        foreach (self::supportedTypes() as $extension) {
            if (file_exists(self::path($type, $id, $extension))) {
                return $extension;
            }
        }
        return null;
    }

    public static function path(string $type, int $id, string $extension): string
    {
        return ($GLOBALS['image_path'] ?? 'images/') . $type . '/' . $id . '.' . $extension;
    }
}
