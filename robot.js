//STILL TO ADD:
//1. Mutation Observation (refresh timeout) (inside the context of web page, using blocking page.evaluate)
//3. PHP should compress the JSON before sending...? Or allow parameter to allow the option of compressing it
//using msgpack or other styles (using gzip compression?)

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
	initialwait: 1000, //initial wait for asynchronous requests to fill up
	callback: false, 
	logfile: false // Log file is recorded in the current working directory of where you started the web server, it is not the same as this script's path (can be log.txt), will auto create the file it doesn't exist
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
	if (key === 'initialwait') defaultConfig.initialwait = propValue;
	if (key === 'callback') defaultConfig.callback = propValue;
	if (key === 'logfile') defaultConfig.logfile = propValue;

});

//log function
var logError = function(exception){
	var msg = exception.message + ' - ' + exception.fileName + ' - ' + exception.lineNumber + ' - ' + (new Date()).toString();
	console.log('Robot hit an error: ' + msg);
	if(typeof defaultConfig.logfile == 'string'){
		fs.write(defaultConfig.logfile, msg + "\n", 'a');
	}
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
		initialwait: 
		callback //string
	}
 */
var parseInputJson = function(input){

	try{
		input = JSON.parse(input);
		return input;
	}catch(e){
		logError(e);
		return false;
	}

};

var outputResult = function(content, response){

	console.log('Robot is responding');

	response.statusCode = 200;
	response.headers = {
		'Content-Type': 'application/json'
	};
	
	content.date = Math.floor(Date.now()/1000);
	content = JSON.stringify(content);

	response.write(content);
	
	response.close();

};

var processTask = function(task){

	console.log('Robot is processing a task');
	
	busy = true;

	var request = task.request, 
		response = task.response, 
		input = parseInputJson(request.post), 
		output = {
			status: '',
			headers: [],
			message: '',
			html: '',
			screenshot: '',
			date: ''
		}, 
		currentConfig = JSON.parse(JSON.stringify(defaultConfig));

	if(!input){
		output.message = 'Input was not valid JSON';
		outputResult(output, response);
		busy = false;
	}
	
	//configuration needs to be isolated for the current request
	for(var key in input){
		currentConfig[key] = input[key];
	}

	//window viewport
	page.viewportSize = {
		width: currentConfig.width,
		height: currentConfig.height
	};

	//some other settings
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
		//trailing slashes could occur
		if(resource.url == currentConfig.url || resource.url.replace(/\/$/,"") == currentConfig.url){
			//headers will be an array of objects {name:'', value: ''}
			output.headers = resource.headers;
			output.status = resource.status;
		}
	};

	//this currently doesn't work, it should run for any aborted requests
	page.onResourceError = function(resource){
		console.log('Robot could not receive: ' + resource.url);
		var index = pageRequests.indexOf(resource.id);
		if (index != -1) {
			pageRequests.splice(index, 1);
		}
	};

	//once the page has been closed, then we are free to do more work
	page.onClosing = function(){
		console.log('Robot has closed the page');
		busy = false;
	};

	//for triggering a max timeout for asynchronous requests
	var startingTime = '',
		currentTime = '',
		difference = '';

	page.open(currentConfig.url).then(function(status){

		if(status == 'success'){

			console.log('Robot has opened the page and loaded all synchronous requests');

			startingTime = Math.floor(Date.now());

			console.log('Robot is waiting for asynchronous requests to fill up');

			var evaluatePage = function(){

				console.log('Robot has loaded all asynchronous requests or requests have hit max timeout');

				//default white background
				page.evaluate(function(){
					var style = document.createElement('style'),
					text = document.createTextNode('body { background: #fff }');
					style.setAttribute('type', 'text/css');
					style.appendChild(text);
					document.head.insertBefore(style, document.head.firstChild);
				});

				//custom callback can be evaluated before rendering
				if(typeof currentConfig.callback == 'string'){
					console.log('Robot is evaluating custom callback');
					page.evaluate(function(callback){
						eval(callback);
					}, currentConfig.callback);
				}

				var html = page.evaluate(function(){
					return document.documentElement.outerHTML;
				});

				var screenshot = '';
				if(currentConfig.loadimages){
					screenshot = page.renderBase64(currentConfig.imgformat);
				}

				output.message = 'Success';
				output.html = html;
				output.screenshot = screenshot;

				outputResult(output, response);
				page.close();
				console.log('Robot has finished a task');

			};

			var checkResourceRequests = function(){
				setTimeout(function(){
					if(pageRequests.length > 0){
						console.log('Robot still has these asynchronous resources to load: ' + pageRequests.join(', '));
					}
					currentTime = Math.floor(Date.now());
					difference = currentTime - startingTime;
					if(pageRequests.length == 0 || difference > currentConfig.maxtimeout){
						evaluatePage();
					}else{
						checkResourceRequests();
					}
				}, currentConfig.initialwait);
			};
			checkResourceRequests();

		}else{

			console.log('Robot failed to open page');
			output.message = 'Failed to load URL: ' + currentConfig.url;
			outputResult(output, response);
			page.close();
			console.log('Robot has finished a task');

		}

	});

};

//every 250 milliseconds, this will check
(function processTasks(){
	setTimeout(function(){
		if(!busy && tasks.length > 0){
			console.log('There are ' + tasks.length + ' tasks in the queue');
			//get the first task
			var task = tasks.shift();
			processTask(task);
		}
		//we want to continue the loop even if a task is being processed
		processTasks();
	}, 250);
})();