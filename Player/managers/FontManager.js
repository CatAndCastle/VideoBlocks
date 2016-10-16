var FontManager = function(){
	this.folder = '_fonts/';

	this.chars = null;
	this.fonts = {list: [
			{
				fFamily: 'Noto Sans',
				fName: 'NotoSans',
				fStyle: 'Regular',
				fWeight: 400,
				// fPath: "https://fonts.googleapis.com/css?family=Noto+Sans:400,400i",
				fPath: "_fonts/NotoSans/NotoSans-Regular.css",
				fOrigin: "g",
				ascent: 75.97
			},
			{
				fFamily: 'Noto Sans',
				fName: 'NotoSans-Bold',
				fStyle: 'Bold',
				fWeight: 700,
				// fPath: "https://fonts.googleapis.com/css?family=Noto+Sans:700,700i",
				fPath: "_fonts/NotoSans/NotoSans-Bold.css",
				fOrigin: "g",
				ascent: 75.97
			}

		]};


}


FontManager.prototype.loadFonts = function(type){
	
	switch(type) {
	    case 'default':
	        this.load(['English', 'Numbers', 'ArialMT']);
	        break;
	    default:
	        this.load(['English', 'Numbers', 'ArialMT']);
	}
}

FontManager.prototype.load = function(fontsArray){

    for(var i=0; i<fontsArray.length; i++){
        var data = JSON.parse(loadFile(this.fontFile(fontsArray[i])));
        this.addFont(data);
    }
}

FontManager.prototype.addFont = function(data){
	this.chars = this.chars.concat(data.chars);
    // this.fonts.list = this.fonts.list.concat(data.fonts.list);
}

FontManager.prototype.fontFile = function(fontName){
	return this.folder + fontName + '.json';
}

FontManager.prototype.getFontFor = function(layerName){
	//TODO: need some rules about diffrent text layers
	return this.fonts.list[0].fName;
}