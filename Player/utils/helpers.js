Array.prototype.random = function () {
  return this[Math.floor((Math.random()*this.length))];
}

function loadFile(path){
    var xhr = new XMLHttpRequest();
    xhr.open('GET', path, false);  // `false` makes the request synchronous
    xhr.send(null);
    if(xhr.status == 200){
        return xhr.responseText;
    }else{
        try{
            // var response = JSON.parse(xhr.responseText);
            // self.configAnimation(response);
            return xhr.responseText;
        }catch(err){
            return null;
        }
    }
}

function apiRequest(path){
    var xhr = new XMLHttpRequest();
    xhr.open('GET', path, false);  // `false` makes the request synchronous
    
    xhr.send(null);

    if(xhr.status == 200){
        return JSON.parse(xhr.responseText);
    }else{
        try{
            return JSON.parse(xhr.responseText);
        }catch(err){
            return null;
        }
    }
}

function getQueryStringValue (key) {  
    return unescape(window.location.search.replace(new RegExp("^(?:.*[&\\?]" + escape(key).replace(/[\.\+\*]/g, "\\$&") + "(?:\\=([^&]*))?)?.*$", "i"), "$1"));  
} 