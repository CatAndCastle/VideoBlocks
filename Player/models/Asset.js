var Asset = function(data){
	this._max_tags = 5;

	this.type = 'image';

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
	        return {text: '@'+this.username, fc:[255/255,237/255,188/255], mf:30}
	        break;
	    case 'photocredit':
	    	return {text: '@'+this.username, fc:[87/255,56/255,92/255], mf:30}
	        break;
	    case 'tweetbody':
	        var t = this.text; 
	        return {text: t, fc:[0,0,0], mf:40}
	        break;
	    case 'hashtags':
	    	if(!('tags' in this) || this.tags.length == 0){
	    		return {text: "", fc:[236/255,114/255,99/255], mf:20};
	    		break;
	    	}
	    	return {text: '#'+this.tags.slice(0, this._max_tags).join(' #').toUpperCase(), fc:[236/255,114/255,99/255], mf:40}
	    	break;
	    case 'media':
	    	if (this.type == 'video'){
	    		// return {'id':this.id};
	    		var data = this.images.standard_resolution;
	    		data.image = this.images.standard_resolution;
	    		data.video = this.videos.standard_resolution;
	    		data.dir = this.dir;
	    		data.n_frames = this.n_frames;
	    		return data;
	    		break;
	    	}else{
	    		return this.images.standard_resolution;
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