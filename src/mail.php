<?php
$time=(date(Y)%6-date(m)*2)*2;
if($time<0) $time*=-1;
$str=explode("_",$_GET["mail"]);
for($i=0;$i<count($str);$i++)
{
$a=$a.(chr($str[$i]-$time));
}
header("Location:mailto:".$a);
echo '
<script type="text/javascript">
history.back();
</script>';
?>