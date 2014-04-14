<?php
/**
 * This skin generates a JSON dump appropriate for importing into the Ghost blogging platform.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * Otherwise, see README.md for more information about usage and requirements.
 *
 * Loosely based on the schema as presented in:
 * https://github.com/TryGhost/Ghost/blob/master/core/server/data/schema.js
 *
 * The aim was to provide the smallest amount of data required to migrate posts from
 * b2evolution into Ghost.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Markdown libraries
require_once( dirname( __FILE__) . '/HTML_To_Markdown.php' );

// GUID creation (adapated from http://phptips.info/generate-guid-in-php/)
function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
    }
}

// Note: even if we request the same post as $Item earlier, the following will do more restrictions (dates, etc.)
// Init the MainList object:
init_MainList( $Blog->get_setting('posts_per_feed') );

// Variables used to build the DOM later on
$posts = array(); // Tracks the list of individual posts
$tag_name_to_id = array(); // Maintains the relationship of tag_name => tag_id
$tag_name_to_url = array(); // Maintains the relationship of tag_name => tag_url
$post_to_tags = array(); // Maintains the relationship of post_id => array(tag_id)

// Variables used as the IDs of various things.
// These don't actually matter outside the context of the exported JSON since they're stripped during import.
$post_id = 1;
$tag_id = 1;

// Build up each post object
while ( $Item = & mainlist_get_item() )
{
	$item = array();
	$item['author_id'] = 1;
	$item['created_at'] = trim( $Item->get_issue_date( array( 'date_format' => 'c', 'use_GMT' => true) ) );
	$item['created_by'] = 1;
	$item['featured'] = ($Item->is_featured() ? 1 : 0);
	$item['id'] = $post_id++;
	$item['language'] = $Blog->locale;

	// get the pre-rendered content; then pre-process it before it gets converted to Markdown
	$content = $Item->get_prerendered_content("htmlbody");
	$content = str_replace( '<code class="codespan">', '<code>', $content ); // pre-process code tags
	$content = str_replace( '<span>', '', $content ); // pre-process span tags
	$content = str_replace( '</span>', '', $content ); // pre-process /span tags

	$params = array( 'before_image' => '', 'after_image' => '' );
	$content = $Item->render_inline_images($content, $params); // pre-process the img tags

	$item['markdown'] = (new HTML_To_Markdown($content))->output();
	$item['page'] = 0;
	$item['published_at'] = $item['created_at'];
	$item['slug'] = $Item->urltitle;
	$item['status'] = $Item->get_status_raw();
	$item['title'] = $Item->get_title( array( 'format' => 'xml', 'link_type' => 'none') );
	$item['uuid'] = getGUID();

	// Chapters in b2evolution == tags in Ghost
	foreach ($Item->get_Chapters() as $Chapter)
	{
		$tag_name = $Chapter->dget('name');
		if (!array_key_exists($tag_name, $tag_name_to_id)) {
			// associate the tag_name to a tag_id
			$tag_name_to_id[$tag_name] = $tag_id++;
			
			// associate the tag name to a url
			$tag_name_to_url[$tag_name] = $Chapter->dget('urlname');
		}

		// associate the item_id to one or more tag_name
		$pt = $post_to_tags[$item['id']];
		if (!isset($pt)) {
			$pt = array();
		}

		array_push( $pt, $tag_name_to_id[$tag_name] );
		$post_to_tags[$item['id']] = $pt;
	}

	// add the post object into the list of posts
	array_push($posts, $item);
}

// create tags for ghost
$tags = array();
foreach ($tag_name_to_id as $tag_name => $tag_id)
{
	$tag_item = array();
	$tag_item['created_at'] = '1970-01-01T00:00:00.000Z';
	$tag_item['created_by'] = 1;
	$tag_item['id'] = $tag_id;
	$tag_item['name'] = $tag_name;
	$tag_item['slug'] = $tag_name_to_url[$tag_name];
	$tag_item['uuid'] = getGUID();

	array_push($tags, $tag_item);
}

// link up posts to tags
$posts_tags = array();
$posts_tags_id = 5;
foreach ($post_to_tags as $post_id => $tag_list)
{
	foreach ($tag_list as $tag_id)
	{
		$posts_tags_item = array();
		$posts_tags_item['id'] = $posts_tags_id++;
		$posts_tags_item['post_id'] = $post_id;
		$posts_tags_item['tag_id'] = $tag_id;

		array_push($posts_tags, $posts_tags_item);
	}
}

// Build up the JSON DOM
$meta = array('exported_on' => time(), 'version' => '002'); // Ghost metadata. This is based on the version 002 parser.
$data = array( 'posts' => $posts, 'tags' => $tags, 'posts_tags' => $posts_tags );
$export = array( 'meta' => $meta, 'data' => $data );
echo json_encode($export, JSON_PRETTY_PRINT);
?>
