var VideoConstructor = function(params){
	// defaults
	this.duration 	= 30;
	this.storyId 	= '';
	this.blockIdx 	= 1;
	this.frameRate	= 29.97;
	this.numBlocks 	= 6;
	this.story 		= null;
	this.asset 		= null;
	this.blockId    = null;
	
	this.animationItem = null;
	this.renderParams = {
        container: document.getElementById('bodymovin'),
        renderer: CONFIG.renderer,
        loop: false,
        autoplay: true
    };

	// set params
	for(var key in params){
		this[key] = params[key];
	}

	this.log_data	 = {blocks: []};

	this.configure();

}

VideoConstructor.prototype.configure = function(){
	// this.numFrames = Math.floor(this.frameRate*this.duration);
	this.loadData();
	
	this.TM = new TemplateManager();

}

VideoConstructor.prototype.loadFonts = function(){
	// TODO: select set of fonts to load
	this.fontManager = new FontManager();
	this.fontManager.loadFonts(['English', 'Numbers', 'ArialMT']);
	// this.fontManager.loadFonts(['ArialMT']);
}

VideoConstructor.prototype.loadColors = function(){
	// TODO: select set of fonts to load
	this.colorManager = new ColorManager();
	this.colorManager.loadPallete('default');
}


VideoConstructor.prototype.loadData = function(){
	if(this.blockId){
		this.numBlocks = 1;
		this.story = new Story(this.storyId);
	}
	if(this.assetId){
		this.numBlocks = 1;
		this.asset = new Asset(this.assetId);
	}else{
		this.story = new Story(this.storyId);
	}
	
}

VideoConstructor.prototype.startRender = function(){
	this.loadNextBlock(true);
}

VideoConstructor.prototype.goToNextFrame = function(){
	if(this.animationItem == null && this.blockIdx>this.numBlocks){
		// if (CONFIG.platform == 'phantom'){
		// 	this.story.saveData();
		// }
		return false;
	}
	if(this.animationItem == null && this.blockIdx<=this.numBlocks){
		this.loadNextBlock(false);
		this.currentFrame = 1;
		this.animationItem.goToAndStop(1,true);
	}else{
		if(this.animationItem.currentFrame<this.animationItem.totalFrames){
			// var tnext = (this.animationItem.currentFrame+1)/this.frameRate;
			// var nextFrame =  tnext >= 2  && tnext < 8 ? Math.floor(8*this.frameRate) : this.animationItem.currentFrame+1;
			// this.animationItem.goToAndStop(nextFrame, true);
			this.currentFrame = this.currentFrame+1;
			this.animationItem.goToAndStop(this.animationItem.currentFrame+1, true);
		}
	}

	return true;


}

/**
	returns bodymovin AnimationItem
*/
VideoConstructor.prototype.loadNextBlock = function(autoplay){
	var self = this;
	var block = this.getNextBlock();
	this.log_data.blocks.push(block.blockId);
	this.renderParams.animationData = block.animationData;
	this.renderParams.autoplay = autoplay;

	bodymovin.destroy();
	// console.log(this.renderParams.animationData);
	this.animationItem =  bodymovin.loadAnimation(this.renderParams);
	console.log(this.animationItem);
	
	this.animationItem.addEventListener('DOMLoaded', function(){
        console.log(" - loaded");
    });

	this.animationItem.addEventListener('enterFrame', function(){
		// console.log(e);
        // console.log("enter frame " + self.animationItem.currentFrame);
    });

    this.animationItem.addEventListener('complete', function(){
    	
		self.animationItem = null;

    	if(autoplay && self.blockIdx<=self.numBlocks){
    		self.loadNextBlock(autoplay);
    	}
    });

    // this.animationItem = animationItem;
	// return {anim: animationItem, loadNext: this.blockIdx<=this.numBlocks };

}

VideoConstructor.prototype.getNextBlock = function(){	
	if(this.blockId){
		var block = this.TM.getBlockById(this.blockId);
		if(block.type == "title"){
			block.fillTemplate([this.story]);
		}else{
			res = block.fillTemplate(this.story.getEndAssets(block.animationData.placeholders.assets.length));
	    	if(res.error){
	        	console.log('error! not enough data to fill end block');
	        }
		}
		return block;

	}
	
	if(this.story == null){
		var block = this.TM.getContentBlock({maxAssets: 1});
        res = block.fillTemplate([this.asset]);
        this.blockIdx++;
        return block;
        // if(res.error){
        // 	this.blockIdx = this.numBlocks
        // 	return this.getNextBlock();
        // }
	}

	if(this.blockIdx == this.numBlocks){
    	var block = this.TM.getEndBlock();
    	res = block.fillTemplate(this.story.getEndAssets(block.animationData.placeholders.assets.length));
    	if(res.error){
        	console.log('error! not enough data to fill end block');
        }
	}
	else if(this.blockIdx == 1){
		var block = this.TM.getTitleBlock();
    	block.fillTemplate([this.story]);
	}
	else{
  		var block = this.TM.getContentBlock({maxAssets: this.story.numAssetsLeft()});
        res = block.fillTemplate(this.story.getAssets(block.animationData.placeholders.assets.length));
        if(res.error){
        	this.blockIdx = this.numBlocks
        	return this.getNextBlock();
        }
	}
	// frames in block
	// this.totalFrames = Math.floor(this.animationData.op - this.animationData.ip);

	this.blockIdx++;

	return block;
}
