var ColorManager = function(){
	this.folder = '_ColorPalletes/';

	this.pallete = {};
}

ColorManager.prototype.loadPallete = function(name){
	this.pallete = JSON.parse(loadFile(this.palleteFile(name)));
}

ColorManager.prototype.palleteFile = function(name){
	return this.folder + name + '.json';
}