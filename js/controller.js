//var galleryImages;
var cameraView=false;
var galleryView=false;

function takePicture(){
	$.mobile.loading( 'show', {
		text: 'Taking Image....',
		textVisible: true,
		theme: 'a'
	});
         
	$.ajax({
		url: "service.php?action=takePicture",
		dataType : "json",
		success: function(data){
			$.mobile.loading( 'hide');
		},
	});
}

function startTether(){
	$.mobile.loading( 'show', {
		text: 'Starting tether....',
		textVisible: true,
		theme: 'a'
	});         	
	$.ajax({
		url: "service.php?action=startTether",
		dataType : "json",
		success: function(data){
			$.mobile.loading( 'hide');
		},
	});
}

function stopTether(){
	$.mobile.loading( 'show', {
		text: 'Stop tether....',
		textVisible: true,
		theme: 'a'
	});         	
	$.ajax({
		url: "service.php?action=stopTether",
		dataType : "json",
		success: function(data){
			$.mobile.loading( 'hide');
		},
	});
}

function checkCameraStatus() {
	if (cameraView) {
		getCamera();
		setTimeout(checkCameraStatus, 10000);
	} 
}

function checkTetherStatus() {
	if (cameraView) {
		setTimeout(checkTetherStatus, 5000);
		$.ajax({
			url: "service.php?action=checkTetherStatus",
			dataType : "json",
			success: function(data){
				$("#tetherStatus").html(data.status);
			},
		});
	}
}

function updateGalleryGrid(data){
	//$("#galleryGrid").html("");
	var galleryHTML = "";	
	for(var i = 0; i < data.length; i++){
		var uiClass = "a";
		if (i % 2 == 1){
			uiClass = "b";
		} 

		var image = data[i];
		var id = image.name.replace(/[-\.]/g,'');

		if ($('#' + id).length	> 0){
			$('#' + id).removeClass("ui-block-a");
			$('#' + id).removeClass("ui-block-b");
			$('#' + id).addClass("ui-block-" + uiClass);					
		}else{
			var galleryTemplate = $("#galleryTemplate").text();
			galleryTemplate = galleryTemplate.replace(/@imageThumb/g, image.thumbPath);
			galleryTemplate = galleryTemplate.replace(/@imageLarge/g, image.largePath);
			galleryTemplate = galleryTemplate.replace(/@char/g, uiClass);
			galleryTemplate = galleryTemplate.replace(/@sourceURL/g, image.sourcePath);
			galleryTemplate = galleryTemplate.replace(/@imageName/g, image.name);	
			galleryTemplate = galleryTemplate.replace(/@id/g, id);	
			$("#galleryGrid").append(galleryTemplate);
		}
	}
}

function checkTetherTransfer() {
	if (galleryView) {
		$.ajax({
			url: "service.php?action=getImages",
			dataType : "json",
			success: function(data){
				updateGalleryGrid(data);
			},
		});
		setTimeout(checkTetherTransfer, 10000);
	}
}

function deleteFile(file){
	var id = file.replace(/[-\.]/g,'');
	$('#' + id).remove();

	$.ajax({
		url: "service.php?action=deleteFile&file=" + file,
		dataType : "json",
		success: function(data){		
			$.ajax({
				url: "service.php?action=getImages",
				dataType : "json",
				success: function(data){
					updateGalleryGrid(data);
				},
			});					
		},
	});
}

function getCamera(){
	$.ajax({
		url: "service.php?action=getCamera",
		dataType : "json",
		success: function(data){
			$("#cameraInfo").html(data.camera + ' | SN:' + data.serialNumber + ' | Bat:' + data.batteryLevel);
		},
	});
}

function getCameras(){
	$.ajax({
		url: "service.php?action=getCameras",
		dataType : "json",
		success: function(data){
			$("#cameraName").html(data.cameras);
		},
	});
}

function getSerialNumber(){
	$.ajax({
		url: "service.php?action=getSerialNumber",
		dataType : "json",
		success: function(data){
			$("#serialNumber").html(data.serialNumber);
		},
	});
}

function getBatteryLevel(){
	$.ajax({
		url: "service.php?action=getBatteryLevel",
		dataType : "json",
		success: function(data){
			$("#batteryLevel").html(data.batteryLevel);
		},
	});
};

$(document).on( "pageshow","#gallery", function( event ) {
	galleryView = true;
	checkTetherTransfer();
});

$(document).on( "pagehide","#gallery", function( event ) {
	galleryView = false;
});

$(document).on( "pageshow","#camera", function( event ) {
	cameraView = true;
	checkCameraStatus();
	checkTetherStatus();
});

$(document).on( "pagehide","#camera", function( event ) {
	cameraView = false;
});

$(document).on( 'pageinit',function(event){
	//checkCameraStatus();
	//checkTetherStatus();
});
