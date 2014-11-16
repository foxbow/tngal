<?php
error_reporting (E_ALL);

// Default settings
$tng_sidebar=true;
// Allow upload?
$tng_upload=true;
// Generate thumbnails?
$tng_thumbgen=true;
$tng_thumbw=120;
$tng_thumbh=120;
// Offer to download zip files?
$tng_zip_dl=true;
// Delete .zip files after unpacking/downloading?
$tng_zip_del=false;
// Offer to unpack zip files?
$tng_zip_up=true;
// Default sort
$tng_sort=0;
// Show dates? false==no, "format"==yes
$tng_date="d.m.Y";
// Show filenames?
$tng_filename=true;
// Show extensions?
$tng_extension=true;
// Time between two pics
$tng_time=8;
// How many columns in preview
$tng_cols=5;
// default mailer
$tng_mailer="none";
// $tng_mailer="php";
$tng_from="tngal@host.com";
$tng_to="myaddress@host.com";
// $tng_mailer="swift";
$tng_swift_host="freemail.org";
$tng_swift_user=$tng_from;
$tng_swift_pass="Sup3rS3cr3t";

$tng_picbase="/tngal/tngal_icons";
$tng_picpic="$tng_picbase/pic.png";
$tng_movpic="$tng_picbase/mov.png";
$tng_dirpic="$tng_picbase/dir.png";
$tng_zippic="$tng_picbase/zip.png";
$tng_bsypic="$tng_picbase/bsy.gif";

$edpass="admin";
$edit=false;

// Override defaults?
if(file_exists("tngal_settings.php"))
   require_once("tngal_settings.php");

// if we're named 'tngal.php' we're embedded, else we're a page
// not sure if that works in any configuration though
$myname=pathinfo($_SERVER['SCRIPT_NAME'])['basename']."</h2>\n";
if( $myname == "tngal.php" ) {
	$tng_embed=true;
} else {
	$tng_embed=false;
}

// Sorting may be controlled through a cookie
// If sorting was changed, try to set the cookie before anything else is sent.
if( isset($_POST['tng_sort']) ) {
	$tng_sort=$_POST["tng_sort"];
	setcookie("tng_sort", $_POST['tng_sort']);
} else if( isset($_COOKIE["tng_sort"]) ) {
	$tng_sort=$_COOKIE["tng_sort"];
}

// Get the path to the dir we want to display
if( isset($_GET["tng_path"]) ) $tng_path=$_GET["tng_path"];
if( isset($_GET["tng_cmd"] ) ) $tng_cmd=$_GET["tng_cmd"];
if( isset($_GET["tng_pass"]) && ($_GET["tng_pass"] == $edpass) ) $edit=true;

// Forms override parameters!
if( isset($_POST["tng_path"]) ) $tng_path=$_POST["tng_path"];
if( isset($_POST["tng_cmd"] ) ) $tng_cmd=$_POST["tng_cmd"];
if( isset($_POST["tng_pass"]) && ( $_POST["tng_pass"] == $edpass ) ) $edit=true;

// Make sure there is a path
if(!isset($tng_path) || ($tng_path=="")) $tng_path="./";
// Do not allow backsteps
else if( strpos("../", $tng_path) > 0 ) $tng_path="./";
// Do not allow absolute paths
else if( $tng_path{0}=="/" ) $tng_path="./";

// browse curent dir is the default command
if( !isset($tng_cmd) ) $tng_cmd="browse";

