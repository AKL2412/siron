function selectService(that){
	var select = $(that);
	idService = parseInt(select.val());
	scenario = parseInt(select.attr('scenario'));
	demat = select.attr('demat');
	var utils = $('#select-utilisateur');
	
	utils.html($('<option value>Sélectionner un utilisateur</option>'));
	if( !isNaN(idService)){
		overlay(utils.parent());
			var object = {
				service:idService,
				scenario : scenario,
				demat : demat,
				method : 'getPosteForServiceByScenario'
			}
			
			$.post(ajaxUrl,object,function(data){
				
				overlayStop();
				if(data.code != -1 ){
					if(data.postes.length > 0){
						for (var i = 0; i < data.postes.length; i++) {
							var temp = data.postes[i];
						    var nom = temp.nom
							if(temp.decision)
								nom = nom + " (D)";
							var option = $('<option value="'+temp.id+'">'+nom+'</option>');
							utils.append(option);

						}
					}else{
						createModal("danger", "Erreur", "Pas d'utilisateurs")
					}
					//
				}else{
					createModal("danger", "Erreur", data.message)
				}
				
			}).fail(function(error){
				////createModal("danger", "Erreur", error.responseText)
					
			});

	}
	
}