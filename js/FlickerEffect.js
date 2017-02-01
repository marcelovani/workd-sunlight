/**
 * @author marcelovani / http://marcelovani.eu/
*/

FlickerEffect = function ( renderer ) {

	var flick = 0;
	var granularity = 8;
	var offset = 5;

	var flickerCamera = new THREE.StereoCamera();
	flickerCamera.aspect = 0.5;
	flickerCamera.cameraL.name = 'L';
	flickerCamera.cameraR.name = 'R';

	this.setEyeSeparation = function ( eyeSep ) {

		flickerCamera.eyeSep = eyeSep;

	};

	this.setSize = function ( width, height ) {

		renderer.setSize( width, height );

	};

	this.render = function ( scene, camera ) {

		scene.updateMatrixWorld();

		if ( camera.parent === null ) camera.updateMatrixWorld();

		flickerCamera.update( camera );

		var size = renderer.getSize();

		if ( renderer.autoClear ) renderer.clear();
		renderer.setScissorTest( true );

	  //

		if (flick % granularity == 0) {
			renderer.setScissor( 0, 0, size.width / 2, size.height );
			renderer.setViewport( 0, 0, size.width / 2, size.height );
		}
		else if (flick % (granularity + offset) == 0) {
			renderer.setScissor( - size.width / 2, 0, size.width / 2, size.height );
			renderer.setViewport( - size.width / 2, 0, size.width / 2, size.height );
		}

		flickerCamera.cameraL.updateProjectionMatrix();
		flickerCamera.cameraL.position.set( this.eyeSep, 0, 3 );

		renderer.render( scene, flickerCamera.cameraL );

		//

		if (flick % granularity == 0) {
			renderer.setScissor( - size.width / 2, 0, size.width / 2, size.height );
			renderer.setViewport( - size.width / 2, 0, size.width / 2, size.height );
		}
		else if (flick % (granularity + offset) == 0) {
			renderer.setScissor( 0, 0, size.width / 2, size.height );
			renderer.setViewport( 0, 0, size.width / 2, size.height );
		}

		flickerCamera.cameraR.updateProjectionMatrix();
		flickerCamera.cameraR.position.set( - this.eyeSep, 0, 3 );

		renderer.render( scene, flickerCamera.cameraR );

		//

		flick++;

		renderer.setScissorTest( false );

	};

};