// Evaluate the command
switch( $tng_cmd ){
case "genThumb": // @todo: error handling...
	if( isset( $_GET["dir"] ) && isset( $_GET["file"] )) {
		generateTN($_GET["dir"], $_GET["file"] );
		echo $_GET["dir"].".small/".$_GET["file"]."\n";
	} else {
		echo "$tng_img\n";
	}
	exit();
	break;
case "upload":
	if( !$tng_upload ){
		printhead();
		echo "<h1>Upload not permitted!</h1>\n";
		printfoot();
		exit();
	}
	if(isset($_FILES['userfile']['name']) && ($_FILES['userfile']['name'] != "")){
		$upfile=$_FILES['userfile']['name'];
		$uploaddir = './';
		$uploadfile = $uploaddir.$upfile;

		if(is_pic($upfile) || is_arc($upfile) || is_mov($upfile)){
			if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
				$body=$_FILES['userfile']['name'].' was successfully uploaded.';
				if( $tng_mailer == "swift" ) {
			  	    require_once 'swift_required.php';
					$message = Swift_Message::newInstance('New File in incoming');
					$message->setCharset('UTF-8');
					$message->setFrom(array($tng_from => 'Uploader'));
					$message->setTo( $tng_to );
					$message->setBody( $body );
					$transport = Swift_SmtpTransport::newInstance( $tng_swift_host, 587, 'tls');
			  	    $transport->setUsername( $tng_swift_user );
			  	    $transport->setPassword( $tng_swift_pass );
					$mailer = Swift_Mailer::newInstance($transport);
					if (!$mailer->send($message, $failures)) {
						echo "Failure:";
						print_r($failures);
					}
				} else if( $tng_mailer == "php" ) {
					mail( $tng_to, "New File in incoming", $body,
						"From: $tng_from\r\n" .
						"Reply-To: $tng_from\r\n" .
						"X-Mailer: PHP/" . phpversion());
				}
				echo "<p>$body</p>\n";
			} else {
				echo "<p>File upload failed - check the length to be below 2MB!</p>\n";
			}
		} else {
			echo "<p>$upfile is no valid file!</p>";
			unlink($_FILES['userfile']['tmp_name']);
		}
		echo "<hr />\n";
	}
	browseDir($tng_path, $tng_sort);
	break;
case "makezip":
    makeZip($tng_path);
    break;
case "showpic":
    showPic( $tng_path, $tng_sort );
    break;
case "slideshow":
    showPic( $tng_path, $tng_sort, 1 );
    break;
case "mkdir":
    if($edit){
      $tng_eddir=$_POST["tng_eddir"];
      if($tng_eddir!=""){
        mkdir("$tng_eddir", 0750);
        mkdir("$tng_eddir/.small", 0750 );
      }else{
        echo "<b>No name given!</b><br>\n";
      }
    }
    browseDir($tng_path, $tng_sort);
    break;
case "move":
    if($edit){
      $tng_edfile=array();
      $tng_edfile=$_POST["tng_edfile"];
      $count=count($tng_edfile);
      $tng_eddir=$_POST["tng_eddir"];
      if($tng_eddir=="") echo "<b>No target dir selected!</b><br>\n";
      else if ($count==0) echo "<b>No source file selected!</b><br>\n";
      else{
	    foreach( $tng_edfile as $pic ){
          $file=pathinfo( $pic, PATHINFO_FILENAME );
          $path=pathinfo( $pic, PATHINFO_DIRNAME );
          rename( "$pic", "$tng_eddir/$file" );
          if(file_exists( "$path.small/$file" ))
            rename( "$path/.small/$file", "$tng_eddir/.small/$file" );
        }
      }
    }
    browseDir($tng_path, $tng_sort);
    break;
case "remove":
	if($edit){
		$tng_edfile=array();
		$tng_edfile=$_POST["tng_edfile"];
		$count=count($tng_edfile);
		foreach( $tng_edfile as $pic ){
			$file=pathinfo( $pic, PATHINFO_FILENAME );
			$path=pathinfo( $pic, PATHINFO_DIRNAME );
			unlink( "$pic" );
			if(file_exists( "$path.small/$file" )) {
				unlink( "$path.small/$file" );
			}
		}
	}
	browseDir($tng_path, $tng_sort);
	break;
case "openzip":
    $dirname=openZip($tng_path);
    $tng_path=upDir($tng_path).$dirname."/";
