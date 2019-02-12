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








function connectToOldSQL(){
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








function iframe2embed($content){
	//<iframe src="//player.vimeo.com/video/251599821?color=fa4c07" height="225" width="800" allowfullscreen="" frameborder="0"></iframe>

	// SHOULD HAVE DONE IT WITH SHORT CODES like the gallery.... but oh well
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









function singlepic_shortcode( $atts ) {
	if( empty($atts['id']) )
		return '';
	else {
		$id = $atts['id'];
		$newId = importNextgenPic(trim($id));
		return '[gallery columns="1" link="none" size="large" ids="'. $newId .'"]';
	}
}

function multipic_shortcode( $atts ) {
	if( empty($atts['ids']) )
		return '';
	else
		$ids = explode(",", $atts['ids']);
		$newIds = '';
		foreach($ids as $id){
			$newId = importNextgenPic(trim($id));
			$newIds .= $newId. ',';
		}
		$newIds = substr($newIds, 0, -1);

		return '[gallery columns="1" link="none" size="large" ids="'. $newIds .'"]';
		// return '[gallery ids="'. $atts['ids'] .'"]';
		//"422, 423, 424
}

	

function nextgen2gallery($content){
	//[multipic ids="39, 40, 41, 42, 43"]
	//[singlepic id=857]

	add_shortcode( 'singlepic', 'singlepic_shortcode' );
	add_shortcode( 'multipic', 'multipic_shortcode' );
	return do_shortcode( $content );
}



















function importImage( $file ){
	// take a copy of the file
	$dest = WP_CONTENT_DIR . '/uploads/' . basename( $file );
	copy( $file, $dest );
	$file = $dest;

	// $filename should be the path to a file in the upload directory.
	$parent_post_id = -1;
	$filetype = wp_check_filetype( basename( $file ), null );
	$wp_upload_dir = wp_upload_dir();

	$attachment = array(
		'guid'           => $wp_upload_dir['url'] . '/' . basename( $file ), 
		'post_mime_type' => $filetype['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file ) ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);

	// $attach_id = wp_insert_attachment( $attachment, $file, $parent_post_id );
	$attach_id = wp_insert_attachment( $attachment, $file );
	// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	wp_update_attachment_metadata( $attach_id, $attach_data );
	set_post_thumbnail( $parent_post_id, $attach_id );

	echo '++  Import Image id:'.$attach_id.' added to WP__' .$file. '  ++<br>';
	// print_r($attach_data);
	return $attach_id;

}





function importNextgenPic( $id ){

	// Get image info from nextGen
	$conn = connectToOldSQL();
	$sql = "SELECT * FROM word_ngg_pictures WHERE(pid LIKE '".$id."')";
	$result = $conn->query($sql);
	$path = 'D:/myStuff/ch3/web/v2.ch3.gr/file/';
	$newId = -1;
	if ($result->num_rows > 0) {
	    $row = $result->fetch_assoc();
	    $file = $row["filename"];
	    $postId = $row["post_id"];
	    $galleryId = $row["galleryid"];
	    if( $galleryId == 1 )
	    	$file = $path . 'photo/'. $file;
	    else if( $galleryId == 2 )
	    	$file = $path . 'image/'. $file;
		else if( $galleryId == 3 )
	    	$file = $path . 'test/'. $file;

        echo "pid: " . $row["pid"]. " - filename: " . $file. " _______ Post ID :". $postId . " # ". $galleryId ."<br>";
	    // $newId = importImage($file);
	}
	$conn->close();
	return $newId;
}





















function migration(){
	echo "Start<br>";
	echo "<br> <b>copyPosts<b> <br>--------------<br>";

	$conn = connectToOldSQL();
	$sql = "SELECT * FROM word_posts WHERE(post_type LIKE 'post')";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		$count = 0;
		while ($row = $result->fetch_assoc()) {
        	$count ++;
			// DEBUG
			if( $count > 10 )
				break;
        	if( true or $row['ID'] == 3268 ){
        		$newPost = array();
        		$newPost['post_title'] = $row['post_title'];
        		$newPost['post_date'] = $row['post_date'];
        		$newPost['post_status'] = 'publish';
				$content = $row['post_content'];

	        	printf ("%s :: %s << %s >>", $newPostId, $newPost['post_date'], $newPost['post_title']);

				echo "<br>------------------------<br>";
				echo "__before__<br>";
				echo $content;
				echo "<br><br>__after__<br><br>";

				$content = nextgen2gallery($content);
				$content = iframe2embed($content);
				$newPost['post_content'] = $content;

				echo ("<br>...Adding Post..<br>");
				// $newPostId = wp_insert_post( $newPost );
				$newPostId = -1;
				echo $content;
			    echo ("<br>...PostEnd......<br><br><br><br><br>");

			    // Update post_parent to all images
			    $shortCodes = Parser::parse_shortcodes($content);
				for($i=0; $i<sizeof($shortCodes); $i++){
					if( $shortCodes[$i]['name'] == 'gallery'){
						$idsStr = $shortCodes[$i]['attrs'][0]['ids'] ;
						$ids = explode(",", $idsStr);
						foreach($ids as $id){
							$img_post = array();
							$img_post['ID'] = $id;
							$img_post['post_parent'] = $newPostId;
							wp_update_post( $img_post );
						}
					}
				}

        	}
	    }
	    printf ("<br><br>Total post count: %s <br>", $count);
	}
	// echo("<br>[embed]https://vimeo.com/10070698[/embed]<br>");

	$result->free();
	$conn->close();
	echo "<br>--------------<br>";
	echo "<br>-- D O N E ---<br>";
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









