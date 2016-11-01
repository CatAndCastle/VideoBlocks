// run with:
// phantomjs phantomRender.js | ffmpeg -y -f image2pipe -vcodec mjpeg -r 29.97 -i - -vcodec libx264 -b:v 8000k -s 1920x1080 -pix_fmt yuv420p render.mp4

// pass in args
// phantomjs phantomRender.js -dir /path/to/.data -storyId XXX -assetId XXX -s 1920x1080 -lang en


var webPage = require('webpage'),
	system = require('system'),
	fs = require('fs'),
	args = system.args;

// PARSE ARGS
var _inputs = 
{
	'dir': null,
	'storyId': null,
	'assetId': null,
	's': '1920x1080',
	'lang': 'en'
}
var i = 1;
while(i < args.length){
	_inputs[args[i].replace('-', '')] = args[i+1];
	i +=  2;
}

var size = _inputs['s'].split("x"),
	WIDTH = size[0],
	HEIGHT = size[1];

var url = "http://blocks.local/render.html?p=phantom&renderer=svg&lang="+_inputs['lang']+"&storyId="+_inputs['storyId']+"&s="+_inputs['s'];
if(_inputs['assetId'] != null){
	url += "&assetId=" + _inputs['assetId'];
}

var workingDir = _inputs['dir'] + "/" + _inputs['storyId'];
if(_inputs['assetId'] != null){
	workingDir += "/assets/" + _inputs['assetId'];
}

var	log = workingDir + "/phantomjs.log",
	error_log = workingDir + "/phantomjs-error.log",
	timeout_log = workingDir + "/phantomjs-timeout.log",
	data_log = workingDir + "/phantomjs-data.log";


var page = webPage.create();
page.viewportSize = { width: WIDTH, height: HEIGHT };

page.onCallback = function(data) {
	if(data['exit']==true){
		// save log_data
		fs.write(data_log, JSON.stringify(data['log_data']), 'w');
		phantom.exit(0);
	}else{
		// render frame to stdout
		page.render('/dev/stdout', {format: 'jpeg', quality: '100'});
		// page.render('frames/frame_'+data['frame']+'.jpg', {format: 'jpeg', quality: '100'});
	}
};

page.onConsoleMessage = function(msg, lineNum, sourceId) {
	// fs.write(log, 'CONSOLE: ' + msg + '\n -> from line #' + lineNum + ' in "' + sourceId + '"\n', 'a');
	fs.write(log, 'CONSOLE: ' + msg + '\n', 'a');
};

// handle resource timeout
page.settings.resourceTimeout = 15000; // 15 seconds
page.onResourceTimeout = function(resourceError) {
	fs.write(error_log, 'Unable to load resource (#' + resourceError.id + 'URL:' + resourceError.url + ')' 
  					+ '\n -> Error code: ' + resourceError.errorCode  
  					+ '\n -> Description: ' + resourceError.errorString 
  					+ '\n -> Time: ' + resourceError.time 
  					+ '\n', 'a');

	fs.write(timeout_log, 'Unable to load resource (#' + resourceError.id + 'URL:' + resourceError.url + ')' 
  					+ '\n -> Error code: ' + resourceError.errorCode  
  					+ '\n -> Description: ' + resourceError.errorString 
  					+ '\n -> Time: ' + resourceError.time 
  					+ '\n', 'w');
	
	phantom.exit(1);
};

// handle resource not found
page.onResourceError = function(resourceError) {
  fs.write(log, 'RESOURCE ERROR: Unable to load resource (#' + resourceError.id + 'URL:' + resourceError.url + ')\n'
  					+' -> Error code: ' + resourceError.errorCode + '. Description: ' + resourceError.errorString + '\n', 'a');
};

// handle javascript error
page.onError = function(msg, trace){
	var msgStack = ['ERROR: ' + msg];
	if (trace && trace.length) {
		msgStack.push('TRACE:');
		trace.forEach(function(t) {
		  msgStack.push(' -> ' + t.file + ': ' + t.line + (t.function ? ' (in function "' + t.function +'")' : ''));
		});
	}
	fs.write(error_log, msgStack.join('\n')+'\n', 'a');
	phantom.exit(1);

}

page.open(url, function start(status) {
	fs.write(log, "Page open status: " + status +"\n", 'a');
	page.evaluate(function() {

		function advance(constructor){
	        var keepgoing = constructor.goToNextFrame();
	        if(keepgoing){
	            setTimeout(function(){
	            	window.callPhantom({ frame: constructor.currentFrame, exit:false });
	                advance(constructor);
	            },100);
	        }
	        else{
	        	// TODO pass story data to write to local file (assets, hashtags, etc)
	        	window.callPhantom({ exit: true, log_data: constructor.log_data });
	        }
	    }
	    var constructor = new VideoConstructor(CONFIG);
	    advance(constructor);


	});

});