default:
    browseDir($tng_path, $tng_sort );
}

/**
 * checks if the given file is a valid image
 * just judging the suffix here.
 **/
function is_pic($file){
   $suffices=array("jpg", "jpeg", "gif", "png");
   return testSuffix( $file, $suffices );
}

/**
 * same goes for movies
 **/
function is_mov($file){
   $suffices=array( "aaf", "3gp", "asf", "avi", "fla", "flr", "flv", "m1v", "m2v", "m4v", "mpg", "mpeg", "mov", "rm", "wmv", "swf",  "mp4");
   return testSuffix( $file, $suffices );
}

/**
 * checks if the given file is a valid archive
 **/
function is_arc($file){
   $suffices=array("zip");
   return testSuffix( $file, $suffices );
}

/**
 * helperfunction to test a file against a list of suffixes
 */
function testSuffix( $file, $suffices ){
	$suffix=pathinfo ( $file, PATHINFO_EXTENSION );
	if( !isset( $suffix ) ) return false;
	$suffix=strtolower($suffix);
	foreach( $suffices as $test ){
		if($suffix == $test) return true;
	}
	return false;
}

/**
 * load an image, resize it and save the thumbnail
 */
function generateTN($dir, $file){
	if(function_exists("imagetypes")){
		$path=$dir.$file;
		$suffix = strtolower( pathinfo ( $file, PATHINFO_EXTENSION ) );
		$image = loadImage( $path, $suffix );
		if ($image){
			$im2=newSize( $image );
			if ($im2) {
				saveImage( $im2, "$dir.small/$file", $suffix );
			} else {
				echo "Could not create thumbnail for $path!<br>";
			}
		}else{
			echo "Problems loading $path!<br>";
		}
	}else{
		echo "No GD lib installed!<br>";
	}
}

/**
 * load an image according to it's suffix
 */
function loadImage( $path, $suffix ){
    $imtypes=imagetypes();
    $image=false;
    if((($suffix=="jpg") || ($suffix=="jpeg")) && ($imtypes & IMG_JPG)){
		$changed=true;
		$image = ImageCreateFromJpeg( $path );

		// try to fix orientation
		$exif = exif_read_data($path);
		//determine what oreientation the image was taken at
		if( isset( $exif['Orientation'] ) ) {
			$orient=$exif['Orientation'];
//			echo "$path - $orient\n";
			switch($orient) {
			    case 3: // 180 rotate left
			        $image = imagerotate($image, 180, -1);
			    	break;
				case 5:
			    case 6: // 90 rotate right
			        $image = imagerotate($image, -90, -1);
			    	break;
				case 7:
			    case 8: // 90 rotate left
			        $image = imagerotate($image, 90, -1);
			    	break;
				default: // orientation is correct or mirrored
					$changed=false;
					break;
			}
			if( $changed ) {
				saveImage( $image, $path, $suffix );
			}
		}
    }else if($suffix=="gif" && ($imtypes & IMG_GIF)){
		$image = @ImageCreateFromGif( $path );
    }else if($suffix=="png" && ($imtypes & IMG_PNG)){
		$image = @ImageCreateFromPng( $path );
    }else if($suffix=="bmp" && ($imtypes & IMG_WBMP)){
		$image = @ImageCreateFromWbmp( $path );
    }
    return $image;
}

/**
 * save an image according to it's suffix
 */
function saveImage( $image, $path, $suffix ){
    $imtypes=imagetypes();
    if((($suffix=="jpg") || ($suffix=="jpeg")) && ($imtypes & IMG_JPG)){
        ImageJpeg($image, $path, 80);
    }else if($suffix=="gif" && ($imtypes & IMG_GIF)){
        ImageGif($image, $path );
    }else if($suffix=="png" && ($imtypes & IMG_PNG)){
        ImagePng($image, $path );
    }else if($suffix=="bmp" && ($imtypes & IMG_WBMP)){
        ImageWbmp($image, $path);
    }
}

