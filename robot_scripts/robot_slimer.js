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
	port: 8500, 
	width: '1280', 
	height: '1024', //this can be used to facilitate scroll based pagination
	imgformat: 'png', 
	useragent: 'SnapSearch', 
	screenshot: false, 
	navigate: false, // allow redirection of the page or not
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
	if (key === 'screenshot') defaultConfig.screenshot = propValue;
	if (key === 'navigate') defaultConfig.navigate = propValue;
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
		screenshot: 
		navigate: 
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

	//allow redirection or not (from header, js, html or user action)
	//this flips the navigate boolean
	//CURRENTLY NOT WORKING: https://github.com/laurentj/slimerjs/issues/114
	//page.navigationLocked = !currentConfig.navigate;

	page.viewportSize = {
		width: currentConfig.width,
		height: currentConfig.height
	};

	page.settings.userAgent = currentConfig.useragent;
	page.settings.loadImages = currentConfig.loadimages;
	page.settings.javascriptEnabled = currentConfig.javascriptenabled;

	//queue up all the asynchronous and synchronous requests that the page will execute
	var pageRequests = [];

	//this stores the current url that openPage will be using
	var currentlyRequestedUrl = currentConfig.url;

	//the resource checking timer will be cleared when we're redirecting to a new page
	var resourceRequestsTimer = false;

	//this function is triggered to open a page with a specific url
	var openPage = function(url){

		console.log('Robot is opening a page to ' + url);

		//for triggering a max timeout for asynchronous requests
		var startingTime = '',
			currentTime = '',
			difference = '';

		page.open(url).then(function(status){

			if(status == 'success'){

				startingTime = Math.floor(Date.now());

				console.log('Robot has opened the page and loaded all synchronous requests');
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
					if(currentConfig.screenshot){
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
					resourceRequestsTimer = setTimeout(function(){
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

				console.log('Robot failed to open url: ' . currentConfig.url);
				output.message = 'Failed';
				outputResult(output, response);
				page.close();
				console.log('Robot has finished a task');

			}

		});

	};

	//deal with js sync & async redirection, html meta redirection and user actions
	//no effect on header redirects
	//beware on _blank pages, this is a bug in gecko
	page.onNavigationRequested = function(url, type, willNavigate, main){

		//if this navigation request is not the original request url and main and willNavigate is true
		//then we'll restart the page open process
		if(url != currentlyRequestedUrl && url.replace(/\/$/,"") != currentlyRequestedUrl){
			if(main && willNavigate){
				console.log('Robot is redirecting to ' + url);
				//reset the current state
				output = {
					status: '',
					headers: [],
					message: '',
					html: '',
					screenshot: '',
					date: ''
				};
				pageRequests = [];
				//cancel the asynchronous resource checker
				if(resourceRequestsTimer) clearTimeout(resourceRequestsTimer);
				//change the currentlyRequestedUrl to the redirection
				currentlyRequestedUrl = url;
				//close and reopen the page
				page.close();
				//we're still busy
				busy = true;
				//reopen the redirected page
				openPage(url);
			}
		}

	};

	page.onResourceRequested = function(resource){
		console.log('Robot is requesting: ' + resource.id + ' - ' + resource.url);
		pageRequests.push(resource.id);
	};

	page.onResourceReceived = function(resource){
		console.log('Robot received: ' + resource.id + ' with ' + resource.status + ' - ' + resource.url + ' at ' + resource.stage);
		if(resource.stage == 'end'){
			var index = pageRequests.indexOf(resource.id);
			if (index != -1) {
				pageRequests.splice(index, 1);
			}
			//the first resource would have any header redirections resolved and will always be the page's headers/status code
			if(resource.id == 1){
				//headers will be an array of objects {name:'', value: ''}
				output.headers = resource.headers;
				output.status = resource.status;
			}
		}
	};

	page.onResourceError = function(resource){
		console.log('Robot could not receive: ' + resource.url + ' due to ' + resource.errorCode + ':' + resource.errorString);
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

	//begin opening the page
	openPage(currentlyRequestedUrl);

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