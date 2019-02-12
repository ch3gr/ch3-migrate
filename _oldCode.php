<?php





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

		// // The ID of the post this attachment is for.
		// $parent_post_id = -1;
		// // Check the type of file. We'll use this as the 'post_mime_type'.
		// $filetype = wp_check_filetype( basename( $image ), null );
		// // Get the path to the upload directory.
		// $wp_upload_dir = wp_upload_dir();
		// // Prepare an array of post data for the attachment.
		// $attachment = array(
		// 	'guid'           => $wp_upload_dir['url'] . '/' . basename( $image ), 
		// 	'post_mime_type' => $filetype['type'],
		// 	'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $image ) ),
		// 	'post_content'   => '',
		// 	'post_status'    => 'inherit'
		// );

		// // Insert the attachment.
		// $attach_id = wp_insert_attachment( $attachment, $image, $parent_post_id );
		// // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		// require_once( ABSPATH . 'wp-admin/includes/image.php' );
		// // Generate the metadata for the attachment, and update the database record.
		// $attach_data = wp_generate_attachment_metadata( $attach_id, $image );
		// wp_update_attachment_metadata( $attach_id, $attach_data );

		// set_post_thumbnail( $parent_post_id, $attach_id );

		// echo '<br>' . $attach_id;
		// // print_r($attach_data);

		// $imgEntry['id1'] = $attach_id;

		array_push($imageLog, $imgEntry);
	}
	echo "<br>--------------<br>";
	return $imageLog;
}




function getImageIDs($imageLog){
	echo "<br><br> <b>getImageIDs<b> <br>--------------<br>";
	$conn = connectToOldSQL();

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









//////////////////////////////////////
// OLD CODE


	// WP loaded database
	// $posts = get_posts();
	// for( $i=0; $i<count($posts); $i++ ){
	// 	echo $posts[$i]->ID;
	// 	echo "<br>";
	// }
	



	// global $imageLog;

	// $imageLog = importImages();
	// $imageLog = getImageIDs($imageLog);
	

	// $text = '123 [embed]youtube[/embed]...[multipic ids="39, 40, 41, 42, 43"] LALAL [singlepic id=857] 789';
	// echo $text;
	// echo "<br><br>";
	// $shortCodes = Parser::parse_shortcodes($text);
	// for($i=0; $i<sizeof($shortCodes); $i++){
	// 	// print_r( $shortCodes[$i] );
	// 	echo $shortCodes[$i]['attrs'][0]['ids'] . '<br>';
	// 	echo "<br>";
	// }
	// echo '<br>...<br>';
	// echo( $shortCodes[1]['attrs'][0]['ids'] );
	
/*
	// Print ImageLog
	echo "<br>--Master Log--";
	foreach( $imageLog as $imgEntry ){
		echo '<br>';
		print_r($imgEntry);
	}
	echo "<br>-----------------<br>";
*/

	// echo '_______imgLoaf____<br>';
	// $img = WP_CONTENT_DIR . '/uploads/sourceImages/ch3_110928_5892.jpg';
	// importImage($img);
	
	// echo $img;
	// echo '<br>';
	// print_r( wp_read_image_metadata( $img ) );

	// $img_post = array();
	// $img_post['ID'] = 135;
	// $img_post['post_parent'] = 999;
	// wp_update_post( $img_post );




       		
        		// $content = '<br><br>23<iframe src="//player.vimeo.com/video/001599821?color=fa4c07" frameborder="0"></iframe>45<br>46<iframe src="//player.vimeo.com/video/002599821?color=fa4c07" frameborder="0"></iframe>67<br>18<iframe src="//player.vimeo.com/video/003599821?color=fa4c07" frameborder="0"></iframe>99<br>11<iframe width="800" height="485" src="http://www.youtube.com/embed/Oyx1D9j1O8g?rel=0" frameborder="0" allowfullscreen></iframe>00<br>99aa <iframe src="//player.vimeo.com/video/003599821?color=fa4c07" frameborder="0"></iframe> dsad <br>dsah sda<br> dsadad as<iframe width="800" height="485" src="http://www.youtube.com/embed/Oyx1D9j1O8g?rel=0" frameborder="0" allowfullscreen></iframe>dsad' ;
        		// $content = 'aaa[multipic ids="39, 40, 41, 42, 43"]bbb[multipic ids="1, 2, 3"]sa<br>[multipic ids="5, 8, 9"]<br>[singlepic id=857] ---- [singlepic]';





	// $sc = Parser::parse_shortcodes('gsfd s [bar ids="1, 3, 4" size="medium"] fsd f  [foo size="medium" ids="7, -1"] ');
	// print_r( $sc[0] );
	// echo '<br>';
	// echo '<br>';
	// print_r( $sc[0]['attrs'] );
	// foreach ($sc[0]['attrs'] as $key => $value){
	// 	echo '<br>';
	// 	if( $key == 'ids' )
	// 		print_r( $value );
	// }
	// echo '<br>';
	// echo '<br>';
	// echo( getShortcodeAttr($sc[0], 'ids') );

	// for( $i=0; sizeof( $sc[0]['attrs'] ); $i++ ){
	// 	print_r( $sc[0]['attrs'][$i] );
	// }

	// print_r( array_column($sc, 0, 'ids') );
	// print_r( getShortcodeAttr($sc, 'ids') );