/**
 * actual resizing of an image while keeping the aspect ratio
 */
function newSize( $image ){
global $tng_thumbw, $tng_thumbh;
    $width = $tng_thumbw;
    $height = $tng_thumbh;
    $iw=imagesx($image);
    $ih=imagesy($image);
    if ( ( $iw < $width ) && ($ih < $height ) ) return false;
    if ($width && ($iw < $ih)) {
        $width = ($height / $ih) * $iw;
    } else {
        $height = ($width / $iw) * $ih;
    }

    if(function_exists("gd_info")){
        $im2 = ImageCreateTrueColor( $width, $height );
        imagecopyResampled( $im2, $image, 0, 0, 0, 0, $width, $height, $iw, $ih );
    }else{
        $im2 = ImageCreate( $width, $height );
        imagecopyResized( $im2, $image, 0, 0, 0, 0, $width, $height, $iw, $ih );
    }
    return $im2;
}

/**
 * move up one directory level
 */
function upDir($dir){
   if($dir != "./"){
      // Cut off the trailing slash (if there, else just the final char)
      $dir=substr($dir, 0, strlen($dir)-1);
      // Find the last slash
      $dir=substr($dir, 0, strrpos($dir, '/')+1);
   }
   return $dir;
}

/**
 * turns the text file into an array
 * Format of the file:
 * <filename1>
 * <text for filename 1>
 * <filename2>
 * <text for filename 2>
 * :
 * This gets turnd into an array:
 * $text['filename1']='text for filename 1'
 * $text['filename2']='text for filename 2'
 * :
 */
function initText( $path ){
	$text=array();
	if( file_exists($path."text.txt") ) {
		$lines = file($path."text.txt");
		$buff="";
		foreach( $lines as $line ){
			$line=trim($line);
			if($buff=="") {
				$buff=$line;
			 } else {
				$text[$buff]=$line;
				$buff="";
			}
		}
	}
	return $text;
}

/*
 * sortmethod:
 *  0 - name asc / default
 *  1 - name desc / missing
 *  2 - age asc
 *  3 - age desc
 */
function sortdir( $dir, $dircont, $sortmethod=0 ) {
	if( $sortmethod < 2 ) {
		sort( $dircont );
	} else {
  		$odir = getcwd();
  		chdir( $dir );
  		if( $sortmethod == 2 ) {
	  		usort( $dircont, function($a, $b) {
    			return filemtime( $a ) < filemtime( $b );
  			});
  		} else {
	  		usort( $dircont, function($a, $b) {
    			return filemtime( $a ) > filemtime( $b );
  			});
  		}
		chdir( $odir );
	}
	return $dircont;
}

/**
 * returns an array of all files files in the given $dir
 * sorted by $sortmethod. 
 */
function fetchFiles( $dir, $sortmethod  ){
	$dircont=array();
    if ($handle = opendir($dir)) {
		$i=0;
		while ( ($file = readdir($handle) ) !== false) {
			if ( ($file{0} != ".") ) { // && (is_readable( $file ))
				$dircont[$i]=$file;
				$i++;
			}
		}
       	closedir($handle);       	
		$dircont=sortdir( $dir, $dircont, $sortmethod );
	}
	return $dircont;
}

/**
 * main view
 */
