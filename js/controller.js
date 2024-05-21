//var galleryImages;
var cameraView = false;
var galleryView = false;

function takePicture(cameraid){
	$.mobile.loading( 'show', {
		text: 'Taking Image....',
		textVisible: true,
		theme: 'a'
	});
         
	$.ajax({
		url: "service.php",
		data:{ 
			action: "takePicture", 
			port: cameraid
		},
		dataType : "json",
		success: function(data){
			$.mobile.loading( 'hide');
		},
	});
}

function startTether(cameraid){
	$.mobile.loading( 'show', {
		text: 'Starting tether....',
		textVisible: true,
		theme: 'a'
	});         	
	$.ajax({
		url: "service.php",
		data:{ 
			action: "startTether", 
			port: cameraid
		},
		dataType : "json",
		success: function(data){
			$.mobile.loading( 'hide');
		},
	});
}

function stopTether(cameraid){
	$.mobile.loading( 'show', {
		text: 'Stop tether....',
		textVisible: true,
		theme: 'a'
	});         	
	$.ajax({
		url: "service.php",
		data:{ 
			action: "stopTether", 
			port: cameraid
		},
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

function checkTetherStatus(cameraid=-1) {
	if (cameraView) {
		setTimeout(checkTetherStatus, 5000);
		$.ajax({
			url: "service.php",
			data:{ 
				action: "checkTetherStatus",
				port: cameraid
			},	
			dataType : "json",
			success: function(data){
				//$("#tetherStatus").html(data[0].status);
				for(var i = 0; i < data.length; i++){
					id = data[i].port.replace(/[-\.\:\,]/g,'');
					$("#tetherStatus-"+id).html("Tether Status: "+data[i].status);
				}				
			},
		});
	}
}

function updateGalleryGrid(data){
	//$("#galleryGrid").html("");
	var galleryHTML = "";	
	for(var i = 0; i < data.length; i++){
		var uiClass = (i % 2 == 1) ? "b" : "a";
		var image = data[i];
		var id = image.name.replace(/[-\.]/g,'');

		if ($('#' + id).length	> 0){
			$('#' + id).removeClass("ui-block-a");
			$('#' + id).removeClass("ui-block-b");
			$('#' + id).addClass("ui-block-" + uiClass);					
		}else{
			var galleryTemplate = $("#galleryTemplate").text();
			galleryTemplate = galleryTemplate.replace(/@imageTotal/g, data.length);
			galleryTemplate = galleryTemplate.replace(/@imageNumber/g, i + 1);
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

function updateCameraGrid(data){
//	$("#cameraGrid").html("");
	var cameraHTML = "";	
	// mark for deletion
	elements = document.querySelectorAll(`div[id^="camera-"]`);		
	for (var i = 0; i < elements.length; i++){		
		elements[i].classList.add("camera-removed");
	}		
	// unmark existing
	for(var i = 0; i < data.length; i++){
		var camera = data[i];
		var id = camera.port.replace(/[-\.\:\,]/g,'');

		if ($('#camera-' + id).length	> 0){			
			$('#camera-' + id).removeClass("camera-removed");
		}
	}	
	// delete removed cameras
	elements = document.getElementsByClassName("camera-removed");	
	for (var i = 0; i < elements.length; i++){
		elements[i].parentNode.removeChild(elements[i]);
	} 
	// add new cameras
	for(var i = 0; i < data.length; i++){
		var camera = data[i];
		var id = camera.port.replace(/[-\.\:\,]/g,'');

		if ($('#camera-' + id).length > 0){
				// ignore
		} else {
			var cameraTemplate = $("#cameraTemplate").text();
			cameraTemplate = cameraTemplate.replace(/@cameraPort/g, camera.port);
			cameraTemplate = cameraTemplate.replace(/@id/g, id);
			cameraTemplate = cameraTemplate.replace(/@cameraName/g, camera.camera);
			cameraTemplate = cameraTemplate.replace(/@cameraSerialNumber/g, camera.serialNumber);
			cameraTemplate = cameraTemplate.replace(/@cameraBatteryLevel/g, camera.batteryLevel);
			cameraTemplate = cameraTemplate.replace(/@cameraShutterCount/g, camera.shutterCount);
			$("#cameraGrid").append(cameraTemplate);
		}
	}
}

function checkTetherTransfer() {
	if (galleryView) {
		$.ajax({
			url: "service.php",
			data:{ 
				action: "getImages"
			},			
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
		url: "service.php",
		data:{ 
			action: "deleteFile",
			file: file
		},
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
		url: "service.php",
		data:{ 
			action: "getCamera"
		},
		dataType : "json",
		success: function(data){
			updateCameraGrid(data);		
		},
	});
}

function getCameras(){
	$.ajax({
		url: "service.php",
		data:{ 
			action: "getCameras"
		},
		dataType : "json",
		success: function(data){
			$("#cameraName").html(data.cameras);
		},
	});
}

function getSerialNumber(cameraid){
	$.ajax({
		url: "service.php",
		data:{ 
			action: "getSerialNumber", 
			port: cameraid
		},
		dataType : "json",
		success: function(data){
			$("#serialNumber").html(data.serialNumber);
		},
	});
}

function getBatteryLevel(cameraid){
	$.ajax({
		url: "service.php",
		data:{ 
			action: "getBatteryLevel", 
			port: cameraid
		},
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
