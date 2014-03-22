///////////////////
// BOOTSTRAPPING //
///////////////////

console.log('Robot is waking up');

//bootstrap
var fs = require('fs'),
    system = require('system'),
    server = require('webserver').create(),
    webpage = require('webpage'),
    eventbus = require('modules/eventbus');

//list of redirecting status codes
var redirectingStatusCodes = [
    '301',
    '302',
    '303',
    '305',
    '306',
    '307',
    '308',
    'null' //shim for null status https://github.com/laurentj/slimerjs/issues/167
];

//this needs to be placed in a module
var parseBooleanStyle = function(value){
    switch(value){
        case true:
        case 'true':
        case 1:
        case '1':
        case 'on':
        case 'yes':
            value = true;
            break;
        case false:
        case 'false':
        case 0:
        case '0':
        case 'off':
        case 'no':
            value = false;
            break;
        default:
            value = false;
            break;
    }
    return value;
};

//default configuration from initialising the process
var defaultConfig = {
    ipaddress: '127.0.0.1', 
    port: 8500, 
    numpages: 20,
    width: '1280', 
    height: '1024', //this can be used to facilitate scroll based pagination
    imgformat: 'png', 
    useragent: 'Mozilla/5.0 Gecko/20100101 Firefox/' + slimer.geckoVersion.major + '.' + slimer.geckoVersion.minor + '.' + slimer.geckoVersion.patch + ' SnapSearch', 
    screenshot: false, 
    navigate: false, // allow redirection of the page or not, if this is false, and there is a redirection, screenshots are not available
    loadimages: false, 
    javascriptenabled: true, 
    totaltimeout: 30000, //total task timeout before failing the request
    maxtimeout: 5000, //maximum timeout for asynchronous requests
    initialwait: 1000, //initial wait for asynchronous requests to fill up
    callback: false, 
    meta: true, //enable checking for meta tags to affect the headers or status code
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

    //some propValues may need to be converted from string boolean to boolean
    if (key === 'ipaddress') defaultConfig.ipaddress = propValue;
    if (key === 'port') defaultConfig.port = propValue;
    if (key === 'numpages') defaultConfig.numpages = propValue;
    if (key === 'width') defaultConfig.width = propValue;
    if (key === 'height') defaultConfig.height = propValue;
    if (key === 'imgformat') defaultConfig.imgformat = propValue;
    if (key === 'useragent') defaultConfig.useragent = propValue;
    if (key === 'screenshot') defaultConfig.screenshot = parseBooleanStyle(propValue);
    if (key === 'navigate') defaultConfig.navigate = parseBooleanStyle(propValue);
    if (key === 'loadimages') defaultConfig.loadimages = parseBooleanStyle(propValue);
    if (key === 'javascriptenabled') defaultConfig.javascriptenabled = parseBooleanStyle(propValue);
    if (key === 'totaltimeout') defaultConfig.totaltimeout = propValue;
    if (key === 'maxtimeout') defaultConfig.maxtimeout = propValue;
    if (key === 'initialwait') defaultConfig.initialwait = propValue;
    if (key === 'callback') defaultConfig.callback = propValue;
    if (key === 'meta') defaultConfig.meta = propValue;
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

//////////////////
//  ENTRY POINT //
//////////////////

//create a queue of tasks {request, response}
var tasks = [];
var currentTasks = 0;

var service = server.listen(defaultConfig.ipaddress + ':' + defaultConfig.port, function(request, response){

    console.log('Robot received a task');
    tasks.push({
        request: request,
        response: response
    });
    eventbus.dispatch('processTask');

});

//in case the service wasn't started
if(service){
    console.log('Robot server started at: ' + defaultConfig.ipaddress + ':' + defaultConfig.port);
}else{
    console.log('Unable to start robot server on ' + defaultConfig.ipaddress + ':' + defaultConfig.port);
    phantom.exit();
}

////////////////
// PROCESSING //
////////////////

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
        totaltimeout: 
        maxtimeout: //milliseconds on the maximum wait before timing out and rendering/return html snapshot
        initialwait: 
        callback: //string
        meta: 
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

/*
    Parse all the string booleans into booleans
 */
var parseBooleans = function(input){

    if ('screenshot' in input) input['screenshot'] = parseBooleanStyle(input['screenshot']);
    if ('navigate' in input) input['navigate'] = parseBooleanStyle(input['navigate']);
    if ('loadimages' in input) input['loadimages'] = parseBooleanStyle(input['loadimages']);
    if ('javascriptenabled' in input) input['javascriptenabled'] = parseBooleanStyle(input['javascriptenabled']);
    if ('meta' in input) input['meta'] = parseBooleanStyle(input['meta']);

    return input;

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

    currentTasks++;

    var page = webpage.create();

    var request = task.request, 
        response = task.response, 
        input = parseInputJson(request.post), 
        output = {
            status: '',
            headers: [],
            message: '',
            html: '',
            screenshot: '',
            date: '',
            callbackResult: '',
            pageErrors: []
        }, 
        currentConfig = JSON.parse(JSON.stringify(defaultConfig)); //clone the object

    if(!input){
        output.message = 'Input was not valid JSON';
        outputResult(output, response);
        page.close();
        return false;
    }
    
    //configuration needs to be isolated for the current request
    for(var key in input){
        currentConfig[key] = input[key];
    }

    //parse all string booleans into booleans
    currentConfig = parseBooleans(currentConfig);

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

    //is the current resource redirecting, only used for the initial page redirections
    var isRedirecting = false;

    //the resource checking timer will be cleared when we're redirecting to a new page
    var resourceRequestsTimer = false;

    var numberOfRedirects = 0;

    var failedOpeningPage = function(){

        console.log('Robot failed to open url: ' + currentConfig.url);
        output.message = 'Failed';
        outputResult(output, response);
        page.close();

    };

    //this is to prevent pages from taking too long to open
    var pageOpened = false;

    //30s for page opening time
    setTimeout(function(){
        if(pageOpened == false){
            console.log('Robot has exceeded maximum synchronous page opening time');
            failedOpeningPage();
        }
    }, currentConfig.totaltimeout);

    //this function is triggered to open a page with a specific url
    var openPage = function(url){

        console.log('Robot is opening a page to ' + url);

        //for triggering a max timeout for asynchronous requests
        var startingTime = '',
            currentTime = '',
            difference = '';

        page.open(url).then(function(status){

            //yay page has been opened
            pageOpened = true;

            if(status == 'success'){

                startingTime = Math.floor(Date.now());

                console.log('Robot has opened the page and loaded all synchronous requests');
                console.log('Robot is waiting for asynchronous requests to fill up');

                var evaluatePage = function(){

                    console.log('Robot has loaded all asynchronous requests or requests have hit max timeout');

                    //retrieve SnapSearch specific meta tags to overwrite headers or status code
                    if(currentConfig.meta){

                        console.log('Robot is looking for SnapSearch specific meta tags');

                        //find <meta name="snapsearch-status" content="301" />
                        var metaStatus = page.evaluate(function(){

                            var tag = document.querySelector('meta[name=snapsearch-status]');

                            if(tag){
                                return tag.content;
                            }

                        });

                        //find <meta name="snapsearch-header" content="Content-Type:text/html" />
                        var metaHeaders = page.evaluate(function(){

                            var headers = [];
                            var tags = document.querySelectorAll('meta[name=snapsearch-header]');

                            if(tags){

                                //can only return string type elements, no forEach on NodeList
                                for(var i = 0, j = tags.length; i < j; i++){
                                    if(tags[i].content){
                                        headers.push(tags[i].content);
                                    }
                                }

                                return headers;

                            }

                        });

                        if(metaStatus){

                            console.log('Robot overwrote status code with meta status code of ' + metaStatus);
                            output.status = metaStatus;

                        }

                        if(metaHeaders && metaHeaders.length > 0){

                            console.log('Robot is adding headers from meta headers');

                            metaHeaders.forEach(function(headerString){

                                var headerParts = headerString.split(':');
                                var headerName = headerParts.shift();
                                var headerValue = headerParts.join(':');

                                if(headerName && headerValue){

                                    var headerIndex = false;
                                    for(var i = 0, j = output.headers.length; i < j; i++){
                                        if(output.headers[i].name === headerName){
                                            headerIndex = i;
                                            break;
                                        }
                                    }

                                    if(typeof headerIndex === 'number'){

                                        output.headers[headerIndex] = {
                                            name: headerName,
                                            value: headerValue
                                        };

                                    }else{

                                        output.headers.push({
                                            name: headerName,
                                            value: headerValue
                                        });

                                    }

                                }

                            });

                        }

                    }

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
                        var callbackResult = page.evaluate(function(callback){
                            return new Function(callback)();
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
                    output.callbackResult = callbackResult;

                    outputResult(output, response);
                    page.close();

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

                failedOpeningPage();

            }

        });

    };

    //only if redirects are allowed do we listen for client side redirections
    if(currentConfig.navigate){

        //deal with js sync & async redirection, html meta redirection and user actions
        //no effect on header redirects
        //beware on _blank pages, this is a bug in gecko
        page.onNavigationRequested = function(url, type, willNavigate, main){
            //if this navigation request is not the original request url and main and willNavigate is true
            //then we'll restart the page open process
            if(url != currentlyRequestedUrl && url.replace(/\/$/,"") != currentlyRequestedUrl){
                if(main && willNavigate){
                    console.log('Robot is executing client side redirection to ' + url);
                    numberOfRedirects++;
                    //reset the current state
                    output = {
                        status: '',
                        headers: [],
                        message: '',
                        html: '',
                        screenshot: '',
                        date: '',
                        callbackResult: '',
                        pageErrors: []
                    };
                    pageRequests = [];
                    isRedirecting = false;
                    //cancel the asynchronous resource checker
                    if(resourceRequestsTimer){
                        clearTimeout(resourceRequestsTimer);
                        resourceRequestsTimer = false;
                    }
                    //change the currentlyRequestedUrl to the redirection
                    currentlyRequestedUrl = url;
                    //if the number of redirects is greater than 10, we need to fail the page instead of reopening
                    if(numberOfRedirects > 10){ 
                        console.log('Robot has exceeded client side redirection limit');
                        failedOpeningPage();
                    }else{
                        //close and reopen the page, make sure we're not dispatching the processTask event
                        page.close(false);
                        //reopen the redirected page
                        openPage(url);
                    }
                }
            }
        };

    }

    page.onResourceRequested = function(resource){
        console.log('Robot is requesting: ' + resource.id + ' - ' + resource.url);
        pageRequests.push(resource.id);
    };

    page.onResourceReceived = function(resource){
        console.log('Robot received: ' + resource.id + ' with ' + resource.status + ' - ' + resource.url + ' at ' + resource.stage);
        if(resource.stage == 'end'){
            var index = pageRequests.indexOf(resource.id);
            if(index != -1){
                pageRequests.splice(index, 1);
            }

            //upon first resource, we are going to check if the resource is redirecting and switch on isRedirecting
            //on each subsequent resource, if the previous resource was redirecting with isRedirecting being true,
            //then we're going to replace the output's headers and status code with the current resource
            //this will iterate until the first occurrence of a status code that does not redirect which will switch off isRedirect, this resource is also the final resolved resource that has the "true" status code and headers for the output
            if(resource.id == 1){

                output.status = resource.status;
                output.headers = resource.headers;

                //cast to to a string first
                var status = resource.status + '';
                if(redirectingStatusCodes.indexOf(status) !== -1){
                    console.log('Robot is starting header redirection');
                    isRedirecting = true;
                }

                //if no redirects are allowed, and the first request is redirecting, we're going to end this task here
                if(isRedirecting && !currentConfig.navigate){
                    console.log('Robot is intercepting this header redirect as redirects are not allowed, and outputting the redirect information');
                    //page is opened here
                    pageOpened = true;
                    //screenshots are not available for redirections
                    output = {
                        status: resource.status,
                        headers: resource.headers,
                        message: 'Success',
                        html: resource.body
                    };
                    outputResult(output, response);
                    page.close();
                }

            }else if(isRedirecting){

                //we only check if isRedirecting is true after the first resource has already been resolved
                output.status = resource.status;
                output.headers = resource.headers;

                //if is not redirecting, we're going to flip off redirecting
                if(redirectingStatusCodes.indexOf(resource.status.toString()) === -1){
                    console.log('Robot has finished header redirection');
                    isRedirecting = false;
                }

            }

        }
    };

    page.onResourceError = function(resource){
        console.log('Robot could not receive: ' + resource.id + ' - ' + resource.url + ' due to ' + resource.errorCode + ':' + resource.errorString);
        var index = pageRequests.indexOf(resource.id);
        if (index != -1) {
            pageRequests.splice(index, 1);
        }
    };

    // Resource timeouts not yet supported
    page.onResourceTimeout = function(resource){
        console.log('Robot could not receive: ' + resource.id + ' - ' + resource.url + ' due to ' + resource.errorCode + ':' + resource.errorString);
        var index = pageRequests.indexOf(resource.id);
        if (index != -1) {
            pageRequests.splice(index, 1);
        }
    };

    page.onError = function(message, trace){

        console.log('Robot is recording javascript errors on the web page');
        output.pageErrors.push({
            error: message,
            trace: trace
        });

    };

    //once the page has been closed, then we are free to do more work
    page.onClosing = function(next){

        //by default we will go to the next task
        next = (typeof next !== 'undefined') ? next : true;

    	//shift out the current task, opening a position for a new task
    	currentTasks--;

        console.log('Robot has finished a task');

        console.log('Robot has closed the page');

        if(next){
            eventbus.dispatch('processTask');
        }

    };

    //begin opening the page
    openPage(currentlyRequestedUrl);

};

//every time we get a new task, or when the current task finishes, this gets ran (also when the current task fails)
eventbus.addEventListener('processTask', function () {

	//if there are tasks and that the currentTasks are less than the concurrent page limit
    if (tasks.length > 0 && currentTasks <= defaultConfig.numpages) {
        console.log('There are ' + tasks.length + ' tasks in the queue');
        var task = tasks.shift();
        processTask(task);
    }

});