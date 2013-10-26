console.log('Robot is waking up');

//bootstrap
var fs = require('fs'),
	system = require('system'),
	server = require('webserver').create(),
	page = require('webpage').create();

//default configuration from initialising the process
var defaultConfig = {
	ipaddress: '127.0.0.1',
	port: 8989,
	width: '1280',
	height: '1024', //this can be used to facilitate scroll based pagination
	imgformat: 'png',
	useragent: 'SnapSearch',
	loadimages: true,
	javascriptenabled: true,
	maxtimeout: 5000,
	logfile: 'log.txt' // Log file is recorded in the current working directory of where you started the web server, it is not the same as this script's path
};

//filling up the options
var args = system.args;

args.forEach(function(value, index){

	//skip the first arg (the script name)
	if(index === 0){
		return;
	}
	
	var key = value.substr(0, value.indexOf('='));
	var propValue = value.substr(value.indexOf('=') + 1);

	if (!key || !propValue) {
		console.log('Incorrect parameters format, use key=value');
		phantom.exit();
	}

	if (key === 'ipaddress') defaultConfig.ipaddress = propValue;
	if (key === 'port') defaultConfig.port = propValue;
	if (key === 'width') defaultConfig.width = propValue;
	if (key === 'height') defaultConfig.height = propValue;
	if (key === 'imgformat') defaultConfig.imgformat = propValue;
	if (key === 'useragent') defaultConfig.useragent = propValue;
	if (key === 'loadimages') defaultConfig.loadimages = propValue;
	if (key === 'javascriptenabled') defaultConfig.javascriptenabled = propValue;
	if (key === 'maxtimeout') defaultConfig.maxtimeout = propValue;
	if (key === 'logfile') defaultConfig.logfile = propValue;

});

//log function
var logError = function(exception){
	var msg = exception.message + ' - ' + exception.fileName + ' - ' + exception.lineNumber + ' - ' + (new Date()).toString();
	console.log('Robot hit an error: ' + msg);
	fs.write(defaultConfig.logfile, msg + "\n", 'a');
};

//create a queue of tasks {request, response}
var tasks = [];
var busy = false;

var service = server.listen(defaultConfig.ipaddress + ':' + defaultConfig.port, function(request, response){

	console.log('Robot received a task');
	tasks.push({
		request: request,
		response: response
	});

});

//in case the service wasn't started
if(service){
	console.log('Robot server started at: ' + defaultConfig.ipaddress + ':' + defaultConfig.port);
}else{
	console.log('Unable to start robot server on ' + defaultConfig.ipaddress + ':' + defaultConfig.port);
	phantom.exit();
}

//requires url (which could be changed), config, page object, response object
var requestPage = function(url, config, page, response){

	page.open(url).then(function(status){

		if(status == 'success'){

			response.statusCode = 200;
			response.write('yea');
			response.closeGracefully();
			page.close();

		}else{


		}

	});

};

//this will process a task, while also requiring the dependencies from defaultConfig and page object
var processTask = function(task){

	console.log('Robot is processing a task');

	busy = true;
	var request = task.request;
	var response = task.response;

	//configuration needs to be isolated for the current request
	var currentConfig = JSON.parse(JSON.stringify(defaultConfig));
	var input = request.post;
	//input comes as a json post
	/*
		{
			url: 
			width: 
			height: 
			imgformat: 
			useragent: 
			loadimages: //wont render screen shot if this is false and ignore width/height/base64... etc
			javascriptenabled:
			maxtimeout: //milliseconds on the maximum wait before timing out and rendering/return html snapshot
			animations: //want to run a mutation check on body tag? (expensive operation)
		}
	 */
	try{
		input = JSON.parse(input);
	}catch(e){
		logError(e);
	}

	for(var key in input){
		currentConfig[key] = input[key];
	}

	page.viewportSize = {
		width: currentConfig.width,
		height: currentConfig.height
	};

	page.settings.userAgent = currentConfig.useragent;
	page.settings.loadImages = currentConfig.loadimages;
	page.settings.javascriptEnabled = currentConfig.javascriptenabled;

	//queue up all the asynchronous and synchronous requests that the page will execute
	var pageRequests = [];

	page.onResourceRequested = function(resource){
		console.log('Robot is requesting: ' + resource.id + ' - ' + resource.url);
		pageRequests.push(resource.id);
	};

	page.onResourceReceived = function(resource){
		console.log('Robot received: ' + resource.id + ' - ' + resource.url + ' at ' + resource.stage);
		if(resource.stage == 'end'){
			var index = pageRequests.indexOf(resource.id);
			if (index != -1) {
				pageRequests.splice(index, 1);
			}
		}
	};

	//this currently doesn't work, it should run for any aborted requests
	page.onResourceError = function(resource){
		console.log('Robot could receive: ' + resource.url);
		var index = pageRequests.indexOf(resource.id);
		if (index != -1) {
			pageRequests.splice(index, 1);
		}
	};

	//once the page has been closed, then we are free to do more work
	page.onClosing = function(){
		console.log('Robot has closed page');
		busy = false;
	};

	//if the page redirects, it doesn't matter, we want to send that redirection to Google, they will process
	//it properly,
	//therefore we need to have the proper http code and headers
	//All http code and headers should be sent back as well of the page itself should be sent back
	//we can get this from the onResourceReceived

	requestPage(currentConfig.url, currentConfig, page, response);

};

//every 500 milliseconds, this will check
(function processTasks(){
	setTimeout(function(){
		if(!busy && tasks.length > 0){
			console.log('There are ' + tasks.length + ' tasks in the queue');
			//get the first task
			var task = tasks.shift();
			processTask(task);
		}else{
			processTasks();
		}
	}, 250);
})();