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
	 * Generate TinyMCE configuration
	 *
	 * @param match Editor element selector
	 * @param height Editor height
	 * @return string TinyMCE configuration
	 */
	function get_tinymceinit($match,$height)
	{
		// Die Breite kommt aus dem Umfeld: 640 Pixel liessen im Backend
		// zwei Drittel der Karte leer stehen und sprengten im Frontend
		// die schmale Spalte. Ziehen laesst sich nur noch die Hoehe -
		// die Breite bestimmt die Spalte.
		return 'tinymce.init({
    selector: "#'.$match.'",
    width: "100%",
    height: "'.$height.'",
    resize: true,
    language: "de",
    plugins: "advlist autolink lists link image charmap preview anchor \
              searchreplace visualblocks code fullscreen insertdatetime media \
              table help wordcount",
    
    toolbar: "undo redo | bold italic underline strikethrough | \
              alignleft aligncenter alignright alignjustify | \
              styleselect formatselect fontselect fontsizeselect | \
              bullist numlist outdent indent blockquote | \
              link image media | forecolor backcolor | \
              removeformat code fullscreen",
		
    content_css: "template_files/style.css",
    body_class: "content_table",
		
    // Externe Listen für Links/Medien/Templates (falls genutzt)
    template_external_list_url: "lists/template_list.js",
    external_link_list_url: "lists/link_list.js",
    external_image_list_url: "lists/image_list.js",
    media_external_list_url: "lists/media_list.js",
		
    // Platzhalter-Werte für Templates
    template_replace_values: {
        username: "Some User",
        staffid: "991234"
    }
});';
	}

	/**
	 * Initialize TinyMCE editor
	 *
	 * @param match Element selector
	 * @param init Initialize flag
	 * @param height Editor height
	 * @return string HTML editor code
	 */
	function get_tinymce($match="content",$init=1,$height=300)
	{
		$str='
<!-- TinyMCE -->
<script type="text/javascript" src="tinymce/tinymce.js"></script>
<script type="text/javascript">
';
		if($init) $str.=get_tinymceinit($match,$height);
		$str.='
</script>
<!-- /TinyMCE -->';
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
