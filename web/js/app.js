 var timers = {};
 $(document).ready(function(){

	    $('.load-image').change(updateImage);
	    $("[data-mask]").inputmask();
	    $('.datepicker').datepicker({
	      autoclose: false,
	      format:'dd-mm-yyyy'
	    });
	 
	   	var tchatTimer = null;
	  	$.each($('input[type=number'),function(index,val){
	  	var input = $(val);
	  	var max = input.attr('max');

	  	if(max != undefined){
	  		
	  		input.blur(function Limiter(){

	  		var maxi = $(this).attr('max');
			var val = $(this).val();
			
			if(parseInt(val) > parseInt(maxi)){
				$(this).val('');
				createModal('danger','Limite de saisir','La valeur maximale autorisée est : '+$(this).attr('max'));

			}
		});
	  	}
	  });

  });

function activeLink(link){
	//alert(link)
	$('#my-sidebar li.treeview').removeClass('active');
	$('#my-sidebar li.treeview ul.treeview-menu li').removeClass('active');
	var lien = $('#my-sidebar li.treeview ul.treeview-menu li.'+link);
	if(lien.length > 0){
		lien.addClass('active');
		if(lien.parent().length > 0 )
			lien.parent().addClass('active')
		if (lien.parent().parent().length > 0 ) 
			lien.parent().parent().addClass('active')
	}
}


function overlay(element){
	var div = $('<div class="overlay" style="z-index: 200;"><i class="fa fa-refresh fa-spin"></i></div>');
	element.prepend(div);
	
}
function overlayStop(){
	$('.overlay').remove();
}

function refreshTempsPoint(id){
	var object = {
		id:id,
		method : 'refreshTempsPoint'
	}
	
	$.post(ajaxUrl,object,function(data){
		$('#refreshTempsPoint').html(data);

		setTimeout(function(){
			refreshTempsPoint(id);
		},5000)
	}).fail(function(error){
		////createModal("danger", "Erreur", error.responseText)
			
	});
}

function ajoutCampagne(event,that){
	form = $(that);
	
	event.preventDefault();

	url = form.attr('url');

	var block = createModal('default','Ajout de campagne','Traitement en cours...');
	overlay(block);
	var texte = "<p>Aller prendre une tasse de thé car l'opération peut prendre du temps. <p>";
	var formData = false;
	if (window.FormData) {
		
	    formData = new FormData(form[0]);
	}
	$.ajax({
        url: url,
        type: 'POST',
        data: formData,
        async: true,
        success: function (data) {
        	block.html(data.message);

        	if(data.code == 1)
        		setTimeout(function(){
        			location.href = data.lien;
        		},3000)
        },
        fail: function(error){
        	alert("Erreur testerConditions JS");//error.responseText);
        },
        cache: false,
        contentType: false,
        processData: false
    });

	setTimeout(function(){
		block.append(texte);
	},60000);
	return false;
}
function testerConditions(event,that){

	form = $(that);
	
	event.preventDefault();
	url = form.attr('url');
	var block = $('#blockTestCondition');
	block.html('<p class"text-center text-danger">Traitement en cours ...</p>');
	overlay(block);
	
	var formData = false;
	if (window.FormData) {
		
	    formData = new FormData(form[0]);
	    console.log(formData);
	    //alert('oi')
	}

	$.ajax({
        url: url,
        type: 'POST',
        data: formData,
        async: true,
        success: function (data) {
        	block.html(data);
        },
        fail: function(error){
        	alert("Erreur testerConditions JS");//error.responseText);
        },
        cache: false,
        contentType: false,
        processData: false
    });
	return false;
}

function envoyerCampagne(code){
	var box = createModal('default','Envoyer de campagne','Envoi en cours....');
	var texte = "<p>Aller prendre une tasse de thé car l'opération peut prendre du temps. <p>";
	overlay(box);
	var object = {
		method : 'envoyerCampagne',
		code : code
	}
	$.post(ajaxUrl,object,function(data){
		box.html(data.message);
		if(data.code == 1 )
			location.reload();
	}).fail(function(error){});

	setTimeout(function(){
		box.append(texte);
	},30000);
}

