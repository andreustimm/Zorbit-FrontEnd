<?php 
$banner_thin = true;
$banner = false;

uses("Db","Html","Object","String");
uses('HtmlHead');

HtmlHead::addjQuery();
HtmlHead::addJavascript('jquery.validate.min.js');
HtmlHead::addJavascript('jquery.maskedinput-1.2.2.min.js');
HtmlHead::addJavascript('mask.messages_ptbr.js'); // Mensagens de aviso e erro em pt_BR para maskedInput
HtmlHead::addJavascript('br.validate.extend.js'); // Regras de validação para o Brasil (CNPJ, CPF, data formato dd/mm/yyyy)

$validador = <<<VALIDATE_JS
$('form[name=form_email]').validate({
							rules: {
							    cnpj: 'cnpj',
							    cpf:  'cpf'
							},
							required:true});
$('input.fone').mask("(99) 9999-9999",{placeholder:" "})
		   .blur(function () {
		   		$('input[name=cep]').focus();
		   });
$('input[name=cep]').mask("99999-999",{placeholder:" "})
					.change(function () {
                    	$.post('ajax_cep.php','cep='+$(this).val(),function(data) {
							if (data==undefined || data.substr(0,2) != "ok") return false;
							var address = data.split(";");
							$('input[name=rua]').val(address[1]);
							$('input[name=bairro]').val(address[2]);
							$('input[name=cidade]').val(address[3]);
							$('input[name=estado]').val(address[4]);
							$('input[name=numero]').focus();
                        });
					});
$('input[name=numero]').blur(function () {
	if ($('input[name=cep]').val().length == 9) $('input[name=cnpj]').focus();
});
$('input[name=estado]').mask("aa",{placeholder:" "}).css('text-transform','uppercase');
$('input[name=cnpj]').mask("99.999.999/9999-99",{placeholder:"–"});
$('label[for=cnpj').dblclick(function() {
	var oldVal= $(this).text();
	var label = (oldval == 'CNPJ') ? 'CPF':'CNPJ';
	$(this).text(label);
	$('input[name=cnpj]').removeClass(oldVal).addClass(label);
});
VALIDATE_JS;

HtmlHead::addJavaScriptContent($validador);
unset ($validador);


// foreach ($_REQUEST as $campo => $valor) {
// 	$$campo = String::anti_injection($valor);
// }
$pedido      = String::anti_injection($_COOKIE ["pedido"]);
$posto       = String::anti_injection($_COOKIE ["posto"]);
$logar       = String::anti_injection($_GET['logar']);
$envia_email = String::anti_injection($_GET['envia_email']);
$tipo_email  = String::anti_injection($_POST['tipo_email']);

#---------- Não é posto autorizado, enviar email para Ronaldo -----------------
if ($envia_email=="sim") {
/*  Adicionando validação do formulário */
	if (!function_exists('is_email')) {
		function is_email($email=""){   // False se não bate...
			return (preg_match("/^[\w!#$%&'*+\/=?^`{|}~-]+(\.[\w!#$%&'*+\/=?^`{|}~-]+)*@(([\w-]+\.)+[A-Za-z]{2,6}|\[\d{1,3}(\.\d{1,3}){3}\])$/", $email));
		}
	}
	if (!function_exists('is_phoneBR')) {
		function is_phoneBR($fone=""){   // False se não bate...
			return (preg_match("/^\(?\d{2}\)?[\s-]?\d{4}-?\d{4}$/", $fone));
		}
	}
	if (!function_exists('is_cnpj')) {
		function is_cnpj($cnpj="") {   // False se não bate...
		    $res = Db::query("SELECT fn_valida_cnpj_cpf('".preg_replace("/\D/","",$cnpj)."')");
		    return is_resource($res);
		}
	}

	foreach($_POST as $campo => $valor) {
	    $$campo = String::anti_injection($valor);
	}
//  Valida formulário, caso o usuário tenha desativado o JavaScript
	$msg_erro = array();
	if (strlen($nome) < 6)		$msg_erro[] = "A razão social é obrigatória. Por favor, digite a razão social.";
	if (!is_email($email))		$msg_erro[] = "O e-mail fornecido ($email) não é válido.";
	if (!is_phoneBR($telefone))	$msg_erro[] = "O nº de telefone fornecido ($telefone) parece estar errado, por favor, confira.";
	if (strlen($rua)==0)		$msg_erro[] = "O endereço é obrigatório, por favor digite o endereço completo.";
	if (strlen($numero)==0)		$msg_erro[] = "O endereço é obrigatório, por favor digite o endereço completo.";
	if (strlen($cep) != 9) 		$msg_erro[] = "O endereço é obrigatório, por favor digite o CEP.";
	if (strlen($cidade)==0) 	$msg_erro[] = "O endereço é obrigatório, por favor digite a cidade.";
	if (strlen($estado)==0) 	$msg_erro[] = "O endereço é obrigatório, por favor digite o estado.";
	if (!is_cnpj($cnpj) && $tipo_email != 'fale_conosco')
						 		$msg_erro[] = "O CNPJ digitado não é válido.";

//  Formata o telefone
	if (is_phoneBR($telefone)) $telefone = preg_replace('/(\d{2})(\d{4})(\d{4})/','($1) $2-$3', $telefone);

	if (count($msg_erro) == 0) {
		if (!$posto and $cnpj != "" and  $tipo_email != 'fale_conosco') {
// 	    echo "Procurando posto por CNPJ: $cnpj<br>\n";
			$sql_pp = "SELECT posto FROM tbl_posto WHERE cnpj = '".preg_replace("/\D/","",$cnpj)."'";
			$res_pp = Db::query($sql_pp);
			if (pg_num_rows($res_pp)==1) {
				$posto = pg_fetch_result($res_pp,0,0);
				if ($pedido) $res_ap= Db::query("UPDATE tbl_pedido SET posto=$posto WHERE pedido=$pedido");
			}
		}
		$tipo_email= (isset($_REQUEST['tipo_email'])) ? String::anti_injection($_REQUEST['tipo_email']) : $_GET['t'];

		if ($tipo_email == 'pedido') {
			$sql = "SELECT SUM (qtde * preco) AS total FROM tbl_pedido_item WHERE pedido = $pedido";
			$res = Db::query($sql);
			$total = pg_fetch_result($res,0,total);

			$sql = "UPDATE tbl_pedido SET /* tabela = 30, pedido_via_distribuidor = true, */ finalizado=CURRENT_TIMESTAMP, total=$total WHERE pedido=$pedido";
			$res = Db::query($sql);

			$destino = "Location:confirmar_pedido_email.php?m=OK&t=".$tipo_email[0];
		}
		include ABS_PATH."controllers/email_pedido_inc.php";
	}
}