function browseDir( $dir, $sortmethod ){
	global $tng_date, $tng_filename;
	global $tng_extension, $tng_thumbgen, $tng_cols, $tng_zip_up;
	global $tng_zip_dl, $tng_dirpic, $tng_zippic, $tng_picpic, $tng_movpic, $tng_bsypic;
	global $edit, $edpass;

	printhead( $dir, $sortmethod );	
echo "<script>
function genThumb( dir, file ) {
	var xmlhttp;
	xmlhttp=new XMLHttpRequest();

	xmlhttp.onreadystatechange=function() {
  		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			document.getElementById(file).src=xmlhttp.responseText;
  		}
	}

	xmlhttp.open('GET', '?tng_cmd=genThumb&file='+file+'&dir='+dir, true);
	xmlhttp.send();
}
</script>";
   	echo "<table style='margin:0px auto;'>\n";
	if ($edit){
		echo "<form action=\"\" method=\"post\">\n";
	}
	
	if ( is_dir($dir) ) {
		$haspic=false;
		$text=initText( $dir );
		if( $dir != "./" ) {
			if(file_exists(upDir($dir)."index.html")) $target=upDir($dir)."index.html";
			else $target="?tng_path=".upDir($dir);
			echo "<tr><th><a href=\"$target\">[up]</a></th><th colspan=\"".($tng_cols-1)."\">".substr($dir,2,strlen($dir)-1)."</th></tr>\n";
		}
		
		// get all files
		$dircont=fetchFiles($dir, $sortmethod );
		$column=0;

		// check for images and movies
		foreach( $dircont as $file ){
			$newtile=false;
			if(is_pic($file)){
				$newtile=true;
				$haspic=true;
				// Create thumbnails?
				$img_src=$tng_picpic;
				if( !file_exists( $dir.".small/".$file ) ) {
					if( $tng_thumbgen ) {
						if(!file_exists($dir.".small/")) {
							if( @mkdir($dir.".small", 0770) === false ) {
								echo "<h1>Insufficient rights in $dir!</h1>\n";
								printfoot();
								exit();
							}
						}
						$img_src=$tng_bsypic;
						echo "<script>genThumb( '".urlencode($dir)."', '".urlencode($file)."' );</script>\n";
//						generateTN($dir, $file);
					}
				} else {
					$img_src=$dir.".small/$file";
	           	}


				$reference="?tng_cmd=showpic&tng_path=$dir$file";

			// Is the file a movie?
			} else if( is_mov($file) ) {
				$newtile=true;
				$reference=$dir.$file;
				$img_src=$tng_movpic;
	        }

			// put the actual tile on the page
			if( $newtile ) {
				// New row of images?
				if($column==0) {
					// @todo: align on bottom when ethere's text?
					if ( $tng_filename || $tng_date ) {
						echo "<tr style='vertical-align:bottom'>\n";
					} else {
						echo "<tr style='vertical-align:center'>\n";
					}
				}

				echo "  <td align='center'>\n";
				echo "    <a href='$reference'>";
				if( $img_src == $tng_bsypic ) {
					echo "<img id='".urlencode($file)."' src='$img_src' border='0' alt='$file' title='$file'></a>\n";
				} else {
					echo "<img src='$img_src' border='0' alt='$file' title='$file'></a>\n";
				}

				// Show filenames? @todo this looks more like comments are printed..
				// Display extensions?

			
				if($tng_filename){
					if( $tng_extension ) {
						$filename=$file;
					} else {
						$filename=pathinfo ( $file, PATHINFO_FILENAME );
						if( array_key_exists( $filename, $text ) ) $filename=$text[$filename];
					}
					echo "    <br>$filename\n";
				}

				if( $tng_date != false ) {
					echo "    <br>".date("$tng_date", filemtime( "$dir$file" ) )."\n";
				}

				if($edit){
					echo "\n    <input type=\"checkbox\" name=\"tng_edfile[]\" value=\"$dir$file\">\n";
				}

				echo "  </td>\n";

				// Last column?
				$column=$column+1;
				if($column==$tng_cols){
					$column=0;
					echo "</tr>\n";
				}

			} // is pic or mov
		}

		// fill up missing tiles
		if($column > 0){
			for($i=$tng_cols; $i>$column; $i--) echo "  <td>&nbsp;</td>\n";
			echo "</tr>\n";
			$column=0;
		}

		// The current dir contains pics
		if($haspic){
			echo "<tr><th colspan='$tng_cols'>";
			if($tng_zip_dl) {
				echo "<a href='?tng_path=$dir&tng_cmd=makezip'>(download zip)</a> \n";
			}
			echo "<a href='?tng_path=$dir&tng_cmd=slideshow'>Slideshow</a></th></tr>\n";
		}

		// now check for subdirs and archives
		foreach( $dircont as $file ){
			$newtile=false;
			$zipact="";

            // $file is a directory and not a thumbnail container
            if( is_dir( $dir.$file ) && ( $file != 'tngal_icons' ) ){
				$newtile=true;
				$uplevel=$dir.$file."/.small";
				$img_src=$tng_dirpic;
				if( file_exists( $uplevel ) && is_dir( $uplevel ) ) {
					if( $handle = opendir( $uplevel ) ) {
						while( ( ( $tnfile = readdir( $handle ) ) !== false) && ( $img_src == $tng_dirpic ) ) {
							if( is_readable( $uplevel."/".$tnfile ) && is_pic( $tnfile ) ) {
								$img_src=$uplevel."/".$tnfile;
							}
						}
						closedir($handle);
					}
				}
				$target="?tng_path=$dir$file/";

				if( array_key_exists( $file, $text ) ) {
					$filename=$text[$file];
				} else { 
					$filename=$file;
				}

				if($edit){
					$zipact = "    <input type='radio' name='tng_eddir' value='$dir$file'>\n";
				}

				if( $tng_zip_dl ) {
					$zipact .= "    <a href='?tng_path=$dir$file&tng_cmd=makezip'>(download)</a>\n";
				}
			// $file is an archive
            }else if(is_arc($file)){
				if(!file_exists(substr( "$dir$file", 0, strrpos( "$dir$file", "." )))){
					$newtile=true;
					$img_src=$tng_zippic;
					$target=$dir.$file;
					if($tng_zip_up)
                     	$zipact = "    <a href='?tng_path=$dir$file&tng_cmd=openzip'>(unZIP)</a>\n";
				}
            }

			if( $newtile ) {
				if( $column==0 ) {
					echo "<tr valign='bottom'>\n";
				}

				echo "  <td align='center'>\n";
				echo "    <a href='$target'><img src='$img_src' border='0' alt='$file'></a>\n";
				echo "    <br><a href='$target'>$file</a>\n";
				echo $zipact;				
				if( $tng_date != false ) {
					echo "    <br>(".date("$tng_date", filemtime( "$dir$file" ) ).")\n";
				}
				echo "  </td>\n";

				$column=$column+1;
			}

            if($column==$tng_cols){
               $column=0;
               echo "</tr>\n";
			}
		}

		if($column > 0){
			 for($i=$tng_cols; $i>$column; $i--) echo "  <td>&nbsp;</td>\n";
			 echo "</tr>\n";
			 $column=0;
		}
	}

	if($edit){
		echo "<tr><td>\n";
		echo "<input type=\"submit\" value=\"Move\">\n";
		echo "<input type=\"hidden\" name=\"tng_cmd\" value=\"move\">\n";
		echo "<input type=\"hidden\" name=\"tng_pass\" value=\"$edpass\">\n";
		echo "<input type=\"hidden\" name=\"tng_path\" value=\"$dir\">\n";
		echo "</td>\n";
		echo "<td>\n";
		echo "<input type=\"submit\" value=\"Delete\">\n";
		echo "<input type=\"hidden\" name=\"tng_cmd\" value=\"remove\">\n";
		echo "<input type=\"hidden\" name=\"tng_pass\" value=\"$edpass\">\n";
		echo "<input type=\"hidden\" name=\"tng_path\" value=\"$dir\">\n";
		echo "</td></tr>\n";
		echo "</form>\n";
		echo "<tr><td colspan=\"$tng_cols\">\n";
		echo "<form action=\"\" method=\"post\">\n";
		echo "<input type=\"text\" name=\"tng_eddir\">\n";
		echo "<input type=\"hidden\" name=\"tng_cmd\" value=\"mkdir\">\n";
		echo "<input type=\"hidden\" name=\"tng_pass\" value=\"$edpass\">\n";
		echo "<input type=\"hidden\" name=\"tng_path\" value=\"$dir\">\n";
		echo "<input type=\"submit\" value=\"Makedir\">\n";
		echo "</form></td></tr>\n";
	}
	echo "</table>\n<!-- End of gallery -->\n";

	printfoot();
}

