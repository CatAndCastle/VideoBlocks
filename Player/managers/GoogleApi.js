var GoogleApi = function(){
	this.GOOGLE_API_KEY = "AIzaSyDT9Hw8wqem-pJm6wS1rz6JtSAd5SEyXk4";
}

GoogleApi.prototype.translate = function(string, from, to){
	var url = 'https://www.googleapis.com/language/translate/v2?'
				+'key='+this.GOOGLE_API_KEY
				+'&q='+encodeURIComponent(string)
				+'&source='+from
				+'&target='+to;
	var response = apiRequest(url);
	if(!('error' in response) 
		&& ('data' in response) && ('translations' in response.data) && response.data.translations.length > 0){
		console.log('translated:' + response.data.translations[0].translatedText);
		return response.data.translations[0].translatedText;
	}else{
		return string;
	}

}