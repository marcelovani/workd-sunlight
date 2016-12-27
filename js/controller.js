var stepped = 0, rowCount = 0, errorCount = 0, firstError =undefined;
var start, end;

$( function () {
	input = '/data/world_cities.csv';

	start = now();
	var config = buildConfig( true );
	var results = Papa.parse( input, config );
	if ( config.worker || config.download )
		console.log( "Running..." );

} );

/**
 * Helper to log stats.
 *
 * @param msg
 */
function printStats( msg ) {
	if ( msg )
		console.log( msg );
	console.log( "       Time:", (end - start || "(Unknown; your browser does not support the Performance API)"), "ms" );
	console.log( "  Row count:", rowCount );
	if ( stepped )
		console.log( "    Stepped:", stepped );
	console.log( "     Errors:", errorCount );
	if ( errorCount )
		console.log( "First error:", firstError );
}

/**
 * Helper to build the config.
 *
 * @param serviceWorker
 * @returns {{}}
 */
function buildConfig( serviceWorker ) {
	var config = {};

	switch ( serviceWorker ) {
		case true:
			config = {
				delimiter: ',',
				header: true,
				dynamicTyping: false,
				skipEmptyLines: true,
				preview: 0,
				step: true,
				encoding: 'UTF-8',
				worker: true,
				comments: '#',
				complete: completeFn,
				error: errorFn,
				download: true
			};
			break;
		default:
			config = {
				delimiter: ',',
				header: true,
				dynamicTyping: false,
				skipEmptyLines: true,
				preview: 0,
				step: false,
				encoding: 'UTF-8',
				worker: false,
				comments: '#',
				complete: completeFn,
				error: errorFn,
				download: true
			}
	}
	return config;
}

/**
 * Callback to do step process.
 *
 * @param results
 * @param parser
 */
function stepFn( results, parser ) {
	stepped++;
	if ( results ) {
		if ( results.data )
			rowCount += results.data.length;
		if ( results.errors ) {
			errorCount += results.errors.length;
			firstError = firstError || results.errors[0];
		}
	}
}

/**
 * Complete callback.
 *
 * @param results
 */
function completeFn( results ) {
	end = now();

	if ( results && results.errors ) {
		if ( results.errors ) {
			errorCount = results.errors.length;
			firstError = results.errors[0];
		}
		if ( results.data && results.data.length > 0 )
			rowCount = results.data.length;
	}

	printStats( "Parse complete" );
	console.log( "    Results:", results );

	var xhr;
	TWEEN.start();

	xhr = new XMLHttpRequest();
	xhr.open('GET', '/globe/population909500.json', true);
	xhr.onreadystatechange = function(e) {
		if (xhr.readyState === 4) {
			if (xhr.status === 200) {
				var data = JSON.parse(xhr.responseText);
				window.data = data;
				data = [
					[
					'1990',
					[6,159,.3]
					],
					[
					'1995',
						[30,99,.2]
					]
				];
					console.log(data);
					console.log(data[i]);
				for (i=0;i<data.length;i++) {
					globe.addData(data[i][1], {
						format: 'magnitude',
						name: data[i][0],
						animated: true}
					);
				}
				globe.createPoints();
				settime(globe,0)();
				globe.animate();
				document.body.style.backgroundImage = 'none'; // remove loading
			}
		}
	};
	xhr.send(null);

}

/**
 * Error callback;
 *
 * @param err
 * @param file
 */
function errorFn( err, file ) {
	end = now();
	console.log( "ERROR:", err, file );
}

function now() {
	return typeof window.performance !== 'undefined'
		? window.performance.now()
		: 0;
}