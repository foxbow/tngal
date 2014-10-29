<?php
global $tng_date, $tng_pichome, $tng_browse;
global $tng_thumbgen,$tng_home, $tng_cols, $tng_zip_dl, $tng_zip_up, $tng_dirpic;
global $tng_zippic, $tng_picpic, $tng_movpic, $tng_zip_del, $tng_filename;
global $tng_extension, $tng_time, $tng_thumbw, $tng_thumbh;
global $tng_tnghome;
// Test editing mode...
global $edit, $edpass;

// Default settings
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
// Show dates?
$tng_date=false;
// Show filenames?
$tng_filename=false;
// Show extensions?
$tng_extension=false;
// Enable browsing?
$tng_browse=true;
// Open new window for pics?
$tng_newwin=true;
// Home for browsing
$tng_path="/tngal";
$tng_home="$tng_path/index.html";
$tng_pichome="";
$tng_tnghome="$tng_path/tngal.php";
// Time between two pics
$tng_time=10;
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

$tng_picpic="$tng_path/tng_icons/pic.png";
$tng_movpic="$tng_path/tng_icons/mov.png";
$tng_dirpic="$tng_path/tng_icons/dir.png";
$tng_zippic="$tng_path/tng_icons/zip.png";

$edpass="admin";
$edit=false;

// Override defaults?
if(file_exists("tngal_settings.php"))
   require_once("tngal_settings.php");

if( isset($_POST['tng_sort']) ) $tng_sort=$_POST["tng_sort"];
else if( isset($_COOKIE["tng_sort"]) ) $tng_sort=$_COOKIE["tng_sort"];
else $tng_sort=0;

error_reporting (E_ALL);

// Get the path to the dir we want to display
if(isset($_GET["tng_path"])) $tng_path=$_GET["tng_path"];
if(isset($_GET["tng_cmd"])) $tng_cmd=$_GET["tng_cmd"];
if(isset($_GET["tng_pass"]) && ($_GET["tng_pass"] == $edpass) ) $edit=true;

// Forms override parameters!
if(isset($_POST["tng_path"])) $tng_path=$_POST["tng_path"];
if(isset($_POST["tng_cmd"])) $tng_cmd=$_POST["tng_cmd"];
if( isset($_POST["tng_pass"]) && ($_POST["tng_pass"] == $edpass) ) $edit=true;

// $tng_pichome is the page where the pictures wil be displayed in
// probably it's not the same as the gallery, maybe it is... who
// knows?
if($tng_pichome=="") $tng_pichome=$tng_home;

// Make sure there is a path
if(!isset($tng_path) || ($tng_path=="")) $tng_path="./";
// Do not allow backsteps
else if(strpos("../", $tng_path) > 0) $tng_path="./";
// Do not allow absolute paths
else if($tng_path{0}=="/") $tng_path="./";

// browse curent dir is the default command
if(!isset($tng_cmd)) $tng_cmd="browse";

