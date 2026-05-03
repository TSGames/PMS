<?php
// Module: functions_xlsx.php - XLSX file parsing and conversion to text

/**
 * Validate XLSX file format and size
 *
 * @param file_path Path to uploaded file
 * @param max_size Maximum file size in bytes (default 5MB)
 * @return array Error message or success: ['success' => true] or ['error' => 'message']
 */
function validate_xlsx_file($file_path, $max_size = 5242880)
{
	// Check file exists
	if (!file_exists($file_path)) {
		return ['error' => 'File not found'];
	}

	// Check file size
	if (filesize($file_path) > $max_size) {
		return ['error' => 'File too large. Maximum size: ' . size(round($max_size / 1024 / 1024)) . 'MB'];
	}

	// Check if valid ZIP (XLSX is ZIP format)
	$zip = new ZipArchive();
	if ($zip->open($file_path) !== true) {
		return ['error' => 'Invalid XLSX file format'];
	}

	// Check for required XML files
	if ($zip->locateName('xl/workbook.xml') === false) {
		$zip->close();
		return ['error' => 'Invalid XLSX structure'];
	}

	$zip->close();
	return ['success' => true];
}

/**
 * Extract text content from XLSX file and convert to pipe-delimited format
 *
 * @param file_path Path to XLSX file
 * @param sheet_index Sheet number to parse (0-based, default 0 for first sheet)
 * @return string|array Pipe-delimited text or error array
 */
function parse_xlsx_to_text($file_path, $sheet_index = 0)
{
	// Validate file first
	$validation = validate_xlsx_file($file_path);
	if (isset($validation['error'])) {
		return $validation;
	}

	try {
		$zip = new ZipArchive();
		$zip->open($file_path);

		// Read workbook to find sheet relationship
		$workbook_xml = $zip->getFromName('xl/workbook.xml');
		if (!$workbook_xml) {
			return ['error' => 'Cannot read workbook'];
		}

		// Parse workbook XML to get sheet files
		$workbook = simplexml_load_string($workbook_xml);
		if (!$workbook) {
			return ['error' => 'Invalid workbook XML'];
		}

		// Register namespaces
		$ns = $workbook->getNamespaces();
		$workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

		// Get sheet list
		$sheets = $workbook->sheets->sheet;
		if (count($sheets) <= $sheet_index) {
			return ['error' => 'Sheet index out of range'];
		}

		// Get sheet ID
		$sheet_item = $sheets[$sheet_index];
		$sheet_id = (string)$sheet_item->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;

		// Read relationships to find sheet filename
		$rels_xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
		$rels = simplexml_load_string($rels_xml);
		$sheet_file = null;

		foreach ($rels->Relationship as $rel) {
			if ((string)$rel['Id'] === $sheet_id) {
				$sheet_file = 'xl/' . (string)$rel['Target'];
				break;
			}
		}

		if (!$sheet_file || !$zip->locateName($sheet_file)) {
			return ['error' => 'Cannot find sheet file'];
		}

		// Read sheet data
		$sheet_xml = $zip->getFromName($sheet_file);
		if (!$sheet_xml) {
			return ['error' => 'Cannot read sheet data'];
		}

		// Read shared strings (for cell references)
		$shared_strings = [];
		$strings_file = $zip->getFromName('xl/sharedStrings.xml');
		if ($strings_file) {
			$strings_xml = simplexml_load_string($strings_file);
			foreach ($strings_xml->si as $si) {
				$text = '';
				if (isset($si->t)) {
					$text = (string)$si->t;
				} else if (isset($si->r)) {
					foreach ($si->r as $r) {
						$text .= (string)$r->t;
					}
				}
				$shared_strings[] = $text;
			}
		}

		// Parse sheet XML to extract cell data
		$sheet = simplexml_load_string($sheet_xml);
		$rows = $sheet->sheetData->row;

		if (!$rows) {
			$zip->close();
			return '';
		}

		$output = '';
		foreach ($rows as $row) {
			$cells_in_row = [];
			foreach ($row->c as $cell) {
				$cell_value = '';

				if (isset($cell->v)) {
					$cell_value = (string)$cell->v;

					// If it's a string reference (type s), get from shared strings
					if ((string)$cell['t'] === 's') {
						$index = (int)$cell_value;
						if (isset($shared_strings[$index])) {
							$cell_value = $shared_strings[$index];
						}
					}
				}

				$cells_in_row[] = $cell_value;
			}

			// Join cells with whitespace separator
			if (!empty($cells_in_row)) {
				// Remove trailing empty cells
				while (!empty($cells_in_row) && end($cells_in_row) === '') {
					array_pop($cells_in_row);
				}
				$output .= implode(' ', $cells_in_row) . "\n";
			}
		}

		$zip->close();
		return trim($output);

	} catch (Exception $e) {
		return ['error' => 'Error parsing XLSX: ' . $e->getMessage()];
	}
}

/**
 * Get list of sheets in XLSX file
 *
 * @param file_path Path to XLSX file
 * @return array Sheet names or error
 */
function get_xlsx_sheets($file_path)
{
	$validation = validate_xlsx_file($file_path);
	if (isset($validation['error'])) {
		return $validation;
	}

	try {
		$zip = new ZipArchive();
		$zip->open($file_path);

		$workbook_xml = $zip->getFromName('xl/workbook.xml');
		$workbook = simplexml_load_string($workbook_xml);

		$sheets = [];
		foreach ($workbook->sheets->sheet as $sheet) {
			$sheets[] = (string)$sheet['name'];
		}

		$zip->close();
		return $sheets;

	} catch (Exception $e) {
		return ['error' => 'Cannot read sheets: ' . $e->getMessage()];
	}
}

/**
 * Clean up temporary XLSX files from uploads folder
 *
 * @param hours Delete files older than N hours (default 24)
 * @return int Number of files deleted
 */
function cleanup_xlsx_temp_files($hours = 24)
{
	$temp_dir = 'images/uploads/temp/';
	if (!is_dir($temp_dir)) {
		return 0;
	}

	$deleted = 0;
	$cutoff_time = time() - ($hours * 3600);

	if ($handle = @opendir($temp_dir)) {
		while (($file = @readdir($handle)) !== false) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$file_path = $temp_dir . $file;
			if (is_file($file_path) && filemtime($file_path) < $cutoff_time) {
				if (@unlink($file_path)) {
					$deleted++;
				}
			}
		}
		@closedir($handle);
	}

	return $deleted;
}

?>
