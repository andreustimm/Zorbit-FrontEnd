// JavaScript Document
jQuery.validator.addMethod(
  "dateBR",
  function(value, element) {
	var val_exp=value.split(/[/-.]]/);
  	if(val_exp.length==3){
      var ano=val_exp[2];
      var mes=val_exp[1];
      var dia=val_exp[0];
      if((ano>=1000)&&(ano<10000)){
        if((mes.match('0[13578]|10|12'))&&(dia.match(/^(0[1-9]|[1-2][0-9]|3[0-1])$/))){
          return true;
        }else if((mes.match('0[469]|11'))&&(dia.match(/^(0[1-9]|[1-2][0-9]|30)$/))){
          return true;
        }else if((mes=='02')&&(dia.match(/^([01][1-9]|2[0-8])$/))){
          return true;
        }else if((mes=='02')&&(dia=='29')&&((ano%400==0)||((ano%4==0)&&(ano%100!=0)))){
          return true;
        }else{
          if(jQuery(element).val().length==0) return true; else return false;
        }
      }else{
        if(jQuery(element).val().length==0) return true; else return false;
      }
    }else{
      if(jQuery(element).val().length==0) return true; else return false;
    }
  },
  "Data inválida"
);

jQuery.validator.addMethod("cpf", function(value, element) {
	value = value.replace('.','');
	value = value.replace('.','');
	cpf = value.replace('-','');
	while(cpf.length < 11) cpf = "0"+ cpf;
	var expReg = /^0+$|^1+$|^2+$|^3+$|^4+$|^5+$|^6+$|^7+$|^8+$|^9+$/;
	var a = [];
	var b = new Number;
	var c = 11;
	for (i=0; i<11; i++){
		a[i] = cpf.charAt(i);
		if (i < 9) b += (a[i] * --c);
	}
	if ((x = b % 11) < 2) { a[9] = 0 } else { a[9] = 11-x }
	b = 0;
	c = 11;
	for (y=0; y<10; y++) b += (a[y] * c--);
	if ((x = b % 11) < 2) { a[10] = 0; } else { a[10] = 11-x; }
	if ((cpf.charAt(9) != a[9]) || (cpf.charAt(10) != a[10]) || cpf.match(expReg)) return false;
	return true;
}, "Informe um CPF válido."); // Mensagem padrão

jQuery.validator.addMethod("cnpj", function(value, element) {
	cnpj = value.replace(/\D/g,"");
	while(cnpj.length < 14) cnpj = "0"+ cnpj;
	var expReg = /^0+$|^1+$|^2+$|^3+$|^4+$|^5+$|^6+$|^7+$|^8+$|^9+$/;
	var a = [];
	var b = new Number;
	var c = [6,5,4,3,2,9,8,7,6,5,4,3,2];

	for (i=0; i<12; i++){
	a[i] = cnpj.charAt(i);
	b += a[i] * c[i+1];
	}

	if ((x = b % 11) < 2) { a[12] = 0 } else { a[12] = 11-x }
	b = 0;
	for (y=0; y<13; y++) {
	b += (a[y] * c[y]);
	}

	if ((x = b % 11) < 2) { a[13] = 0; } else { a[13] = 11-x; }
	if ((cnpj.charAt(12) != a[12]) || (cnpj.charAt(13) != a[13]) || cnpj.match(expReg) ) if(jQuery(element).val().length==0) return true; else return false;
	return true;
}, "CNPJ inválido."); // Mensagem padrão



jQuery.validator.addMethod(
  "checked",
  function(value, element){
    var inp_name=(jQuery(element).attr('name'));
    if(jQuery('input[name="'+inp_name+'"]:checked').length){
      return true;
    }else{
      return false;
    }
  },
  "Escolha uma opção"
);
