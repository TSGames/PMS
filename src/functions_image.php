<?php
// Module: functions_image.php

	/**
	 * Get file or directory size
	 *
	 * @param what File/directory path
	 * @return int|false Size in bytes or false on failure
	 */
	function get_filesize($what)
	{
		$dir=@opendir($what);
		if($dir)
			{
			while (($file = readdir($dir)) !== false)
				{
				if($file=="." || $file=="..")
					{
					continue;
				}
				$size+=get_filesize($what."/".$file);
			}
			closedir($dir);
			return $size;
		}
		else
			{
			return @filesize($what);
		}
	}

	/**
	 * Delete directory contents recursively
	 *
	 * @param dir Directory path
	 * @return bool Success status
	 */
	function delete_all($dir)
	{
		$a=@opendir($dir);
		while (($file = @readdir($a)) !== false)
			{
			if($file=="." || $file=="..")
				{
				continue;
			}
			if(!@unlink($dir."/".$file)) delete_all($dir."/".$file);
		}
		@closedir($a);
		return @rmdir($dir);
	}

	/**
	 * Recursively copy directory
	 *
	 * @param from Source directory
	 * @param to Destination directory
	 * @param results Results array reference
	 * @return void
	 */
	function copy_recrusive($from,$to,&$results)
	{
		@mkdir($to);
		if(substr($from,-1)!="/") $from.="/";
		if(substr($to,-1)!="/") $to.="/";
		$dir=@opendir($from);
		if($dir)
			{
			while (($file = @readdir($dir)) !== false)
				{
				if($file=="." || $file=="..")
					{
					continue;
				}
				$copy_from=$from.$file;
				$copy_to=$to.$file;
				$dir2=@opendir($copy_from);
				if($dir2)
					{
					copy_recrusive($copy_from,$copy_to,$results);
					@closedir($dir2);
				}
				else
					{
					$results[0]+=copy($copy_from,$copy_to);
					$results[1]++;
				}
			}
			@closedir($dir);
		}
	}

	/**
	 * Generate image tag with alt text
	 *
	 * @param str Image filename
	 * @param border Border width
	 * @param alt Alt text
	 * @param folder Image folder
	 * @param class CSS class
	 * @param title Title attribute
	 * @return string HTML img tag with attributes
	 */
	function make_imgalt($str,$border,$alt,$folder="",$class="",$title="")
	{
		global $image_path;
		if(!$folder)
			{
			$folder=$image_path;
		}
		if($title) $title=" title=\"".$title."\"";
		$border=" border=\"".$border."\"";
		$size=@getimagesize($folder.$str);
		if($class) $class=" class=\"".$class."\"";
		if($size)
			{
			$size=" width=\"".$size[0]."px\" height=\"".$size[1]."px\"";
		}
		return "<img src=\"".$folder.$str."\"".$border.$size.$title." alt=\"".$alt."\"".$class.">";
	}

	/**
	 * Generate simple image tag
	 *
	 * @param str Image filename
	 * @param border Border width
	 * @return string HTML img tag
	 */
	function make_img($str,$border)
	{
		return make_imgalt($str,$border,"");
	}

	/**
	 * Format file size for display
	 *
	 * @param byte Size in bytes
	 * @return string Formatted size (B, KB, MB, GB)
	 */
	function size($byte)
	{
		$a[0]="Byte";
		$a[1]="KB";
		$a[2]="MB";
		$a[3]="GB";
		for($i=0;$byte>1024;$i++) $byte/=1024;
		return round($byte,2)." ".$a[$i];
	}

	/**
	 * Display content-related image
	 *
	 * @param what Content type
	 * @param id Content ID
	 * @param typ Image type
	 * @param size Image size
	 * @param width Width
	 * @param height Height
	 * @return string HTML image
	 */
	function make_contentimg($what,$id,$typ,$size=0,$width=-1,$height=-1) // 0 = thumb, 1 = default
	{
		if(!$id)
			{
			return "";
		}
		global $image_path;
		$file=$what."/".$id;
		if($size==1)
			{
			$full=$image_path.$file."_full.".$typ;
			$file=$file."_large";
			$alt=" alt=\"".str_replace('"',"&quot;",from_db($what,$id,"name"))."\"";
			if(file_exists($full)) $link_full=1;
		}
		$file=$file.".".$typ;
		$str="<img class=\"small_image\"".$alt." src=\"".$image_path;
		if(file_exists($image_path.$file))
			{
			$size=@getimagesize($image_path.$file);
			if($size)
				$size=" width=\"".$size[0]."px\" height=\"".$size[1]."px\"";
			
			$str.=$file;
		}
		else
			{
			return ""; // No picture also looks well at all?!
			$str.="noimage.gif";
		}
		$str.="\"".$size." border=\"0\">";
		if($link_full) $str="<a href=\"".$full."\" class=\"full_image\">".$str."</a>";
		return $str;
	}

	/**
	 * Delete content image
	 *
	 * @param what Content type
	 * @param id Content ID
	 * @param typ Image type
	 * @param all Delete all flag
	 * @return void
	 */
	function del_contentimg($what,$id,$typ,$all=1)
	{
		global $image_path;
		$file=$image_path.$what."/".$id.".".$typ;
		$file2=$image_path.$what."/".$id."_large.".$typ;
		$file3=$image_path.$what."/".$id."_full.".$typ;
		if(file_exists($file) && $all)
			unlink($file);
		
		if(file_exists($file2))
			unlink($file2);
		
		if(file_exists($file3))
			unlink($file3);
	}

	/**
	 * Calculate image dimensions
	 *
	 * @param img Image filename
	 * @param width Target width
	 * @param height Target height
	 * @param what Image type
	 * @return array Width and height
	 */
	function get_size($img,$width,$height,$what)
	{
		if(!is_array($img))
			{
			if(!$size=@getimagesize($img))
				{
				return 0;
			}
		}
		else $size=$img;
		// scale to full-size
		if($width>$size[0])
			{
			$width=$size[0];
		}
		if($height>$size[1])
			{
			$height=$size[1];
		}
		// end
		if($size[0]>=$size[1])
			{
			$s=$size[1]/$size[0];
			$height=$s*$width;
		}
		else
			{
			$s=$size[0]/$size[1];
			$width=$s*$height;
		}
		
		if($what==1)
			{
			return $width;
		}
		if($what==2)
			{
			return $height;
		}
	}

	/**
	 * Create thumbnail or resized image
	 *
	 * @param img Source image
	 * @param width1 Target width
	 * @param height1 Target height
	 * @param constrains Keep aspect ratio
	 * @param filter Apply filter
	 * @return int 0 if no resize needed, 1 on success
	 */
	function create_img($img,$width1,$height1,$constrains=1,$filter=0)
	{
		global $config_values;
		$quali=$config_values->picquali;
		$size=@getimagesize($img);
		if($width1==-1) $width1=$size[0];
		if($height1==-1) $height1=$size[1];
		if($constrains)
			{
			$width=get_size($img,$width1,$height1,1);
			$height=get_size($img,$width1,$height1,2);
		}
		else
			{
			$width=$width1;
			$height=$height1;
		}
		$typ=explode(".",$img);
		$typ=strtolower($typ[count($typ)-1]);
		global $supported_img;
		$supported=$supported_img;
		if($size[0]==$width && $size[1]==$height && !$filter)
			{
			return 0;
		}
		@ini_set("memory_limit","500M"); // To use more memory
		$img_new=imagecreatetruecolor($width,$height);
		if($typ=="jpg" || $typ=="jpeg")
			{
			$img_org=imagecreatefromjpeg($img);
			$typ="jpg";
		}
		if($typ=="gif")
			{
			$img_org=imagecreatefromgif($img);
		}
		if($typ=="png")
			{
			$img_org=imagecreatefrompng($img);
		}
		
		imagecopyresampled($img_new,$img_org,0,0,0,0,$width,$height,$size[0],$size[1]);
		if($filter)
			{
			if($filter==1) imagefilter($img_new,IMG_FILTER_GRAYSCALE);
			else if($filter==2) imagefilter($img_new,IMG_FILTER_NEGATE);
			else if($filter==3) imagefilter($img_new,IMG_FILTER_MEAN_REMOVAL);
			else if($filter==4) imagefilter($img_new,IMG_FILTER_EMBOSS);
		}
		if($typ=="jpg"  || $typ=="jpeg")
			{
			imagejpeg($img_new,$img,$quali);
		}
		if($typ=="gif")
			{
			imagegif($img_new,$img);
		}
		if($typ=="png")
			{
			$result=imagepng($img_new,$img, -1);
		}
		imagedestroy($img_new);
		imagedestroy($img_org);
		return 1;
	}

	/**
	 * Crop image to specified rectangle
	 * @param string $file_path Path to image file
	 * @param int $x Left offset
	 * @param int $y Top offset
	 * @param int $width Crop width
	 * @param int $height Crop height
	 * @return array {success: bool, width: int, height: int, error: string}
	 */
	function crop_image($file_path, $x, $y, $width, $height)
	{
		global $config_values;

		// Validate file exists
		if (!file_exists($file_path)) {
			return ['success' => false, 'error' => 'Bilddatei nicht gefunden'];
		}

		// Get original image dimensions
		$size = @getimagesize($file_path);
		if (!$size) {
			return ['success' => false, 'error' => 'Bilddatei konnte nicht gelesen werden'];
		}

		// Validate crop area doesn't exceed original
		if ($x + $width > $size[0] || $y + $height > $size[1]) {
			return ['success' => false, 'error' => 'Zuschnittfläche überschreitet Bildgrenzen'];
		}

		if ($x < 0 || $y < 0) {
			return ['success' => false, 'error' => 'Ungültige Zuschnittkoordinaten'];
		}

		if ($width < 10 || $height < 10) {
			return ['success' => false, 'error' => 'Zuschnittfläche zu klein'];
		}

		$type = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

		// Load original image based on type
		$src = null;
		if ($type === 'jpg' || $type === 'jpeg') {
			$src = @imagecreatefromjpeg($file_path);
		} elseif ($type === 'png') {
			$src = @imagecreatefrompng($file_path);
			// Preserve transparency for PNG
			imagealphablending($src, false);
			imagesavealpha($src, true);
		} elseif ($type === 'gif') {
			$src = @imagecreatefromgif($file_path);
		}

		if (!$src) {
			return ['success' => false, 'error' => 'Bild konnte nicht geladen werden'];
		}

		// Use PHP 5.5+ imagecrop if available, otherwise use imagecopyresampled
		if (function_exists('imagecrop')) {
			// PHP 5.5+: imagecrop() is cleaner
			$cropped = @imagecrop($src, ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height]);

			if (!$cropped) {
				imagedestroy($src);
				return ['success' => false, 'error' => 'Zuschneiden fehlgeschlagen'];
			}
		} else {
			// Fallback for older PHP: use imagecopyresampled
			$cropped = imagecreatetruecolor($width, $height);

			if ($type === 'png') {
				imagealphablending($cropped, false);
				imagesavealpha($cropped, true);
			}

			$result = @imagecopyresampled(
				$cropped, $src,
				0, 0, $x, $y,
				$width, $height, $width, $height
			);

			if (!$result) {
				imagedestroy($src);
				imagedestroy($cropped);
				return ['success' => false, 'error' => 'Zuschneiden fehlgeschlagen'];
			}
		}

		// Save cropped image with quality settings
		@ini_set("memory_limit", "500M");
		$quality = isset($config_values->picquali) ? intval($config_values->picquali) : 85;

		// Ensure quality is in valid range
		if ($quality < 10) $quality = 10;
		if ($quality > 100) $quality = 100;

		$save_success = false;
		if ($type === 'jpg' || $type === 'jpeg') {
			$save_success = @imagejpeg($cropped, $file_path, $quality);
		} elseif ($type === 'png') {
			// PNG compression level (0-9, -1 = default)
			$save_success = @imagepng($cropped, $file_path, -1);
		} elseif ($type === 'gif') {
			$save_success = @imagegif($cropped, $file_path);
		}

		imagedestroy($src);
		imagedestroy($cropped);

		if (!$save_success) {
			return ['success' => false, 'error' => 'Zugeschnittenes Bild konnte nicht gespeichert werden'];
		}

		return [
			'success' => true,
			'width' => $width,
			'height' => $height
		];
	}

?>
