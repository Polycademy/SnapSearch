(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);throw new Error("Cannot find module '"+o+"'")}var f=n[o]={exports:{}};t[o][0].call(f.exports,function(e){var n=t[o][1][e];return s(n?n:e)},f,f.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){

},{}],2:[function(require,module,exports){
/*!
 * EventEmitter v4.2.7 - git.io/ee
 * Oliver Caldwell
 * MIT license
 * @preserve
 */

(function () {
	'use strict';

	/**
	 * Class for managing events.
	 * Can be extended to provide event functionality in other classes.
	 *
	 * @class EventEmitter Manages event registering and emitting.
	 */
	function EventEmitter() {}

	// Shortcuts to improve speed and size
	var proto = EventEmitter.prototype;
	var exports = this;
	var originalGlobalValue = exports.EventEmitter;

	/**
	 * Finds the index of the listener for the event in it's storage array.
	 *
	 * @param {Function[]} listeners Array of listeners to search through.
	 * @param {Function} listener Method to look for.
	 * @return {Number} Index of the specified listener, -1 if not found
	 * @api private
	 */
	function indexOfListener(listeners, listener) {
		var i = listeners.length;
		while (i--) {
			if (listeners[i].listener === listener) {
				return i;
			}
		}

		return -1;
	}

	/**
	 * Alias a method while keeping the context correct, to allow for overwriting of target method.
	 *
	 * @param {String} name The name of the target method.
	 * @return {Function} The aliased method
	 * @api private
	 */
	function alias(name) {
		return function aliasClosure() {
			return this[name].apply(this, arguments);
		};
	}

	/**
	 * Returns the listener array for the specified event.
	 * Will initialise the event object and listener arrays if required.
	 * Will return an object if you use a regex search. The object contains keys for each matched event. So /ba[rz]/ might return an object containing bar and baz. But only if you have either defined them with defineEvent or added some listeners to them.
	 * Each property in the object response is an array of listener functions.
	 *
	 * @param {String|RegExp} evt Name of the event to return the listeners from.
	 * @return {Function[]|Object} All listener functions for the event.
	 */
	proto.getListeners = function getListeners(evt) {
		var events = this._getEvents();
		var response;
		var key;

		// Return a concatenated array of all matching events if
		// the selector is a regular expression.
		if (evt instanceof RegExp) {
			response = {};
			for (key in events) {
				if (events.hasOwnProperty(key) && evt.test(key)) {
					response[key] = events[key];
				}
			}
		}
		else {
			response = events[evt] || (events[evt] = []);
		}

		return response;
	};

	/**
	 * Takes a list of listener objects and flattens it into a list of listener functions.
	 *
	 * @param {Object[]} listeners Raw listener objects.
	 * @return {Function[]} Just the listener functions.
	 */
	proto.flattenListeners = function flattenListeners(listeners) {
		var flatListeners = [];
		var i;

		for (i = 0; i < listeners.length; i += 1) {
			flatListeners.push(listeners[i].listener);
		}

		return flatListeners;
	};

	/**
	 * Fetches the requested listeners via getListeners but will always return the results inside an object. This is mainly for internal use but others may find it useful.
	 *
	 * @param {String|RegExp} evt Name of the event to return the listeners from.
	 * @return {Object} All listener functions for an event in an object.
	 */
	proto.getListenersAsObject = function getListenersAsObject(evt) {
		var listeners = this.getListeners(evt);
		var response;

		if (listeners instanceof Array) {
			response = {};
			response[evt] = listeners;
		}

		return response || listeners;
	};

	/**
	 * Adds a listener function to the specified event.
	 * The listener will not be added if it is a duplicate.
	 * If the listener returns true then it will be removed after it is called.
	 * If you pass a regular expression as the event name then the listener will be added to all events that match it.
	 *
	 * @param {String|RegExp} evt Name of the event to attach the listener to.
	 * @param {Function} listener Method to be called when the event is emitted. If the function returns true then it will be removed after calling.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.addListener = function addListener(evt, listener) {
		var listeners = this.getListenersAsObject(evt);
		var listenerIsWrapped = typeof listener === 'object';
		var key;

		for (key in listeners) {
			if (listeners.hasOwnProperty(key) && indexOfListener(listeners[key], listener) === -1) {
				listeners[key].push(listenerIsWrapped ? listener : {
					listener: listener,
					once: false
				});
			}
		}

		return this;
	};

	/**
	 * Alias of addListener
	 */
	proto.on = alias('addListener');

	/**
	 * Semi-alias of addListener. It will add a listener that will be
	 * automatically removed after it's first execution.
	 *
	 * @param {String|RegExp} evt Name of the event to attach the listener to.
	 * @param {Function} listener Method to be called when the event is emitted. If the function returns true then it will be removed after calling.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.addOnceListener = function addOnceListener(evt, listener) {
		return this.addListener(evt, {
			listener: listener,
			once: true
		});
	};

	/**
	 * Alias of addOnceListener.
	 */
	proto.once = alias('addOnceListener');

	/**
	 * Defines an event name. This is required if you want to use a regex to add a listener to multiple events at once. If you don't do this then how do you expect it to know what event to add to? Should it just add to every possible match for a regex? No. That is scary and bad.
	 * You need to tell it what event names should be matched by a regex.
	 *
	 * @param {String} evt Name of the event to create.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.defineEvent = function defineEvent(evt) {
		this.getListeners(evt);
		return this;
	};

	/**
	 * Uses defineEvent to define multiple events.
	 *
	 * @param {String[]} evts An array of event names to define.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.defineEvents = function defineEvents(evts) {
		for (var i = 0; i < evts.length; i += 1) {
			this.defineEvent(evts[i]);
		}
		return this;
	};

	/**
	 * Removes a listener function from the specified event.
	 * When passed a regular expression as the event name, it will remove the listener from all events that match it.
	 *
	 * @param {String|RegExp} evt Name of the event to remove the listener from.
	 * @param {Function} listener Method to remove from the event.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.removeListener = function removeListener(evt, listener) {
		var listeners = this.getListenersAsObject(evt);
		var index;
		var key;

		for (key in listeners) {
			if (listeners.hasOwnProperty(key)) {
				index = indexOfListener(listeners[key], listener);

				if (index !== -1) {
					listeners[key].splice(index, 1);
				}
			}
		}

		return this;
	};

	/**
	 * Alias of removeListener
	 */
	proto.off = alias('removeListener');

	/**
	 * Adds listeners in bulk using the manipulateListeners method.
	 * If you pass an object as the second argument you can add to multiple events at once. The object should contain key value pairs of events and listeners or listener arrays. You can also pass it an event name and an array of listeners to be added.
	 * You can also pass it a regular expression to add the array of listeners to all events that match it.
	 * Yeah, this function does quite a bit. That's probably a bad thing.
	 *
	 * @param {String|Object|RegExp} evt An event name if you will pass an array of listeners next. An object if you wish to add to multiple events at once.
	 * @param {Function[]} [listeners] An optional array of listener functions to add.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.addListeners = function addListeners(evt, listeners) {
		// Pass through to manipulateListeners
		return this.manipulateListeners(false, evt, listeners);
	};

	/**
	 * Removes listeners in bulk using the manipulateListeners method.
	 * If you pass an object as the second argument you can remove from multiple events at once. The object should contain key value pairs of events and listeners or listener arrays.
	 * You can also pass it an event name and an array of listeners to be removed.
	 * You can also pass it a regular expression to remove the listeners from all events that match it.
	 *
	 * @param {String|Object|RegExp} evt An event name if you will pass an array of listeners next. An object if you wish to remove from multiple events at once.
	 * @param {Function[]} [listeners] An optional array of listener functions to remove.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.removeListeners = function removeListeners(evt, listeners) {
		// Pass through to manipulateListeners
		return this.manipulateListeners(true, evt, listeners);
	};

	/**
	 * Edits listeners in bulk. The addListeners and removeListeners methods both use this to do their job. You should really use those instead, this is a little lower level.
	 * The first argument will determine if the listeners are removed (true) or added (false).
	 * If you pass an object as the second argument you can add/remove from multiple events at once. The object should contain key value pairs of events and listeners or listener arrays.
	 * You can also pass it an event name and an array of listeners to be added/removed.
	 * You can also pass it a regular expression to manipulate the listeners of all events that match it.
	 *
	 * @param {Boolean} remove True if you want to remove listeners, false if you want to add.
	 * @param {String|Object|RegExp} evt An event name if you will pass an array of listeners next. An object if you wish to add/remove from multiple events at once.
	 * @param {Function[]} [listeners] An optional array of listener functions to add/remove.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.manipulateListeners = function manipulateListeners(remove, evt, listeners) {
		var i;
		var value;
		var single = remove ? this.removeListener : this.addListener;
		var multiple = remove ? this.removeListeners : this.addListeners;

		// If evt is an object then pass each of it's properties to this method
		if (typeof evt === 'object' && !(evt instanceof RegExp)) {
			for (i in evt) {
				if (evt.hasOwnProperty(i) && (value = evt[i])) {
					// Pass the single listener straight through to the singular method
					if (typeof value === 'function') {
						single.call(this, i, value);
					}
					else {
						// Otherwise pass back to the multiple function
						multiple.call(this, i, value);
					}
				}
			}
		}
		else {
			// So evt must be a string
			// And listeners must be an array of listeners
			// Loop over it and pass each one to the multiple method
			i = listeners.length;
			while (i--) {
				single.call(this, evt, listeners[i]);
			}
		}

		return this;
	};

	/**
	 * Removes all listeners from a specified event.
	 * If you do not specify an event then all listeners will be removed.
	 * That means every event will be emptied.
	 * You can also pass a regex to remove all events that match it.
	 *
	 * @param {String|RegExp} [evt] Optional name of the event to remove all listeners for. Will remove from every event if not passed.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.removeEvent = function removeEvent(evt) {
		var type = typeof evt;
		var events = this._getEvents();
		var key;

		// Remove different things depending on the state of evt
		if (type === 'string') {
			// Remove all listeners for the specified event
			delete events[evt];
		}
		else if (evt instanceof RegExp) {
			// Remove all events matching the regex.
			for (key in events) {
				if (events.hasOwnProperty(key) && evt.test(key)) {
					delete events[key];
				}
			}
		}
		else {
			// Remove all listeners in all events
			delete this._events;
		}

		return this;
	};

	/**
	 * Alias of removeEvent.
	 *
	 * Added to mirror the node API.
	 */
	proto.removeAllListeners = alias('removeEvent');

	/**
	 * Emits an event of your choice.
	 * When emitted, every listener attached to that event will be executed.
	 * If you pass the optional argument array then those arguments will be passed to every listener upon execution.
	 * Because it uses `apply`, your array of arguments will be passed as if you wrote them out separately.
	 * So they will not arrive within the array on the other side, they will be separate.
	 * You can also pass a regular expression to emit to all events that match it.
	 *
	 * @param {String|RegExp} evt Name of the event to emit and execute listeners for.
	 * @param {Array} [args] Optional array of arguments to be passed to each listener.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.emitEvent = function emitEvent(evt, args) {
		var listeners = this.getListenersAsObject(evt);
		var listener;
		var i;
		var key;
		var response;

		for (key in listeners) {
			if (listeners.hasOwnProperty(key)) {
				i = listeners[key].length;

				while (i--) {
					// If the listener returns true then it shall be removed from the event
					// The function is executed either with a basic call or an apply if there is an args array
					listener = listeners[key][i];

					if (listener.once === true) {
						this.removeListener(evt, listener.listener);
					}

					response = listener.listener.apply(this, args || []);

					if (response === this._getOnceReturnValue()) {
						this.removeListener(evt, listener.listener);
					}
				}
			}
		}

		return this;
	};

	/**
	 * Alias of emitEvent
	 */
	proto.trigger = alias('emitEvent');

	/**
	 * Subtly different from emitEvent in that it will pass its arguments on to the listeners, as opposed to taking a single array of arguments to pass on.
	 * As with emitEvent, you can pass a regex in place of the event name to emit to all events that match it.
	 *
	 * @param {String|RegExp} evt Name of the event to emit and execute listeners for.
	 * @param {...*} Optional additional arguments to be passed to each listener.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.emit = function emit(evt) {
		var args = Array.prototype.slice.call(arguments, 1);
		return this.emitEvent(evt, args);
	};

	/**
	 * Sets the current value to check against when executing listeners. If a
	 * listeners return value matches the one set here then it will be removed
	 * after execution. This value defaults to true.
	 *
	 * @param {*} value The new value to check for when executing listeners.
	 * @return {Object} Current instance of EventEmitter for chaining.
	 */
	proto.setOnceReturnValue = function setOnceReturnValue(value) {
		this._onceReturnValue = value;
		return this;
	};

	/**
	 * Fetches the current value to check against when executing listeners. If
	 * the listeners return value matches this one then it should be removed
	 * automatically. It will return true by default.
	 *
	 * @return {*|Boolean} The current value to check for or the default, true.
	 * @api private
	 */
	proto._getOnceReturnValue = function _getOnceReturnValue() {
		if (this.hasOwnProperty('_onceReturnValue')) {
			return this._onceReturnValue;
		}
		else {
			return true;
		}
	};

	/**
	 * Fetches the events object and creates one if required.
	 *
	 * @return {Object} The events storage object.
	 * @api private
	 */
	proto._getEvents = function _getEvents() {
		return this._events || (this._events = {});
	};

	/**
	 * Reverts the global {@link EventEmitter} to its previous value and returns a reference to this version.
	 *
	 * @return {Function} Non conflicting EventEmitter class.
	 */
	EventEmitter.noConflict = function noConflict() {
		exports.EventEmitter = originalGlobalValue;
		return EventEmitter;
	};

	// Expose the class either via AMD, CommonJS or the global object
	if (typeof define === 'function' && define.amd) {
		define(function () {
			return EventEmitter;
		});
	}
	else if (typeof module === 'object' && module.exports){
		module.exports = EventEmitter;
	}
	else {
		this.EventEmitter = EventEmitter;
	}
}.call(this));

},{}],3:[function(require,module,exports){
/*!
 * eventie v1.0.5
 * event binding helper
 *   eventie.bind( elem, 'click', myFn )
 *   eventie.unbind( elem, 'click', myFn )
 * MIT license
 */

/*jshint browser: true, undef: true, unused: true */
/*global define: false, module: false */

( function( window ) {

'use strict';

var docElem = document.documentElement;

var bind = function() {};

function getIEEvent( obj ) {
  var event = window.event;
  // add event.target
  event.target = event.target || event.srcElement || obj;
  return event;
}

if ( docElem.addEventListener ) {
  bind = function( obj, type, fn ) {
    obj.addEventListener( type, fn, false );
  };
} else if ( docElem.attachEvent ) {
  bind = function( obj, type, fn ) {
    obj[ type + fn ] = fn.handleEvent ?
      function() {
        var event = getIEEvent( obj );
        fn.handleEvent.call( fn, event );
      } :
      function() {
        var event = getIEEvent( obj );
        fn.call( obj, event );
      };
    obj.attachEvent( "on" + type, obj[ type + fn ] );
  };
}

var unbind = function() {};

if ( docElem.removeEventListener ) {
  unbind = function( obj, type, fn ) {
    obj.removeEventListener( type, fn, false );
  };
} else if ( docElem.detachEvent ) {
  unbind = function( obj, type, fn ) {
    obj.detachEvent( "on" + type, obj[ type + fn ] );
    try {
      delete obj[ type + fn ];
    } catch ( err ) {
      // can't delete window object properties
      obj[ type + fn ] = undefined;
    }
  };
}

var eventie = {
  bind: bind,
  unbind: unbind
};

// ----- module definition ----- //

if ( typeof define === 'function' && define.amd ) {
  // AMD
  define( eventie );
} else if ( typeof exports === 'object' ) {
  // CommonJS
  module.exports = eventie;
} else {
  // browser global
  window.eventie = eventie;
}

})( this );

},{}],4:[function(require,module,exports){
/*!
 * imagesLoaded v3.1.4
 * JavaScript is all like "You images are done yet or what?"
 * MIT License
 */

( function( window, factory ) { 'use strict';
  // universal module definition

  /*global define: false, module: false, require: false */

  if ( typeof define === 'function' && define.amd ) {
    // AMD
    define( [
      'eventEmitter/EventEmitter',
      'eventie/eventie'
    ], function( EventEmitter, eventie ) {
      return factory( window, EventEmitter, eventie );
    });
  } else if ( typeof exports === 'object' ) {
    // CommonJS
    module.exports = factory(
      window,
      require("./..\\eventEmitter\\EventEmitter.js"),
      require("./..\\eventie\\eventie.js")
    );
  } else {
    // browser global
    window.imagesLoaded = factory(
      window,
      window.EventEmitter,
      window.eventie
    );
  }

})( this,

// --------------------------  factory -------------------------- //

function factory( window, EventEmitter, eventie ) {

'use strict';

var $ = window.jQuery;
var console = window.console;
var hasConsole = typeof console !== 'undefined';

// -------------------------- helpers -------------------------- //

// extend objects
function extend( a, b ) {
  for ( var prop in b ) {
    a[ prop ] = b[ prop ];
  }
  return a;
}

var objToString = Object.prototype.toString;
function isArray( obj ) {
  return objToString.call( obj ) === '[object Array]';
}

// turn element or nodeList into an array
function makeArray( obj ) {
  var ary = [];
  if ( isArray( obj ) ) {
    // use object if already an array
    ary = obj;
  } else if ( typeof obj.length === 'number' ) {
    // convert nodeList to array
    for ( var i=0, len = obj.length; i < len; i++ ) {
      ary.push( obj[i] );
    }
  } else {
    // array of single index
    ary.push( obj );
  }
  return ary;
}

  // -------------------------- imagesLoaded -------------------------- //

  /**
   * @param {Array, Element, NodeList, String} elem
   * @param {Object or Function} options - if function, use as callback
   * @param {Function} onAlways - callback function
   */
  function ImagesLoaded( elem, options, onAlways ) {
    // coerce ImagesLoaded() without new, to be new ImagesLoaded()
    if ( !( this instanceof ImagesLoaded ) ) {
      return new ImagesLoaded( elem, options );
    }
    // use elem as selector string
    if ( typeof elem === 'string' ) {
      elem = document.querySelectorAll( elem );
    }

    this.elements = makeArray( elem );
    this.options = extend( {}, this.options );

    if ( typeof options === 'function' ) {
      onAlways = options;
    } else {
      extend( this.options, options );
    }

    if ( onAlways ) {
      this.on( 'always', onAlways );
    }

    this.getImages();

    if ( $ ) {
      // add jQuery Deferred object
      this.jqDeferred = new $.Deferred();
    }

    // HACK check async to allow time to bind listeners
    var _this = this;
    setTimeout( function() {
      _this.check();
    });
  }

  ImagesLoaded.prototype = new EventEmitter();

  ImagesLoaded.prototype.options = {};

  ImagesLoaded.prototype.getImages = function() {
    this.images = [];

    // filter & find items if we have an item selector
    for ( var i=0, len = this.elements.length; i < len; i++ ) {
      var elem = this.elements[i];
      // filter siblings
      if ( elem.nodeName === 'IMG' ) {
        this.addImage( elem );
      }
      // find children
      var childElems = elem.querySelectorAll('img');
      // concat childElems to filterFound array
      for ( var j=0, jLen = childElems.length; j < jLen; j++ ) {
        var img = childElems[j];
        this.addImage( img );
      }
    }
  };

  /**
   * @param {Image} img
   */
  ImagesLoaded.prototype.addImage = function( img ) {
    var loadingImage = new LoadingImage( img );
    this.images.push( loadingImage );
  };

  ImagesLoaded.prototype.check = function() {
    var _this = this;
    var checkedCount = 0;
    var length = this.images.length;
    this.hasAnyBroken = false;
    // complete if no images
    if ( !length ) {
      this.complete();
      return;
    }

    function onConfirm( image, message ) {
      if ( _this.options.debug && hasConsole ) {
        console.log( 'confirm', image, message );
      }

      _this.progress( image );
      checkedCount++;
      if ( checkedCount === length ) {
        _this.complete();
      }
      return true; // bind once
    }

    for ( var i=0; i < length; i++ ) {
      var loadingImage = this.images[i];
      loadingImage.on( 'confirm', onConfirm );
      loadingImage.check();
    }
  };

  ImagesLoaded.prototype.progress = function( image ) {
    this.hasAnyBroken = this.hasAnyBroken || !image.isLoaded;
    // HACK - Chrome triggers event before object properties have changed. #83
    var _this = this;
    setTimeout( function() {
      _this.emit( 'progress', _this, image );
      if ( _this.jqDeferred && _this.jqDeferred.notify ) {
        _this.jqDeferred.notify( _this, image );
      }
    });
  };

  ImagesLoaded.prototype.complete = function() {
    var eventName = this.hasAnyBroken ? 'fail' : 'done';
    this.isComplete = true;
    var _this = this;
    // HACK - another setTimeout so that confirm happens after progress
    setTimeout( function() {
      _this.emit( eventName, _this );
      _this.emit( 'always', _this );
      if ( _this.jqDeferred ) {
        var jqMethod = _this.hasAnyBroken ? 'reject' : 'resolve';
        _this.jqDeferred[ jqMethod ]( _this );
      }
    });
  };

  // -------------------------- jquery -------------------------- //

  if ( $ ) {
    $.fn.imagesLoaded = function( options, callback ) {
      var instance = new ImagesLoaded( this, options, callback );
      return instance.jqDeferred.promise( $(this) );
    };
  }


  // --------------------------  -------------------------- //

  function LoadingImage( img ) {
    this.img = img;
  }

  LoadingImage.prototype = new EventEmitter();

  LoadingImage.prototype.check = function() {
    // first check cached any previous images that have same src
    var resource = cache[ this.img.src ] || new Resource( this.img.src );
    if ( resource.isConfirmed ) {
      this.confirm( resource.isLoaded, 'cached was confirmed' );
      return;
    }

    // If complete is true and browser supports natural sizes,
    // try to check for image status manually.
    if ( this.img.complete && this.img.naturalWidth !== undefined ) {
      // report based on naturalWidth
      this.confirm( this.img.naturalWidth !== 0, 'naturalWidth' );
      return;
    }

    // If none of the checks above matched, simulate loading on detached element.
    var _this = this;
    resource.on( 'confirm', function( resrc, message ) {
      _this.confirm( resrc.isLoaded, message );
      return true;
    });

    resource.check();
  };

  LoadingImage.prototype.confirm = function( isLoaded, message ) {
    this.isLoaded = isLoaded;
    this.emit( 'confirm', this, message );
  };

  // -------------------------- Resource -------------------------- //

  // Resource checks each src, only once
  // separate class from LoadingImage to prevent memory leaks. See #115

  var cache = {};

  function Resource( src ) {
    this.src = src;
    // add to cache
    cache[ src ] = this;
  }

  Resource.prototype = new EventEmitter();

  Resource.prototype.check = function() {
    // only trigger checking once
    if ( this.isChecked ) {
      return;
    }
    // simulate loading on detached element
    var proxyImage = new Image();
    eventie.bind( proxyImage, 'load', this );
    eventie.bind( proxyImage, 'error', this );
    proxyImage.src = this.src;
    // set flag
    this.isChecked = true;
  };

  // ----- events ----- //

  // trigger specified handler for event type
  Resource.prototype.handleEvent = function( event ) {
    var method = 'on' + event.type;
    if ( this[ method ] ) {
      this[ method ]( event );
    }
  };

  Resource.prototype.onload = function( event ) {
    this.confirm( true, 'onload' );
    this.unbindProxyEvents( event );
  };

  Resource.prototype.onerror = function( event ) {
    this.confirm( false, 'onerror' );
    this.unbindProxyEvents( event );
  };

  // ----- confirm ----- //

  Resource.prototype.confirm = function( isLoaded, message ) {
    this.isConfirmed = true;
    this.isLoaded = isLoaded;
    this.emit( 'confirm', this, message );
  };

  Resource.prototype.unbindProxyEvents = function( event ) {
    eventie.unbind( event.target, 'load', this );
    eventie.unbind( event.target, 'error', this );
  };

  // -----  ----- //

  return ImagesLoaded;

});

},{"./..\\eventEmitter\\EventEmitter.js":2,"./..\\eventie\\eventie.js":3}],5:[function(require,module,exports){
//! moment.js
//! version : 2.4.0
//! authors : Tim Wood, Iskren Chernev, Moment.js contributors
//! license : MIT
//! momentjs.com

(function (undefined) {

    /************************************
        Constants
    ************************************/

    var moment,
        VERSION = "2.4.0",
        round = Math.round,
        i,

        YEAR = 0,
        MONTH = 1,
        DATE = 2,
        HOUR = 3,
        MINUTE = 4,
        SECOND = 5,
        MILLISECOND = 6,

        // internal storage for language config files
        languages = {},

        // check for nodeJS
        hasModule = (typeof module !== 'undefined' && module.exports),

        // ASP.NET json date format regex
        aspNetJsonRegex = /^\/?Date\((\-?\d+)/i,
        aspNetTimeSpanJsonRegex = /(\-)?(?:(\d*)\.)?(\d+)\:(\d+)(?:\:(\d+)\.?(\d{3})?)?/,

        // from http://docs.closure-library.googlecode.com/git/closure_goog_date_date.js.source.html
        // somewhat more in line with 4.4.3.2 2004 spec, but allows decimal anywhere
        isoDurationRegex = /^(-)?P(?:(?:([0-9,.]*)Y)?(?:([0-9,.]*)M)?(?:([0-9,.]*)D)?(?:T(?:([0-9,.]*)H)?(?:([0-9,.]*)M)?(?:([0-9,.]*)S)?)?|([0-9,.]*)W)$/,

        // format tokens
        formattingTokens = /(\[[^\[]*\])|(\\)?(Mo|MM?M?M?|Do|DDDo|DD?D?D?|ddd?d?|do?|w[o|w]?|W[o|W]?|YYYYY|YYYY|YY|gg(ggg?)?|GG(GGG?)?|e|E|a|A|hh?|HH?|mm?|ss?|S{1,4}|X|zz?|ZZ?|.)/g,
        localFormattingTokens = /(\[[^\[]*\])|(\\)?(LT|LL?L?L?|l{1,4})/g,

        // parsing token regexes
        parseTokenOneOrTwoDigits = /\d\d?/, // 0 - 99
        parseTokenOneToThreeDigits = /\d{1,3}/, // 0 - 999
        parseTokenThreeDigits = /\d{3}/, // 000 - 999
        parseTokenFourDigits = /\d{1,4}/, // 0 - 9999
        parseTokenSixDigits = /[+\-]?\d{1,6}/, // -999,999 - 999,999
        parseTokenDigits = /\d+/, // nonzero number of digits
        parseTokenWord = /[0-9]*['a-z\u00A0-\u05FF\u0700-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+|[\u0600-\u06FF\/]+(\s*?[\u0600-\u06FF]+){1,2}/i, // any word (or two) characters or numbers including two/three word month in arabic.
        parseTokenTimezone = /Z|[\+\-]\d\d:?\d\d/i, // +00:00 -00:00 +0000 -0000 or Z
        parseTokenT = /T/i, // T (ISO seperator)
        parseTokenTimestampMs = /[\+\-]?\d+(\.\d{1,3})?/, // 123456789 123456789.123

        // preliminary iso regex
        // 0000-00-00 0000-W00 or 0000-W00-0 + T + 00 or 00:00 or 00:00:00 or 00:00:00.000 + +00:00 or +0000)
        isoRegex = /^\s*\d{4}-(?:(\d\d-\d\d)|(W\d\d$)|(W\d\d-\d)|(\d\d\d))((T| )(\d\d(:\d\d(:\d\d(\.\d+)?)?)?)?([\+\-]\d\d:?\d\d|Z)?)?$/,

        isoFormat = 'YYYY-MM-DDTHH:mm:ssZ',

        isoDates = [
            'YYYY-MM-DD',
            'GGGG-[W]WW',
            'GGGG-[W]WW-E',
            'YYYY-DDD'
        ],

        // iso time formats and regexes
        isoTimes = [
            ['HH:mm:ss.SSSS', /(T| )\d\d:\d\d:\d\d\.\d{1,3}/],
            ['HH:mm:ss', /(T| )\d\d:\d\d:\d\d/],
            ['HH:mm', /(T| )\d\d:\d\d/],
            ['HH', /(T| )\d\d/]
        ],

        // timezone chunker "+10:00" > ["10", "00"] or "-1530" > ["-15", "30"]
        parseTimezoneChunker = /([\+\-]|\d\d)/gi,

        // getter and setter names
        proxyGettersAndSetters = 'Date|Hours|Minutes|Seconds|Milliseconds'.split('|'),
        unitMillisecondFactors = {
            'Milliseconds' : 1,
            'Seconds' : 1e3,
            'Minutes' : 6e4,
            'Hours' : 36e5,
            'Days' : 864e5,
            'Months' : 2592e6,
            'Years' : 31536e6
        },

        unitAliases = {
            ms : 'millisecond',
            s : 'second',
            m : 'minute',
            h : 'hour',
            d : 'day',
            D : 'date',
            w : 'week',
            W : 'isoWeek',
            M : 'month',
            y : 'year',
            DDD : 'dayOfYear',
            e : 'weekday',
            E : 'isoWeekday',
            gg: 'weekYear',
            GG: 'isoWeekYear'
        },

        camelFunctions = {
            dayofyear : 'dayOfYear',
            isoweekday : 'isoWeekday',
            isoweek : 'isoWeek',
            weekyear : 'weekYear',
            isoweekyear : 'isoWeekYear'
        },

        // format function strings
        formatFunctions = {},

        // tokens to ordinalize and pad
        ordinalizeTokens = 'DDD w W M D d'.split(' '),
        paddedTokens = 'M D H h m s w W'.split(' '),

        formatTokenFunctions = {
            M    : function () {
                return this.month() + 1;
            },
            MMM  : function (format) {
                return this.lang().monthsShort(this, format);
            },
            MMMM : function (format) {
                return this.lang().months(this, format);
            },
            D    : function () {
                return this.date();
            },
            DDD  : function () {
                return this.dayOfYear();
            },
            d    : function () {
                return this.day();
            },
            dd   : function (format) {
                return this.lang().weekdaysMin(this, format);
            },
            ddd  : function (format) {
                return this.lang().weekdaysShort(this, format);
            },
            dddd : function (format) {
                return this.lang().weekdays(this, format);
            },
            w    : function () {
                return this.week();
            },
            W    : function () {
                return this.isoWeek();
            },
            YY   : function () {
                return leftZeroFill(this.year() % 100, 2);
            },
            YYYY : function () {
                return leftZeroFill(this.year(), 4);
            },
            YYYYY : function () {
                return leftZeroFill(this.year(), 5);
            },
            gg   : function () {
                return leftZeroFill(this.weekYear() % 100, 2);
            },
            gggg : function () {
                return this.weekYear();
            },
            ggggg : function () {
                return leftZeroFill(this.weekYear(), 5);
            },
            GG   : function () {
                return leftZeroFill(this.isoWeekYear() % 100, 2);
            },
            GGGG : function () {
                return this.isoWeekYear();
            },
            GGGGG : function () {
                return leftZeroFill(this.isoWeekYear(), 5);
            },
            e : function () {
                return this.weekday();
            },
            E : function () {
                return this.isoWeekday();
            },
            a    : function () {
                return this.lang().meridiem(this.hours(), this.minutes(), true);
            },
            A    : function () {
                return this.lang().meridiem(this.hours(), this.minutes(), false);
            },
            H    : function () {
                return this.hours();
            },
            h    : function () {
                return this.hours() % 12 || 12;
            },
            m    : function () {
                return this.minutes();
            },
            s    : function () {
                return this.seconds();
            },
            S    : function () {
                return toInt(this.milliseconds() / 100);
            },
            SS   : function () {
                return leftZeroFill(toInt(this.milliseconds() / 10), 2);
            },
            SSS  : function () {
                return leftZeroFill(this.milliseconds(), 3);
            },
            SSSS : function () {
                return leftZeroFill(this.milliseconds(), 3);
            },
            Z    : function () {
                var a = -this.zone(),
                    b = "+";
                if (a < 0) {
                    a = -a;
                    b = "-";
                }
                return b + leftZeroFill(toInt(a / 60), 2) + ":" + leftZeroFill(toInt(a) % 60, 2);
            },
            ZZ   : function () {
                var a = -this.zone(),
                    b = "+";
                if (a < 0) {
                    a = -a;
                    b = "-";
                }
                return b + leftZeroFill(toInt(10 * a / 6), 4);
            },
            z : function () {
                return this.zoneAbbr();
            },
            zz : function () {
                return this.zoneName();
            },
            X    : function () {
                return this.unix();
            }
        },

        lists = ['months', 'monthsShort', 'weekdays', 'weekdaysShort', 'weekdaysMin'];

    function padToken(func, count) {
        return function (a) {
            return leftZeroFill(func.call(this, a), count);
        };
    }
    function ordinalizeToken(func, period) {
        return function (a) {
            return this.lang().ordinal(func.call(this, a), period);
        };
    }

    while (ordinalizeTokens.length) {
        i = ordinalizeTokens.pop();
        formatTokenFunctions[i + 'o'] = ordinalizeToken(formatTokenFunctions[i], i);
    }
    while (paddedTokens.length) {
        i = paddedTokens.pop();
        formatTokenFunctions[i + i] = padToken(formatTokenFunctions[i], 2);
    }
    formatTokenFunctions.DDDD = padToken(formatTokenFunctions.DDD, 3);


    /************************************
        Constructors
    ************************************/

    function Language() {

    }

    // Moment prototype object
    function Moment(config) {
        checkOverflow(config);
        extend(this, config);
    }

    // Duration Constructor
    function Duration(duration) {
        var normalizedInput = normalizeObjectUnits(duration),
            years = normalizedInput.year || 0,
            months = normalizedInput.month || 0,
            weeks = normalizedInput.week || 0,
            days = normalizedInput.day || 0,
            hours = normalizedInput.hour || 0,
            minutes = normalizedInput.minute || 0,
            seconds = normalizedInput.second || 0,
            milliseconds = normalizedInput.millisecond || 0;

        // store reference to input for deterministic cloning
        this._input = duration;

        // representation for dateAddRemove
        this._milliseconds = +milliseconds +
            seconds * 1e3 + // 1000
            minutes * 6e4 + // 1000 * 60
            hours * 36e5; // 1000 * 60 * 60
        // Because of dateAddRemove treats 24 hours as different from a
        // day when working around DST, we need to store them separately
        this._days = +days +
            weeks * 7;
        // It is impossible translate months into days without knowing
        // which months you are are talking about, so we have to store
        // it separately.
        this._months = +months +
            years * 12;

        this._data = {};

        this._bubble();
    }

    /************************************
        Helpers
    ************************************/


    function extend(a, b) {
        for (var i in b) {
            if (b.hasOwnProperty(i)) {
                a[i] = b[i];
            }
        }

        if (b.hasOwnProperty("toString")) {
            a.toString = b.toString;
        }

        if (b.hasOwnProperty("valueOf")) {
            a.valueOf = b.valueOf;
        }

        return a;
    }

    function absRound(number) {
        if (number < 0) {
            return Math.ceil(number);
        } else {
            return Math.floor(number);
        }
    }

    // left zero fill a number
    // see http://jsperf.com/left-zero-filling for performance comparison
    function leftZeroFill(number, targetLength) {
        var output = number + '';
        while (output.length < targetLength) {
            output = '0' + output;
        }
        return output;
    }

    // helper function for _.addTime and _.subtractTime
    function addOrSubtractDurationFromMoment(mom, duration, isAdding, ignoreUpdateOffset) {
        var milliseconds = duration._milliseconds,
            days = duration._days,
            months = duration._months,
            minutes,
            hours;

        if (milliseconds) {
            mom._d.setTime(+mom._d + milliseconds * isAdding);
        }
        // store the minutes and hours so we can restore them
        if (days || months) {
            minutes = mom.minute();
            hours = mom.hour();
        }
        if (days) {
            mom.date(mom.date() + days * isAdding);
        }
        if (months) {
            mom.month(mom.month() + months * isAdding);
        }
        if (milliseconds && !ignoreUpdateOffset) {
            moment.updateOffset(mom);
        }
        // restore the minutes and hours after possibly changing dst
        if (days || months) {
            mom.minute(minutes);
            mom.hour(hours);
        }
    }

    // check if is an array
    function isArray(input) {
        return Object.prototype.toString.call(input) === '[object Array]';
    }

    function isDate(input) {
        return  Object.prototype.toString.call(input) === '[object Date]' ||
                input instanceof Date;
    }

    // compare two arrays, return the number of differences
    function compareArrays(array1, array2, dontConvert) {
        var len = Math.min(array1.length, array2.length),
            lengthDiff = Math.abs(array1.length - array2.length),
            diffs = 0,
            i;
        for (i = 0; i < len; i++) {
            if ((dontConvert && array1[i] !== array2[i]) ||
                (!dontConvert && toInt(array1[i]) !== toInt(array2[i]))) {
                diffs++;
            }
        }
        return diffs + lengthDiff;
    }

    function normalizeUnits(units) {
        if (units) {
            var lowered = units.toLowerCase().replace(/(.)s$/, '$1');
            units = unitAliases[units] || camelFunctions[lowered] || lowered;
        }
        return units;
    }

    function normalizeObjectUnits(inputObject) {
        var normalizedInput = {},
            normalizedProp,
            prop,
            index;

        for (prop in inputObject) {
            if (inputObject.hasOwnProperty(prop)) {
                normalizedProp = normalizeUnits(prop);
                if (normalizedProp) {
                    normalizedInput[normalizedProp] = inputObject[prop];
                }
            }
        }

        return normalizedInput;
    }

    function makeList(field) {
        var count, setter;

        if (field.indexOf('week') === 0) {
            count = 7;
            setter = 'day';
        }
        else if (field.indexOf('month') === 0) {
            count = 12;
            setter = 'month';
        }
        else {
            return;
        }

        moment[field] = function (format, index) {
            var i, getter,
                method = moment.fn._lang[field],
                results = [];

            if (typeof format === 'number') {
                index = format;
                format = undefined;
            }

            getter = function (i) {
                var m = moment().utc().set(setter, i);
                return method.call(moment.fn._lang, m, format || '');
            };

            if (index != null) {
                return getter(index);
            }
            else {
                for (i = 0; i < count; i++) {
                    results.push(getter(i));
                }
                return results;
            }
        };
    }

    function toInt(argumentForCoercion) {
        var coercedNumber = +argumentForCoercion,
            value = 0;

        if (coercedNumber !== 0 && isFinite(coercedNumber)) {
            if (coercedNumber >= 0) {
                value = Math.floor(coercedNumber);
            } else {
                value = Math.ceil(coercedNumber);
            }
        }

        return value;
    }

    function daysInMonth(year, month) {
        return new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
    }

    function daysInYear(year) {
        return isLeapYear(year) ? 366 : 365;
    }

    function isLeapYear(year) {
        return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
    }

    function checkOverflow(m) {
        var overflow;
        if (m._a && m._pf.overflow === -2) {
            overflow =
                m._a[MONTH] < 0 || m._a[MONTH] > 11 ? MONTH :
                m._a[DATE] < 1 || m._a[DATE] > daysInMonth(m._a[YEAR], m._a[MONTH]) ? DATE :
                m._a[HOUR] < 0 || m._a[HOUR] > 23 ? HOUR :
                m._a[MINUTE] < 0 || m._a[MINUTE] > 59 ? MINUTE :
                m._a[SECOND] < 0 || m._a[SECOND] > 59 ? SECOND :
                m._a[MILLISECOND] < 0 || m._a[MILLISECOND] > 999 ? MILLISECOND :
                -1;

            if (m._pf._overflowDayOfYear && (overflow < YEAR || overflow > DATE)) {
                overflow = DATE;
            }

            m._pf.overflow = overflow;
        }
    }

    function initializeParsingFlags(config) {
        config._pf = {
            empty : false,
            unusedTokens : [],
            unusedInput : [],
            overflow : -2,
            charsLeftOver : 0,
            nullInput : false,
            invalidMonth : null,
            invalidFormat : false,
            userInvalidated : false,
            iso: false
        };
    }

    function isValid(m) {
        if (m._isValid == null) {
            m._isValid = !isNaN(m._d.getTime()) &&
                m._pf.overflow < 0 &&
                !m._pf.empty &&
                !m._pf.invalidMonth &&
                !m._pf.nullInput &&
                !m._pf.invalidFormat &&
                !m._pf.userInvalidated;

            if (m._strict) {
                m._isValid = m._isValid &&
                    m._pf.charsLeftOver === 0 &&
                    m._pf.unusedTokens.length === 0;
            }
        }
        return m._isValid;
    }

    function normalizeLanguage(key) {
        return key ? key.toLowerCase().replace('_', '-') : key;
    }

    /************************************
        Languages
    ************************************/


    extend(Language.prototype, {

        set : function (config) {
            var prop, i;
            for (i in config) {
                prop = config[i];
                if (typeof prop === 'function') {
                    this[i] = prop;
                } else {
                    this['_' + i] = prop;
                }
            }
        },

        _months : "January_February_March_April_May_June_July_August_September_October_November_December".split("_"),
        months : function (m) {
            return this._months[m.month()];
        },

        _monthsShort : "Jan_Feb_Mar_Apr_May_Jun_Jul_Aug_Sep_Oct_Nov_Dec".split("_"),
        monthsShort : function (m) {
            return this._monthsShort[m.month()];
        },

        monthsParse : function (monthName) {
            var i, mom, regex;

            if (!this._monthsParse) {
                this._monthsParse = [];
            }

            for (i = 0; i < 12; i++) {
                // make the regex if we don't have it already
                if (!this._monthsParse[i]) {
                    mom = moment.utc([2000, i]);
                    regex = '^' + this.months(mom, '') + '|^' + this.monthsShort(mom, '');
                    this._monthsParse[i] = new RegExp(regex.replace('.', ''), 'i');
                }
                // test the regex
                if (this._monthsParse[i].test(monthName)) {
                    return i;
                }
            }
        },

        _weekdays : "Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),
        weekdays : function (m) {
            return this._weekdays[m.day()];
        },

        _weekdaysShort : "Sun_Mon_Tue_Wed_Thu_Fri_Sat".split("_"),
        weekdaysShort : function (m) {
            return this._weekdaysShort[m.day()];
        },

        _weekdaysMin : "Su_Mo_Tu_We_Th_Fr_Sa".split("_"),
        weekdaysMin : function (m) {
            return this._weekdaysMin[m.day()];
        },

        weekdaysParse : function (weekdayName) {
            var i, mom, regex;

            if (!this._weekdaysParse) {
                this._weekdaysParse = [];
            }

            for (i = 0; i < 7; i++) {
                // make the regex if we don't have it already
                if (!this._weekdaysParse[i]) {
                    mom = moment([2000, 1]).day(i);
                    regex = '^' + this.weekdays(mom, '') + '|^' + this.weekdaysShort(mom, '') + '|^' + this.weekdaysMin(mom, '');
                    this._weekdaysParse[i] = new RegExp(regex.replace('.', ''), 'i');
                }
                // test the regex
                if (this._weekdaysParse[i].test(weekdayName)) {
                    return i;
                }
            }
        },

        _longDateFormat : {
            LT : "h:mm A",
            L : "MM/DD/YYYY",
            LL : "MMMM D YYYY",
            LLL : "MMMM D YYYY LT",
            LLLL : "dddd, MMMM D YYYY LT"
        },
        longDateFormat : function (key) {
            var output = this._longDateFormat[key];
            if (!output && this._longDateFormat[key.toUpperCase()]) {
                output = this._longDateFormat[key.toUpperCase()].replace(/MMMM|MM|DD|dddd/g, function (val) {
                    return val.slice(1);
                });
                this._longDateFormat[key] = output;
            }
            return output;
        },

        isPM : function (input) {
            // IE8 Quirks Mode & IE7 Standards Mode do not allow accessing strings like arrays
            // Using charAt should be more compatible.
            return ((input + '').toLowerCase().charAt(0) === 'p');
        },

        _meridiemParse : /[ap]\.?m?\.?/i,
        meridiem : function (hours, minutes, isLower) {
            if (hours > 11) {
                return isLower ? 'pm' : 'PM';
            } else {
                return isLower ? 'am' : 'AM';
            }
        },

        _calendar : {
            sameDay : '[Today at] LT',
            nextDay : '[Tomorrow at] LT',
            nextWeek : 'dddd [at] LT',
            lastDay : '[Yesterday at] LT',
            lastWeek : '[Last] dddd [at] LT',
            sameElse : 'L'
        },
        calendar : function (key, mom) {
            var output = this._calendar[key];
            return typeof output === 'function' ? output.apply(mom) : output;
        },

        _relativeTime : {
            future : "in %s",
            past : "%s ago",
            s : "a few seconds",
            m : "a minute",
            mm : "%d minutes",
            h : "an hour",
            hh : "%d hours",
            d : "a day",
            dd : "%d days",
            M : "a month",
            MM : "%d months",
            y : "a year",
            yy : "%d years"
        },
        relativeTime : function (number, withoutSuffix, string, isFuture) {
            var output = this._relativeTime[string];
            return (typeof output === 'function') ?
                output(number, withoutSuffix, string, isFuture) :
                output.replace(/%d/i, number);
        },
        pastFuture : function (diff, output) {
            var format = this._relativeTime[diff > 0 ? 'future' : 'past'];
            return typeof format === 'function' ? format(output) : format.replace(/%s/i, output);
        },

        ordinal : function (number) {
            return this._ordinal.replace("%d", number);
        },
        _ordinal : "%d",

        preparse : function (string) {
            return string;
        },

        postformat : function (string) {
            return string;
        },

        week : function (mom) {
            return weekOfYear(mom, this._week.dow, this._week.doy).week;
        },

        _week : {
            dow : 0, // Sunday is the first day of the week.
            doy : 6  // The week that contains Jan 1st is the first week of the year.
        },

        _invalidDate: 'Invalid date',
        invalidDate: function () {
            return this._invalidDate;
        }
    });

    // Loads a language definition into the `languages` cache.  The function
    // takes a key and optionally values.  If not in the browser and no values
    // are provided, it will load the language file module.  As a convenience,
    // this function also returns the language values.
    function loadLang(key, values) {
        values.abbr = key;
        if (!languages[key]) {
            languages[key] = new Language();
        }
        languages[key].set(values);
        return languages[key];
    }

    // Remove a language from the `languages` cache. Mostly useful in tests.
    function unloadLang(key) {
        delete languages[key];
    }

    // Determines which language definition to use and returns it.
    //
    // With no parameters, it will return the global language.  If you
    // pass in a language key, such as 'en', it will return the
    // definition for 'en', so long as 'en' has already been loaded using
    // moment.lang.
    function getLangDefinition(key) {
        var i = 0, j, lang, next, split,
            get = function (k) {
                if (!languages[k] && hasModule) {
                    try {
                        require('./lang/' + k);
                    } catch (e) { }
                }
                return languages[k];
            };

        if (!key) {
            return moment.fn._lang;
        }

        if (!isArray(key)) {
            //short-circuit everything else
            lang = get(key);
            if (lang) {
                return lang;
            }
            key = [key];
        }

        //pick the language from the array
        //try ['en-au', 'en-gb'] as 'en-au', 'en-gb', 'en', as in move through the list trying each
        //substring from most specific to least, but move to the next array item if it's a more specific variant than the current root
        while (i < key.length) {
            split = normalizeLanguage(key[i]).split('-');
            j = split.length;
            next = normalizeLanguage(key[i + 1]);
            next = next ? next.split('-') : null;
            while (j > 0) {
                lang = get(split.slice(0, j).join('-'));
                if (lang) {
                    return lang;
                }
                if (next && next.length >= j && compareArrays(split, next, true) >= j - 1) {
                    //the next array item is better than a shallower substring of this one
                    break;
                }
                j--;
            }
            i++;
        }
        return moment.fn._lang;
    }

    /************************************
        Formatting
    ************************************/


    function removeFormattingTokens(input) {
        if (input.match(/\[[\s\S]/)) {
            return input.replace(/^\[|\]$/g, "");
        }
        return input.replace(/\\/g, "");
    }

    function makeFormatFunction(format) {
        var array = format.match(formattingTokens), i, length;

        for (i = 0, length = array.length; i < length; i++) {
            if (formatTokenFunctions[array[i]]) {
                array[i] = formatTokenFunctions[array[i]];
            } else {
                array[i] = removeFormattingTokens(array[i]);
            }
        }

        return function (mom) {
            var output = "";
            for (i = 0; i < length; i++) {
                output += array[i] instanceof Function ? array[i].call(mom, format) : array[i];
            }
            return output;
        };
    }

    // format date using native date object
    function formatMoment(m, format) {

        if (!m.isValid()) {
            return m.lang().invalidDate();
        }

        format = expandFormat(format, m.lang());

        if (!formatFunctions[format]) {
            formatFunctions[format] = makeFormatFunction(format);
        }

        return formatFunctions[format](m);
    }

    function expandFormat(format, lang) {
        var i = 5;

        function replaceLongDateFormatTokens(input) {
            return lang.longDateFormat(input) || input;
        }

        localFormattingTokens.lastIndex = 0;
        while (i >= 0 && localFormattingTokens.test(format)) {
            format = format.replace(localFormattingTokens, replaceLongDateFormatTokens);
            localFormattingTokens.lastIndex = 0;
            i -= 1;
        }

        return format;
    }


    /************************************
        Parsing
    ************************************/


    // get the regex to find the next token
    function getParseRegexForToken(token, config) {
        var a;
        switch (token) {
        case 'DDDD':
            return parseTokenThreeDigits;
        case 'YYYY':
        case 'GGGG':
        case 'gggg':
            return parseTokenFourDigits;
        case 'YYYYY':
        case 'GGGGG':
        case 'ggggg':
            return parseTokenSixDigits;
        case 'S':
        case 'SS':
        case 'SSS':
        case 'DDD':
            return parseTokenOneToThreeDigits;
        case 'MMM':
        case 'MMMM':
        case 'dd':
        case 'ddd':
        case 'dddd':
            return parseTokenWord;
        case 'a':
        case 'A':
            return getLangDefinition(config._l)._meridiemParse;
        case 'X':
            return parseTokenTimestampMs;
        case 'Z':
        case 'ZZ':
            return parseTokenTimezone;
        case 'T':
            return parseTokenT;
        case 'SSSS':
            return parseTokenDigits;
        case 'MM':
        case 'DD':
        case 'YY':
        case 'GG':
        case 'gg':
        case 'HH':
        case 'hh':
        case 'mm':
        case 'ss':
        case 'M':
        case 'D':
        case 'd':
        case 'H':
        case 'h':
        case 'm':
        case 's':
        case 'w':
        case 'ww':
        case 'W':
        case 'WW':
        case 'e':
        case 'E':
            return parseTokenOneOrTwoDigits;
        default :
            a = new RegExp(regexpEscape(unescapeFormat(token.replace('\\', '')), "i"));
            return a;
        }
    }

    function timezoneMinutesFromString(string) {
        var tzchunk = (parseTokenTimezone.exec(string) || [])[0],
            parts = (tzchunk + '').match(parseTimezoneChunker) || ['-', 0, 0],
            minutes = +(parts[1] * 60) + toInt(parts[2]);

        return parts[0] === '+' ? -minutes : minutes;
    }

    // function to convert string input to date
    function addTimeToArrayFromToken(token, input, config) {
        var a, datePartArray = config._a;

        switch (token) {
        // MONTH
        case 'M' : // fall through to MM
        case 'MM' :
            if (input != null) {
                datePartArray[MONTH] = toInt(input) - 1;
            }
            break;
        case 'MMM' : // fall through to MMMM
        case 'MMMM' :
            a = getLangDefinition(config._l).monthsParse(input);
            // if we didn't find a month name, mark the date as invalid.
            if (a != null) {
                datePartArray[MONTH] = a;
            } else {
                config._pf.invalidMonth = input;
            }
            break;
        // DAY OF MONTH
        case 'D' : // fall through to DD
        case 'DD' :
            if (input != null) {
                datePartArray[DATE] = toInt(input);
            }
            break;
        // DAY OF YEAR
        case 'DDD' : // fall through to DDDD
        case 'DDDD' :
            if (input != null) {
                config._dayOfYear = toInt(input);
            }

            break;
        // YEAR
        case 'YY' :
            datePartArray[YEAR] = toInt(input) + (toInt(input) > 68 ? 1900 : 2000);
            break;
        case 'YYYY' :
        case 'YYYYY' :
            datePartArray[YEAR] = toInt(input);
            break;
        // AM / PM
        case 'a' : // fall through to A
        case 'A' :
            config._isPm = getLangDefinition(config._l).isPM(input);
            break;
        // 24 HOUR
        case 'H' : // fall through to hh
        case 'HH' : // fall through to hh
        case 'h' : // fall through to hh
        case 'hh' :
            datePartArray[HOUR] = toInt(input);
            break;
        // MINUTE
        case 'm' : // fall through to mm
        case 'mm' :
            datePartArray[MINUTE] = toInt(input);
            break;
        // SECOND
        case 's' : // fall through to ss
        case 'ss' :
            datePartArray[SECOND] = toInt(input);
            break;
        // MILLISECOND
        case 'S' :
        case 'SS' :
        case 'SSS' :
        case 'SSSS' :
            datePartArray[MILLISECOND] = toInt(('0.' + input) * 1000);
            break;
        // UNIX TIMESTAMP WITH MS
        case 'X':
            config._d = new Date(parseFloat(input) * 1000);
            break;
        // TIMEZONE
        case 'Z' : // fall through to ZZ
        case 'ZZ' :
            config._useUTC = true;
            config._tzm = timezoneMinutesFromString(input);
            break;
        case 'w':
        case 'ww':
        case 'W':
        case 'WW':
        case 'd':
        case 'dd':
        case 'ddd':
        case 'dddd':
        case 'e':
        case 'E':
            token = token.substr(0, 1);
            /* falls through */
        case 'gg':
        case 'gggg':
        case 'GG':
        case 'GGGG':
        case 'GGGGG':
            token = token.substr(0, 2);
            if (input) {
                config._w = config._w || {};
                config._w[token] = input;
            }
            break;
        }
    }

    // convert an array to a date.
    // the array should mirror the parameters below
    // note: all values past the year are optional and will default to the lowest possible value.
    // [year, month, day , hour, minute, second, millisecond]
    function dateFromConfig(config) {
        var i, date, input = [], currentDate,
            yearToUse, fixYear, w, temp, lang, weekday, week;

        if (config._d) {
            return;
        }

        currentDate = currentDateArray(config);

        //compute day of the year from weeks and weekdays
        if (config._w && config._a[DATE] == null && config._a[MONTH] == null) {
            fixYear = function (val) {
                return val ?
                  (val.length < 3 ? (parseInt(val, 10) > 68 ? '19' + val : '20' + val) : val) :
                  (config._a[YEAR] == null ? moment().weekYear() : config._a[YEAR]);
            };

            w = config._w;
            if (w.GG != null || w.W != null || w.E != null) {
                temp = dayOfYearFromWeeks(fixYear(w.GG), w.W || 1, w.E, 4, 1);
            }
            else {
                lang = getLangDefinition(config._l);
                weekday = w.d != null ?  parseWeekday(w.d, lang) :
                  (w.e != null ?  parseInt(w.e, 10) + lang._week.dow : 0);

                week = parseInt(w.w, 10) || 1;

                //if we're parsing 'd', then the low day numbers may be next week
                if (w.d != null && weekday < lang._week.dow) {
                    week++;
                }

                temp = dayOfYearFromWeeks(fixYear(w.gg), week, weekday, lang._week.doy, lang._week.dow);
            }

            config._a[YEAR] = temp.year;
            config._dayOfYear = temp.dayOfYear;
        }

        //if the day of the year is set, figure out what it is
        if (config._dayOfYear) {
            yearToUse = config._a[YEAR] == null ? currentDate[YEAR] : config._a[YEAR];

            if (config._dayOfYear > daysInYear(yearToUse)) {
                config._pf._overflowDayOfYear = true;
            }

            date = makeUTCDate(yearToUse, 0, config._dayOfYear);
            config._a[MONTH] = date.getUTCMonth();
            config._a[DATE] = date.getUTCDate();
        }

        // Default to current date.
        // * if no year, month, day of month are given, default to today
        // * if day of month is given, default month and year
        // * if month is given, default only year
        // * if year is given, don't default anything
        for (i = 0; i < 3 && config._a[i] == null; ++i) {
            config._a[i] = input[i] = currentDate[i];
        }

        // Zero out whatever was not defaulted, including time
        for (; i < 7; i++) {
            config._a[i] = input[i] = (config._a[i] == null) ? (i === 2 ? 1 : 0) : config._a[i];
        }

        // add the offsets to the time to be parsed so that we can have a clean array for checking isValid
        input[HOUR] += toInt((config._tzm || 0) / 60);
        input[MINUTE] += toInt((config._tzm || 0) % 60);

        config._d = (config._useUTC ? makeUTCDate : makeDate).apply(null, input);
    }

    function dateFromObject(config) {
        var normalizedInput;

        if (config._d) {
            return;
        }

        normalizedInput = normalizeObjectUnits(config._i);
        config._a = [
            normalizedInput.year,
            normalizedInput.month,
            normalizedInput.day,
            normalizedInput.hour,
            normalizedInput.minute,
            normalizedInput.second,
            normalizedInput.millisecond
        ];

        dateFromConfig(config);
    }

    function currentDateArray(config) {
        var now = new Date();
        if (config._useUTC) {
            return [
                now.getUTCFullYear(),
                now.getUTCMonth(),
                now.getUTCDate()
            ];
        } else {
            return [now.getFullYear(), now.getMonth(), now.getDate()];
        }
    }

    // date from string and format string
    function makeDateFromStringAndFormat(config) {

        config._a = [];
        config._pf.empty = true;

        // This array is used to make a Date, either with `new Date` or `Date.UTC`
        var lang = getLangDefinition(config._l),
            string = '' + config._i,
            i, parsedInput, tokens, token, skipped,
            stringLength = string.length,
            totalParsedInputLength = 0;

        tokens = expandFormat(config._f, lang).match(formattingTokens) || [];

        for (i = 0; i < tokens.length; i++) {
            token = tokens[i];
            parsedInput = (getParseRegexForToken(token, config).exec(string) || [])[0];
            if (parsedInput) {
                skipped = string.substr(0, string.indexOf(parsedInput));
                if (skipped.length > 0) {
                    config._pf.unusedInput.push(skipped);
                }
                string = string.slice(string.indexOf(parsedInput) + parsedInput.length);
                totalParsedInputLength += parsedInput.length;
            }
            // don't parse if it's not a known token
            if (formatTokenFunctions[token]) {
                if (parsedInput) {
                    config._pf.empty = false;
                }
                else {
                    config._pf.unusedTokens.push(token);
                }
                addTimeToArrayFromToken(token, parsedInput, config);
            }
            else if (config._strict && !parsedInput) {
                config._pf.unusedTokens.push(token);
            }
        }

        // add remaining unparsed input length to the string
        config._pf.charsLeftOver = stringLength - totalParsedInputLength;
        if (string.length > 0) {
            config._pf.unusedInput.push(string);
        }

        // handle am pm
        if (config._isPm && config._a[HOUR] < 12) {
            config._a[HOUR] += 12;
        }
        // if is 12 am, change hours to 0
        if (config._isPm === false && config._a[HOUR] === 12) {
            config._a[HOUR] = 0;
        }

        dateFromConfig(config);
        checkOverflow(config);
    }

    function unescapeFormat(s) {
        return s.replace(/\\(\[)|\\(\])|\[([^\]\[]*)\]|\\(.)/g, function (matched, p1, p2, p3, p4) {
            return p1 || p2 || p3 || p4;
        });
    }

    // Code from http://stackoverflow.com/questions/3561493/is-there-a-regexp-escape-function-in-javascript
    function regexpEscape(s) {
        return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    }

    // date from string and array of format strings
    function makeDateFromStringAndArray(config) {
        var tempConfig,
            bestMoment,

            scoreToBeat,
            i,
            currentScore;

        if (config._f.length === 0) {
            config._pf.invalidFormat = true;
            config._d = new Date(NaN);
            return;
        }

        for (i = 0; i < config._f.length; i++) {
            currentScore = 0;
            tempConfig = extend({}, config);
            initializeParsingFlags(tempConfig);
            tempConfig._f = config._f[i];
            makeDateFromStringAndFormat(tempConfig);

            if (!isValid(tempConfig)) {
                continue;
            }

            // if there is any input that was not parsed add a penalty for that format
            currentScore += tempConfig._pf.charsLeftOver;

            //or tokens
            currentScore += tempConfig._pf.unusedTokens.length * 10;

            tempConfig._pf.score = currentScore;

            if (scoreToBeat == null || currentScore < scoreToBeat) {
                scoreToBeat = currentScore;
                bestMoment = tempConfig;
            }
        }

        extend(config, bestMoment || tempConfig);
    }

    // date from iso format
    function makeDateFromString(config) {
        var i,
            string = config._i,
            match = isoRegex.exec(string);

        if (match) {
            config._pf.iso = true;
            for (i = 4; i > 0; i--) {
                if (match[i]) {
                    // match[5] should be "T" or undefined
                    config._f = isoDates[i - 1] + (match[6] || " ");
                    break;
                }
            }
            for (i = 0; i < 4; i++) {
                if (isoTimes[i][1].exec(string)) {
                    config._f += isoTimes[i][0];
                    break;
                }
            }
            if (parseTokenTimezone.exec(string)) {
                config._f += "Z";
            }
            makeDateFromStringAndFormat(config);
        }
        else {
            config._d = new Date(string);
        }
    }

    function makeDateFromInput(config) {
        var input = config._i,
            matched = aspNetJsonRegex.exec(input);

        if (input === undefined) {
            config._d = new Date();
        } else if (matched) {
            config._d = new Date(+matched[1]);
        } else if (typeof input === 'string') {
            makeDateFromString(config);
        } else if (isArray(input)) {
            config._a = input.slice(0);
            dateFromConfig(config);
        } else if (isDate(input)) {
            config._d = new Date(+input);
        } else if (typeof(input) === 'object') {
            dateFromObject(config);
        } else {
            config._d = new Date(input);
        }
    }

    function makeDate(y, m, d, h, M, s, ms) {
        //can't just apply() to create a date:
        //http://stackoverflow.com/questions/181348/instantiating-a-javascript-object-by-calling-prototype-constructor-apply
        var date = new Date(y, m, d, h, M, s, ms);

        //the date constructor doesn't accept years < 1970
        if (y < 1970) {
            date.setFullYear(y);
        }
        return date;
    }

    function makeUTCDate(y) {
        var date = new Date(Date.UTC.apply(null, arguments));
        if (y < 1970) {
            date.setUTCFullYear(y);
        }
        return date;
    }

    function parseWeekday(input, language) {
        if (typeof input === 'string') {
            if (!isNaN(input)) {
                input = parseInt(input, 10);
            }
            else {
                input = language.weekdaysParse(input);
                if (typeof input !== 'number') {
                    return null;
                }
            }
        }
        return input;
    }

    /************************************
        Relative Time
    ************************************/


    // helper function for moment.fn.from, moment.fn.fromNow, and moment.duration.fn.humanize
    function substituteTimeAgo(string, number, withoutSuffix, isFuture, lang) {
        return lang.relativeTime(number || 1, !!withoutSuffix, string, isFuture);
    }

    function relativeTime(milliseconds, withoutSuffix, lang) {
        var seconds = round(Math.abs(milliseconds) / 1000),
            minutes = round(seconds / 60),
            hours = round(minutes / 60),
            days = round(hours / 24),
            years = round(days / 365),
            args = seconds < 45 && ['s', seconds] ||
                minutes === 1 && ['m'] ||
                minutes < 45 && ['mm', minutes] ||
                hours === 1 && ['h'] ||
                hours < 22 && ['hh', hours] ||
                days === 1 && ['d'] ||
                days <= 25 && ['dd', days] ||
                days <= 45 && ['M'] ||
                days < 345 && ['MM', round(days / 30)] ||
                years === 1 && ['y'] || ['yy', years];
        args[2] = withoutSuffix;
        args[3] = milliseconds > 0;
        args[4] = lang;
        return substituteTimeAgo.apply({}, args);
    }


    /************************************
        Week of Year
    ************************************/


    // firstDayOfWeek       0 = sun, 6 = sat
    //                      the day of the week that starts the week
    //                      (usually sunday or monday)
    // firstDayOfWeekOfYear 0 = sun, 6 = sat
    //                      the first week is the week that contains the first
    //                      of this day of the week
    //                      (eg. ISO weeks use thursday (4))
    function weekOfYear(mom, firstDayOfWeek, firstDayOfWeekOfYear) {
        var end = firstDayOfWeekOfYear - firstDayOfWeek,
            daysToDayOfWeek = firstDayOfWeekOfYear - mom.day(),
            adjustedMoment;


        if (daysToDayOfWeek > end) {
            daysToDayOfWeek -= 7;
        }

        if (daysToDayOfWeek < end - 7) {
            daysToDayOfWeek += 7;
        }

        adjustedMoment = moment(mom).add('d', daysToDayOfWeek);
        return {
            week: Math.ceil(adjustedMoment.dayOfYear() / 7),
            year: adjustedMoment.year()
        };
    }

    //http://en.wikipedia.org/wiki/ISO_week_date#Calculating_a_date_given_the_year.2C_week_number_and_weekday
    function dayOfYearFromWeeks(year, week, weekday, firstDayOfWeekOfYear, firstDayOfWeek) {
        var d = new Date(Date.UTC(year, 0)).getUTCDay(),
            daysToAdd, dayOfYear;

        weekday = weekday != null ? weekday : firstDayOfWeek;
        daysToAdd = firstDayOfWeek - d + (d > firstDayOfWeekOfYear ? 7 : 0);
        dayOfYear = 7 * (week - 1) + (weekday - firstDayOfWeek) + daysToAdd + 1;

        return {
            year: dayOfYear > 0 ? year : year - 1,
            dayOfYear: dayOfYear > 0 ?  dayOfYear : daysInYear(year - 1) + dayOfYear
        };
    }

    /************************************
        Top Level Functions
    ************************************/

    function makeMoment(config) {
        var input = config._i,
            format = config._f;

        if (typeof config._pf === 'undefined') {
            initializeParsingFlags(config);
        }

        if (input === null) {
            return moment.invalid({nullInput: true});
        }

        if (typeof input === 'string') {
            config._i = input = getLangDefinition().preparse(input);
        }

        if (moment.isMoment(input)) {
            config = extend({}, input);

            config._d = new Date(+input._d);
        } else if (format) {
            if (isArray(format)) {
                makeDateFromStringAndArray(config);
            } else {
                makeDateFromStringAndFormat(config);
            }
        } else {
            makeDateFromInput(config);
        }

        return new Moment(config);
    }

    moment = function (input, format, lang, strict) {
        if (typeof(lang) === "boolean") {
            strict = lang;
            lang = undefined;
        }
        return makeMoment({
            _i : input,
            _f : format,
            _l : lang,
            _strict : strict,
            _isUTC : false
        });
    };

    // creating with utc
    moment.utc = function (input, format, lang, strict) {
        var m;

        if (typeof(lang) === "boolean") {
            strict = lang;
            lang = undefined;
        }
        m = makeMoment({
            _useUTC : true,
            _isUTC : true,
            _l : lang,
            _i : input,
            _f : format,
            _strict : strict
        }).utc();

        return m;
    };

    // creating with unix timestamp (in seconds)
    moment.unix = function (input) {
        return moment(input * 1000);
    };

    // duration
    moment.duration = function (input, key) {
        var isDuration = moment.isDuration(input),
            isNumber = (typeof input === 'number'),
            duration = (isDuration ? input._input : (isNumber ? {} : input)),
            // matching against regexp is expensive, do it on demand
            match = null,
            sign,
            ret,
            parseIso,
            timeEmpty,
            dateTimeEmpty;

        if (isNumber) {
            if (key) {
                duration[key] = input;
            } else {
                duration.milliseconds = input;
            }
        } else if (!!(match = aspNetTimeSpanJsonRegex.exec(input))) {
            sign = (match[1] === "-") ? -1 : 1;
            duration = {
                y: 0,
                d: toInt(match[DATE]) * sign,
                h: toInt(match[HOUR]) * sign,
                m: toInt(match[MINUTE]) * sign,
                s: toInt(match[SECOND]) * sign,
                ms: toInt(match[MILLISECOND]) * sign
            };
        } else if (!!(match = isoDurationRegex.exec(input))) {
            sign = (match[1] === "-") ? -1 : 1;
            parseIso = function (inp) {
                // We'd normally use ~~inp for this, but unfortunately it also
                // converts floats to ints.
                // inp may be undefined, so careful calling replace on it.
                var res = inp && parseFloat(inp.replace(',', '.'));
                // apply sign while we're at it
                return (isNaN(res) ? 0 : res) * sign;
            };
            duration = {
                y: parseIso(match[2]),
                M: parseIso(match[3]),
                d: parseIso(match[4]),
                h: parseIso(match[5]),
                m: parseIso(match[6]),
                s: parseIso(match[7]),
                w: parseIso(match[8])
            };
        }

        ret = new Duration(duration);

        if (isDuration && input.hasOwnProperty('_lang')) {
            ret._lang = input._lang;
        }

        return ret;
    };

    // version number
    moment.version = VERSION;

    // default format
    moment.defaultFormat = isoFormat;

    // This function will be called whenever a moment is mutated.
    // It is intended to keep the offset in sync with the timezone.
    moment.updateOffset = function () {};

    // This function will load languages and then set the global language.  If
    // no arguments are passed in, it will simply return the current global
    // language key.
    moment.lang = function (key, values) {
        var r;
        if (!key) {
            return moment.fn._lang._abbr;
        }
        if (values) {
            loadLang(normalizeLanguage(key), values);
        } else if (values === null) {
            unloadLang(key);
            key = 'en';
        } else if (!languages[key]) {
            getLangDefinition(key);
        }
        r = moment.duration.fn._lang = moment.fn._lang = getLangDefinition(key);
        return r._abbr;
    };

    // returns language data
    moment.langData = function (key) {
        if (key && key._lang && key._lang._abbr) {
            key = key._lang._abbr;
        }
        return getLangDefinition(key);
    };

    // compare moment object
    moment.isMoment = function (obj) {
        return obj instanceof Moment;
    };

    // for typechecking Duration objects
    moment.isDuration = function (obj) {
        return obj instanceof Duration;
    };

    for (i = lists.length - 1; i >= 0; --i) {
        makeList(lists[i]);
    }

    moment.normalizeUnits = function (units) {
        return normalizeUnits(units);
    };

    moment.invalid = function (flags) {
        var m = moment.utc(NaN);
        if (flags != null) {
            extend(m._pf, flags);
        }
        else {
            m._pf.userInvalidated = true;
        }

        return m;
    };

    moment.parseZone = function (input) {
        return moment(input).parseZone();
    };

    /************************************
        Moment Prototype
    ************************************/


    extend(moment.fn = Moment.prototype, {

        clone : function () {
            return moment(this);
        },

        valueOf : function () {
            return +this._d + ((this._offset || 0) * 60000);
        },

        unix : function () {
            return Math.floor(+this / 1000);
        },

        toString : function () {
            return this.clone().lang('en').format("ddd MMM DD YYYY HH:mm:ss [GMT]ZZ");
        },

        toDate : function () {
            return this._offset ? new Date(+this) : this._d;
        },

        toISOString : function () {
            return formatMoment(moment(this).utc(), 'YYYY-MM-DD[T]HH:mm:ss.SSS[Z]');
        },

        toArray : function () {
            var m = this;
            return [
                m.year(),
                m.month(),
                m.date(),
                m.hours(),
                m.minutes(),
                m.seconds(),
                m.milliseconds()
            ];
        },

        isValid : function () {
            return isValid(this);
        },

        isDSTShifted : function () {

            if (this._a) {
                return this.isValid() && compareArrays(this._a, (this._isUTC ? moment.utc(this._a) : moment(this._a)).toArray()) > 0;
            }

            return false;
        },

        parsingFlags : function () {
            return extend({}, this._pf);
        },

        invalidAt: function () {
            return this._pf.overflow;
        },

        utc : function () {
            return this.zone(0);
        },

        local : function () {
            this.zone(0);
            this._isUTC = false;
            return this;
        },

        format : function (inputString) {
            var output = formatMoment(this, inputString || moment.defaultFormat);
            return this.lang().postformat(output);
        },

        add : function (input, val) {
            var dur;
            // switch args to support add('s', 1) and add(1, 's')
            if (typeof input === 'string') {
                dur = moment.duration(+val, input);
            } else {
                dur = moment.duration(input, val);
            }
            addOrSubtractDurationFromMoment(this, dur, 1);
            return this;
        },

        subtract : function (input, val) {
            var dur;
            // switch args to support subtract('s', 1) and subtract(1, 's')
            if (typeof input === 'string') {
                dur = moment.duration(+val, input);
            } else {
                dur = moment.duration(input, val);
            }
            addOrSubtractDurationFromMoment(this, dur, -1);
            return this;
        },

        diff : function (input, units, asFloat) {
            var that = this._isUTC ? moment(input).zone(this._offset || 0) : moment(input).local(),
                zoneDiff = (this.zone() - that.zone()) * 6e4,
                diff, output;

            units = normalizeUnits(units);

            if (units === 'year' || units === 'month') {
                // average number of days in the months in the given dates
                diff = (this.daysInMonth() + that.daysInMonth()) * 432e5; // 24 * 60 * 60 * 1000 / 2
                // difference in months
                output = ((this.year() - that.year()) * 12) + (this.month() - that.month());
                // adjust by taking difference in days, average number of days
                // and dst in the given months.
                output += ((this - moment(this).startOf('month')) -
                        (that - moment(that).startOf('month'))) / diff;
                // same as above but with zones, to negate all dst
                output -= ((this.zone() - moment(this).startOf('month').zone()) -
                        (that.zone() - moment(that).startOf('month').zone())) * 6e4 / diff;
                if (units === 'year') {
                    output = output / 12;
                }
            } else {
                diff = (this - that);
                output = units === 'second' ? diff / 1e3 : // 1000
                    units === 'minute' ? diff / 6e4 : // 1000 * 60
                    units === 'hour' ? diff / 36e5 : // 1000 * 60 * 60
                    units === 'day' ? (diff - zoneDiff) / 864e5 : // 1000 * 60 * 60 * 24, negate dst
                    units === 'week' ? (diff - zoneDiff) / 6048e5 : // 1000 * 60 * 60 * 24 * 7, negate dst
                    diff;
            }
            return asFloat ? output : absRound(output);
        },

        from : function (time, withoutSuffix) {
            return moment.duration(this.diff(time)).lang(this.lang()._abbr).humanize(!withoutSuffix);
        },

        fromNow : function (withoutSuffix) {
            return this.from(moment(), withoutSuffix);
        },

        calendar : function () {
            var diff = this.diff(moment().zone(this.zone()).startOf('day'), 'days', true),
                format = diff < -6 ? 'sameElse' :
                diff < -1 ? 'lastWeek' :
                diff < 0 ? 'lastDay' :
                diff < 1 ? 'sameDay' :
                diff < 2 ? 'nextDay' :
                diff < 7 ? 'nextWeek' : 'sameElse';
            return this.format(this.lang().calendar(format, this));
        },

        isLeapYear : function () {
            return isLeapYear(this.year());
        },

        isDST : function () {
            return (this.zone() < this.clone().month(0).zone() ||
                this.zone() < this.clone().month(5).zone());
        },

        day : function (input) {
            var day = this._isUTC ? this._d.getUTCDay() : this._d.getDay();
            if (input != null) {
                input = parseWeekday(input, this.lang());
                return this.add({ d : input - day });
            } else {
                return day;
            }
        },

        month : function (input) {
            var utc = this._isUTC ? 'UTC' : '',
                dayOfMonth;

            if (input != null) {
                if (typeof input === 'string') {
                    input = this.lang().monthsParse(input);
                    if (typeof input !== 'number') {
                        return this;
                    }
                }

                dayOfMonth = this.date();
                this.date(1);
                this._d['set' + utc + 'Month'](input);
                this.date(Math.min(dayOfMonth, this.daysInMonth()));

                moment.updateOffset(this);
                return this;
            } else {
                return this._d['get' + utc + 'Month']();
            }
        },

        startOf: function (units) {
            units = normalizeUnits(units);
            // the following switch intentionally omits break keywords
            // to utilize falling through the cases.
            switch (units) {
            case 'year':
                this.month(0);
                /* falls through */
            case 'month':
                this.date(1);
                /* falls through */
            case 'week':
            case 'isoWeek':
            case 'day':
                this.hours(0);
                /* falls through */
            case 'hour':
                this.minutes(0);
                /* falls through */
            case 'minute':
                this.seconds(0);
                /* falls through */
            case 'second':
                this.milliseconds(0);
                /* falls through */
            }

            // weeks are a special case
            if (units === 'week') {
                this.weekday(0);
            } else if (units === 'isoWeek') {
                this.isoWeekday(1);
            }

            return this;
        },

        endOf: function (units) {
            units = normalizeUnits(units);
            return this.startOf(units).add((units === 'isoWeek' ? 'week' : units), 1).subtract('ms', 1);
        },

        isAfter: function (input, units) {
            units = typeof units !== 'undefined' ? units : 'millisecond';
            return +this.clone().startOf(units) > +moment(input).startOf(units);
        },

        isBefore: function (input, units) {
            units = typeof units !== 'undefined' ? units : 'millisecond';
            return +this.clone().startOf(units) < +moment(input).startOf(units);
        },

        isSame: function (input, units) {
            units = typeof units !== 'undefined' ? units : 'millisecond';
            return +this.clone().startOf(units) === +moment(input).startOf(units);
        },

        min: function (other) {
            other = moment.apply(null, arguments);
            return other < this ? this : other;
        },

        max: function (other) {
            other = moment.apply(null, arguments);
            return other > this ? this : other;
        },

        zone : function (input) {
            var offset = this._offset || 0;
            if (input != null) {
                if (typeof input === "string") {
                    input = timezoneMinutesFromString(input);
                }
                if (Math.abs(input) < 16) {
                    input = input * 60;
                }
                this._offset = input;
                this._isUTC = true;
                if (offset !== input) {
                    addOrSubtractDurationFromMoment(this, moment.duration(offset - input, 'm'), 1, true);
                }
            } else {
                return this._isUTC ? offset : this._d.getTimezoneOffset();
            }
            return this;
        },

        zoneAbbr : function () {
            return this._isUTC ? "UTC" : "";
        },

        zoneName : function () {
            return this._isUTC ? "Coordinated Universal Time" : "";
        },

        parseZone : function () {
            if (typeof this._i === 'string') {
                this.zone(this._i);
            }
            return this;
        },

        hasAlignedHourOffset : function (input) {
            if (!input) {
                input = 0;
            }
            else {
                input = moment(input).zone();
            }

            return (this.zone() - input) % 60 === 0;
        },

        daysInMonth : function () {
            return daysInMonth(this.year(), this.month());
        },

        dayOfYear : function (input) {
            var dayOfYear = round((moment(this).startOf('day') - moment(this).startOf('year')) / 864e5) + 1;
            return input == null ? dayOfYear : this.add("d", (input - dayOfYear));
        },

        weekYear : function (input) {
            var year = weekOfYear(this, this.lang()._week.dow, this.lang()._week.doy).year;
            return input == null ? year : this.add("y", (input - year));
        },

        isoWeekYear : function (input) {
            var year = weekOfYear(this, 1, 4).year;
            return input == null ? year : this.add("y", (input - year));
        },

        week : function (input) {
            var week = this.lang().week(this);
            return input == null ? week : this.add("d", (input - week) * 7);
        },

        isoWeek : function (input) {
            var week = weekOfYear(this, 1, 4).week;
            return input == null ? week : this.add("d", (input - week) * 7);
        },

        weekday : function (input) {
            var weekday = (this.day() + 7 - this.lang()._week.dow) % 7;
            return input == null ? weekday : this.add("d", input - weekday);
        },

        isoWeekday : function (input) {
            // behaves the same as moment#day except
            // as a getter, returns 7 instead of 0 (1-7 range instead of 0-6)
            // as a setter, sunday should belong to the previous week.
            return input == null ? this.day() || 7 : this.day(this.day() % 7 ? input : input - 7);
        },

        get : function (units) {
            units = normalizeUnits(units);
            return this[units]();
        },

        set : function (units, value) {
            units = normalizeUnits(units);
            if (typeof this[units] === 'function') {
                this[units](value);
            }
            return this;
        },

        // If passed a language key, it will set the language for this
        // instance.  Otherwise, it will return the language configuration
        // variables for this instance.
        lang : function (key) {
            if (key === undefined) {
                return this._lang;
            } else {
                this._lang = getLangDefinition(key);
                return this;
            }
        }
    });

    // helper for adding shortcuts
    function makeGetterAndSetter(name, key) {
        moment.fn[name] = moment.fn[name + 's'] = function (input) {
            var utc = this._isUTC ? 'UTC' : '';
            if (input != null) {
                this._d['set' + utc + key](input);
                moment.updateOffset(this);
                return this;
            } else {
                return this._d['get' + utc + key]();
            }
        };
    }

    // loop through and add shortcuts (Month, Date, Hours, Minutes, Seconds, Milliseconds)
    for (i = 0; i < proxyGettersAndSetters.length; i ++) {
        makeGetterAndSetter(proxyGettersAndSetters[i].toLowerCase().replace(/s$/, ''), proxyGettersAndSetters[i]);
    }

    // add shortcut for year (uses different syntax than the getter/setter 'year' == 'FullYear')
    makeGetterAndSetter('year', 'FullYear');

    // add plural methods
    moment.fn.days = moment.fn.day;
    moment.fn.months = moment.fn.month;
    moment.fn.weeks = moment.fn.week;
    moment.fn.isoWeeks = moment.fn.isoWeek;

    // add aliased format methods
    moment.fn.toJSON = moment.fn.toISOString;

    /************************************
        Duration Prototype
    ************************************/


    extend(moment.duration.fn = Duration.prototype, {

        _bubble : function () {
            var milliseconds = this._milliseconds,
                days = this._days,
                months = this._months,
                data = this._data,
                seconds, minutes, hours, years;

            // The following code bubbles up values, see the tests for
            // examples of what that means.
            data.milliseconds = milliseconds % 1000;

            seconds = absRound(milliseconds / 1000);
            data.seconds = seconds % 60;

            minutes = absRound(seconds / 60);
            data.minutes = minutes % 60;

            hours = absRound(minutes / 60);
            data.hours = hours % 24;

            days += absRound(hours / 24);
            data.days = days % 30;

            months += absRound(days / 30);
            data.months = months % 12;

            years = absRound(months / 12);
            data.years = years;
        },

        weeks : function () {
            return absRound(this.days() / 7);
        },

        valueOf : function () {
            return this._milliseconds +
              this._days * 864e5 +
              (this._months % 12) * 2592e6 +
              toInt(this._months / 12) * 31536e6;
        },

        humanize : function (withSuffix) {
            var difference = +this,
                output = relativeTime(difference, !withSuffix, this.lang());

            if (withSuffix) {
                output = this.lang().pastFuture(difference, output);
            }

            return this.lang().postformat(output);
        },

        add : function (input, val) {
            // supports only 2.0-style add(1, 's') or add(moment)
            var dur = moment.duration(input, val);

            this._milliseconds += dur._milliseconds;
            this._days += dur._days;
            this._months += dur._months;

            this._bubble();

            return this;
        },

        subtract : function (input, val) {
            var dur = moment.duration(input, val);

            this._milliseconds -= dur._milliseconds;
            this._days -= dur._days;
            this._months -= dur._months;

            this._bubble();

            return this;
        },

        get : function (units) {
            units = normalizeUnits(units);
            return this[units.toLowerCase() + 's']();
        },

        as : function (units) {
            units = normalizeUnits(units);
            return this['as' + units.charAt(0).toUpperCase() + units.slice(1) + 's']();
        },

        lang : moment.fn.lang,

        toIsoString : function () {
            // inspired by https://github.com/dordille/moment-isoduration/blob/master/moment.isoduration.js
            var years = Math.abs(this.years()),
                months = Math.abs(this.months()),
                days = Math.abs(this.days()),
                hours = Math.abs(this.hours()),
                minutes = Math.abs(this.minutes()),
                seconds = Math.abs(this.seconds() + this.milliseconds() / 1000);

            if (!this.asSeconds()) {
                // this is the same as C#'s (Noda) and python (isodate)...
                // but not other JS (goog.date)
                return 'P0D';
            }

            return (this.asSeconds() < 0 ? '-' : '') +
                'P' +
                (years ? years + 'Y' : '') +
                (months ? months + 'M' : '') +
                (days ? days + 'D' : '') +
                ((hours || minutes || seconds) ? 'T' : '') +
                (hours ? hours + 'H' : '') +
                (minutes ? minutes + 'M' : '') +
                (seconds ? seconds + 'S' : '');
        }
    });

    function makeDurationGetter(name) {
        moment.duration.fn[name] = function () {
            return this._data[name];
        };
    }

    function makeDurationAsGetter(name, factor) {
        moment.duration.fn['as' + name] = function () {
            return +this / factor;
        };
    }

    for (i in unitMillisecondFactors) {
        if (unitMillisecondFactors.hasOwnProperty(i)) {
            makeDurationAsGetter(i, unitMillisecondFactors[i]);
            makeDurationGetter(i.toLowerCase());
        }
    }

    makeDurationAsGetter('Weeks', 6048e5);
    moment.duration.fn.asMonths = function () {
        return (+this - this.years() * 31536e6) / 2592e6 + this.years() * 12;
    };


    /************************************
        Default Lang
    ************************************/


    // Set default language, other languages will inherit from English.
    moment.lang('en', {
        ordinal : function (number) {
            var b = number % 10,
                output = (toInt(number % 100 / 10) === 1) ? 'th' :
                (b === 1) ? 'st' :
                (b === 2) ? 'nd' :
                (b === 3) ? 'rd' : 'th';
            return number + output;
        }
    });

    /* EMBED_LANGUAGES */

    /************************************
        Exposing Moment
    ************************************/

    function makeGlobal(deprecate) {
        var warned = false, local_moment = moment;
        /*global ender:false */
        if (typeof ender !== 'undefined') {
            return;
        }
        // here, `this` means `window` in the browser, or `global` on the server
        // add `moment` as a global object via a string identifier,
        // for Closure Compiler "advanced" mode
        if (deprecate) {
            this.moment = function () {
                if (!warned && console && console.warn) {
                    warned = true;
                    console.warn(
                            "Accessing Moment through the global scope is " +
                            "deprecated, and will be removed in an upcoming " +
                            "release.");
                }
                return local_moment.apply(null, arguments);
            };
        } else {
            this['moment'] = moment;
        }
    }

    // CommonJS module is defined
    if (hasModule) {
        module.exports = moment;
        makeGlobal(true);
    } else if (typeof define === "function" && define.amd) {
        define("moment", function (require, exports, module) {
            if (module.config().noGlobal !== true) {
                // If user provided noGlobal, he is aware of global
                makeGlobal(module.config().noGlobal === undefined);
            }

            return moment;
        });
    } else {
        makeGlobal();
    }
}).call(this);

},{}],6:[function(require,module,exports){
'use strict';

/**
 * Bootstrapping Angular Modules
 */
var app = angular.module('App', [
    'ngCookies',
    'ngResource',
    'ngSanitize',
    'ngAnimate',
    'restangular',
    'ui.router',
    'ui.bootstrap',
    'ui.utils',
    'angulartics',
    'angulartics.google.analytics',
    'nvd3ChartDirectives',
    require('./modules/Modules').name,
    require('./services/Services').name,
    require('./filters/Filters').name,
    require('./directives/Directives').name,
    require('./elements/Elements').name,
    require('./controllers/Controllers').name
]);

/**
 * Configuration & Routing
 */
app.config(require('./Router'));

/**
 * Initialisation
 */
app.run(require('./Run'));

/**
 * Execute!
 */
angular.element(document).ready(function(){

    angular.bootstrap(document, ['App']);

});
},{"./Router":7,"./Run":8,"./controllers/Controllers":10,"./directives/Directives":31,"./elements/Elements":39,"./filters/Filters":77,"./modules/Modules":78,"./services/Services":88}],7:[function(require,module,exports){
'use strict';

var fs = require('fs');

/**
 * Angular Router
 */
module.exports = [
    '$locationProvider', 
    '$stateProvider', 
    '$urlRouterProvider', 
    function ($locationProvider, $stateProvider, $urlRouterProvider) {

        //HTML5 Mode URLs
        $locationProvider.html5Mode(true).hashPrefix('!');

        //redirections, this must be above the stateProvider
        $urlRouterProvider
            .when('/control_panel', '/control_panel/crawling') //setting default child state
            .otherwise('/');

        //precompiled templates, these routes should be used with ui-sref and ui-sref-active
        $stateProvider
            .state(
                'home',
                {
                    url: '/',
                    template: "<div class=\"introduction panel panel_lego panel_transition_white_dark\">\n    <div class=\"container\">\n        <div class=\"panel-body\">\n            <div class=\"row\">\n                <div class=\"col-md-6\">\n                    <div class=\"page-header\">\n                        <h1>SnapSearch is Search Engine Optimisation for Javascript, HTML 5 and Single Page Applications</h1>\n                        <h3>Make your sites crawlable with SnapSearch!</h3>\n                        <button class=\"call-to-action btn btn-primary\" type=\"button\" ng-click=\"modal.signUp()\">\n                            <h4 class=\"call-to-action-text\">Get Started for Free<br /><small>No Credit Card Required</small></h4>\n                        </button>\n                    </div>\n                </div>\n                <div class=\"col-md-6\">\n                    <div class=\"code-group clearfix\" ng-controller=\"CodeGroupCtrl\">\n                        <ul class=\"nav nav-tabs\">\n                            <li class=\"tab\" ng-class=\"{'active': activeCode == 'php'}\">\n                                <button class=\"btn\" ng-click=\"changeCode('php')\">PHP</button>\n                            </li>\n                            <li class=\"tab\" ng-class=\"{'active': activeCode == 'ruby'}\">\n                                <button class=\"btn\" ng-click=\"changeCode('ruby')\">Ruby</button>\n                            </li>\n                            <li class=\"tab\" ng-class=\"{'active': activeCode == 'node.js'}\">\n                                <button class=\"btn\" ng-click=\"changeCode('node.js')\">Node.js</button>\n                            </li>\n                            <li class=\"tab\" ng-class=\"{'active': activeCode == 'python'}\">\n                                <button class=\"btn\" ng-click=\"changeCode('python')\">Python</button>\n                            </li>\n                        </ul>\n                        <div class=\"tab-content clearfix\" ng-switch=\"activeCode\">\n                            <div class=\"tab-panel\" ng-switch-when=\"php\">\n                                <p>Installation:</p>\n                                <syntax syntax-language=\"bash\">composer require snapsearch/snapsearch-client-php</syntax>\n                                <p>Usage:</p>\n                                <syntax class=\"code-usage\" syntax-language=\"php\">$client = new SnapSearchClientPHPClient('email', 'key');\n$detector = new SnapSearchClientPHPDetector;\n$interceptor = new SnapSearchClientPHPInterceptor(\n    $client, \n    $detector\n);\n\n$response = $this-&gt;interceptor-&gt;intercept();\n\nif($response){\n\n    header(' ', true, $response['status']);\n\n    foreach($response['headers'] as $header){\n        if($header['name'] == 'Location'){\n            header($header['name'] . ': ' . $header['value']);\n        }\n    }\n\n    echo $response['html'];\n\n}else{\n\n    //continue with normal operations...\n\n}</syntax>\n                                <a class=\"btn btn-primary btn-fork pull-right\" href=\"https://github.com/SnapSearch/SnapSearch-Client-PHP\" target=\"_blank\">\n                                    <img src=\"assets/img/github_mark.png\" />\n                                    Fork me on Github\n                                </a>                                </div>\n                            <div class=\"tab-panel\" ng-switch-when=\"ruby\">\n                                <p>Installation:</p>\n                                <syntax syntax-language=\"bash\">gem install snapsearch-client-ruby</syntax>\n                                <p>Usage:</p>\n                                <syntax class=\"code-usage\" syntax-language=\"ruby\">require 'rack/snap_search'\n\nuse Rack::SnapSearch do |config|\n    \n    # Required: The email to authenticate with.\n    config.email = 'user@example.com'\n    \n    # Required: The key to authenticate with.\n    config.key = 'API_KEY_HERE'\n    \nend</syntax>\n                                <a class=\"btn btn-primary btn-fork pull-right\" href=\"https://github.com/SnapSearch/SnapSearch-Client-Ruby\" target=\"_blank\">\n                                    <img src=\"assets/img/github_mark.png\" />\n                                    Fork me on Github\n                                </a>\n                            </div>\n                            <div class=\"tab-panel\" ng-switch-when=\"node.js\">\n                                <p>Installation:</p>\n                                <syntax syntax-language=\"bash\">npm install snapsearch-client-node</syntax>\n                                <p>Usage:</p>\n                                <syntax class=\"code-usage\" syntax-language=\"javascript\">var app = express();\nvar client = new SnapSearch.Client();\nvar detector = new SnapSearch.Detector();\nvar interceptor = new SnapSearch.Interceptor(client, detector);\n\napp.use(function (req, res, next) {\n\n    interceptor.intercept(req, function (data) {\n        if (data) {\n            console.log(data);\n            res.send('Was a robot and SnapChat Intercepted it Correctly');\n        } else {\n            next();\n        }\n    });\n\n});</syntax>\n                                <a class=\"btn btn-primary btn-fork pull-right\" href=\"https://github.com/SnapSearch/SnapSearch-Client-Node\" target=\"_blank\">\n                                    <img src=\"assets/img/github_mark.png\" />\n                                    Fork me on Github\n                                </a>\n                            </div>\n                            <div class=\"tab-panel\" ng-switch-when=\"python\">\n                                <p>Installation:</p>\n                                <syntax syntax-language=\"bash\">pip install snapsearch-client-python</syntax>\n                                <p>Usage:</p>\n                                <syntax class=\"code-usage\" syntax-language=\"python\">#coming soon!</syntax>\n                                <a class=\"btn btn-primary btn-fork pull-right\" href=\"https://github.com/SnapSearch/SnapSearch-Client-Python\" target=\"_blank\">\n                                    <img src=\"assets/img/github_mark.png\" />\n                                    Fork me on Github\n                                </a>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>\n<div class=\"demo panel panel_white panel_transition_white_dark\">\n    <div class=\"container\">\n        <div class=\"panel-heading\">\n            <h2 class=\"panel-title\">Try our Demo</h2>\n        </div>\n        <div class=\"panel-body\">\n            <form class=\"demo-form\" ng-controller=\"DemoCtrl\" name=\"demoForm\">\n                <div \n                    class=\"form-group\" \n                    ng-class=\"{\n                        'has-error': demoForm.url.$invalid && demoForm.url.$dirty\n                    }\"\n                >\n                    <div class=\"input-group input-group-lg\">\n                        <input \n                            class=\"form-control\" \n                            type=\"url\" \n                            name=\"url\" \n                            ng-model=\"demo.url\" \n                            required \n                            placeholder=\"http://your-site.com/\" \n                        />\n                        <span class=\"input-group-btn\">\n                            <button \n                                class=\"btn btn-primary\" \n                                type=\"submit\" \n                                ng-disabled=\"demoForm.$invalid\" \n                                ng-click=\"submit(demo)\" \n                            >\n                                Scrape\n                            </button>\n                        </span>\n                    </div>\n                </div>\n                <div class=\"form-errors\" ng-show=\"formErrors\">\n                    <em class=\"text-warning\">Oops! Please fix up these errors:</em>\n                    <ul class=\"form-errors-list\">\n                        <li class=\"form-errors-list-item alert alert-warning\" ng-repeat=\"error in formErrors\">{{error}}</li>\n                    </ul>\n                </div>\n                <div class=\"demo-output\" ng-switch=\"requestingDemoService\">\n                    <p class=\"demo-explanation\" ng-switch-when=\"never\">Try this on a single page application like https://snapsearch.io/. You'll see the difference between how \"javascriptless\" search engine robots view your application without SnapSearch, and how they view your application with SnapSearch.</p>\n                    <img class=\"demo-loading\" ng-switch-when=\"started\" src=\"assets/img/loading.gif\" />\n                    <div class=\"demo-response row\" ng-switch-when=\"finished\" ng-show=\"formSuccess\">\n                        <div class=\"col-sm-6\">\n                            <h4 class=\"demo-response-title\">Source Code without SnapSearch</h4>\n                            <pre class=\"demo-response-code\"><code>{{demoServiceResponse.withoutSnapSearch}}</code></pre>\n                            <span class=\"demo-response-length\">Content Length: {{demoServiceResponse.withoutSnapSearch.length}} <span class=\"text-muted\">(this one should be lower!)</span></span>\n                        </div>\n                        <div class=\"col-sm-6\">\n                            <h4 class=\"demo-response-title\">Source Code with SnapSearch</h4>\n                            <pre class=\"demo-response-code\"><code>{{demoServiceResponse.withSnapSearch}}</code></pre>\n                            <span class=\"demo-response-length\">Content Length: {{demoServiceResponse.withSnapSearch.length}} <span class=\"text-muted\">(this one should be higher!)</span></span>\n                        </div>\n                    </div>\n                </div>\n            </form>\n        </div>\n    </div>\n</div>\n<div class=\"problem-solution panel panel_lego panel_transition_yellow_dark\">\n    <div class=\"container\">\n        <div class=\"panel-heading\">\n            <h2 class=\"panel-title\">Why use SnapSearch?</h2>\n        </div>\n        <div class=\"panel-body\">\n            <h3 class=\"problem-title\">The Problem</h3>\n            <div class=\"problem row\">\n                <div class=\"col-md-6\">\n                    <img src=\"assets/img/user_coding.png\" />\n                    <div class=\"problem-explanation\">\n                        <p>You’ve coded up a javascript enhanced or single page application using the latest HTML5 technologies. Using a modern browser, you can see all the asynchronous or animated content appear.</p>\n                    </div>\n                </div>\n                <div class=\"col-md-6\">\n                    <img src=\"assets/img/spider_reading.png\" />\n                    <div class=\"problem-explanation\">\n                        <p>Search engines however see nothing. This is because search engine robots are simple HTTP clients that cannot execute advanced javascript. They do not execute AJAX, and thus cannot load asynchronous resources, nor can they activate javascript events that make your application dynamic and user friendly.</p>\n                    </div>\n                </div>\n            </div>\n            <h3 class=\"solution-title\">Our Solution</h3>\n            <div class=\"solution row\">\n                <div class=\"col-md-3\">\n                    <img src=\"assets/img/globe.png\" />\n                    <div class=\"solution-explanation\">\n                        <p class=\"request-pipe\">Client initiates an HTTP Request. This client can be search engine robot or a social network crawler such as Facebook or Twitter.</p>\n                        <p class=\"response-pipe\">The client will now receive the true full representation of your site’s content even though it cannot execute javascript.</p>\n                    </div>\n                </div>\n                <div class=\"col-md-3\">\n                    <img src=\"assets/img/application.png\" />\n                    <div class=\"solution-explanation\">\n                        <p class=\"request-pipe\">Your application using our supplied middleware detects whether the client cannot execute javascript. The middleware then initiates a snapshot request to SnapSearch. The request contains the client request URL, authentication credentials and custom API parameters.</p>\n                        <p class=\"response-pipe\">Once the response is received, it outputs your page’s status code, HTML content and any HTTP response headers.</p>\n                    </div>\n                </div>\n                <div class=\"col-md-3\">\n                    <img src=\"assets/img/cloud_service.png\" />\n                    <div class=\"solution-explanation\">\n                        <p class=\"request-pipe\">SnapSearch receives the request and commands our load balanced browser workers to scrape your site based on the client request URL while executing your javascript. Your content will be cached for future requests.</p>\n                        <p class=\"response-pipe\">A response is constructed containing the resulting status code, HTML content, headers and optionally a screenshot of your resource. This is returned to your application’s middleware.</p>\n                    </div>\n                </div>\n                <div class=\"col-md-3\">\n                    <img src=\"assets/img/cache.png\" />\n                    <div class=\"solution-explanation\">\n                        <p class=\"request-pipe\">A cache of the content is securely and safely stored on Amazon S3. All cached content are distinguished by a parameter checksum, so the same URL with different API parameters will be stored independently.</p>\n                        <p class=\"response-pipe\">If a resource has been cached before, SnapSearch will return the cached content. All cached content have adjustable cache lifetime.</p>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>\n<div class=\"features panel panel_yellow panel_transition_white_yellow\">\n    <div class=\"container\">\n        <div class=\"panel-heading\">\n            <h2 class=\"panel-title\">Features</h2>\n        </div>\n        <div class=\"panel-body\">\n            <div class=\"row\" equalise-heights=\".features .feature-object\">\n                <div class=\"feature-object col-sm-6 col-md-4 col-lg-3\">\n                    <h3 class=\"feature-title\">On Demand</h3>\n                    <img class=\"feature-image\" src=\"assets/img/snapsearch_bolt.png\" />\n                    <p class=\"feature-explanation\">Snapshots are created on the fly as you request it from the API. Resources are cached for a default time of 24 hrs.</p>\n                </div>\n                <div class=\"feature-object col-sm-6 col-md-4 col-lg-3\">\n                    <h3 class=\"feature-title\">Real Browser Workers</h3>\n                    <img class=\"feature-image\" src=\"assets/img/firefox.png\" />\n                    <p class=\"feature-explanation\">Our scrapers are powered by nightly versions of Mozilla Firefox. We’re able to run cutting edge HTML5 techniques. Our scrapers evolve as the web evolves.</p>\n                </div>\n                <div class=\"feature-object col-sm-6 col-md-4 col-lg-3\">\n                    <h3 class=\"feature-title\">Google Approved</h3>\n                    <img class=\"feature-image\" src=\"assets/img/google.png\" />\n                    <p class=\"feature-explanation\">SnapSearch complies with the AJAX Crawling Specification by Google. SnapSearch responds with the same content as a normal user would see, so you’re not in violation of cloaking rules.</p>\n                </div>\n                <div class=\"feature-object col-sm-6 col-md-4 col-lg-3\">\n                    <h3 class=\"feature-title\">Powerful Middleware</h3>\n                    <img class=\"feature-image\" src=\"assets/img/middleware.png\" />\n                    <p class=\"feature-explanation\">Our middleware supports a variety of server setups and detection algorithms in order to determine search engine clients. Currently they can detect 196 robots. They can be configured to support custom clients.</p>\n                </div>\n                <div class=\"feature-object col-sm-6 col-md-4 col-lg-3\">\n                    <h3 class=\"feature-title\">Flexibility</h3>\n                    <img class=\"feature-image\" src=\"assets/img/flexibility.png\" />\n                    <p class=\"feature-explanation\">The API supports image snapshots, soft 404s, following redirects, custom headers and status code, cache time settings, width and height of the scraper (useful for infinite scrolling), and custom javascript callbacks that are evaled on the page.</p>\n                </div>\n                <div class=\"feature-object col-sm-6 col-md-4 col-lg-3\">\n                    <h3 class=\"feature-title\">Pay for What You Use</h3>\n                    <img class=\"feature-image\" src=\"assets/img/tiger_face.png\" />\n                    <p class=\"feature-explanation\">You only pay for each usage of the API that initiates a fresh snapshot. There is no minimum monthly fee. Requests hitting the cache is free, and storage of the cache is free.</p>\n                </div>\n                <div class=\"feature-object col-sm-6 col-md-4 col-lg-3\">\n                    <h3 class=\"feature-title\">Load Balanced</h3>\n                    <img class=\"feature-image\" src=\"assets/img/load_balanced.png\" />\n                    <p class=\"feature-explanation\">SnapSearch was built as a fault-tolerant load balanced service. We can handle small and big sites. Scrapers are horizontally scaled according to the number of users.</p>\n                </div>\n                <div class=\"feature-object col-sm-6 col-md-4 col-lg-3\">\n                    <h3 class=\"feature-title\">Analytics</h3>\n                    <img class=\"feature-image\" src=\"assets/img/analytics.png\" />\n                    <p class=\"feature-explanation\">Analytics shows how many requests come from your API key, and what their request parameters are. You can quickly understand your monthly usage, and proximity to the monthly limit. All cached content can be manually refreshed or deleted.</p>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>\n<div class=\"framework-support panel panel_white panel_transition_white_yellow\">\n    <div class=\"container\">\n        <div class=\"panel-heading\">\n            <h2 class=\"panel-title\">We’re 100% framework agnostic!</h2>\n        </div>\n        <div class=\"panel-body\">\n            <div class=\"framework-logos row\">\n                <div class=\"framework-box col-xs-6 col-sm-4 col-md-3\">\n                    <img class=\"framework-logo\" src=\"assets/img/sails_logo.png\" />\n                    <a href=\"http://sailsjs.org/\">Sails.js</a>\n                </div>\n                <div class=\"framework-box col-xs-6 col-sm-4 col-md-3\">\n                    <img class=\"framework-logo\" src=\"assets/img/angular_logo.png\" />\n                    <a href=\"http://angularjs.org/\">AngularJS</a>\n                </div>\n                <div class=\"framework-box col-xs-6 col-sm-4 col-md-3\">\n                    <img class=\"framework-logo\" src=\"assets/img/js_logo.png\" />\n                    <a href=\"http://http://www.html5rocks.com/\">HTML5 Javascript</a>\n                </div>\n                <div class=\"framework-box col-xs-6 col-sm-4 col-md-3\">\n                    <img class=\"framework-logo\" src=\"assets/img/jquery_logo.png\" />\n                    <a href=\"http://jquery.com/\">jQuery</a>\n                </div>\n                <div class=\"framework-box col-xs-6 col-sm-4 col-md-3\">\n                    <img class=\"framework-logo\" src=\"assets/img/backbone_logo.png\" />\n                    <a href=\"http://backbonejs.org/\">Backbone</a>\n                </div>\n                <div class=\"framework-box col-xs-6 col-sm-4 col-md-3\">\n                    <img class=\"framework-logo\" src=\"assets/img/ember_logo.png\" />\n                    <a href=\"http://emberjs.com/\">ember</a>\n                </div>\n                <div class=\"framework-box col-xs-6 col-sm-4 col-md-3\">\n                    <img class=\"framework-logo\" src=\"assets/img/knockout_logo.png\" />\n                    <a href=\"http://knockoutjs.com/\">Knockout</a>\n                </div>\n                <div class=\"framework-box col-xs-6 col-sm-4 col-md-3\">\n                    <img class=\"framework-logo\" src=\"assets/img/meteor_logo.png\" />\n                    <a href=\"https://www.meteor.com/\">Meteor</a>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>",
                    controller: 'HomeCtrl'
                }
            )
            .state(
                'documentation',
                {
                    url: '/documentation',
                    template: "<div class=\"documentation panel panel_lego panel_transition_yellow_dark\">\n    <div class=\"container\">\n        <div class=\"panel-heading\">\n            <h2 class=\"panel-title\">Documentation</h2>\n        </div>\n        <div class=\"panel-body row\">\n            <div class=\"col-md-2\">\n                <nav class=\"btn-group-vertical documentation-nav\" affix affix-top=\"214\" affix-bottom=\"910\">\n                    <a class=\"btn\" scroll=\"introduction\">Introduction</a>\n                    <a class=\"btn\" scroll=\"apiUsage\">API Usage</a>\n                    <a class=\"btn\" scroll=\"parameters\">Parameters</a>\n                    <a class=\"btn\" scroll=\"middleware\">Middleware</a>\n                    <a class=\"btn\" scroll=\"notes\">Notes</a>\n                </nav>\n            </div>\n            <div class=\"col-md-10\">\n                <div class=\"documentation-box\">\n                    <div class=\"documentation-information\">\n                        <h3 class=\"documentation-title\" anchor=\"introduction\">Introduction</h3>\n                        <p>Snapsearch is a search engine optimisation (SEO) and robot proxy for complex front-end javascript & AJAX enabled (potentially realtime) HTML5 web applications.</p>\n                        <p>Search engines like Google's crawler and dumb HTTP clients such as Facebook's image extraction robot cannot execute complex javascript applications. Complex javascript applications include websites that utilise AngularJS, EmberJS, KnockoutJS, Dojo, Backbone.js, Ext.js, jQuery, JavascriptMVC, Meteor, SailsJS, Derby, RequireJS and much more. Basically any website that utilises javascript in order to bring in content and resources asynchronously after the page has been loaded, or utilises javascript to manipulate the page's content while the user is viewing them such as animation.</p>\n                        <p>Snapsearch intercepts any requests made by search engines or robots and sends its own javascript enabled robot to extract your page's content and creates a cached snapshot. This snapshot is then passed through your own web application back to the search engine, robot or browser.</p>\n                        <p>Snapsearch's robot is an automated load balanced Firefox browser. This Firefox browser is kept up to date with the release, beta, alpha and nightly versions (beta, alpha and nightly is still under implementation), so we'll always be able to serve the latest in HTML5 technology. Our load balancer ensures your requests won't be hampered by other user's requests.</p>\n                    </div>\n                    <div class=\"documentation-information\">\n                        <h3 class=\"documentation-title\" anchor=\"apiUsage\">API Usage</h3>\n                        <p>Make sure to register an account on SnapSearch before utilising these libraries. You will need your email and key to access SnapSearch's robot over SSL encrypted HTTP Basic Authorization.</p>\n                        <p>SnapSearch can be easily integrated into your solution stack via our client middleware libraries. These client libraries first automatically detect if an HTTP request comes from a search engine or robot. If it is indeed a search engine, it sends an HTTP POST request to <code>https://snapsearch.io/api/v1/robot</code> passing in parameters configuring how SnapSearch's robot should extract your content. SnapSearch will then send a HTTP GET request to the same URL and return the HTTP response (status code, headers and content) as a JSON response to the library. The client library then returns that data back to your application. You will have to select which data to present to the search engine. It is recommended to return the status code and content but not all of the headers, due to potential header mismatch with content encoding. However if you have specific headers that are important, then first test if it works with a simple HTTP client before deploying it.</p>\n                        <p>Our cache is dependent on your request parameters. So new request parameters will create a new independent snapshot.</p>\n                        <p>All of the middleware libraries are ran on the application level requiring a server side programming language.</p>\n                        <p>For specific usage and installation instructions navigate to specific middleware.</p>\n                        <p>For sites using hash based url schemes please see our <a scroll=\"notes\">notes on Hashbang urls</a>.</p>\n                    </div>\n                    <div class=\"documentation-information\">\n                        <h3 class=\"documentation-title\" anchor=\"parameters\">Request Parameters</h3>\n                        <p>Our HTTPS Robot endpoint <code>/api/v1/robot</code> requires HTTP basic auth with your email and key as username and password respectively, and it accepts these parameters in POST or GET format:</p>\n                        <dl>\n                            <dt>url</dt>\n                            <dd>\n                                Url to scrape. This is the only required parameter. Every other parameter can be left as default.\n                            </dd>\n                            <dt>useragent</dt>\n                            <dd>\n                                Custom user agent. Default: Mozilla/5.0 (OPERATING SYSTEM) Gecko/VERSION Firefox/VERSION SnapSearch\n                                <br /> \n                                Setting this user agent to something that does not include \"SnapSearch\" can cause an infinite interception loop.\n                            </dd>\n                            <dt>width</dt>\n                            <dd>\n                                Width of the Browser. Default: 1280px\n                                <br />\n                                Can be used if you have specific requirements regarding the dimensions of the browser.\n                            </dd>\n                            <dt>height</dt>\n                            <dd>\n                                Height of the Browser. Default: 1024px\n                                <br />\n                                Can be used if you have specific requirements regarding the dimensions of the browser.\n                            </dd>\n                            <dt>imgformat</dt>\n                            <dd>\n                                Image Format. Default: png\n                                <br />\n                                Is used in conjunction with <code>screenshot</code> parameter. Currently only supports png format. \n                            </dd>\n                            <dt>screenshot</dt>\n                            <dd>\n                                Take a screenshot or not. Default: false\n                                <br />\n                                The screenshot is returned as base 64 encoded image and the format is determined by the <code>imgformat</code> parameter.\n                            </dd>\n                            <dt>navigate</dt>\n                            <dd>\n                                Follow redirects or not. Default: false\n                                <br />\n                                In most cases you do not want to follow redirects. If you leave this false, it will return the status, headers and body of the page asking for a redirect. If you switch this to true, it will follow header, client side, javascript, and meta tag redirects.\n                            </dd>\n                            <dt>loadimages</dt>\n                            <dd>\n                                Load images or not. Default: false\n                                <br />\n                                Loading images is not required when doing content scraping, leaving this off results in faster scrapes. However if you are taking screenshots, then you should switch this to true.\n                            </dd>\n                            <dt>javascriptenabled</dt>\n                            <dd>\n                                Process javascript or not. Default: true\n                                <br />\n                                Can be used in circumstances where you don't want to process javascript.\n                            </dd>\n                            <dt>maxtimeout</dt>\n                            <dd>\n                                Maximum millisecond timeout for asynchronous requests. Default: 5000\n                                <br />\n                                This determines how long the browser will wait for asynchronous requests to finish. This means the browser will initiate the capture of the page contents either when all asynchronous requests finish, or at the maximum timeout. The number can be between 1000 to 15000 milliseconds. Longer times will result in potentially slower scrapes, but may capture more content if your site produces many slow asynchronous requests. If you set it too long, the client search engine robot may timeout. Play with this setting to the most optimal scraping speed.\n                            </dd>\n                            <dt>initialwait</dt>\n                            <dd>\n                                Initial millisecond wait before checking asynchronous requests. Default: 1000\n                                <br />\n                                This determines how long the browser will wait before it starts to check for when the asynchronous requests finish. The <code>maxtimeout</code> only begins onces the <code>initialwait</code> finishes. This is intended to allow delayed asynchronous requests or for pages which don't have asynchronous requests but have DOM mutations. The number has to be lower than <code>maxtimeout</code>.\n                            </dd>\n                            <dt>callback</dt>\n                            <dd>\n                                Javascript string to be evaled on the page. Default: false\n                                <br />\n                                This javascript string is evaled on the page after all asynchronous requests have finished but prior to the capture of the page contents. This allows you to do DOM mutations or capture specific content which will be returned as string independent of the page capture. You can assume this string is executed in the context of an anonymous function. So you can just write <code>return 'hello world';</code>. Returning string values is recommended, as other values may not be encoded. These returned values will be stored in the <code>callbackResult</code> property in the response.\n                            </dd>\n                            <dt>meta</dt>\n                            <dd>\n                                Scrape for custom meta tags or not. Default: true\n                                <br />\n                                You can use custom meta tags to change the status code or headers that is returned from the snapshot. This is intended for soft 404 techniques. It will look for meta tags such as <code>&lt;meta name=&quot;snapsearch-status&quot; content=&quot;404&quot; /&gt;</code> and <code>&lt;meta name=&quot;snapsearch-header&quot; content=&quot;Content-Type:text/html&quot; /&gt;</code>\n                            </dd>\n                            <dt>cache</dt>\n                            <dd>\n                                Allow caching of the snapshot or not. Default: true\n                                <br />\n                                This determines two things. The first is whether the snapshot can be acquired from the cache. The second is whether the snapshot can be cached. If this is left as true, snapshots can be returned from the cache if it exists in the cache or cached if it is a fresh snapshot. If this is switched to false, snapshots will always be fresh and the result will not be cached.\n                            </dd>\n                            <dt>cachetime</dt>\n                            <dd>\n                                Cache time in hours. Default: 24\n                                <br />\n                                You can change how long until the snapshots will expire. This number can be between 1 to 100 hours. Shorter cache time will result in more up to date snapshots, but it will use up more of your usage cap. Longer cache time will result in less up to date snapshots, but it will conserve your usage cap. This figure will depend on how often your pages change, what proportion of those pages are changing compared to the rest of the website.\n                            </dd>\n                            <dt>test</dt>\n                            <dd>\n                                Testing the API? Default: false\n                                <br />\n                                If you're in development mode or on localhost, set this to true, and it will only validate your request parameters, but not attempt to actually scrape anything.\n                            </dd>\n                        </dl>\n                        <p>Here is an example response:</p>\n                        <pre><code>[\n    'cache'             =&gt; true/false,\n    'callbackResult'    =&gt; '',\n    'date'              =&gt; 1390382314,\n    'headers'           =&gt; [\n        [\n            'name'  =&gt; 'Content-Type',\n            'value' =&gt; 'text/html'\n        ]\n    ],\n    'html'              =&gt; '&lt;html&gt;&lt;/html&gt;',\n    'message'           =&gt; 'Success/Failed/Validation Errors',\n    'pageErrors'        =&gt; [\n        [\n            'error'   =&gt; 'Error: document.querySelector(...) is null',\n            'trace'   =&gt; [\n                [\n                    'file'      =&gt; 'filename',\n                    'function'  =&gt; 'anonymous',\n                    'line'      =&gt; '41',\n                    'sourceURL' =&gt; 'urltofile'\n                ]\n            ]\n        ]\n    ],\n    'screensot'         =&gt; 'BASE64 ENCODED IMAGE CONTENT',\n    'status'            =&gt; 200\n]</code></pre>\n                        <p>We also record any javascript errors that occur on the page, these are stored in <code>pageErrors</code>, and it can be used for debugging custom <code>callback</code> parameters.</p>\n                    </div>\n                    <div class=\"documentation-information\">\n                        <h3 class=\"documentation-title\" anchor=\"middleware\">Middleware</h3>\n                        <p>SnapSearch officially supports and provides PHP, Ruby, Node.js and Python middleware. All middleware are framework agnostic, and should be able to work within a middleware framework or without.</p>\n                        <ul>\n                            <li><a href=\"https://github.com/SnapSearch/SnapSearch-Client-PHP\">PHP</a> - 1.1.0 (most stable)</li>\n                            <li><a href=\"https://github.com/SnapSearch/SnapSearch-Client-Ruby\">Ruby</a> - 0.1.0 (almost there)</li>\n                            <li><a href=\"https://github.com/SnapSearch/SnapSearch-Client-Node\">Node.js</a> - 0.0.2 (works but requires some improvements)</li>\n                            <li><a href=\"https://github.com/SnapSearch/SnapSearch-Client-Python\">Python</a> - Still under development.</li>\n                        </ul>\n                        <p>All of the middleware are open source, and we welcome pull requests for patches or new middleware implementations. Check out our <a href=\"https://github.com/SnapSearch/\">Github organisation</a> for more.</p>\n                    </div>\n                    <div class=\"documentation-information\">\n                        <h3 class=\"documentation-title\" anchor=\"notes\">Notes</h3>\n                        <h4>Hashbang Urls</h4>\n                        <p>Make sure you are using hash bang urls and not just hash urls. This fits with <a href=\"https://developers.google.com/webmasters/ajax-crawling/docs/specification\">Google's AJAX Crawling Scheme</a>. It makes it easier to identify what is a hash, and what is meant to be path. Remember hash fragments are never passed to the server. The middleware needs to know the full HTTP url or else it won't know where to scrape. This means you will need to rely on the search engine robots to convert hash bang urls to query fragment urls. <strong>This meta tag will need to be on every page: <code>&lt;meta name=&quot;fragment&quot; content=&quot;!&quot; /&gt;</code></strong>. If you are using HTML 5 push state urls, the meta tag is still a good practice as it allows search engines following the AJAX specification to know that your site is a single page application.</p>\n                        <h4>Dealing with non-HTML resources</h4>\n                        <p>You need to make sure that non-HTML resources are not intercepted by SnapSearch. Non-HTML resources refer to:</p>\n                        <ul>\n                            <li>Static files that are served through your application and not through an HTTP server such as NGINX or Apache.</li>\n                            <li>Downloads that served through an application level controller.</li>\n                            <li>Text data interchange formats that are not meant to be used for the end user's browser. For example: JSON, XML, RSS... etc.</li>\n                            <li>API resources that don't display the front end site, but are there for interaction between machines.</li>\n                            <li>Any connections that do not go through the HTTP protocol.</li>\n                        </ul>\n                        <p>You can prevent SnapSearch from intercepting these non-HTML resources by:</p>\n                        <ul>\n                            <li>Setup an array of whitelisted regular expression routes which will be matched against the request URL. SnapSearch will not intercept any routes that are not on the whitelist.</li>\n                            <li>Setup an array of blacklisted regular expression routes which will be matched against the request URL. SnapSearch will not intercept any routes that are on the blacklist.</li>\n                            <li>SnapSearch middleware can optionally check if the URL path has an invalid file extension. Some extensions are valid for HTML resources such as <code>.html</code>, but others such as <code>.js</code> are not. Our middleware has an option to switch on this detection and it will ignore requests that go invalid extensions. It is left off by default due to possible confusion.</li>\n                            <li>In MVC style applications, you may have a single controller which is responsible for displaying the front end code. If you execute our clients inside that particular controller, then you will not have any problems with non-HTML resources, since it can only intercept requests that go to the front end.</li>\n                        </ul>\n                        <h4>SSL issues</h4>\n                        <p>SnapSearch will not be able to capture from sites that have invalid SSL certificates (this means self-signed SSL certificates as well). Make sure your SSL is a valid certificate that will work in a normal browser before using SnapSearch.</p>\n                        <h4>Ensuring Analytics Works with Snapsearch</h4>\n                        <p>When Snapsearch visits your site, it will come with a UserAgent containing \"SnapSearch\". You can however configure this to your own liking. Use this user agent in order to filter out our requests when using web analytics.</p>\n                        <h4>Large content</h4>\n                        <p>SnapSearch middleware will timeout the request if the initial request to your site and its synchronous resources takes longer than 30 seconds.</p>\n                        <h4>Supporting JS disabled Browsers</h4>\n                        <p>It's not possible to detect if the HTTP client supports Javascript on the first page load. Therefore you have to know the user agents beforehand. A workaround involves the HTML Meta Refresh tag. You set an HTML meta refresh tag which will refresh the browser and point it to the same url but with query parameter indicating to the server that the client doesn't run javascript. This meta refresh tag can be then be cancelled using javascript. Another approach would be to use the Noscript tag and place the meta refresh tag there. None of these methods are guaranteed to work. but if you're interested check out: <a href=\"http://stackoverflow.com/q/3252743/582917\">http://stackoverflow.com/q/3252743/582917</a></p>\n                        <h4>Soft 404s</h4>\n                        <p>Soft 404s should be avoided. The final representation to the search engine should be exactly the same as normal user with a normal browser would see. However you can achieve this by using the special meta tags and switching <code>meta</code> request parameter to true.</p>\n                    </div>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>",
                    controller: 'DocumentationCtrl'
                }
            )
            .state(
                'pricing',
                {
                    url: '/pricing',
                    template: "<div class=\"pricing panel panel_lego panel_transition_yellow_dark\">\n    <div class=\"container\">\n        <div class=\"panel-heading\">\n            <h2 class=\"panel-title\">Pricing</h2>\n        </div>\n        <div class=\"panel-body\">\n            <div class=\"pricing-box\">\n                <h3 class=\"pricing-heading\">Pay for What You Use</h3>\n                <h4 class=\"pricing-subheading\">never exceed your budget with a flexible cap</h4>\n                <p class=\"price-per-month\">${{pricePerUsage}} AUD per Usage*<br /><small>(free {{freeUsageCap}} Usages Per Month)</small></p>\n                <dl class=\"feature-set\">\n                    <dt>Pages</dt>\n                    <dd>Unlimited</dd>\n                    <dt>Free Usage Cap</dt>\n                    <dd>{{freeUsageCap}} Usages per Month<br /><small>(good for small applications)</small></dd>\n                    <dt>Cache Requests</dt>\n                    <dd>Unlimited</dd>\n                    <dt>Cache Storage</dt>\n                    <dd>Unlimited</dd>\n                    <dt>Cache Lifetime</dt>\n                    <dd>Configurable from 1 - 200 hrs</dd>\n                    <dt>Feature Set</dt>\n                    <dd>Complete</dd>\n                </dl>\n                <div class=\"usage-price-explanation\">\n                    <p class=\"lead\">* What is a Usage?</p>\n                    <p>Each request to the SnapSearch API either results in content being dynamically scraped using the SnapSearch scrapers, or content being fetched from the cache.  A usage refers to a request that does not hit the cache, and initiates a fresh snapshot.</p>\n                    <p>The number of usages per month is used for the calculation of the cost per month. The number of requests per month is not capped, but the number of usages per month can be capped in your control panel.</p>\n                    <p>If you’ve exceeded your usage cap, our middleware simply returns your content normally. So it’s best to keep your cap above average in case of search engine traffic spikes.</p>\n                </div>\n            </div>\n            <div class=\"cost-estimator\">\n                <h3 class=\"cost-heading\">Cost Estimator</h3>\n                <div class=\"cost-explanation\">\n                    <p>Use this tool to estimate your monthly payment. If you’re using a 24 hr cache lifetime, <strong>requests per month are roughly cut in half when converted to usages per month</strong>. The cost per month is calculated from total usages minus free usage cap, multiplied by the price per usage, rounded to the nearest cent.</p>\n                    <p>This is an estimation, to get proper usage figures, we recommend that you try our service with the free usage cap, and use our analytics to determine how many usages per month your web application needs.</p>\n                    <p>Our research shows that most small websites generate between 1000 to 2000 requests per month and hence 500 to 1000 usages per month.</p>\n                    <p>Checkout our <a href=\"documentation\">strategies</a> for reducing usages per month.</p>\n                </div>\n                <form class=\"cost-calculator\" ng-controller=\"CostCalculatorCtrl\" name=\"costCalculatorForm\">\n                    <h4>Usages per Month</h4>\n                    <div class=\"form-group\">\n                        <input \n                            class=\"form-control input-lg\" \n                            type=\"number\" \n                            name=\"quantity\" \n                            ng-model=\"cost.quantity\" \n                            placeholder=\"1000\" \n                            maxlength=\"5\" \n                        />\n                        <span class=\"help-block\">Try a number above the free usage cap.</span>\n                    </div>\n                    <h4>Cost per Month <small>(discounting Free Usage Cap)</small></h4>\n                    <p class=\"calculated-price-per-month\">${{price}} AUD per Month</p>\n                </form>\n                <em class=\"custom-plan\">Need an absurd number of Usages Per Month?<br /><a href=\"http://www.google.com/recaptcha/mailhide/d?k=01KxkEAwiT1nfx-BhMp7WKWg==&amp;c=iaojzr8kgOuD5gSlcb7Tdexe9yVtnztvwDbDcomRY24=\" onclick=\"window.open('http://www.google.com/recaptcha/mailhide/d?k\\07501KxkEAwiT1nfx-BhMp7WKWg\\75\\75\\46c\\75iaojzr8kgOuD5gSlcb7Tdexe9yVtnztvwDbDcomRY24\\075', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"Reveal this e-mail address\">Contact us!</a> We can figure out an economical plan for your business.</em>\n            </div>\n        </div>\n    </div>\n</div>",
                    controller: 'PricingCtrl'
                }
            )
            .state(
                'controlPanel',
                {
                    url: '/control_panel',
                    template: "<div class=\"control-panel panel panel_lego panel_transition_yellow_dark\">\n    <div class=\"container\">\n        <div class=\"panel-body row\" ng-show=\"loggedIn\">\n            <div class=\"col-md-2\">\n                <nav class=\"btn-group-vertical control-panel-nav\" affix affix-top=\"214\" affix-bottom=\"910\">\n                    <a class=\"btn\" ui-sref-active=\"active\" ui-sref=\".crawling\">Crawling</a>\n                    <a class=\"btn\" ui-sref-active=\"active\" ui-sref=\".cache\">Cache</a>\n                    <a class=\"btn\" ui-sref-active=\"active\" ui-sref=\".payments\">Payments</a>\n                    <a class=\"btn\" ui-sref-active=\"active\" ui-sref=\".billing\">Billing</a>\n                    <a class=\"btn\" ui-sref-active=\"active\" ui-sref=\".account\">Account</a>\n                </nav>\n            </div>\n            <div class=\"col-md-10\">\n                <div class=\"control-box\">\n                    <div ui-view></div>\n                </div>\n            </div>\n        </div>\n        <div class=\"panel-body\" ng-show=\"loggedOut\">\n            <p>You must be logged in to view this page!</p>\n        </div>\n    </div>\n</div>",
                    controller: 'ControlPanelCtrl'
                }
            )
            .state(
                'controlPanel.crawling', //default controlPanel childstate
                {
                    url: '/crawling',
                    template: "<div class=\"crawling\">\n    <h2 class=\"control-title\">Crawling Statistics</h2>\n    <em class=\"api-key\">API Key: {{userAccount.sharedKey}}</em>\n    <div class=\"telemetry-block overview\">\n        <h3 class=\"telemetry-title\">Overview</h3>\n        <em class=\"telemetry-emphasis\">This Cycle - from <strong>{{chargeCycle.beginning.format('YYYY/MM/DD')}}</strong> to <strong>{{chargeCycle.ending.format('YYYY/MM/DD')}}</strong></em>\n        <div class=\"row overview-requests-usages-tally no-gutter\">\n            <div class=\"col-sm-4 tally-col\">\n                <div class=\"tally-block tally_block_request\">\n                    <span class=\"tally-bg\">R</span>\n                    <p class=\"tally-number\">{{userAccount.apiRequests}}</p>\n                </div>\n                <p class=\"tally-description\">Requests Received</p>\n            </div>\n            <div class=\"col-sm-4 tally-col\">\n                <div class=\"tally-block tally_block_usage\">\n                    <span class=\"tally-bg\">U</span>\n                    <p class=\"tally-number\">{{userAccount.apiUsage}}</p>\n                </div>\n                <p class=\"tally-description\">Usages Used</p>\n            </div>\n            <div class=\"col-sm-4 tally-col\">\n                <div class=\"tally-block tally_block_available\">\n                    <span class=\"tally-bg\">A</span>\n                    <p class=\"tally-number\">{{userAccount.apiLimit - userAccount.apiUsage}}</p>\n                </div>\n                <p class=\"tally-description\">Usages Available</p>\n            </div>\n        </div>\n        <div class=\"progress progress-striped active usage-bar\">\n            <div class=\"progress-bar\" ng-style=\"{ width: userAccount.apiUsagePercentage + '%' }\"></div>\n        </div>\n        <p class=\"telemetry-emphasis\">Used up {{userAccount.apiUsagePercentage}}% of API Usage Cap this cycle.</p>\n    </div>\n    <div class=\"telemetry-block\">\n        <h3 class=\"telemetry-title\">Monthly Usage Cap</h3>\n        <form class=\"api-limit-modifier form-horizontal\" name=\"apiLimitModifierForm\">\n            <div class=\"form-errors\" ng-show=\"formErrors\">\n                <em class=\"text-warning\">Oops! Please fix up these errors:</em>\n                <ul class=\"form-errors-list\">\n                    <li class=\"form-errors-list-item alert alert-warning\" ng-repeat=\"error in formErrors\">{{error}}</li>\n                </ul>\n            </div>\n            <div class=\"form-success alert alert-success\" ng-show=\"formSuccess\">\n                {{formSuccess}}\n            </div>\n            <dl>\n                <dt>\n                    <label class=\"control-label\" for=\"apiLimitModifierFormQuantity\">Enter Usage Cap:</label>\n                </dt>\n                <dd \n                    class=\"input-group\" \n                    ng-class=\"{\n                        'has-error': apiLimitModifierForm.quantity.$invalid && apiLimitModifierForm.quantity.$dirty\n                    }\" \n                >\n                    <input \n                        id=\"apiLimitModifierFormQuantity\"\n                        class=\"form-control\" \n                        type=\"number\" \n                        name=\"quantity\" \n                        ng-model=\"apiLimitModifier.quantity\" \n                        ng-disabled = \"!hasBillingDetails\" \n                        min-valid=\"{{userAccount.apiFreeLimit}}\" \n                        maxlength=\"10\" \n                        required \n                    />\n                    <span class=\"input-group-btn\">\n                        <button \n                            class=\"btn btn-primary\" \n                            type=\"submit\" \n                            ng-disabled=\"apiLimitModifierForm.$invalid || !hasBillingDetails\" \n                            ng-click=\"changeLimit(apiLimitModifier)\" \n                        >\n                            Change Cap\n                        </button>\n                    </span>\n                </dd>\n                <dt>Free Usage Cap:</dt>\n                <dd>{{userAccount.apiFreeLimit}}</dd>\n                <dt>Cost Per Month:<br /><small>(discounting free usage cap)</small></dt>\n                <dd>${{price}} AUD</dd>\n            </dl>\n        </form>\n    </div>\n    <div class=\"telemetry-block\">\n        <h3 class=\"telemetry-title\">API Requests & Usage History</h3>\n        <div class=\"history-buttons button-group\">\n            <button class=\"btn btn-primary\" type=\"button\" ng-click=\"backwardGraph()\">Backward</button>\n            <button class=\"btn btn-primary\" type=\"button\" ng-click=\"forwardGraph()\">Forward</button>\n        </div>\n        <div \n            id=\"usageHistoryChart\" \n            class=\"history-chart\" \n            nvd3-line-chart \n            data=\"usageHistoryData\" \n            showXAxis=\"true\" \n            showYAxis=\"true\" \n            tooltips=\"true\" \n            interactive=\"true\" \n            showLegend=\"true\" \n            showControls=\"true\" \n            xAxisTickFormat=\"xAxisDateFormatFunction()\" \n            noData=\"No API history yet!\" \n        ></div>\n    </div>\n    <div class=\"telemetry-block\">\n        <h3 class=\"telemetry-title\">Domain Distinction</h3>\n        <p>Coming Soon!</p>\n    </div>\n    <div class=\"telemetry-block\">\n        <h3 class=\"telemetry-title\">Log</h3>\n        <p>Coming Soon!</p>\n    </div>\n</div>",
                    controller: 'ControlCrawlingCtrl'
                }
            )
            .state(
                'controlPanel.cache',
                {
                    url: '/cache',
                    template: "<div class=\"cache\">\n    <h2 class=\"control-title\">Cache Statistics</h2>\n    <em class=\"api-key\">API Key: {{userAccount.sharedKey}}</em>\n    <p class=\"placeholder-text\">Coming Soon!</p>\n    <div ng-show=\"false\">\n        <div class=\"telemetry-block overview\">\n            <h3>Overview</h3>\n            <div class=\"row overview-cache\">\n                <div class=\"col-sm-6\">\n                    <p>2040</p>\n                    <p>Pages Cached</p>\n                </div>\n                <div class=\"col-sm-6\">\n                    <p>48 KB</p>\n                    <p>Storage Used</p>\n                </div>\n            </div>\n        </div>\n        <div class=\"telemetry-block\">\n            <h3>Domain Distinction</h3>\n        </div>\n        <div class=\"telemetry-block\">\n            <h3>Cached Snapshots</h3>\n            <form class=\"cache-form\" name=\"cacheForm\">\n                <div \n                    class=\"form-group\" \n                    ng-class=\"{\n                        'has-error': cacheForm.url.$invalid && cacheForm.url.$dirty\n                    }\"\n                >\n                    <div class=\"input-group\">\n                        <input \n                            class=\"form-control\" \n                            type=\"url\" \n                            name=\"url\" \n                            ng-model=\"cache.url\" \n                            required \n                            placeholder=\"http://your-site.com/\" \n                        />\n                        <span class=\"input-group-btn\">\n                            <button \n                                class=\"btn btn-primary\" \n                                type=\"submit\" \n                                ng-disabled=\"cacheForm.$invalid\" \n                                ng-click=\"submit(cache)\" \n                            >\n                                Prime Cache\n                            </button>\n                        </span>\n                        <span class=\"help-block\">Priming a snapshot will use up one usage.</span>\n                    </div>\n                </div>\n                <div class=\"form-errors\" ng-show=\"formErrors\">\n                    <em class=\"text-warning\">Oops! Please fix up these errors:</em>\n                    <ul class=\"form-errors-list\">\n                        <li class=\"form-errors-list-item alert alert-warning\" ng-repeat=\"error in formErrors\">{{error}}</li>\n                    </ul>\n                </div>\n                <div class=\"form-success alert alert-success\" ng-show=\"formSuccess\">\n                    {{formSuccess}}\n                </div>\n            </form>\n            <table>\n                <thead>\n                    <th>id</th>\n                    <th>snapshot (this would be a link)</th>\n                    <th>date</th>\n                    <th>expires</th>\n                    <th>refresh</th>\n                    <th>delete</th>\n                </thead>\n                <tbody>\n                    <tr ng-repeat=\"record in snapshots\">\n                        \n                    </tr>\n                </tbody>\n            </table>\n        </div>\n    </div>\n</div>",
                    controller: 'ControlCacheCtrl'
                }
            )
            .state(
                'controlPanel.payments',
                {
                    url: '/payments',
                    template: "<div class=\"payments\">\n    <h2 class=\"control-title\">Payment History</h2>\n    <em class=\"api-key\">API Key: {{userAccount.sharedKey}}</em>\n    <p class=\"placeholder-text\">Coming Soon!</p>\n    <div ng-show=\"false\">\n        <div class=\"telemetry-block overview\">\n            <h3>Overview</h3>\n            <div class=\"row overview-payments\">\n                <div class=\"col-sm-6\">\n                    <p>2040</p>\n                    <p>Usages Used</p>\n                </div>\n                <div class=\"col-sm-6\">\n                    <p>$20.00 AUD</p>\n                    <p>Bill this Month</p>\n                </div>\n            </div>\n        </div>\n        <div class=\"telemetry-block\">\n            <table ng-show=\"paymentHistory\">\n                <thead>\n                    <th>id</th>\n                    <th>date</th>\n                    <th>usage rate (including left over usage from previous cycles)</th>\n                    <th>amount</th>\n                    <th>currency</th>\n                    <th>invoice</th>\n                </thead>\n                <tbody>\n                    <tr ng-repeat=\"record in paymentHistory\">\n                        <td>{{record.id}}</td>\n                        <td>{{record.date}}</td>\n                        <td>{{record.usageRate}}</td>\n                        <td>{{record.amount}}</td>\n                        <td>{{record.currency}}</td>\n                        <td><a href=\"#\">{{record.invoice}}</a></td>\n                    </tr>\n                </tbody>\n            </table>\n            <p ng-show=\"!paymentHistory\">No payment history.</p>\n        </div>\n    </div>\n</div>",
                    controller: 'ControlPaymentsCtrl'
                }
            )
            .state(
                'controlPanel.billing',
                {
                    url: '/billing',
                    template: "<div class=\"billing\">\n    <h2 class=\"control-title\">Billing Information</h2>\n    <em class=\"api-key\">API Key: {{userAccount.sharedKey}}</em>\n    <p class=\"placeholder-text\">Coming Soon!</p>\n    <div ng-show=\"false\">\n        <div class=\"telemetry-block\">\n            <button ng-click=\"createCard\">Add a Card</button>\n            <table>\n                <thead>\n                    <th>id</th>\n                    <th>card number hint</th>\n                    <th>active</th>\n                    <th>update</th>\n                    <th>delete</th>\n                </thead>\n                <tbody>\n                    <tr ng-repeat=\"record in cards\">\n                        <td></td>\n                        <td></td>\n                        <td></td>\n                        <td><button>Update Card</button></td>\n                        <td><button>Delete Card</button></td>\n                    </tr>\n                </tbody>\n            </table>\n        </div>\n    </div>\n</div>",
                    controller: 'ControlBillingCtrl'
                }
            )
            .state(
                'controlPanel.account',
                {
                    url: '/account',
                    template: "<div class=\"account\">\n    <h2 class=\"control-title\">Account Details</h2>\n    <em class=\"api-key\">API Key: {{userAccount.sharedKey}}</em>\n    <p class=\"placeholder-text\">Coming Soon!</p>\n    <div ng-show=\"false\">\n        <button ng-click=\"regenerateApiKey()\">Regenerate API Key</button>\n        <form class=\"form-horizontal\" name=\"accountForm\">\n            <div \n                class=\"form-group\" \n                ng-class=\"{\n                    'has-error': accountForm.username.$invalid && accountForm.username.$dirty\n                }\"\n            >\n                <label class=\"control-label col-sm-2\" for=\"accountFormUsername\">Username:</label>\n                <div class=\"col-sm-10\">\n                    <input id=\"accountFormUsername\" class=\"form-control\" type=\"text\" name=\"username\" ng-model=\"account.username\" required ng-minlength=\"2\" ng-maxlength=\"100\" />\n                    <span class=\"help-block\" ng-show=\"accountForm.username.$error.required\">Required</span>\n                </div>\n            </div>\n            <div \n                class=\"form-group\" \n                ng-class=\"{\n                    'has-error': accountForm.email.$invalid && accountForm.email.$dirty\n                }\"\n            >\n                <label class=\"control-label col-sm-2\" for=\"accountFormEmail\">Email:</label>\n                <div class=\"col-sm-10\">\n                    <input id=\"accountFormEmail\" class=\"form-control\" type=\"email\" name=\"email\" ng-model=\"account.email\" required ng-minlength=\"2\" ng-maxlength=\"100\" />\n                    <span class=\"help-block\" ng-show=\"accountForm.email.$error.required\">Required</span>\n                </div>\n            </div>\n            <div \n                class=\"form-group\" \n                ng-class=\"{\n                    'has-error': accountForm.password.$invalid && accountForm.password.$dirty\n                }\"\n            >\n                <label class=\"control-label col-sm-2\" for=\"accountFormPassword\">Password:</label>\n                <div class=\"col-sm-10\">\n                    <input id=\"accountFormPassword\" class=\"form-control\" type=\"password\" name=\"password\" ng-model=\"account.password\" required ng-minlength=\"2\" ng-maxlength=\"100\" />\n                    <span class=\"help-block\" ng-show=\"accountForm.password.$error.required\">Required</span>\n                </div>\n            </div>\n            <div \n                class=\"form-group\" \n                ng-class=\"{\n                    'has-error': accountForm.passwordConfirm.$invalid && accountForm.passwordConfirm.$dirty\n                }\"\n            >\n                <label class=\"control-label col-sm-2\" for=\"accountFormPasswordConfirm\">Password Confirm:</label>\n                <div class=\"col-sm-10\">\n                    <input id=\"accountFormPasswordConfirm\" class=\"form-control\" type=\"passwordConfirm\" name=\"passwordConfirm\" ng-model=\"account.passwordConfirm\" required ng-minlength=\"2\" ng-maxlength=\"100\" password-match=\"account.password\" />\n                    <span class=\"help-block\" ng-show=\"accountForm.passwordConfirm.$error.required\">Required</span>\n                </div>\n            </div>\n            <div class=\"form-errors\" ng-show=\"formErrors\">\n                <em class=\"text-warning\">Oops! Please fix up these errors:</em>\n                <ul class=\"form-errors-list\">\n                    <li class=\"form-errors-list-item alert alert-warning\" ng-repeat=\"error in formErrors\">{{error}}</li>\n                </ul>\n            </div>\n            <div class=\"form-success alert alert-success\" ng-show=\"formSuccess\">\n                {{formSuccess}}\n            </div>\n            <button class=\"btn btn-primary\" ng-click=\"updateAccount(account)\" ng-disabled=\"accountForm.$invalid\">Update</button>\n        </form>\n    </div>\n</div>",
                    controller: 'ControlAccountCtrl'
                }
            )
            .state(
                'terms',
                {
                    url: '/terms',
                    template: "<div class=\"terms panel panel_lego panel_transition_yellow_dark\">\n    <div class=\"container\">\n        <div class=\"panel-heading\">\n            <h2 class=\"panel-title\">SnapSearch Terms of Service (\"Agreement\")</h2>\n        </div>\n        <div class=\"panel-body\">\n            <p>This Agreement was last modified on March 05, 2014.</p>\n\n            <p>Please read these Terms of Service (\"Agreement\", \"Terms of Service\") carefully before using https://snapsearch.io (\"the Site\") operated by Golden World (au) pty ltd (\"us\", \"we\", or \"our\"). This Agreement sets forth the legally binding terms and conditions for your use of the Site at https://snapsearch.io.</p>\n\n            <p>By accessing or using the Site in any manner, including, but not limited to, visiting or browsing the Site or contributing content or other materials to the Site, you agree to be bound by these Terms of Service. Capitalized terms are defined in this Agreement.</p>\n\n            <p><strong>Intellectual Property</strong><br />The Site and its original content, features and functionality are owned by Golden World (au) pty ltd and are protected by international copyright, trademark, patent, trade secret and other intellectual property or proprietary rights laws.</p>\n\n            <p><strong>Termination</strong><br />We may terminate your access to the Site, without cause or notice, which may result in the forfeiture and destruction of all information associated with you. All provisions of this Agreement that by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity, and limitations of liability.</p>\n\n            <p><strong>Links To Other Sites</strong><br />Our Site may contain links to third-party sites that are not owned or controlled by Golden World (au) pty ltd.</p>\n\n            <p>Golden World (au) pty ltd has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third party sites or services. We strongly advise you to read the terms and conditions and privacy policy of any third-party site that you visit.</p>\n\n            <p><strong>Governing Law</strong><br />This Agreement (and any further rules, polices, or guidelines incorporated by reference) shall be governed and construed in accordance with the laws of NSW, Australia, without giving effect to any principles of conflicts of law.</p>\n\n            <p><strong>Changes To This Agreement</strong><br />We reserve the right, at our sole discretion, to modify or replace these Terms of Service by posting the updated terms on the Site. Your continued use of the Site after any such changes constitutes your acceptance of the new Terms of Service.</p>\n\n            <p>Please review this Agreement periodically for changes. If you do not agree to any of this Agreement or any changes to this Agreement, do not use, access or continue to access the Site or discontinue any use of the Site immediately.</p>\n\n            <p><strong>Contact Us</strong><br />If you have any questions about this Agreement, please contact us.</p>\n        </div>\n    </div>\n</div>",
                    controller: 'TermsCtrl'
                }
            )
            .state(
                'privacy',
                {
                    url: '/privacy',
                    template: "<div class=\"privacy panel panel_lego panel_transition_yellow_dark\">\n    <div class=\"container\">\n        <div class=\"panel-heading\">\n            <h2 class=\"panel-title\">SnapSearch Privacy Policy</h2>\n        </div>\n        <div class=\"panel-body\">\n            <p>This Privacy Policy was last modified on March 05, 2014.</p>\n\n            <p>Golden World (au) pty ltd (\"us\", \"we\", or \"our\") operates https://snapsearch.io (the \"Site\"). This page informs you of our policies regarding the collection, use and disclosure of Personal Information we receive from users of the Site.</p>\n\n            <p>We use your Personal Information only for providing and improving the Site. By using the Site, you agree to the collection and use of information in accordance with this policy. Unless otherwise defined in this Privacy Policy, terms used in this Privacy Policy have the same meanings as in our Terms and Conditions, accessible at https://snapsearch.io.</p>\n\n            <p><strong>Information Collection And Use</strong><br />While using our Site, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you. Personally identifiable information may include, but is not limited to, your name, email address, postal address and phone number (\"Personal Information\").</p>\n\n            <p><strong>Log Data</strong><br />Like many site operators, we collect information that your browser sends whenever you visit our Site (\"Log Data\"). This Log Data may include information such as your computer's Internet Protocol (\"IP\") address, browser type, browser version, the pages of our Site that you visit, the time and date of your visit, the time spent on those pages and other statistics.</p>\n\n            <p><strong>Cookies</strong><br />Cookies are files with small amount of data, which may include an anonymous unique identifier. Cookies are sent to your browser from a web site and stored on your computer's hard drive.</p>\n\n            <p>Like many sites, we use \"cookies\" to collect information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, you may not be able to use some portions of our Site.</p>\n\n            <p><strong>Security</strong><br />The security of your Personal Information is important to us, but remember that no method of transmission over the Internet, or method of electronic storage, is 100% secure. While we strive to use commercially acceptable means to protect your Personal Information, we cannot guarantee its absolute security.</p>\n\n            <p><strong>Links To Other Sites</strong><br />Our Site may contain links to other sites that are not operated by us. If you click on a third party link, you will be directed to that third party's site. We strongly advise you to review the Privacy Policy of every site you visit.</p>\n            \n            <p>Golden World (au) pty ltd has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third party sites or services.</p>\n\n            <p><strong>Changes To This Privacy Policy</strong><br />Golden World (au) pty ltd may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on the Site. You are advised to review this Privacy Policy periodically for any changes.</p>\n\n            <p><strong>Contact Us</strong><br />If you have any questions about this Privacy Policy, please contact us.</p>\n        </div>\n    </div>\n</div>",
                    controller: 'PrivacyCtrl'
                }
            );

    }
];
},{"fs":1}],8:[function(require,module,exports){
'use strict';

var settings = require('./Settings');

/**
 * Angular Initialisation & Front Controller
 *
 * @param {Object}   $rootScope
 * @param {Object}   $cookies
 * @param {Object}   $http
 * @param {Object}   $state 
 * @param {Object}   $stateParams
 */
module.exports = [
    '$rootScope',
    '$cookies',
    '$http',
    '$state',
    '$stateParams',
    'BaseUrlConst',
    function($rootScope, $cookies, $http, $state, $stateParams, BaseUrlConst){
        
        //PROVIDING STATE ON ROOTSCOPE
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;

        //CONFIGURATION
        $rootScope.settings = settings;

        //PROVIDING BASE URL
        $rootScope.baseUrl = BaseUrlConst;

    }
];
},{"./Settings":9}],9:[function(require,module,exports){
'use strict';

module.exports = {
    meta: {
        email: 'enquiry@snapsearch.io',
        price: 0.002,
        freeUsageCap: 1000,
        chatUrl: 'http://www.hipchat.com/gz6yae0iP'
    },
    apiKeys: {}
};
},{}],10:[function(require,module,exports){
'use strict';

/**
 * Controllers
 */
angular.module('App.Controllers', [])
    //common
    .controller('AppCtrl', require('./common/AppCtrl'))
    .controller('HeaderCtrl', require('./common/HeaderCtrl'))
    //home
    .controller('HomeCtrl', require('./home/HomeCtrl'))
    .controller('CodeGroupCtrl', require('./home/CodeGroupCtrl'))
    .controller('DemoCtrl', require('./home/DemoCtrl'))
    //home
    .controller('DocumentationCtrl', require('./documentation/DocumentationCtrl'))
    //pricing
    .controller('PricingCtrl', require('./pricing/PricingCtrl'))
    .controller('CostCalculatorCtrl', require('./pricing/CostCalculatorCtrl'))
    //control panel
    .controller('ControlPanelCtrl', require('./control_panel/ControlPanelCtrl'))
    .controller('ControlCrawlingCtrl', require('./control_panel/ControlCrawlingCtrl'))
    .controller('ControlCacheCtrl', require('./control_panel/ControlCacheCtrl'))
    .controller('ControlPaymentsCtrl', require('./control_panel/ControlPaymentsCtrl'))
    .controller('ControlBillingCtrl', require('./control_panel/ControlBillingCtrl'))
    .controller('ControlAccountCtrl', require('./control_panel/ControlAccountCtrl'))
    //terms
    .controller('TermsCtrl', require('./terms/TermsCtrl'))
    //privacy
    .controller('PrivacyCtrl', require('./privacy/PrivacyCtrl'));

module.exports = angular.module('App.Controllers');
},{"./common/AppCtrl":11,"./common/HeaderCtrl":12,"./control_panel/ControlAccountCtrl":17,"./control_panel/ControlBillingCtrl":18,"./control_panel/ControlCacheCtrl":19,"./control_panel/ControlCrawlingCtrl":20,"./control_panel/ControlPanelCtrl":21,"./control_panel/ControlPaymentsCtrl":22,"./documentation/DocumentationCtrl":23,"./home/CodeGroupCtrl":24,"./home/DemoCtrl":25,"./home/HomeCtrl":26,"./pricing/CostCalculatorCtrl":27,"./pricing/PricingCtrl":28,"./privacy/PrivacyCtrl":29,"./terms/TermsCtrl":30}],11:[function(require,module,exports){
'use strict';

var fs = require('fs');

/**
 * App Controller
 * 
 * @param {Object} $scope
 * @param {Object} $modal
 */
module.exports = ['$scope', '$modal', '$state', 'BusyLoopServ', 'UserSystemServ', function ($scope, $modal, $state, BusyLoopServ, UserSystemServ) {

    //no need to cancel this, it will check if the session is active every 20 seconds
    BusyLoopServ(function () {
        UserSystemServ.getSession();
    }, 20000);

    $scope.modal = {};
    $scope.auth = {};

    /**
     * In the future, these 2 functions opening up the signup and login modal could be replaced with "modal" states that transition to and from the parent state which would whichever state that the person activated the modal box.
     * Watch: https://github.com/angular-ui/ui-router/issues/92
     * Then these states could be bound to a particular URL.
     * Also look into multiple inheritance of states.
     */

    $scope.modal.signUp = function () {

        $modal.open({
            template: "<div class=\"modal-header\">\n    <h3>Sign Up</h3>\n</div>\n<div class=\"modal-body\">\n    <form name=\"signupForm\">\n        <div \n            class=\"form-group clearfix\" \n            ng-class=\"{\n                'has-error': signupForm.username.$invalid && signupForm.username.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"signupFormUsername\">Username:</label>\n            <div class=\"col-sm-10\">\n                <input id=\"signupFormUsername\" class=\"form-control\" type=\"text\" name=\"username\" ng-model=\"user.username\" required ng-minlength=\"2\" ng-maxlength=\"100\" />\n                <span class=\"help-block\" ng-show=\"signupForm.username.$error.required\">Required</span>\n                <span class=\"help-block\" ng-show=\"signupForm.username.$error.minlength\">Username is too short</span>\n                <span class=\"help-block\" ng-show=\"signupForm.username.$error.maxlength\">Username is too long</span>\n            </div>\n        </div>\n        <div \n            class=\"form-group clearfix\" \n            ng-class=\"{\n                'has-error': signupForm.email.$invalid && signupForm.email.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"signupFormEmail\">Email:</label>\n            <div class=\"col-sm-10\">\n                <input id=\"signupFormEmail\" class=\"form-control\" type=\"email\" name=\"email\" ng-model=\"user.email\" required ng-maxlength=\"100\" />\n                <span class=\"help-block\" ng-show=\"signupForm.email.$error.required\">Required</span>\n                <span class=\"help-block\" ng-show=\"signupForm.email.$error.maxlength\">Email is too long</span>\n                <span class=\"help-block\" ng-show=\"signupForm.email.$error.email\">Email is invalid</span>\n            </div>\n        </div>\n        <div \n            class=\"form-group clearfix\"\n            ng-class=\"{\n                'has-error': signupForm.password.$invalid && signupForm.password.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"signupFormPassword\">Password:</label>\n            <div class=\"col-sm-10\">\n                <input \n                    id=\"signupFormPassword\" \n                    class=\"form-control\" \n                    type=\"password\" \n                    name=\"password\" \n                    ng-model=\"user.password\" \n                    required \n                    ng-minlength=\"6\" \n                    ng-maxlength=\"100\" \n                />\n                <span class=\"help-block\" ng-show=\"signupForm.password.$error.required\">Required</span>\n                <span class=\"help-block\" ng-show=\"signupForm.password.$error.minlength\">Password is too short</span>\n                <span class=\"help-block\" ng-show=\"signupForm.password.$error.maxlength\">Password is too long</span>\n            </div>\n        </div>\n        <div \n            class=\"form-group clearfix\" \n            ng-class=\"{\n                'has-error': signupForm.passwordConfirm.$invalid && signupForm.passwordConfirm.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"signupFormPasswordConfirm\">Password Confirm:</label>\n            <div class=\"col-sm-10\">\n                <input \n                    id=\"signupFormPasswordConfirm\" \n                    class=\"form-control\" \n                    type=\"password\" \n                    name=\"passwordConfirm\" \n                    ng-model=\"user.passwordConfirm\" \n                    required \n                    ng-minlength=\"6\" \n                    ng-maxlength=\"100\" \n                    password-match=\"user.password\" \n                />\n                <span class=\"help-block\" ng-show=\"signupForm.passwordConfirm.$error.required\">Required</span>\n                <span class=\"help-block\" ng-show=\"signupForm.passwordConfirm.$error.minlength\">Password Confirm is too short</span>\n                <span class=\"help-block\" ng-show=\"signupForm.passwordConfirm.$error.maxlength\">Password Confirm is too long</span>\n                <span class=\"help-block\" ng-show=\"signupForm.passwordConfirm.$error.passwordMatch\">Password Confirm doesn't match Password.</span>\n            </div>\n        </div>\n        <div class=\"form-group clearfix\">\n            <label class=\"control-label col-sm-2\" for=\"signupFormCode\">Code:</label>\n            <div class=\"col-sm-4\">\n                <input id=\"signupFormCode\" class=\"form-control\" type=\"text\" name=\"code\" ng-model=\"user.code\" />\n                <span class=\"help-block\">Optional Promo Code</span>\n            </div>\n        </div>\n    </form>\n    <p>By clicking \"Sign Up\", you agree to our <a href=\"terms\" ng-click=\"cancel()\">terms of service</a> and <a href=\"privacy\" ng-click=\"cancel()\">privacy policy</a>.</p>\n    <div class=\"form-errors\" ng-show=\"formErrors\">\n        <em class=\"text-warning\">Oops! Please fix up these errors:</em>\n        <ul class=\"form-errors-list\">\n            <li class=\"form-errors-list-item alert alert-warning\" ng-repeat=\"error in formErrors\">{{error}}</li>\n        </ul>\n    </div>\n    <div class=\"form-success alert alert-success\" ng-show=\"formSuccess\">\n        {{formSuccess}}\n    </div>\n</div>\n<div class=\"modal-footer\">\n    <button class=\"btn btn-primary\" ng-click=\"signup(user)\" ng-disabled=\"signupForm.$invalid\">Sign Up</button>\n    <button class=\"btn btn-warning\" ng-click=\"cancel()\">Close</button>\n</div>", 
            controller: require('./SignUpModalCtrl'),
            windowClass: 'signup-modal form-modal'
        }).result.then(function () {
            $state.go('controlPanel');
        });

    };

    $scope.modal.logIn = function () {

        $modal.open({
            template: "<div class=\"modal-header\">\n    <h3>Log In</h3>\n</div>\n<div class=\"modal-body\">\n    <form class=\"form-horizontal\" name=\"loginForm\">\n        <div \n            class=\"form-group clearfix\" \n            ng-class=\"{\n                'has-error': loginForm.email.$invalid && loginForm.email.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"loginFormEmail\">Email:</label>\n            <div class=\"col-sm-10\">\n                <input id=\"loginFormEmail\" class=\"form-control\" type=\"email\" name=\"email\" ng-model=\"user.email\" required ng-maxlength=\"100\" />\n                <span class=\"help-block\" ng-show=\"loginForm.email.$error.required\">Required</span>\n                <span class=\"help-block\" ng-show=\"loginForm.email.$error.maxlength\">Email is too long</span>\n                <span class=\"help-block\" ng-show=\"loginForm.email.$error.email\">Email is invalid</span>\n            </div>\n        </div>\n        <div \n            class=\"form-group clearfix\"\n            ng-class=\"{\n                'has-error': loginForm.password.$invalid && loginForm.password.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"loginFormPassword\">Password:</label>\n            <div class=\"col-sm-10\">\n                <input \n                    id=\"loginFormPassword\" \n                    class=\"form-control\" \n                    type=\"password\" \n                    name=\"password\" \n                    ng-model=\"user.password\" \n                    required \n                    ng-minlength=\"6\" \n                    ng-maxlength=\"100\" \n                />\n                <span class=\"help-block\" ng-show=\"loginForm.password.$error.required\">Required</span>\n                <span class=\"help-block\" ng-show=\"loginForm.password.$error.minlength\">Password is too short</span>\n                <span class=\"help-block\" ng-show=\"loginForm.password.$error.maxlength\">Password is too long</span>\n            </div>\n        </div>\n        <div class=\"form-group clearfix\">\n            <div class=\"col-sm-offset-2 col-sm-10\">\n                <div class=\"checkbox\">\n                    <label>\n                        <input type=\"checkbox\" name=\"autologin\" ng-model=\"user.autologin\"> Remember Me\n                    </label>\n                </div>\n            </div>\n        </div>\n    </form>\n    <div class=\"form-errors\" ng-show=\"formErrors\">\n        <em class=\"text-warning\">Oops! Please fix up these errors:</em>\n        <ul class=\"form-errors-list\">\n            <li class=\"form-errors-list-item alert alert-warning\" ng-repeat=\"error in formErrors\">{{error}}</li>\n        </ul>\n    </div>\n    <div class=\"form-success alert alert-success\" ng-show=\"formSuccess\">\n        {{formSuccess}}\n    </div>\n</div>\n<div class=\"modal-footer\">\n    <button class=\"btn btn-primary\" ng-click=\"login(user)\" ng-disabled=\"loginForm.$invalid\">Log In</button>\n    <button class=\"btn btn-warning\" ng-click=\"cancel()\">Close</button>\n</div>",
            controller: require('./LogInModalCtrl'),
            windowClass: 'login-modal form-modal'
        }).result.then(function () {
            $state.go('controlPanel');
        });

    };

    $scope.auth.logOut = function () {

        UserSystemServ.logoutSession().then(function () {
            $state.go('home');
        });

    };

}];
},{"./LogInModalCtrl":13,"./SignUpModalCtrl":14,"fs":1}],12:[function(require,module,exports){
'use strict';

/**
 * Header Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

}];
},{}],13:[function(require,module,exports){
'use strict';

/**
 * Login Modal Controller
 */
module.exports = ['$scope', '$modalInstance', '$timeout', 'UserSystemServ', function ($scope, $modalInstance, $timeout, UserSystemServ) {

    $scope.user = {};

    $scope.login = function (user) {

        $scope.formErrors = false;
        $scope.formSuccess = false;

        UserSystemServ.loginSession(user).then(function () {

            $scope.formSuccess = 'Successfully logged in.';
            $timeout(function () {
                $modalInstance.close();
            }, 1500);

        }, function (response) {

            if (response.status === 400 || response.status === 401) {
                $scope.formErrors = response.data.content;
            } else {
                $scope.formErrors = ['System error, try again or contact us.'];
            }

        });

    };

    $scope.cancel = function () {

        $modalInstance.dismiss();

    };

}];
},{}],14:[function(require,module,exports){
'use strict';

/**
 * Sign Up Modal Controller
 */
module.exports = ['$scope', '$modalInstance', '$timeout', 'UserSystemServ', function ($scope, $modalInstance, $timeout, UserSystemServ) {

    $scope.user = {};

    $scope.signup = function (user) {

        $scope.formErrors = false;
        $scope.formSuccess = false;

        UserSystemServ.registerAccount(user).then(function (response) {

            $scope.formSuccess = 'Successfully registered. Automatically logging in.';
            $timeout(function () {
                $modalInstance.close();
            }, 1500);

        }, function (response) {

            if (response.status === 400) {
                $scope.formErrors = response.data.content;
            } else {
                $scope.formErrors = ['System error, try again or contact us.'];
            }

        });

    };

    $scope.cancel = function () {

        $modalInstance.dismiss();

    };

}];
},{}],15:[function(require,module,exports){
'use strict';

/**
 * Card Create Modal
 */
module.exports = ['$scope', '$modalInstance', function ($scope, $modalInstance) {

    

}];
},{}],16:[function(require,module,exports){
'use strict';

/**
 * Card Update Modal
 */
module.exports = ['$scope', '$modalInstance', function ($scope, $modalInstance) {



}];
},{}],17:[function(require,module,exports){
'use strict';

/**
 * Control Account Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

}];
},{}],18:[function(require,module,exports){
'use strict';

var fs = require('fs');

/**
 * Control Billing Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', '$modal', function ($scope, $modal) {

    //once cards get created or updated, we need to either add to the card model client side, or requery the server...
    //client side updating is quicker and more "semantic"
    //server side requerying is easier to do

    $scope.modal.cardCreate = function () {

        $modal.open({
            template: "<div class=\"modal-header\">\n    <h2>Create a new Credit Card</h2>\n    <em>Payments are processed by Pin payments, and we don't keep any credit card information on our servers.</em>\n</div>\n<div class=\"modal-body\">\n    <form class=\"form-horizontal\" name=\"signupForm\">\n        <div \n            class=\"form-group\" \n            ng-class=\"{\n                'has-error': billingForm.cardNumber.$invalid && billingForm.cardNumber.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"billingFormCardNumber\">Card Number:</label>\n            <div class=\"col-sm-10\">\n                <input id=\"billingFormCardNumber\" class=\"form-control\" type=\"text\" name=\"cardNumber\" ng-model=\"card.cardNumber\" required ng-minlength=\"2\" ng-maxlength=\"100\" />\n                <span class=\"help-block\" ng-show=\"billingForm.cardNumber.$error.required\">Required</span>\n            </div>\n        </div>\n    </form>\n    <div class=\"form-errors\" ng-show=\"formErrors\">\n        <em class=\"text-warning\">Oops! Please fix up these errors:</em>\n        <ul class=\"form-errors-list\">\n            <li class=\"form-errors-list-item alert alert-warning\" ng-repeat=\"error in formErrors\">{{error}}</li>\n        </ul>\n    </div>\n    <div class=\"form-success alert alert-success\" ng-show=\"formSuccess\">\n        {{formSuccess}}\n    </div>\n</div>\n<div class=\"modal-footer\">\n    <button class=\"btn btn-primary\" ng-click=\"addCard(card)\" ng-disabled=\"billingForm.$invalid\">Add Card</button>\n    <button class=\"btn btn-warning\" ng-click=\"cancel()\">Close</button>\n</div>",
            controller: require('./CardCreateModalCtrl'),
            windowClass: 'card-create-modal form-modal'
        }).result.then(function () {

            //requery the server and update the cards model

        });

    };

    //card update modal is only available from the Billing controller
    $scope.modal.cardUpdate = function () {

        $modal.open({
            template: "<div class=\"modal-header\">\n    <h3>Sign Up</h3>\n</div>\n<div class=\"modal-body\">\n    <form class=\"form-horizontal\" name=\"signupForm\">\n        <div \n            class=\"form-group\" \n            ng-class=\"{\n                'has-error': signupForm.username.$invalid && signupForm.username.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"signupFormUsername\">Username:</label>\n            <div class=\"col-sm-10\">\n                <input id=\"signupFormUsername\" class=\"form-control\" type=\"text\" name=\"username\" ng-model=\"user.username\" required ng-minlength=\"2\" ng-maxlength=\"100\" />\n                <span class=\"help-block\" ng-show=\"signupForm.username.$error.required\">Required</span>\n                <span class=\"help-block\" ng-show=\"signupForm.username.$error.minlength\">Username is too short</span>\n                <span class=\"help-block\" ng-show=\"signupForm.username.$error.maxlength\">Username is too long</span>\n            </div>\n        </div>\n        <div \n            class=\"form-group\" \n            ng-class=\"{\n                'has-error': signupForm.email.$invalid && signupForm.email.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"signupFormEmail\">Email:</label>\n            <div class=\"col-sm-10\">\n                <input id=\"signupFormEmail\" class=\"form-control\" type=\"email\" name=\"email\" ng-model=\"user.email\" required ng-maxlength=\"100\" />\n                <span class=\"help-block\" ng-show=\"signupForm.email.$error.required\">Required</span>\n                <span class=\"help-block\" ng-show=\"signupForm.email.$error.maxlength\">Email is too long</span>\n                <span class=\"help-block\" ng-show=\"signupForm.email.$error.email\">Email is invalid</span>\n            </div>\n        </div>\n        <div \n            class=\"form-group\"\n            ng-class=\"{\n                'has-error': signupForm.password.$invalid && signupForm.password.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"signupFormPassword\">Password:</label>\n            <div class=\"col-sm-10\">\n                <input \n                    id=\"signupFormPassword\" \n                    class=\"form-control\" \n                    type=\"password\" \n                    name=\"password\" \n                    ng-model=\"user.password\" \n                    required \n                    ng-minlength=\"6\" \n                    ng-maxlength=\"100\" \n                />\n                <span class=\"help-block\" ng-show=\"signupForm.password.$error.required\">Required</span>\n                <span class=\"help-block\" ng-show=\"signupForm.password.$error.minlength\">Password is too short</span>\n                <span class=\"help-block\" ng-show=\"signupForm.password.$error.maxlength\">Password is too long</span>\n            </div>\n        </div>\n        <div \n            class=\"form-group\" \n            ng-class=\"{\n                'has-error': signupForm.passwordConfirm.$invalid && signupForm.passwordConfirm.$dirty\n            }\"\n        >\n            <label class=\"control-label col-sm-2\" for=\"signupFormPasswordConfirm\">Password Confirm:</label>\n            <div class=\"col-sm-10\">\n                <input \n                    id=\"signupFormPasswordConfirm\" \n                    class=\"form-control\" \n                    type=\"password\" \n                    name=\"passwordConfirm\" \n                    ng-model=\"user.passwordConfirm\" \n                    required \n                    ng-minlength=\"6\" \n                    ng-maxlength=\"100\" \n                    password-match=\"user.password\" \n                />\n                <span class=\"help-block\" ng-show=\"signupForm.passwordConfirm.$error.required\">Required</span>\n                <span class=\"help-block\" ng-show=\"signupForm.passwordConfirm.$error.minlength\">Password Confirm is too short</span>\n                <span class=\"help-block\" ng-show=\"signupForm.passwordConfirm.$error.maxlength\">Password Confirm is too long</span>\n                <span class=\"help-block\" ng-show=\"signupForm.passwordConfirm.$error.passwordMatch\">Password Confirm doesn't match Password.</span>\n            </div>\n        </div>\n        <div class=\"form-group\">\n            <label class=\"control-label col-sm-2\" for=\"signupFormCode\">Code:</label>\n            <div class=\"col-sm-4\">\n                <input id=\"signupFormCode\" class=\"form-control\" type=\"text\" name=\"code\" ng-model=\"user.code\" />\n                <span class=\"help-block\">Optional Promo Code</span>\n            </div>\n        </div>\n    </form>\n    <p>By clicking \"Sign Up\", you agree to our <a href=\"terms\" ng-click=\"cancel()\">terms of service</a> and <a href=\"privacy\" ng-click=\"cancel()\">privacy policy</a>.</p>\n    <div class=\"form-errors\" ng-show=\"formErrors\">\n        <em class=\"text-warning\">Oops! Please fix up these errors:</em>\n        <ul class=\"form-errors-list\">\n            <li class=\"form-errors-list-item alert alert-warning\" ng-repeat=\"error in formErrors\">{{error}}</li>\n        </ul>\n    </div>\n    <div class=\"form-success alert alert-success\" ng-show=\"formSuccess\">\n        {{formSuccess}}\n    </div>\n</div>\n<div class=\"modal-footer\">\n    <button class=\"btn btn-primary\" ng-click=\"signup(user)\" ng-disabled=\"signupForm.$invalid\">Sign Up</button>\n    <button class=\"btn btn-warning\" ng-click=\"cancel()\">Close</button>\n</div>", 
            controller: require('./CardUpdateModalCtrl'),
            windowClass: 'card-update-modal form-modal'
        }).result.then(function () {

            //request the server and update the cards model

        });

    };

}];
},{"./CardCreateModalCtrl":15,"./CardUpdateModalCtrl":16,"fs":1}],19:[function(require,module,exports){
'use strict';

/**
 * Control Cache Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

}];
},{}],20:[function(require,module,exports){
'use strict';

var settings = require('../../Settings');

/**
 * Control Crawling Controller
 *
 * @param {Object} $scope
 */
module.exports = [
    '$scope', 
    '$q',
    'UserSystemServ', 
    'CalculateServ', 
    'Restangular', 
    'MomentServ', 
    function ($scope, $q, UserSystemServ, CalculateServ, Restangular, MomentServ) {

        var checkUserAccount = function () {

            return $scope.userAccount || false;
        
        };

        /**
         * Handle API Limit Modifier Form
         */
        var handleApiLimitModifierForm = function (userAccount) {

            $scope.apiLimitModifier = {};

            //default api limit quantity is the current api limit
            $scope.apiLimitModifier.quantity = userAccount.apiLimit;

            //check if the user has billing details
            Restangular.all('billing').customGET('', {
                user: userAccount.id, 
                active: true, 
                valid: true
            }).then(function () {
                $scope.hasBillingDetails = true;
            }, function () {
                $scope.hasBillingDetails = false;
            });

            //calculate the price
            $scope.$watch(function (scope) {

                return scope.apiLimitModifier.quantity;

            }, function (quantity) {

                if (!quantity) {
                    quantity = 0;
                }

                //coerce to integer
                quantity = parseInt(quantity);

                //calculate the price while subtracting from free usage limit
                var price = settings.meta.price * (quantity - userAccount.apiFreeLimit);

                //if the price is negative, reset to zero
                if (price < 0) {
                    price = 0;
                }

                //round to 2 decimal points, nearest cent
                price = CalculateServ.round(price, 2);

                $scope.price = price;

            });

            //change the limit
            $scope.changeLimit = function (apiLimitModifier) {

                $scope.formErrors = false;
                $scope.formSuccess = false;

                UserSystemServ.patchAccount({
                    apiLimit: apiLimitModifier.quantity
                }).then(function (response) {

                    $scope.formSuccess = 'Successfully updated API Usage Cap!';

                }, function (response) {

                    if (typeof response.data.content == 'string') {
                        $scope.formErrors = [response.data.content];
                    } else {
                        $scope.formErrors = response.data.content;
                    }

                });

            };

        };

        /**
         * Get Request & Usage History Stats
         */
        var getGraphStats = function (userAccount) {

            var logGraphDate = MomentServ().subtract(MomentServ.duration.fromIsoduration(userAccount.chargeInterval));

            var getGraph = function () {

                var cutOffDate = logGraphDate.format('YYYY-MM-DD HH:mm:ss');
                var dates = [];
                var requests = [];
                var usage = [];

                var cachedLog = Restangular.all('log').customGET('', {
                    user: userAccount.id,
                    date: cutOffDate,
                    type: 'cached',
                    transform: 'by_date'
                });

                var uncachedLog = Restangular.all('log').customGET('', {
                    user: userAccount.id,
                    date: cutOffDate,
                    type: 'uncached',
                    transform: 'by_date'
                });

                $q.all([
                    cachedLog,
                    uncachedLog
                ]).then(function (responses) {

                    responses[0].content.forEach(function (value, index) {
                        var date = MomentServ(value.date, 'YYYY-MM-DD HH:mm:ss');
                        dates.push(date);
                        requests.push([date, value.quantity]);
                    });

                    responses[1].content.forEach(function (value, index) {
                        var date = MomentServ(value.date, 'YYYY-MM-DD HH:mm:ss');
                        dates.push(date);
                        usage.push([date, value.quantity]);
                    });

                    var oldestDate = dates.reduce(function (prevDate, curDate) {
                        return curDate.unix() < prevDate.unix() ? curDate : prevDate;
                    });

                    var latestDate = dates.reduce(function (prevDate, curDate) {
                        return curDate.unix() > prevDate.unix() ? curDate : prevDate;
                    });

                    $scope.usageHistoryData = [
                        {
                            key: "Usage Cap",
                            values: [
                                [oldestDate, userAccount.apiLimit],
                                [latestDate, userAccount.apiLimit]
                            ]
                        },
                        {
                            key: "Usages",
                            values: usage
                        },
                        {
                            key: "Requests",
                            values: requests
                        }
                    ];

                }, function (response) {

                    $scope.usageHistoryData = [];

                });

            };

            //sets up the date formatting on the x axis
            $scope.xAxisDateFormatFunction = function(){
                //xValue is a Moment.js wrapped date objects
                //it's evaluated in milliseconds, but d3 needs it in a JS date object
                return function(xValue){
                    return d3.time.format('%Y-%m-%d')(new Date(xValue));
                }
            };

            getGraph();

            $scope.forwardGraph = function () {

                logGraphDate = logGraphDate.add(MomentServ.duration.fromIsoduration(userAccount.chargeInterval));
                getGraph();

            };

            $scope.backwardGraph = function () {

                logGraphDate = logGraphDate.subtract(MomentServ.duration.fromIsoduration(userAccount.chargeInterval));
                getGraph();

            };

        };

        var getHistoryStats = function (userAccount) {

            //get the full log up to a date...?
            //log_model will need to support date offsets instead of offset/limit
            //domain distinction actually needs to grab it off the server?
            //probably better to execute it from the server, there could be a lot of data

            //finally for the log table we'll need to extract the entire data

        };

        var initialise = function (userAccount) {

            handleApiLimitModifierForm(userAccount);
            getGraphStats(userAccount);
            getHistoryStats();

        };

        //run every time the controller is reinstantiated
        if (checkUserAccount()) {
            
            initialise(checkUserAccount());
        
        } else {

            $scope.$watch(checkUserAccount, function (userAccount) {
                initialise(userAccount);
            });

        }

}];
},{"../../Settings":9}],21:[function(require,module,exports){
'use strict';

/**
 * Control Panel Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', 'BusyLoopServ', 'UserSystemServ', 'MomentServ', 'CalculateServ', function ($scope, BusyLoopServ, UserSystemServ, MomentServ, CalculateServ) {

    var cancelBusyLoop = BusyLoopServ(function () {
        if (UserSystemServ.getUserState()){
            var userData = UserSystemServ.getUserData();
            UserSystemServ.getAccount(userData.id);
        }
    }, 45000);

    $scope.$on('$destroy', function () {
        cancelBusyLoop();
    });

    $scope.$watch(UserSystemServ.getUserData, function (value) {

        if (Object.keys(value).length > 0) {

            var userAccount = angular.copy(value);

            // console.log(userAccount);

            $scope.userAccount = userAccount;
            $scope.userAccount.apiUsagePercentage = CalculateServ.round((value.apiUsage / value.apiLimit) * 100, '2');

            //chargeCycle will wrap the dates as moment objects
            $scope.chargeCycle = {
                beginning: MomentServ(value.chargeDate, 'YYYY-MM-DD HH:mm:ss').subtract(MomentServ.duration.fromIsoduration(value.chargeInterval)),
                ending: MomentServ(value.chargeDate, 'YYYY-MM-DD HH:mm:ss')
            };

        }

    }, true);

}];
},{}],22:[function(require,module,exports){
'use strict';

/**
 * Control Payments Controller
 *
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

}];
},{}],23:[function(require,module,exports){
'use strict';

/**
 * Documentation Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

}];
},{}],24:[function(require,module,exports){
'use strict';

/**
 * Code Group Controller
 * Controls the code group allowing the ability to switch the code examples.
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

    $scope.activeCode = 'php';

    $scope.changeCode = function(value){
        $scope.activeCode = value;
    }

}];
},{}],25:[function(require,module,exports){
'use strict';

/**
 * Demo Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', 'Restangular', function ($scope, Restangular) {

    /**
     * State to indicate requesting status.
     * 'never' => never requested
     * 'started' => started a request
     * 'finished' => finished request
     * 
     * @type {Number}
     */
    $scope.requestingDemoService = 'never';

    $scope.submit = function (demo) {

        $scope.formErrors = false;
        $scope.formSuccess = false;
        $scope.requestingDemoService = 'started';

        Restangular.all('demo').customGET('', {url: demo.url}).then(function (response) {

            $scope.formSuccess = true;
            $scope.demoServiceResponse = response.content;

        }, function (response) {

            if (response.status === 400) {
                $scope.formErrors = response.data.content;
            } else if (response.status === 500) {
                $scope.formErrors = [];
                $scope.formErrors = $scope.formErrors.concat(
                    response.data.content.robotErrors, 
                    response.data.content.curlErrors
                ).filter(function (value) {
                    return value != undefined;
                });
            }

        })['finally'](function () {

            $scope.requestingDemoService = 'finished';

        });

    };

}];
},{}],26:[function(require,module,exports){
'use strict';

/**
 * Home Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

}];
},{}],27:[function(require,module,exports){
'use strict';

var settings = require('../../Settings');

/**
 * Cost Calculator Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', 'CalculateServ', function ($scope, CalculateServ) {

    var pricingPerUsage = settings.meta.price;
    var freeUsageCap = settings.meta.freeUsageCap;

    //setup the cost object
    $scope.cost = {};

    $scope.$watch(function (scope) {

        return scope.cost.quantity;

    }, function (quantity) {

        if (!quantity) {
            quantity = 0;
        }

        //coerce to integer
        quantity = parseInt(quantity);

        //calculate the price while subtracting from freeUsageCap
        var price = pricingPerUsage * (quantity - freeUsageCap);

        //if the price is negative, reset to zero
        if (price < 0) {
            price = 0;
        }

        //round to 2 decimal points, nearest cent
        price = CalculateServ.round(price, 2);

        $scope.price = price;

    });

}];
},{"../../Settings":9}],28:[function(require,module,exports){
'use strict';

var settings = require('../../Settings');

/**
 * Pricing Controller
 * 
 * @param {Object} $scope
 */
module.exports = ['$scope', function ($scope) {

    $scope.pricePerUsage = settings.meta.price;
    $scope.freeUsageCap = settings.meta.freeUsageCap;

}];
},{"../../Settings":9}],29:[function(require,module,exports){
'use strict';

/**
 * Privacy Controller
 */
module.exports = ['$scope', function ($scope) {

}];
},{}],30:[function(require,module,exports){
'use strict';

/**
 * Terms Controller
 */
module.exports = ['$scope', function ($scope) {

}];
},{}],31:[function(require,module,exports){
'use strict';

/**
 * Directives
 */
angular.module('App.Directives', []);

module.exports = angular.module('App.Directives')
    .directive('equaliseHeights', require('./equaliseHeights'))
    .directive('anchor', require('./anchor'))
    .directive('scroll', require('./scroll'))
    .directive('passwordMatch', require('./passwordMatch'))
    .directive('affix', require('./affix'))
    .directive('minValid', require('./minValid'))
    .directive('maxValid', require('./maxValid'));
},{"./affix":32,"./anchor":33,"./equaliseHeights":34,"./maxValid":35,"./minValid":36,"./passwordMatch":37,"./scroll":38}],32:[function(require,module,exports){
'use strict';

/**
 * Affix Directive
 *
 * Requires Lodash, jQuery
 * Does not work on IE8 or lower.
 */
module.exports = ['$window', '$document', function ($window, $document) {

    return {
        link: function (scope, element, attributes) {

                var win = angular.element($window),
                    doc  = angular.element($document),
                    affixed;

                var affixPosition = function () {

                    //default parameters of 0, it will always be fixed if 0
                    var offsetTop = scope.$eval(attributes.affixTop) || 0,
                        offsetBottom = scope.$eval(attributes.affixBottom) || 0,
                        affix;

                    //if the window scroll position is less or equal (above) the offsetTop, then set "affix-top"
                    //if the element offsetTop + element height is greater or equal (below) the document height - offsetBottom, then set "affix-bottom"
                    if (win.prop('pageYOffset') <= offsetTop) {
                        affix = 'affix-top';
                    } else if ((win.prop('pageYOffset') + element.outerHeight()) < (doc.height() - offsetBottom)) {
                        affix = 'affix';
                    } else if ((win.prop('pageYOffset') + element.outerHeight()) >= (doc.height() - offsetBottom)) {
                        affix = 'affix-bottom';
                    }

                    //if the same value, don't bother changing classes, because nothing changed
                    if(affixed === affix) return;
                    affixed = affix;

                    //reset the css classes and add either affix or affix-top or affix-bottom
                    element.removeClass('affix affix-top affix-bottom').addClass(affix);

                    //if affix was bottom, then pin it to where it currently is
                    if (affix === 'affix-bottom') {
                        element.offset({ top: doc.height() - offsetBottom - element.outerHeight() });
                    } else {
                        element.css('top', '');
                    }

                };

                var ensureWidth = function () {
                    element.css('width', element.parent().width());
                };

                var throttledAffix = _.throttle(affixPosition, 50);

                var throttledWidth = _.throttle(ensureWidth, 100);

                var resizeHandler = function () {
                    throttledAffix();
                    throttledWidth();
                };

                //when scrolling, we only have to figure out whether its affix, affix-top or affix-bottom
                win.bind('scroll', throttledAffix);

                //when resizing, we need to ensure the width and check the affix in case elements above pushed down this affixed element
                win.bind('resize', resizeHandler);

                //run both at initialisation
                affixPosition();
                ensureWidth();

                //unbind external event handlers on destruction
                scope.$on('$destroy', function () {
                    win.unbind('scroll', throttledAffix);
                    win.unbind('resize', resizeHandler);
                });

        }
    };

}];
},{}],33:[function(require,module,exports){
'use strict';

var imagesloaded = require("./..\\..\\..\\components\\imagesloaded\\imagesloaded.js");

/**
 * Asynchronous Anchor Scroll
 *
 * @param {string} anchor      ID to scroll to
 * @param {string} anchorDelay Delay in microseconds when scrolling
 * @param {string} anchorEvent Event to listen to before scrolling
 */
module.exports = ['$location', '$anchorScroll', '$timeout', function ($location, $anchorScroll, $timeout) {

        return {
            link: function(scope, element, attributes){

                var id = attributes.anchor || attributes.id || attributes.name;
                var delay = attributes.anchorDelay;
                var eventName = attributes.anchorEvent;
                var firstTimeScrolling = true;

                element.attr('id', id);

                var scrollToHash = function(hash){

                    if(id && hash && id === hash){

                        if(delay && firstTimeScrolling){

                            $timeout(function () {

                                imagesloaded(element, function () {
                                    $anchorScroll();
                                });

                            }, delay);

                        }else{

                            imagesloaded(element, function () {
                                $anchorScroll();
                            });

                        }
                        
                        //only run the delay the first time this scrolling function executes
                        //if the hash didn't match, then this function didn't execute!
                        firstTimeScrolling = false;

                    }

                };
                
                //listen for a custom event, useful if you're waiting on something else to be fully loaded as well
                if(eventName){

                    scope.$on(eventName, function(){

                        scrollToHash($location.hash());

                    });

                }

                //hash may be asynchronously changed, the directive may load before the hash is added
                scope.$watch(function(){

                    return $location.hash();
                
                }, function(hash){

                    scrollToHash(hash);

                });

            }
        };

}];
},{"./..\\..\\..\\components\\imagesloaded\\imagesloaded.js":4}],34:[function(require,module,exports){
'use strict';

var imagesloaded = require("./..\\..\\..\\components\\imagesloaded\\imagesloaded.js");

/**
 * Equalise Heights given a selector
 *
 * @param {string} equaliseHeights jQuery selector pointing to multiple DOM elements requiring an equal height
 */
module.exports = [function () {
    
    return {
        link: function(scope, element, attributes){
        
            //we're not using scope.watch here because, watch would require the values to change, and it can't watch browser events like window.resize, also we're not watching value changes, but events! therefore we're doing jquery event binding
            //another method here: http://jsfiddle.net/bY5qe/
            var items = angular.element(attributes.equaliseHeights);
            
            var equaliseHeight = function(){
                var maxHeight = 0;
                items
                    .height('auto') //reset the heights to auto to see if the content pushes down to the same height
                    .each(function(){
                        //find out which has the max height (wrap it in angular.element, or else each this is the raw DOM)
                        maxHeight = angular.element(this).height() > maxHeight ? angular.element(this).height() : maxHeight; 
                    })
                    .height(maxHeight); //then make them all the same maximum height!
            };

            //run it once after all images are loaded
            imagesloaded(items, function () {
                equaliseHeight();
            });
            
            //on the resize event from jquery, run a function, this function is a pointer!
            angular.element(window).resize(equaliseHeight);
        
        }
    };

}];
},{"./..\\..\\..\\components\\imagesloaded\\imagesloaded.js":4}],35:[function(require,module,exports){
'use strict';

module.exports = [function () {
    return {
        restrict: 'A',
        require: 'ngModel',
        link: function(scope, elem, attr, ctrl) {

            var isEmpty = function (value) {
                return angular.isUndefined(value) || value === '' || value === null || value !== value;
            };

            scope.$watch(attr.maxValid, function(){
                ctrl.$setViewValue(ctrl.$viewValue);
            });

            var maxValidator = function(value) {

                var max = scope.$eval(attr.maxValid) || Infinity;
                if (!isEmpty(value) && value > max) {

                    ctrl.$setValidity('maxValid', false);
                    return undefined;

                } else {

                    ctrl.$setValidity('maxValid', true);
                    return value;

                }

            };

            ctrl.$parsers.push(maxValidator);
            ctrl.$formatters.push(maxValidator);

        }
    };
}];
},{}],36:[function(require,module,exports){
'use strict';

module.exports = [function () {
    return {
        restrict: 'A',
        require: 'ngModel',
        link: function(scope, elem, attr, ctrl) {

            var isEmpty = function (value) {
                return angular.isUndefined(value) || value === '' || value === null || value !== value;
            };

            scope.$watch(attr.minValid, function(){
                ctrl.$setViewValue(ctrl.$viewValue);
            });

            var minValidator = function(value) {

                var min = scope.$eval(attr.minValid) || 0;
                if (!isEmpty(value) && value < min) {

                    ctrl.$setValidity('minValid', false);
                    return undefined;

                } else {

                    ctrl.$setValidity('minValid', true);
                    return value;

                }

            };

            ctrl.$parsers.push(minValidator);
            ctrl.$formatters.push(minValidator);

        }
    };
}];
},{}],37:[function(require,module,exports){
'use strict';

/**
 * Password Match
 *
 * Example:
 * <form name="form">
 *     <input name="password" ng-model="user.password" />
 *     <input name="passwordConfirm" ng-model="user.passwordConfirm" password-match="user.password" />
 * </form>
 * <p ng-show="form.passwordConfirm.$error.passwordMatch">Passwords are not matched!</p>
 *
 * @param {string} passwordMatch Model property identifier to be "matched against"
 */
module.exports = [function () {
    
    return {
        restrict: 'A',
        require: 'ngModel',
        scope: false,
        link: function (scope, element, attributes, controller) {

            //watch the "matched against" model value, and set its passwordMatch validity conditional upon being identical to current model value
            //this executes when the "matched against" model value changes
            scope.$watch(attributes.passwordMatch, function (value) {
                controller.$setValidity('passwordMatch', value === controller.$viewValue);
            });

            //push a parsing pipe to the current model value, and set its passwordMatch validity conditional upon being identical to matched against model value
            //this executes when the current model value changes
            controller.$parsers.push(function (value) {
                var isValid = (value === scope.$eval(attributes.passwordMatch));
                controller.$setValidity('passwordMatch', isValid);
                return isValid ? value : undefined;
            });

        }
    };

}];
},{}],38:[function(require,module,exports){
'use strict';

/**
 * Scroll Directive
 */
module.exports = ['$anchorScroll', '$location', function ($anchorScroll, $location) {

    return {
        link:  function (scope, element, attributes) {

            var scroll = function () {
                $location.hash(attributes.scroll);
                $anchorScroll();
                scope.$apply();
            };

            element.on('click', scroll);

            //we don't need to manually destroy as angular should handle direct element binding

        }
    };

}];
},{}],39:[function(require,module,exports){
'use strict';

/**
 * Elements
 *
 * It should be possible to require(Module).name instead of directly bringing in directives. This is because some reusable elements will become modules due to configuration or other things.
 */
angular.module('App.Elements', []);

module.exports = angular.module('App.Elements')
    .directive('syntax', require('./syntaxHighlight'))
    .directive('chatTab', require('./chatTab'));
},{"./chatTab":40,"./syntaxHighlight":76}],40:[function(require,module,exports){
'use strict';

var fs = require('fs');
var insertCss = require('insert-css');
var css = ".chatTab {\n    position: fixed;\n    right: 1%;\n    bottom: 0;\n    width: 180px;\n}\n\n.chatTab button {\n    color: #FFF;\n    margin: 0 auto;\n    display: block;\n    padding: 6px;\n    background: #428bca;\n    border-top-left-radius: 6px;\n    border-top-right-radius: 6px;\n    border-left: 1px solid #357ebd;\n    border-right: 1px solid #357ebd;\n    border-top: 1px solid #357ebd;\n    border-bottom: none;\n    width: 100%;\n}\n\n.chatTab button:hover {\n    background-color: #2D6CA2;\n    border-color: #2B669A;\n}\n\n.chatTab-content {\n    background: #FFF;\n    width: auto;\n    height: auto;\n    padding: 10px;\n    border-left: 1px solid #dcdcdc;\n    border-right: 1px solid #dcdcdc;\n}\n\n.chatTab-content.crushed {\n    width: 0;\n    height: 0;\n    display: none;\n}\n\n.chatTab-link {\n    display: block;\n    margin-bottom: 5px;\n    text-align: center;\n}";
var chatTemplate = "<div class=\"chatTab\">\n    <button ng-click=\"openCloseChatTab()\">Chat with the Developers</button>\n    <div class=\"chatTab-content crushed\">\n        <a class=\"chatTab-link\" ng-href=\"{{chatUrl}}\" target=\"_blank\">Access WebChat</a>\n        <p>If we're not online, just leave a message or ping us as we will receive it on our mobile phones.</p>\n    </div>\n</div>";

insertCss(css);

/**
 * Chat Tab
 *
 * Relies on Angular jQuery
 */
module.exports = [function () {

    return {
        scope: {
            'chatUrl': '@' //this gets passed in directly
        }, 
        restrict: 'AE',
        template: chatTemplate, 
        replace: true, 
        link: function (scope, element, attributes) {

            scope.openCloseChatTab = function () {

                element.children('.chatTab-content').toggleClass('crushed');

            };

        }
    };

}];
},{"fs":1,"insert-css":90}],41:[function(require,module,exports){
var Highlight = function() {

  /* Utility functions */

  function escape(value) {
    return value.replace(/&/gm, '&amp;').replace(/</gm, '&lt;').replace(/>/gm, '&gt;');
  }

  function tag(node) {
    return node.nodeName.toLowerCase();
  }

  function testRe(re, lexeme) {
    var match = re && re.exec(lexeme);
    return match && match.index == 0;
  }

  function blockLanguage(block) {
    var classes = (block.className + ' ' + (block.parentNode ? block.parentNode.className : '')).split(/\s+/);
    classes = classes.map(function(c) {return c.replace(/^lang(uage)?-/, '');});
    return classes.filter(function(c) {return getLanguage(c) || c == 'no-highlight';})[0];
  }

  function inherit(parent, obj) {
    var result = {};
    for (var key in parent)
      result[key] = parent[key];
    if (obj)
      for (var key in obj)
        result[key] = obj[key];
    return result;
  };

  /* Stream merging */

  function nodeStream(node) {
    var result = [];
    (function _nodeStream(node, offset) {
      for (var child = node.firstChild; child; child = child.nextSibling) {
        if (child.nodeType == 3)
          offset += child.nodeValue.length;
        else if (tag(child) == 'br')
          offset += 1;
        else if (child.nodeType == 1) {
          result.push({
            event: 'start',
            offset: offset,
            node: child
          });
          offset = _nodeStream(child, offset);
          result.push({
            event: 'stop',
            offset: offset,
            node: child
          });
        }
      }
      return offset;
    })(node, 0);
    return result;
  }

  function mergeStreams(original, highlighted, value) {
    var processed = 0;
    var result = '';
    var nodeStack = [];

    function selectStream() {
      if (!original.length || !highlighted.length) {
        return original.length ? original : highlighted;
      }
      if (original[0].offset != highlighted[0].offset) {
        return (original[0].offset < highlighted[0].offset) ? original : highlighted;
      }

      /*
      To avoid starting the stream just before it should stop the order is
      ensured that original always starts first and closes last:

      if (event1 == 'start' && event2 == 'start')
        return original;
      if (event1 == 'start' && event2 == 'stop')
        return highlighted;
      if (event1 == 'stop' && event2 == 'start')
        return original;
      if (event1 == 'stop' && event2 == 'stop')
        return highlighted;

      ... which is collapsed to:
      */
      return highlighted[0].event == 'start' ? original : highlighted;
    }

    function open(node) {
      function attr_str(a) {return ' ' + a.nodeName + '="' + escape(a.value) + '"';}
      result += '<' + tag(node) + Array.prototype.map.call(node.attributes, attr_str).join('') + '>';
    }

    function close(node) {
      result += '</' + tag(node) + '>';
    }

    function render(event) {
      (event.event == 'start' ? open : close)(event.node);
    }

    while (original.length || highlighted.length) {
      var stream = selectStream();
      result += escape(value.substr(processed, stream[0].offset - processed));
      processed = stream[0].offset;
      if (stream == original) {
        /*
        On any opening or closing tag of the original markup we first close
        the entire highlighted node stack, then render the original tag along
        with all the following original tags at the same offset and then
        reopen all the tags on the highlighted stack.
        */
        nodeStack.reverse().forEach(close);
        do {
          render(stream.splice(0, 1)[0]);
          stream = selectStream();
        } while (stream == original && stream.length && stream[0].offset == processed);
        nodeStack.reverse().forEach(open);
      } else {
        if (stream[0].event == 'start') {
          nodeStack.push(stream[0].node);
        } else {
          nodeStack.pop();
        }
        render(stream.splice(0, 1)[0]);
      }
    }
    return result + escape(value.substr(processed));
  }

  /* Initialization */

  function compileLanguage(language) {

    function reStr(re) {
        return (re && re.source) || re;
    }

    function langRe(value, global) {
      return RegExp(
        reStr(value),
        'm' + (language.case_insensitive ? 'i' : '') + (global ? 'g' : '')
      );
    }

    function compileMode(mode, parent) {
      if (mode.compiled)
        return;
      mode.compiled = true;

      mode.keywords = mode.keywords || mode.beginKeywords;
      if (mode.keywords) {
        var compiled_keywords = {};

        function flatten(className, str) {
          if (language.case_insensitive) {
            str = str.toLowerCase();
          }
          str.split(' ').forEach(function(kw) {
            var pair = kw.split('|');
            compiled_keywords[pair[0]] = [className, pair[1] ? Number(pair[1]) : 1];
          });
        }

        if (typeof mode.keywords == 'string') { // string
          flatten('keyword', mode.keywords);
        } else {
          Object.keys(mode.keywords).forEach(function (className) {
            flatten(className, mode.keywords[className]);
          });
        }
        mode.keywords = compiled_keywords;
      }
      mode.lexemesRe = langRe(mode.lexemes || /\b[A-Za-z0-9_]+\b/, true);

      if (parent) {
        if (mode.beginKeywords) {
          mode.begin = '\\b(' + mode.beginKeywords.split(' ').join('|') + ')\\b';
        }
        if (!mode.begin)
          mode.begin = /\B|\b/;
        mode.beginRe = langRe(mode.begin);
        if (!mode.end && !mode.endsWithParent)
          mode.end = /\B|\b/;
        if (mode.end)
          mode.endRe = langRe(mode.end);
        mode.terminator_end = reStr(mode.end) || '';
        if (mode.endsWithParent && parent.terminator_end)
          mode.terminator_end += (mode.end ? '|' : '') + parent.terminator_end;
      }
      if (mode.illegal)
        mode.illegalRe = langRe(mode.illegal);
      if (mode.relevance === undefined)
        mode.relevance = 1;
      if (!mode.contains) {
        mode.contains = [];
      }
      var expanded_contains = [];
      mode.contains.forEach(function(c) {
        if (c.variants) {
          c.variants.forEach(function(v) {expanded_contains.push(inherit(c, v));});
        } else {
          expanded_contains.push(c == 'self' ? mode : c);
        }
      });
      mode.contains = expanded_contains;
      mode.contains.forEach(function(c) {compileMode(c, mode);});

      if (mode.starts) {
        compileMode(mode.starts, parent);
      }

      var terminators =
        mode.contains.map(function(c) {
          return c.beginKeywords ? '\\.?(' + c.begin + ')\\.?' : c.begin;
        })
        .concat([mode.terminator_end, mode.illegal])
        .map(reStr)
        .filter(Boolean);
      mode.terminators = terminators.length ? langRe(terminators.join('|'), true) : {exec: function(s) {return null;}};

      mode.continuation = {};
    }

    compileMode(language);
  }

  /*
  Core highlighting function. Accepts a language name, or an alias, and a
  string with the code to highlight. Returns an object with the following
  properties:

  - relevance (int)
  - value (an HTML string with highlighting markup)

  */
  function highlight(name, value, ignore_illegals, continuation) {

    function subMode(lexeme, mode) {
      for (var i = 0; i < mode.contains.length; i++) {
        if (testRe(mode.contains[i].beginRe, lexeme)) {
          return mode.contains[i];
        }
      }
    }

    function endOfMode(mode, lexeme) {
      if (testRe(mode.endRe, lexeme)) {
        return mode;
      }
      if (mode.endsWithParent) {
        return endOfMode(mode.parent, lexeme);
      }
    }

    function isIllegal(lexeme, mode) {
      return !ignore_illegals && testRe(mode.illegalRe, lexeme);
    }

    function keywordMatch(mode, match) {
      var match_str = language.case_insensitive ? match[0].toLowerCase() : match[0];
      return mode.keywords.hasOwnProperty(match_str) && mode.keywords[match_str];
    }

    function buildSpan(classname, insideSpan, leaveOpen, noPrefix) {
      var classPrefix = noPrefix ? '' : options.classPrefix,
          openSpan    = '<span class="' + classPrefix,
          closeSpan   = leaveOpen ? '' : '</span>';

      openSpan += classname + '">';

      return openSpan + insideSpan + closeSpan;
    }

    function processKeywords() {
      if (!top.keywords)
        return escape(mode_buffer);
      var result = '';
      var last_index = 0;
      top.lexemesRe.lastIndex = 0;
      var match = top.lexemesRe.exec(mode_buffer);
      while (match) {
        result += escape(mode_buffer.substr(last_index, match.index - last_index));
        var keyword_match = keywordMatch(top, match);
        if (keyword_match) {
          relevance += keyword_match[1];
          result += buildSpan(keyword_match[0], escape(match[0]));
        } else {
          result += escape(match[0]);
        }
        last_index = top.lexemesRe.lastIndex;
        match = top.lexemesRe.exec(mode_buffer);
      }
      return result + escape(mode_buffer.substr(last_index));
    }

    function processSubLanguage() {
      if (top.subLanguage && !languages[top.subLanguage]) {
        return escape(mode_buffer);
      }
      var result = top.subLanguage ? highlight(top.subLanguage, mode_buffer, true, top.continuation.top) : highlightAuto(mode_buffer);
      // Counting embedded language score towards the host language may be disabled
      // with zeroing the containing mode relevance. Usecase in point is Markdown that
      // allows XML everywhere and makes every XML snippet to have a much larger Markdown
      // score.
      if (top.relevance > 0) {
        relevance += result.relevance;
      }
      if (top.subLanguageMode == 'continuous') {
        top.continuation.top = result.top;
      }
      return buildSpan(result.language, result.value, false, true);
    }

    function processBuffer() {
      return top.subLanguage !== undefined ? processSubLanguage() : processKeywords();
    }

    function startNewMode(mode, lexeme) {
      var markup = mode.className? buildSpan(mode.className, '', true): '';
      if (mode.returnBegin) {
        result += markup;
        mode_buffer = '';
      } else if (mode.excludeBegin) {
        result += escape(lexeme) + markup;
        mode_buffer = '';
      } else {
        result += markup;
        mode_buffer = lexeme;
      }
      top = Object.create(mode, {parent: {value: top}});
    }

    function processLexeme(buffer, lexeme) {

      mode_buffer += buffer;
      if (lexeme === undefined) {
        result += processBuffer();
        return 0;
      }

      var new_mode = subMode(lexeme, top);
      if (new_mode) {
        result += processBuffer();
        startNewMode(new_mode, lexeme);
        return new_mode.returnBegin ? 0 : lexeme.length;
      }

      var end_mode = endOfMode(top, lexeme);
      if (end_mode) {
        var origin = top;
        if (!(origin.returnEnd || origin.excludeEnd)) {
          mode_buffer += lexeme;
        }
        result += processBuffer();
        do {
          if (top.className) {
            result += '</span>';
          }
          relevance += top.relevance;
          top = top.parent;
        } while (top != end_mode.parent);
        if (origin.excludeEnd) {
          result += escape(lexeme);
        }
        mode_buffer = '';
        if (end_mode.starts) {
          startNewMode(end_mode.starts, '');
        }
        return origin.returnEnd ? 0 : lexeme.length;
      }

      if (isIllegal(lexeme, top))
        throw new Error('Illegal lexeme "' + lexeme + '" for mode "' + (top.className || '<unnamed>') + '"');

      /*
      Parser should not reach this point as all types of lexemes should be caught
      earlier, but if it does due to some bug make sure it advances at least one
      character forward to prevent infinite looping.
      */
      mode_buffer += lexeme;
      return lexeme.length || 1;
    }

    var language = getLanguage(name);
    if (!language) {
      throw new Error('Unknown language: "' + name + '"');
    }

    compileLanguage(language);
    var top = continuation || language;
    var result = '';
    for(var current = top; current != language; current = current.parent) {
      if (current.className) {
        result += buildSpan(current.className, result, true);
      }
    }
    var mode_buffer = '';
    var relevance = 0;
    try {
      var match, count, index = 0;
      while (true) {
        top.terminators.lastIndex = index;
        match = top.terminators.exec(value);
        if (!match)
          break;
        count = processLexeme(value.substr(index, match.index - index), match[0]);
        index = match.index + count;
      }
      processLexeme(value.substr(index));
      for(var current = top; current.parent; current = current.parent) { // close dangling modes
        if (current.className) {
          result += '</span>';
        }
      };
      return {
        relevance: relevance,
        value: result,
        language: name,
        top: top
      };
    } catch (e) {
      if (e.message.indexOf('Illegal') != -1) {
        return {
          relevance: 0,
          value: escape(value)
        };
      } else {
        throw e;
      }
    }
  }

  /*
  Highlighting with language detection. Accepts a string with the code to
  highlight. Returns an object with the following properties:

  - language (detected language)
  - relevance (int)
  - value (an HTML string with highlighting markup)
  - second_best (object with the same structure for second-best heuristically
    detected language, may be absent)

  */
  function highlightAuto(text, languageSubset) {
    languageSubset = languageSubset || options.languages || Object.keys(languages);
    var result = {
      relevance: 0,
      value: escape(text)
    };
    var second_best = result;
    languageSubset.forEach(function(name) {
      if (!getLanguage(name)) {
        return;
      }
      var current = highlight(name, text, false);
      current.language = name;
      if (current.relevance > second_best.relevance) {
        second_best = current;
      }
      if (current.relevance > result.relevance) {
        second_best = result;
        result = current;
      }
    });
    if (second_best.language) {
      result.second_best = second_best;
    }
    return result;
  }

  /*
  Post-processing of the highlighted markup:

  - replace TABs with something more useful
  - replace real line-breaks with '<br>' for non-pre containers

  */
  function fixMarkup(value) {
    if (options.tabReplace) {
      value = value.replace(/^((<[^>]+>|\t)+)/gm, function(match, p1, offset, s) {
        return p1.replace(/\t/g, options.tabReplace);
      });
    }
    if (options.useBR) {
      value = value.replace(/\n/g, '<br>');
    }
    return value;
  }

  /*
  Applies highlighting to a DOM node containing code. Accepts a DOM node and
  two optional parameters for fixMarkup.
  */
  function highlightBlock(block) {
    var text = options.useBR ? block.innerHTML
      .replace(/\n/g,'').replace(/<br>|<br [^>]*>/g, '\n').replace(/<[^>]*>/g,'')
      : block.textContent;
    var language = blockLanguage(block);
    if (language == 'no-highlight')
        return;
    var result = language ? highlight(language, text, true) : highlightAuto(text);
    var original = nodeStream(block);
    if (original.length) {
      var pre = document.createElementNS('http://www.w3.org/1999/xhtml', 'pre');
      pre.innerHTML = result.value;
      result.value = mergeStreams(original, nodeStream(pre), text);
    }
    result.value = fixMarkup(result.value);

    block.innerHTML = result.value;
    block.className += ' hljs ' + (!language && result.language || '');
    block.result = {
      language: result.language,
      re: result.relevance
    };
    if (result.second_best) {
      block.second_best = {
        language: result.second_best.language,
        re: result.second_best.relevance
      };
    }
  }

  var options = {
    classPrefix: 'hljs-',
    tabReplace: null,
    useBR: false,
    languages: undefined
  };

  /*
  Updates highlight.js global options with values passed in the form of an object
  */
  function configure(user_options) {
    options = inherit(options, user_options);
  }

  /*
  Applies highlighting to all <pre><code>..</code></pre> blocks on a page.
  */
  function initHighlighting() {
    if (initHighlighting.called)
      return;
    initHighlighting.called = true;

    var blocks = document.querySelectorAll('pre code');
    Array.prototype.forEach.call(blocks, highlightBlock);
  }

  /*
  Attaches highlighting to the page load event.
  */
  function initHighlightingOnLoad() {
    addEventListener('DOMContentLoaded', initHighlighting, false);
    addEventListener('load', initHighlighting, false);
  }

  var languages = {};
  var aliases = {};

  function registerLanguage(name, language) {
    var lang = languages[name] = language(this);
    if (lang.aliases) {
      lang.aliases.forEach(function(alias) {aliases[alias] = name;});
    }
  }

  function listLanguages() {
    return Object.keys(languages);
  }

  function getLanguage(name) {
    return languages[name] || languages[aliases[name]];
  }

  /* Interface definition */

  this.highlight = highlight;
  this.highlightAuto = highlightAuto;
  this.fixMarkup = fixMarkup;
  this.highlightBlock = highlightBlock;
  this.configure = configure;
  this.initHighlighting = initHighlighting;
  this.initHighlightingOnLoad = initHighlightingOnLoad;
  this.registerLanguage = registerLanguage;
  this.listLanguages = listLanguages;
  this.getLanguage = getLanguage;
  this.inherit = inherit;

  // Common regexps
  this.IDENT_RE = '[a-zA-Z][a-zA-Z0-9_]*';
  this.UNDERSCORE_IDENT_RE = '[a-zA-Z_][a-zA-Z0-9_]*';
  this.NUMBER_RE = '\\b\\d+(\\.\\d+)?';
  this.C_NUMBER_RE = '(\\b0[xX][a-fA-F0-9]+|(\\b\\d+(\\.\\d*)?|\\.\\d+)([eE][-+]?\\d+)?)'; // 0x..., 0..., decimal, float
  this.BINARY_NUMBER_RE = '\\b(0b[01]+)'; // 0b...
  this.RE_STARTERS_RE = '!|!=|!==|%|%=|&|&&|&=|\\*|\\*=|\\+|\\+=|,|-|-=|/=|/|:|;|<<|<<=|<=|<|===|==|=|>>>=|>>=|>=|>>>|>>|>|\\?|\\[|\\{|\\(|\\^|\\^=|\\||\\|=|\\|\\||~';

  // Common modes
  this.BACKSLASH_ESCAPE = {
    begin: '\\\\[\\s\\S]', relevance: 0
  };
  this.APOS_STRING_MODE = {
    className: 'string',
    begin: '\'', end: '\'',
    illegal: '\\n',
    contains: [this.BACKSLASH_ESCAPE]
  };
  this.QUOTE_STRING_MODE = {
    className: 'string',
    begin: '"', end: '"',
    illegal: '\\n',
    contains: [this.BACKSLASH_ESCAPE]
  };
  this.PHRASAL_WORDS_MODE = {
    begin: /\b(a|an|the|are|I|I'm|isn't|don't|doesn't|won't|but|just|should|pretty|simply|enough|gonna|going|wtf|so|such)\b/
  };
  this.C_LINE_COMMENT_MODE = {
    className: 'comment',
    begin: '//', end: '$',
    contains: [this.PHRASAL_WORDS_MODE]
  };
  this.C_BLOCK_COMMENT_MODE = {
    className: 'comment',
    begin: '/\\*', end: '\\*/',
    contains: [this.PHRASAL_WORDS_MODE]
  };
  this.HASH_COMMENT_MODE = {
    className: 'comment',
    begin: '#', end: '$',
    contains: [this.PHRASAL_WORDS_MODE]
  };
  this.NUMBER_MODE = {
    className: 'number',
    begin: this.NUMBER_RE,
    relevance: 0
  };
  this.C_NUMBER_MODE = {
    className: 'number',
    begin: this.C_NUMBER_RE,
    relevance: 0
  };
  this.BINARY_NUMBER_MODE = {
    className: 'number',
    begin: this.BINARY_NUMBER_RE,
    relevance: 0
  };
  this.CSS_NUMBER_MODE = {
    className: 'number',
    begin: this.NUMBER_RE + '(' +
      '%|em|ex|ch|rem'  +
      '|vw|vh|vmin|vmax' +
      '|cm|mm|in|pt|pc|px' +
      '|deg|grad|rad|turn' +
      '|s|ms' +
      '|Hz|kHz' +
      '|dpi|dpcm|dppx' +
      ')?',
    relevance: 0
  };
  this.REGEXP_MODE = {
    className: 'regexp',
    begin: /\//, end: /\/[gim]*/,
    illegal: /\n/,
    contains: [
      this.BACKSLASH_ESCAPE,
      {
        begin: /\[/, end: /\]/,
        relevance: 0,
        contains: [this.BACKSLASH_ESCAPE]
      }
    ]
  };
  this.TITLE_MODE = {
    className: 'title',
    begin: this.IDENT_RE,
    relevance: 0
  };
  this.UNDERSCORE_TITLE_MODE = {
    className: 'title',
    begin: this.UNDERSCORE_IDENT_RE,
    relevance: 0
  };
};
module.exports = Highlight;
},{}],42:[function(require,module,exports){
var Highlight = require('./highlight');
var hljs = new Highlight();
hljs.registerLanguage('apache', require('./languages/apache.js'));
hljs.registerLanguage('bash', require('./languages/bash.js'));
hljs.registerLanguage('clojure', require('./languages/clojure.js'));
hljs.registerLanguage('coffeescript', require('./languages/coffeescript.js'));
hljs.registerLanguage('cpp', require('./languages/cpp.js'));
hljs.registerLanguage('cs', require('./languages/cs.js'));
hljs.registerLanguage('css', require('./languages/css.js'));
hljs.registerLanguage('diff', require('./languages/diff.js'));
hljs.registerLanguage('erlang', require('./languages/erlang.js'));
hljs.registerLanguage('go', require('./languages/go.js'));
hljs.registerLanguage('ruby', require('./languages/ruby.js'));
hljs.registerLanguage('haml', require('./languages/haml.js'));
hljs.registerLanguage('haskell', require('./languages/haskell.js'));
hljs.registerLanguage('http', require('./languages/http.js'));
hljs.registerLanguage('ini', require('./languages/ini.js'));
hljs.registerLanguage('java', require('./languages/java.js'));
hljs.registerLanguage('javascript', require('./languages/javascript.js'));
hljs.registerLanguage('json', require('./languages/json.js'));
hljs.registerLanguage('lisp', require('./languages/lisp.js'));
hljs.registerLanguage('lua', require('./languages/lua.js'));
hljs.registerLanguage('makefile', require('./languages/makefile.js'));
hljs.registerLanguage('xml', require('./languages/xml.js'));
hljs.registerLanguage('markdown', require('./languages/markdown.js'));
hljs.registerLanguage('nginx', require('./languages/nginx.js'));
hljs.registerLanguage('objectivec', require('./languages/objectivec.js'));
hljs.registerLanguage('perl', require('./languages/perl.js'));
hljs.registerLanguage('php', require('./languages/php.js'));
hljs.registerLanguage('python', require('./languages/python.js'));
hljs.registerLanguage('r', require('./languages/r.js'));
hljs.registerLanguage('rust', require('./languages/rust.js'));
hljs.registerLanguage('scala', require('./languages/scala.js'));
hljs.registerLanguage('scss', require('./languages/scss.js'));
hljs.registerLanguage('sql', require('./languages/sql.js'));
module.exports = hljs;
},{"./highlight":41,"./languages/apache.js":43,"./languages/bash.js":44,"./languages/clojure.js":45,"./languages/coffeescript.js":46,"./languages/cpp.js":47,"./languages/cs.js":48,"./languages/css.js":49,"./languages/diff.js":50,"./languages/erlang.js":51,"./languages/go.js":52,"./languages/haml.js":53,"./languages/haskell.js":54,"./languages/http.js":55,"./languages/ini.js":56,"./languages/java.js":57,"./languages/javascript.js":58,"./languages/json.js":59,"./languages/lisp.js":60,"./languages/lua.js":61,"./languages/makefile.js":62,"./languages/markdown.js":63,"./languages/nginx.js":64,"./languages/objectivec.js":65,"./languages/perl.js":66,"./languages/php.js":67,"./languages/python.js":68,"./languages/r.js":69,"./languages/ruby.js":70,"./languages/rust.js":71,"./languages/scala.js":72,"./languages/scss.js":73,"./languages/sql.js":74,"./languages/xml.js":75}],43:[function(require,module,exports){
module.exports = function(hljs) {
  var NUMBER = {className: 'number', begin: '[\\$%]\\d+'};
  return {
    aliases: ['apacheconf'],
    case_insensitive: true,
    contains: [
      hljs.HASH_COMMENT_MODE,
      {className: 'tag', begin: '</?', end: '>'},
      {
        className: 'keyword',
        begin: /\w+/,
        relevance: 0,
        // keywords aren’t needed for highlighting per se, they only boost relevance
        // for a very generally defined mode (starts with a word, ends with line-end
        keywords: {
          common:
            'order deny allow setenv rewriterule rewriteengine rewritecond documentroot ' +
            'sethandler errordocument loadmodule options header listen serverroot ' +
            'servername'
        },
        starts: {
          end: /$/,
          relevance: 0,
          keywords: {
            literal: 'on off all'
          },
          contains: [
            {
              className: 'sqbracket',
              begin: '\\s\\[', end: '\\]$'
            },
            {
              className: 'cbracket',
              begin: '[\\$%]\\{', end: '\\}',
              contains: ['self', NUMBER]
            },
            NUMBER,
            hljs.QUOTE_STRING_MODE
          ]
        }
      }
    ],
    illegal: /\S/
  };
};
},{}],44:[function(require,module,exports){
module.exports = function(hljs) {
  var VAR = {
    className: 'variable',
    variants: [
      {begin: /\$[\w\d#@][\w\d_]*/},
      {begin: /\$\{(.*?)\}/}
    ]
  };
  var QUOTE_STRING = {
    className: 'string',
    begin: /"/, end: /"/,
    contains: [
      hljs.BACKSLASH_ESCAPE,
      VAR,
      {
        className: 'variable',
        begin: /\$\(/, end: /\)/,
        contains: [hljs.BACKSLASH_ESCAPE]
      }
    ]
  };
  var APOS_STRING = {
    className: 'string',
    begin: /'/, end: /'/
  };

  return {
    aliases: ['sh', 'zsh'],
    lexemes: /-?[a-z\.]+/,
    keywords: {
      keyword:
        'if then else elif fi for break continue while in do done exit return set '+
        'declare case esac export exec',
      literal:
        'true false',
      built_in:
        'printf echo read cd pwd pushd popd dirs let eval unset typeset readonly '+
        'getopts source shopt caller type hash bind help sudo',
      operator:
        '-ne -eq -lt -gt -f -d -e -s -l -a' // relevance booster
    },
    contains: [
      {
        className: 'shebang',
        begin: /^#![^\n]+sh\s*$/,
        relevance: 10
      },
      {
        className: 'function',
        begin: /\w[\w\d_]*\s*\(\s*\)\s*\{/,
        returnBegin: true,
        contains: [hljs.inherit(hljs.TITLE_MODE, {begin: /\w[\w\d_]*/})],
        relevance: 0
      },
      hljs.HASH_COMMENT_MODE,
      hljs.NUMBER_MODE,
      QUOTE_STRING,
      APOS_STRING,
      VAR
    ]
  };
};
},{}],45:[function(require,module,exports){
module.exports = function(hljs) {
  var keywords = {
    built_in:
      // Clojure keywords
      'def cond apply if-not if-let if not not= = &lt; < > &lt;= <= >= == + / * - rem '+
      'quot neg? pos? delay? symbol? keyword? true? false? integer? empty? coll? list? '+
      'set? ifn? fn? associative? sequential? sorted? counted? reversible? number? decimal? '+
      'class? distinct? isa? float? rational? reduced? ratio? odd? even? char? seq? vector? '+
      'string? map? nil? contains? zero? instance? not-every? not-any? libspec? -> ->> .. . '+
      'inc compare do dotimes mapcat take remove take-while drop letfn drop-last take-last '+
      'drop-while while intern condp case reduced cycle split-at split-with repeat replicate '+
      'iterate range merge zipmap declare line-seq sort comparator sort-by dorun doall nthnext '+
      'nthrest partition eval doseq await await-for let agent atom send send-off release-pending-sends '+
      'add-watch mapv filterv remove-watch agent-error restart-agent set-error-handler error-handler '+
      'set-error-mode! error-mode shutdown-agents quote var fn loop recur throw try monitor-enter '+
      'monitor-exit defmacro defn defn- macroexpand macroexpand-1 for dosync and or '+
      'when when-not when-let comp juxt partial sequence memoize constantly complement identity assert '+
      'peek pop doto proxy defstruct first rest cons defprotocol cast coll deftype defrecord last butlast '+
      'sigs reify second ffirst fnext nfirst nnext defmulti defmethod meta with-meta ns in-ns create-ns import '+
      'refer keys select-keys vals key val rseq name namespace promise into transient persistent! conj! '+
      'assoc! dissoc! pop! disj! use class type num float double short byte boolean bigint biginteger '+
      'bigdec print-method print-dup throw-if printf format load compile get-in update-in pr pr-on newline '+
      'flush read slurp read-line subvec with-open memfn time re-find re-groups rand-int rand mod locking '+
      'assert-valid-fdecl alias resolve ref deref refset swap! reset! set-validator! compare-and-set! alter-meta! '+
      'reset-meta! commute get-validator alter ref-set ref-history-count ref-min-history ref-max-history ensure sync io! '+
      'new next conj set! to-array future future-call into-array aset gen-class reduce map filter find empty '+
      'hash-map hash-set sorted-map sorted-map-by sorted-set sorted-set-by vec vector seq flatten reverse assoc dissoc list '+
      'disj get union difference intersection extend extend-type extend-protocol int nth delay count concat chunk chunk-buffer '+
      'chunk-append chunk-first chunk-rest max min dec unchecked-inc-int unchecked-inc unchecked-dec-inc unchecked-dec unchecked-negate '+
      'unchecked-add-int unchecked-add unchecked-subtract-int unchecked-subtract chunk-next chunk-cons chunked-seq? prn vary-meta '+
      'lazy-seq spread list* str find-keyword keyword symbol gensym force rationalize'
   };

  var CLJ_IDENT_RE = '[a-zA-Z_0-9\\!\\.\\?\\-\\+\\*\\/\\<\\=\\>\\&\\#\\$\';]+';
  var SIMPLE_NUMBER_RE = '[\\s:\\(\\{]+\\d+(\\.\\d+)?';

  var NUMBER = {
    className: 'number', begin: SIMPLE_NUMBER_RE,
    relevance: 0
  };
  var STRING = hljs.inherit(hljs.QUOTE_STRING_MODE, {illegal: null});
  var COMMENT = {
    className: 'comment',
    begin: ';', end: '$',
    relevance: 0
  };
  var COLLECTION = {
    className: 'collection',
    begin: '[\\[\\{]', end: '[\\]\\}]'
  };
  var HINT = {
    className: 'comment',
    begin: '\\^' + CLJ_IDENT_RE
  };
  var HINT_COL = {
    className: 'comment',
    begin: '\\^\\{', end: '\\}'

  };
  var KEY = {
    className: 'attribute',
    begin: '[:]' + CLJ_IDENT_RE
  };
  var LIST = {
    className: 'list',
    begin: '\\(', end: '\\)'
  };
  var BODY = {
    endsWithParent: true,
    keywords: {literal: 'true false nil'},
    relevance: 0
  };
  var TITLE = {
    keywords: keywords,
    lexemes: CLJ_IDENT_RE,
    className: 'title', begin: CLJ_IDENT_RE,
    starts: BODY
  };

  LIST.contains = [{className: 'comment', begin: 'comment'}, TITLE, BODY];
  BODY.contains = [LIST, STRING, HINT, HINT_COL, COMMENT, KEY, COLLECTION, NUMBER];
  COLLECTION.contains = [LIST, STRING, HINT, COMMENT, KEY, COLLECTION, NUMBER];

  return {
    aliases: ['clj'],
    illegal: /\S/,
    contains: [
      COMMENT,
      LIST,
      {
        className: 'prompt',
        begin: /^=> /,
        starts: {end: /\n\n|\Z/} // eat up prompt output to not interfere with the illegal
      }
    ]
  }
};
},{}],46:[function(require,module,exports){
module.exports = function(hljs) {
  var KEYWORDS = {
    keyword:
      // JS keywords
      'in if for while finally new do return else break catch instanceof throw try this ' +
      'switch continue typeof delete debugger super ' +
      // Coffee keywords
      'then unless until loop of by when and or is isnt not',
    literal:
      // JS literals
      'true false null undefined ' +
      // Coffee literals
      'yes no on off',
    reserved:
      'case default function var void with const let enum export import native ' +
      '__hasProp __extends __slice __bind __indexOf',
    built_in:
      'npm require console print module global window document'
  };
  var JS_IDENT_RE = '[A-Za-z$_][0-9A-Za-z$_]*';
  var TITLE = hljs.inherit(hljs.TITLE_MODE, {begin: JS_IDENT_RE});
  var SUBST = {
    className: 'subst',
    begin: /#\{/, end: /}/,
    keywords: KEYWORDS
  };
  var EXPRESSIONS = [
    hljs.BINARY_NUMBER_MODE,
    hljs.inherit(hljs.C_NUMBER_MODE, {starts: {end: '(\\s*/)?', relevance: 0}}), // a number tries to eat the following slash to prevent treating it as a regexp
    {
      className: 'string',
      variants: [
        {
          begin: /'''/, end: /'''/,
          contains: [hljs.BACKSLASH_ESCAPE]
        },
        {
          begin: /'/, end: /'/,
          contains: [hljs.BACKSLASH_ESCAPE]
        },
        {
          begin: /"""/, end: /"""/,
          contains: [hljs.BACKSLASH_ESCAPE, SUBST]
        },
        {
          begin: /"/, end: /"/,
          contains: [hljs.BACKSLASH_ESCAPE, SUBST]
        }
      ]
    },
    {
      className: 'regexp',
      variants: [
        {
          begin: '///', end: '///',
          contains: [SUBST, hljs.HASH_COMMENT_MODE]
        },
        {
          begin: '//[gim]*',
          relevance: 0
        },
        {
          begin: '/\\S(\\\\.|[^\\n])*?/[gim]*(?=\\s|\\W|$)' // \S is required to parse x / 2 / 3 as two divisions
        }
      ]
    },
    {
      className: 'property',
      begin: '@' + JS_IDENT_RE
    },
    {
      begin: '`', end: '`',
      excludeBegin: true, excludeEnd: true,
      subLanguage: 'javascript'
    }
  ];
  SUBST.contains = EXPRESSIONS;

  return {
    aliases: ['coffee', 'cson', 'iced'],
    keywords: KEYWORDS,
    contains: EXPRESSIONS.concat([
      {
        className: 'comment',
        begin: '###', end: '###'
      },
      hljs.HASH_COMMENT_MODE,
      {
        className: 'function',
        begin: '(' + JS_IDENT_RE + '\\s*=\\s*)?(\\(.*\\))?\\s*\\B[-=]>', end: '[-=]>',
        returnBegin: true,
        contains: [
          TITLE,
          {
            className: 'params',
            begin: '\\(', returnBegin: true,
            /* We need another contained nameless mode to not have every nested
            pair of parens to be called "params" */
            contains: [{
              begin: /\(/, end: /\)/,
              keywords: KEYWORDS,
              contains: ['self'].concat(EXPRESSIONS)
            }]
          }
        ]
      },
      {
        className: 'class',
        beginKeywords: 'class',
        end: '$',
        illegal: /[:="\[\]]/,
        contains: [
          {
            beginKeywords: 'extends',
            endsWithParent: true,
            illegal: /[:="\[\]]/,
            contains: [TITLE]
          },
          TITLE
        ]
      },
      {
        className: 'attribute',
        begin: JS_IDENT_RE + ':', end: ':',
        returnBegin: true, excludeEnd: true,
        relevance: 0
      }
    ])
  };
};
},{}],47:[function(require,module,exports){
module.exports = function(hljs) {
  var CPP_KEYWORDS = {
    keyword: 'false int float while private char catch export virtual operator sizeof ' +
      'dynamic_cast|10 typedef const_cast|10 const struct for static_cast|10 union namespace ' +
      'unsigned long throw volatile static protected bool template mutable if public friend ' +
      'do return goto auto void enum else break new extern using true class asm case typeid ' +
      'short reinterpret_cast|10 default double register explicit signed typename try this ' +
      'switch continue wchar_t inline delete alignof char16_t char32_t constexpr decltype ' +
      'noexcept nullptr static_assert thread_local restrict _Bool complex _Complex _Imaginary',
    built_in: 'std string cin cout cerr clog stringstream istringstream ostringstream ' +
      'auto_ptr deque list queue stack vector map set bitset multiset multimap unordered_set ' +
      'unordered_map unordered_multiset unordered_multimap array shared_ptr abort abs acos ' +
      'asin atan2 atan calloc ceil cosh cos exit exp fabs floor fmod fprintf fputs free frexp ' +
      'fscanf isalnum isalpha iscntrl isdigit isgraph islower isprint ispunct isspace isupper ' +
      'isxdigit tolower toupper labs ldexp log10 log malloc memchr memcmp memcpy memset modf pow ' +
      'printf putchar puts scanf sinh sin snprintf sprintf sqrt sscanf strcat strchr strcmp ' +
      'strcpy strcspn strlen strncat strncmp strncpy strpbrk strrchr strspn strstr tanh tan ' +
      'vfprintf vprintf vsprintf'
  };
  return {
    aliases: ['c', 'h', 'c++', 'h++'],
    keywords: CPP_KEYWORDS,
    illegal: '</',
    contains: [
      hljs.C_LINE_COMMENT_MODE,
      hljs.C_BLOCK_COMMENT_MODE,
      hljs.QUOTE_STRING_MODE,
      {
        className: 'string',
        begin: '\'\\\\?.', end: '\'',
        illegal: '.'
      },
      {
        className: 'number',
        begin: '\\b(\\d+(\\.\\d*)?|\\.\\d+)(u|U|l|L|ul|UL|f|F)'
      },
      hljs.C_NUMBER_MODE,
      {
        className: 'preprocessor',
        begin: '#', end: '$',
        keywords: 'if else elif endif define undef warning error line pragma',
        contains: [
          {
            begin: 'include\\s*[<"]', end: '[>"]',
            keywords: 'include',
            illegal: '\\n'
          },
          hljs.C_LINE_COMMENT_MODE
        ]
      },
      {
        className: 'stl_container',
        begin: '\\b(deque|list|queue|stack|vector|map|set|bitset|multiset|multimap|unordered_map|unordered_set|unordered_multiset|unordered_multimap|array)\\s*<', end: '>',
        keywords: CPP_KEYWORDS,
        relevance: 10,
        contains: ['self']
      },
      {
        begin: hljs.IDENT_RE + '::'
      }
    ]
  };
};
},{}],48:[function(require,module,exports){
module.exports = function(hljs) {
  var KEYWORDS =
    // Normal keywords.
    'abstract as base bool break byte case catch char checked const continue decimal ' +
    'default delegate do double else enum event explicit extern false finally fixed float ' +
    'for foreach goto if implicit in int interface internal is lock long new null ' +
    'object operator out override params private protected public readonly ref return sbyte ' +
    'sealed short sizeof stackalloc static string struct switch this throw true try typeof ' +
    'uint ulong unchecked unsafe ushort using virtual volatile void while async await ' +
    // Contextual keywords.
    'ascending descending from get group into join let orderby partial select set value var ' +
    'where yield';
  return {
    keywords: KEYWORDS,
    illegal: /::/,
    contains: [
      {
        className: 'comment',
        begin: '///', end: '$', returnBegin: true,
        contains: [
          {
            className: 'xmlDocTag',
            variants: [
              {
                begin: '///', relevance: 0
              },
              {
                begin: '<!--|-->'
              },
              {
                begin: '</?', end: '>'
              }
            ]
          }
        ]
      },
      hljs.C_LINE_COMMENT_MODE,
      hljs.C_BLOCK_COMMENT_MODE,
      {
        className: 'preprocessor',
        begin: '#', end: '$',
        keywords: 'if else elif endif define undef warning error line region endregion pragma checksum'
      },
      {
        className: 'string',
        begin: '@"', end: '"',
        contains: [{begin: '""'}]
      },
      hljs.APOS_STRING_MODE,
      hljs.QUOTE_STRING_MODE,
      hljs.C_NUMBER_MODE,
      {
        beginKeywords: 'protected public private internal', end: /[{;=]/,
        keywords: KEYWORDS,
        contains: [
          {
            beginKeywords: 'class namespace interface',
            starts: {
              contains: [hljs.TITLE_MODE]
            }
          },
          {
            begin: hljs.IDENT_RE + '\\s*\\(', returnBegin: true,
            contains: [
              hljs.TITLE_MODE
            ]
          }
        ]
      }
    ]
  };
};
},{}],49:[function(require,module,exports){
module.exports = function(hljs) {
  var IDENT_RE = '[a-zA-Z-][a-zA-Z0-9_-]*';
  var FUNCTION = {
    className: 'function',
    begin: IDENT_RE + '\\(', 
    returnBegin: true,
    excludeEnd: true,
    end: '\\('
  };
  return {
    case_insensitive: true,
    illegal: '[=/|\']',
    contains: [
      hljs.C_BLOCK_COMMENT_MODE,
      {
        className: 'id', begin: '\\#[A-Za-z0-9_-]+'
      },
      {
        className: 'class', begin: '\\.[A-Za-z0-9_-]+',
        relevance: 0
      },
      {
        className: 'attr_selector',
        begin: '\\[', end: '\\]',
        illegal: '$'
      },
      {
        className: 'pseudo',
        begin: ':(:)?[a-zA-Z0-9\\_\\-\\+\\(\\)\\"\\\']+'
      },
      {
        className: 'at_rule',
        begin: '@(font-face|page)',
        lexemes: '[a-z-]+',
        keywords: 'font-face page'
      },
      {
        className: 'at_rule',
        begin: '@', end: '[{;]', // at_rule eating first "{" is a good thing
                                 // because it doesn’t let it to be parsed as
                                 // a rule set but instead drops parser into
                                 // the default mode which is how it should be.
        contains: [
          {
            className: 'keyword',
            begin: /\S+/
          },
          {
            begin: /\s/, endsWithParent: true, excludeEnd: true,
            relevance: 0,
            contains: [
              FUNCTION,
              hljs.APOS_STRING_MODE, hljs.QUOTE_STRING_MODE,
              hljs.CSS_NUMBER_MODE
            ]
          }
        ]
      },
      {
        className: 'tag', begin: IDENT_RE,
        relevance: 0
      },
      {
        className: 'rules',
        begin: '{', end: '}',
        illegal: '[^\\s]',
        relevance: 0,
        contains: [
          hljs.C_BLOCK_COMMENT_MODE,
          {
            className: 'rule',
            begin: '[^\\s]', returnBegin: true, end: ';', endsWithParent: true,
            contains: [
              {
                className: 'attribute',
                begin: '[A-Z\\_\\.\\-]+', end: ':',
                excludeEnd: true,
                illegal: '[^\\s]',
                starts: {
                  className: 'value',
                  endsWithParent: true, excludeEnd: true,
                  contains: [
                    FUNCTION,
                    hljs.CSS_NUMBER_MODE,
                    hljs.QUOTE_STRING_MODE,
                    hljs.APOS_STRING_MODE,
                    hljs.C_BLOCK_COMMENT_MODE,
                    {
                      className: 'hexcolor', begin: '#[0-9A-Fa-f]+'
                    },
                    {
                      className: 'important', begin: '!important'
                    }
                  ]
                }
              }
            ]
          }
        ]
      }
    ]
  };
};
},{}],50:[function(require,module,exports){
module.exports = function(hljs) {
  return {
    aliases: ['patch'],
    contains: [
      {
        className: 'chunk',
        relevance: 10,
        variants: [
          {begin: /^\@\@ +\-\d+,\d+ +\+\d+,\d+ +\@\@$/},
          {begin: /^\*\*\* +\d+,\d+ +\*\*\*\*$/},
          {begin: /^\-\-\- +\d+,\d+ +\-\-\-\-$/}
        ]
      },
      {
        className: 'header',
        variants: [
          {begin: /Index: /, end: /$/},
          {begin: /=====/, end: /=====$/},
          {begin: /^\-\-\-/, end: /$/},
          {begin: /^\*{3} /, end: /$/},
          {begin: /^\+\+\+/, end: /$/},
          {begin: /\*{5}/, end: /\*{5}$/}
        ]
      },
      {
        className: 'addition',
        begin: '^\\+', end: '$'
      },
      {
        className: 'deletion',
        begin: '^\\-', end: '$'
      },
      {
        className: 'change',
        begin: '^\\!', end: '$'
      }
    ]
  };
};
},{}],51:[function(require,module,exports){
module.exports = function(hljs) {
  var BASIC_ATOM_RE = '[a-z\'][a-zA-Z0-9_\']*';
  var FUNCTION_NAME_RE = '(' + BASIC_ATOM_RE + ':' + BASIC_ATOM_RE + '|' + BASIC_ATOM_RE + ')';
  var ERLANG_RESERVED = {
    keyword:
      'after and andalso|10 band begin bnot bor bsl bzr bxor case catch cond div end fun let ' +
      'not of orelse|10 query receive rem try when xor',
    literal:
      'false true'
  };

  var COMMENT = {
    className: 'comment',
    begin: '%', end: '$',
    relevance: 0
  };
  var NUMBER = {
    className: 'number',
    begin: '\\b(\\d+#[a-fA-F0-9]+|\\d+(\\.\\d+)?([eE][-+]?\\d+)?)',
    relevance: 0
  };
  var NAMED_FUN = {
    begin: 'fun\\s+' + BASIC_ATOM_RE + '/\\d+'
  };
  var FUNCTION_CALL = {
    begin: FUNCTION_NAME_RE + '\\(', end: '\\)',
    returnBegin: true,
    relevance: 0,
    contains: [
      {
        className: 'function_name', begin: FUNCTION_NAME_RE,
        relevance: 0
      },
      {
        begin: '\\(', end: '\\)', endsWithParent: true,
        returnEnd: true,
        relevance: 0
        // "contains" defined later
      }
    ]
  };
  var TUPLE = {
    className: 'tuple',
    begin: '{', end: '}',
    relevance: 0
    // "contains" defined later
  };
  var VAR1 = {
    className: 'variable',
    begin: '\\b_([A-Z][A-Za-z0-9_]*)?',
    relevance: 0
  };
  var VAR2 = {
    className: 'variable',
    begin: '[A-Z][a-zA-Z0-9_]*',
    relevance: 0
  };
  var RECORD_ACCESS = {
    begin: '#' + hljs.UNDERSCORE_IDENT_RE,
    relevance: 0,
    returnBegin: true,
    contains: [
      {
        className: 'record_name',
        begin: '#' + hljs.UNDERSCORE_IDENT_RE,
        relevance: 0
      },
      {
        begin: '{', end: '}',
        relevance: 0
        // "contains" defined later
      }
    ]
  };

  var BLOCK_STATEMENTS = {
    beginKeywords: 'fun receive if try case', end: 'end',
    keywords: ERLANG_RESERVED
  };
  BLOCK_STATEMENTS.contains = [
    COMMENT,
    NAMED_FUN,
    hljs.inherit(hljs.APOS_STRING_MODE, {className: ''}),
    BLOCK_STATEMENTS,
    FUNCTION_CALL,
    hljs.QUOTE_STRING_MODE,
    NUMBER,
    TUPLE,
    VAR1, VAR2,
    RECORD_ACCESS
  ];

  var BASIC_MODES = [
    COMMENT,
    NAMED_FUN,
    BLOCK_STATEMENTS,
    FUNCTION_CALL,
    hljs.QUOTE_STRING_MODE,
    NUMBER,
    TUPLE,
    VAR1, VAR2,
    RECORD_ACCESS
  ];
  FUNCTION_CALL.contains[1].contains = BASIC_MODES;
  TUPLE.contains = BASIC_MODES;
  RECORD_ACCESS.contains[1].contains = BASIC_MODES;

  var PARAMS = {
    className: 'params',
    begin: '\\(', end: '\\)',
    contains: BASIC_MODES
  };
  return {
    aliases: ['erl'],
    keywords: ERLANG_RESERVED,
    illegal: '(</|\\*=|\\+=|-=|/=|/\\*|\\*/|\\(\\*|\\*\\))',
    contains: [
      {
        className: 'function',
        begin: '^' + BASIC_ATOM_RE + '\\s*\\(', end: '->',
        returnBegin: true,
        illegal: '\\(|#|//|/\\*|\\\\|:|;',
        contains: [
          PARAMS,
          hljs.inherit(hljs.TITLE_MODE, {begin: BASIC_ATOM_RE})
        ],
        starts: {
          end: ';|\\.',
          keywords: ERLANG_RESERVED,
          contains: BASIC_MODES
        }
      },
      COMMENT,
      {
        className: 'pp',
        begin: '^-', end: '\\.',
        relevance: 0,
        excludeEnd: true,
        returnBegin: true,
        lexemes: '-' + hljs.IDENT_RE,
        keywords:
          '-module -record -undef -export -ifdef -ifndef -author -copyright -doc -vsn ' +
          '-import -include -include_lib -compile -define -else -endif -file -behaviour ' +
          '-behavior',
        contains: [PARAMS]
      },
      NUMBER,
      hljs.QUOTE_STRING_MODE,
      RECORD_ACCESS,
      VAR1, VAR2,
      TUPLE
    ]
  };
};
},{}],52:[function(require,module,exports){
module.exports = function(hljs) {
  var GO_KEYWORDS = {
    keyword:
      'break default func interface select case map struct chan else goto package switch ' +
      'const fallthrough if range type continue for import return var go defer',
    constant:
       'true false iota nil',
    typename:
      'bool byte complex64 complex128 float32 float64 int8 int16 int32 int64 string uint8 ' +
      'uint16 uint32 uint64 int uint uintptr rune',
    built_in:
      'append cap close complex copy imag len make new panic print println real recover delete'
  };
  return {
    aliases: ["golang"],
    keywords: GO_KEYWORDS,
    illegal: '</',
    contains: [
      hljs.C_LINE_COMMENT_MODE,
      hljs.C_BLOCK_COMMENT_MODE,
      hljs.QUOTE_STRING_MODE,
      {
        className: 'string',
        begin: '\'', end: '[^\\\\]\''
      },
      {
        className: 'string',
        begin: '`', end: '`'
      },
      {
        className: 'number',
        begin: '[^a-zA-Z_0-9](\\-|\\+)?\\d+(\\.\\d+|\\/\\d+)?((d|e|f|l|s)(\\+|\\-)?\\d+)?',
        relevance: 0
      },
      hljs.C_NUMBER_MODE
    ]
  };
};
},{}],53:[function(require,module,exports){
module.exports = // TODO support filter tags like :javascript, support inline HTML
function(hljs) {
  return {
    case_insensitive: true,
    contains: [
      {
        className: 'doctype',
        begin: '^!!!( (5|1\\.1|Strict|Frameset|Basic|Mobile|RDFa|XML\\b.*))?$',
        relevance: 10
      },
      {
        className: 'comment',
        // FIXME these comments should be allowed to span indented lines
        begin: '^\\s*(!=#|=#|-#|/).*$',
        relevance: 0
      },
      {
        begin: '^\\s*(-|=|!=)(?!#)',
        starts: {
          end: '\\n',
          subLanguage: 'ruby'
        }
      },
      {
        className: 'tag',
        begin: '^\\s*%',
        contains: [
          {
            className: 'title',
            begin: '\\w+'
          },
          {
            className: 'value',
            begin: '[#\\.]\\w+'
          },
          {
            begin: '{\\s*',
            end: '\\s*}',
            excludeEnd: true,
            contains: [
              {
                //className: 'attribute',
                begin: ':\\w+\\s*=>',
                end: ',\\s+',
                returnBegin: true,
                endsWithParent: true,
                contains: [
                  {
                    className: 'symbol',
                    begin: ':\\w+'
                  },
                  {
                    className: 'string',
                    begin: '"',
                    end: '"'
                  },
                  {
                    className: 'string',
                    begin: '\'',
                    end: '\''
                  },
                  {
                    begin: '\\w+',
                    relevance: 0
                  }
                ]
              }
            ]
          },
          {
            begin: '\\(\\s*',
            end: '\\s*\\)',
            excludeEnd: true,
            contains: [
              {
                //className: 'attribute',
                begin: '\\w+\\s*=',
                end: '\\s+',
                returnBegin: true,
                endsWithParent: true,
                contains: [
                  {
                    className: 'attribute',
                    begin: '\\w+',
                    relevance: 0
                  },
                  {
                    className: 'string',
                    begin: '"',
                    end: '"'
                  },
                  {
                    className: 'string',
                    begin: '\'',
                    end: '\''
                  },
                  {
                    begin: '\\w+',
                    relevance: 0
                  }
                ]
              }
            ]
          }
        ]
      },
      {
        className: 'bullet',
        begin: '^\\s*[=~]\\s*',
        relevance: 0
      },
      {
        begin: '#{',
        starts: {
          end: '}',
          subLanguage: 'ruby'
        }
      }
    ]
  };
};
},{}],54:[function(require,module,exports){
module.exports = function(hljs) {

  var COMMENT = {
    className: 'comment',
    variants: [
      { begin: '--', end: '$' },
      { begin: '{-', end: '-}'
      , contains: ['self']
      }
    ]
  };

  var PRAGMA = {
    className: 'pragma',
    begin: '{-#', end: '#-}'
  };

  var PREPROCESSOR = {
    className: 'preprocessor',
    begin: '^#', end: '$'
  };

  var CONSTRUCTOR = {
    className: 'type',
    begin: '\\b[A-Z][\\w\']*', // TODO: other constructors (build-in, infix).
    relevance: 0
  };

  var LIST = {
    className: 'container',
    begin: '\\(', end: '\\)',
    illegal: '"',
    contains: [
      PRAGMA,
      COMMENT,
      PREPROCESSOR,
      {className: 'type', begin: '\\b[A-Z][\\w]*(\\((\\.\\.|,|\\w+)\\))?'},
      hljs.inherit(hljs.TITLE_MODE, {begin: '[_a-z][\\w\']*'})
    ]
  };

  var RECORD = {
    className: 'container',
    begin: '{', end: '}',
    contains: LIST.contains
  };

  return {
    aliases: ['hs'],
    keywords:
      'let in if then else case of where do module import hiding ' +
      'qualified type data newtype deriving class instance as default ' +
      'infix infixl infixr foreign export ccall stdcall cplusplus ' +
      'jvm dotnet safe unsafe family forall mdo proc rec',
    contains: [

      // Top-level constructions.

      {
        className: 'module',
        begin: '\\bmodule\\b', end: 'where',
        keywords: 'module where',
        contains: [LIST, COMMENT],
        illegal: '\\W\\.|;'
      },
      {
        className: 'import',
        begin: '\\bimport\\b', end: '$',
        keywords: 'import|0 qualified as hiding',
        contains: [LIST, COMMENT],
        illegal: '\\W\\.|;'
      },

      {
        className: 'class',
        begin: '^(\\s*)?(class|instance)\\b', end: 'where',
        keywords: 'class family instance where',
        contains: [CONSTRUCTOR, LIST, COMMENT]
      },
      {
        className: 'typedef',
        begin: '\\b(data|(new)?type)\\b', end: '$',
        keywords: 'data family type newtype deriving',
        contains: [PRAGMA, COMMENT, CONSTRUCTOR, LIST, RECORD]
      },
      {
        className: 'default',
        beginKeywords: 'default', end: '$',
        contains: [CONSTRUCTOR, LIST, COMMENT]
      },
      {
        className: 'infix',
        beginKeywords: 'infix infixl infixr', end: '$',
        contains: [hljs.C_NUMBER_MODE, COMMENT]
      },
      {
        className: 'foreign',
        begin: '\\bforeign\\b', end: '$',
        keywords: 'foreign import export ccall stdcall cplusplus jvm ' +
                  'dotnet safe unsafe',
        contains: [CONSTRUCTOR, hljs.QUOTE_STRING_MODE, COMMENT]
      },
      {
        className: 'shebang',
        begin: '#!\\/usr\\/bin\\/env\ runhaskell', end: '$'
      },

      // "Whitespaces".

      PRAGMA,
      COMMENT,
      PREPROCESSOR,

      // Literals and names.

      // TODO: characters.
      hljs.QUOTE_STRING_MODE,
      hljs.C_NUMBER_MODE,
      CONSTRUCTOR,
      hljs.inherit(hljs.TITLE_MODE, {begin: '^[_a-z][\\w\']*'}),

      {begin: '->|<-'} // No markup, relevance booster
    ]
  };
};
},{}],55:[function(require,module,exports){
module.exports = function(hljs) {
  return {
    illegal: '\\S',
    contains: [
      {
        className: 'status',
        begin: '^HTTP/[0-9\\.]+', end: '$',
        contains: [{className: 'number', begin: '\\b\\d{3}\\b'}]
      },
      {
        className: 'request',
        begin: '^[A-Z]+ (.*?) HTTP/[0-9\\.]+$', returnBegin: true, end: '$',
        contains: [
          {
            className: 'string',
            begin: ' ', end: ' ',
            excludeBegin: true, excludeEnd: true
          }
        ]
      },
      {
        className: 'attribute',
        begin: '^\\w', end: ': ', excludeEnd: true,
        illegal: '\\n|\\s|=',
        starts: {className: 'string', end: '$'}
      },
      {
        begin: '\\n\\n',
        starts: {subLanguage: '', endsWithParent: true}
      }
    ]
  };
};
},{}],56:[function(require,module,exports){
module.exports = function(hljs) {
  return {
    case_insensitive: true,
    illegal: /\S/,
    contains: [
      {
        className: 'comment',
        begin: ';', end: '$'
      },
      {
        className: 'title',
        begin: '^\\[', end: '\\]'
      },
      {
        className: 'setting',
        begin: '^[a-z0-9\\[\\]_-]+[ \\t]*=[ \\t]*', end: '$',
        contains: [
          {
            className: 'value',
            endsWithParent: true,
            keywords: 'on off true false yes no',
            contains: [hljs.QUOTE_STRING_MODE, hljs.NUMBER_MODE],
            relevance: 0
          }
        ]
      }
    ]
  };
};
},{}],57:[function(require,module,exports){
module.exports = function(hljs) {
  var KEYWORDS =
    'false synchronized int abstract float private char boolean static null if const ' +
    'for true while long throw strictfp finally protected import native final return void ' +
    'enum else break transient new catch instanceof byte super volatile case assert short ' +
    'package default double public try this switch continue throws';
  return {
    aliases: ['jsp'],
    keywords: KEYWORDS,
    illegal: /<\//,
    contains: [
      {
        className: 'javadoc',
        begin: '/\\*\\*', end: '\\*/',
        contains: [{
          className: 'javadoctag', begin: '(^|\\s)@[A-Za-z]+'
        }],
        relevance: 10
      },
      hljs.C_LINE_COMMENT_MODE,
      hljs.C_BLOCK_COMMENT_MODE,
      hljs.APOS_STRING_MODE,
      hljs.QUOTE_STRING_MODE,
      {
        beginKeywords: 'protected public private', end: /[{;=]/,
        keywords: KEYWORDS,
        contains: [
          {
            className: 'class',
            beginKeywords: 'class interface', endsWithParent: true, excludeEnd: true,
            illegal: /[:"<>]/,
            contains: [
              {
                beginKeywords: 'extends implements',
                relevance: 10
              },
              hljs.UNDERSCORE_TITLE_MODE
            ]
          },
          {
            begin: hljs.UNDERSCORE_IDENT_RE + '\\s*\\(', returnBegin: true,
            contains: [
              hljs.UNDERSCORE_TITLE_MODE
            ]
          }
        ]
      },
      hljs.C_NUMBER_MODE,
      {
        className: 'annotation', begin: '@[A-Za-z]+'
      }
    ]
  };
};
},{}],58:[function(require,module,exports){
module.exports = function(hljs) {
  return {
    aliases: ['js'],
    keywords: {
      keyword:
        'in if for while finally var new function do return void else break catch ' +
        'instanceof with throw case default try this switch continue typeof delete ' +
        'let yield const class',
      literal:
        'true false null undefined NaN Infinity',
      built_in:
        'eval isFinite isNaN parseFloat parseInt decodeURI decodeURIComponent ' +
        'encodeURI encodeURIComponent escape unescape Object Function Boolean Error ' +
        'EvalError InternalError RangeError ReferenceError StopIteration SyntaxError ' +
        'TypeError URIError Number Math Date String RegExp Array Float32Array ' +
        'Float64Array Int16Array Int32Array Int8Array Uint16Array Uint32Array ' +
        'Uint8Array Uint8ClampedArray ArrayBuffer DataView JSON Intl arguments require ' +
        'module console window document'
    },
    contains: [
      {
        className: 'pi',
        begin: /^\s*('|")use strict('|")/,
        relevance: 10
      },
      hljs.APOS_STRING_MODE,
      hljs.QUOTE_STRING_MODE,
      hljs.C_LINE_COMMENT_MODE,
      hljs.C_BLOCK_COMMENT_MODE,
      hljs.C_NUMBER_MODE,
      { // "value" container
        begin: '(' + hljs.RE_STARTERS_RE + '|\\b(case|return|throw)\\b)\\s*',
        keywords: 'return throw case',
        contains: [
          hljs.C_LINE_COMMENT_MODE,
          hljs.C_BLOCK_COMMENT_MODE,
          hljs.REGEXP_MODE,
          { // E4X
            begin: /</, end: />;/,
            relevance: 0,
            subLanguage: 'xml'
          }
        ],
        relevance: 0
      },
      {
        className: 'function',
        beginKeywords: 'function', end: /\{/, excludeEnd: true,
        contains: [
          hljs.inherit(hljs.TITLE_MODE, {begin: /[A-Za-z$_][0-9A-Za-z$_]*/}),
          {
            className: 'params',
            begin: /\(/, end: /\)/,
            contains: [
              hljs.C_LINE_COMMENT_MODE,
              hljs.C_BLOCK_COMMENT_MODE
            ],
            illegal: /["'\(]/
          }
        ],
        illegal: /\[|%/
      },
      {
        begin: /\$[(.]/ // relevance booster for a pattern common to JS libs: `$(something)` and `$.something`
      },
      {
        begin: '\\.' + hljs.IDENT_RE, relevance: 0 // hack: prevents detection of keywords after dots
      }
    ]
  };
};
},{}],59:[function(require,module,exports){
module.exports = function(hljs) {
  var LITERALS = {literal: 'true false null'};
  var TYPES = [
    hljs.QUOTE_STRING_MODE,
    hljs.C_NUMBER_MODE
  ];
  var VALUE_CONTAINER = {
    className: 'value',
    end: ',', endsWithParent: true, excludeEnd: true,
    contains: TYPES,
    keywords: LITERALS
  };
  var OBJECT = {
    begin: '{', end: '}',
    contains: [
      {
        className: 'attribute',
        begin: '\\s*"', end: '"\\s*:\\s*', excludeBegin: true, excludeEnd: true,
        contains: [hljs.BACKSLASH_ESCAPE],
        illegal: '\\n',
        starts: VALUE_CONTAINER
      }
    ],
    illegal: '\\S'
  };
  var ARRAY = {
    begin: '\\[', end: '\\]',
    contains: [hljs.inherit(VALUE_CONTAINER, {className: null})], // inherit is also a workaround for a bug that makes shared modes with endsWithParent compile only the ending of one of the parents
    illegal: '\\S'
  };
  TYPES.splice(TYPES.length, 0, OBJECT, ARRAY);
  return {
    contains: TYPES,
    keywords: LITERALS,
    illegal: '\\S'
  };
};
},{}],60:[function(require,module,exports){
module.exports = function(hljs) {
  var LISP_IDENT_RE = '[a-zA-Z_\\-\\+\\*\\/\\<\\=\\>\\&\\#][a-zA-Z0-9_\\-\\+\\*\\/\\<\\=\\>\\&\\#!]*';
  var LISP_SIMPLE_NUMBER_RE = '(\\-|\\+)?\\d+(\\.\\d+|\\/\\d+)?((d|e|f|l|s)(\\+|\\-)?\\d+)?';
  var SHEBANG = {
    className: 'shebang',
    begin: '^#!', end: '$'
  };
  var LITERAL = {
    className: 'literal',
    begin: '\\b(t{1}|nil)\\b'
  };
  var NUMBER = {
    className: 'number',
    variants: [
      {begin: LISP_SIMPLE_NUMBER_RE, relevance: 0},
      {begin: '#b[0-1]+(/[0-1]+)?'},
      {begin: '#o[0-7]+(/[0-7]+)?'},
      {begin: '#x[0-9a-f]+(/[0-9a-f]+)?'},
      {begin: '#c\\(' + LISP_SIMPLE_NUMBER_RE + ' +' + LISP_SIMPLE_NUMBER_RE, end: '\\)'}
    ]
  };
  var STRING = hljs.inherit(hljs.QUOTE_STRING_MODE, {illegal: null});
  var COMMENT = {
    className: 'comment',
    begin: ';', end: '$'
  };
  var VARIABLE = {
    className: 'variable',
    begin: '\\*', end: '\\*'
  };
  var KEYWORD = {
    className: 'keyword',
    begin: '[:&]' + LISP_IDENT_RE
  };
  var QUOTED_LIST = {
    begin: '\\(', end: '\\)',
    contains: ['self', LITERAL, STRING, NUMBER]
  };
  var QUOTED = {
    className: 'quoted',
    contains: [NUMBER, STRING, VARIABLE, KEYWORD, QUOTED_LIST],
    variants: [
      {
        begin: '[\'`]\\(', end: '\\)'
      },
      {
        begin: '\\(quote ', end: '\\)',
        keywords: {title: 'quote'}
      }
    ]
  };
  var LIST = {
    className: 'list',
    begin: '\\(', end: '\\)'
  };
  var BODY = {
    endsWithParent: true,
    relevance: 0
  };
  LIST.contains = [{className: 'title', begin: LISP_IDENT_RE}, BODY];
  BODY.contains = [QUOTED, LIST, LITERAL, NUMBER, STRING, COMMENT, VARIABLE, KEYWORD];

  return {
    illegal: /\S/,
    contains: [
      NUMBER,
      SHEBANG,
      LITERAL,
      STRING,
      COMMENT,
      QUOTED,
      LIST
    ]
  };
};
},{}],61:[function(require,module,exports){
module.exports = function(hljs) {
  var OPENING_LONG_BRACKET = '\\[=*\\[';
  var CLOSING_LONG_BRACKET = '\\]=*\\]';
  var LONG_BRACKETS = {
    begin: OPENING_LONG_BRACKET, end: CLOSING_LONG_BRACKET,
    contains: ['self']
  };
  var COMMENTS = [
    {
      className: 'comment',
      begin: '--(?!' + OPENING_LONG_BRACKET + ')', end: '$'
    },
    {
      className: 'comment',
      begin: '--' + OPENING_LONG_BRACKET, end: CLOSING_LONG_BRACKET,
      contains: [LONG_BRACKETS],
      relevance: 10
    }
  ]
  return {
    lexemes: hljs.UNDERSCORE_IDENT_RE,
    keywords: {
      keyword:
        'and break do else elseif end false for if in local nil not or repeat return then ' +
        'true until while',
      built_in:
        '_G _VERSION assert collectgarbage dofile error getfenv getmetatable ipairs load ' +
        'loadfile loadstring module next pairs pcall print rawequal rawget rawset require ' +
        'select setfenv setmetatable tonumber tostring type unpack xpcall coroutine debug ' +
        'io math os package string table'
    },
    contains: COMMENTS.concat([
      {
        className: 'function',
        beginKeywords: 'function', end: '\\)',
        contains: [
          hljs.inherit(hljs.TITLE_MODE, {begin: '([_a-zA-Z]\\w*\\.)*([_a-zA-Z]\\w*:)?[_a-zA-Z]\\w*'}),
          {
            className: 'params',
            begin: '\\(', endsWithParent: true,
            contains: COMMENTS
          }
        ].concat(COMMENTS)
      },
      hljs.C_NUMBER_MODE,
      hljs.APOS_STRING_MODE,
      hljs.QUOTE_STRING_MODE,
      {
        className: 'string',
        begin: OPENING_LONG_BRACKET, end: CLOSING_LONG_BRACKET,
        contains: [LONG_BRACKETS],
        relevance: 10
      }
    ])
  };
};
},{}],62:[function(require,module,exports){
module.exports = function(hljs) {
  var VARIABLE = {
    className: 'variable',
    begin: /\$\(/, end: /\)/,
    contains: [hljs.BACKSLASH_ESCAPE]
  };
  return {
    aliases: ['mk', 'mak'],
    contains: [
      hljs.HASH_COMMENT_MODE,
      {
        begin: /^\w+\s*\W*=/, returnBegin: true,
        relevance: 0,
        starts: {
          className: 'constant',
          end: /\s*\W*=/, excludeEnd: true,
          starts: {
            end: /$/,
            relevance: 0,
            contains: [
              VARIABLE
            ]
          }
        }
      },
      {
        className: 'title',
        begin: /^[\w]+:\s*$/
      },
      {
        className: 'phony',
        begin: /^\.PHONY:/, end: /$/,
        keywords: '.PHONY', lexemes: /[\.\w]+/
      },
      {
        begin: /^\t+/, end: /$/,
        contains: [
          hljs.QUOTE_STRING_MODE,
          VARIABLE
        ]
      }
    ]
  };
};
},{}],63:[function(require,module,exports){
module.exports = function(hljs) {
  return {
    aliases: ['md', 'mkdown', 'mkd'],
    contains: [
      // highlight headers
      {
        className: 'header',
        variants: [
          { begin: '^#{1,6}', end: '$' },
          { begin: '^.+?\\n[=-]{2,}$' }
        ]
      },
      // inline html
      {
        begin: '<', end: '>',
        subLanguage: 'xml',
        relevance: 0
      },
      // lists (indicators only)
      {
        className: 'bullet',
        begin: '^([*+-]|(\\d+\\.))\\s+'
      },
      // strong segments
      {
        className: 'strong',
        begin: '[*_]{2}.+?[*_]{2}'
      },
      // emphasis segments
      {
        className: 'emphasis',
        variants: [
          { begin: '\\*.+?\\*' },
          { begin: '_.+?_'
          , relevance: 0
          }
        ]
      },
      // blockquotes
      {
        className: 'blockquote',
        begin: '^>\\s+', end: '$'
      },
      // code snippets
      {
        className: 'code',
        variants: [
          { begin: '`.+?`' },
          { begin: '^( {4}|\t)', end: '$'
          , relevance: 0
          }
        ]
      },
      // horizontal rules
      {
        className: 'horizontal_rule',
        begin: '^[-\\*]{3,}', end: '$'
      },
      // using links - title and link
      {
        begin: '\\[.+?\\][\\(\\[].+?[\\)\\]]',
        returnBegin: true,
        contains: [
          {
            className: 'link_label',
            begin: '\\[', end: '\\]',
            excludeBegin: true,
            returnEnd: true,
            relevance: 0
          },
          {
            className: 'link_url',
            begin: '\\]\\(', end: '\\)',
            excludeBegin: true, excludeEnd: true
          },
          {
            className: 'link_reference',
            begin: '\\]\\[', end: '\\]',
            excludeBegin: true, excludeEnd: true
          }
        ],
        relevance: 10
      },
      {
        begin: '^\\[\.+\\]:', end: '$',
        returnBegin: true,
        contains: [
          {
            className: 'link_reference',
            begin: '\\[', end: '\\]',
            excludeBegin: true, excludeEnd: true
          },
          {
            className: 'link_url',
            begin: '\\s', end: '$'
          }
        ]
      }
    ]
  };
};
},{}],64:[function(require,module,exports){
module.exports = function(hljs) {
  var VAR = {
    className: 'variable',
    variants: [
      {begin: /\$\d+/},
      {begin: /\$\{/, end: /}/},
      {begin: '[\\$\\@]' + hljs.UNDERSCORE_IDENT_RE}
    ]
  };
  var DEFAULT = {
    endsWithParent: true,
    lexemes: '[a-z/_]+',
    keywords: {
      built_in:
        'on off yes no true false none blocked debug info notice warn error crit ' +
        'select break last permanent redirect kqueue rtsig epoll poll /dev/poll'
    },
    relevance: 0,
    illegal: '=>',
    contains: [
      hljs.HASH_COMMENT_MODE,
      {
        className: 'string',
        contains: [hljs.BACKSLASH_ESCAPE, VAR],
        variants: [
          {begin: /"/, end: /"/},
          {begin: /'/, end: /'/}
        ]
      },
      {
        className: 'url',
        begin: '([a-z]+):/', end: '\\s', endsWithParent: true, excludeEnd: true
      },
      {
        className: 'regexp',
        contains: [hljs.BACKSLASH_ESCAPE, VAR],
        variants: [
          {begin: "\\s\\^", end: "\\s|{|;", returnEnd: true},
          // regexp locations (~, ~*)
          {begin: "~\\*?\\s+", end: "\\s|{|;", returnEnd: true},
          // *.example.com
          {begin: "\\*(\\.[a-z\\-]+)+"},
          // sub.example.*
          {begin: "([a-z\\-]+\\.)+\\*"}
        ]
      },
      // IP
      {
        className: 'number',
        begin: '\\b\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}(:\\d{1,5})?\\b'
      },
      // units
      {
        className: 'number',
        begin: '\\b\\d+[kKmMgGdshdwy]*\\b',
        relevance: 0
      },
      VAR
    ]
  };

  return {
    aliases: ['nginxconf'],
    contains: [
      hljs.HASH_COMMENT_MODE,
      {
        begin: hljs.UNDERSCORE_IDENT_RE + '\\s', end: ';|{', returnBegin: true,
        contains: [
          {
            className: 'title',
            begin: hljs.UNDERSCORE_IDENT_RE,
            starts: DEFAULT
          }
        ],
        relevance: 0
      }
    ],
    illegal: '[^\\s\\}]'
  };
};
},{}],65:[function(require,module,exports){
module.exports = function(hljs) {
  var OBJC_KEYWORDS = {
    keyword:
      'int float while char export sizeof typedef const struct for union ' +
      'unsigned long volatile static bool mutable if do return goto void ' +
      'enum else break extern asm case short default double register explicit ' +
      'signed typename this switch continue wchar_t inline readonly assign ' +
      'self synchronized id ' +
      'nonatomic super unichar IBOutlet IBAction strong weak ' +
      '@private @protected @public @try @property @end @throw @catch @finally ' +
      '@synthesize @dynamic @selector @optional @required',
    literal:
    	'false true FALSE TRUE nil YES NO NULL',
    built_in:
      'NSString NSDictionary CGRect CGPoint UIButton UILabel UITextView UIWebView MKMapView ' +
      'UISegmentedControl NSObject UITableViewDelegate UITableViewDataSource NSThread ' +
      'UIActivityIndicator UITabbar UIToolBar UIBarButtonItem UIImageView NSAutoreleasePool ' +
      'UITableView BOOL NSInteger CGFloat NSException NSLog NSMutableString NSMutableArray ' +
      'NSMutableDictionary NSURL NSIndexPath CGSize UITableViewCell UIView UIViewController ' +
      'UINavigationBar UINavigationController UITabBarController UIPopoverController ' +
      'UIPopoverControllerDelegate UIImage NSNumber UISearchBar NSFetchedResultsController ' +
      'NSFetchedResultsChangeType UIScrollView UIScrollViewDelegate UIEdgeInsets UIColor ' +
      'UIFont UIApplication NSNotFound NSNotificationCenter NSNotification ' +
      'UILocalNotification NSBundle NSFileManager NSTimeInterval NSDate NSCalendar ' +
      'NSUserDefaults UIWindow NSRange NSArray NSError NSURLRequest NSURLConnection ' +
      'UIInterfaceOrientation MPMoviePlayerController dispatch_once_t ' +
      'dispatch_queue_t dispatch_sync dispatch_async dispatch_once'
  };
  var LEXEMES = /[a-zA-Z@][a-zA-Z0-9_]*/;
  var CLASS_KEYWORDS = '@interface @class @protocol @implementation';
  return {
    aliases: ['m', 'mm', 'objc', 'obj-c'],
    keywords: OBJC_KEYWORDS, lexemes: LEXEMES,
    illegal: '</',
    contains: [
      hljs.C_LINE_COMMENT_MODE,
      hljs.C_BLOCK_COMMENT_MODE,
      hljs.C_NUMBER_MODE,
      hljs.QUOTE_STRING_MODE,
      {
        className: 'string',
        begin: '\'',
        end: '[^\\\\]\'',
        illegal: '[^\\\\][^\']'
      },

      {
        className: 'preprocessor',
        begin: '#import',
        end: '$',
        contains: [
        {
          className: 'title',
          begin: '\"',
          end: '\"'
        },
        {
          className: 'title',
          begin: '<',
          end: '>'
        }
        ]
      },
      {
        className: 'preprocessor',
        begin: '#',
        end: '$'
      },
      {
        className: 'class',
        begin: '(' + CLASS_KEYWORDS.split(' ').join('|') + ')\\b', end: '({|$)', excludeEnd: true,
        keywords: CLASS_KEYWORDS, lexemes: LEXEMES,
        contains: [
          hljs.UNDERSCORE_TITLE_MODE
        ]
      },
      {
        className: 'variable',
        begin: '\\.'+hljs.UNDERSCORE_IDENT_RE,
        relevance: 0
      }
    ]
  };
};
},{}],66:[function(require,module,exports){
module.exports = function(hljs) {
  var PERL_KEYWORDS = 'getpwent getservent quotemeta msgrcv scalar kill dbmclose undef lc ' +
    'ma syswrite tr send umask sysopen shmwrite vec qx utime local oct semctl localtime ' +
    'readpipe do return format read sprintf dbmopen pop getpgrp not getpwnam rewinddir qq' +
    'fileno qw endprotoent wait sethostent bless s|0 opendir continue each sleep endgrent ' +
    'shutdown dump chomp connect getsockname die socketpair close flock exists index shmget' +
    'sub for endpwent redo lstat msgctl setpgrp abs exit select print ref gethostbyaddr ' +
    'unshift fcntl syscall goto getnetbyaddr join gmtime symlink semget splice x|0 ' +
    'getpeername recv log setsockopt cos last reverse gethostbyname getgrnam study formline ' +
    'endhostent times chop length gethostent getnetent pack getprotoent getservbyname rand ' +
    'mkdir pos chmod y|0 substr endnetent printf next open msgsnd readdir use unlink ' +
    'getsockopt getpriority rindex wantarray hex system getservbyport endservent int chr ' +
    'untie rmdir prototype tell listen fork shmread ucfirst setprotoent else sysseek link ' +
    'getgrgid shmctl waitpid unpack getnetbyname reset chdir grep split require caller ' +
    'lcfirst until warn while values shift telldir getpwuid my getprotobynumber delete and ' +
    'sort uc defined srand accept package seekdir getprotobyname semop our rename seek if q|0 ' +
    'chroot sysread setpwent no crypt getc chown sqrt write setnetent setpriority foreach ' +
    'tie sin msgget map stat getlogin unless elsif truncate exec keys glob tied closedir' +
    'ioctl socket readlink eval xor readline binmode setservent eof ord bind alarm pipe ' +
    'atan2 getgrent exp time push setgrent gt lt or ne m|0 break given say state when';
  var SUBST = {
    className: 'subst',
    begin: '[$@]\\{', end: '\\}',
    keywords: PERL_KEYWORDS
  };
  var METHOD = {
    begin: '->{', end: '}'
    // contains defined later
  };
  var VAR = {
    className: 'variable',
    variants: [
      {begin: /\$\d/},
      {begin: /[\$\%\@\*](\^\w\b|#\w+(\:\:\w+)*|{\w+}|\w+(\:\:\w*)*)/},
      {begin: /[\$\%\@\*][^\s\w{]/, relevance: 0}
    ]
  };
  var COMMENT = {
    className: 'comment',
    begin: '^(__END__|__DATA__)', end: '\\n$',
    relevance: 5
  };
  var STRING_CONTAINS = [hljs.BACKSLASH_ESCAPE, SUBST, VAR];
  var PERL_DEFAULT_CONTAINS = [
    VAR,
    hljs.HASH_COMMENT_MODE,
    COMMENT,
    {
      className: 'comment',
      begin: '^\\=\\w', end: '\\=cut', endsWithParent: true
    },
    METHOD,
    {
      className: 'string',
      contains: STRING_CONTAINS,
      variants: [
        {
          begin: 'q[qwxr]?\\s*\\(', end: '\\)',
          relevance: 5
        },
        {
          begin: 'q[qwxr]?\\s*\\[', end: '\\]',
          relevance: 5
        },
        {
          begin: 'q[qwxr]?\\s*\\{', end: '\\}',
          relevance: 5
        },
        {
          begin: 'q[qwxr]?\\s*\\|', end: '\\|',
          relevance: 5
        },
        {
          begin: 'q[qwxr]?\\s*\\<', end: '\\>',
          relevance: 5
        },
        {
          begin: 'qw\\s+q', end: 'q',
          relevance: 5
        },
        {
          begin: '\'', end: '\'',
          contains: [hljs.BACKSLASH_ESCAPE]
        },
        {
          begin: '"', end: '"'
        },
        {
          begin: '`', end: '`',
          contains: [hljs.BACKSLASH_ESCAPE]
        },
        {
          begin: '{\\w+}',
          contains: [],
          relevance: 0
        },
        {
          begin: '\-?\\w+\\s*\\=\\>',
          contains: [],
          relevance: 0
        }
      ]
    },
    {
      className: 'number',
      begin: '(\\b0[0-7_]+)|(\\b0x[0-9a-fA-F_]+)|(\\b[1-9][0-9_]*(\\.[0-9_]+)?)|[0_]\\b',
      relevance: 0
    },
    { // regexp container
      begin: '(\\/\\/|' + hljs.RE_STARTERS_RE + '|\\b(split|return|print|reverse|grep)\\b)\\s*',
      keywords: 'split return print reverse grep',
      relevance: 0,
      contains: [
        hljs.HASH_COMMENT_MODE,
        COMMENT,
        {
          className: 'regexp',
          begin: '(s|tr|y)/(\\\\.|[^/])*/(\\\\.|[^/])*/[a-z]*',
          relevance: 10
        },
        {
          className: 'regexp',
          begin: '(m|qr)?/', end: '/[a-z]*',
          contains: [hljs.BACKSLASH_ESCAPE],
          relevance: 0 // allows empty "//" which is a common comment delimiter in other languages
        }
      ]
    },
    {
      className: 'sub',
      beginKeywords: 'sub', end: '(\\s*\\(.*?\\))?[;{]',
      relevance: 5
    },
    {
      className: 'operator',
      begin: '-\\w\\b',
      relevance: 0
    }
  ];
  SUBST.contains = PERL_DEFAULT_CONTAINS;
  METHOD.contains = PERL_DEFAULT_CONTAINS;

  return {
    aliases: ['pl'],
    keywords: PERL_KEYWORDS,
    contains: PERL_DEFAULT_CONTAINS
  };
};
},{}],67:[function(require,module,exports){
module.exports = function(hljs) {
  var VARIABLE = {
    className: 'variable', begin: '\\$+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*'
  };
  var PREPROCESSOR = {
    className: 'preprocessor', begin: /<\?(php)?|\?>/
  };
  var STRING = {
    className: 'string',
    contains: [hljs.BACKSLASH_ESCAPE, PREPROCESSOR],
    variants: [
      {
        begin: 'b"', end: '"'
      },
      {
        begin: 'b\'', end: '\''
      },
      hljs.inherit(hljs.APOS_STRING_MODE, {illegal: null}),
      hljs.inherit(hljs.QUOTE_STRING_MODE, {illegal: null})
    ]
  };
  var NUMBER = {variants: [hljs.BINARY_NUMBER_MODE, hljs.C_NUMBER_MODE]};
  return {
    aliases: ['php3', 'php4', 'php5', 'php6'],
    case_insensitive: true,
    keywords:
      'and include_once list abstract global private echo interface as static endswitch ' +
      'array null if endwhile or const for endforeach self var while isset public ' +
      'protected exit foreach throw elseif include __FILE__ empty require_once do xor ' +
      'return parent clone use __CLASS__ __LINE__ else break print eval new ' +
      'catch __METHOD__ case exception default die require __FUNCTION__ ' +
      'enddeclare final try switch continue endfor endif declare unset true false ' +
      'trait goto instanceof insteadof __DIR__ __NAMESPACE__ ' +
      'yield finally',
    contains: [
      hljs.C_LINE_COMMENT_MODE,
      hljs.HASH_COMMENT_MODE,
      {
        className: 'comment',
        begin: '/\\*', end: '\\*/',
        contains: [
          {
            className: 'phpdoc',
            begin: '\\s@[A-Za-z]+'
          },
          PREPROCESSOR
        ]
      },
      {
          className: 'comment',
          begin: '__halt_compiler.+?;', endsWithParent: true,
          keywords: '__halt_compiler', lexemes: hljs.UNDERSCORE_IDENT_RE
      },
      {
        className: 'string',
        begin: '<<<[\'"]?\\w+[\'"]?$', end: '^\\w+;',
        contains: [hljs.BACKSLASH_ESCAPE]
      },
      PREPROCESSOR,
      VARIABLE,
      {
        className: 'function',
        beginKeywords: 'function', end: /[;{]/, excludeEnd: true,
        illegal: '\\$|\\[|%',
        contains: [
          hljs.UNDERSCORE_TITLE_MODE,
          {
            className: 'params',
            begin: '\\(', end: '\\)',
            contains: [
              'self',
              VARIABLE,
              hljs.C_BLOCK_COMMENT_MODE,
              STRING,
              NUMBER
            ]
          }
        ]
      },
      {
        className: 'class',
        beginKeywords: 'class interface', end: '{', excludeEnd: true,
        illegal: /[:\(\$"]/,
        contains: [
          {
            beginKeywords: 'extends implements',
            relevance: 10
          },
          hljs.UNDERSCORE_TITLE_MODE
        ]
      },
      {
        beginKeywords: 'namespace', end: ';',
        illegal: /[\.']/,
        contains: [hljs.UNDERSCORE_TITLE_MODE]
      },
      {
        beginKeywords: 'use', end: ';',
        contains: [hljs.UNDERSCORE_TITLE_MODE]
      },
      {
        begin: '=>' // No markup, just a relevance booster
      },
      STRING,
      NUMBER
    ]
  };
};
},{}],68:[function(require,module,exports){
module.exports = function(hljs) {
  var PROMPT = {
    className: 'prompt',  begin: /^(>>>|\.\.\.) /
  };
  var STRING = {
    className: 'string',
    contains: [hljs.BACKSLASH_ESCAPE],
    variants: [
      {
        begin: /(u|b)?r?'''/, end: /'''/,
        contains: [PROMPT],
        relevance: 10
      },
      {
        begin: /(u|b)?r?"""/, end: /"""/,
        contains: [PROMPT],
        relevance: 10
      },
      {
        begin: /(u|r|ur)'/, end: /'/,
        relevance: 10
      },
      {
        begin: /(u|r|ur)"/, end: /"/,
        relevance: 10
      },
      {
        begin: /(b|br)'/, end: /'/
      },
      {
        begin: /(b|br)"/, end: /"/
      },
      hljs.APOS_STRING_MODE,
      hljs.QUOTE_STRING_MODE
    ]
  };
  var NUMBER = {
    className: 'number', relevance: 0,
    variants: [
      {begin: hljs.BINARY_NUMBER_RE + '[lLjJ]?'},
      {begin: '\\b(0o[0-7]+)[lLjJ]?'},
      {begin: hljs.C_NUMBER_RE + '[lLjJ]?'}
    ]
  };
  var PARAMS = {
    className: 'params',
    begin: /\(/, end: /\)/,
    contains: ['self', PROMPT, NUMBER, STRING]
  };
  var FUNC_CLASS_PROTO = {
    end: /:/,
    illegal: /[${=;\n]/,
    contains: [hljs.UNDERSCORE_TITLE_MODE, PARAMS]
  };

  return {
    aliases: ['py', 'gyp'],
    keywords: {
      keyword:
        'and elif is global as in if from raise for except finally print import pass return ' +
        'exec else break not with class assert yield try while continue del or def lambda ' +
        'nonlocal|10 None True False',
      built_in:
        'Ellipsis NotImplemented'
    },
    illegal: /(<\/|->|\?)/,
    contains: [
      PROMPT,
      NUMBER,
      STRING,
      hljs.HASH_COMMENT_MODE,
      hljs.inherit(FUNC_CLASS_PROTO, {className: 'function', beginKeywords: 'def', relevance: 10}),
      hljs.inherit(FUNC_CLASS_PROTO, {className: 'class', beginKeywords: 'class'}),
      {
        className: 'decorator',
        begin: /@/, end: /$/
      },
      {
        begin: /\b(print|exec)\(/ // don’t highlight keywords-turned-functions in Python 3
      }
    ]
  };
};
},{}],69:[function(require,module,exports){
module.exports = function(hljs) {
  var IDENT_RE = '([a-zA-Z]|\\.[a-zA-Z.])[a-zA-Z0-9._]*';

  return {
    contains: [
      hljs.HASH_COMMENT_MODE,
      {
        begin: IDENT_RE,
        lexemes: IDENT_RE,
        keywords: {
          keyword:
            'function if in break next repeat else for return switch while try tryCatch|10 ' +
            'stop warning require library attach detach source setMethod setGeneric ' +
            'setGroupGeneric setClass ...|10',
          literal:
            'NULL NA TRUE FALSE T F Inf NaN NA_integer_|10 NA_real_|10 NA_character_|10 ' +
            'NA_complex_|10'
        },
        relevance: 0
      },
      {
        // hex value
        className: 'number',
        begin: "0[xX][0-9a-fA-F]+[Li]?\\b",
        relevance: 0
      },
      {
        // explicit integer
        className: 'number',
        begin: "\\d+(?:[eE][+\\-]?\\d*)?L\\b",
        relevance: 0
      },
      {
        // number with trailing decimal
        className: 'number',
        begin: "\\d+\\.(?!\\d)(?:i\\b)?",
        relevance: 0
      },
      {
        // number
        className: 'number',
        begin: "\\d+(?:\\.\\d*)?(?:[eE][+\\-]?\\d*)?i?\\b",
        relevance: 0
      },
      {
        // number with leading decimal
        className: 'number',
        begin: "\\.\\d+(?:[eE][+\\-]?\\d*)?i?\\b",
        relevance: 0
      },

      {
        // escaped identifier
        begin: '`',
        end: '`',
        relevance: 0
      },

      {
        className: 'string',
        contains: [hljs.BACKSLASH_ESCAPE],
        variants: [
          {begin: '"', end: '"'},
          {begin: "'", end: "'"}
        ]
      }
    ]
  };
};
},{}],70:[function(require,module,exports){
module.exports = function(hljs) {
  var RUBY_METHOD_RE = '[a-zA-Z_]\\w*[!?=]?|[-+~]\\@|<<|>>|=~|===?|<=>|[<>]=?|\\*\\*|[-/+%^&*~`|]|\\[\\]=?';
  var RUBY_KEYWORDS =
    'and false then defined module in return redo if BEGIN retry end for true self when ' +
    'next until do begin unless END rescue nil else break undef not super class case ' +
    'require yield alias while ensure elsif or include attr_reader attr_writer attr_accessor';
  var YARDOCTAG = {
    className: 'yardoctag',
    begin: '@[A-Za-z]+'
  };
  var COMMENT = {
    className: 'comment',
    variants: [
      {
        begin: '#', end: '$',
        contains: [YARDOCTAG]
      },
      {
        begin: '^\\=begin', end: '^\\=end',
        contains: [YARDOCTAG],
        relevance: 10
      },
      {
        begin: '^__END__', end: '\\n$'
      }
    ]
  };
  var SUBST = {
    className: 'subst',
    begin: '#\\{', end: '}',
    keywords: RUBY_KEYWORDS
  };
  var STRING = {
    className: 'string',
    contains: [hljs.BACKSLASH_ESCAPE, SUBST],
    variants: [
      {begin: /'/, end: /'/},
      {begin: /"/, end: /"/},
      {begin: '%[qw]?\\(', end: '\\)'},
      {begin: '%[qw]?\\[', end: '\\]'},
      {begin: '%[qw]?{', end: '}'},
      {
        begin: '%[qw]?<', end: '>',
        relevance: 10
      },
      {
        begin: '%[qw]?/', end: '/',
        relevance: 10
      },
      {
        begin: '%[qw]?%', end: '%',
        relevance: 10
      },
      {
        begin: '%[qw]?-', end: '-',
        relevance: 10
      },
      {
        begin: '%[qw]?\\|', end: '\\|',
        relevance: 10
      },
      {
        // \B in the beginning suppresses recognition of ?-sequences where ?
        // is the last character of a preceding identifier, as in: `func?4`
        begin: /\B\?(\\\d{1,3}|\\x[A-Fa-f0-9]{1,2}|\\u[A-Fa-f0-9]{4}|\\?\S)\b/
      }
    ]
  };
  var PARAMS = {
    className: 'params',
    begin: '\\(', end: '\\)',
    keywords: RUBY_KEYWORDS
  };

  var RUBY_DEFAULT_CONTAINS = [
    STRING,
    COMMENT,
    {
      className: 'class',
      beginKeywords: 'class module', end: '$|;',
      illegal: /=/,
      contains: [
        hljs.inherit(hljs.TITLE_MODE, {begin: '[A-Za-z_]\\w*(::\\w+)*(\\?|\\!)?'}),
        {
          className: 'inheritance',
          begin: '<\\s*',
          contains: [{
            className: 'parent',
            begin: '(' + hljs.IDENT_RE + '::)?' + hljs.IDENT_RE
          }]
        },
        COMMENT
      ]
    },
    {
      className: 'function',
      beginKeywords: 'def', end: ' |$|;',
      relevance: 0,
      contains: [
        hljs.inherit(hljs.TITLE_MODE, {begin: RUBY_METHOD_RE}),
        PARAMS,
        COMMENT
      ]
    },
    {
      className: 'constant',
      begin: '(::)?(\\b[A-Z]\\w*(::)?)+',
      relevance: 0
    },
    {
      className: 'symbol',
      begin: ':',
      contains: [STRING, {begin: RUBY_METHOD_RE}],
      relevance: 0
    },
    {
      className: 'symbol',
      begin: hljs.UNDERSCORE_IDENT_RE + '(\\!|\\?)?:',
      relevance: 0
    },
    {
      className: 'number',
      begin: '(\\b0[0-7_]+)|(\\b0x[0-9a-fA-F_]+)|(\\b[1-9][0-9_]*(\\.[0-9_]+)?)|[0_]\\b',
      relevance: 0
    },
    {
      className: 'variable',
      begin: '(\\$\\W)|((\\$|\\@\\@?)(\\w+))'
    },
    { // regexp container
      begin: '(' + hljs.RE_STARTERS_RE + ')\\s*',
      contains: [
        COMMENT,
        {
          className: 'regexp',
          contains: [hljs.BACKSLASH_ESCAPE, SUBST],
          illegal: /\n/,
          variants: [
            {begin: '/', end: '/[a-z]*'},
            {begin: '%r{', end: '}[a-z]*'},
            {begin: '%r\\(', end: '\\)[a-z]*'},
            {begin: '%r!', end: '![a-z]*'},
            {begin: '%r\\[', end: '\\][a-z]*'}
          ]
        }
      ],
      relevance: 0
    }
  ];
  SUBST.contains = RUBY_DEFAULT_CONTAINS;
  PARAMS.contains = RUBY_DEFAULT_CONTAINS;

  return {
    aliases: ['rb', 'gemspec', 'podspec', 'thor'],
    keywords: RUBY_KEYWORDS,
    contains: RUBY_DEFAULT_CONTAINS
  };
};
},{}],71:[function(require,module,exports){
module.exports = function(hljs) {
  return {
    aliases: ['rs'],
    keywords:
      'assert bool break char check claim comm const cont copy dir do drop ' +
      'else enum extern export f32 f64 fail false float fn for i16 i32 i64 i8 ' +
      'if impl int let log loop match mod move mut priv pub pure ref return ' +
      'self static str struct task true trait type u16 u32 u64 u8 uint unsafe ' +
      'use vec while',
    illegal: '</',
    contains: [
      hljs.C_LINE_COMMENT_MODE,
      hljs.C_BLOCK_COMMENT_MODE,
      hljs.inherit(hljs.QUOTE_STRING_MODE, {illegal: null}),
      hljs.APOS_STRING_MODE,
      {
        className: 'number',
        begin: '\\b(0[xb][A-Za-z0-9_]+|[0-9_]+(\\.[0-9_]+)?([uif](8|16|32|64)?)?)',
        relevance: 0
      },
      {
        className: 'function',
        beginKeywords: 'fn', end: '(\\(|<)', excludeEnd: true,
        contains: [hljs.UNDERSCORE_TITLE_MODE]
      },
      {
        className: 'preprocessor',
        begin: '#\\[', end: '\\]'
      },
      {
        beginKeywords: 'type', end: '(=|<)',
        contains: [hljs.UNDERSCORE_TITLE_MODE],
        illegal: '\\S'
      },
      {
        beginKeywords: 'trait enum', end: '({|<)',
        contains: [hljs.UNDERSCORE_TITLE_MODE],
        illegal: '\\S'
      },
      {
        begin: hljs.IDENT_RE + '::'
      },
      {
        begin: '->'
      }
    ]
  };
};
},{}],72:[function(require,module,exports){
module.exports = function(hljs) {
  var ANNOTATION = {
    className: 'annotation', begin: '@[A-Za-z]+'
  };
  var STRING = {
    className: 'string',
    begin: 'u?r?"""', end: '"""',
    relevance: 10
  };
  var SYMBOL = {
    className: 'symbol',
    begin: '\'\\w[\\w\\d_]*(?!\')'
  };
  return {
    keywords:
      'type yield lazy override def with val var false true sealed abstract private trait ' +
      'object null if for while throw finally protected extends import final return else ' +
      'break new catch super class case package default try this match continue throws',
    contains: [
      {
        className: 'javadoc',
        begin: '/\\*\\*', end: '\\*/',
        contains: [{
          className: 'javadoctag',
          begin: '@[A-Za-z]+'
        }],
        relevance: 10
      },
      hljs.C_LINE_COMMENT_MODE, hljs.C_BLOCK_COMMENT_MODE,
      STRING, hljs.QUOTE_STRING_MODE,
      SYMBOL,
      {
        className: 'class',
        begin: '((case )?class |object |trait )', // beginKeywords won't work because a single "case" shouldn't start this mode
        end: '({|$)', excludeEnd: true,
        illegal: ':',
        keywords: 'case class trait object',
        contains: [
          {
            beginKeywords: 'extends with',
            relevance: 10
          },
          hljs.UNDERSCORE_TITLE_MODE,
          {
            className: 'params',
            begin: '\\(', end: '\\)',
            contains: [
              hljs.QUOTE_STRING_MODE, STRING,
              ANNOTATION
            ]
          }
        ]
      },
      hljs.C_NUMBER_MODE,
      ANNOTATION
    ]
  };
};
},{}],73:[function(require,module,exports){
module.exports = function(hljs) {
  var IDENT_RE = '[a-zA-Z-][a-zA-Z0-9_-]*';
  var VARIABLE = {
    className: 'variable',
    begin: '(\\$' + IDENT_RE + ')\\b'
  };
  var FUNCTION = {
    className: 'function',
    begin: IDENT_RE + '\\(', 
    returnBegin: true,
    excludeEnd: true,
    end: '\\('
  };
  var HEXCOLOR = {
    className: 'hexcolor', begin: '#[0-9A-Fa-f]+'
  };
  var DEF_INTERNALS = {
    className: 'attribute',
    begin: '[A-Z\\_\\.\\-]+', end: ':',
    excludeEnd: true,
    illegal: '[^\\s]',
    starts: {
      className: 'value',
      endsWithParent: true, excludeEnd: true,
      contains: [
        FUNCTION,
        HEXCOLOR,
        hljs.CSS_NUMBER_MODE,
        hljs.QUOTE_STRING_MODE,
        hljs.APOS_STRING_MODE,
        hljs.C_BLOCK_COMMENT_MODE,
        {
          className: 'important', begin: '!important'
        }
      ]
    }
  };
  return {
    case_insensitive: true,
    illegal: '[=/|\']',
    contains: [
      hljs.C_LINE_COMMENT_MODE,
      hljs.C_BLOCK_COMMENT_MODE,
      FUNCTION,
      {
        className: 'id', begin: '\\#[A-Za-z0-9_-]+',
        relevance: 0
      },
      {
        className: 'class', begin: '\\.[A-Za-z0-9_-]+',
        relevance: 0
      },
      {
        className: 'attr_selector',
        begin: '\\[', end: '\\]',
        illegal: '$'
      },
      {
        className: 'tag', // begin: IDENT_RE, end: '[,|\\s]'
        begin: '\\b(a|abbr|acronym|address|area|article|aside|audio|b|base|big|blockquote|body|br|button|canvas|caption|cite|code|col|colgroup|command|datalist|dd|del|details|dfn|div|dl|dt|em|embed|fieldset|figcaption|figure|footer|form|frame|frameset|(h[1-6])|head|header|hgroup|hr|html|i|iframe|img|input|ins|kbd|keygen|label|legend|li|link|map|mark|meta|meter|nav|noframes|noscript|object|ol|optgroup|option|output|p|param|pre|progress|q|rp|rt|ruby|samp|script|section|select|small|span|strike|strong|style|sub|sup|table|tbody|td|textarea|tfoot|th|thead|time|title|tr|tt|ul|var|video)\\b',
        relevance: 0
      },
      {
        className: 'pseudo',
        begin: ':(visited|valid|root|right|required|read-write|read-only|out-range|optional|only-of-type|only-child|nth-of-type|nth-last-of-type|nth-last-child|nth-child|not|link|left|last-of-type|last-child|lang|invalid|indeterminate|in-range|hover|focus|first-of-type|first-line|first-letter|first-child|first|enabled|empty|disabled|default|checked|before|after|active)'
      },
      {
        className: 'pseudo',
        begin: '::(after|before|choices|first-letter|first-line|repeat-index|repeat-item|selection|value)'
      },
      VARIABLE,
      {
        className: 'attribute',
        begin: '\\b(z-index|word-wrap|word-spacing|word-break|width|widows|white-space|visibility|vertical-align|unicode-bidi|transition-timing-function|transition-property|transition-duration|transition-delay|transition|transform-style|transform-origin|transform|top|text-underline-position|text-transform|text-shadow|text-rendering|text-overflow|text-indent|text-decoration-style|text-decoration-line|text-decoration-color|text-decoration|text-align-last|text-align|tab-size|table-layout|right|resize|quotes|position|pointer-events|perspective-origin|perspective|page-break-inside|page-break-before|page-break-after|padding-top|padding-right|padding-left|padding-bottom|padding|overflow-y|overflow-x|overflow-wrap|overflow|outline-width|outline-style|outline-offset|outline-color|outline|orphans|order|opacity|object-position|object-fit|normal|none|nav-up|nav-right|nav-left|nav-index|nav-down|min-width|min-height|max-width|max-height|mask|marks|margin-top|margin-right|margin-left|margin-bottom|margin|list-style-type|list-style-position|list-style-image|list-style|line-height|letter-spacing|left|justify-content|initial|inherit|ime-mode|image-orientation|image-resolution|image-rendering|icon|hyphens|height|font-weight|font-variant-ligatures|font-variant|font-style|font-stretch|font-size-adjust|font-size|font-language-override|font-kerning|font-feature-settings|font-family|font|float|flex-wrap|flex-shrink|flex-grow|flex-flow|flex-direction|flex-basis|flex|filter|empty-cells|display|direction|cursor|counter-reset|counter-increment|content|column-width|column-span|column-rule-width|column-rule-style|column-rule-color|column-rule|column-gap|column-fill|column-count|columns|color|clip-path|clip|clear|caption-side|break-inside|break-before|break-after|box-sizing|box-shadow|box-decoration-break|bottom|border-width|border-top-width|border-top-style|border-top-right-radius|border-top-left-radius|border-top-color|border-top|border-style|border-spacing|border-right-width|border-right-style|border-right-color|border-right|border-radius|border-left-width|border-left-style|border-left-color|border-left|border-image-width|border-image-source|border-image-slice|border-image-repeat|border-image-outset|border-image|border-color|border-collapse|border-bottom-width|border-bottom-style|border-bottom-right-radius|border-bottom-left-radius|border-bottom-color|border-bottom|border|background-size|background-repeat|background-position|background-origin|background-image|background-color|background-clip|background-attachment|background|backface-visibility|auto|animation-timing-function|animation-play-state|animation-name|animation-iteration-count|animation-fill-mode|animation-duration|animation-direction|animation-delay|animation|align-self|align-items|align-content)\\b',
        illegal: '[^\\s]'
      },
      {
        className: 'value',
        begin: '\\b(whitespace|wait|w-resize|visible|vertical-text|vertical-ideographic|uppercase|upper-roman|upper-alpha|underline|transparent|top|thin|thick|text|text-top|text-bottom|tb-rl|table-header-group|table-footer-group|sw-resize|super|strict|static|square|solid|small-caps|separate|se-resize|scroll|s-resize|rtl|row-resize|ridge|right|repeat|repeat-y|repeat-x|relative|progress|pointer|overline|outside|outset|oblique|nowrap|not-allowed|normal|none|nw-resize|no-repeat|no-drop|newspaper|ne-resize|n-resize|move|middle|medium|ltr|lr-tb|lowercase|lower-roman|lower-alpha|loose|list-item|line|line-through|line-edge|lighter|left|keep-all|justify|italic|inter-word|inter-ideograph|inside|inset|inline|inline-block|inherit|inactive|ideograph-space|ideograph-parenthesis|ideograph-numeric|ideograph-alpha|horizontal|hidden|help|hand|groove|fixed|ellipsis|e-resize|double|dotted|distribute|distribute-space|distribute-letter|distribute-all-lines|disc|disabled|default|decimal|dashed|crosshair|collapse|col-resize|circle|char|center|capitalize|break-word|break-all|bottom|both|bolder|bold|block|bidi-override|below|baseline|auto|always|all-scroll|absolute|table|table-cell)\\b'
      },
      {
        className: 'value',
        begin: ':', end: ';',
        contains: [
          FUNCTION,
          VARIABLE,
          HEXCOLOR,
          hljs.CSS_NUMBER_MODE,
          hljs.QUOTE_STRING_MODE,
          hljs.APOS_STRING_MODE,
          {
            className: 'important', begin: '!important'
          }
        ]
      },
      {
        className: 'at_rule',
        begin: '@', end: '[{;]',
        keywords: 'mixin include extend for if else each while charset import debug media page content font-face namespace warn',
        contains: [
          FUNCTION,
          VARIABLE,
          hljs.QUOTE_STRING_MODE,
          hljs.APOS_STRING_MODE,
          HEXCOLOR,
          hljs.CSS_NUMBER_MODE,
          {
            className: 'preprocessor',
            begin: '\\s[A-Za-z0-9_.-]+',
            relevance: 0
          }
        ]
      }
    ]
  };
};
},{}],74:[function(require,module,exports){
module.exports = function(hljs) {
  var COMMENT_MODE = {
    className: 'comment',
    begin: '--', end: '$'
  };
  return {
    case_insensitive: true,
    illegal: /[<>]/,
    contains: [
      {
        className: 'operator',
        beginKeywords:
          'begin end start commit rollback savepoint lock alter create drop rename call '+
          'delete do handler insert load replace select truncate update set show pragma grant '+
          'merge describe use explain help declare prepare execute deallocate savepoint release '+
          'unlock purge reset change stop analyze cache flush optimize repair kill '+
          'install uninstall checksum restore check backup',
        end: /;/, endsWithParent: true,
        keywords: {
          keyword:
            'abs absolute acos action add adddate addtime aes_decrypt aes_encrypt after aggregate all allocate alter ' +
            'analyze and any are as asc ascii asin assertion at atan atan2 atn2 authorization authors avg backup ' +
            'before begin benchmark between bin binlog bit_and bit_count bit_length bit_or bit_xor both by ' +
            'cache call cascade cascaded case cast catalog ceil ceiling chain change changed char_length ' +
            'character_length charindex charset check checksum checksum_agg choose close coalesce ' +
            'coercibility collate collation collationproperty column columns columns_updated commit compress concat ' +
            'concat_ws concurrent connect connection connection_id consistent constraint constraints continue ' +
            'contributors conv convert convert_tz corresponding cos cot count count_big crc32 create cross cume_dist ' +
            'curdate current current_date current_time current_timestamp current_user cursor curtime data database ' +
            'databases datalength date_add date_format date_sub dateadd datediff datefromparts datename ' +
            'datepart datetime2fromparts datetimeoffsetfromparts day dayname dayofmonth dayofweek dayofyear ' +
            'deallocate declare decode default deferrable deferred degrees delayed delete des_decrypt ' +
            'des_encrypt des_key_file desc describe descriptor diagnostics difference disconnect distinct ' +
            'distinctrow div do domain double drop dumpfile each else elt enclosed encode encrypt end end-exec ' +
            'engine engines eomonth errors escape escaped event eventdata events except exception exec execute ' +
            'exists exp explain export_set extended external extract fast fetch field fields find_in_set ' +
            'first first_value floor flush for force foreign format found found_rows from from_base64 ' +
            'from_days from_unixtime full function get get_format get_lock getdate getutcdate global go goto grant ' +
            'grants greatest group group_concat grouping grouping_id gtid_subset gtid_subtract handler having help ' +
            'hex high_priority hosts hour ident_current ident_incr ident_seed identified identity if ifnull ignore ' +
            'iif ilike immediate in index indicator inet6_aton inet6_ntoa inet_aton inet_ntoa infile initially inner ' +
            'innodb input insert install instr intersect into is is_free_lock is_ipv4 ' +
            'is_ipv4_compat is_ipv4_mapped is_not is_not_null is_used_lock isdate isnull isolation join key kill ' +
            'language last last_day last_insert_id last_value lcase lead leading least leaves left len lenght level ' +
            'like limit lines ln load load_file local localtime localtimestamp locate lock log log10 log2 logfile ' +
            'logs low_priority lower lpad ltrim make_set makedate maketime master master_pos_wait match matched max ' +
            'md5 medium merge microsecond mid min minute mod mode module month monthname mutex name_const names ' +
            'national natural nchar next no no_write_to_binlog not now nullif nvarchar oct ' +
            'octet_length of old_password on only open optimize option optionally or ord order outer outfile output ' +
            'pad parse partial partition password patindex percent_rank percentile_cont percentile_disc period_add ' +
            'period_diff pi plugin position pow power pragma precision prepare preserve primary prior privileges ' +
            'procedure procedure_analyze processlist profile profiles public publishingservername purge quarter ' +
            'query quick quote quotename radians rand read references regexp relative relaylog release ' +
            'release_lock rename repair repeat replace replicate reset restore restrict return returns reverse ' +
            'revoke right rlike rollback rollup round row row_count rows rpad rtrim savepoint schema scroll ' +
            'sec_to_time second section select serializable server session session_user set sha sha1 sha2 share ' +
            'show sign sin size slave sleep smalldatetimefromparts snapshot some soname soundex ' +
            'sounds_like space sql sql_big_result sql_buffer_result sql_cache sql_calc_found_rows sql_no_cache ' +
            'sql_small_result sql_variant_property sqlstate sqrt square start starting status std ' +
            'stddev stddev_pop stddev_samp stdev stdevp stop str str_to_date straight_join strcmp string stuff ' +
            'subdate substr substring subtime subtring_index sum switchoffset sysdate sysdatetime sysdatetimeoffset ' +
            'system_user sysutcdatetime table tables tablespace tan temporary terminated tertiary_weights then time ' +
            'time_format time_to_sec timediff timefromparts timestamp timestampadd timestampdiff timezone_hour ' +
            'timezone_minute to to_base64 to_days to_seconds todatetimeoffset trailing transaction translation ' +
            'trigger trigger_nestlevel triggers trim truncate try_cast try_convert try_parse ucase uncompress ' +
            'uncompressed_length unhex unicode uninstall union unique unix_timestamp unknown unlock update upgrade ' +
            'upped upper usage use user user_resources using utc_date utc_time utc_timestamp uuid uuid_short ' +
            'validate_password_strength value values var var_pop var_samp variables variance varp ' +
            'version view warnings week weekday weekofyear weight_string when whenever where with work write xml ' +
            'xor year yearweek zon',
          literal:
            'true false null',
          built_in:
            'array bigint binary bit blob boolean char character date dec decimal float int integer interval number ' +
            'numeric real serial smallint varchar varying int8 serial8 text'
        },
        contains: [
          {
            className: 'string',
            begin: '\'', end: '\'',
            contains: [hljs.BACKSLASH_ESCAPE, {begin: '\'\''}]
          },
          {
            className: 'string',
            begin: '"', end: '"',
            contains: [hljs.BACKSLASH_ESCAPE, {begin: '""'}]
          },
          {
            className: 'string',
            begin: '`', end: '`',
            contains: [hljs.BACKSLASH_ESCAPE]
          },
          hljs.C_NUMBER_MODE,
          hljs.C_BLOCK_COMMENT_MODE,
          COMMENT_MODE
        ]
      },
      hljs.C_BLOCK_COMMENT_MODE,
      COMMENT_MODE
    ]
  };
};
},{}],75:[function(require,module,exports){
module.exports = function(hljs) {
  var XML_IDENT_RE = '[A-Za-z0-9\\._:-]+';
  var PHP = {
    begin: /<\?(php)?(?!\w)/, end: /\?>/,
    subLanguage: 'php', subLanguageMode: 'continuous'
  };
  var TAG_INTERNALS = {
    endsWithParent: true,
    illegal: /</,
    relevance: 0,
    contains: [
      PHP,
      {
        className: 'attribute',
        begin: XML_IDENT_RE,
        relevance: 0
      },
      {
        begin: '=',
        relevance: 0,
        contains: [
          {
            className: 'value',
            variants: [
              {begin: /"/, end: /"/},
              {begin: /'/, end: /'/},
              {begin: /[^\s\/>]+/}
            ]
          }
        ]
      }
    ]
  };
  return {
    aliases: ['html', 'xhtml', 'rss', 'atom', 'xsl', 'plist'],
    case_insensitive: true,
    contains: [
      {
        className: 'doctype',
        begin: '<!DOCTYPE', end: '>',
        relevance: 10,
        contains: [{begin: '\\[', end: '\\]'}]
      },
      {
        className: 'comment',
        begin: '<!--', end: '-->',
        relevance: 10
      },
      {
        className: 'cdata',
        begin: '<\\!\\[CDATA\\[', end: '\\]\\]>',
        relevance: 10
      },
      {
        className: 'tag',
        /*
        The lookahead pattern (?=...) ensures that 'begin' only matches
        '<style' as a single word, followed by a whitespace or an
        ending braket. The '$' is needed for the lexeme to be recognized
        by hljs.subMode() that tests lexemes outside the stream.
        */
        begin: '<style(?=\\s|>|$)', end: '>',
        keywords: {title: 'style'},
        contains: [TAG_INTERNALS],
        starts: {
          end: '</style>', returnEnd: true,
          subLanguage: 'css'
        }
      },
      {
        className: 'tag',
        // See the comment in the <style tag about the lookahead pattern
        begin: '<script(?=\\s|>|$)', end: '>',
        keywords: {title: 'script'},
        contains: [TAG_INTERNALS],
        starts: {
          end: '</script>', returnEnd: true,
          subLanguage: 'javascript'
        }
      },
      {
        begin: '<%', end: '%>',
        subLanguage: 'vbscript'
      },
      PHP,
      {
        className: 'pi',
        begin: /<\?\w+/, end: /\?>/,
        relevance: 10
      },
      {
        className: 'tag',
        begin: '</?', end: '/?>',
        contains: [
          {
            className: 'title', begin: '[^ /><]+', relevance: 0
          },
          TAG_INTERNALS
        ]
      }
    ]
  };
};
},{}],76:[function(require,module,exports){
'use strict';

var fs = require('fs');
var insertCss = require('insert-css');
var hljs = require('./lib/hljs/index');
var css = "/*\n\nOriginal style from softwaremaniacs.org (c) Ivan Sagalaev <Maniac@SoftwareManiacs.Org>\n\n*/\n\n.hljs {\n  display: block;\n  padding: 0.5em;\n  background: #f0f0f0;\n}\n\n.hljs,\n.hljs-subst,\n.hljs-tag .hljs-title,\n.lisp .hljs-title,\n.clojure .hljs-built_in,\n.nginx .hljs-title {\n  color: black;\n}\n\n.hljs-string,\n.hljs-title,\n.hljs-constant,\n.hljs-parent,\n.hljs-tag .hljs-value,\n.hljs-rules .hljs-value,\n.hljs-preprocessor,\n.hljs-pragma,\n.haml .hljs-symbol,\n.ruby .hljs-symbol,\n.ruby .hljs-symbol .hljs-string,\n.hljs-template_tag,\n.django .hljs-variable,\n.smalltalk .hljs-class,\n.hljs-addition,\n.hljs-flow,\n.hljs-stream,\n.bash .hljs-variable,\n.apache .hljs-tag,\n.apache .hljs-cbracket,\n.tex .hljs-command,\n.tex .hljs-special,\n.erlang_repl .hljs-function_or_atom,\n.asciidoc .hljs-header,\n.markdown .hljs-header,\n.coffeescript .hljs-attribute {\n  color: #800;\n}\n\n.smartquote,\n.hljs-comment,\n.hljs-annotation,\n.hljs-template_comment,\n.diff .hljs-header,\n.hljs-chunk,\n.asciidoc .hljs-blockquote,\n.markdown .hljs-blockquote {\n  color: #888;\n}\n\n.hljs-number,\n.hljs-date,\n.hljs-regexp,\n.hljs-literal,\n.hljs-hexcolor,\n.smalltalk .hljs-symbol,\n.smalltalk .hljs-char,\n.go .hljs-constant,\n.hljs-change,\n.lasso .hljs-variable,\n.makefile .hljs-variable,\n.asciidoc .hljs-bullet,\n.markdown .hljs-bullet,\n.asciidoc .hljs-link_url,\n.markdown .hljs-link_url {\n  color: #080;\n}\n\n.hljs-label,\n.hljs-javadoc,\n.ruby .hljs-string,\n.hljs-decorator,\n.hljs-filter .hljs-argument,\n.hljs-localvars,\n.hljs-array,\n.hljs-attr_selector,\n.hljs-important,\n.hljs-pseudo,\n.hljs-pi,\n.haml .hljs-bullet,\n.hljs-doctype,\n.hljs-deletion,\n.hljs-envvar,\n.hljs-shebang,\n.apache .hljs-sqbracket,\n.nginx .hljs-built_in,\n.tex .hljs-formula,\n.erlang_repl .hljs-reserved,\n.hljs-prompt,\n.asciidoc .hljs-link_label,\n.markdown .hljs-link_label,\n.vhdl .hljs-attribute,\n.clojure .hljs-attribute,\n.asciidoc .hljs-attribute,\n.lasso .hljs-attribute,\n.coffeescript .hljs-property,\n.hljs-phony {\n  color: #88f;\n}\n\n.hljs-keyword,\n.hljs-id,\n.hljs-title,\n.hljs-built_in,\n.css .hljs-tag,\n.hljs-javadoctag,\n.hljs-phpdoc,\n.hljs-yardoctag,\n.smalltalk .hljs-class,\n.hljs-winutils,\n.bash .hljs-variable,\n.apache .hljs-tag,\n.go .hljs-typename,\n.tex .hljs-command,\n.asciidoc .hljs-strong,\n.markdown .hljs-strong,\n.hljs-request,\n.hljs-status {\n  font-weight: bold;\n}\n\n.asciidoc .hljs-emphasis,\n.markdown .hljs-emphasis {\n  font-style: italic;\n}\n\n.nginx .hljs-built_in {\n  font-weight: normal;\n}\n\n.coffeescript .javascript,\n.javascript .xml,\n.lasso .markup,\n.tex .hljs-formula,\n.xml .javascript,\n.xml .vbscript,\n.xml .css,\n.xml .hljs-cdata {\n  opacity: 0.5;\n}\n";
var codeBlockTemplate = "<pre><code ng-bind-html=\"highlightedCode\"></code></pre>";

insertCss(css);

/**
 * Syntax Highlight Element
 *
 * Assuming the directive is named "syntax":
 * 
 * Element Name Usage
 *     <syntax syntax-language="language">{{code}}</syntax>
 *     =>
 *     <pre syntax-language="language"><code>{{highlightedCode}}</code></pre>
 * Attribute Usage
 *     <e syntax syntax-language="language">{{code}}</syntax>
 *     =>
 *     <pre syntax syntax-language="language"><code>{{highlightedCode}}</code></pre>
 *
 * @param {string} syntaxLanguage Determines the language to highlight
 */
module.exports = ['$sce', function($sce){

    return {
        scope: {
            'syntaxLanguage': '@'
        }, 
        restrict: 'AE',
        template: codeBlockTemplate, 
        transclude: true, 
        replace: true, 
        link: function (scope, element, attributes, controller, transclude) {

            //transclude's clone is the child elements of the directive element, it will wrap any unwrapped text nodes with the span tag
            transclude(scope, function (clone) {

                //get the directive element's content as text, this will be the {{code}}
                var code = angular.element(clone).text();

                //convert the code string into a highlighted code string
                var highlightedCode = hljs.highlight(scope.syntaxLanguage, code, true);

                //bind to the scope as trusted HTML
                scope.highlightedCode = $sce.trustAsHtml(highlightedCode.value.replace(/\n/g,'<br />'));

            });

        }
    };

}];
},{"./lib/hljs/index":42,"fs":1,"insert-css":90}],77:[function(require,module,exports){
'use strict';

/**
 * Filters
 */
angular.module('App.Filters', []);

module.exports = angular.module('App.Filters');
},{}],78:[function(require,module,exports){
'use strict';

/**
 * Modules
 */
angular.module('App.Modules', [
    require('./UserSystem').name
]);

module.exports = angular.module('App.Modules');
},{"./UserSystem":79}],79:[function(require,module,exports){
'use strict';

/**
 * User System Module
 */
angular.module('UserSystemMod', [])
    .provider('UserSystemServ', require('./UserSystemServ'))
    .run(require('./UserSystemRun'));

module.exports = angular.module('UserSystemMod');
},{"./UserSystemRun":80,"./UserSystemServ":81}],80:[function(require,module,exports){
'use strict';

/**
 * User System Run Block
 */
module.exports = ['$rootScope', 'UserSystemServ', function ($rootScope, UserSystemServ) {

    //attempt to get the user's session upon startup, there are three outcomes:
    //1. continues with the current session with a valid session cookie
    //2. triggers autologin with an autologin cookie and returns a valid session cookie
    //3. remains as an anonymous user
    //if the session retrieved was not anonymous it will broadcast that the sesion is logged in
    UserSystemServ.getSession().then(function (response) {
        if (response.content !== 'anonymous') {
            $rootScope.$broadcast('sessionLogin.UserSystem', response.content);
        }
    });

}];
},{}],81:[function(require,module,exports){
'use strict';

/**
 * User System Service Provider.
 * Relies on Restangular.
 */
module.exports = function () {

    var userState = false,
        userData = {},
        accountsResource = 'accounts',
        sessionResource = 'sessions';

    this.setAccountsResource = function (resource) {
        accountsResource = resource;
    };

    this.setSessionResource = function (resource) {
        sessionResource = resource;
    };

    this.$get = [
        '$rootScope',
        '$location',
        'Restangular',
        function ($rootScope, $location, Restangular) {

            //these functions will return a promise
            var userApi = {
                getUserState: function () {
                    return userState;
                },
                getUserData: function () {
                    return userData;
                },
                setUserData: function (data) {
                    userData = data;
                },
                mergeUserData: function (data) {
                    angular.extend(userData, data);
                },
                getAccount: function (id) {
                    return Restangular.all(accountsResource).get(id).then(function (response) {
                        $rootScope.$broadcast('accountProvided.UserSystem', response.content);
                        return response;
                    });
                },
                registerAccount: function (payload) {
                    return Restangular.all(accountsResource).post(payload).then(function (response) {
                        $rootScope.$broadcast('accountRegistered.UserSystem', payload);
                        return response;
                    });
                },
                updateAccount: function (payload) {
                    return Restangular.one(accountsResource, userData.id).customPUT(payload).then(function (response) {
                        $rootScope.$broadcast('accountUpdated.UserSystem', payload);
                        return response;
                    });
                },
                patchAccount: function (payload) {
                    return Restangular.one(accountsResource, userData.id).patch(payload).then(function (response) {
                        $rootScope.$broadcast('accountPatched.UserSystem', payload);
                        return response;
                    });
                },
                deleteAccount: function () {
                    return Restangular.one(accountsResource, userData.id).remove().then(function (response) {
                        $rootScope.$broadcast('accountDestroyed.UserSystem', userData.id);
                        return response;
                    });
                },
                getSession: function () {
                    return Restangular.all(sessionResource).customGET().then(function (response) {
                        $rootScope.$broadcast('sessionProvided.UserSystem', response.content);
                        return response;
                    });
                },
                loginSession: function (payload) {
                    return Restangular.all(sessionResource).post(payload).then(function (response) {
                        $rootScope.$broadcast('sessionLogin.UserSystem', response.content);
                        return response;
                    });
                },
                logoutSession: function () {
                    return Restangular.all(sessionResource).customDELETE().then(function (response) {
                        $rootScope.$broadcast('sessionLogout.UserSystem', userData.id);
                        return response;
                    });
                }
            };

            /**
             * Upon the account being provided, the user data is set to the response content.
             */
            $rootScope.$on('accountProvided.UserSystem', function (event, content) {
                userApi.setUserData(content);
            });

            /**
             * Upon the account being registered, attempt to login given the registration payload's username, email or password.
             */
            $rootScope.$on('accountRegistered.UserSystem', function (event, payload) {
                userApi.loginSession({
                    'username': payload.username,
                    'email': payload.email,
                    'password': payload.password
                });
            });

            /**
             * Upon the account being updated, replace the user data with the payload.
             */
            $rootScope.$on('accountUpdated.UserSystem', function (event, payload) {
                userApi.setUserData(payload);
            });

            /**
             * Upon the account being patched, merge the user data with the payload.
             */
            $rootScope.$on('accountPatched.UserSystem', function (event, payload) {
                userApi.mergeUserData(payload);
            });

            /**
             * Upon the account being destroyed, attempt to logout.
             */
            $rootScope.$on('accountDestroyed.UserSystem', function (event, id) {
                userState = false;
                userApi.logoutSession();
            });

            /**
             * Upon the session being provided, check if the session is registered. If registered broadcast a sessionLogin event.
             */
            $rootScope.$on('sessionProvided.UserSystem', function (event, id) {
                if (id !== 'anonymous') {
                    userState = true;
                }else{
                    userState = false;
                }
            });

            /**
             * Upon session login, get the account.
             */
            $rootScope.$on('sessionLogin.UserSystem', function (event, id) {
                userState = true;
                userApi.getAccount(id);
            });

            /**
             * Upon session logout, clear the userData.
             */
            $rootScope.$on('sessionLogout.UserSystem', function (event, args) {
                userState = false;
                userApi.setUserData({});
            });

            return userApi;

        }
    ];

};
},{}],82:[function(require,module,exports){
'use strict';

/**
 * Authentication State Run Block
 */
module.exports = ['$rootScope', 'UserSystemServ', function ($rootScope, UserSystemServ) {

    $rootScope.loggedIn = false;
    $rootScope.loggedOut = true;

    $rootScope.$watch(function () {

        return UserSystemServ.getUserState();
    
    }, function (state) {

        $rootScope.loggedIn = state;
        $rootScope.loggedOut = !state;

    }, true);

}];
},{}],83:[function(require,module,exports){
'use strict';

/**
 * Base Url Constant
 */
module.exports = angular.element('base').attr('href');
},{}],84:[function(require,module,exports){
'use strict';

module.exports = ['$timeout', function ($timeout) {

    return function (callback, delay, invokeApply) {

        //default delay is 0
        delay = delay || 0;
        //default invokeApply is true
        invokeApply = typeof invokeApply !== 'undefined' ? invokeApply : true;

        var timer;

        var loop = function () {
            //replace the timer promise
            timer = $timeout(function () {
                callback();
                loop();
            }, delay, invokeApply);
        };

        loop();

        return function () {
            loop = angular.noop;
            $timeout.cancel(timer);
        };

    };

}];
},{}],85:[function(require,module,exports){
'use strict';

/**
 * Calculate Service
 */
module.exports = [function () {

    /**
     * Rounds to the nearest place. It can be decimal place, or negative place.
     * 
     * @param  {string|integer|float} value  Number to be rounded
     * @param  {integer}              places Places can be positive or negative.
     * @return {integer|float}
     */
    this.round = function round(value, places) {

        if (typeof places === 'undefined' || +places === 0)
        return Math.round(value);

        value = +value;
        places  = +places;

        if (isNaN(value) || !(typeof places === 'number' && places % 1 === 0))
        return NaN;

        // Shift
        value = value.toString().split('e');
        value = Math.round(+(value[0] + 'e' + (value[1] ? (+value[1] + places) : places)));

        // Shift back
        value = value.toString().split('e');
        return +(value[0] + 'e' + (value[1] ? (+value[1] - places) : -places));

    };

}];
},{}],86:[function(require,module,exports){
'use strict';

var moment = require("./..\\..\\..\\components\\moment\\moment.js");

module.exports = [function () {

    //patching moment with iso duration support
    var isoDurationRegex = /P((([0-9]*\.?[0-9]*)Y)?(([0-9]*\.?[0-9]*)M)?(([0-9]*\.?[0-9]*)W)?(([0-9]*\.?[0-9]*)D)?)?(T(([0-9]*\.?[0-9]*)H)?(([0-9]*\.?[0-9]*)M)?(([0-9]*\.?[0-9]*)S)?)?/

    moment.duration.fromIsoduration = function (duration) {

        var matches = duration.match(isoDurationRegex);

        return moment.duration({
            years: parseFloat(matches[3]),
            months: parseFloat(matches[5]),
            weeks: parseFloat(matches[7]),
            days: parseFloat(matches[9]),
            hours: parseFloat(matches[12]),
            minutes: parseFloat(matches[14]),
            seconds: parseFloat(matches[16])
        });

    };

    moment.duration.fn.isoduration = function () {

        var duration = 'P'
            + ((this.years())? this.years() + 'Y' : '')
            + ((this.months())? this.months() + 'M' : '')
            + ((this.days())? this.days() + 'D' : '')
            + ((this.hours() || this.minutes() || this.seconds())? 'T' : '')
            + ((this.hours())? this.hours() + 'H' : '')
            + ((this.minutes())? this.minutes() + 'M' : '')
            + ((this.seconds())? this.seconds() + 'S' : '');

        return duration;

    };

    return moment;

}];
},{"./..\\..\\..\\components\\moment\\moment.js":5}],87:[function(require,module,exports){
'use strict';

/**
 * Restangular Config Block
 */
module.exports = ['RestangularProvider', 'BaseUrlConst', function (RestangularProvider, BaseUrlConst) {

    //trim the base url of slashes if they exist
    BaseUrlConst = BaseUrlConst.replace(new RegExp('/' + '*$'), '');

    RestangularProvider.setBaseUrl(BaseUrlConst + '/api');

}];
},{}],88:[function(require,module,exports){
'use strict';

/**
 * Services
 */
angular.module('App.Services', []);

module.exports = angular.module('App.Services')
    //Constants
    .constant('BaseUrlConst', require('./BaseUrlConst'))
    //Configuration Services
    .config(require('./RestangularConfig'))
    .config(require('./UserSystemConfig'))
    //Initialisation Services
    .run(require('./AuthenticationStateRun'))
    // .run(require('./RestangularXSRF')) // doesn't yet work, need cookies in response interception
    //Service Objects
    .service('CalculateServ', require('./CalculateServ'))
    .factory('MomentServ', require('./MomentServ'))
    .factory('BusyLoopServ', require('./BusyLoopServ'));
},{"./AuthenticationStateRun":82,"./BaseUrlConst":83,"./BusyLoopServ":84,"./CalculateServ":85,"./MomentServ":86,"./RestangularConfig":87,"./UserSystemConfig":89}],89:[function(require,module,exports){
'use strict';

/**
 * User System Config Block
 */
module.exports = ['UserSystemServProvider', function (UserSystemServProvider) {

    UserSystemServProvider.setAccountsResource('accounts');
    UserSystemServProvider.setSessionResource('session');

}];
},{}],90:[function(require,module,exports){
var inserted = {};

module.exports = function (css) {
    if (inserted[css]) return;
    inserted[css] = true;
    
    var elem = document.createElement('style');
    elem.setAttribute('type', 'text/css');

    if ('textContent' in elem) {
      elem.textContent = css;
    } else {
      elem.styleSheet.cssText = css;
    }
    
    var head = document.getElementsByTagName('head')[0];
    head.appendChild(elem);
};

},{}]},{},[6])