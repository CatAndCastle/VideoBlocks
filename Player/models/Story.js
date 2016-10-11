var Story = function(storyId){
	this.load(storyId);
	this.users = []; // array of userhandels used in video
}

Story.prototype.load = function(storyId){
	var storyData;
	
	if (CONFIG.platform == 'phantom'){
		var path = '.data/' + storyId + '/story.json';
		var dataString = loadFile(path);
    	storyData = JSON.parse(dataString);
	}
	else{
		var url = CONFIG.api_url + 'story/'+storyId;
		storyData = apiRequest(url);
	}

	// save data
	for(var key in storyData){
		this[key] = storyData[key];
	}

	// TODO: remove poster and description assets (should be done on the server)
	// this.body.splice(0, 2);
	
}

Story.prototype.get = function(key){

	switch(key) {
	    case 'location':
	        return {text: this.location.name.toUpperCase(), fc:[236/255,114/255,99/255], mf:30}
	        break;
	    case 'title':
	    	return {text: this.name.toUpperCase(), fc:[255/255,237/255,188/255], mf:40}
	        break;
	    case 'date':
	        return {text: this.dateString, fc:[236/255,114/255,99/255], mf:20}
	        break;
	    case 'userhandle':
	    	return {text: '@'+this.poster_full.username, fc:[87/255,56/255,92/255], mf:30}
	    	break;
	    case 'media':
	    	return this.poster_full.images.standard_resolution;
	    	break;
	    default:
	        return '';
	}

}

Story.prototype.getAsset = function(idx){
	var asset = new Asset(this.body[idx]);
	return asset;
}

Story.prototype.getAssets = function(num){
	var arr = []
	for(var i=0; i<Math.min(num, this.body.length); i++){
		// for multi-asset blocks - prefer images ?
		var data = num>3 ? this.body.pop() : this.body.shift();
		arr.push(new Asset(data));
		// arr.push(this.getVidAsset());
	}
	return arr;
}

Story.prototype.getVidAsset = function(){
	// var str = '{"created_time":1475109683,"images":{"thumbnail":{"width":150,"url":"https://scontent.cdninstagram.com/t51.2885-15/s150x150/e15/14473906_6439291…31_54982126918959104_n.jpg?ig_cache_key=MTM0OTY0MDMwNzI0NzE1MTU5Nw%3D%3D.2","height":150},"low_resolution":{"width":320,"url":"https://scontent.cdninstagram.com/t51.2885-15/s320x320/e15/14473906_6439291…31_54982126918959104_n.jpg?ig_cache_key=MTM0OTY0MDMwNzI0NzE1MTU5Nw%3D%3D.2","height":320},"standard_resolution":{"width":640,"url":"https://scontent.cdninstagram.com/t51.2885-15/s640x640/e15/14473906_6439291…31_54982126918959104_n.jpg?ig_cache_key=MTM0OTY0MDMwNzI0NzE1MTU5Nw%3D%3D.2","height":640}},"link":"https://www.instagram.com/p/BK64rVwA_nt/","location":{"latitude":40.447065339264,"name":"PNC Park","id":1877,"longitude":-80.005978363921},"videos":{"thumbnail":{"width":0,"url":"","height":0},"low_resolution":{"width":480,"url":"https://scontent.cdninstagram.com/t50.2886-16/14473416_1254325124612413_2189624784776593408_s.mp4","height":480},"standard_resolution":{"width":640,"url":"https://scontent.cdninstagram.com/t50.2886-16/14515260_1107072049376669_3459930627506176000_n.mp4","height":640}},"id":"1349640307247151597_1301720133","text":"Racing pierogies.","source":"instagram","type":"video","user":"1301720133","username":"madadmirer","tags":[],"share_link":"https://www.instagram.com/p/BK64rVwA_nt/","time_since":"20h","score":21,"block":"3"}';
	// var str = '{"created_time":1475649579,"images":{"thumbnail":{"width":150,"url":"https://scontent.cdninstagram.com/t51.2885-15/s150x150/e15/14547677_3844881…_1522076168491106304_n.jpg?ig_cache_key=MTM1NDE2OTI3NjIwNjM1NjMwOQ%3D%3D.2","height":150},"low_resolution":{"width":320,"url":"https://scontent.cdninstagram.com/t51.2885-15/s320x320/e15/14547677_3844881…_1522076168491106304_n.jpg?ig_cache_key=MTM1NDE2OTI3NjIwNjM1NjMwOQ%3D%3D.2","height":320},"standard_resolution":{"width":640,"url":"https://scontent.cdninstagram.com/t51.2885-15/s640x640/e15/14547677_3844881…_1522076168491106304_n.jpg?ig_cache_key=MTM1NDE2OTI3NjIwNjM1NjMwOQ%3D%3D.2","height":640}},"link":"https://www.instagram.com/p/BLK-cgyBxdV/","location":{"latitude":37.750348690953,"name":"Oracle Arena - Warriors Ground","id":735596465,"longitude":-122.20289254759},"videos":{"thumbnail":{"width":0,"url":"","height":0},"low_resolution":{"width":480,"url":"https://scontent.cdninstagram.com/t50.2886-16/14613465_961948163917163_6594900219761000448_n.mp4","height":480},"standard_resolution":{"width":640,"url":"https://scontent.cdninstagram.com/t50.2886-16/14598118_157928867996635_3186745259799347200_n.mp4","height":640}},"id":"1354169276206356309_3628659528","text":"#theWarriors vs LA Clippers at the Oracle, Oakland 😂","source":"instagram","type":"video","user":"3628659528","username":"ayalaelle","tags":["thewarriors"],"share_link":"https://www.instagram.com/p/BLK-cgyBxdV/","time_since":"53m","score":19,"block":"1"}';
	// var data = JSON.parse(str);
	// return new Asset(data);

	var data = this.body[6];
	return new Asset(data);
}