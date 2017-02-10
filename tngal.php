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
$tng_dirpic="$tng_picbase/dir.png";
$tng_zippic="$tng_picbase/zip.png";
$tng_bsypic="$tng_picbase/bsy.gif";

$edpass="admin";
$edit=false;
$tng_embed=false;

// Override defaults?
if(file_exists("tngal_settings.php"))
   require_once("tngal_settings.php");

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

if( $tng_cmd == "button" ) $tng_cmd=$_POST['button'];

// Evaluate the command
switch( $tng_cmd ){
case "genThumb": // @todo: error handling...
	if( isset( $_GET["dir"] ) && isset( $_GET["file"] )) {
		echo generateTN($_GET["dir"], $_GET["file"] );
	} else {
		echo "$tng_picpic\n";
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
//				echo "<p>$body</p>\n";
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
        if( isset ( $_POST["tng_edfile"]) ) { 
            $tng_edfile=$_POST["tng_edfile"];
//          $count=count($tng_edfile);
            if( isset( $_POST["tng_eddir"] ) ) {
                $tng_eddir=$_POST["tng_eddir"];
                foreach( $tng_edfile as $pic ){
                    $base=pathinfo( $pic, PATHINFO_FILENAME );
                    $file=pathinfo( $pic, PATHINFO_BASENAME );
                    $path=pathinfo( $pic, PATHINFO_DIRNAME );
                    rename( "$pic", "$tng_eddir/$file" );
                    if(file_exists( "$path/.small/$base.jpg" ))
                        rename( "$path/.small/$base.jpg", "$tng_eddir/.small/$base.jpg" );
		        }
            } else {
                echo "<b>No target dir selected!</b><br>\n";
            }
        } else {
            echo "<b>No source file selected!</b><br>\n";
        }
    }
    browseDir($tng_path, $tng_sort);
    break;
case "delete":
	if($edit){
		$tng_edfile=array();
        if( isset( $_POST["tng_edfile"] ) ) { 		
		    $tng_edfile=$_POST["tng_edfile"];
	        foreach( $tng_edfile as $pic ){
		        $base=pathinfo( $pic, PATHINFO_FILENAME );
		        $file=pathinfo( $pic, PATHINFO_BASENAME );
		        $path=pathinfo( $pic, PATHINFO_DIRNAME );
		        unlink( "$pic" );
		        if(file_exists( "$path/.small/$base.jpg" )) {
			        unlink( "$path/.small/$base.jpg" );
		        }
	        }
        }
        if( isset( $_POST["tng_eddir"] ) ){
            echo "<h2>removal of directories is not yet implemented</h2>\n";
        }
	}
	browseDir($tng_path, $tng_sort);
	break;
case "openzip":
    $dirname=openZip($tng_path);
//    $tng_path=upDir($tng_path); // .$dirname."/";
    $tng_path=pathinfo( $tng_path, PATHINFO_DIRNAME )."/";
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
   $suffices=array( "aaf", "3gp", "asf", "avi", "fla", "flr", "flv", "m1v", "m2v", "m4v", "mpg", "mpeg", "mov", "rm", "webm", "wmv", "swf",  "mp4");
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
function generateTN( $dir, $file ){
	global $tng_picpic;
	$retval = $tng_picpic;

	if(function_exists("imagetypes")){
		$path=$dir.$file;
		$base   = pathinfo ( $file, PATHINFO_FILENAME );
		$suffix = strtolower( pathinfo ( $file, PATHINFO_EXTENSION ) );
		$tnname = "$dir.small/$base.jpg";

		if( is_mov( $file ) ) {
			$image = loadFrame( $path );
		} else {
			$image = loadImage( $path, $suffix );
		}
		if ( $image ){
			$im2=newSize( $image );
			if ($im2) {
		        ImageJpeg($im2, $tnname, 80);
				imagedestroy( $im2 );
				$retval=$tnname;
			}
			imagedestroy($image);
		}
	}

	return $retval;
}

function loadFrame( $path ) {
	if( class_exists ( ffmpeg_movie ) ) {
		@$mov = new ffmpeg_movie($path);
		if( $mov ) {
			$frame = $mov->getFrame(10);
			if ($frame) {
				return $frame->toGDImage();	
			}
		}
	}
	return false;
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
	global $tng_date, $tng_filename, $tng_thumbw, $tng_thumbh;
	global $tng_extension, $tng_thumbgen, $tng_cols, $tng_zip_up;
	global $tng_zip_dl, $tng_dirpic, $tng_zippic, $tng_picpic, $tng_bsypic;
	global $edit, $edpass;

	printhead( $dir, $sortmethod );	
	   	echo "<div style='padding: 10px; width:100%;'>\n";
	if ($edit){
		echo "<form action=\"\" method=\"post\">\n";
	}
	
	if ( is_dir($dir) ) {
		$haspic=false;
		$text=initText( $dir );
		if( $dir != "./" ) {
			$target="?tng_path=".upDir($dir);
			echo "<div style='width:100%'><a href=\"$target\">[up]</a></th> - ".substr($dir,2,strlen($dir)-1)."</div><hr style='clear: left;' />\n";
		}
		
		// get all files
		$dircont=fetchFiles($dir, $sortmethod );

		// check for images and movies
		foreach( $dircont as $file ){
			if( is_pic($file) || is_mov($file) ){
				$haspic=true;
				// Create thumbnails?
				$img_src=$tng_picpic;

				$tnname = $dir.".small/".pathinfo( $file, PATHINFO_FILENAME ).".jpg";

				if( !file_exists( $tnname ) ) {
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
					}
				} 
				else {
					$img_src=$tnname;
		           	}

				if( is_pic( $file ) ) $reference="?tng_cmd=showpic&tng_path=$dir$file";
				else $reference="$dir$file";

				echo "  <div style='float:left; padding:5px;'>\n";
				echo "    <div style='width:".($tng_thumbw+10)."px; min-height:".($tng_thumbh+10)."px; '>\n";
				echo "    <a href='$reference'><img";
				if( $img_src == $tng_bsypic ) {
					echo " id='".urlencode($file)."'";
				}
				if( is_mov( $file ) ) {
					echo " style='border:dashed; border-width:1px; border-color:#000;'";
				} else {
					echo " style='border:none;'";
				}
				echo " src='$img_src' alt='$file' title='$file'></a>\n";
				echo "    </div>\n";
				// Show filenames? @todo this looks more like comments are printed..
				// Display extensions?
			
				if($tng_filename){
					if( $tng_extension ) {
						$filename=$file;
					} else {
						$filename=pathinfo ( $file, PATHINFO_FILENAME );
						if( array_key_exists( $filename, $text ) ) $filename=$text[$filename];
					}
					echo "    $filename\n";
				}

				if( $tng_date != false ) {
					echo "    <br>(".date("$tng_date", filemtime( "$dir$file" ) ).")\n";
				}

				if($edit){
					echo "\n    <input type=\"checkbox\" name=\"tng_edfile[]\" value=\"$dir$file\">\n";
				}

				echo "  </div>\n";

			} // is pic or mov
		} // foreach

		// The current dir contains pics
		if($haspic){
			echo "<hr style='clear: left;' />\n<div style='width:100%'>";
			if($tng_zip_dl) {
				echo "<a href='?tng_path=$dir&tng_cmd=makezip'>(download zip)</a> \n";
			}
			echo "<a href='?tng_path=$dir&tng_cmd=slideshow'>Slideshow</a></div>\n";
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
					// sort thumbnails by name
					$thumbs=fetchFiles($uplevel, 0 ); // $sortmethod
					if( isset( $thumbs[0] ) ) $img_src=$uplevel."/".$thumbs[0];
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
			
			}
			// $file is an archive
			else if(is_arc($file)){
				if(!file_exists(substr( "$dir$file", 0, strrpos( "$dir$file", "." )))){
					$newtile=true;
					$img_src=$tng_zippic;
					$target=$dir.$file;
					if($edit){
						$zipact = "    <input type=\"checkbox\" name=\"tng_edfile[]\" value=\"$dir$file\">\n";
					}
					if($tng_zip_up)
						$zipact .= "    <a href='?tng_path=$dir$file&tng_cmd=openzip'>(unZIP)</a>\n";
				}
			}

			if( $newtile ) {
				echo "  <div style='float:left; width:".($tng_thumbw+10)."px; min-height:".($tng_thumbh+10)."px; padding:5px;'>\n";
				echo "    <a href='$target'><img src='$img_src' border='0' alt='$file'></a>\n";
				echo "    <br><a href='$target'>$file</a>\n";
				echo $zipact;				
				if( $tng_date != false ) {
					echo "    <br>(".date("$tng_date", filemtime( "$dir$file" ) ).")\n";
				}
				echo "  </div>\n";
			}
		}
	}

	if($edit){
		echo "<hr style='clear: left;' />\n";
		if( $dir != "./" ) {
			echo "<input type='radio' name='tng_eddir' value='".updir($dir)."'>&lt;up&gt;\n";
		}
		echo "<input type=\"submit\" name=\"button\" value=\"move\">\n";
		echo "<input type=\"hidden\" name=\"tng_cmd\" value=\"button\">\n";
		echo "<input type=\"hidden\" name=\"tng_pass\" value=\"$edpass\">\n";
		echo "<input type=\"hidden\" name=\"tng_path\" value=\"$dir\">\n";
		echo "<br>\n";
		echo "<input type=\"submit\" name=\"button\" value=\"delete\">\n";
		echo "<br>\n";
		echo "</form>\n";
		echo "<br>\n";
		echo "<form action=\"\" method=\"post\">\n";
		echo "<input type=\"text\" name=\"tng_eddir\">\n";
		echo "<input type=\"hidden\" name=\"tng_cmd\" value=\"mkdir\">\n";
		echo "<input type=\"hidden\" name=\"tng_pass\" value=\"$edpass\">\n";
		echo "<input type=\"hidden\" name=\"tng_path\" value=\"$dir\">\n";
		echo "<input type=\"submit\" value=\"Makedir\">\n";
		echo "</form>\n";
	}
	echo "</div>\n<!-- End of gallery -->\n";

	printfoot();
}

