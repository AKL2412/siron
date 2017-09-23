function updateImage(evt){
	var exts = ['jpg','png','jpeg'];
	var url = $(this).val();
		var tab = url.split('.');
		var extension = tab[tab.length -1 ];
		if($.inArray(extension.toLowerCase(),exts) != -1 ){
			var image = $('#img-update');
			if(image.length > 0 ){
				overlay(image.parent().append());
				    var tgt = evt.target || window.event.srcElement,
				        files = tgt.files;

				    // FileReader support
				    if (FileReader && files && files.length) {
				        var fr = new FileReader();
				        fr.onload = function () {
				            //document.getElementById(outImage).src = fr.result;

				           image.attr('src',fr.result);
				           overlayStop()
				           
				        }
				        fr.readAsDataURL(files[0]);
				    }

				    // Not supported
				    else {
				    	alert('Fichier non supporté');
				    }
				  }
			
		}else{
			
			createModal("danger", "extension de fichier", "Vueillez selectionner un ficher d'extension suivante : "+exts)
			$(this).val('');
		}
}

function updateExcel(evt,that){
	var exts = ['xls','xlsx'];
	var url = $(that).val();
		var tab = url.split('.');
		var extension = tab[tab.length -1 ];
		if($.inArray(extension.toLowerCase(),exts) == -1 ){
			createModal("danger", "extension de fichier", "Vueillez selectionner un ficher d'extension suivante : "+exts)
			$(that).val('');
			
		}
}

function importExcel(){

	var body = createModal('default','Importation de données','Traitement de données en cours...');
	overlay(body);
}