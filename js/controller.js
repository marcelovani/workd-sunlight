var stepped = 0, rowCount = 0, errorCount = 0, firstError =undefined;
var start, end;

$( function () {
	input = './data/world_cities_lights.csv';

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
	//console.log( "    Results:", results );

	// Normalize data.
	var dayData = [];
	var darkData = [];
	var step = 100;
	for ( i = 0; i < results.data.length; i++ ) {
		var item = results.data[i];
		// filename, lat, lng, daylight, perc_day, darkness, per_dark, longest_day, longest_dark
		dayData.push( item.lat, item.lng, (item.longest_day / 60 ) + Math.random() * 2);
		darkData.push( item.lat, item.lng, item.longest_dark / 50 );
		if (step > 0) i = i + step;
	}

	TWEEN.start();

	data = [
		[
			'Light',
			dayData
		],
		[
			'Darkness',
			darkData
		]
	];

	//console.log(data);
	for ( i = 0; i < data.length; i++ ) {
		globe.addData( data[i][1], {
				format: 'magnitude',
				name: data[i][0],
				animated: true,
			}
		);
	}
	globe.createPoints();
	settime( globe, 0 )();
	globe.animate();
	document.body.style.backgroundImage = 'none'; // remove loading

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