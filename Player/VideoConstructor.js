var VideoConstructor = function(params){
	// defaults
	this.duration = 30;
	this.storyId = '';
	this.blockIdx = 1;
	this.frameRate = 29.97;
	this.numBlocks = 6;
	
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

	this.configure();

}

VideoConstructor.prototype.configure = function(){
	this.numFrames = Math.floor(this.frameRate*this.duration);
	this.loadStoryData();
	
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


VideoConstructor.prototype.loadStoryData = function(){
	this.story = new Story(this.storyId);

	this.numBlocks = Math.min(this.numBlocks, 2+Math.floor(this.story.body.length/2));
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
		this.animationItem.goToAndStop(1,true);
	}else{
		if(this.animationItem.currentFrame<this.animationItem.totalFrames){
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
	this.renderParams.animationData = block.animationData;
	this.renderParams.autoplay = autoplay;

	bodymovin.destroy();
	console.log(this.renderParams.animationData);
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
	if(this.blockIdx == 1){
		var block = this.TM.getTitleBlock();
    	block.fillTemplate([this.story]);
	}
	else if(this.blockIdx == this.numBlocks){
    	var block = this.TM.getEndBlock();
	}
	else{
  		var block = this.TM.getContentBlock({maxAssets: this.story.numAssetsLeft()});
        res = block.fillTemplate(this.story.getAssets(block.animationData.placeholders.assets.length));
        if(res.error){
        	this.blockIdx = this.numBlocks
        	return this.TM.getEndBlock();
        }
	}
	// frames in block
	// this.totalFrames = Math.floor(this.animationData.op - this.animationData.ip);

	this.blockIdx++;

	return block;
}
