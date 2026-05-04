<?php
// Module: functions_mail.php

	/**
	 * Compare spam content
	 *
	 * @param a First content
	 * @param session Session ID
	 * @param count Check count
	 * @return 0|bool Comparison result
	 */
	function comp_spam($a,$session,$count)
	{
		if(strlen($session)<1) return 0;
		session_write_close();
		session_id($session);
		session_start();
		$spam=$_SESSION["spam".($count*1)];
		return $a==$spam && strlen($spam)==6;
	}

	/**
	 * Format email address safely
	 *
	 * @param mail Email address
	 * @param text Display text
	 * @param force_safe Force safe encoding
	 * @return string Formatted email or link
	 */
	function make_mail($mail,$text="",$force_safe=0)
	{
		global $config_values;
		if($config_values->safemail || $force_safe)
			{
			$a=strlen($mail);
			$time=(date(Y)%6-date(m)*2)*2;
			if($time<0) $time*=-1;
			for($i=0;$i<strlen($mail);$i++)
				{
				$str.=(ord($mail[$i])+$time);
				if($i!=strlen($mail)-1)
					{
					$str.="_";
				}
			}
			$str2=language("MAIL_SEND");
			if($text) $str2=$text;
			return "<a href=\"mail.php?mail=$str\">".$str2."</a>";
		}
		else
			{
			return "<a href=\"mailto:".$mail."\">".$mail."</a>";
		}
	}

	/**
	 * Check for spam in email
	 *
	 * @param s Content to check
	 * @return string|array<array-key, string>|null Cleaned content
	 */
	function mail_spam($s)
	{
		return preg_replace("/(\n+|\r+|%0A|%0D)/i", '', $s);
	}

	/**
	 * Send email message
	 *
	 * @param to Recipient email
	 * @param subject Email subject
	 * @param body Email body
	 * @return bool Success flag
	 */
	function my_mail($to,$subject,$body)
	{
		global $config_values;
		$to=mail_spam($to); // spam removing
		return mail($to,$subject,$body."
		
".$config_values->page."
".str_replace("%1",date("Y"),language("MAIL_BODY_FOOTER")),"FROM: ".$config_values->name." <".$config_values->mail.">");
	}

?>
