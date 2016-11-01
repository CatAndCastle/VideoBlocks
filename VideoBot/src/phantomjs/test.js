var webPage = require('webpage');
var system = require('system');
var fs = require('fs');
var args = system.args;

console.log('HELLO');

arr = {hello:'world'};
fs.write('test.txt', JSON.stringify(arr), 'w');

phantom.exit(0);