// Evaluate the command
switch( $tng_cmd ){
case "upload":
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
			$transport = Swift_SmtpTransport::newInstance( $tng_swift_host, 25);
	  	    $transport->setUsername( $tng_swift_user );
	  	    $transport->setPassword( $tng_swift_pass );
		    $mailer = Swift_Mailer::newInstance($transport);
		    if (!$mailer->send($message, $failures)) {
	  		  echo "Failures:";
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
          $pos=strrpos( $pic, "/" );
          $file=substr( $pic, $pos+1 );
          $path=substr( $pic, 0, $pos+1 );
          rename( "$pic", "$tng_eddir/$file" );
          if(file_exists( "$path.small/$file" ))
            rename( "$path.small/$file", "$tng_eddir/.small/$file" );
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
   $suffices=array("mpg", "avi", "mpeg", "mov", "wmv", "swf");
   return testSuffix( $file, $suffices );
}

/**
 * checks if the given file is a valid archive
 **/
function is_arc($file){
   $suffices=array("zip");
   return testSuffix( $file, $suffices );
}

function testSuffix( $file, $suffices ){
   $file = strtolower($file);
   if(($offset=strrpos($file, '.')) === false) return false;
   $suffix=substr($file, $offset+1);
   foreach( $suffices as $test ){
      if($suffix == $test) return true;
   }
   return false;
}

function cutExtension( $file ){
    $name  =strtolower($file);
    $offset=strrpos($name, '.');
    $name  =substr($name, 0, $offset);
    return $name;
}

function generateTN($dir, $file){
  if(function_exists("imagetypes")){
    $path=$dir.$file;
    $suffix = strtolower($file);
    $offset=strrpos($suffix, '.');
    $suffix=substr($suffix, $offset+1);
    $image = loadImage( $path, $suffix );
	if ($image){
		$im2=newSize( $image );
		if ($im2) {
			saveImage( $im2, "$dir.small/$file", $suffix );
		} else {
   			echo "Could not create thumbnail for $path!<br>";
//		else symlink( "../$file", "$dir.small/$file" );
		}
    }else{
		echo "Problems loading $path!<br>";
	}
  }else{
    echo "No GD lib installed!<br>";
  }
}

function loadImage( $path, $suffix ){
    $imtypes=imagetypes();
    $image=false;
    if((($suffix=="jpg") || ($suffix=="jpeg")) && ($imtypes & IMG_JPG)){
      $image = @ImageCreateFromJpeg( $path );
    }else if($suffix=="gif" && ($imtypes & IMG_GIF)){
      $image = @ImageCreateFromGif( $path );
    }else if($suffix=="png" && ($imtypes & IMG_PNG)){
      $image = @ImageCreateFromPng( $path );
    }else if($suffix=="bmp" && ($imtypes & IMG_WBMP)){
      $image = @ImageCreateFromWbmp( $path );
    }
    return $image;
}

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

function upDir($dir){
   if($dir != "./"){
      // Cut off the trailing slash (if there, else just the final char)
      $dir=substr($dir, 0, strlen($dir)-1);
      // Find the last slash
      $dir=substr($dir, 0, strrpos($dir, '/')+1);
   }
   return $dir;
}

function initText( $path ){
  $text=array();
  if( file_exists($path."text.txt") ){
    $lines = file($path."text.txt");
    $buff="";
    foreach( $lines as $line ){
      $line=trim($line);
      if($buff=="") $buff=$line;
      else {
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

function fetchPics( $dir, $sortmethod, $pics=true ){
	$dircont=array();
    if ($handle = opendir($dir)) {
		$i=0;
		while ( ($file = readdir($handle) ) !== false) {
			if ( ($file{0} != ".") && ( !$pics || is_pic( $file ) ) ) { // && (is_readable( $file ))
				$dircont[$i]=$file;
				$i++;
			}
		}
       	closedir($handle);       	
		$dircont=sortdir( $dir, $dircont, $sortmethod );
	}
	return $dircont;
}

function browseDir( $dir, $sortmethod ){
	global $tng_newwin, $tng_date, $tng_pichome, $tng_browse, $tng_filename;
	global $tng_extension, $tng_thumbgen, $tng_home, $tng_cols, $tng_zip_up;
	global $tng_zip_dl, $tng_dirpic, $tng_zippic, $tng_picpic, $tng_movpic;
	global $edit, $edpass, $tng_tnghome;

	printhead( $dir, $sortmethod );	
   	echo "<table>\n";
	if ($edit){
		echo "<form action=\"$tng_home\" method=\"post\">\n";
	}
	
	if ( is_dir($dir) ) {
		$text=initText( $dir );
		if( $dir != "./" ) {
			if(file_exists(upDir($dir)."index.html")) $target=upDir($dir)."index.html";
			else $target="$tng_home?tng_path=".upDir($dir);
			echo "<tr><th><a href=\"$target\">[up]</a></th><th colspan=\"".($tng_cols-1)."\">".substr($dir,2,strlen($dir)-1)."</th></tr>\n";
		}
		$dircont=fetchPics($dir, $sortmethod, false);
         // Go through the list
		$haspic=false;
		$column=0;
		foreach( $dircont as $file ){
            if(is_pic($file)){
               $haspic=true;
               if($tng_thumbgen && !file_exists($dir.".small/".$file)){
                  if(!file_exists($dir.".small/")) mkdir($dir.".small", 0770);
                  generateTN($dir, $file);
               }
               if($column==0)
                 if ($tng_filename) echo "<tr valign=\"bottom\">\n";
                 else echo "<tr>\n";
               if($tng_extension) $filename=$file;
               else $filename=cutExtension($file);

//               if($tng_browse) $reference="\"$tng_pichome?tng_cmd=showpic&tng_path=$dir$file\"";
               if($tng_browse) $reference="\"$tng_tnghome?tng_cmd=showpic&tng_path=$dir$file\"";
               else $reference="\"$dir$file\"";

               if($tng_newwin) $reference=$reference." target=\"_blank\"";

               if(!file_exists($dir.".small/".$file)){
                  echo "  <td align=\"center\"><a href=$reference><img src=\"$tng_picpic\" border=\"0\" alt=\"$file\" title=\"$file\"></a>";
               }else{
                  echo "  <td align=\"center\"><a href=$reference><img src=\"$dir".".small/$file\" border=\"0\" alt=\"$file\" title=\"$file\"></a>";
               }
               if($tng_filename){
                   if(array_key_exists($filename, $text)) $filename=$text[$filename];
                   echo "<br>\n";
               }
               if($edit){
                 echo "    <input type=\"checkbox\" name=\"tng_edfile[]\" value=\"$dir$file\">\n";
               }
               if($tng_filename){
                 echo "    <a href=$reference>$filename</a>\n";
               }
               echo "</td>\n";
               $column=$column+1;
               if($column==$tng_cols){
                  $column=0;
                  echo "</tr>";
               }
            } else if(is_mov($file)) {
               if($column==0)
                 if ($tng_filename) echo "<tr valign=\"bottom\">\n";
                 else echo "<tr>\n";
               if($tng_extension) $filename=$file;
               else $filename=cutExtension($file);

               $reference="\"$dir$file\"";

               if($tng_newwin) $reference=$reference." target=\"_blank\"";

               echo "  <td align=\"center\"><a href=$reference><img src=\"$tng_movpic\" border=\"0\" alt=\"$file\" title=\"$file\"></a>";
               if($tng_filename) echo "<br>\n    <a href=$reference>$filename</a>";
               echo "</td>\n";
               $column=$column+1;
               if($column==$tng_cols){
                  $column=0;
                  echo "</tr>";
               }
            }
         }
         if($column > 0){
            for($i=$tng_cols; $i>$column; $i--) echo "  <td>&nbsp;</td>\n";
            echo "</tr>\n";
            $column=0;
         }

         if($haspic){
            echo "<tr><th colspan=\"$tng_cols\">";
            if($tng_zip_dl)
               echo "<a href=\"$tng_tnghome?tng_path=$dir&tng_cmd=makezip\">(download zip)</a> \n";
            echo "<a href=\"$tng_tnghome?tng_path=$dir&tng_cmd=slideshow\">Slideshow</a></th></tr>\n";
         }

         foreach( $dircont as $file ){
            if($tng_date) $date=date("Y-m-d", filemtime("$dir$file"));
            // $file is a directory and not a thumbnail container
            if(is_dir($dir.$file)){
               if($column==0) echo "<tr valign=\"bottom\">\n";
               echo "  <td align=\"center\">\n";
               $uplevel=$dir.$file."/.small";
               $thumbnail="";
               if(file_exists($uplevel) && is_dir($uplevel)){
                  if ($handle = opendir($uplevel)) {
                     while ((($tnfile = readdir($handle)) !== false) && ($thumbnail=="")){
                        if(is_readable($uplevel."/".$tnfile) && is_pic($tnfile)){
                           $thumbnail=$uplevel."/".$tnfile;
                        }
                     }
                     closedir($handle);
                  }
               }

               if(file_exists($dir.$file."/index.html")) $target=$dir.$file."/index.html";
               else if (file_exists($dir.$file."/index.html")) $target=$dir.$file."/index.html";
               else $target="$tng_home?tng_path=$dir$file/";

               if($thumbnail != ""){
                  echo "    <a href=\"$target\"><img src=\"$thumbnail\" border=\"0\" alt=\"(thumbnail)\"></a>\n";
               }else{
                  echo "    <a href=\"$target\"><img src=\"$tng_dirpic\" border=\"0\" alt=\"DIR\"></a>\n";
               }

               if(array_key_exists($file, $text)) $filename=$text[$file];
               else $filename=$file;

               echo "    <br>";
               if($edit){
                 echo "<input type=\"radio\" name=\"tng_eddir\" value=\"$dir$file\">\n";
               }

               echo "<a href=\"$target\">$filename</a>\n";
               if($tng_zip_dl){
                  echo "    <a href=\"$tng_tnghome?tng_path=$dir$file&tng_cmd=makezip\">(download)</a>\n";
               }
               if($tng_date)
                  echo "    <br>($date)\n";
               echo "  </td>\n";
               $column=$column+1;
            // $file is an archive
            }else if(is_arc($file)){
               if(!file_exists(substr( "$dir$file", 0, strrpos( "$dir$file", "." )))){
                  if($column==0) echo "<tr>\n";
                  echo "  <td align=\"center\">\n";
                  echo "    <a href=\"$dir$file\"><img src=\"$tng_zippic\" border=\"0\" alt=\"ZIP\"></a>\n";
                  echo "    <br><a href=\"$dir$file\">$file</a>\n";
                  if($tng_zip_up)
                     echo "    <a href=\"$tng_home?tng_path=$dir$file&tng_cmd=openzip\">(unZIP)</a>\n";
                  if($tng_date)
                     echo "    <br>($date)\n";
                  echo "  </td>\n";
                  $column=$column+1;
               }
            }
            if($column==$tng_cols){
               $column=0;
               echo "</tr>\n";
            }
         }
//    } // handle

      if($column > 0){
         for($i=$tng_cols; $i>$column; $i--) echo "  <td>&nbsp;</td>\n";
         echo "</tr>\n";
         $column=0;
      }
   }

   if($edit){
     echo "<tr><td colspan=\"$tng_cols\">\n";
     echo "<input type=\"submit\" value=\"Move\">\n";
     echo "<input type=\"hidden\" name=\"tng_cmd\" value=\"move\">\n";
     echo "<input type=\"hidden\" name=\"tng_pass\" value=\"$edpass\">\n";
     echo "<input type=\"hidden\" name=\"tng_path\" value=\"$dir\">\n";
     echo "</td></tr>\n";
     echo "</form>\n";
     echo "<tr><td colspan=\"$tng_cols\">\n";
     echo "<form action=\"$tng_home\" method=\"post\">\n";
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

function openZip( $path ){
  global $tng_home, $tng_zip_del;
  $dirname = substr( $path, strrpos( $path, "/" )+1, strrpos( $path, "." )-2);
//  $path=substr( $path, 2 );
  if(!file_exists($dirname)){
    $zip = new ZipArchive;
    $res = $zip->open($path);
    if ($res === TRUE) {
//    mkdir($dirname, 0777);
      $zip->extractTo("./");
      $zip->close();
      if( $tng_zip_del ) unlink($path);
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
  if( ($path != "./") && ( strrpos( $path, "/" ) == strlen( $path )-1 ) )
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
      }else{
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

  if($tng_zip_del || $dirname==".") unlink($zippath);
  // Make sure that the CMS won't come and confuse the page setup.
  exit();
}

/**
 * Slideshow with mobile support
*/
function showpic( $path, $sortmethod, $slide=0 ){
	global $tng_pichome, $tng_tnghome, $tng_time;

// Post header
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//DE\"\n";
	echo "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
	echo "<html>\n";
	echo "  <head>\n";
	echo "    <title>slidefox</title>\n";
	echo "    <script type='text/javascript' src='tngal.js'></script>\n";
	echo "  </head>\n";
	echo "  <body style=\"margin: 0px; width: 100%; height: 100%;\">\n";
	echo "    <div id='bgd' onselectstart='return false' onmousedown='return false' style='background-color:#fff; text-align:center;'><img width='100' height='100' onload='setPicDim();' src='' id='image' border='0'></div>\n";

// split between file (if any) and directory
	$dir = substr( $path, 0, strrpos( $path, "/" )+1 );
	$current = substr( $path, strrpos( $path, "/" )+1 );

// are we holding a proper directory?
	if (is_dir($dir)) {
		echo "<script>\n";
		echo "var tout=$tng_time;\n";
		echo "var mypic=document.getElementById('image');\n";
		echo "var bg=document.getElementById('bgd');\n";

		$dircont=fetchPics( $dir, $sortmethod, true );
		$curpic=0;
		$picnum=0;
		foreach( $dircont as $file ){
			if( $file == $current ) $curpic=$picnum;
			echo "piclist[$picnum]=\"$dir$file\"\n";
			$picnum++;
		}

		echo "var current=$curpic\n";
		echo "var lastpic=".($picnum-1)."\n";
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
global $tng_home, $edit;
	echo "  <table width='100%'>";
	echo "    <tr>";
	if( $sortmethod != -1 ){
		echo "      <td nowrap width='20%' align='left' valign='top' bgcolor='#cccccc'>"; // width='175px'
		echo "<!-- Start of menu -->";
		echo "        <p>&nbsp;<a href='$tng_home'>Home</a>&nbsp;</p>";
		echo "        <p>\n";
		echo "          <form enctype=\"multipart/form-data\" action=\"$tng_home\" method=\"post\">\n";
		echo "            <input type=\"hidden\" name=\"tng_cmd\" value=\"upload\" />\n";
		echo "            <input name=\"userfile\" type=\"file\" /><br>\n";
		echo "            <input type=\"submit\" value=\"upload file\" />\n";
		echo "          </form>\n";
		echo "        </p>\n";
		echo "		  <p>\n";
		echo "Sort<br>\n";
		$methods=array( 'by name asc', 'by name desc', 'newest first', 'oldest first' );
		echo "<form action='$tng_home' method='post'>\n";
		echo "<input type='hidden' name='tng_cmd' value='browse'>\n";
		echo "<input type='hidden' name='tng_path' value='$dir'>\n";
		echo "<select name='tng_sort'>\n";
		for( $i=0; $i<4; $i++ ) {
			echo "<option ";
			if( $sortmethod == $i ) echo "selected ";
			echo "value='$i'>".$methods[$i]."\n";
		}
		echo "</select>\n";
		echo "<input type='submit' value='update'>\n";
		echo "</form>\n";
		echo "        </p>\n";
		
		if( !$edit ) {
			echo "<p><form action='$tng_home' method='post'>\n";
			echo "<input type='hidden' name='tng_cmd' value='browse'>\n";
			echo "<input type='hidden' name='tng_path' value='$dir'>\n";
			echo "<input type='password' name='tng_pass' size='10'>\n";
			echo "<input type='submit' value='admin'>\n";
			echo "</form></p>\n";
		}
		echo "<h3>Imageview:</h3>\n";
		echo "<table>\n";
		echo "<tr><th>func</th><th>key</th><th>click</th><th>swipe</th></tr>\n";
		echo "<tr><td>prev image</td><td>&larr;</td><td>left edge</td><td>right</td></tr>\n";
		echo "<tr><td>next image</td><td>&rarr;</td><td>right edge</td><td>left</td></tr>\n";
		echo "<tr><td>slideshow</td><td>&darr;</td><td>bottom edge</td><td>down</td></tr>\n";
		echo "<tr><td>back</td><td>&uarr;</td><td>top edge</td><td>up</td></tr>\n";
		echo "</table>\n";
		// echo "        <p>&nbsp;<a href='/upload/upload.htm'>Upload</a>&nbsp;</p>";
		echo "<!-- End of menu -->";
		echo "      </td>";
	}
	echo "      <td align='center' valign='top'>";
}

function printfoot(){
	echo "      </td>";
	echo "    </tr>";
	echo "  </table>";
}

?>
