<?php
/**
 * @package Migration tool
 * @version 1.0
 */
/*
Plugin Name: ch3-migrate
Plugin URI:
Description: Scripts to help me migrate my old database.
Author: Georgios Cherouvim
Version: 1.0
Author URI: http://ch3.gr
*/

/***************************************************************
 * SECURITY : Exit if accessed directly
 ***************************************************************/
if ( !defined( 'ABSPATH' ) ) {
	
	die( 'Direct access not allowed!' );
	
}


include 'extract_tags.php';


add_action('admin_menu', 'ch3_migration_menu');

function ch3_migration_menu(){
    add_menu_page( 'Test Plugin Page', 'ch3 Migration', 'manage_options', 'test-plugin', 'migration' );
}





function output_iptc_data( $image_path ) {
	$size = getimagesize ( $image_path, $info);
	if(!is_array($info)) 
		$info = iptcparse($info['APP13']);
	// print_r($info);
	if(is_array($info)) {

	    $iptc = iptcparse($info["APP13"]);
	    $title = $iptc['2#005'][0];
	    $caption = $iptc['2#120'][0];
	    $tags = $iptc['2#025'];
		    array_push( $tags, $iptc['2#090'][0] );
		    array_push( $tags, $iptc['2#095'][0] );
		    array_push( $tags, $iptc['2#101'][0] );

		echo "<br><br><b>IPTC</b><br>";
	    var_dump($iptc);
		echo "<br><b>IPTC END</b><br><br>";

	    echo 'Title <b>'.$title. '</b><br>';
	    echo 'Caption <b>'.$caption. '</b><br>';
	    print_r($tags);
	    // echo $iptc['2#090'][0];


	    // foreach (array_keys($iptc) as $s) {
	    //     $c = count ($iptc[$s]);
	    //     for ($i=0; $i <$c; $i++)
	    //     {
	    //         echo $s.' = '.$iptc[$s][$i].'<br>';
	    //     }
	    // }
	}
}


function importImages(){
	echo "<br><br> <b>importImages<b> <br>--------------<br>";

	// MASTER LOG ARRAY FOR IDs
	$imageLog = array();

	$imageFolder = WP_CONTENT_DIR . '/uploads/sourceImages';
	echo 'Looking at folder <b>' . $imageFolder . '</b><br>';

	$images = list_files( $imageFolder );
	
	foreach( $images as $image ){
		// Enter Image entry to the Master log
		$imgEntry = array();
		$imgEntry['filename'] = wp_basename($image);
		$imgEntry['id0'] = -1;
		$imgEntry['id1'] = -1;
		$imgEntry['post0'] = -1;
		$imgEntry['post1'] = -1;
		



		echo '<br>Inserting photo <b>' . $image . '</b>';
		$exif = exif_read_data($image, 'IFD0');
		// echo '<br><br><br><b>EXIF</b><br>';
		// print_r($exif);
		// echo '<br><b>EXIF END</b><br><br>';
		// echo '<br>';
		// output_iptc_data($image);
		// echo '<br>';



		// REPLACE with plugin function
/*
		// The ID of the post this attachment is for.
		$parent_post_id = -1;
		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $image ), null );
		// Get the path to the upload directory.
		$wp_upload_dir = wp_upload_dir();
		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $image ), 
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $image ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $image, $parent_post_id );
		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $image );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		set_post_thumbnail( $parent_post_id, $attach_id );

		echo '<br>' . $attach_id;
		// print_r($attach_data);

		$imgEntry['id1'] = $attach_id;
*/
		array_push($imageLog, $imgEntry);
	}
	echo "<br>--------------<br>";
	return $imageLog;
}



function connectToSQL(){
	$servername = "localhost";
	$username = "georgios";
	$password = "123";
	$dbname = "v2_ch3_gr";

	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}
	return $conn;
}