/**
 * unpack an uploaded zip archive
 */
function openZip( $path ){
	global $tng_zip_del;
	$target=pathinfo( $path, PATHINFO_DIRNAME );
	$dirname=pathinfo( $path, PATHINFO_FILENAME );
	if( !file_exists( $target.$dirname ) ){
	    $zip = new ZipArchive;
		$res = $zip->open($path);
		if ($res === TRUE) {
			$res = $zip->extractTo( $target );
			// @todo: check for success
			$zip->close();
			if( $tng_zip_del ) unlink( $path );
		} else {
			echo "Could not open $path!<br>\n";
			echo 'failed, code:' . $res;
		}
	}else{
	    echo "<h3>$target$dirname already exists!</h3>\n";
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
		$dircont=fetchFiles( $dir, $sortmethod );
		$curpic=0;
		$picnum=0;
		// Do not just use $dircont as that may also include videos
		foreach( $dircont as $file ){
			if( is_Pic( $file ) ) {
				if( $file == $current ) $curpic=$picnum;
				echo "piclist[$picnum]=\"$dir$file\"\n";
				$picnum++;
			}
		}
		if ( $slide != 0 ){ 
			echo "last=$curpic\n";
		}
		echo "initPicViewer( $curpic, $tng_time );\n";
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
		echo "    <script type='text/javascript' src='tngal.js'></script>\n";
		echo "  </head>\n";
		echo "  <body>\n";
	}
	if( $tng_sidebar ) {
		echo "  <div style='background-color:#ccc; width:100%; padding:5px;'>\n";
		echo "<!-- Start of menu -->\n";
		
		if( $tng_upload ) {
			echo "    <div style='float:left; width:30%; padding:5px;'>\n";
			echo "      <form enctype='multipart/form-data' action='' method='post'>\n";
			echo "        <input type='hidden' name='tng_cmd' value='upload' />\n";
			echo "        <input name='userfile' type='file' />\n";
			echo "        <input type='submit' value='upload' />\n";
			echo "      </form>\n";
			echo "    </div>\n";
		}
		echo "    <div style='float:left; width:30%; padding:5px;'>\n";
		$methods=array( 'by name asc', 'by name desc', 'newest first', 'oldest first' );
		echo "      <form action='' method='post'>";
		echo "        Sort:&nbsp;";
		echo "        <input type='hidden' name='tng_cmd' value='browse'>";
		echo "        <input type='hidden' name='tng_path' value='$dir'>";
		echo "        <select name='tng_sort'>";
		for( $i=0; $i<4; $i++ ) {
			echo "<option ";
			if( $sortmethod == $i ) echo "selected ";
			echo "value='$i'>".$methods[$i]."</option>";
		}
		echo "          </select>";
		echo "        <input type='submit' value='update'>";
		echo "      </form>";
		echo "    </div>\n";
		
		if( !$edit ) {
			echo "    <div style='float:right; padding:5px;'>\n";
			echo "      <form style='float:right;' action='' method='post'>\n";
			echo "        <input type='hidden' name='tng_cmd' value='browse'>\n";
			echo "        <input type='hidden' name='tng_path' value='$dir'>\n";
			echo "        <input type='password' name='tng_pass' size='10'>\n";
			echo "        <input type='submit' value='admin'>\n";
			echo "      </form>\n";
			echo "    </div>\n";
		}

		echo "    <br style='clear:left;'>\n";
		echo "  </div>\n";
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
