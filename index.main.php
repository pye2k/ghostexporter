<?php
/**
 * This template generates an RSS 2.0 feed for the requested blog's latest posts
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * See {@link http://backend.userland.com/rss092}
 *
 * @todo iTunes podcast tags: http://www.apple.com/itunes/store/podcaststechspecs.html
 * Note: itunes support: .m4a, .mp3, .mov, .mp4, .m4v, and .pdf.
 *
 * @package evoskins
 * @subpackage rss
 *
 * @version $Id: index.main.php 3157 2013-03-06 04:34:44Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Markdown libraries
require_once( dirname( __FILE__) . '/html-to-markdown-master/HTML_To_Markdown.php' );

// GUID creation
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

$meta = array('exported_on' => time(), 'version' => '002');
//echo json_encode($meta);

$posts = array();
$post_id = 2; // keep track of post_ids

$item_tags = array();
$tag_id = 5; // keep track of tag_ids

$tag_urls = array();
$item_posts_tags = array();

while ( $Item = & mainlist_get_item() )
{
	$item = array();
	$item['author_id'] = 1;
	$item['created_at'] = trim( $Item->get_issue_date( array( 'date_format' => 'c', 'use_GMT' => true) ) );
	$item['created_by'] = 1;
	$item['featured'] = ($Item->is_featured() ? 1 : 0);
	$content = $Item->get_prerendered_content("htmlbody");
	//$item['html'] = $content;
	$item['id'] = $post_id++;
	$item['image'] = NULL;
	$item['language'] = $Blog->locale;
	$item['markdown'] = (new HTML_To_Markdown($content))->output();
	$item['meta_description'] = NULL;
	$item['meta_title'] = NULL;
	$item['page'] = 0;
	$item['published_at'] = $item['created_at'];
	$item['published_by'] = 1;
	$item['slug'] = $Item->urltitle;
	$item['status'] = $Item->get_status_raw();
	$item['title'] = $Item->get_title( array( 'format' => 'xml', 'link_type' => 'none') );
	$item['updated_at'] = $item['created_at'];
	$item['updated_by'] = 1;
	$item['uuid'] = getGUID();

	//var_dump($item);
	//var_dump($Item->get_Chapters());
	foreach ($Item->get_Chapters() as $Chapter)
	{
		$tag_name = $Chapter->dget('name');
		if (!array_key_exists($tag_name, $item_tags)) {
			$item_tags[$tag_name] = $tag_id++;
			
			$tag_urls[$tag_name] = $Chapter->dget('urlname');

		}

		$pt = $item_posts_tags[$item['id']];
		if (!isset($pt)) {
			$pt = array();
		}

		array_push( $pt, $item_tags[$tag_name] );
		$item_posts_tags[$item['id']] = $pt;
	}

	array_push($posts, $item);
}
//var_dump($posts);
//var_dump($item_tags);
//var_dump($tag_urls);
//var_dump($item_posts_tags);

// create tags
$tags = array();
foreach ($item_tags as $key => $value)
{
	$tag_item = array();
	$tag_item['created_at'] = '1970-01-01T00:00:00.000Z';
	$tag_item['created_by'] = 1;
	$tag_item['description'] = NULL;
	$tag_item['id'] = $value;
	$tag_item['meta_description'] = NULL;
	$tag_item['meta_title'] = NULL;
	$tag_item['name'] = $key;
	$tag_item['parent_id'] = NULL;
	$tag_item['slug'] = $tag_urls[$key];
	$tag_item['updated_at'] = $tag_item['created_at'];
	$tag_item['updated_by'] = 1;
	$tag_item['uuid'] = getGUID();

	array_push($tags, $tag_item);
}

// link up posts to tags
$posts_tags = array();
$posts_tags_id = 5;
foreach ($item_posts_tags as $key => $value)
{
	foreach ($value as $v)
	{
		$posts_tags_item = array();
		$posts_tags_item['id'] = $posts_tags_id++;
		$posts_tags_item['post_id'] = $key;
		$posts_tags_item['tag_id'] = $v;

		array_push($posts_tags, $posts_tags_item);
	}
}


$data = array( 'posts' => $posts, 'tags' => $tags, 'posts_tags' => $posts_tags );
$export = array( 'meta' => $meta, 'data' => $data );
echo json_encode($export, JSON_PRETTY_PRINT);
?>