function annulerCampagne(code){
	var box = createModal('default','Annuler de campagne','Annulation en cours....');
	
	overlay(box);
	var object = {
		method : 'annulerCampagne',
		code : code
	}
	$.post(ajaxUrl,object,function(data){
		box.html(data.message);
		if(data.code == 1 )
			location.reload();
	}).fail(function(error){});

}

function majReglage(code){
	var box = createModal('default','Mise à jour de réglage','opération en cours....');
	
	overlay(box);
	//*
	var object = {
		method : 'majReglage',
		code : code
	}
	$.post(ajaxUrl,object,function(data){
		box.html(data.message);
		//if(data.code == 1 )
		//	location.reload();
	}).fail(function(error){});
	//*/
}


function createModal(type, title, message,fixed){
	
	$('.modal-backdrop ').remove();
	$('.modal').remove();
	var classe = 'modal-'+type;
	var modal = $('<div  class="modal  '+classe+'"></div>');
	var dialog = $('<div class="modal-dialog"></div>');
	var content = $('<div class="modal-content"></div>');
	var header = $('<div class="modal-header"></div>');
	var body = $('<div class="modal-body" ></div>');
	var footer = $('<div class="modal-footer"></div>');
	var titleelt = $('<h4 class="modal-title">'+title+'</h4>');
	header.html(titleelt);
	var main = $('<p>'+message+'</p>');
	body.html(main);
	var btn = $('<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>');
	footer.html(btn);
	content.html(header);
	content.append(body);
	content.append(footer);
	dialog.html(content);
	modal.html(dialog);
	$('body').append(modal);
	modal.modal('show');
	if(fixed != undefined){
		modal.unbind('click');
		btn.html('OK');
		btn.click(function(){
			$('.modal').remove();
			location.reload();
		});
	}
	return body;

}

