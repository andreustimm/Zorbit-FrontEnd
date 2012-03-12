// Determina se o navegador tem suporte para input type='range'
var rangeNotSupported;
var i = document.createElement("input");

i.setAttribute("type", "range");
rangeNotSupported = (i.type === "text");
delete i;

// método 'repeat' para o objeto String
// ex. a='123'; b = a.repeat(3) -> b = '123123123'
String.prototype.repeat = function(iterates) {

    if (iterates == undefined || iterates < 1) return "";
    var str = this;

    while (--iterates > 0) {
        str += this;
    }

    return str;

}

/* Minha versão ... */
/* ============= Função FORMATA NÚMERO ===========================
   Nome da Função : numFmt(DOM formField)
   Formata um campo numérico a medida que ocorre a digitação
   Parâm.: campo.
   PparseStr(rseInt(arâm.: options (obj)
           prefix, suffix, decimals
   TODO: Se o campo tiver a class data-moneyField e data-moneyPrefix
   formata o campo como moeda, com o prefixo ou sufixo informado
   =================================================================*/
function numFmt(campo, options) {
    
    if (options == undefined) {
        options = new Object;
        options.decimals = 2;
    }
    if (options.decimals == undefined) {
        options.decimals = 2;
    }
    var valor = campo.value;
    valor = valor.replace(/\D/g, '');

    var ln        = valor.length;
    var tGroups   = Math.round((ln-options.decimals)/3+0.49);
    var thGroup   = "(\\d{3})".repeat(tGroups - 1);
    var decGroup  = "(" + "\\d".repeat(options.decimals)+")";
    var regEx     = new RegExp("^(\\d{1,3})"+thGroup+decGroup+"$");
    var repStr    = new Array('','','');
    var grupos    = tGroups;
    var repString = '';

    if (options.prefix != undefined) repStr[0] = options.prefix;
    if (options.suffix != undefined) repStr[2] = options.suffix;

    for (i = 2; i < tGroups+1; i++) {
        repString += "$"+i+'.';
    }

    repString = "$1." + repString;
    repString = repString.replace(/\.$/, '');
    repStr[1] = repString + ',$' + i;

    console.log(repStr.join(''));
    /*
    if (ln ==  2) campo.value = ',' + valor;
    if (ln ==  6) campo.value = '.' + valor;
    if (ln == 10) campo.value = '.' + valor;
    */
    console.log(valor, ln, tGroups, regEx, repStr);
    campo.value = valor.replace(regEx, repStr.join(''));

    return true;

}