/**
 * unpack an uploaded zip archive
 */
function openZip( $path ){
	global $tng_zip_del;
	$dirname=pathinfo( $path, PATHINFO_FILENAME );
	if( !file_exists( $dirname ) ){
	    $zip = new ZipArchive;
		$res = $zip->open($path);
		if ($res === TRUE) {
			$zip->extractTo( pathinfo( $path, PATHINFO_DIRNAME ) );
			$zip->close();
			if( $tng_zip_del ) unlink( $path );
		} else {
			echo "Could not open $path!<br>\n";
			echo 'failed, code:' . $res;
		}
	}
	return $dirname;
}

/**
 * function to download a directory as zip file.
 * This will create the .zip if it's not there and
 * will delete it after download if $tng_zip_del is
 * set true.
 **/
function makeZip( $path ){
	global $tng_zip_del;
// Make sure the path doesn't end with a '/'
	if( ( $path != "./" ) && ( strrpos( $path, "/" ) == strlen( $path )-1 ) )
		$path=substr( $path, 0, strlen( $path )-1 );

// The plain name of the dir
	$dirname = substr( $path, strrpos( $path, "/" )+1);
	if($dirname==""){
		$dirname=".";
		$zipname="pics.zip";
	}else{
		$zipname = "$dirname.zip";
		$zippath = "$path.zip";
	}

	if(!file_exists($zippath)){
		$zip = new ZipArchive;
		$res = $zip->open("$zippath", ZipArchive::CREATE);
		if ($res === TRUE) {
			if ($handle = opendir($path)) {
				while (($file = readdir($handle)) !== false) {
					if(is_pic($file)){
						$res = $zip->addFile("$path/$file", "$dirname/$file");
						if ($res === false) {
							echo "<b>Could not add $path/$file to $zipname!</b><br>\n";
							// echo $zip->errorInfo(true);
							unlink($zippath);
							return;
						}
					}
				}
				closedir($handle);
				$zip->close();
			} else {
				echo "<b>$dirname ($path) is not accessible!</b>\n";
			}
		}
	}
	// add header and stream stuff...
	header("Content-Description: File Transfer");
	header("Content-Type: application/zip");
	header("Content-Disposition: attachment; filename=".$zipname);
	header("Content-Length: ".filesize($zippath)."bytes");
	readfile($zippath);

	if( $tng_zip_del || $dirname=="." ) unlink( $zippath );
	// Make sure that nothing else gets sent
	exit();
}

