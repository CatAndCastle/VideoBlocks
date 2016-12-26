var TemplateManager = function(params){
	// TYPE can be sports, music, news - different colors, fonts, blocks for each
	this.type = 'template_v2';// 'black'; //'default';
	this.folder = '_templates_v2/';
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
	/* 
		_templates, _templates_will 
	*/
	// this.TITLE_TEMPLATES = ['ZS_Template_SPLIT_Title_01', 'ZS_Template_SPLIT_Title_02', 'ZS_Template_SPLIT_Title_03', 'ZS_Template_FLOAT_Title_01']
	// this.END_TEMPLATES = ['ZS_Template_EndState'];
	// this.CONTENT_TEMPLATES = ['ZS_Template_SPLIT_QuoteWPhoto_01',
	// 							'ZS_Template_SPLIT_Photos_01', 
	// 							'ZS_Template_Float_Photos_01', 
	// 							'ZS_Template_FLOAT_Quote_01', 
	// 							'ZS_Template_FLOAT_Quote_02', 
	// 							'ZS_Template_FLOAT_Quote_03'];

	/* 
		_templates_v1
	*/
	// this.TITLE_TEMPLATES = ['TitleCard_01', 'TitleCard_02', 'TitleCard_03'];
	// this.END_TEMPLATES = ['EndCard_01'];
	// this.CONTENT_TEMPLATES = [
	// 							'Block_01',
	// 							// 'Block_02',
	// 							// 'Block_03',
	// 							'Block_04',
	// 							'Block_05',
	// 							'Block_06'
	// 							];

	/* 
		_templates_v2
	*/
	this.TITLE_TEMPLATES = ['TitleCard_01', 'TitleCard_02', 'TitleCard_03', 'TitleCard_04', 'TitleCard_06'];
	// this.TITLE_TEMPLATES = ['TitleCard_01'];
	this.END_TEMPLATES = ['EndCard_01', 'EndCard_02'];
	// this.CONTENT_TEMPLATES =['Block_17'];
	this.CONTENT_TEMPLATES =[
							'Block_01',
							'Block_02',
							'Block_03',
							'Block_04',
							'Block_05',
							'Block_06',
							'Block_07',
							'Block_08',
							'Block_09',
							'Block_10',
							'Block_11',
							'Block_12',
							'Block_13',
							'Block_14',
							'Block_15',
							'Block_16',
							'Block_17'
							];

	
	this.CONTENT_TEMPLATES_SINGLE = [
							'Block_01',
							'Block_04',
							'Block_05',
							'Block_06',
							'Block_07',
							'Block_08',
							'Block_12',
							'Block_13',
							'Block_14',
							'Block_15',
							'Block_16'
							];
}

TemplateManager.prototype.loadFonts = function(){
	this.fontManager = new FontManager();
	// this.fontManager.loadFonts(this.type); // - using NotoSans.
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
	var templates = this.CONTENT_TEMPLATES;
	if (typeof params.maxAssets !== 'undefined' && params.maxAssets == 1) {
		templates = this.CONTENT_TEMPLATES_SINGLE;
	}
	// don't show the same block a row
	var tmp = templates.slice();
	if(tmp.indexOf(this.previousBlock) > -1){
		tmp.splice(tmp.indexOf(this.previousBlock), 1);
	}
	// pick random block
	this.previousBlock =  tmp.random();
	return this.loadBlock(this.folder + this.previousBlock + '/data.json');
}

TemplateManager.prototype.getBlockById = function(blockId){
	return this.loadBlock(this.folder + blockId + '/data.json');
}

TemplateManager.prototype.loadBlock = function(path){
	console.log("loading " + path);
	var block = new Block();
	block.loadTemplate(path);
	block.setFont(this.fontManager);
	block.setColorPallete(this.colorManager.pallete);
	return block;
}