$(function() {

    Calendar.setup({
        inputField : "data",    // ID of the input field
        ifFormat   : "%d/%m/%Y" // the date format
    });

    Calendar.setup({
        inputField : "diaehora",       // ID of the input field
        showsTime  : true,
        ifFormat   : "%d/%m/%Y %H:%M" // the date format
    });

    var calendar = Calendar(1, null);

    $.datepicker.setDefaults($.datepicker.regional['br']);
    $.datepicker.setDefaults({
        dateFormat         : 'dd/mm/yy',
        showMonthAfterYear : false,
        changeMonth        : true,
        constrainInput     : true,
        maxDate            : '+0'
    });

    $('#tabs').tabs();

    // Adiciona alguns 'extras' ao maskedinput: 0 núm opcional, A letras "especiais" (não todas), H caractere hexadecimal
    $.mask.definitions['0'] = '[0-9]?';
    $.mask.definitions['A'] = '[A-Za-záéíóúÁÉÍÓÚàèòùïüãõñçÀÈÒÙÏÜÃÕÑÇôÔêÊ]?';
    $.mask.definitions['H'] = '[A-Fa-f0-9]';

    $('form[name=exemplo]').validate({
        rules : {
            cnpj  : 'cnpj',
            cpf   : 'cpf',
            email : {
                required : true,
                email    : true
            },
            password : {equalTo: '#pass2'}
        }
    });

    //Não bate com todos os telefones do mundo, mas quase...
    $('input[rel=fone_en]')
        .mask("+099 (099) 0999-9999", {placeholder:" "});
    $('input[rel=fone]')
        .mask("(99) 9999-9999", {placeholder:" "});

    $('input[name=numero]').blur(function () {//efeitos do prototipo.
        if ($('input[name=cep]').val().length == 9) $('input[name=cnpj]').focus();
    });

    $('#dialog').click(function () {
        $('#msg').html("Conteudo do Dialog").dialog('open');
    });

    $('input[type=color], input.color')
        .mask('#HHHHHH');

    $('input[rel=date]')
        .mask("99/99/9999")
        .attr("pattern", "[0-3]\d\/[012]\d\/[12][019]\d{2}");

    $('input[rel=time]')
        .mask("99:99")
        .attr("pattern", "[0-2][0-9]:[0-5][0-9]");

    $('input[rel=datetime]')
        .mask("99/99/9999 99:99")
        .focus(function() {
            var meuID = $(this).attr('id');
            Calendar.setup({
                inputField : meuID,            // ID of the input field
                showsTime  : true,
                ifFormat   : "%d/%m/%Y %H:%M", // the date format
            });
        });

    $('input[rel=cep]')
        .mask("99999-999", {
            placeholder : " ",
            completed   : function() {
                var cep = $(this).val();
                $('#msg').html("Aqui devolve os dados do CEP " + cep + ".").dialog('open');
            }
        });

    $('input[name=estado]')
        .mask("aa", {placeholder:" "})
        .css('text-transform', 'uppercase')
        .blur(function() {
            $(this).val($this-val().toLocaleUpperCase());
        });

    $('input[rel=cpf]')
        .mask("999.999.999-99", {placeholder:" "});
    $('input[rel=cnpj]')
        .mask("99.999.999/9999-99", {placeholder:" "});

    /***************************************************************************************
     * Se quiser limitar o tamanho máximo, utilizar o 'mask', no segundo caso, devidamente *
     * modificado.                                                                         *
     * Para setar um mínimo obrigatório de caracteres, colocar o '?' no lugar apropriado.  *
     * Neste caso, o login tem um mínimo de 5 caracteres e o 'nome completo' apenas 1.     *
     * Se for apenas limitar os caracteres e não o comprimento mínimo e máximo,            *
     * então utilizar o 'alpha':                                                           *
     ***************************************************************************************/

    /*
       $('#login').mask("aaaaa?aaaaaaaaaa", {placeholder:" "});
       $('#nome_completo').mask("A?AAAAAAAAAAAAAAAAAAA", {placeholder:" "});
     */
    $('input[rel=login]')
        .attr("pattern", "[A-Za-z_.-]+")
        .alpha({allow: '_.', restraints:{nocaps:true}});
    $('input[rel=nome_completo]')
        .attr("pattern", "[A-Za-záéíóúÁÉÍÓÚàèòùïüãõñçÀÈÒÙÏÜÃÕÑÇôÔêÊ]+\s([A-Za-záéíóúÁÉÍÓÚàèòùïüãõñçÀÈÒÙÏÜÃÕÑÇôÔêÊ]\s?)+")
        .alpha({allow: 'áéíóúÁÉÍÓÚàèòùïüãõêñçÀÈÒÙÏÜÃÕÊÑÇòÔ '});

    $('input[type=password]').mask("******?*********", {placeholder:''});
    $('#trans').change(function() {

        $('#opaque').text($(this).val());
        $('#color').change();

    });

    $('#msg').dialog({
        autoOpen      : false,
        modal         : true,
        closeOnEscape : true,
        buttons: {
            OK: function () {$(this).dialog('close')}
        }
    });

    $('#color').change(function() {

        var colorEl = document.getElementById('color'); //Precisa ser o DOM element
        var cor     = '#' + $(this).val();

        var r = colorEl.color.rgb[0] * 100;
        var g = colorEl.color.rgb[1] * 100;
        var b = colorEl.color.rgb[2] * 100;

        var o = $('#trans').val();
        o = (o.length < 2) ? '0'+o : o;

        //console.log("Transparência: " + o);

        if (o != '100') {
            cor = 'rgba(' + r +'%,'+ g +'%,'+ b +'%, 0.'+ o + ')';
        }

        //console.log(cor);
        $('body').css('background-color', cor);

    });

    $("#tabsForm").show('normal');

});
