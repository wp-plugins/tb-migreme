<?php
/*
Plugin Name: TB Migre.me
Plugin URI: http://tecnoblog.net/
Description: O TB Migre.me encurta as suas urls com o Migre.me e publica na sua conta do twitter. O plugin ainda pode inserir o botão do Twitter em seus posts automaticamente.
Author: Tecnoblog
Version: 1.0.3
Author URI: http://tecnoblog.net/
*/

/* QUANDO O PLUGIN FOR ATIVADO */

register_activation_hook( __FILE__, 'tbmigreme_activate' );

function tbmigreme_activate() {
	global $wpdb;
	
	if ($wpdb->get_var("SHOW TABLES LIKE 'migreme'") != 'migreme') {
		$sql = "
		CREATE TABLE `migreme` (
		  `id` BIGINT( 20 ) UNSIGNED NOT NULL auto_increment,
		  `url` text NOT NULL,
		  `compac` text NOT NULL,
		  PRIMARY KEY ( `id` )
		)";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
}

register_deactivation_hook( __FILE__, 'tbmigreme_deactivate' );

function tbmigreme_deactivate() {
	global $wpdb;
	$wpdb->query("DROP TABLE IF EXISTS `migreme`");
}

/* ADICIONA O TBTWIT NO MENU DE PLUGINS */

function tbmigreme_add_menu() {
 if (function_exists('add_options_page')) {
    add_submenu_page('options-general.php', 'TB Migre.me', 'TB Migre.me', 8, basename(__FILE__), 'tbmigreme_page');
  }
}
add_action('admin_menu', 'tbmigreme_add_menu');

function tbmigreme_page() {
	include_once(dirname(__FILE__) . '/page.php');
}

/* QUANDO UM POST FOR PUBLICADO, TWITTA-LO */

add_action('admin_menu', 'tbmigreme_add_custom_box');

add_action('save_post', 'tbmigreme_save_postdata');

function tbmigreme_add_custom_box() {
	
	$u = get_option('tbmigreme_user');
	if (!empty($u)) {
		add_meta_box( 'tbmigreme_sectionid', __( 'Twittar esse post?', 'tbmigreme_textdomain' ), 
		            'tbmigreme_inner_custom_box', 'post', 'side', 'high');
	}

}
   
function tbmigreme_inner_custom_box() {

	echo '<input type="hidden" name="tbmigreme_noncename" id="tbmigreme_noncename" value="' . 
	  wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

	echo '<label for="tbmigreme_twittar"><input type="checkbox" name="tbmigreme_twittar" id="tbmigreme_twittar" value="true" /> Sim, twittar!</label>
	
	<script type="text/javascript" charset="utf-8">
		jQuery(document).ready(function(){
			
			jQuery("#tbmigreme_twittar").click(function(){
				if (jQuery("#tbmigreme_twittar:checked").val() == undefined) {
					jQuery("#tbmigreme_msg").hide();
					jQuery(".cc").hide();
				} else {
					jQuery("#tbmigreme_msg").show();
					jQuery(".cc").show();
				}
			});

			jQuery("#tbmigreme_msg").keyup(function(){
				c = 120 - jQuery("#tbmigreme_msg").val().length;
				if (c == 0) { return false; }
				jQuery(".cc").html(c + " caracteres restantes");
			});
			
		});
	</script>
	
	<textarea type="text" name="tbmigreme_msg" id="tbmigreme_msg" value="" lines="2" placeholder="Digite o texto do tweet aqui" style="display:none;width:100%;margin-top:10px;" maxlength="120"></textarea><span style="display:block;float:right;color:#888;margin-top:5px;display:none;" class="cc">120 caracteres restantes</span><div style="clear:both;"></div>';

}

function tbmigreme_save_postdata($postID) {

	if ( !wp_verify_nonce( $_POST['tbmigreme_noncename'], plugin_basename(__FILE__) )) {
	  return $postID;
	}

	if ( 'page' == $_POST['post_type'] ) {
	  if ( !current_user_can( 'edit_page', $postID ))
	    return $postID;
	} else {
	  if ( !current_user_can( 'edit_post', $postID ))
	    return $postID;
	}
	
	if ($parent_id = wp_is_post_revision($postID)) {
		$postID = $parent_id;
	}
	
	if ($_POST['tbmigreme_twittar'] && (get_post_status($postID) == 'publish')) {
		twit_post($postID, $_POST['tbmigreme_msg']);
	}

}

add_filter('the_content', 'tbmigreme_display_button');

function tbmigreme_display_button($text) {
	if (get_option('tbmigreme_uso_botao') == 'before') {
		$text = '<div class="tb_migreme_antes">' . tb_migreme_button() . '</div>' . $text;
	} elseif (get_option('tbmigreme_uso_botao') == 'after') {
		$text = $text . '<div class="tb_migreme_depois">' . tb_migreme_button() . '</div>';
	}
	
	return $text;
}

global $twitado;
$twitado = false;

/**
 * Função que twitta um post que acabou de ser publicado
 * @author Leandro Alonso
 * @param int $postID
 */
function twit_post($postID, $text = '') {
	global $twittado;
	
	// Usado para evitar que o post seja twittado duas vezes
	if ($twitado) { return false; }
	
	$twittado = true;
	
	$twitt = new Twit(get_permalink($postID));
	
	$user = get_option('tbmigreme_user'); // Usuário no Twitter
	$pass = base64_decode(get_option('tbmigreme_pass')); // Password
	$twitter = 'http://twitter.com/statuses/update.xml';
	if (!empty($text)) {
		$status = $text . ' ' . $twitt->getMigremeUrl();
	} else {
		$status = get_the_title($postID) . ' ' . $twitt->getMigremeUrl();
	}
	
	twitterSetStatus($user, $pass, $status);
}

/**
 * Classe responsável por fazer a compactação de uma URL no migre.me e retornar a URL para Twittar
 * @author Leandro Alonso
 * @param string $url
 */
class Twit {
	
	public $url;
	
	/**
	 * Método construtor que seta o atributo $url do objeto
	 * @author Leandro Alonso
	 * @param string $url
	 */
	function __construct($url) {
		$this->url = $url;
	}
	
	/**
	 * Método que retorna a URL "migremizada". É feita uma checagem no cache, e caso ele não exista
	 * é realizada uma consulta na API do migre.me
	 * @author Leandro Alonso
	 * @param string $url
	 */
	function getMigremeUrl() {
		if ($this->isInCache()) {
			// Faz nada
		} else {
			$this->migremeUrl = $this->consultApi();
			$this->saveInCache($this->migremeUrl);
		}
		
		return $this->migremeUrl;
	}
	
	/**
	 * Método que verifica se a URL está no cache
	 * @author Leandro Alonso
	 * @param string $url
	 */
	function isInCache() {
		global $wpdb;
		$cache = $wpdb->get_row("SELECT * FROM `migreme` WHERE `url` = '$this->url'");
		if ($cache) {
			$this->migremeUrl = $cache->compac;
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Método que consulta a API do migre.me retornando a URL "migremizada"
	 * @author Leandro Alonso
	 * @param string $url
	 */
	function consultApi() {
		$api = 'http://migre.me/api.xml?url=' . urlencode($this->url);
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $api);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		$compact = curl_exec($curl_handle);
		curl_close($curl_handle);
		try {
			$xml = new SimpleXMLElement(str_replace('&', '&amp;', $compact));
		} catch (Exception $err) {
			return $this->url;
		}
		return $xml->migre;
	}
	
	/**
	 * Método que faz o cache da consulta à API do migre.me
	 * @author Leandro Alonso
	 * @param string $url
	 */
	function saveInCache($migremeUrl) {
		if (!strstr($migremeUrl, 'migre.me')) return false;
		global $wpdb;
		$wpdb->query("INSERT INTO `migreme` VALUES (NULL, '$this->url', '$migremeUrl')");
	}
	
	function getUrl($title) {
		$migreme = $this->getMigremeUrl();
		$twit = $migreme . ' ' .  $title;
		
		// Verifica se a mensagem possui mais de 140 caracteres
		// Em caso positivo, refaz a mensagem para que a URL não seja cortada
		if (strlen($twit) > 140) {
			$caractersTitulo = 136 - strlen($migreme);
			$twit = $migreme . '... ' . trim(substr($title, 0, $caractersTitulo));
		}
		
		echo 'http://twitter.com/home?status=' . urlencode($twit);
	}
	
}

function TbTwit() {
	$twitt = new Twit(get_permalink());
	$twitt->getUrl(get_the_title());
}

function tb_migreme() {
	echo tb_migreme_button();
}

function tb_migreme_button() {
	$botao = get_option('tbmigreme_botao');
	
	$t = new Twit(get_permalink());
	$url = $t->getMigremeUrl();
	
	if ($botao == 'botao1') {
		return '<iframe allowtransparency="true" frameborder="0" scrolling="no" tabindex="0" class="twitter-share-button twitter-count-none" src="http://platform2.twitter.com/widgets/tweet_button.html?count=vertical&amp;lang=en&amp;text='.get_the_title().'&amp;url='.get_permalink().'" style="width: 55px; height: 62px; "></iframe>';
	} elseif ($botao == 'botao2') {
		return '<iframe allowtransparency="true" frameborder="0" scrolling="no" tabindex="0" class="twitter-share-button twitter-count-none" src="http://platform2.twitter.com/widgets/tweet_button.html?count=horizontal&amp;lang=en&amp;text='.get_the_title().'&amp;url='.get_permalink().'" style="width: 110px; height: 20px; "></iframe>';
	} elseif ($botao == 'botao3') {
		return '<iframe allowtransparency="true" frameborder="0" scrolling="no" tabindex="0" class="twitter-share-button twitter-count-none" src="http://platform2.twitter.com/widgets/tweet_button.html?count=none&amp;lang=en&amp;text='.get_the_title().'&amp;url='.$url.'" style="width: 55px; height: 20px; "></iframe>';
	} elseif ($botao == 'botao4') {
		return '<style type="text/css" media="screen">.tbtwit {padding: 3px;background: -moz-linear-gradient(center top , #fff, #e4e7e9) repeat scroll 0 0 transparent;background: -webkit-gradient(linear, 0% 40%, 0% 70%, from(#fff), to(#e4e7e9));border: 1px solid #dfdfdf;-moz-border-radius: 3px;-khtml-border-radius: 3px;-webkit-border-radius: 3px;height:14px;font:12px Arial, helveltica, "Lucida Grande", "Lucida Sans Unicode", Verdana, Sans-serif;color:#222;}.tbtwit:hover{border: 1px solid #c0c0c0;color:#222;text-decoration:none;}.tbtwit img{margin-bottom:-3px;margin-right:3px;}</style>
		
		<a class="tbtwit" href="'.get_tbmigreme_link().'"><img src="'.get_bloginfo('wpurl').'/wp-content/plugins/tbmigreme/bird_16_blue.png'.'" alt="" />Twittar</a>';
		
	}
}

function get_tbmigreme_link() {
	$t = new Twit(get_permalink());
	$url = $t->getMigremeUrl();
	return 'http://twitter.com/intent/tweet?related='.get_option('tbmigreme_user').'&text=' . get_the_title() . ' ' . $url;
}

function tb_migreme_form() {
	$t = new Twit(get_permalink());
	$url = $t->getMigremeUrl();
	echo '<style type="text/css" media="screen">
		.tbmigreme_form {
			margin: 5px
		}
		.tbmigreme_form input {
			width: 130px;
			border: 1px solid #ccc;
			-webkit-border-radius: 3px;
			-moz-border-radius: 3px;
			border-radius: 3px;
			padding: 2px;
		}
	</style>
	<div id="tbmigreme_form" class="tbmigreme_form">
		<label for="tbmigreme">Link: <input type="text" id="tbmigreme" value="'.$url.'" onclick="this.focus();this.select();" /></label>
	</div>';
}

function get_tbmigreme() {
	$t = new Twit(get_permalink());
	return $t->getMigremeUrl();
}

global $tbplugin;
$tbplugin[] = 'tbmigreme';

/*add_action('wp_footer', 'tbmigreme_footer');

function tbmigreme_footer() {
	if (get_option('tbmigreme_promova') == 'false') return false;
	global $tbplugin;
	$tbplugin['msg_footer'] = 'posted';
	echo '<div style="font-size:10px;color:#555;">Usando plugins do <a href="http://tecnoblog.net/">TB</a></div>';
}*/

/* Função que atualiza o status no Twitter sem usar oAuth */

function twitterSetStatus($user,$pwd,$status) {
	if (!function_exists("curl_init")) die("twitterSetStatus needs CURL module, please install CURL on your php.");
	$ch = curl_init();

	// -------------------------------------------------------
	// get login form and parse it
	curl_setopt($ch, CURLOPT_URL, "https://mobile.twitter.com/session/new");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "my_cookies.txt");
	curl_setopt($ch, CURLOPT_COOKIEFILE, "my_cookies.txt");
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1A543a Safari/419.3 ");
	$page = curl_exec($ch);
	$page = stristr($page, "<div class='signup-body'>");
	preg_match("/form action=\"(.*?)\"/", $page, $action);
	preg_match("/input name=\"authenticity_token\" type=\"hidden\" value=\"(.*?)\"/", $page, $authenticity_token);

	// -------------------------------------------------------
	// make login and get home page
	$strpost = "authenticity_token=".urlencode($authenticity_token[1])."&username=".urlencode($user)."&password=".urlencode($pwd);
	curl_setopt($ch, CURLOPT_URL, $action[1]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $strpost);
	$page = curl_exec($ch);
	// check if login was ok
	preg_match("/\<div class=\"warning\"\>(.*?)\<\/div\>/", $page, $warning);
	if (isset($warning[1])) return $warning[1];
	$page = stristr($page,"<div class='tweetbox'>");
	preg_match("/form action=\"(.*?)\"/", $page, $action);
	preg_match("/input name=\"authenticity_token\" type=\"hidden\" value=\"(.*?)\"/", $page, $authenticity_token);

	// -------------------------------------------------------
	// send status update
	$strpost = "authenticity_token=".urlencode($authenticity_token[1]);
	$tweet['display_coordinates']='';
	$tweet['in_reply_to_status_id']='';
	$tweet['lat']='';
	$tweet['long']='';
	$tweet['place_id']='';
	$tweet['text']=$status;
	$ar = array("authenticity_token" => $authenticity_token[1], "tweet"=>$tweet);
	$data = http_build_query($ar);
	curl_setopt($ch, CURLOPT_URL, $action[1]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$page = curl_exec($ch);

	return true;
}