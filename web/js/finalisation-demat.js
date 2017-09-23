$(document).ready(function(){
	//alert('function')
});

function statuerDemat(event,that){

    event.preventDefault();
    var form = $(that);
    formData = new FormData(form[0]);
    formData.append('method','statuerDemat');
    form.find('#message').html("Traitement en cours ...");
    overlay(form);
    //*
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: formData,
        async: true,
        success: function (data) {
            //console.log(data);
            if(data.code != -1 ){
                overlayStop();
                form.find('#message').html(data.message);
                setTimeout(function(){
                    location.href = data.lien;
                },5000);
            }
            else{
                 overlayStop();
                form.find('#message').html(data.message);
                createModal("danger", "Validation de décision", data.message)
            }
        },
        fail: function(error){
             overlayStop();
            alert("Erreur validerDecisionDemat JS");//error.responseText);
        },
        cache: false,
        contentType: false,
        processData: false
    });
    //*/
    return false;
}

function validerDecisionDemat(event,that){

    event.preventDefault();
    var form = $(that);
    formData = new FormData(form[0]);
    formData.append('method','validerDecisionDemat');
    form.find('#message').html("Traitement en cours ...");
    var body = createModal("default", "Validation de décision", "Traitement en cours ...",'fixed');
    overlay(body);
    //*
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: formData,
        async: true,
        success: function (data) {
            //console.log(data);
            if(data.code != -1 ){
                overlayStop();
                body.html(data.message);
                /*
                setTimeout(function(){
                    location.reload();
                },30000);
                //*/
            }
            else{
                 overlayStop();
                form.find('#message').html(data.message);
                createModal("danger", "Validation de décision", data.message)
            }
        },
        fail: function(error){
             overlayStop();
            alert("Erreur validerDecisionDemat JS");//error.responseText);
        },
        cache: false,
        contentType: false,
        processData: false
    });
    //*/
    return false;
}


function sendFile(event,that){

	event.preventDefault();
	var form = $(that);
	formData = new FormData(form[0]);
	formData.append('method','sendFile');
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
            if(data.code != -1 ){
                overlayStop();
                form.find('.lien').html(data.lien);
                form.find('input.clean').val("");
            }
            else
                createModal("danger", "Chargement de fichier", data.message)
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

function submitDematParameter(event,that){

    event.preventDefault();
    var form = $(that);
    formData = new FormData(form[0]);
    formData.append('method','submitDematParameter');
    var url = form.attr('url');
    overlay(form);
    console.log(formData);
    //*
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        async: true,
        success: function (data) {
            overlayStop();
            form.find('.message-block').html(data.message);
            /*
            if(data.code != -1 ){
                
                form.hide();
            }
            else
                createModal("danger", "Chargement de fichier", data.message)
            //*/
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