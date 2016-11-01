var Asset = function(data){
	this._max_tags = 5;

	this.type = 'image';

	if(typeof data === 'string'){
		if (CONFIG.platform == 'phantom'){
			var path = '.data/' + CONFIG.storyId + '/assets/' + data + '/asset.json';
			var dataString = loadFile(path);
	    	data = JSON.parse(dataString);
		}
		else{
			// TODO: API - 
			console.log("TODO: get Asset Data from API");
		}
	}

	for(var key in data){
		this[key] = data[key];
	}

	// Load usericons
	// if(this.source == 'instagram'){
	// 	var url = GLOBALS.api_url + 'instagram/user/'+this.user;
	// 	userdata = apiRequest(url);
	// 	if(userdata.user != null){
	// 		this.usericon = userdata.user.data.profile_picture;
	// 	}else{
	// 		this.error = true;
	// 	}
	// }
	// else if(this.source == 'twitter'){
	// 	var url = GLOBALS.api_url + 'twitter/user/'+this.user;
	// 	userdata = apiRequest(url);
	// 	if(userdata.user != null){
	// 		this.usericon = userdata.user.profile_image_url;
	// 	}else{
	// 		this.error = true;
	// 	}
	// }

	// TODO: aync load images, videos. Callback or trigger on ready event
}

Asset.prototype.get = function(key){

	switch(key) {
	    case 'userhandle':
	        return {text: '@'+this.username, fc:COLORS.userhandle, mf:30}
	        break;
	    case 'photocredit':
	    	return {text: '@'+this.username, fc:COLORS.photocredit, mf:30}
	        break;
	    case 'tweetbody':
	        var t = this.text; 
	        return {text: t, fc:COLORS.tweetbody, mf:40}
	        break;
	    case 'hashtags':
	    	if(!('tags' in this) || this.tags.length == 0){
	    		return {text: "", fc:COLORS.hashtags, mf:20};
	    		break;
	    	}
	    	return {text: '#'+this.tags.slice(0, this._max_tags).join(' #').toUpperCase(), fc:COLORS.hashtags, mf:40}
	    	break;
	    case 'media':
	    	if (this.type == 'video'){
	    		// return {'id':this.id};
	    		var data = this.images.standard_resolution;
	    		data.image = this.images.standard_resolution;
	    		data.video = this.videos.standard_resolution;
	    		data.dir = this.dir;
	    		data.n_frames = this.n_frames;
	    		data.type = 'video';
	    		return data;
	    		break;
	    	}else{
	    		var data = this.images.standard_resolution;
	    		data.type = 'image';
	    		return data;
	    		break;
	    	}
	    	
	    case 'usericon':
	    	return this.usericon;
	    	break;
	    default:
	        return '';
	}

}

Asset.prototype.isVideo = function(){
	return this.type === 'video';
}

Asset.prototype.getUser = function(){
	return {platform: this.platform, username: this.username};
}