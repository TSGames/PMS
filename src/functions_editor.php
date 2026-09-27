<?php
// Module: functions_editor.php

	/**
	 * Get template placeholder markers
	 * @return array<int, string> Template markers
	 */
	function get_template()
	{
		return array("#content","#title","#menu","#user_panel","#poll","#footer","#counter","#birthday","#topuser","#mostdiscussed","#search","#position_row","#latest_comments","#comments_list","#newsletter","#pms_styles");
	}

	/**
	 * Bindet den grafischen Inhaltseditor ein (Quill, vormals TinyMCE).
	 *
	 * Quill ersetzt die textarea nicht direkt, sondern legt ein eigenes
	 * Element daneben und synchronisiert erst beim Absenden zurück -
	 * siehe admin-content-editor.js. Deshalb reicht hier ein Aufruf von
	 * pmsInitEditor() statt einer langen Konfiguration wie bei TinyMCE.
	 *
	 * @param match Element-ID der textarea
	 * @param init Initialisieren? (bislang stets true, siehe Aufrufer)
	 * @param height Höhe des Bearbeitungsbereichs in Pixeln
	 * @return string HTML zum Einbinden des Editors
	 */
	function get_editor($match="content",$init=1,$height=300)
	{
		$str='
<!-- Quill -->
<link rel="stylesheet" type="text/css" href="css/quill.snow.css">
<script type="text/javascript" src="js/vendor/quill.js"></script>
<script type="text/javascript" src="js/admin-content-editor.js"></script>
';
		if($init) $str.='<script type="text/javascript">pmsInitEditor("'.$match.'",'.(int)$height.');</script>
';
		$str.='<!-- /Quill -->';
		return $str;
	}

	/**
	 * Bindet den Code-Editor der Variablen-Seite ein.
	 *
	 * Er haengt sich an jedes Textfeld mit dem Merkmal data-editor. Frueher
	 * stand hier der Monaco-Editor, der von einem CDN nachgeladen wurde -
	 * rund fuenf Megabyte, eine Anfrage an einen Dritt-Server bei jeder
	 * Bearbeitung, und ohne Internetzugang blieb das Feld leer. Die jetzige
	 * Zusammenstellung liefert das Projekt selbst aus
	 * (src/js/vendor/editor.js, gebaut mit "npm run vendor:editor").
	 *
	 * @return string HTML zum Einbinden des Editors
	 */
	function get_code_editor(){
		return '<link rel="stylesheet" type="text/css" href="css/code-editor.css">'
			.'<script defer src="js/vendor/editor.js"></script>';
	}

	/**
	 * Generate edit mode output
	 *
	 * @param string Content string
	 * @param name Field name
	 * @param class CSS class
	 * @param edit_mode Edit mode flag
	 * @param mode Output mode
	 * @return string Edit output HTML
	 */
	function edit_out($string,$name,$class,$edit_mode,$mode=0)
	{
		if(!$edit_mode) return $string;
		if($mode==2) $string=cleanup_content($string);
		$str="";
		// Die Breite kommt aus pms.css, nicht aus cols oder width: cols
		// setzt eine feste Spaltenzahl, width kennt ein textarea gar
		// nicht - beides sprengte im Inhaltsbereich die Spalte.
		$add='rows="4"';
		if($mode==2) $add='rows="20"';
		$class=trim($class." item_edit_field");
		if(!$mode) $str.='<input type="text" name="edit_'.$name.'" id="edit_'.$name.'" class="'.$class.'" value="'.str_replace('"',"&quot;",$string).'">';
		else $str.='<textarea name="edit_'.$name.'" id="edit_'.$name.'" class="'.$class.'" '.$add.'>'.str_replace('&','&amp;',$string).'</textarea>';
		return $str;
	}

?>
