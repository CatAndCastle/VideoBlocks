var TemplateManager = function(params){
	// TYPE can be sports, music, news - different colors, fonts, blocks for each
	this.type = 'default';
	this.folder = '_templates/';
	this.configure(params);
	this.previousBlock = "";


}

TemplateManager.prototype.configure = function(params){
	for(var key in params){
		this[key] = params[key];
	}

	this.loadFonts();
	this.loadColors();

	// template sets will depend on this.type
	this.TITLE_TEMPLATES = ['ZS_Template_SPLIT_Title_01', 'ZS_Template_SPLIT_Title_02', 'ZS_Template_SPLIT_Title_03', 'ZS_Template_FLOAT_Title_01'];
	// this.TITLE_TEMPLATES = ['ZS_Template_SPLIT_Title_03'];
	// this.TITLE_TEMPLATES = ['ZS_Template_FLOAT_Title_01'];

	this.END_TEMPLATES = ['ZS_Template_SPLIT_EndState_01'];
	this.CONTENT_TEMPLATES = ['ZS_Template_SPLIT_QuoteWPhoto_01', 
								// 'ZS_Template_SPLIT_QuoteWPhoto_02', 
								'ZS_Template_SPLIT_Photos_01', 
								'ZS_Template_Float_Photos_01', 
								'ZS_Template_FLOAT_Quote_01', 
								'ZS_Template_FLOAT_Quote_02', 
								'ZS_Template_FLOAT_Quote_03'];
	// this.CONTENT_TEMPLATES = ['ZS_Template_SPLIT_Photos_01'];


}

TemplateManager.prototype.loadFonts = function(){
	this.fontManager = new FontManager();
	// this.fontManager.loadFonts(this.type);
}

TemplateManager.prototype.loadColors = function(){
	this.colorManager = new ColorManager();
	this.colorManager.loadPallete(this.type);
}

TemplateManager.prototype.getTitleBlock = function(){
	var path =  this.folder + this.TITLE_TEMPLATES.random() + '/data.json';
	return this.loadBlock(path);
}

TemplateManager.prototype.getEndBlock = function(){
	var path =  this.folder + this.END_TEMPLATES.random() + '/data.json';
	return this.loadBlock(path);
}

TemplateManager.prototype.getContentBlock = function(params){
	// don't show the same block a row
	var tmp = this.CONTENT_TEMPLATES.slice();
	if(tmp.indexOf(this.previousBlock) > -1){
		tmp.splice(tmp.indexOf(this.previousBlock), 1);
	}
	this.previousBlock =  tmp.random();
	return this.loadBlock(this.folder + this.previousBlock + '/data.json');
}

TemplateManager.prototype.loadBlock = function(path){
	console.log("loading " + path);
	var block = new Block();
	block.loadTemplate(path);
	block.setFont(this.fontManager);
	block.setColorPallete(this.colorManager.pallete);
	return block;
}