function getImageIDs($imageLog){
	echo "<br><br> <b>getImageIDs<b> <br>--------------<br>";
	$conn = connectToSQL();

	//foreach( $imageLog as $imageEntry ){
	for( $i=0; $i<count($imageLog); $i++ ){
		$image = $imageLog[$i]['filename'];
		$sql = "SELECT * FROM word_ngg_pictures WHERE(filename LIKE '".$image."')";
		$result = $conn->query($sql);

		if ($result->num_rows > 0) {
		    // output data of each row
		    while($row = $result->fetch_assoc()) {
		        // echo "pid: " . $row["pid"]. " - filename: " . $row["filename"]. " _______ Post ID :". $row["post_id"] ."<br>";
		        // Doing this to prevent orphan images in duplications
		        if( $imageLog[$i]['post0'] <= 0){
		        	$imageLog[$i]['post0'] = $row["post_id"];
		        	$imageLog[$i]['id0'] = $row["pid"];
		        }
		    }
		} else {
		    echo "<<< MISSING ENTRY >>> <b>". $image . "</b>";
		}
	}

	$conn->close();
	echo "<br>--------------<br>";
	return $imageLog;
}


function getTextBetweenTags($string, $tagname) {
    $pattern = "/<$tagname ?.*>(.*)<\/$tagname>/";
    preg_match($pattern, $string, $matches);
    return $matches;
}




function iframe2embed_XXX($content){
	//<iframe src="//player.vimeo.com/video/251599821?color=fa4c07" frameborder="0"></iframe>
	//<iframe width="800" height="485" src="http://www.youtube.com/embed/Oyx1D9j1O8g?rel=0" frameborder="0" allowfullscreen></iframe>

	$out = '';
	// echo $content;

	$iframes = extract_tags( $content, 'iframe' );
	print_r($iframes);
	foreach($iframes as $iframe){

		if( strpos($iframe['attributes']['src'], 'vimeo') == true ){
			$out .= '[vimeo]';
		}
		else if( strpos($iframe['attributes']['src'], 'youtube') == true ){
			$out .= '[youtube]';
		}
	    // echo $iframe['attributes']['src'] , '<br>';
	}
	return $out;
}




function iframe2embed($content){
	//<iframe src="//player.vimeo.com/video/251599821?color=fa4c07" height="225" width="800" allowfullscreen="" frameborder="0"></iframe>
	$tag0 = '<iframe';
	$tag1 = 'iframe>';
	if( strpos($content, $tag0) == false )
		return $content;

	$s0 = explode($tag0, $content );
	$out .= $s0[0];
	for($i=1; $i<sizeof($s0); ++$i){
		$s1 = explode($tag1, $s0[$i] );
		$tag = $s1[0];
		if( strpos($tag, 'vimeo') == true ){

			$matches = array();
			preg_match("/[0-9]{5,15}/", $tag, $matches);
			$out .= "[embed]https://vimeo.com/". $matches[0] ."[/embed]";
		}
		else if( strpos($tag, 'youtube') == true ){
			$matches = explode('"', $tag );
			$index = array_search(' src=', $matches) + 1;
			$out .= "[embed]". $matches[$index] ."[/embed]";
		}
		// Add sub content
		$out .= $s1[1];
		
	}
	return $out;	
}





function nextgen2gallery_XXX($content){
	//[multipic ids="39, 40, 41, 42, 43"]
	//[singlepic id=857]

	$tag0 = '[';
	$tag1 = ']';
	if( strpos($content, $tag0) == false )
		return $content;

	$s0 = explode($tag0, $content );
	$out .= $s0[0];
	for($i=1; $i<sizeof($s0); ++$i){
		$text = '['.$s0[$i];
		$shortCodes = Parser::parse_shortcodes($text);
		$out .= $text;
		// $out .= "<___".sizeof($shortCodes)."__>";
		// for($i=0; $i<sizeof($shortCodes); $i++){
		// 	echo( $shortCodes[$i] );
		// }

		// $s1 = explode($tag1, $s0[$i] );
		// $tag = $s1[0];
		// if( strpos($tag, 'embed') == true ){
		// 	$out .= $tag0;
		// 	$out .= $tag;
		// 	$out .= $tag1;
		// }

		// Add sub content
		// $out .= $s1[1];
		
	}
	return $out;	
}






function singlepic_shortcode( $atts ) {
	if( empty($atts['id']) )
		return '';
	else {
		$id = $atts['id'];	
		return '[gallery ids="'. $id .'"]';
	}
}

