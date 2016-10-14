var Story = function(storyId){
	this.load(storyId);
	this.usedAssets = []; //array of assets used in video
}

Story.prototype.load = function(storyId){
	var storyData;
	
	if (CONFIG.platform == 'phantom'){
		var path = '.data/' + storyId + '/story.json';
		var dataString = loadFile(path);
    	storyData = JSON.parse(dataString);
	}
	else{
		var url = CONFIG.api_url + 'story/'+storyId+'?q=keywords,hashtags';
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
	    	var asset = this.getAssets(1)[0];
	    	return asset.get('media');
	    	// return this.poster_full.images.standard_resolution;
	    	break;
	    default:
	        return '';
	}

}

Story.prototype.getAsset = function(idx){
	var asset = new Asset(this.body[idx]);
	return asset;
}

Story.prototype.numAssetsLeft = function(){
	return this.body.length;
}

Story.prototype.getAssets = function(num){
	var arr = []
	for(var i=0; i<Math.min(num, this.body.length); i++){
		// for multi-asset blocks - prefer images ?
		var data = num>3 ? this.body.pop() : this.body.shift();
		arr.push(new Asset(data));
		this.usedAssets.push(data);
		// arr.push(this.getVidAsset());
	}

	return arr;
}
Story.prototype.saveData = function(){
	var data = {
		'assets': [],
		'hashtags': [],
		'users': []
	};
	for(var i=0; i<this.hashtags.length; i++){
		data['hashtags'].push("#" + this.hashtags[i]);
	}
	for(var i=0; i<this.usedAssets.length; i++){
		data['assets'].push({
			assetId : this.usedAssets[i].id,
			source : this.usedAssets[i].source,
			type : this.usedAssets[i].type,
			user : "@" + this.usedAssets[i].username,
			url : this.usedAssets[i].link
		});

		data['users'].push("@" + this.usedAssets[i].username);
	}

	//write to file - doesnt work duhh
	writeTextFile('.data/' + this.storyId + '/data.json', JSON.stringify(data));

}