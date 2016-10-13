var Block = function(){
	this.type = "post";
	this.animationData = {};

};


Block.prototype.loadTemplate = function(path) {

	this.folder = path.replace(/\/[^\/]+\/?$/, '');

    var dataString = loadFile(path);
    this.animationData = JSON.parse(dataString);

    var configString = loadFile(this.folder + '/placeholders.json');
    this.animationData.placeholders = JSON.parse(configString);

    this.setBaseFolder();
    
};

Block.prototype.setBaseFolder = function(){
	for(var i=0; i<this.animationData.assets.length; i++){
		var obj = this.animationData.assets[i];
		if(this.isImageAsset(obj)){
			obj.u = this.folder + '/images/';
		}
	}
}

Block.prototype.setFont = function(fontManager){
	this.animationData.chars = fontManager.chars;
	this.animationData.fonts.list = fontManager.fonts.list;

	// // Update font-names of text layers to match the template
	// for(var i=0; i<this.animationData.layers.length; i++){
	// 	var obj = this.animationData.layers[i];
	// 	if(this.isTextLayer(obj)){
	// 		obj.t.d.f = fontManager.getFontFor(obj.nm);
	// 	}
	// }

	// for(var i=0; i<this.animationData.assets.length; i++){
	// 	var obj = this.animationData.assets[i];
	// 	if('layers' in obj){
	// 		for(var j=0; j<obj.layers.length; i++){
	// 			var l = obj.layers[j];
	// 			if(this.isTextLayer(l)){
	// 				l.t.d.f = fontManager.getFontFor(obj.nm);
	// 			}
	// 		}
	// 	}
	// }
}


Block.prototype.setColorPallete = function(pallete){
	this.animationData.assets.push(pallete);
}

Block.prototype.loadFonts = function(fontsArray){
	var self = this;
	var chars = [];
    for(var i=0; i<fontsArray.length; i++){
        var data = JSON.parse(self.loadFile(fontsArray[i]));
        chars = chars.concat(data.chars);
    }
    this.animationData.chars = chars;	
}

Block.prototype.fillTemplate = function(data){
	if(data.length < this.animationData.placeholders.assets.length){ 
		return {error:true, message:"not enough assets provided"}; 
	};
	console.log(data);
	for(var i=0; i<this.animationData.placeholders.assets.length; i++){
		this.fillData(this.animationData.placeholders.assets[i], data[i]);
		return {error:false}; 
	}

}

Block.prototype.fillData = function (config, asset){
	var self = this;
	for(var key in config){
		if(key=='usericon'){continue;}
		var arr = config[key];

		for(var i=0; i<arr.length; i++){
			var obj = arr[i],
				layer = this.animationData.layers[obj.idx],
				value = asset.get(key);
			if(obj.type == 'text'){
				self.setText(layer, value);
			}
			else if(obj.type == 'image' && asset.type == 'video'){
				self.setVideo(layer, value);
				// self.setImage(layer, value);
			}
			else if(obj.type == 'image'){
				self.setImage(layer, value);
			}
			
		}
	}
}

Block.prototype.setText = function(layer, v){
	var obj = layer;
	while(!this.isTextLayer(obj)){
		if('refId' in obj){
			obj = this.findAsset(obj.refId);
		}
		else if('layers' in obj){
			for(var idx in obj.layers){
				if(this.isTextLayer(obj.layers[idx])){
					obj = obj.layers[idx];
					break;
				}
			}

			for(var idx in obj.layers){
				if('refId' in obj.layers[idx]){
					obj = this.findAsset(obj.layers[idx].refId);
					break;
				}
			}
		}
	}
	obj.t.d.t = v.text;
	obj.t.d.mf = Math.min(v.mf, obj.t.d.s);
	obj.t.d.f = "NotoSans";
	if(v.fc){
		obj.t.d.fc=v.fc;
	}
	// obj.t.d.fStyle = "normal";
	// obj.t.d.fWeight = "400";

}

Block.prototype.setImage = function(layer, src){
	var obj = layer;
	while(!this.isImageAsset(obj)){
		if('refId' in obj){
			obj = this.findAsset(obj.refId);
		}
		else if('layers' in obj){
			for(var idx in obj.layers){
				var l = obj.layers[idx];
				if('refId' in l){
					this.setImageLayer(l);
					obj = this.findAsset(obj.layers[idx].refId);
					break;
				}
			}
		}
	}

	obj.u = "";
	obj.p = src.url;
	// obj.ty = 2;

}

Block.prototype.setVideo = function(layer, src){
	var obj = layer;
	while(!this.isImageAsset(obj)){
		if('refId' in obj){
			obj = this.findAsset(obj.refId);
		}
		else if('layers' in obj){
			for(var idx in obj.layers){
				var l = obj.layers[idx];
				if('refId' in l){
					this.setVideoLayer(l);
					obj = this.findAsset(obj.layers[idx].refId);
					break;
				}
			}
		}
	}

	obj.u = src.dir;
	obj.nf = src.n_frames;
	// obj.p = src.url;
	// obj.ty = 9;
	// obj.w = src.width;
	// obj.h = src.height;

}

Block.prototype.isImageAsset = function(obj){
	return ('id' in obj) && ('u' in obj) && ('p' in obj) && ('h' in obj) && ('w' in obj);
}
Block.prototype.isVideoLayer = function(obj){
	return ('ty' in obj) && obj.ty==9;
}
Block.prototype.isTextLayer = function(obj){
	return ('t' in obj) && ('d' in obj.t) && ('f' in obj.t.d);
}
Block.prototype.setImageLayer = function(layer){
	if(('ty' in layer) && layer.ty!=0){
		layer.ty = 2;
		layer.cl = 'jpg';
	}
}
Block.prototype.setVideoLayer = function(layer){
	if(('ty' in layer) && layer.ty!=0){
		//convert to image for now!
		layer.ty = 9;
		layer.cl = 'mp4';
	}
}
Block.prototype.findAsset = function(refId){
	for(var i=0; i<this.animationData.assets.length; i++){
		if(this.animationData.assets[i].id == refId){
			return this.animationData.assets[i];
		}
	}
}

Block.prototype.loadFile = function(path){
    var xhr = new XMLHttpRequest();
    xhr.open('GET', path, false);  // `false` makes the request synchronous
    xhr.send(null);
    if(xhr.status == 200){
        return xhr.responseText;
    }else{
        try{
            return xhr.responseText;
        }catch(err){
            return null;
        }
    }
}

