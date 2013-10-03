//example CLI execution "phantomjs scraper.js url=http://blah width=1024 height= 800"

try {

	var config = {
		url: '',
		width: '1024',
		height: '800'
	};

	var args = require('system').args;

	//forEach is a callback, a recursion, so to skip the first loop we use return instead of continue
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

		if (key === 'url') config.url = propValue;
		if (key === 'width') config.width = propValue;
		if (key === 'height') config.height = propValue;

	});

	// Outputing the data back to the Node process - NOTE: Needs further work
	var outputData = function(output){
		console.log(JSON.stringify(output));
	};

	// Creating the page object representing the API to manipulate the page.
	var page = require('webpage').create();

	// Setting the viewportsize object properites
	page.viewportSize = {
		width: config.width,
		height: config.height
	};

	// Loading the page and executing the function on the onLoadFinished event
	page.open(config.url, function(status){
		if(status === 'success'){

			var screenshot, html;

			page.injectJs('ajax_tracker.js');

			page.evaluate(function(){

				var checkAjaxRequests = function(){
					setTimeout(function(){
						if (_ss_ajax_tracker.checkAjax()){
							screenshot = page.renderBase64('png');
							html = document.all[0].outerHTML;
						}else{
							checkAjaxRequests();
						}
					}, 500);
				};

				checkAjaxRequests();

			});

			outputData({
				screenshot: screenshot,
				html: html,
				date: Math.floor(Date.now()/1000) //convert to unix timestamp and round to highest seconds
			});

			phantom.exit();

		}else{
			console.log('Failed Loading!');
			phantom.exit();	
		}
	});

} catch(e) {

	console.log(e.name + ': ' + e.message);
	phantom.exit();

}