<?php

if ($_POST['enviado']) {
	
	$username = $_POST['username'];
	
	$password = $_POST['password'];
	
	if (empty($username) || empty($password)) {
		echo "Preencha todos os dados";
	} else {
		update_option('tbmigreme_user', $username);
		update_option('tbmigreme_pass', base64_encode($password));
	}
	
	update_option('tbmigreme_botao', $_POST['botao']);
	
	update_option('tbmigreme_promova', $_POST['nao_promover']);
	
	update_option('tbmigreme_uso_botao', $_POST['uso_botao']);
	
	echo "<div id='update-nag' style='background:'>Suas opções foram salvas. :)</div>";
}

?>

<style type="text/css" media="screen">
	#botao_estilo label {
		display: block;
		float: left;
		margin-right: 15px;
		text-align: center;
		cursor: pointer;
		height: 100px;
		width: 120px;
		padding: 10px 7px 0 7px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
	}
	#botao_estilo label.sel, #botao_estilo label:hover {
		background: #DBE6EC;
	}
	#botao_estilo label img {
		margin-top: 10px;
	}
</style>

<script type="text/javascript" charset="utf-8">
	jQuery('#botao_estilo input').live('click', function(){
		jQuery('#botao_estilo label').removeClass('sel');
		jQuery(this).parents('label').addClass('sel');
	});
	
	jQuery('#nao_promover').live('click', function(){
		if (jQuery(this).attr('checked')) {
			jQuery('.emoticon').attr('src', 'http://tecnoblog.net/wp-content/uploads/2011/04/11.png');
		} else {
			jQuery('.emoticon').attr('src', 'http://tecnoblog.net/wp-content/uploads/2011/04/7.png');
		}
	});
</script>

<div class="wrap">
	<div id="icon-plugins" class="icon32"><br /></div>
	<h2 class="tb-twit">TB Migre.me</h2>
	
	<h3 class="title">Configurações</h3>
	
	<div>
		<p>Preencha os campos abaixo com os dados do seu perfil no Twitter. Dessa forma, você poderá twittar seus posts assim que eles forem publicados.</p>
		<form action="" method="post">
			
			<p><label for="username">Username: <input type="text" name="username" value="<?php echo get_option('tbmigreme_user') ?>" id="username" style="width:150px;" /></label></p>
			
			<p><label for="password">Password: <input type="password" name="password" value="<?php echo base64_decode(get_option('tbmigreme_pass')) ?>" id="password" style="margin-left:4px;width:150px;" /></label></p>

	</div>
	
	<h3 class="title">Botão do Twitter</h3>
	
	
		<p>O TB Migre.me pode inserir um botão com a contagem de retweets no seu post. Você pode ativá-lo e configurá-lo abaixo</p>
		
		<div style="margin:10px 0;">
		<label for="uso_botao">
		Inserir botão:
		<select name="uso_botao" id="uso_botao">
			<option value="manual" <?php if (get_option('tbmigreme_uso_botao') == 'manual') echo 'selected="selected"' ?>>Vou inserir manualmente</option>
			<option value="before" <?php if (get_option('tbmigreme_uso_botao') == 'before') echo 'selected="selected"' ?>>Antes do post</option>
			<option value="after" <?php if (get_option('tbmigreme_uso_botao') == 'after') echo 'selected="selected"' ?>>Depois do post</option>
		</select>
		</label>
	</div>
		
		<p><b>Estilo do botão:</b></p>
		<div id="botao_estilo">		
	
			<label for="botao1" <?php if (get_option('tbmigreme_botao') == 'botao1') echo 'class="sel"' ?>><input type="radio" name="botao" value="botao1" id="botao1" <?php if (get_option('tbmigreme_botao') == 'botao1') echo 'checked=""' ?> /><br/>
			<img src="http://a1.twimg.com/a/1301438647/images/goodies/tweetv.png" alt="" /></label>
			
			<label for="botao2" <?php if (get_option('tbmigreme_botao') == 'botao2') echo 'class="sel"' ?>><input type="radio" name="botao" value="botao2" id="botao2" <?php if (get_option('tbmigreme_botao') == 'botao2') echo 'checked=""' ?> /><br/>
			<img src="http://a1.twimg.com/a/1301438647/images/goodies/tweeth.png" alt="" style="margin-top:25px;" /></label>
			
			<label for="botao3" <?php if (get_option('tbmigreme_botao') == 'botao3') echo 'class="sel"' ?>><input type="radio" name="botao" value="botao3" id="botao3" <?php if (get_option('tbmigreme_botao') == 'botao3') echo 'checked=""' ?> /><br/>
			<img src="http://a1.twimg.com/a/1301438647/images/goodies/tweetn.png" alt="" style="margin-top:25px;" /></label>
			
			<label for="botao4" <?php if (get_option('tbmigreme_botao') == 'botao4') echo 'class="sel"' ?>><input type="radio" name="botao" value="botao4" id="botao4" <?php if (get_option('tbmigreme_botao') == 'botao4') echo 'checked=""' ?> /><br/>
			<img src="<?php bloginfo('wpurl') ?>/wp-content/plugins/tbmigreme/twittar.png" alt=""  style="margin-top:25px;" /></label>
			
			<div style="clear:both;"></div>
		
	</div>
	
	<h3 class="title">Retirar link do footer</h3>
	
	<div>
		<?php if (get_option('tbmigreme_promova') == 'false') : ?>
		<img src="http://tecnoblog.net/wp-content/uploads/2011/04/11.png" alt="Obrigado pelo apoio!" title="Obrigado pelo apoio!" style="float:right;" class="emoticon" />
		<?php else: ?>
		<img src="http://tecnoblog.net/wp-content/uploads/2011/04/7.png" alt="Obrigado pelo apoio!" title="Obrigado pelo apoio!" style="float:right;" class="emoticon" />
		<?php endif; ?>
		<p>O Tecnoblog não recebe nada pelo desenvolvimento de plugins, que muitas vezes são produzidos por necessidades internas e depois têm seu código adaptado para compartilhar com a comunidade. Você pode nos ajudar a divulgar esse plugin deixando a opção abaixo desmarcarda. Se preferir, também pode remover o link ao marcar a checkbox e depois clicar em <b>Salvar</b>.</p>
			<label for="nao_promover"><input type="checkbox" name="nao_promover" value="false" id="nao_promover" <?php if (get_option('tbmigreme_promova') == 'false') echo 'checked=""' ?> /> Remover o link de divulgação</label>

			<div class="submit">
			<input type="submit" value="Salvar">
			<input type="hidden" name="enviado" value="true" id="enviado">
			</div>
		</form>
	</div>
	<h3>Funções do TB Migre.me</h3>
	<div>
		<p><b><em>tb_migreme();</em></b> - Utilize essa função para inserir o botão de twitter nas páginas. Ela exibirá o botão conforme configurado nessa página.</p>
		<p><b><em>get_tb_migreme();</em></b> - Se você precisar apenas da url do post encurtada pelo Migre.me, atribua essa função a uma variável.</p>
		<p><b><em>tb_migreme_form();</em></b> - Essa função imprime um form com a url do Migre.me dentro.</p>
</div>