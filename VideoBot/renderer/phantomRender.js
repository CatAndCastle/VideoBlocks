// run with:
// phantomjs phantomRender.js | ffmpeg -y -f image2pipe -vcodec mjpeg -r 29.97 -i - -vcodec libx264 -b:v 8000k -s 1920x1080 -pix_fmt yuv420p render.mp4

var webPage = require('webpage');
var system = require('system');
var args = system.args;

var page = webPage.create();

var STORYID = args[1];
var url = "blocks.local/render.html?p=phantom&renderer=svg&storyId="+STORYID;


page.onConsoleMessage = function(msg, lineNum, sourceId) {
  console.log('CONSOLE: ' + msg + ' (from line #' + lineNum + ' in "' + sourceId + '")');
};

page.viewportSize = { width: 1920, height: 1080 };

page.onCallback = function(data) {

	if(data['exit']==true){
		phantom.exit(0);
	}else{
		// render to stdout
		page.render('/dev/stdout', {format: 'jpeg', quality: '100'});
		// page.render('frame.jpg', {format: 'jpeg', quality: '100'});
	}
};


page.open(url, function start(status) {

	page.evaluate(function() {

		document.getElementById("bodymovin").style.width = "1920px";
    	document.getElementById("bodymovin").style.height = "1080px";

		function advance(constructor){
	        var keepgoing = constructor.goToNextFrame();
	        if(keepgoing){
	            setTimeout(function(){
	            	window.callPhantom({ currentFrame: 0, exit:false });
	                advance(constructor);
	            },50);
	        }
	        else{
	        	window.callPhantom({ exit: true });
	        }
	    }
	    var constructor = new VideoConstructor({storyId: STORYID});

	    advance(constructor);


	});

});
