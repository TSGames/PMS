<?php
// Module: functions_editor.php

	function get_template()
	{
		return array("#content","#title","#menu","#user_panel","#poll","#footer","#counter","#birthday","#topuser","#mostdiscussed","#search","#position_row","#latest_comments","#comments_list","#newsletter");
	}

	function get_tinymceinit($match,$height)
	{
		return 'tinymce.init({
    selector: "#'.$match.'",
    width: 640,
    height: "'.$height.'",
    resize: "both",
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

	function get_monaco(){
		return <<<JS
		
		<!-- Load Monaco from CDN -->
		<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.49.0/min/vs/loader.js"></script>
		<script>
		// Configure the Monaco base path for its internal modules
		require.config({ paths: { 'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.49.0/min/vs' } });
		
		
		// Configure Monaco base path
		require.config({ paths: { 'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.49.0/min/vs' } });
		
		// Load Monaco editor
		require(['vs/editor/editor.main'], function() {
			// Get all textarea elements
			document.querySelectorAll('textarea').forEach(function(textarea) {
				// Create a wrapper div to host the editor
				const wrapper = document.createElement('div');
				wrapper.className = 'monaco-wrapper';
				wrapper.style.minHeight = '200px';
				
				// Insert wrapper before textarea
				textarea.parentNode.insertBefore(wrapper, textarea);
				// Hide the original textarea
				textarea.style.display = 'none';
				
				// Determine language from optional data attribute or simple detection
				let language = 'php';
				
				// Create Monaco editor in the wrapper
				const editor = monaco.editor.create(wrapper, {
					value: textarea.value,
					language: language,
					theme: 'vs-light',
					minimap: { enabled: false },
					automaticLayout: true
				});
				
				// Sync back to textarea on change (for form submission, etc.)
				editor.onDidChangeModelContent(() => {
					textarea.value = editor.getValue();
				});
			});
		});
		
		</script>
		JS;
		
	}

	function edit_out($string,$name,$class,$edit_mode,$mode=0)
	{
		if(!$edit_mode) return $string;
		if($mode==2) $string=cleanup_content($string);
		//if($mode) $string=my_stripslashes($string);
		$add='rows="4" cols="50"';
		if($mode==2) $add='rows="20" width="100%"';
		if(!$mode) $str.='<input type="text" name="edit_'.$name.'" id="edit_'.$name.'" class="'.$class.'" value="'.str_replace('"',"&quot;",$string).'">';
		else $str.='<textarea name="edit_'.$name.'" id="edit_'.$name.'" class="'.$class.'" '.$add.'>'.str_replace('&','&amp;',$string).'</textarea>';
		return $str;
	}

?>
