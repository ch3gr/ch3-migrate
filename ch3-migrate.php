<?php
/**
 * @package Migration tool
 * @version 1.0
 */
/*
Plugin Name: ch3 Migrate
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
// include 'ch3-metadata.php';

ini_set('max_execution_time', 60*60*10);
ini_set( 'upload_max_size' , '64M' );






//	FROM ch3-plugin.php
$customDir = array();

$customDir['uploads'] = 'file';
$customDir['images'] = 'img';
$customDir['intermediate'] = 'int';

define('UPLOADS', $customDir['uploads']);

$customDir['uploads_full'] = wp_normalize_path( wp_upload_dir()['path'] ) ;
$customDir['images_full'] = $customDir['uploads_full'] . '/' . $customDir['images'];
$customDir['intermediate_full'] = $customDir['images_full'] .'/' .$customDir['intermediate'];
////////////////






// Global array with all files in photo archive
$glob = array();















add_action('admin_menu', 'ch3_migration_menu', 2);

function ch3_migration_menu(){
    add_menu_page( 'ch3 Migration', 'ch3 Migration', 'manage_options', 'ch3-migration', 'ch3_migration' );
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
	mysqli_set_charset($conn,"utf8");
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

















// 
// IMPORT IMAGE
function importImage( $file, $latestPostId ){


//				DEBUG 
	if( 1 ){
		global $customDir;
		global $glob;

		// print_ar( $customDir );
		// print_ar( wp_upload_dir());


		// take a copy of the file
		// $dest = wp_normalize_path( wp_upload_dir()['basedir'] ."/". basename( $file ) );
		// $dest = wp_normalize_path( $customDir['images_full'] ."/". basename( $file ) );
		$dest = $customDir['uploads_full'] ."/". basename( $file ) ;

		$path2 = 'D:/myStuff/ch3/web/v4.ch3.gr/__tmp/imgSrc';


		$id = array_search_partial( $glob, basename($file));
		if( $id >= 0 ) {
		// if( file_exists($path2 .'/'. basename($file)) ) {
			echo 'File exist in secondary path';
			$file = $glob[$id];
			// $file = $path2 .'/'. basename( $file );
		}
		elseif( file_exists( $file . '_backup')) {
			// Use the backup version for full size
			$file = $file . '_backup';
		}



		echo '<br> Copying image ... <br>';
		echo 'file :: '. $file .'<br>';
		echo 'dest :: '. $dest .'<br>';
		// echo 'uplo :: '. wp_upload_dir()['url'] .'<br>';
		// echo 'uphi :: '. wp_upload_dir()['basedir'] .'<br>';
		copy( $file, $dest );
		$file = $dest;
		
		// $filename should be the path to a file in the upload directory.
		$parent_post_id = $latestPostId;
		$filetype = wp_check_filetype( basename( $file ), null );

		$attachment = array(
			'guid'           => wp_upload_dir()['url'] . '/' . $customDir['upload'] . '/' . basename( $file ), 
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		$attach_id = wp_insert_attachment( $attachment, basename($file) );
		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		set_post_thumbnail( $parent_post_id, $attach_id );
		echo '++  Imported :: '. $file .' | ID ::' .$attach_id .'<br><br>';

		return $attach_id;
	}
	else {






		// THIS WILL JUST OUTPUT ALL THE FILE NAMES TO A TEXT FILE TO READ AND MANAGE VIA MF Expression Media
		print( 'Writting file :'. $file .' to disk file <br>');
		$myfile = fopen("D:/myStuff/My Pictures/MEM_imageSelection.txt", "a") or die("Unable to open file!");

		$txt = basename($file) . "\n";
		fwrite($myfile, $txt);

		fclose($myfile);
	}


}





// Check function above for source filename  ----^


function importNextgenPic( $id ){

	global $latestPostId;
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


	    // Check if the image already exist in the new database to prevent duplicates
		global $wpdb;
		$result = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE(guid LIKE '%{$row["filename"]}%') " );
		if( sizeof($result) == 0 ){
	    	$newId = importImage($file, $latestPostId);					// INSERT IMAGE //////////
		}
		else{
			$newId = $result[0]->ID;
			echo '######### PICTURE '. $row["filename"] .' ALREADY EXISTS - SKIPPING ############## <br><br>';
		}



	} else {
		echo '################# PICTURE '. $id .' WAS NOT FOUND IN NEXTGEN #################### <br><br>';
	}


	$conn->close();
	return $newId;
}












function getShortcodeAttr($shortcode, $attr){
	foreach ($shortcode['attrs'] as $at ){
		foreach ($at as $key => $value){
			if( $key == 'ids' )
				return( $at['ids'] );
		}
	}
}










function getTaxonomyFromDB( $oldPostId ){
	// global $wpdb;
	$conn = connectToOldSQL();

	// array that holds an array of attributes for each term
	$terms = array();

	// Get terms
	$sql = 'SELECT * FROM word_term_relationships WHERE object_id = '.$oldPostId;
	$result = $conn->query($sql);
	
	while( $row = $result->fetch_assoc() ) {
		$elem = array();
		$elem['term_taxonomy_id'] = $row['term_taxonomy_id'];
		$elem['term_order'] = $row['term_order'];
		array_push( $terms, $elem );
		// print_ar($row);
	} 
	// print_ar($terms);
	// echo "<br> -- <br>";

	// Redirect term_taxonomy_id -> term_id
	foreach($terms as $key => $term) {
		$term_taxonomy_id = $term['term_taxonomy_id'];
		$sql = "SELECT * FROM word_term_taxonomy WHERE term_taxonomy_id = $term_taxonomy_id";
		$result = $conn->query($sql);

		while( $row = $result->fetch_assoc() ) {
			$terms[$key]['term_id'] = $row['term_id'];
			$terms[$key]['taxonomy'] = $row['taxonomy'];
		}
	}
	// print_ar($terms);
	// echo "<br> -- <br>";


	// term_id -> name
	foreach($terms as $key => $term) {
		$term_id = $term['term_id'];
		$sql = "SELECT * FROM word_terms WHERE term_id = $term_id";
		$result = $conn->query($sql);

		while( $row = $result->fetch_assoc() ) {
			$terms[$key]['name'] = $row['name'];
		}
	}

	$conn->close();
	// print_ar($terms);
	return $terms;
}








function ch3_migration(){
	
	global $glob;

	date_default_timezone_set( date_default_timezone_get() );
	echo "Start<br>";
	echo "<br>--------------     TIME ::  ";
	print( date("H:i:s") );
	echo "<br> Loading all files in archive...   ";
	$glob = glob("D:/myStuff/My Pictures/digi/*/*/*");
	$glob = array_merge($glob, glob("D:/myStuff/My Pictures/film/*/*") );
	$glob = array_merge($glob, glob("D:/myStuff/My Pictures/cg/*") );
	echo "DONE ! <br>";
	echo "<br><b>copyPosts<b> <br>--------------<br>";

	

	echo "<br>____ TEST ____ <br>";	
	// print_ar( getTaxonomyFromDB( 500 ) );
	// wp_set_post_terms( 1761, 'lakis_TAG', 'post_tag', 1 );
	// wp_set_post_terms( 1761, 2, 'category', 1 );

	// print_ar( term_exists( 'CAT_C', 'category') );

	// $uncategorized_id = term_exists( 'uncategorized', 'category');
	// // wp_remove_object_terms( 1778, $uncategorized_id['term_id'], 'category' );
	// wp_remove_object_terms( 1778, 'uncategorized', 'category' );
	// print("done");






	global $wpdb;
	$conn = connectToOldSQL();
	$sql = "SELECT * FROM word_posts WHERE(post_type LIKE 'post') ORDER BY post_date";
	$result = $conn->query($sql);


	if (1 && $result->num_rows > 0) {
		$count = 0;
		// while ($row = $result->fetch_assoc()) {
		// 	echo '<br>'.$row['ID'] .'   '.$row['post_title'];
		// }
		while ($row = $result->fetch_assoc()) {
			flush();
			// DEBUG

			// HOW MANY POSTS TO COPY
			if( $count >= 2 )	break;

			// if( $row['post_title'] != 'Distorted faces' &&
			// 	$row['post_title'] != 'Fighter - print')	continue;
			// if( $row['post_title'] != 'Fighter - print')	continue;
			// if( $row['post_title'] != 'Distorted faces')	continue;




    		$newPost = array();
    		$newPost['post_title'] = $row['post_title'];
    		$newPost['post_date'] = $row['post_date'];
    		$newPost['post_status'] = 'publish';
			

			$content = $row['post_content'] ;
			$content = str_replace('”','sec',$content);
			$content = str_replace('\'','min',$content);
			$newPost['post_content'] = $content;

			// Skip if post exists
			if( post_exists($newPost['post_title']) != 0 ){
				print( 'Post :: <b>'. $newPost['post_title'] .'</b>			EXISTS, so skipping -------  <br>');
				continue;
			}

        	$count ++;
			



			echo "<br><br><br><br><br><br>".$count."  ------------------------  TIME ::  ";
			print( date("H:i:s") );
        	printf ("<br>%s :: %s << %s >>", $row['ID'], $newPost['post_date'], $newPost['post_title']);
			echo "<br><br>__before__<br>";
			echo $content;
			echo "<br><br>__after __<br>";
			echo ("...Adding Post..<br>");

			$newPostId = -1;
			global $latestPostId;		// Hack for the images to get attached to the post via shortcode bottleneck
			$newPostId = wp_insert_post( $newPost );			// INSERT POST //////////
			$latestPostId = $newPostId;

			$content = nextgen2gallery($content);
			$content = iframe2embed($content);
			$latestPostId = 0;
			echo $content;

			// Update the content of the new post
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_content = %s WHERE ID = %d", $content, $newPostId ) );


			//	Add taxonomy
			print( '<br><br>Adding Taxonomy <br>');
			$terms = getTaxonomyFromDB( $row['ID'] );
			foreach( $terms as $term ){
				// if( $term['taxonomy'] == 'category' || $term['taxonomy'] == 'post_tag')
				// 	wp_set_post_terms( $newPostId, $term['name'], $term['taxonomy'], 1 );

				// POST TAG
				if( $term['taxonomy'] == 'post_tag'){
					print('[TAG::'.$term['name'].']');
					wp_set_post_terms( $newPostId, $term['name'], 'post_tag', 1 );
				}
				// POST CATEGORY
				if( $term['taxonomy'] == 'category'){
					print('[CAT::'.$term['name'].']');
					$cat_id = term_exists( $term['name'], 'category');
					if( !isset($cat_id) ){
						$cat_id = wp_create_category( $term['name'] );
						wp_set_post_terms( $newPostId, $cat_id, 'category', 1 );
					}else
						wp_set_post_terms( $newPostId, $cat_id['term_id'], 'category', 1 );

				}
			}
			// Remove Uncategorized
			wp_remove_object_terms( $newPostId, 'uncategorized', 'category' );

			// DEBUG
			// DELETE POST
			if(0){
				wp_delete_post( $newPostId , 1);
			}

	    }
	    printf ("<br><br>Total post count: %s <br>", $count);
	}

	

	$result->free();
	$conn->close();
	echo "<br>--------------<br>";
	echo "<br>-- D O N E ---<br>";
	echo 'TIME ::   ';
	print( date("H:i:s") );
}













