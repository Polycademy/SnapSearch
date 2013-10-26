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

//global script and page error handler
var logError = function(exception){
	var msg = exception.message + ' - ' + exception.fileName + ' - ' + exception.lineNumber + ' - ' + (new Date()).toString();
	console.log('Error: ' + msg);
	fs.write(defaultConfig.logfile, msg + "\n", 'a');
};

//start the web server
var service = server.listen(defaultConfig.ipaddress + ':' + defaultConfig.port, function(request, response){

	//support cliprect
	//support dom mutation due to animation

	//input comes in as a JSON object hash
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
		var input = request.post;
		input = JSON.parse(input);
	}catch(e){
		logError(e);
	}

	for(var key in input){
		defaultConfig[key] = input[key];
	}

	page.viewPort = {
		width: defaultConfig.width,
		height: defaultConfig.height
	};

	page.settings.userAgent = defaultConfig.useragent;
	page.settings.loadImages = defaultConfig.loadimages;
	page.settings.javascriptEnabled = defaultConfig.javascriptenabled;

	var beginExtraction = function(url){

		var requests = [],
			requestsComplete,
			redirectUrl = false;

		page.onNavigationRequested = function(newUrl, type, willNavigate, main){
			if(main && newUrl != url && newUrl.replace(/\/$/, '') != url && (type == 'Other' || type == 'Undefined')){
				redirectUrl = newUrl;
			}
		};

		page.onResourceRequested = function(resource){
			requests.push(resource.id);
			requestsComplete = false;
		};

		page.onResourceReceived = function(resource){
			if(resource.stage === 'end'){
				var index = requests.indexOf(resource.id);
				if(index != -1){
					requests.splice(index, 1);
				}
				if(requests.length == 0){
					requestsComplete = true;
				}
			}
		};

		page.onResourceError = function(resource){
			var index = requests.indexOf(resource.id);
			if(index != -1){
				requests.splice(index, 1);
			}
			if(requests.length == 0){
				requestsComplete = true;
			}
		};

		var outputData = function(message, html, screenshot, code){

			response.statusCode = code;

			var payload = {
				message: message,
				html: html,
				screenshot: screenshot,
				date: Math.floor(Date.now()/1000)
			};
			payload = JSON.stringify(payload);

			response.headers = {
				'Cache': 'no-cache',
				'Content-Type': 'application/json',
				'Content-Length': payload.length
			};

			response.write(payload);
			response.closeGracefully();

		};

		page.open(url).then(function(status){
			if(redirectUrl){

				page.close();
				beginExtraction(redirectUrl);
			
			}else if(status == 'success'){

				var evaluatePage = function(){

					var screenshot = '';
					
					//default background color of white, that can be overwritten by the browser
					page.evaluate(function() {
						var style = document.createElement('style'),
							text = document.createTextNode('body { background: #fff }');
						style.setAttribute('type', 'text/css');
						style.appendChild(text);
						document.head.insertBefore(style, document.head.firstChild);
					});

					if(defaultConfig.loadimages){
						screenshot = page.renderBase64(defaultConfig.imgformat);
					}

					var html = page.evaluate(function(){
						return document.all[0].outerHTML;
					});

					page.close();
					outputData('Success', html, screenshot, 200);

				};

				//wait for the requests to queue up
				var checkResouceRequests = function(){
					setTimeout(function(){
						if(requestsComplete){
							evaluatePage();
						}else{
							checkResouceRequests();
						}
					}, 500);
				};

				//initialise the check
				checkResouceRequests();

			}else{

				page.close();
				outputData('Failed to load URL: ' + defaultConfig.url, '', '', 400);
			
			}
		});

	};

	beginExtraction(defaultConfig.url);

});

//if the service didn't get created, close the process
if(service){
	console.log('SlimerJS Server Started at ' + defaultConfig.ipaddress + ':' + defaultConfig.port);
}else{
	console.log('Unable to start the webserver listening on ' + defaultConfig.ipaddress + ':' + defaultConfig.port);
	phantom.exit();
}