#------- Conferir login do posto -------------
/*
Exemplo: visitar_loja = 1307005122250000
lu:      1  (é login único)
cplen:   3
ctrlpos:07
fabrica:00
posto:  512 (len=3, login único (lu=1))
control:2250000
*/
if (($loginAssist = $_GET['visitar_loja']) != "") {   // Este sistema de login é para quando vem do Assist
	$lu = ($loginAssist[0] == '1');
	$cp_len         = hexdec(substr($loginAssist, 1, 1));
	$ctrl_pos       = substr($loginAssist, 2, 2);
	$fabrica		= substr($loginAssist, 4, 2);
	$codigo_posto	= Db::escape(substr($loginAssist, 6, $cp_len));
	$controle		= substr($loginAssist, 2+$ctrl_pos);
	$controle2      = (date('d')*24 + date('h')) * 3600;
// 	echo "<h2>Es LU: ";
// 	echo ($lu)?"sim":"não";
// 	echo "<br>".
// 		 "Posto/LU: $codigo_posto ($cp_len)<br>".
// 		 "Fabrica: $fabrica<br>".
// 		 "$controle = $controle2</h2>";

	if ($controle == $controle2) {
		if ($lu) {
			$sql = "SELECT posto FROM tbl_login_unico
								WHERE login_unico	= $codigo_posto
								  AND ativo IS TRUE";
			} else {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE posto			= $codigo_posto
														  AND fabrica		= $fabrica
														  AND credenciamento= 'CREDENCIADO'";
		}
	}
	$res = Db::query($sql);
	if (is_resource($res) && pg_num_rows($res) == 1){
		$posto	= pg_fetch_result ($res,0,'posto');
// 		$sql	= "UPDATE tbl_pedido SET posto=$posto WHERE pedido=$pedido";
// 		$res	= Db::query($sql);
		setcookie ("posto", $posto);
		Log::debug('Login->Redirecionar');
		header("Location: ./");
		exit;
	}
}

#------- Posto já está logado e autenticado e tem pedido em aberto --------------
if (strlen ($posto) > 0 and $pedido) {
	$sql ="UPDATE tbl_pedido SET posto=$posto where pedido=$pedido";
	$res = Db::query($sql);
	header("location: confirmar_pedido.php");
	exit;
}

if ($logar=="sim") {
	Log::debug('Login->Logar');
	$codigo_posto 	= Db::escape($_POST['codigo_posto']);
	$senha			= Db::escape($_POST['senha']);
	$sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto='$codigo_posto' and senha='$senha'";
	$res = Db::query($sql) ;

	if ( is_resource($res) && pg_num_rows($res) == 0){
		Log::debug('Login->Buscar login unico');
		$senha_md5 = "md5" . md5($senha);
		$sql = "SELECT posto FROM tbl_login_unico where email='$codigo_posto' and senha='$senha_md5'";
		$res = Db::query($sql) ;
	}

	if ( is_resource($res) && pg_num_rows($res) > 0){
		$posto=pg_fetch_result ($res,0,'posto');
		$sql ="UPDATE tbl_pedido SET posto=$posto where pedido=$pedido";
		$res = Db::query($sql);
		setcookie ("posto", $posto);
		Log::debug('Login->Redirecionar');
		header("Location: confirmar_pedido.php");
		exit;
	}else{
		$msg_erro[] = 'Código ou senha inválido.';
	}
}


