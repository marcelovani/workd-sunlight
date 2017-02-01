var gui;
var guiData = {};
var guiDataChanged = false;

function initGui() {
	if (typeof(camera) == 'undefined') return;

	var object = {
		separation: camera.separation,
		rotate: camera.rotate,
		fov: camera.fov,
		near: camera.near,
		far: camera.far,
		focus: camera.focus,
		zoom: camera.zoom
	};
	gui = new dat.gui.GUI();
	gui.remember( object );
	//gui.remember( object.separation );
	gui.add( object, 'separation', -2.0, 2.0 ).onFinishChange( function ( v ) {updateGuiData( this, v )} );
	gui.add( object, 'rotate', -0.06, 0.06 ).onFinishChange( function ( v ) {updateGuiData( this, v )} );
	gui.add( object, 'fov', 0, 150 ).onFinishChange( function ( v ) {updateGuiData( this, v )} );
	gui.add( object, 'near', -10, 10 ).onFinishChange( function ( v ) {updateGuiData( this, v )} );
	gui.add( object, 'far', -8000, 8000 ).onFinishChange( function ( v ) {updateGuiData( this, v )} );
	gui.add( object, 'focus', -20, 20 ).onFinishChange( function ( v ) {updateGuiData( this, v )} );
	gui.add( object, 'zoom', -10, 10 ).onFinishChange( function ( v ) {updateGuiData( this, v )} );
}

function updateObjectProperties() {
	if ( typeof(gui) == 'object' && Object.keys( guiData ).length > 0 ) {
		for ( var i = 0; i < Object.keys(guiData).length; i ++ ) {
			var k = Object.keys(guiData)[i];
			var v = guiData[k];
			camera[k] = v;
			delete(guiData[k]);
		}
//		camera.separation = guiData.separation;
//		rotate = guiData.rotate;
//		camera.fov = guiData.fov;
//		delete(guiData['separation']);
//		delete(guiData['rotate']);
//		delete(guiData['fov']);
	}
}

function updateGui() {
	if ( typeof(gui) != 'object' ) {
		initGui();
	}

	// Do properties
	if ( guiDataChanged ) {
		jQuery.each( gui.__controllers, function ( i, controller ) {
			var property = controller.property;
			console.log( 'update gui' );

			if ( typeof(guiData[property]) != 'undefined' ) {
				if ( controller.object[property] != guiData[property] ) {
					controller.setValue( guiData[property] );
				}
			}
		} );
		guiDataChanged = false;
	}
}

function updateGuiDataItem( folder, property, value ) {
	console.log( 'guiDataQueue' );
	var camData = {};
	if (typeof(cameraLeft) != 'undefined') {
		camData.l = {
			position: {
				x: cameraLeft.position.x,
				y: cameraLeft.position.y,
				z: cameraLeft.position.z
			}
		}
	}
	if (typeof(cameraRight) != 'undefined') {
		camData.r = {
			position: {
				x: cameraRight.position.x,
				y: cameraRight.position.y,
				z: cameraRight.position.z
			}
		}
	}
	camData[property] = value;

	if ( typeof(socket) != 'undefined' && socket.connected ) {
		// Socket will update when it gets the message.
		socket.emit( 'camera', camData );
	}
	else {
		// No socket, update now.
		guiDataChanged = true;
		for ( var i = 0; i < Object.keys(camData).length; i ++ ) {
			var k = Object.keys(camData)[i];
			var v = camData[k];
			guiData[k] = v;
		}

//		guiData.separation = camera.separation;
//		guiData.rotate = camera.rotate;
//		guiData.fov = camera.fov;
	}
}

function updateGuiData( change, value ) {
	var folder = null;
	updateGuiDataItem( folder, change.property, value );
}
