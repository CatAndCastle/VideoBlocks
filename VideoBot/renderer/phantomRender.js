// run with:
// phantomjs phantomRender.js | ffmpeg -y -f image2pipe -vcodec mjpeg -r 29.97 -i - -vcodec libx264 -b:v 8000k -s 1920x1080 -pix_fmt yuv420p render.mp4

var webPage = require('webpage');
var system = require('system');
var fs = require('fs');
var args = system.args;

var page = webPage.create();

var dataDir = args[1];
var STORYID = args[2];

var url = "http://blocks.local/render.html?p=phantom&renderer=svg&storyId="+STORYID;

var storyDir = dataDir + "/" + STORYID;
var log = storyDir + "/phantomjs.log";
var error_log = storyDir + "/phantomjs-error.log";
var timeout_log = storyDir + "/phantomjs-timeout.log";

var _WIDTH = 1920;
var _HEIGHT = 1080;
page.viewportSize = { width: _WIDTH, height: _HEIGHT };

page.onCallback = function(data) {
	if(data['exit']==true){
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

		document.getElementById("bodymovin").style.width =  "1920px";
    	document.getElementById("bodymovin").style.height = "1080px";

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
	        	window.callPhantom({ exit: true });
	        }
	    }
	    var constructor = new VideoConstructor({storyId: STORYID});

	    advance(constructor);


	});

});
