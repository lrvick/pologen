<?php
$mime_type        = "image/png" ; // mime type of file to pretend to be
$compression      = "95"        ; // compression 
$trimming         = "yes"       ; // trim canvas after rendering, yes or no
$canvas_height    = "800"       ; // height of entire canvas
$canvas_width     = "800"       ; // width of entire canvas
$shadow_color     = "black"     ; // color of drop shadow
$size[0]          = "140"       ; // size in pixils for top image
$size[1]          = "120"       ; // size in pixils for middle image
$size[2]          = "100`"      ; // size in pixils for bottom image
$angles[0]        = "-5"        ; // angle in degrees for top image
$angles[1]        = "10"        ; // angle in degrees for middle image
$angles[2]        = "-15"       ; // angle in degrees for bottom image
$offsets_y[0]     = "80"        ; // left offset for top image
$offsets_y[1]     = "0"         ; // left offset for middle image
$offsets_y[2]     = "100"       ; // left offset for bottom image
$offsets_x[0]     = "210"       ; // top offset for top image
$offsets_x[1]     = "90"        ; // top offset for middle image
$offsets_x[2]     = "0"         ; // top offset for bottom image
$pic_dir          = "./"        ; // directory where images are stored
$cachedir         = "./cache"   ; // directory where cache is stored
$send_buffer_size = "4096"      ; // send buffer size
$bottom_white     = "20"        ; // White under picture

// A little prep
if (! is_dir($cachedir)) {
  mkdir($cachedir);
}


$picdir = opendir($pic_dir);
while (($file = readdir($picdir)) !== false) {
  if (is_dir($file) && $file != '.' && $file != '..' && $file != basename($cachedir)) {
    $cand_categories[] = $file;
  }
}

$i=1;
$categories[] = $cand_categories[rand(0, (count($cand_categories)-1))];
while ($i <= 2) {
  $randcat = $cand_categories[rand(0, (count($cand_categories)-1))];
  if (! in_array($randcat,$categories)) {
    $categories[]=$randcat;
    $i++;
  }
}

for ($i=0; $i <= 2; $i++) {
  $scan_results = scandir($categories[$i]);
  $originals[$i] = $categories[$i]."/".$scan_results[rand(2, (count($scan_results)-1))];
}

$cachefile = $cachedir."/".md5($originals[0] . $originals[1] . $originals[2] . $trimming . $canvas_height . $canvas_width . $shadow_color . $size[0] . $size[1] . $size[2] . $angles[0] . $angles[1] . $angles[2] . $offsets_y[0] . $offsets_y[1] . $offsets_y[2] . $offsets_x[0] . $offsets_x[1] . $offsets_x[2]) . $bottom_white . ".png";

if(!file_exists($cachefile)){
$background = new ImagickPixel( transparent );
$canvas = new Imagick();
$canvas->newImage( $canvas_height, $canvas_width, $background);
$canvas->setImageFormat( "png" );
for ($key=2; $key >= 0; $key--) {
  $image = new Imagick( $originals[$key] );
  $image->setFormat( "png" );
  $image->setImageBackgroundColor( new ImagickPixel( black ) );
  $image->thumbnailImage( $size[$key], $size[$key] );
  $image->borderImage(white, 0, $bottom_white);
  $image->cropImage( $size[$key], $size[$key]+$bottom_white, 0, $bottom_white );
  $image->polaroidImage( new ImagickDraw() , $angles[$key] );   
  $canvas->compositeImage( $image, Imagick::COMPOSITE_OVER, $offsets_y[$key], $offsets_x[$key]);  
  $image->destroy();
}
$canvas->trimimage(0);
$canvas->setCompression(Imagick::COMPRESSION_ZIP);
$canvas->setCompressionQuality($compression);
$canvas->writeImage( $cachefile );
}
$last_modified = gmdate('D, d M Y H:i:s',filemtime($cachefile)).' GMT';
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
  $if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
  if ($if_modified_since == $last_modified) {
    header("HTTP/1.0 304 Not Modified");
    header("Cache-Control: max-age=86400, must-revalidate");
    exit;
  }
}
if ($file = @fopen($cachefile,'rb')){
  $content_length = filesize($cachefile);
  header('Cache-Control: max-age=86400, must-revalidate');
  header('Content-Length: '.$content_length);
  header('Last-Modified: '.$last_modified);
  header('Content-type: ' . $mime_type);
  while(!feof($file))
    print(($buffer = fread($file,$send_buffer_size))) ;
  fclose($file) ;
  exit ;
}

?> 
