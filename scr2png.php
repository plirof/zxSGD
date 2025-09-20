<?php
$filename=$_GET['scrimg'];
if(!$filename) error("Invalid filename");
$handle = fopen($filename, "r") or error("Unable to open ".$filename);
if(filesize($filename)<>6912) { fclose($handle); error("Size of file must be 6912 bytes"); }
$zxscr = fread($handle, 6144);
$attrs = fread($handle, 768);
fclose($handle);
$tok=strtok($filename,".");
header("Content-type: image/png");
header("Content-Disposition: attachment; filename=".$tok.".png;");

$color_pal = array( 000, 000, 000,
                    000, 000, 192,
		    192, 000, 000,
		    192, 000, 192,
		    000, 192, 000,
		    000, 192, 192,
		    192, 192, 000,
		    192, 192, 192,
		    000, 000, 000,
		    000, 000, 255,
		    255, 000, 000,
		    255, 000, 255,
		    000, 255, 000,
		    000, 255, 255,
		    255, 255, 000,
		    255, 255, 255 );

$octet_binar = array(0, 0, 0, 0, 0, 0, 0, 0);
$attri_binar = array(0, 0, 0, 0, 0, 0, 0, 0);

$im = @imagecreate(256, 192) or die("Cannot Initialize new GD image stream");
imagecolorallocate($im, $color_pal[21], $color_pal[22], $color_pal[23]);

for($k=0;$k<3;$k++)
    {
        for($d=0;$d<8;$d++)
            {
		for($i=0;$i<8;$i++)
		    {
			$imtmp = @imagecreate(256, 1) or die("Cannot Initialize new GD image stream");
			for($octetnr=($d*32);$octetnr<(($d*32)+32);$octetnr++)
			    {
				$octet_val = base_convert(bin2hex($zxscr[$octetnr+(256*$i)+($k*2048)]),16,2);
				$attr_nr = base_convert(bin2hex($attrs[$octetnr+(256*$k)]),16,2);
				$pix=((8*$octetnr)-(256*$d));
				$piy=($i+(8*$d)+($k*64));
				if(strlen($octet_val) < 8)
				    {
					$pos = 7;
					for($a=strlen($octet_val)-1;$a>-1;$a--)
					    {
						$octet_binar[$pos]=$octet_val[$a];
						$pos--;
					    }
				    } else {
					for($a=0;$a<8;$a++)
					    {
						$octet_binar[$a]=$octet_val[$a];
					    }
				    }
				if(strlen($attr_nr) < 8)
				    {
					$pos = 7;
					for($a=strlen($attr_nr)-1;$a>-1;$a--)
					    {
						$attri_binar[$pos]=$attr_nr[$a];
						$pos--;
					    }
				    } else {
					for($a=0;$a<8;$a++)
					    {
						$attri_binar[$a]=$attr_nr[$a];
					    }
				    }
				$ink = $attri_binar[7]+2*$attri_binar[6]+4*$attri_binar[5];
				$paper = $attri_binar[4]+2*$attri_binar[3]+4*$attri_binar[2];
				$bright = $attri_binar[1];
				if($bright)
				    {
					$ink=$ink+8;
					$paper=$paper+8;
				    }
				for($a=0;$a<8;$a++)
				    {
					if($octet_binar[$a])
					    {
						$color = imagecolorallocate($imtmp, $color_pal[0+3*$ink],
						                                    $color_pal[1+3*$ink],
										    $color_pal[2+3*$ink]);
					    } else {
						$color = imagecolorallocate($imtmp, $color_pal[0+3*$paper],
						                                    $color_pal[1+3*$paper],
										    $color_pal[2+3*$paper]);
					    }
					imagesetpixel($imtmp, $pix, 0, $color);
					$pix++;
				    }
				for($a=0;$a<8;$a++)
				    {
					$octet_binar[$a]=0;
					$attri_binar[$a]=0;
				    }
			    }
			imagecopy ($im, $imtmp, 0, $piy, 0, 0, 256, 1);
			imagedestroy($imtmp);
		    }
	    }
    }
imagepng($im);
imagedestroy($im);
?>

<?php
function error($mesg)
{
header("Content-type: image/png");
header("Content-Disposition: attachment; filename=error.png;");
$im = @imagecreate(256, 192) or die("Cannot Initialize new GD image stream");
imagecolorallocate($im, 192, 192, 192);
$text_color=imagecolorallocate($im, 255, 0, 0);
imagestring($im, 3, 10, 5, $mesg, $text_color);
$text_color=imagecolorallocate($im, 0, 0, 255);
imagestring($im, 3, 40, 100, "(c) 2004 Catalin Mihaila", $text_color);
imagestring($im, 3, 55, 115, "mihaila_ac@yahoo.com", $text_color);
$text_color=imagecolorallocate($im, 0, 0, 0);
imagestring($im, 3, 40, 170, "R Tape loading error, 0:1", $text_color);
imagepng($im);
imagedestroy($im);
exit;
}