function multipic_shortcode( $atts ) {
	if( empty($atts['ids']) )
		return '';
	else
		$ids = $atts['ids'];

		return '[gallery ids="'. $ids .'"]';
}

	

function nextgen2gallery($content){
	//[multipic ids="39, 40, 41, 42, 43"]
	//[singlepic id=857]

	add_shortcode( 'singlepic', 'singlepic_shortcode' );
	add_shortcode( 'multipic', 'multipic_shortcode' );
	return do_shortcode( $content );
}



function copyPost(){
	// WP loaded database
	// $posts = get_posts();
	// for( $i=0; $i<count($posts); $i++ ){
	// 	echo $posts[$i]->ID;
	// 	echo "<br>";
	// }
	
	echo "<br><br> <b>copyPosts<b> <br>--------------<br>";
	$conn = connectToSQL();
	$sql = "SELECT * FROM word_posts WHERE(post_type LIKE 'post')";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		$count = 0;
		while ($row = $result->fetch_assoc()) {
			// DEBUG
        	if( false or $row['ID'] == 3268 ){
				$id = $row['ID'];
				$title = $row['post_title'];
				$content = $row['post_content'];
	        	printf ("%s _ %s <br>", $id, $title);
        		
        		// $content = '<br><br>23<iframe src="//player.vimeo.com/video/001599821?color=fa4c07" frameborder="0"></iframe>45<br>46<iframe src="//player.vimeo.com/video/002599821?color=fa4c07" frameborder="0"></iframe>67<br>18<iframe src="//player.vimeo.com/video/003599821?color=fa4c07" frameborder="0"></iframe>99<br>11<iframe width="800" height="485" src="http://www.youtube.com/embed/Oyx1D9j1O8g?rel=0" frameborder="0" allowfullscreen></iframe>00<br>99aa <iframe src="//player.vimeo.com/video/003599821?color=fa4c07" frameborder="0"></iframe> dsad <br>dsah sda<br> dsadad as<iframe width="800" height="485" src="http://www.youtube.com/embed/Oyx1D9j1O8g?rel=0" frameborder="0" allowfullscreen></iframe>dsad' ;
        		// $content = 'aaa[multipic ids="39, 40, 41, 42, 43"]bbb[multipic ids="1, 2, 3"]sa<br>[multipic ids="5, 8, 9"]<br>[singlepic id=857] ---- [singlepic]';

				echo "<br><br>__before__<br><br>";
				echo $content;
				echo "<br><br>__after__<br><br>";
				
				$content = nextgen2gallery($content);
				$content = iframe2embed($content);
				echo $content;
			    echo ("<br><br>...PostEnd...<br><br>");
        	}
        	$count ++;
	    }
	    printf ("<br><br>Total post count: %s <br>", $count);
	}
	// echo("<br>[embed]https://vimeo.com/10070698[/embed]<br>");

	$result->free();
	$conn->close();
	echo "<br>--------------<br>";
}


function createPost(){
	// Create post object
	$my_post = array(
	  'post_title'    => 'Auto Post',
	  'post_content'  => 'This post has been generated automaticaly',
	  'post_status'   => 'publish',
	  'post_author'   => 1
	);
	 
	// Insert the post into the database
	// wp_insert_post( $my_post );
	echo 'Creating post: <b>' . $my_post['post_title'] . '</b>';
}







function migration(){
	echo "Start<br>";



	// global $shortcode_tags;
	// print_r($shortcode_tags);


	// $imageLog = importImages();
	// $imageLog = getImageIDs($imageLog);
	copyPost();
	// createPost();

	// $text = '123 [embed]youtube[/embed]...[multipic ids="39, 40, 41, 42, 43"] LALAL [singlepic id=857] 789';
	// echo $text;
	// echo "<br><br>";
	// $shortCodes = Parser::parse_shortcodes($text);
	// for($i=0; $i<sizeof($shortCodes); $i++){
	// 	print_r( $shortCodes[$i] );
	// 	echo "<br>";
	// }
	
/*
	// Print ImageLog
	echo "<br>--Master Log--";
	foreach( $imageLog as $imgEntry ){
		echo '<br>';
		print_r($imgEntry);
	}
	echo "<br>-----------------<br>";
*/


    echo "<p>DONE</p>";
}






?>