/**
 * Slideshow with mobile support
*/
function showpic( $path, $sortmethod, $slide=0 ){
	global $tng_time;

// Post header
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//DE\"\n";
	echo "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
	echo "<html>\n";
	echo "  <head>\n";
	echo "    <title>slidefox</title>\n";
	echo "    <script type='text/javascript' src='tngal.js'></script>\n";
	echo "  </head>\n";
	echo "  <body style=\"background-color:#000; margin: 0px; width: 100%; height: 100%;\">\n";
	echo "    <div id='bgd' onselectstart='return false' onmousedown='return false' style='background-color:#000; text-align:center; margin: 0px;'><img width='100' height='100' onload='setPicDim();' src='' id='image' border='0'></div>\n";

// split between file (if any) and directory
	if( is_dir( $path ) ) {
		$dir=$path;
		$current="";
	} else {
		$dir = pathinfo( $path, PATHINFO_DIRNAME )."/";
		$current = pathinfo( $path, PATHINFO_BASENAME );
	}

// are we holding a proper directory?
	if (is_dir($dir)) {
		echo "<script>\n";
		
echo "var ddir='$dir';\n";
echo "var dcur='$current';\n";
		echo "var tout=$tng_time;\n";
		echo "var mypic=document.getElementById('image');\n";
		echo "var bg=document.getElementById('bgd');\n";

		$dircont=fetchFiles( $dir, $sortmethod );
		$curpic=0;
		$picnum=0;
		foreach( $dircont as $file ){
			if( is_Pic( $file ) ) {
				if( $file == $current ) $curpic=$picnum;
				echo "piclist[$picnum]=\"$dir$file\"\n";
				$picnum++;
			}
		}

		echo "var current=$curpic\n";
		echo "var lastpic=".($picnum-1)."\n";
		echo "initPicViewer();\n";
		echo "loadPic($curpic);\n";
		if ( $slide != 0 ){ 
			echo "last=current\n";
			echo "setTimeout( \"nextpic()\", 1000 );\n";
		}
		echo "</script>\n";
	}else{
    	echo "    <h1>Could not access $dir!</h1>\n";
	}

	echo "  </body>\n";
	echo "</html>\n";
}

