<?php
require_once('./php-plurk-api/plurk_api.php');
require_once('./simplepie/simplepie.inc');

// --- Configuration ---
$api_key  = ''; // Plurk api key (http://www.plurk.com/API); required
$username = ''; // Username for login; required
$password = ''; // Password; required
$feedurl  = ''; //Feed URL; required

$feedConf = new SimplePie();
$feedConf->set_feed_url($feedurl);
$feedConf->enable_cache(false);

function dc($string){ return html_entity_decode( $string, ENT_QUOTES, "utf-8" ); }
function p($message, $lv=0, $cr=0) { for ($i=0; $i<$lv; $i++) print "\t"; print $message; for ($i=-1; $i<$cr; $i++) print "\n"; }
function uni_strsplit($string, $split_length=1){
	preg_match_all('`.`u', $string, $arr);
	$arr = array_chunk($arr[0], $split_length);
	$arr = array_map('implode', $arr);
	return $arr;
}
p('- init');

$after = time();
if ( isset( $_SERVER['argv'][1] )) {
	$after = $after-$_SERVER['argv'][1];
	p('now-'.$_SERVER['argv'][1], 1);
}

unset($plurk);
$plurk = new plurk_api();
$plurk->login($api_key, $username, $password);
p('- logged in, going to loop');

while (true) {
	p('- cycle start');
	$feed = clone $feedConf;
	$feed->init();
	$new = array();

	$feed = array_reverse( $feed->get_items() );

	foreach ($feed as $item) {
		if ($item->get_date('U') > $after) {
			$new[] = $item;
			$after = $item->get_date('U');
		}
	}
	if ( count($new) == 0 ) p('- no new article', 1);
	 
	foreach($new as $item) {
		p( '- '.dc($item->get_title()), 1);

		p("post title", 2, -1);
		$post = $plurk->add_plurk('zh_Hant', '',  dc($item->get_title()) , NULL, 1);
		if ( isset( $post->{'error_text'} ) ) {
			p( '['.$post->{'error_text'}.']' );
			$after = time();
			break;
		}
		p(' id:'.$post->{'plurk_id'}, 0 );

		p("post reply", 2);
		$decoded = dc($item->get_description());
		$content = explode ( "\r\n\r\n", $decoded );
		$j = 1;
		foreach($content as $pargraph) {
			if ($pargraph == '') {
				p( "- no content", 3);
				break;
			}
			p( 'paragraph '.$j.', line:', 3,-1);
			$k = 1;
			$lines = uni_strsplit($pargraph, 140);
			foreach($lines as $line) {
				p( $k.' ', 0,-1);
				$plurk->add_response( $post->{'plurk_id'}, $line, '');
				p('', 0, 0);
			$k++;
			}
			$j++;
		}

		$plurk->add_response( $post->{'plurk_id'}, $item->get_link(), '');
		sleep(3);
	}

	p("- cycle ended, sleep for 60s then go on", 0, 1);
	sleep(60);
}
