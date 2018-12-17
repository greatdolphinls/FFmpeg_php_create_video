<?php

# Input data
$audio_file = "src/audio.mp3";
$slide_duration = 3;
$result_file = "newvideo.mp4";
$data = [
  [
    "image" => "src/images/image1.jpg",
    "subtitle" => "Wow, beautiful birds."
  ],
  [
    "image" => "src/images/image2.jpg",
    "subtitle" => "A terrible snake, What are you doing now."
  ],
  [
    "image" => "src/images/image3.jpg",
    "subtitle" => "Horse, Howdy."
  ],
  [
    "image" => "src/images/image4.jpg",
    "subtitle" => "Frog kitten, Why, hello there! What's your name?."
  ],
  [
    "image" => "src/images/image5.jpg",
    "subtitle" => "My Mom, I love you. ~end~"
  ],
];

createVideo($data, $slide_duration, $audio_file, $result_file);

function createVideo($data, $slide_duration, $audio_file, $result_file) {
  
  $width = 460;
  $height = 320;
  $scale = get_scale($width, $height);
  $video_coder = "-c:v libx264";
  $temp_path = "temp/";
  $merge_file = "temp/input.txt";
  $subtitle = "temp/subtitles.srt";
  $image_count = sizeof($data);
  $total_time = "-t ".$slide_duration * $image_count;

  init_data($data, $slide_duration, $merge_file, $subtitle);

  foreach($data as $key => $row) {
    convert_image_video($row['image'], $slide_duration, $key, $video_coder, $temp_path, $scale);
    $result_image_path = $temp_path.$key.".mp4";
    add_slide_video($result_image_path, $slide_duration, $key);
    add_merge_file($key, $merge_file);
  }

  merge_videos($merge_file);
  add_audio_video($total_time, $audio_file, $video_coder, $scale);
  add_subtitle_video($subtitle, $result_file);
  delete_temp_files();
}

function init_data($data, $slide_duration, $merge_file_path, $subtitle){
  delete_temp_files();
  create_subtitle($data, $slide_duration, $subtitle);
}

function delete_temp_files(){
  $files = glob('temp/*'); 
  foreach($files as $file){ 
    if(is_file($file))
       unlink($file);
    }
}

function create_subtitle($data, $slide_duration, $subtitle){
  $file = fopen($subtitle, "w");
  foreach($data as $key => $row) {
    $txt = ($key + 1)."\n";
    $txt .= create_duration($key, $slide_duration);
    $txt .= "\n";
    $txt .= $row["subtitle"];
    $txt .= "\n\n";
    create_duration($key, $slide_duration);
    fwrite($file, $txt);
  }
  fclose($file);
}

function create_duration($key, $slide_duration){
  $start_time = format_seconds($key * $slide_duration);
  $end_time = format_seconds(($key + 1) * $slide_duration);
  return "$start_time,00 --> $end_time,00";
}

function format_seconds( $seconds )
{
  $hours = 0;
  $milliseconds = str_replace( "0.", '', $seconds - floor( $seconds ) );

  if ( $seconds > 3600 )
  {
    $hours = floor( $seconds / 3600 );
  }
  $seconds = $seconds % 3600;

  return str_pad( $hours, 2, '0', STR_PAD_LEFT )
       . gmdate( ':i:s', $seconds )
       . ($milliseconds ? ".$milliseconds" : '')
  ;
}

function add_merge_file($key, $merge_file){
  $file = fopen($merge_file, "a");
  $txt = "file '$key.mp4' \n";
  fwrite($file, $txt);
  fclose($file);
}

function convert_image_video($input_image, $slide_duration, $key, $video_coder, $temp_path, $scale) {
  $ffmpeg = "ffmpeg -loop 1 -i $input_image $video_coder -t $slide_duration -r 30 -pix_fmt yuv420p -y -vf $scale $temp_path$key.mp4";
  shell_exec($ffmpeg);
}

function add_slide_video($input_image, $slide_duration, $key) {
  $add_fade_in ="ffmpeg -i " .$input_image. " -strict experimental -y -vf fade=in:0:30 temp/temp.mp4";
  shell_exec($add_fade_in);
  $add_fade_out ="ffmpeg -i temp/temp.mp4 -strict experimental -y -vf fade=out:".(($slide_duration-1)*30).":30 ".$input_image;
  shell_exec($add_fade_out);
  
}
  
function merge_videos($merge_file){
  $cat = "ffmpeg -f concat -i $merge_file -y -codec copy temp/temp.mp4";
  shell_exec($cat);
}

function add_audio_video($total_time, $audio_file, $video_coder, $scale) {
  $ffmpeg = "ffmpeg -y -i temp/temp.mp4 $total_time -i $audio_file $video_coder -r 30 -c:a aac -vf $scale temp/temp1.mp4";
  shell_exec($ffmpeg);
}

function add_subtitle_video($subtitle, $result_file) {
  $slide_ffmpeg ="ffmpeg -y -i temp/temp1.mp4 -f srt -i $subtitle -c:v copy -c:a copy -c:s mov_text $result_file";
  shell_exec($slide_ffmpeg);
}

function get_scale($width, $height){
  return "scale=".$width.":".$height;
}
?>