function printhead( $dir="./", $sortmethod=-1 ){
global $edit, $tng_sidebar, $tng_upload, $tng_embed;
	if( !$tng_embed ) {
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//DE\"\n";
		echo "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
		echo "<html>\n";
		echo "  <head>\n";
		echo "    <title>Gallery</title>\n";
		echo "  </head>\n";
		echo "  <body>\n";
	}
	if( $tng_sidebar ) {
		echo "  <div style='float:left; width:20%; background-color:#ccc'>\n";
		echo "<!-- Start of menu -->";
//		echo "        <p>&nbsp;<a href='?'>Home</a>&nbsp;</p>";
		if( $tng_upload ) {
			echo "        <p>\n";
			echo "          <form enctype=\"multipart/form-data\" action=\"\" method=\"post\">\n";
			echo "            <input type=\"hidden\" name=\"tng_cmd\" value=\"upload\" />\n";
			echo "            <input name=\"userfile\" type=\"file\" /><br>\n";
			echo "            <input type=\"submit\" value=\"upload file\" />\n";
			echo "          </form>\n";
			echo "        </p>\n";
		}
		echo "		  <p>\n";
		echo "Sort<br>\n";
		$methods=array( 'by name asc', 'by name desc', 'newest first', 'oldest first' );
		echo "<form action='' method='post'>\n";
		echo "<input type='hidden' name='tng_cmd' value='browse'>\n";
		echo "<input type='hidden' name='tng_path' value='$dir'>\n";
		echo "<select name='tng_sort'>\n";
		for( $i=0; $i<4; $i++ ) {
			echo "<option ";
			if( $sortmethod == $i ) echo "selected ";
			echo "value='$i'>".$methods[$i]."</option>\n";
		}
		echo "</select>\n";
		echo "<input type='submit' value='update'>\n";
		echo "</form>\n";
		echo "        </p>\n";
	
		if( !$edit ) {
			echo "<p><form action='' method='post'>\n";
			echo "<input type='hidden' name='tng_cmd' value='browse'>\n";
			echo "<input type='hidden' name='tng_path' value='$dir'>\n";
			echo "<input type='password' name='tng_pass' size='10'>\n";
			echo "<input type='submit' value='admin'>\n";
			echo "</form></p>\n";
		}

		echo "</div>\n";
		echo "<!-- End of menu -->\n";
	}
	echo "<!-- Start of picview -->\n";
}

function printfoot(){
	global $tng_embed;
	if( ! $tng_embed ) {
		echo "  </body>\n";
		echo "</html>\n";
	}
}
?>