echo HtmlHead::title("Autenticação de usuário");

/*  Se tem erro de validação do formulário no PHP...    */
if (count($msg_erro)) {
	echo "	<div class='erro'>\n";
	foreach($msg_erro as $text_erro) {
	    echo "		<li class='branco negrito'>$text_erro</li>\n";
	}
	echo "	</div>\n";
}

/*	Continua o layout	*/
?>
		<h5>IDENTIFICA&Ccedil;&Atilde;O</h5>
		<h6>DIGITE OS DADOS PARA CONFIRMAR A COMPRA</h6>
		<table id='login' width="95%" align='center' border="0">
			<form name="form_login" method="post" action="<?php echo $PHP_SELF; ?>?logar=sim">
	            <input type="hidden" name="tipo_email"	value="pedido">
				<tr>
					<td width="193">
						<label  for="codigo_posto">Cód. Posto</label>
						<input name="codigo_posto" type="text" id="codigo_posto" size="30" value='<?=$codigo_posto?>'>
					</td>
					<td width="574" rowspan="2" valign="top"><br>
						Se você é um posto autorizado faça o seu login para confirmar o pedido. </td>
				</tr>
				<tr>
					<td>
						<label  for="senha">Senha</label>
						<input name="senha" type="password" id="senha" size="30">
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?echo Html::submit('botao','Entrar','Entrar','rc-all'); ?>
					</td>
				</tr>
			</form>
		</table>


		<form name="form_email" method="post" action="<?=$PHP_SELF?>?envia_email=sim">
            <input type="hidden" name="pedido"		value="<?=$pedido?>">
            <input type="hidden" name="tipo_email"	value="pedido">
			<h6>PARA QUEM N&Atilde;O &Eacute; CADASTRADO PREENCHA OS DADOS, LOGO ENTRAREMOS EM CONTATO COM VOC&Ecirc;</h6>
			<table id='email' border="0" width='95%'>
				<tr>
					<td colspan="2">
						<label  for='nome'>Razão Social</label>
						<input name="nome" type="text" id="nome" size="77" class='required' minlength='6' value='<?=$nome?>'>
					</td>
					<td width="343" rowspan="7" valign="top" bgcolor="#EFEFEF">
						<p>
							Se voc&ecirc; n&atilde;o &eacute; um posto autorizado,
							preencha o formul&aacute;rio ao lado que entraremos em contato
							para a formaliza&ccedil;&atilde;o do pedido. 
						</p>
						</td>
				</tr>
				<tr>
					<td width="280">
						<label  for='email'>E-Mail</label>
						<input name="email" type="text" id="codigo_posto" size="53" class='email required' value='<?=$email?>'>
					</td>
					<td>
						<label  for='telefone'>Telefone</label>
						<input name="telefone" type="text" id="telefone" size="16" class='fone' class='required' value='<?=$telefone?>'>
					</td>
				</tr>
				<tr>
					<td width="280">
						<label  for='rua'>Rua</label>
						<input name="rua" type="text" id="rua" size="53" minlength='6' class='required' value='<?=$rua?>'>
					</td>
					<td>
						<label  for='numero'>Nº</label>
						<input name="numero" type="text" id="numero" size="16" class='numeric required' value='<?=$numero?>'>
					</td>
				</tr>
				<tr>
					<td>
						<label  for='bairro'>Bairro</label>
						<input name="bairro" type="text" id="bairro" size="53" class='required' value='<?=$bairro?>'>
					</td>
					<td>
						<label  for='cep'>CEP</label>
						<input name="cep" type="text" id="CEP" size="16"  minlength='9' maxlength='9' class='required' value='<?=$cep?>'>
					</td>
				</tr>
				<tr>
					<td>
						<label  for='cidade'>Cidade</label>
						<input name="cidade" type="text" id="cidade" size="53" class='required' value='<?=$cidade?>'>
					</td>
					<td>
						<label  for='estado'>Estado</label>
						<input name="estado" type="text" id="estado" size="16"  minlength='2' class='required' value='<?=$estado?>'>
					</td>
				</tr>
				<tr>
					<td>
						<label  for='cnpj'>CNPJ</label>
						<input name="cnpj" type="text" id="cnpj" size="53" maxlength='19'  minlength='11' class='required' value='<?=$cnpj?>'>
					</td>
					<td>
						<label  for='ie' title='Inscrição Estadual'>I.E.</label>
						<input name="ie" type="text" id="ie" size="16" maxlength='20' class='required' value='<?=$ie?>'>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<label for='mensagem'>Mensagem</label>
						<textarea name="mensagem" cols="74" rows="3" id="mensagem" size="16"><?=$mensagem?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="3">
	            		<?=Html::botao("botao","Limpar","Limpar","reset","rc-left");?>
	            		<?=Html::submit("botao","Enviar","Enviar","rc-right");?>
					</td>
				</tr>
			</table>
		</form>