function ajax(object){

	$.post(ajaxUrl,object.controller,function(data){
		window[object.callback](object.callbackparams,data);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
}

function mAffecterDemat(codeDemat){

	$cleprive = prompt("Votre clé privée");

	if($cleprive.length > 0 ){

		var box = createModal("default", "Affectation de la démat "+codeDemat, "Traitement en cours");
		overlay(box);
		var object = {
				method : "mAffecterDemat",
				code : codeDemat,
				cleprive:$cleprive
			};
		
		$.post(ajaxUrl,object,function(data){
			if( data.code != -1 ){
				box.html(data.message)
			}else
				createModal("danger", "Erreur", data.message);

		}).fail(function(error){
			createModal("danger", "Erreur", error.responseText)
				
		});
	}else{
		alert("Veuillez entrer votre clé privée");
	}
}

function commentDemat(event,that){

	event.preventDefault();
	var form = $("#form-comment-demat");
	var val = $(that).val();
	
	if(val.length > 0 && event.keyCode == 13 ){
		overlay($('#box-comments'));

	    formData = new FormData(form[0]);
	    formData.append('method','commentDemat');

	   
	    //*
	    $.ajax({
	        url: ajaxUrl,
	        type: 'POST',
	        data: formData,
	        async: true,
	        success: function (data) {
	           $(that).val("");
	           $('input#fichier').val('');
				$('#box-comments').html(data);
	        },
	        fail: function(error){
	            alert("Erreur comment Démat JS");//error.responseText);
	        },
	        cache: false,
	        contentType: false,
	        processData: false
	    });
	    //*/
	    
		}
	
    return false;
}

function validerReglement(that,idReglement,type){


	/*var body = ;*/

		overlay($(that).parent());
	var object = {
			method : "validerReglement",
			id : idReglement,
			type : type
		};
	
	$.post(ajaxUrl,object,function(data){
		//body.html();
		if(data.code == 1 ){
			$(that).parent().html($(data.btn));
			//createModal('danger','Erreur Serveur',data.message)
		}else
			createModal('danger','Erreur Serveur',data.message)
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});



}

function supprimerAssocie(idAssocie){


	var body = createModal('default',"Suppression d'associé","Suppression en cours de traitement....");

		overlay(body);
	var object = {
			method : "supprimerAssocie",
			id : idAssocie
		};
	
	$.post(ajaxUrl,object,function(data){
		//body.html();
		if(data.code == 1 ){
			body.html(data.message);
			setTimeout(function(){location.reload() },5000);
		}else
			createModal('danger','Erreur Serveur',data.message)
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});



}
function validerPaiement(that,idReglement,type){


		overlay($(that).parent());
	var object = {
			method : "validerPaiement",
			id : idReglement,
			type : type
		};
	
	$.post(ajaxUrl,object,function(data){
		if(data.code == 1 ){
			$(that).parent().html($(data.btn));
		}else
			createModal('danger','Erreur Serveur',data.message)
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});



}

function validerProvision(that,idReglement,type){


		overlay($(that).parent());
	var object = {
			method : "validerProvision",
			id : idReglement,
			type : type
		};
	
	$.post(ajaxUrl,object,function(data){
		if(data.code == 1 ){
			$(that).parent().html($(data.btn));
		}else
			createModal('danger','Erreur Serveur',data.message)
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});



}
function validerTouteEcrituresEcriture(){
	var cleprive = $('#clePrivee').val();
	if(cleprive.length > 0){
	var body = createModal('default','Validation des écritures',"Validation en cours...");
	overlay(body);
	body.append($('<br><em>Cette opération peut prendre du temps; selon le nombre de lignes à exécuter</em>'));
	var object = {
			method : "validerTouteEcrituresEcriture",
			cle : cleprive
		};
	
	$.post(ajaxUrl,object,function(data){
		//body.html();
		if(data.code == 1 ){
			body.html(data.message);
		}else
			createModal('danger','Erreur Serveur',data.message)
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
	}else{
		alert('Entrer votre clé privée');
	}
}
function comptabiliserEcriture(){


	
	var cleprive = $('#clePrivee').val();
	if(cleprive.length > 0){
	var body = createModal('default','Comptabilisation',"Comptabilisation en cours...");
	overlay(body);
	body.append($('<br><em>Cette opération peut prendre du temps; selon le nombre de lignes à exécuter</em>'));
	var object = {
			method : "comptabiliserEcriture",
			cle : cleprive
		};
	
	$.post(ajaxUrl,object,function(data){
		//body.html();
		if(data.code == 1 ){
			body.html(data.message);
		}else
			createModal('danger','Erreur Serveur',data.message)
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
	}else{
		alert('Entrer votre clé privée');
	}
}

function commentAvocat(event,that,codeDemat){

	event.preventDefault();
	//if(event.keyCode == 13 ){
	var val = $(that).find('textarea.input-sm').val();
	//alert(val);
	if($(val).text().length > 0 ){
		overlay($('#box-comments'));
	
	
	var object = {
			method : "commentAvocat",
			comment : val,
			code : codeDemat
		};
	
	$.post(ajaxUrl,object,function(data){
		$(that).find('textarea.input-sm').val("");
		$('#box-comments').html(data);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

	}
	return false;
}

function commentDossier(event,that,codeDemat){

	event.preventDefault();
	//if(event.keyCode == 13 ){
	var val = $(that).find('textarea.input-sm').val();
	if(val.length > 0 ){
		overlay($('#box-comments'));
	
	
	var object = {
			method : "commentDossier",
			comment : val,
			code : codeDemat
		};
	
	$.post(ajaxUrl,object,function(data){
		$(that).find('textarea.input-sm').val("");
		$('#box-comments').html(data);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

	}
	return false;
}
function getCommentDemat(codeDemat){
	var object = {
			method : "getCommentDemat",
			code : codeDemat
		};
	//overlay($('#box-comments'));
	$.post(ajaxUrl,object,function(data){
		$('#box-comments').html(data);
		setTimeout(function(){
			getCommentDemat(codeDemat);
		},60000);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}

function getCommentAvocat(codeDemat){
	var object = {
			method : "getCommentAvocat",
			code : codeDemat
		};
	//overlay($('#box-comments'));
	$.post(ajaxUrl,object,function(data){
		$('#box-comments').html(data);
		setTimeout(function(){
			getCommentAvocat(codeDemat);
		},60000);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}

function getCommentDossier(codeDemat){
	var object = {
			method : "getCommentDossier",
			code : codeDemat
		};
	//overlay($('#box-comments'));
	$.post(ajaxUrl,object,function(data){
		$('#box-comments').html(data);
		setTimeout(function(){
			getCommentDossier(codeDemat);
		},60000);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}

function getDematTrace(codeDemat){

	var box = createModal("default", "Chargement de trace démat "+codeDemat, "Chargement en cours");
	var object = {
			method : "getDematTrace",
			code : codeDemat
		};
	overlay(box);
	$.post(ajaxUrl,object,function(data){
		box.html(data);
		/*
		setTimeout(function(){
			getDematTrace(codeDemat);
		},60000);
		//*/
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}


function likeComite(that,idComite){

	overlay($('#stats-like-comites'));
	var object = {
		controller : {
			method : "likeComite",
			id : idComite
		},
		callback :'actualiserLike',
		callbackparams : {
			id: idComite,
			btn : $(that),
			hide : true
		}
	};
	ajax(object);
}
function likePoint(that,idComite){

	overlay($('#stats-like-comites'));
	var object = {
		controller : {
			method : "likePoint",
			id : idComite
		},
		callback :'actualiserLike',
		callbackparams : {
			id: idComite,
			btn : $(that),
			hide : true
		}
	};
	ajax(object);
}
function actualiserNoti(){
	var rubriques = ['mesNotif','mesSMS','onLine'];
	//var rubriques = ['onLine'];
	for (var i = 0; i < rubriques.length; i++) {
		var t = rubriques[i];
		refreshNotif(t);
	}
}
function readNotification(that,idNotification){

	var object = {
		method : 'readNotification',
		id : idNotification
	}
	var body = createModal("default","Lecture de notification","Chargement en cours");
	overlay(body);
	$.post(ajaxUrl,object,function(data){
		body.html(data);
		refreshNotif('mesNotif');
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}

function supprimerMesNotifications(){

	var object = {
		method : 'supprimerMesNotifications'
	}
	var body = createModal("default","Suppression de notification","Suppression en cours");
	overlay(body);
	$.post(ajaxUrl,object,function(data){
		body.html(data.message);
		//refreshNotif('mesNotif');
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}

function desActiveParametre(that,idParameter){

	var object = {
		method : 'desActiveParametre',
		id : idParameter
	}
	var body = createModal("default","DesActivation de paramètre","Traitement en cours..");
	overlay(body);
	$.post(ajaxUrl,object,function(data){
		body.html(data.message);
		$(that).removeClass('btn-danger btn-success').addClass(data.etat);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}
function desActiveScenario(that,idParameter){

	var object = {
		method : 'desActiveScenario',
		id : idParameter
	}
	var body = createModal("default","DesActivation de scénario","Traitement en cours..");
	overlay(body);
	$.post(ajaxUrl,object,function(data){
		body.html(data.message);
		$(that).removeClass('btn-danger btn-success').addClass(data.etat);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}
function desActiveDecision(that,idParameter){

	var object = {
		method : 'desActiveDecision',
		id : idParameter
	}
	var body = createModal("default","DesActivation de décision","Traitement en cours..");
	overlay(body);
	$.post(ajaxUrl,object,function(data){
		body.html(data.message);
		$(that).removeClass('btn-danger btn-success').addClass(data.etat);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}
function desActiveDocument(that,idParameter){

	var object = {
		method : 'desActiveDocument',
		id : idParameter
	}
	var body = createModal("default","DesActivation de document","Traitement en cours..");
	overlay(body);
	$.post(ajaxUrl,object,function(data){
		body.html(data.message);
		$(that).removeClass('btn-danger btn-success').addClass(data.etat);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}
function desActiveObjet(that,idParameter){

	var object = {
		method : 'desActiveObjet',
		id : idParameter
	}
	var body = createModal("default","DesActivation d'objet","Traitement en cours..");
	overlay(body);
	$.post(ajaxUrl,object,function(data){
		body.html(data.message);
		$(that).removeClass('btn-danger btn-success').addClass(data.etat);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}

function desActiveInstance(that,idParameter){

	var object = {
		method : 'desActiveInstance',
		id : idParameter
	}
	var body = createModal("default","DesActivation d'Instance","Traitement en cours..");
	overlay(body);
	$.post(ajaxUrl,object,function(data){
		body.html(data.message);
		$(that).removeClass('btn-danger btn-success').addClass(data.etat);
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});

}
function signatureValidation(event,that){

    event.preventDefault();
    var form = $(that);
    formData = new FormData(form[0]);
    formData.append('method','signature');
   // var url = form.attr('url');
    
	var extensions = ['jpg','png'];
	var t = form.find('input[type=file]').val().split('.');
	if(t.length > 0 && jQuery.inArray(t[t.length - 1].toLowerCase(),extensions) >= 0){
	var body = createModal("default","Ajout de signature","Traitement en cours");
	overlay(body);
    //*
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: formData,
        async: true,
        success: function (data) {
            //console.log(data);
            //*
            overlayStop();
            if(data.code != -1 ){
                body.html(data.message);
				//if(data.code == 2 )
				//	location.href = data.lien;
            }
            else{

                createModal("danger", "Chargement de fichier", data.message)
            }
            //*/
        },
        fail: function(error){
            alert("Erreur signature JS");//error.responseText);
        },
        cache: false,
        contentType: false,
        processData: false
    });
    //*/
    }else{
		alert("Erreur de ficher");
	}
    return false;
}

function modifierMotPasse(event,that){

    event.preventDefault();
    var form = $(that);
    formData = new FormData(form[0]);
    formData.append('method','modifierMotPasse');
   // var url = form.attr('url');
    var body = createModal("default","Modification de mot de passe","Traitement en cours");
	overlay(body);
    //*
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: formData,
        async: true,
        success: function (data) {
            //console.log(data);
            overlayStop();
            if(data.code != -1 ){
                body.html(data.message);
				if(data.code == 2 )
					location.href = data.lien;
            }
            else{

                createModal("danger", "Chargement de fichier", data.message)
            }
        },
        fail: function(error){
            alert("Erreur SendFile JS");//error.responseText);
        },
        cache: false,
        contentType: false,
        processData: false
    });
    //*/
    return false;
}
function rendreIndisponible(event,that){

	event.preventDefault();
    var form = $(that);
    formData = new FormData(form[0]);
    formData.append('method','rendreIndisponible');
   // var url = form.attr('url');
    var body = createModal("default","Modification de ma disponibilité","Traitement en cours");
	overlay(body);
    //*
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: formData,
        async: true,
        success: function (data) {
            //console.log(data);
            overlayStop();
            if(data.code != -1 ){
                body.html(data.message);
				if(data.code == 2 )
					location.reload();
            }
            else{

                createModal("danger", "Chargement de fichier", data.message)
            }
        },
        fail: function(error){
            alert("Erreur SendFile JS");//error.responseText);
        },
        cache: false,
        contentType: false,
        processData: false
    });
    //*/
    return false;
}

function refreshNotif($rubrique){
	var object = {
		method : 'mesNotification',
		rubrique : $rubrique
	}
	$.post(ajaxUrl,object,function(data){
		if($rubrique != 'onLine')
			$('#'+$rubrique).html(data);
		
	}).fail(function(error){
		////createModal("danger", "Erreur", error.responseText)
			
	});
}
function boxExists(send){
	var temp = false ;
	$.each($('.direct-chat'),function(index,val){
		var t = $(val)
		//console.log(t);
		//console.log(parseInt(t.attr('send')) == parseInt(send))
		if(parseInt(t.attr('send')) == parseInt(send)){
			
			temp = true;
			
		}
	});
	return temp;
}
function removeBox(that){
	var b = $(that).parent().parent().parent();
	//console.log($(b).offset().left);
	var find = false;
	$.each($('.direct-chat'),function(index,val){
		var temp = $(val);
		if(find){
			temp.css('left',parseInt(temp.offset().left - 310)+'px');
		}

		if(temp.attr('send') == b.attr('send')){
			b.remove();
			find = true;
			clearInterval(timers[temp.attr('send')]);
		}


	});
}
function readSMS(that){

	var a = $(that);
	var idSend = a.attr('send');
	var verdict = boxExists(idSend);
	if(!verdict){
		var object = {
			method : 'readSMS',
			id : idSend
		}
		//var body = createModal("default","Lecture de message","Chargement en cours...");
		//overlay(body);
		var nbre = $('.direct-chat').length ;
		if(nbre < 4 ){
			$.post(ajaxUrl,object,function(data){
				
				
				var nbre = $('.direct-chat').length ;
				if(nbre < 4 ){
					nbre = nbre * 310;
					$('.modal-backdrop ').remove();
					$('.modal').remove();
					var box = $(data);
					box.css('left',nbre+'px');
					
					//console.log(bb);
					$('body').append(box);
					var bb = box.find('.box-body .direct-chat-messages:first'); 
					bb[0].scrollTop = bb[0].scrollHeight
					maintainContact(idSend,bb);
				}else{
					body.html('<h4>Veuillez fermer des fenêtre</h4>');
				}
				
				refreshNotif('mesSMS');
			}).fail(function(error){
				//createModal("danger", "Erreur", error.responseText)
					
			});
		}else{
			body.html('<h4>Veuillez fermer des fenêtres</h4>');
		}
	}
}
function maintainContact(id,box){
	var time = setInterval(function(){
		refreshBox(id,box)
	},5000);
	timers[id] = time;
}
function refreshBox (id,box){
	var object = {
		method : 'refreshBox',
		id : id
	}
	$.post(ajaxUrl,object,function(data){
			box.html(data);
			box[0].scrollTop = box[0].scrollHeight
		}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
}
function sendSMSInput(that,event){

	if(event.keyCode == 13){
		var sms = $(that).val();
		var send = $(that).attr('send');
		var box = $(that).parent().parent().parent().find('.box-body .direct-chat-messages:first');
		//box.append('<p>'+sms+" :"+ send+'</p>');
		box[0].scrollTop = box[0].scrollHeight
		overlay($(that).parent().parent().parent());
		
		sendSMSToServer(send,sms,box,$(that));
	}
}
function sendSMSToServer(send,sms,box,input){

		var object = {
			method : 'sendSMS',
			id : send,
			sms : sms
		};
		$.post(ajaxUrl,object,function(data){
			box.html(data);
			box[0].scrollTop = box[0].scrollHeight
			overlayStop();
			if(input != null)
				input.val('');
		}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
}
function debuterPoint(id){
	var body = createModal('default','Debuter un point','Activation en cours....');
	overlay(body);
	var object = {
		id:id,
		method : 'debuterPoint'
	}
	$.post(ajaxUrl,object,function(data){
		body.html(data);
		setTimeout(function(){

			location.reload();
		},5000)
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
}

function supprimerMembreComite(idMembre){
	var body = createModal('default','Retrait de membre de comité','Suppression en cours....');
	overlay(body);
	var object = {
		id:idMembre,
		method : 'supprimerMembreComite'
	}
	$.post(ajaxUrl,object,function(data){
		body.html(data);
		setTimeout(function(){

			location.reload();
		},5000)
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
}

function cadredemandeProrogation(idPoint){
	var parent = $('<div class="row"></div>');
	var div = $('<div class="col-md-12"></div>');
	var textarea = $('<textarea placeholder="Saisir le message ici" class="form-control" rows="7" id="textarea'+idPoint+'"></textarea>');
	var btn = $('<button class="btn pull-right btn-xs btn-danger">Envoyer ma demande</button>');
	div.html(textarea);
	div.append($('<br>'));
	div.append(btn);
	parent.html(div)
	var body = createModal('default','Demande de prorogation','');
	body.html(parent);
	
	btn.click({id:idPoint},demandeProrogation);
}
function demandeProrogation(param){
	var textarea = $('#textarea'+param.data.id);
	text = textarea.val();
	
	if(text.length > 0 ){
		//*
		var body = createModal('default','Demande de prorogation','Traitement de la demande en cours....');
			overlay(body);
			var object = {
				id:param.data.id,
				method : 'demandeProrogation',
				msg: text
			}
			$.post(ajaxUrl,object,function(data){
				body.html(data);
				/*
				setTimeout(function(){

					location.reload();
				},5000)
				//*/
			}).fail(function(error){
				//createModal("danger", "Erreur", error.responseText)
					
			});
			//*/
	}else{
		
		createModal('danger','Demande de prorogation',"Veuillez saisir un texte qui sera envoyé au sécrétariat du comité. Dans lequel vous pouvez spécifier les raisons de cette demande ainsi faire une proposition de nouvelle date" );
	}

	/*
	
	//*/
}


function cloturePoint(id){
	var body = createModal('default','Clôture un point','Clôture en cours....');
	overlay(body);
	var object = {
		id:id,
		method : 'cloturePoint'
	}
	$.post(ajaxUrl,object,function(data){
		body.html(data);
		setTimeout(function(){
			location.reload();
		},5000)
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
}
function confirmerCloturerReunion(idReunion){
	var body = createModal('default','Confirmation de Clôture de réunion','Clôture en cours....');
	overlay(body);
	var object = {
		id:idReunion,
		method : 'confirmerCloturerReunion'
	}
	$.post(ajaxUrl,object,function(data){
		if(data.trim() == 'terminer')
			location.reload();
		else
			createModal("danger", "Erreur", data)
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
}
function cloturerReunion(idReunion){

	
	var body = createModal('default','Clôture de réunion','Traitement en cours....');
	overlay(body);
	var object = {
		id:idReunion,
		method : 'cloturerReunion'
	}
	$.post(ajaxUrl,object,function(data){
		body.html(data);
		
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
}

function likeReunion(that,idComite){
	overlay($('#stats-like-comites'));
	var object = {
		controller : {
			method : "likeReunion",
			id : idComite
		},
		callback :'actualiserLike',
		callbackparams : {
			id: idComite,
			btn : $(that),
			hide : true
		}
	};
	ajax(object);
}

function commentReunion(event,that,idComite){

	
	//
	
	if(event.keyCode == 13 ){
		var val = $(that).val();
		overlay($('#stats-like-comites'));
	
	var object = {
		controller : {
			method : "commentReunion",
			comment : val,
			id : idComite
		},
		callback :'actualiserLike',
		callbackparams : {
			id: idComite,
			btn : $(that),
			hide : false
		}
	};
	ajax(object);
	}
}
function commentPoint(event,that,idPoint){

	
	//
	
	if(event.keyCode == 13 ){
		var val = $(that).val();
		overlay($('#stats-like-comites'));
	
	var object = {
		controller : {
			method : "commentPoint",
			comment : val,
			id : idPoint
		},
		callback :'actualiserLike',
		callbackparams : {
			id: idPoint,
			btn : $(that),
			hide : false
		}
	};
	ajax(object);
	}
}

function deleteComment(idComment){
	
		overlay($('#stats-like-comites'));
	
	var object = {
		controller : {
			method : "deleteComment",
			id : idComment
		},
		callback :'actualiserLike',
		callbackparams : {
			id: idComment,
			btn : null,
			hide : false
		}
	};
	ajax(object);
}
function actualiserLike(params,data){
			$('#stats-like-comites').html(data)
			
			if(!params.hide)
				$(params.btn).val("");
			overlayStop()
}

function getFicheSuivie(object,id) {
	var body = createModal('default','Génération de fiche de suivi','Traitement en cours....');
	overlay(body);
	var object = {
		id:id,
		object: object,
		method : 'getFicheSuivie'
	}
	$.post(ajaxUrl,object,function(data){
		body.html(data.message)
		//console.log(data);
		if(data.code == 1 ){
			var a = $('<div class="text-center"><a target="_blank" href="'+data.lien+'">Télécharger</a></div>');
			body.html(a);
		}
		
	}).fail(function(error){
		//createModal("danger", "Erreur", error.responseText)
			
	});
}

function modificationClePrive(event,that){

    event.preventDefault();
    var form = $(that);
    formData = new FormData(form[0]);
    //formData.append('method','sendFile');
    var url = form.attr('url');
    overlay(form);
    //*
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        async: true,
        success: function (data) {
            //console.log(data);
            overlayStop();
            if(data.code != -1 ){
                form.find('#message').html(data.message);
            }
            else{

                createModal("danger", "Chargement de fichier", data.message)
            }
        },
        fail: function(error){
            alert("Erreur SendFile JS");//error.responseText);
        },
        cache: false,
        contentType: false,
        processData: false
    });
    //*/
    return false;
}

function activerCompteConnexion(idCompte){

	var box  = createModal("defaul", "Paramétrage de compte de connexion",
		"Traitement en cours...");

	
	
	var object = {
			method : "activerCompteConnexion",
			id : idCompte
		};
	//*
	$.post(ajaxUrl,object,function(data){
		box.html(data.message)
	}).fail(function(error){		
	});

	//*/
}

function reinitialiserMotDePasse(idCompte){

	var box  = createModal("defaul", "Réinitialisation de mot de passe",
		"Traitement en cours...");

	
	
	var object = {
			method : "reinitialiserMotDePasse",
			id : idCompte
		};
	//*
	$.post(ajaxUrl,object,function(data){
		box.html(data.message)
	}).fail(function(error){		
	});

	//*/
}

function meRendreDisponible(idCompte){

	var box  = createModal("defaul", "Rendre disponible",
		"Traitement en cours...");

	
	
	var object = {
			method : "meRendreDisponible",
			id : idCompte
		};
	//*
	$.post(ajaxUrl,object,function(data){
		box.html(data.message)
	}).fail(function(error){		
	});

	//*/
}

function supprimerDocumentDemat(idDocument,codeDemat){

	var box  = createModal("default", "Suppression de document",
		"Suppression en cours...");

	
	
	var object = {
			method : "supprimerDocumentDemat",
			id : idDocument,
			code : codeDemat
		};
	//*
	$.post(ajaxUrl,object,function(data){
		box.html(data.message)

		if(data.code == 1)
			location.reload();
	}).fail(function(error){		
	});

	//*/
}

function supprimerCondition(idCondition){

	var box  = createModal("default", "Suppression de condition",
		"Suppression en cours...");

	
	
	var object = {
			method : "supprimerCondition",
			id : idCondition
		};
	//*
	$.post(ajaxUrl,object,function(data){
		box.html(data.message)

		if(data.code == 1)
			location.reload();
	}).fail(function(error){		
	});

	//*/
}
