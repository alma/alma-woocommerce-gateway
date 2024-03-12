(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
  typeof define === 'function' && define.amd ? define(['exports'], factory) :
  (global = global || self, factory(global.Alma = {}));
}(this, (function (exports) {
  var fails = function (exec) {
    try {
      return !!exec();
    } catch (error) {
      return true;
    }
  };

  var functionBindNative = !fails(function () {
    // eslint-disable-next-line es-x/no-function-prototype-bind -- safe
    var test = function () {
      /* empty */
    }.bind(); // eslint-disable-next-line no-prototype-builtins -- safe


    return typeof test != 'function' || test.hasOwnProperty('prototype');
  });

  var FunctionPrototype = Function.prototype;
  var bind = FunctionPrototype.bind;
  var call = FunctionPrototype.call;
  var uncurryThis = functionBindNative && bind.bind(call, call);
  var functionUncurryThis = functionBindNative ? function (fn) {
    return fn && uncurryThis(fn);
  } : function (fn) {
    return fn && function () {
      return call.apply(fn, arguments);
    };
  };

  var ceil = Math.ceil;
  var floor = Math.floor; // `Math.trunc` method
  // https://tc39.es/ecma262/#sec-math.trunc
  // eslint-disable-next-line es-x/no-math-trunc -- safe

  var mathTrunc = Math.trunc || function trunc(x) {
    var n = +x;
    return (n > 0 ? floor : ceil)(n);
  };

  // https://tc39.es/ecma262/#sec-tointegerorinfinity

  var toIntegerOrInfinity = function (argument) {
    var number = +argument; // eslint-disable-next-line no-self-compare -- NaN check

    return number !== number || number === 0 ? 0 : mathTrunc(number);
  };

  var commonjsGlobal = typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : typeof global !== 'undefined' ? global : typeof self !== 'undefined' ? self : {};

  function unwrapExports (x) {
  	return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, 'default') ? x['default'] : x;
  }

  function createCommonjsModule(fn, basedir, module) {
  	return module = {
  	  path: basedir,
  	  exports: {},
  	  require: function (path, base) {
        return commonjsRequire();
      }
  	}, fn(module, module.exports), module.exports;
  }

  function commonjsRequire () {
  	throw new Error('Dynamic requires are not currently supported by @rollup/plugin-commonjs');
  }

  var check = function (it) {
    return it && it.Math == Math && it;
  }; // https://github.com/zloirock/core-js/issues/86#issuecomment-115759028


  var global_1 = // eslint-disable-next-line es-x/no-global-this -- safe
  check(typeof globalThis == 'object' && globalThis) || check(typeof window == 'object' && window) || // eslint-disable-next-line no-restricted-globals -- safe
  check(typeof self == 'object' && self) || check(typeof commonjsGlobal == 'object' && commonjsGlobal) || // eslint-disable-next-line no-new-func -- fallback
  function () {
    return this;
  }() || Function('return this')();

  var defineProperty = Object.defineProperty;

  var defineGlobalProperty = function (key, value) {
    try {
      defineProperty(global_1, key, {
        value: value,
        configurable: true,
        writable: true
      });
    } catch (error) {
      global_1[key] = value;
    }

    return value;
  };

  var SHARED = '__core-js_shared__';
  var store = global_1[SHARED] || defineGlobalProperty(SHARED, {});
  var sharedStore = store;

  var shared = createCommonjsModule(function (module) {
    (module.exports = function (key, value) {
      return sharedStore[key] || (sharedStore[key] = value !== undefined ? value : {});
    })('versions', []).push({
      version: '3.22.8',
      mode:  'global',
      copyright: '© 2014-2022 Denis Pushkarev (zloirock.ru)',
      license: 'https://github.com/zloirock/core-js/blob/v3.22.8/LICENSE',
      source: 'https://github.com/zloirock/core-js'
    });
  });

  var $TypeError = TypeError; // `RequireObjectCoercible` abstract operation
  // https://tc39.es/ecma262/#sec-requireobjectcoercible

  var requireObjectCoercible = function (it) {
    if (it == undefined) throw $TypeError("Can't call method on " + it);
    return it;
  };

  var $Object = Object; // `ToObject` abstract operation
  // https://tc39.es/ecma262/#sec-toobject

  var toObject = function (argument) {
    return $Object(requireObjectCoercible(argument));
  };

  var hasOwnProperty = functionUncurryThis({}.hasOwnProperty); // `HasOwnProperty` abstract operation
  // https://tc39.es/ecma262/#sec-hasownproperty
  // eslint-disable-next-line es-x/no-object-hasown -- safe

  var hasOwnProperty_1 = Object.hasOwn || function hasOwn(it, key) {
    return hasOwnProperty(toObject(it), key);
  };

  var id = 0;
  var postfix = Math.random();
  var toString = functionUncurryThis(1.0.toString);

  var uid = function (key) {
    return 'Symbol(' + (key === undefined ? '' : key) + ')_' + toString(++id + postfix, 36);
  };

  // `IsCallable` abstract operation
  // https://tc39.es/ecma262/#sec-iscallable
  var isCallable = function (argument) {
    return typeof argument == 'function';
  };

  var aFunction = function (argument) {
    return isCallable(argument) ? argument : undefined;
  };

  var getBuiltIn = function (namespace, method) {
    return arguments.length < 2 ? aFunction(global_1[namespace]) : global_1[namespace] && global_1[namespace][method];
  };

  var engineUserAgent = getBuiltIn('navigator', 'userAgent') || '';

  var process = global_1.process;
  var Deno = global_1.Deno;
  var versions = process && process.versions || Deno && Deno.version;
  var v8 = versions && versions.v8;
  var match, version;

  if (v8) {
    match = v8.split('.'); // in old Chrome, versions of V8 isn't V8 = Chrome / 10
    // but their correct versions are not interesting for us

    version = match[0] > 0 && match[0] < 4 ? 1 : +(match[0] + match[1]);
  } // BrowserFS NodeJS `process` polyfill incorrectly set `.v8` to `0.0`
  // so check `userAgent` even if `.v8` exists, but 0


  if (!version && engineUserAgent) {
    match = engineUserAgent.match(/Edge\/(\d+)/);

    if (!match || match[1] >= 74) {
      match = engineUserAgent.match(/Chrome\/(\d+)/);
      if (match) version = +match[1];
    }
  }

  var engineV8Version = version;

  /* eslint-disable es-x/no-symbol -- required for testing */
  // eslint-disable-next-line es-x/no-object-getownpropertysymbols -- required for testing

  var nativeSymbol = !!Object.getOwnPropertySymbols && !fails(function () {
    var symbol = Symbol(); // Chrome 38 Symbol has incorrect toString conversion
    // `get-own-property-symbols` polyfill symbols converted to object are not Symbol instances

    return !String(symbol) || !(Object(symbol) instanceof Symbol) || // Chrome 38-40 symbols are not inherited from DOM collections prototypes to instances
    !Symbol.sham && engineV8Version && engineV8Version < 41;
  });

  /* eslint-disable es-x/no-symbol -- required for testing */

  var useSymbolAsUid = nativeSymbol && !Symbol.sham && typeof Symbol.iterator == 'symbol';

  var WellKnownSymbolsStore = shared('wks');
  var Symbol$1 = global_1.Symbol;
  var symbolFor = Symbol$1 && Symbol$1['for'];
  var createWellKnownSymbol = useSymbolAsUid ? Symbol$1 : Symbol$1 && Symbol$1.withoutSetter || uid;

  var wellKnownSymbol = function (name) {
    if (!hasOwnProperty_1(WellKnownSymbolsStore, name) || !(nativeSymbol || typeof WellKnownSymbolsStore[name] == 'string')) {
      var description = 'Symbol.' + name;

      if (nativeSymbol && hasOwnProperty_1(Symbol$1, name)) {
        WellKnownSymbolsStore[name] = Symbol$1[name];
      } else if (useSymbolAsUid && symbolFor) {
        WellKnownSymbolsStore[name] = symbolFor(description);
      } else {
        WellKnownSymbolsStore[name] = createWellKnownSymbol(description);
      }
    }

    return WellKnownSymbolsStore[name];
  };

  var TO_STRING_TAG = wellKnownSymbol('toStringTag');
  var test = {};
  test[TO_STRING_TAG] = 'z';
  var toStringTagSupport = String(test) === '[object z]';

  var toString$1 = functionUncurryThis({}.toString);
  var stringSlice = functionUncurryThis(''.slice);

  var classofRaw = function (it) {
    return stringSlice(toString$1(it), 8, -1);
  };

  var TO_STRING_TAG$1 = wellKnownSymbol('toStringTag');
  var $Object$1 = Object; // ES3 wrong here

  var CORRECT_ARGUMENTS = classofRaw(function () {
    return arguments;
  }()) == 'Arguments'; // fallback for IE11 Script Access Denied error

  var tryGet = function (it, key) {
    try {
      return it[key];
    } catch (error) {
      /* empty */
    }
  }; // getting tag from ES6+ `Object.prototype.toString`


  var classof = toStringTagSupport ? classofRaw : function (it) {
    var O, tag, result;
    return it === undefined ? 'Undefined' : it === null ? 'Null' // @@toStringTag case
    : typeof (tag = tryGet(O = $Object$1(it), TO_STRING_TAG$1)) == 'string' ? tag // builtinTag case
    : CORRECT_ARGUMENTS ? classofRaw(O) // ES3 arguments fallback
    : (result = classofRaw(O)) == 'Object' && isCallable(O.callee) ? 'Arguments' : result;
  };

  var $String = String;

  var toString_1 = function (argument) {
    if (classof(argument) === 'Symbol') throw TypeError('Cannot convert a Symbol value to a string');
    return $String(argument);
  };

  var charAt = functionUncurryThis(''.charAt);
  var charCodeAt = functionUncurryThis(''.charCodeAt);
  var stringSlice$1 = functionUncurryThis(''.slice);

  var createMethod = function (CONVERT_TO_STRING) {
    return function ($this, pos) {
      var S = toString_1(requireObjectCoercible($this));
      var position = toIntegerOrInfinity(pos);
      var size = S.length;
      var first, second;
      if (position < 0 || position >= size) return CONVERT_TO_STRING ? '' : undefined;
      first = charCodeAt(S, position);
      return first < 0xD800 || first > 0xDBFF || position + 1 === size || (second = charCodeAt(S, position + 1)) < 0xDC00 || second > 0xDFFF ? CONVERT_TO_STRING ? charAt(S, position) : first : CONVERT_TO_STRING ? stringSlice$1(S, position, position + 2) : (first - 0xD800 << 10) + (second - 0xDC00) + 0x10000;
    };
  };

  var stringMultibyte = {
    // `String.prototype.codePointAt` method
    // https://tc39.es/ecma262/#sec-string.prototype.codepointat
    codeAt: createMethod(false),
    // `String.prototype.at` method
    // https://github.com/mathiasbynens/String.prototype.at
    charAt: createMethod(true)
  };

  var functionToString = functionUncurryThis(Function.toString); // this helper broken in `core-js@3.4.1-3.4.4`, so we can't use `shared` helper

  if (!isCallable(sharedStore.inspectSource)) {
    sharedStore.inspectSource = function (it) {
      return functionToString(it);
    };
  }

  var inspectSource = sharedStore.inspectSource;

  var WeakMap$1 = global_1.WeakMap;
  var nativeWeakMap = isCallable(WeakMap$1) && /native code/.test(inspectSource(WeakMap$1));

  var isObject = function (it) {
    return typeof it == 'object' ? it !== null : isCallable(it);
  };

  var descriptors = !fails(function () {
    // eslint-disable-next-line es-x/no-object-defineproperty -- required for testing
    return Object.defineProperty({}, 1, {
      get: function () {
        return 7;
      }
    })[1] != 7;
  });

  var document$1 = global_1.document; // typeof document.createElement is 'object' in old IE

  var EXISTS = isObject(document$1) && isObject(document$1.createElement);

  var documentCreateElement = function (it) {
    return EXISTS ? document$1.createElement(it) : {};
  };

  var ie8DomDefine = !descriptors && !fails(function () {
    // eslint-disable-next-line es-x/no-object-defineproperty -- required for testing
    return Object.defineProperty(documentCreateElement('div'), 'a', {
      get: function () {
        return 7;
      }
    }).a != 7;
  });

  // https://bugs.chromium.org/p/v8/issues/detail?id=3334

  var v8PrototypeDefineBug = descriptors && fails(function () {
    // eslint-disable-next-line es-x/no-object-defineproperty -- required for testing
    return Object.defineProperty(function () {
      /* empty */
    }, 'prototype', {
      value: 42,
      writable: false
    }).prototype != 42;
  });

  var $String$1 = String;
  var $TypeError$1 = TypeError; // `Assert: Type(argument) is Object`

  var anObject = function (argument) {
    if (isObject(argument)) return argument;
    throw $TypeError$1($String$1(argument) + ' is not an object');
  };

  var call$1 = Function.prototype.call;
  var functionCall = functionBindNative ? call$1.bind(call$1) : function () {
    return call$1.apply(call$1, arguments);
  };

  var objectIsPrototypeOf = functionUncurryThis({}.isPrototypeOf);

  var $Object$2 = Object;
  var isSymbol = useSymbolAsUid ? function (it) {
    return typeof it == 'symbol';
  } : function (it) {
    var $Symbol = getBuiltIn('Symbol');
    return isCallable($Symbol) && objectIsPrototypeOf($Symbol.prototype, $Object$2(it));
  };

  var $String$2 = String;

  var tryToString = function (argument) {
    try {
      return $String$2(argument);
    } catch (error) {
      return 'Object';
    }
  };

  var $TypeError$2 = TypeError; // `Assert: IsCallable(argument) is true`

  var aCallable = function (argument) {
    if (isCallable(argument)) return argument;
    throw $TypeError$2(tryToString(argument) + ' is not a function');
  };

  // https://tc39.es/ecma262/#sec-getmethod

  var getMethod = function (V, P) {
    var func = V[P];
    return func == null ? undefined : aCallable(func);
  };

  var $TypeError$3 = TypeError; // `OrdinaryToPrimitive` abstract operation
  // https://tc39.es/ecma262/#sec-ordinarytoprimitive

  var ordinaryToPrimitive = function (input, pref) {
    var fn, val;
    if (pref === 'string' && isCallable(fn = input.toString) && !isObject(val = functionCall(fn, input))) return val;
    if (isCallable(fn = input.valueOf) && !isObject(val = functionCall(fn, input))) return val;
    if (pref !== 'string' && isCallable(fn = input.toString) && !isObject(val = functionCall(fn, input))) return val;
    throw $TypeError$3("Can't convert object to primitive value");
  };

  var $TypeError$4 = TypeError;
  var TO_PRIMITIVE = wellKnownSymbol('toPrimitive'); // `ToPrimitive` abstract operation
  // https://tc39.es/ecma262/#sec-toprimitive

  var toPrimitive = function (input, pref) {
    if (!isObject(input) || isSymbol(input)) return input;
    var exoticToPrim = getMethod(input, TO_PRIMITIVE);
    var result;

    if (exoticToPrim) {
      if (pref === undefined) pref = 'default';
      result = functionCall(exoticToPrim, input, pref);
      if (!isObject(result) || isSymbol(result)) return result;
      throw $TypeError$4("Can't convert object to primitive value");
    }

    if (pref === undefined) pref = 'number';
    return ordinaryToPrimitive(input, pref);
  };

  // https://tc39.es/ecma262/#sec-topropertykey

  var toPropertyKey = function (argument) {
    var key = toPrimitive(argument, 'string');
    return isSymbol(key) ? key : key + '';
  };

  var $TypeError$5 = TypeError; // eslint-disable-next-line es-x/no-object-defineproperty -- safe

  var $defineProperty = Object.defineProperty; // eslint-disable-next-line es-x/no-object-getownpropertydescriptor -- safe

  var $getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;
  var ENUMERABLE = 'enumerable';
  var CONFIGURABLE = 'configurable';
  var WRITABLE = 'writable'; // `Object.defineProperty` method
  // https://tc39.es/ecma262/#sec-object.defineproperty

  var f = descriptors ? v8PrototypeDefineBug ? function defineProperty(O, P, Attributes) {
    anObject(O);
    P = toPropertyKey(P);
    anObject(Attributes);

    if (typeof O === 'function' && P === 'prototype' && 'value' in Attributes && WRITABLE in Attributes && !Attributes[WRITABLE]) {
      var current = $getOwnPropertyDescriptor(O, P);

      if (current && current[WRITABLE]) {
        O[P] = Attributes.value;
        Attributes = {
          configurable: CONFIGURABLE in Attributes ? Attributes[CONFIGURABLE] : current[CONFIGURABLE],
          enumerable: ENUMERABLE in Attributes ? Attributes[ENUMERABLE] : current[ENUMERABLE],
          writable: false
        };
      }
    }

    return $defineProperty(O, P, Attributes);
  } : $defineProperty : function defineProperty(O, P, Attributes) {
    anObject(O);
    P = toPropertyKey(P);
    anObject(Attributes);
    if (ie8DomDefine) try {
      return $defineProperty(O, P, Attributes);
    } catch (error) {
      /* empty */
    }
    if ('get' in Attributes || 'set' in Attributes) throw $TypeError$5('Accessors not supported');
    if ('value' in Attributes) O[P] = Attributes.value;
    return O;
  };
  var objectDefineProperty = {
    f: f
  };

  var createPropertyDescriptor = function (bitmap, value) {
    return {
      enumerable: !(bitmap & 1),
      configurable: !(bitmap & 2),
      writable: !(bitmap & 4),
      value: value
    };
  };

  var createNonEnumerableProperty = descriptors ? function (object, key, value) {
    return objectDefineProperty.f(object, key, createPropertyDescriptor(1, value));
  } : function (object, key, value) {
    object[key] = value;
    return object;
  };

  var keys = shared('keys');

  var sharedKey = function (key) {
    return keys[key] || (keys[key] = uid(key));
  };

  var hiddenKeys = {};

  var OBJECT_ALREADY_INITIALIZED = 'Object already initialized';
  var TypeError$1 = global_1.TypeError;
  var WeakMap$2 = global_1.WeakMap;
  var set, get, has;

  var enforce = function (it) {
    return has(it) ? get(it) : set(it, {});
  };

  var getterFor = function (TYPE) {
    return function (it) {
      var state;

      if (!isObject(it) || (state = get(it)).type !== TYPE) {
        throw TypeError$1('Incompatible receiver, ' + TYPE + ' required');
      }

      return state;
    };
  };

  if (nativeWeakMap || sharedStore.state) {
    var store$1 = sharedStore.state || (sharedStore.state = new WeakMap$2());
    var wmget = functionUncurryThis(store$1.get);
    var wmhas = functionUncurryThis(store$1.has);
    var wmset = functionUncurryThis(store$1.set);

    set = function (it, metadata) {
      if (wmhas(store$1, it)) throw new TypeError$1(OBJECT_ALREADY_INITIALIZED);
      metadata.facade = it;
      wmset(store$1, it, metadata);
      return metadata;
    };

    get = function (it) {
      return wmget(store$1, it) || {};
    };

    has = function (it) {
      return wmhas(store$1, it);
    };
  } else {
    var STATE = sharedKey('state');
    hiddenKeys[STATE] = true;

    set = function (it, metadata) {
      if (hasOwnProperty_1(it, STATE)) throw new TypeError$1(OBJECT_ALREADY_INITIALIZED);
      metadata.facade = it;
      createNonEnumerableProperty(it, STATE, metadata);
      return metadata;
    };

    get = function (it) {
      return hasOwnProperty_1(it, STATE) ? it[STATE] : {};
    };

    has = function (it) {
      return hasOwnProperty_1(it, STATE);
    };
  }

  var internalState = {
    set: set,
    get: get,
    has: has,
    enforce: enforce,
    getterFor: getterFor
  };

  var $propertyIsEnumerable = {}.propertyIsEnumerable; // eslint-disable-next-line es-x/no-object-getownpropertydescriptor -- safe

  var getOwnPropertyDescriptor = Object.getOwnPropertyDescriptor; // Nashorn ~ JDK8 bug

  var NASHORN_BUG = getOwnPropertyDescriptor && !$propertyIsEnumerable.call({
    1: 2
  }, 1); // `Object.prototype.propertyIsEnumerable` method implementation
  // https://tc39.es/ecma262/#sec-object.prototype.propertyisenumerable

  var f$1 = NASHORN_BUG ? function propertyIsEnumerable(V) {
    var descriptor = getOwnPropertyDescriptor(this, V);
    return !!descriptor && descriptor.enumerable;
  } : $propertyIsEnumerable;
  var objectPropertyIsEnumerable = {
    f: f$1
  };

  var $Object$3 = Object;
  var split = functionUncurryThis(''.split); // fallback for non-array-like ES3 and non-enumerable old V8 strings

  var indexedObject = fails(function () {
    // throws an error in rhino, see https://github.com/mozilla/rhino/issues/346
    // eslint-disable-next-line no-prototype-builtins -- safe
    return !$Object$3('z').propertyIsEnumerable(0);
  }) ? function (it) {
    return classofRaw(it) == 'String' ? split(it, '') : $Object$3(it);
  } : $Object$3;

  var toIndexedObject = function (it) {
    return indexedObject(requireObjectCoercible(it));
  };

  var $getOwnPropertyDescriptor$1 = Object.getOwnPropertyDescriptor; // `Object.getOwnPropertyDescriptor` method
  // https://tc39.es/ecma262/#sec-object.getownpropertydescriptor

  var f$2 = descriptors ? $getOwnPropertyDescriptor$1 : function getOwnPropertyDescriptor(O, P) {
    O = toIndexedObject(O);
    P = toPropertyKey(P);
    if (ie8DomDefine) try {
      return $getOwnPropertyDescriptor$1(O, P);
    } catch (error) {
      /* empty */
    }
    if (hasOwnProperty_1(O, P)) return createPropertyDescriptor(!functionCall(objectPropertyIsEnumerable.f, O, P), O[P]);
  };
  var objectGetOwnPropertyDescriptor = {
    f: f$2
  };

  var FunctionPrototype$1 = Function.prototype; // eslint-disable-next-line es-x/no-object-getownpropertydescriptor -- safe

  var getDescriptor = descriptors && Object.getOwnPropertyDescriptor;
  var EXISTS$1 = hasOwnProperty_1(FunctionPrototype$1, 'name'); // additional protection from minified / mangled / dropped function names

  var PROPER = EXISTS$1 && function something() {
    /* empty */
  }.name === 'something';

  var CONFIGURABLE$1 = EXISTS$1 && (!descriptors || descriptors && getDescriptor(FunctionPrototype$1, 'name').configurable);
  var functionName = {
    EXISTS: EXISTS$1,
    PROPER: PROPER,
    CONFIGURABLE: CONFIGURABLE$1
  };

  var makeBuiltIn_1 = createCommonjsModule(function (module) {
    var CONFIGURABLE_FUNCTION_NAME = functionName.CONFIGURABLE;
    var enforceInternalState = internalState.enforce;
    var getInternalState = internalState.get; // eslint-disable-next-line es-x/no-object-defineproperty -- safe

    var defineProperty = Object.defineProperty;
    var CONFIGURABLE_LENGTH = descriptors && !fails(function () {
      return defineProperty(function () {
        /* empty */
      }, 'length', {
        value: 8
      }).length !== 8;
    });
    var TEMPLATE = String(String).split('String');

    var makeBuiltIn = module.exports = function (value, name, options) {
      if (String(name).slice(0, 7) === 'Symbol(') {
        name = '[' + String(name).replace(/^Symbol\(([^)]*)\)/, '$1') + ']';
      }

      if (options && options.getter) name = 'get ' + name;
      if (options && options.setter) name = 'set ' + name;

      if (!hasOwnProperty_1(value, 'name') || CONFIGURABLE_FUNCTION_NAME && value.name !== name) {
        defineProperty(value, 'name', {
          value: name,
          configurable: true
        });
      }

      if (CONFIGURABLE_LENGTH && options && hasOwnProperty_1(options, 'arity') && value.length !== options.arity) {
        defineProperty(value, 'length', {
          value: options.arity
        });
      }

      try {
        if (options && hasOwnProperty_1(options, 'constructor') && options.constructor) {
          if (descriptors) defineProperty(value, 'prototype', {
            writable: false
          }); // in V8 ~ Chrome 53, prototypes of some methods, like `Array.prototype.values`, are non-writable
        } else if (value.prototype) value.prototype = undefined;
      } catch (error) {
        /* empty */
      }

      var state = enforceInternalState(value);

      if (!hasOwnProperty_1(state, 'source')) {
        state.source = TEMPLATE.join(typeof name == 'string' ? name : '');
      }

      return value;
    }; // add fake Function#toString for correct work wrapped methods / constructors with methods like LoDash isNative
    // eslint-disable-next-line no-extend-native -- required


    Function.prototype.toString = makeBuiltIn(function toString() {
      return isCallable(this) && getInternalState(this).source || inspectSource(this);
    }, 'toString');
  });

  var defineBuiltIn = function (O, key, value, options) {
    if (!options) options = {};
    var simple = options.enumerable;
    var name = options.name !== undefined ? options.name : key;
    if (isCallable(value)) makeBuiltIn_1(value, name, options);

    if (options.global) {
      if (simple) O[key] = value;else defineGlobalProperty(key, value);
    } else {
      if (!options.unsafe) delete O[key];else if (O[key]) simple = true;
      if (simple) O[key] = value;else createNonEnumerableProperty(O, key, value);
    }

    return O;
  };

  var max = Math.max;
  var min = Math.min; // Helper for a popular repeating case of the spec:
  // Let integer be ? ToInteger(index).
  // If integer < 0, let result be max((length + integer), 0); else let result be min(integer, length).

  var toAbsoluteIndex = function (index, length) {
    var integer = toIntegerOrInfinity(index);
    return integer < 0 ? max(integer + length, 0) : min(integer, length);
  };

  var min$1 = Math.min; // `ToLength` abstract operation
  // https://tc39.es/ecma262/#sec-tolength

  var toLength = function (argument) {
    return argument > 0 ? min$1(toIntegerOrInfinity(argument), 0x1FFFFFFFFFFFFF) : 0; // 2 ** 53 - 1 == 9007199254740991
  };

  // https://tc39.es/ecma262/#sec-lengthofarraylike

  var lengthOfArrayLike = function (obj) {
    return toLength(obj.length);
  };

  var createMethod$1 = function (IS_INCLUDES) {
    return function ($this, el, fromIndex) {
      var O = toIndexedObject($this);
      var length = lengthOfArrayLike(O);
      var index = toAbsoluteIndex(fromIndex, length);
      var value; // Array#includes uses SameValueZero equality algorithm
      // eslint-disable-next-line no-self-compare -- NaN check

      if (IS_INCLUDES && el != el) while (length > index) {
        value = O[index++]; // eslint-disable-next-line no-self-compare -- NaN check

        if (value != value) return true; // Array#indexOf ignores holes, Array#includes - not
      } else for (; length > index; index++) {
        if ((IS_INCLUDES || index in O) && O[index] === el) return IS_INCLUDES || index || 0;
      }
      return !IS_INCLUDES && -1;
    };
  };

  var arrayIncludes = {
    // `Array.prototype.includes` method
    // https://tc39.es/ecma262/#sec-array.prototype.includes
    includes: createMethod$1(true),
    // `Array.prototype.indexOf` method
    // https://tc39.es/ecma262/#sec-array.prototype.indexof
    indexOf: createMethod$1(false)
  };

  var indexOf = arrayIncludes.indexOf;
  var push = functionUncurryThis([].push);

  var objectKeysInternal = function (object, names) {
    var O = toIndexedObject(object);
    var i = 0;
    var result = [];
    var key;

    for (key in O) !hasOwnProperty_1(hiddenKeys, key) && hasOwnProperty_1(O, key) && push(result, key); // Don't enum bug & hidden keys


    while (names.length > i) if (hasOwnProperty_1(O, key = names[i++])) {
      ~indexOf(result, key) || push(result, key);
    }

    return result;
  };

  // IE8- don't enum bug keys
  var enumBugKeys = ['constructor', 'hasOwnProperty', 'isPrototypeOf', 'propertyIsEnumerable', 'toLocaleString', 'toString', 'valueOf'];

  var hiddenKeys$1 = enumBugKeys.concat('length', 'prototype'); // `Object.getOwnPropertyNames` method
  // https://tc39.es/ecma262/#sec-object.getownpropertynames
  // eslint-disable-next-line es-x/no-object-getownpropertynames -- safe

  var f$3 = Object.getOwnPropertyNames || function getOwnPropertyNames(O) {
    return objectKeysInternal(O, hiddenKeys$1);
  };

  var objectGetOwnPropertyNames = {
    f: f$3
  };

  // eslint-disable-next-line es-x/no-object-getownpropertysymbols -- safe
  var f$4 = Object.getOwnPropertySymbols;
  var objectGetOwnPropertySymbols = {
    f: f$4
  };

  var concat = functionUncurryThis([].concat); // all object keys, includes non-enumerable and symbols

  var ownKeys = getBuiltIn('Reflect', 'ownKeys') || function ownKeys(it) {
    var keys = objectGetOwnPropertyNames.f(anObject(it));
    var getOwnPropertySymbols = objectGetOwnPropertySymbols.f;
    return getOwnPropertySymbols ? concat(keys, getOwnPropertySymbols(it)) : keys;
  };

  var copyConstructorProperties = function (target, source, exceptions) {
    var keys = ownKeys(source);
    var defineProperty = objectDefineProperty.f;
    var getOwnPropertyDescriptor = objectGetOwnPropertyDescriptor.f;

    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];

      if (!hasOwnProperty_1(target, key) && !(exceptions && hasOwnProperty_1(exceptions, key))) {
        defineProperty(target, key, getOwnPropertyDescriptor(source, key));
      }
    }
  };

  var replacement = /#|\.prototype\./;

  var isForced = function (feature, detection) {
    var value = data[normalize(feature)];
    return value == POLYFILL ? true : value == NATIVE ? false : isCallable(detection) ? fails(detection) : !!detection;
  };

  var normalize = isForced.normalize = function (string) {
    return String(string).replace(replacement, '.').toLowerCase();
  };

  var data = isForced.data = {};
  var NATIVE = isForced.NATIVE = 'N';
  var POLYFILL = isForced.POLYFILL = 'P';
  var isForced_1 = isForced;

  var getOwnPropertyDescriptor$1 = objectGetOwnPropertyDescriptor.f;
  /*
    options.target         - name of the target object
    options.global         - target is the global object
    options.stat           - export as static methods of target
    options.proto          - export as prototype methods of target
    options.real           - real prototype method for the `pure` version
    options.forced         - export even if the native feature is available
    options.bind           - bind methods to the target, required for the `pure` version
    options.wrap           - wrap constructors to preventing global pollution, required for the `pure` version
    options.unsafe         - use the simple assignment of property instead of delete + defineProperty
    options.sham           - add a flag to not completely full polyfills
    options.enumerable     - export as enumerable property
    options.dontCallGetSet - prevent calling a getter on target
    options.name           - the .name of the function if it does not match the key
  */

  var _export = function (options, source) {
    var TARGET = options.target;
    var GLOBAL = options.global;
    var STATIC = options.stat;
    var FORCED, target, key, targetProperty, sourceProperty, descriptor;

    if (GLOBAL) {
      target = global_1;
    } else if (STATIC) {
      target = global_1[TARGET] || defineGlobalProperty(TARGET, {});
    } else {
      target = (global_1[TARGET] || {}).prototype;
    }

    if (target) for (key in source) {
      sourceProperty = source[key];

      if (options.dontCallGetSet) {
        descriptor = getOwnPropertyDescriptor$1(target, key);
        targetProperty = descriptor && descriptor.value;
      } else targetProperty = target[key];

      FORCED = isForced_1(GLOBAL ? key : TARGET + (STATIC ? '.' : '#') + key, options.forced); // contained in target

      if (!FORCED && targetProperty !== undefined) {
        if (typeof sourceProperty == typeof targetProperty) continue;
        copyConstructorProperties(sourceProperty, targetProperty);
      } // add a flag to not completely full polyfills


      if (options.sham || targetProperty && targetProperty.sham) {
        createNonEnumerableProperty(sourceProperty, 'sham', true);
      }

      defineBuiltIn(target, key, sourceProperty, options);
    }
  };

  // https://tc39.es/ecma262/#sec-object.keys
  // eslint-disable-next-line es-x/no-object-keys -- safe

  var objectKeys = Object.keys || function keys(O) {
    return objectKeysInternal(O, enumBugKeys);
  };

  // https://tc39.es/ecma262/#sec-object.defineproperties
  // eslint-disable-next-line es-x/no-object-defineproperties -- safe

  var f$5 = descriptors && !v8PrototypeDefineBug ? Object.defineProperties : function defineProperties(O, Properties) {
    anObject(O);
    var props = toIndexedObject(Properties);
    var keys = objectKeys(Properties);
    var length = keys.length;
    var index = 0;
    var key;

    while (length > index) objectDefineProperty.f(O, key = keys[index++], props[key]);

    return O;
  };
  var objectDefineProperties = {
    f: f$5
  };

  var html = getBuiltIn('document', 'documentElement');

  /* global ActiveXObject -- old IE, WSH */

  var GT = '>';
  var LT = '<';
  var PROTOTYPE = 'prototype';
  var SCRIPT = 'script';
  var IE_PROTO = sharedKey('IE_PROTO');

  var EmptyConstructor = function () {
    /* empty */
  };

  var scriptTag = function (content) {
    return LT + SCRIPT + GT + content + LT + '/' + SCRIPT + GT;
  }; // Create object with fake `null` prototype: use ActiveX Object with cleared prototype


  var NullProtoObjectViaActiveX = function (activeXDocument) {
    activeXDocument.write(scriptTag(''));
    activeXDocument.close();
    var temp = activeXDocument.parentWindow.Object;
    activeXDocument = null; // avoid memory leak

    return temp;
  }; // Create object with fake `null` prototype: use iframe Object with cleared prototype


  var NullProtoObjectViaIFrame = function () {
    // Thrash, waste and sodomy: IE GC bug
    var iframe = documentCreateElement('iframe');
    var JS = 'java' + SCRIPT + ':';
    var iframeDocument;
    iframe.style.display = 'none';
    html.appendChild(iframe); // https://github.com/zloirock/core-js/issues/475

    iframe.src = String(JS);
    iframeDocument = iframe.contentWindow.document;
    iframeDocument.open();
    iframeDocument.write(scriptTag('document.F=Object'));
    iframeDocument.close();
    return iframeDocument.F;
  }; // Check for document.domain and active x support
  // No need to use active x approach when document.domain is not set
  // see https://github.com/es-shims/es5-shim/issues/150
  // variation of https://github.com/kitcambridge/es5-shim/commit/4f738ac066346
  // avoid IE GC bug


  var activeXDocument;

  var NullProtoObject = function () {
    try {
      activeXDocument = new ActiveXObject('htmlfile');
    } catch (error) {
      /* ignore */
    }

    NullProtoObject = typeof document != 'undefined' ? document.domain && activeXDocument ? NullProtoObjectViaActiveX(activeXDocument) // old IE
    : NullProtoObjectViaIFrame() : NullProtoObjectViaActiveX(activeXDocument); // WSH

    var length = enumBugKeys.length;

    while (length--) delete NullProtoObject[PROTOTYPE][enumBugKeys[length]];

    return NullProtoObject();
  };

  hiddenKeys[IE_PROTO] = true; // `Object.create` method
  // https://tc39.es/ecma262/#sec-object.create
  // eslint-disable-next-line es-x/no-object-create -- safe

  var objectCreate = Object.create || function create(O, Properties) {
    var result;

    if (O !== null) {
      EmptyConstructor[PROTOTYPE] = anObject(O);
      result = new EmptyConstructor();
      EmptyConstructor[PROTOTYPE] = null; // add "__proto__" for Object.getPrototypeOf polyfill

      result[IE_PROTO] = O;
    } else result = NullProtoObject();

    return Properties === undefined ? result : objectDefineProperties.f(result, Properties);
  };

  var correctPrototypeGetter = !fails(function () {
    function F() {
      /* empty */
    }

    F.prototype.constructor = null; // eslint-disable-next-line es-x/no-object-getprototypeof -- required for testing

    return Object.getPrototypeOf(new F()) !== F.prototype;
  });

  var IE_PROTO$1 = sharedKey('IE_PROTO');
  var $Object$4 = Object;
  var ObjectPrototype = $Object$4.prototype; // `Object.getPrototypeOf` method
  // https://tc39.es/ecma262/#sec-object.getprototypeof
  // eslint-disable-next-line es-x/no-object-getprototypeof -- safe

  var objectGetPrototypeOf = correctPrototypeGetter ? $Object$4.getPrototypeOf : function (O) {
    var object = toObject(O);
    if (hasOwnProperty_1(object, IE_PROTO$1)) return object[IE_PROTO$1];
    var constructor = object.constructor;

    if (isCallable(constructor) && object instanceof constructor) {
      return constructor.prototype;
    }

    return object instanceof $Object$4 ? ObjectPrototype : null;
  };

  var ITERATOR = wellKnownSymbol('iterator');
  var BUGGY_SAFARI_ITERATORS = false; // `%IteratorPrototype%` object
  // https://tc39.es/ecma262/#sec-%iteratorprototype%-object

  var IteratorPrototype, PrototypeOfArrayIteratorPrototype, arrayIterator;
  /* eslint-disable es-x/no-array-prototype-keys -- safe */

  if ([].keys) {
    arrayIterator = [].keys(); // Safari 8 has buggy iterators w/o `next`

    if (!('next' in arrayIterator)) BUGGY_SAFARI_ITERATORS = true;else {
      PrototypeOfArrayIteratorPrototype = objectGetPrototypeOf(objectGetPrototypeOf(arrayIterator));
      if (PrototypeOfArrayIteratorPrototype !== Object.prototype) IteratorPrototype = PrototypeOfArrayIteratorPrototype;
    }
  }

  var NEW_ITERATOR_PROTOTYPE = IteratorPrototype == undefined || fails(function () {
    var test = {}; // FF44- legacy iterators case

    return IteratorPrototype[ITERATOR].call(test) !== test;
  });
  if (NEW_ITERATOR_PROTOTYPE) IteratorPrototype = {}; // `%IteratorPrototype%[@@iterator]()` method
  // https://tc39.es/ecma262/#sec-%iteratorprototype%-@@iterator

  if (!isCallable(IteratorPrototype[ITERATOR])) {
    defineBuiltIn(IteratorPrototype, ITERATOR, function () {
      return this;
    });
  }

  var iteratorsCore = {
    IteratorPrototype: IteratorPrototype,
    BUGGY_SAFARI_ITERATORS: BUGGY_SAFARI_ITERATORS
  };

  var defineProperty$1 = objectDefineProperty.f;
  var TO_STRING_TAG$2 = wellKnownSymbol('toStringTag');

  var setToStringTag = function (target, TAG, STATIC) {
    if (target && !STATIC) target = target.prototype;

    if (target && !hasOwnProperty_1(target, TO_STRING_TAG$2)) {
      defineProperty$1(target, TO_STRING_TAG$2, {
        configurable: true,
        value: TAG
      });
    }
  };

  var iterators = {};

  var IteratorPrototype$1 = iteratorsCore.IteratorPrototype;

  var returnThis = function () {
    return this;
  };

  var createIteratorConstructor = function (IteratorConstructor, NAME, next, ENUMERABLE_NEXT) {
    var TO_STRING_TAG = NAME + ' Iterator';
    IteratorConstructor.prototype = objectCreate(IteratorPrototype$1, {
      next: createPropertyDescriptor(+!ENUMERABLE_NEXT, next)
    });
    setToStringTag(IteratorConstructor, TO_STRING_TAG, false);
    iterators[TO_STRING_TAG] = returnThis;
    return IteratorConstructor;
  };

  var $String$3 = String;
  var $TypeError$6 = TypeError;

  var aPossiblePrototype = function (argument) {
    if (typeof argument == 'object' || isCallable(argument)) return argument;
    throw $TypeError$6("Can't set " + $String$3(argument) + ' as a prototype');
  };

  /* eslint-disable no-proto -- safe */
  // `Object.setPrototypeOf` method
  // https://tc39.es/ecma262/#sec-object.setprototypeof
  // Works with __proto__ only. Old v8 can't work with null proto objects.
  // eslint-disable-next-line es-x/no-object-setprototypeof -- safe

  var objectSetPrototypeOf = Object.setPrototypeOf || ('__proto__' in {} ? function () {
    var CORRECT_SETTER = false;
    var test = {};
    var setter;

    try {
      // eslint-disable-next-line es-x/no-object-getownpropertydescriptor -- safe
      setter = functionUncurryThis(Object.getOwnPropertyDescriptor(Object.prototype, '__proto__').set);
      setter(test, []);
      CORRECT_SETTER = test instanceof Array;
    } catch (error) {
      /* empty */
    }

    return function setPrototypeOf(O, proto) {
      anObject(O);
      aPossiblePrototype(proto);
      if (CORRECT_SETTER) setter(O, proto);else O.__proto__ = proto;
      return O;
    };
  }() : undefined);

  var PROPER_FUNCTION_NAME = functionName.PROPER;
  var CONFIGURABLE_FUNCTION_NAME = functionName.CONFIGURABLE;
  var IteratorPrototype$2 = iteratorsCore.IteratorPrototype;
  var BUGGY_SAFARI_ITERATORS$1 = iteratorsCore.BUGGY_SAFARI_ITERATORS;
  var ITERATOR$1 = wellKnownSymbol('iterator');
  var KEYS = 'keys';
  var VALUES = 'values';
  var ENTRIES = 'entries';

  var returnThis$1 = function () {
    return this;
  };

  var defineIterator = function (Iterable, NAME, IteratorConstructor, next, DEFAULT, IS_SET, FORCED) {
    createIteratorConstructor(IteratorConstructor, NAME, next);

    var getIterationMethod = function (KIND) {
      if (KIND === DEFAULT && defaultIterator) return defaultIterator;
      if (!BUGGY_SAFARI_ITERATORS$1 && KIND in IterablePrototype) return IterablePrototype[KIND];

      switch (KIND) {
        case KEYS:
          return function keys() {
            return new IteratorConstructor(this, KIND);
          };

        case VALUES:
          return function values() {
            return new IteratorConstructor(this, KIND);
          };

        case ENTRIES:
          return function entries() {
            return new IteratorConstructor(this, KIND);
          };
      }

      return function () {
        return new IteratorConstructor(this);
      };
    };

    var TO_STRING_TAG = NAME + ' Iterator';
    var INCORRECT_VALUES_NAME = false;
    var IterablePrototype = Iterable.prototype;
    var nativeIterator = IterablePrototype[ITERATOR$1] || IterablePrototype['@@iterator'] || DEFAULT && IterablePrototype[DEFAULT];
    var defaultIterator = !BUGGY_SAFARI_ITERATORS$1 && nativeIterator || getIterationMethod(DEFAULT);
    var anyNativeIterator = NAME == 'Array' ? IterablePrototype.entries || nativeIterator : nativeIterator;
    var CurrentIteratorPrototype, methods, KEY; // fix native

    if (anyNativeIterator) {
      CurrentIteratorPrototype = objectGetPrototypeOf(anyNativeIterator.call(new Iterable()));

      if (CurrentIteratorPrototype !== Object.prototype && CurrentIteratorPrototype.next) {
        if ( objectGetPrototypeOf(CurrentIteratorPrototype) !== IteratorPrototype$2) {
          if (objectSetPrototypeOf) {
            objectSetPrototypeOf(CurrentIteratorPrototype, IteratorPrototype$2);
          } else if (!isCallable(CurrentIteratorPrototype[ITERATOR$1])) {
            defineBuiltIn(CurrentIteratorPrototype, ITERATOR$1, returnThis$1);
          }
        } // Set @@toStringTag to native iterators


        setToStringTag(CurrentIteratorPrototype, TO_STRING_TAG, true);
      }
    } // fix Array.prototype.{ values, @@iterator }.name in V8 / FF


    if (PROPER_FUNCTION_NAME && DEFAULT == VALUES && nativeIterator && nativeIterator.name !== VALUES) {
      if ( CONFIGURABLE_FUNCTION_NAME) {
        createNonEnumerableProperty(IterablePrototype, 'name', VALUES);
      } else {
        INCORRECT_VALUES_NAME = true;

        defaultIterator = function values() {
          return functionCall(nativeIterator, this);
        };
      }
    } // export additional methods


    if (DEFAULT) {
      methods = {
        values: getIterationMethod(VALUES),
        keys: IS_SET ? defaultIterator : getIterationMethod(KEYS),
        entries: getIterationMethod(ENTRIES)
      };
      if (FORCED) for (KEY in methods) {
        if (BUGGY_SAFARI_ITERATORS$1 || INCORRECT_VALUES_NAME || !(KEY in IterablePrototype)) {
          defineBuiltIn(IterablePrototype, KEY, methods[KEY]);
        }
      } else _export({
        target: NAME,
        proto: true,
        forced: BUGGY_SAFARI_ITERATORS$1 || INCORRECT_VALUES_NAME
      }, methods);
    } // define iterator


    if ( IterablePrototype[ITERATOR$1] !== defaultIterator) {
      defineBuiltIn(IterablePrototype, ITERATOR$1, defaultIterator, {
        name: DEFAULT
      });
    }

    iterators[NAME] = defaultIterator;
    return methods;
  };

  var charAt$1 = stringMultibyte.charAt;
  var STRING_ITERATOR = 'String Iterator';
  var setInternalState = internalState.set;
  var getInternalState = internalState.getterFor(STRING_ITERATOR); // `String.prototype[@@iterator]` method
  // https://tc39.es/ecma262/#sec-string.prototype-@@iterator

  defineIterator(String, 'String', function (iterated) {
    setInternalState(this, {
      type: STRING_ITERATOR,
      string: toString_1(iterated),
      index: 0
    }); // `%StringIteratorPrototype%.next` method
    // https://tc39.es/ecma262/#sec-%stringiteratorprototype%.next
  }, function next() {
    var state = getInternalState(this);
    var string = state.string;
    var index = state.index;
    var point;
    if (index >= string.length) return {
      value: undefined,
      done: true
    };
    point = charAt$1(string, index);
    state.index += point.length;
    return {
      value: point,
      done: false
    };
  });

  var bind$1 = functionUncurryThis(functionUncurryThis.bind); // optional / simple context binding

  var functionBindContext = function (fn, that) {
    aCallable(fn);
    return that === undefined ? fn : functionBindNative ? bind$1(fn, that) : function
      /* ...args */
    () {
      return fn.apply(that, arguments);
    };
  };

  var iteratorClose = function (iterator, kind, value) {
    var innerResult, innerError;
    anObject(iterator);

    try {
      innerResult = getMethod(iterator, 'return');

      if (!innerResult) {
        if (kind === 'throw') throw value;
        return value;
      }

      innerResult = functionCall(innerResult, iterator);
    } catch (error) {
      innerError = true;
      innerResult = error;
    }

    if (kind === 'throw') throw value;
    if (innerError) throw innerResult;
    anObject(innerResult);
    return value;
  };

  var callWithSafeIterationClosing = function (iterator, fn, value, ENTRIES) {
    try {
      return ENTRIES ? fn(anObject(value)[0], value[1]) : fn(value);
    } catch (error) {
      iteratorClose(iterator, 'throw', error);
    }
  };

  var ITERATOR$2 = wellKnownSymbol('iterator');
  var ArrayPrototype = Array.prototype; // check on default Array iterator

  var isArrayIteratorMethod = function (it) {
    return it !== undefined && (iterators.Array === it || ArrayPrototype[ITERATOR$2] === it);
  };

  var noop = function () {
    /* empty */
  };

  var empty = [];
  var construct = getBuiltIn('Reflect', 'construct');
  var constructorRegExp = /^\s*(?:class|function)\b/;
  var exec = functionUncurryThis(constructorRegExp.exec);
  var INCORRECT_TO_STRING = !constructorRegExp.exec(noop);

  var isConstructorModern = function isConstructor(argument) {
    if (!isCallable(argument)) return false;

    try {
      construct(noop, empty, argument);
      return true;
    } catch (error) {
      return false;
    }
  };

  var isConstructorLegacy = function isConstructor(argument) {
    if (!isCallable(argument)) return false;

    switch (classof(argument)) {
      case 'AsyncFunction':
      case 'GeneratorFunction':
      case 'AsyncGeneratorFunction':
        return false;
    }

    try {
      // we can't check .prototype since constructors produced by .bind haven't it
      // `Function#toString` throws on some built-it function in some legacy engines
      // (for example, `DOMQuad` and similar in FF41-)
      return INCORRECT_TO_STRING || !!exec(constructorRegExp, inspectSource(argument));
    } catch (error) {
      return true;
    }
  };

  isConstructorLegacy.sham = true; // `IsConstructor` abstract operation
  // https://tc39.es/ecma262/#sec-isconstructor

  var isConstructor = !construct || fails(function () {
    var called;
    return isConstructorModern(isConstructorModern.call) || !isConstructorModern(Object) || !isConstructorModern(function () {
      called = true;
    }) || called;
  }) ? isConstructorLegacy : isConstructorModern;

  var createProperty = function (object, key, value) {
    var propertyKey = toPropertyKey(key);
    if (propertyKey in object) objectDefineProperty.f(object, propertyKey, createPropertyDescriptor(0, value));else object[propertyKey] = value;
  };

  var ITERATOR$3 = wellKnownSymbol('iterator');

  var getIteratorMethod = function (it) {
    if (it != undefined) return getMethod(it, ITERATOR$3) || getMethod(it, '@@iterator') || iterators[classof(it)];
  };

  var $TypeError$7 = TypeError;

  var getIterator = function (argument, usingIterator) {
    var iteratorMethod = arguments.length < 2 ? getIteratorMethod(argument) : usingIterator;
    if (aCallable(iteratorMethod)) return anObject(functionCall(iteratorMethod, argument));
    throw $TypeError$7(tryToString(argument) + ' is not iterable');
  };

  var $Array = Array; // `Array.from` method implementation
  // https://tc39.es/ecma262/#sec-array.from

  var arrayFrom = function from(arrayLike
  /* , mapfn = undefined, thisArg = undefined */
  ) {
    var O = toObject(arrayLike);
    var IS_CONSTRUCTOR = isConstructor(this);
    var argumentsLength = arguments.length;
    var mapfn = argumentsLength > 1 ? arguments[1] : undefined;
    var mapping = mapfn !== undefined;
    if (mapping) mapfn = functionBindContext(mapfn, argumentsLength > 2 ? arguments[2] : undefined);
    var iteratorMethod = getIteratorMethod(O);
    var index = 0;
    var length, result, step, iterator, next, value; // if the target is not iterable or it's an array with the default iterator - use a simple case

    if (iteratorMethod && !(this === $Array && isArrayIteratorMethod(iteratorMethod))) {
      iterator = getIterator(O, iteratorMethod);
      next = iterator.next;
      result = IS_CONSTRUCTOR ? new this() : [];

      for (; !(step = functionCall(next, iterator)).done; index++) {
        value = mapping ? callWithSafeIterationClosing(iterator, mapfn, [step.value, index], true) : step.value;
        createProperty(result, index, value);
      }
    } else {
      length = lengthOfArrayLike(O);
      result = IS_CONSTRUCTOR ? new this(length) : $Array(length);

      for (; length > index; index++) {
        value = mapping ? mapfn(O[index], index) : O[index];
        createProperty(result, index, value);
      }
    }

    result.length = index;
    return result;
  };

  var ITERATOR$4 = wellKnownSymbol('iterator');
  var SAFE_CLOSING = false;

  try {
    var called = 0;
    var iteratorWithReturn = {
      next: function () {
        return {
          done: !!called++
        };
      },
      'return': function () {
        SAFE_CLOSING = true;
      }
    };

    iteratorWithReturn[ITERATOR$4] = function () {
      return this;
    }; // eslint-disable-next-line es-x/no-array-from, no-throw-literal -- required for testing


    Array.from(iteratorWithReturn, function () {
      throw 2;
    });
  } catch (error) {
    /* empty */
  }

  var checkCorrectnessOfIteration = function (exec, SKIP_CLOSING) {
    if (!SKIP_CLOSING && !SAFE_CLOSING) return false;
    var ITERATION_SUPPORT = false;

    try {
      var object = {};

      object[ITERATOR$4] = function () {
        return {
          next: function () {
            return {
              done: ITERATION_SUPPORT = true
            };
          }
        };
      };

      exec(object);
    } catch (error) {
      /* empty */
    }

    return ITERATION_SUPPORT;
  };

  var INCORRECT_ITERATION = !checkCorrectnessOfIteration(function (iterable) {
    // eslint-disable-next-line es-x/no-array-from -- required for testing
    Array.from(iterable);
  }); // `Array.from` method
  // https://tc39.es/ecma262/#sec-array.from

  _export({
    target: 'Array',
    stat: true,
    forced: INCORRECT_ITERATION
  }, {
    from: arrayFrom
  });

  var widgetTypes;

  (function (widgetTypes) {
    widgetTypes["PaymentPlans"] = "PaymentPlans";
    widgetTypes["Modal"] = "Modal";
  })(widgetTypes || (widgetTypes = {}));

  var apiStatus;

  (function (apiStatus) {
    apiStatus["PENDING"] = "pending";
    apiStatus["SUCCESS"] = "success";
    apiStatus["FAILED"] = "failed";
  })(apiStatus || (apiStatus = {}));

  var Locale;

  (function (Locale) {
    Locale["en"] = "en";
    Locale["fr-FR"] = "fr-FR";
    Locale["fr"] = "fr";
    Locale["de-DE"] = "de-DE";
    Locale["de"] = "de";
    Locale["it"] = "it";
    Locale["it-IT"] = "it-IT";
    Locale["es"] = "es";
    Locale["es-ES"] = "es-ES";
    Locale["pt"] = "pt";
    Locale["pt-PT"] = "pt-PT";
    Locale["nl"] = "nl";
    Locale["nl-NL"] = "nl-NL";
    Locale["nl-BE"] = "nl-BE";
  })(Locale || (Locale = {}));

  function priceToCents(price) {
    return Math.round(price * 100);
  }
  function priceFromCents(cents) {
    return Number((cents / 100).toFixed(2));
  }
  function formatCents(cents) {
    return String(priceFromCents(cents)).replace('.', ',');
  }
  var desktopWidth = 800;
  var isP1X = function isP1X(plan) {
    return (plan == null ? void 0 : plan.installments_count) === 1 && (plan == null ? void 0 : plan.deferred_days) === 0 && (plan == null ? void 0 : plan.deferred_months) === 0;
  };

  /* eslint-disable */

  (function (ElementProto) {
    if (typeof ElementProto.matches !== 'function') {
      ElementProto.matches = ElementProto.msMatchesSelector || ElementProto.mozMatchesSelector || ElementProto.webkitMatchesSelector || function matches(selector) {
        var element = this;
        var elements = (element.document || element.ownerDocument).querySelectorAll(selector);
        var index = 0;

        while (elements[index] && elements[index] !== element) {
          ++index;
        }

        return Boolean(elements[index]);
      };
    }

    if (typeof ElementProto.closest !== 'function') {
      ElementProto.closest = function closest(selector) {
        var element = this;

        while (element && element.nodeType === 1) {
          if (element.matches(selector)) {
            return element;
          }

          element = element.parentNode;
        }

        return null;
      };
    }
  })(window.Element.prototype);
  /*
   * Element.prototype.classList polyfill
   */
  // 1. String.prototype.trim polyfill


  if (!''.trim) String.prototype.trim = function () {
    return this.replace(/^[\s﻿]+|[\s﻿]+$/g, '');
  };

  (function (window) {

    if (!window.DOMException) (DOMException = function DOMException(reason) {
      this.message = reason;
    }).prototype = new Error();

    var wsRE = /[\11\12\14\15\40]/,
        wsIndex = 0,
        checkIfValidClassListEntry = function checkIfValidClassListEntry(O, V) {
      if (V === '') throw new DOMException("Failed to execute '" + O + "' on 'DOMTokenList': The token provided must not be empty.");
      if ((wsIndex = V.search(wsRE)) !== -1) throw new DOMException("Failed to execute '" + O + "' on 'DOMTokenList': " + "The token provided ('" + V[wsIndex] + "') contains HTML space characters, which are not valid in tokens.");
    }; // 2. Implement the barebones DOMTokenList livelyness polyfill


    if (typeof DOMTokenList !== 'function') (function (window) {
      var document = window.document,
          Object = window.Object,
          hasOwnProp = Object.prototype.hasOwnProperty;
      var defineProperty = Object.defineProperty,
          allowTokenListConstruction = 0,
          skipPropChange = 0;

      var DOMTokenList = function DOMTokenList() {
        if (!allowTokenListConstruction) throw TypeError('Illegal constructor'); // internally let it through
      };

      DOMTokenList.prototype.toString = DOMTokenList.prototype.toLocaleString = function () {
        return this.value;
      };

      DOMTokenList.prototype.add = function () {
        a: for (var v = 0, argLen = arguments.length, val = '', ele = this[' uCL'], proto = ele[' uCLp']; v !== argLen; ++v) {
          val = arguments[v] + '', checkIfValidClassListEntry('add', val);

          for (var i = 0, Len = proto.length, resStr = val; i !== Len; ++i) {
            if (this[i] === val) continue a;else resStr += ' ' + this[i];
          }

          this[Len] = val, proto.length += 1, proto.value = resStr;
        }
        skipPropChange = 1, ele.className = proto.value, skipPropChange = 0;
      };

      DOMTokenList.prototype.remove = function () {
        for (var v = 0, argLen = arguments.length, val = '', ele = this[' uCL'], proto = ele[' uCLp']; v !== argLen; ++v) {
          val = arguments[v] + '', checkIfValidClassListEntry('remove', val);

          for (var i = 0, Len = proto.length, resStr = '', is = 0; i !== Len; ++i) {
            if (is) {
              this[i - 1] = this[i];
            } else {
              if (this[i] !== val) {
                resStr += this[i] + ' ';
              } else {
                is = 1;
              }
            }
          }

          if (!is) continue;
          delete this[Len], proto.length -= 1, proto.value = resStr;
        }
        skipPropChange = 1, ele.className = proto.value, skipPropChange = 0;
      };

      window.DOMTokenList = DOMTokenList;

      function whenPropChanges() {
        var evt = window.event,
            prop = evt.propertyName;

        if (!skipPropChange && (prop === 'className' || prop === 'classList' && !defineProperty)) {
          var target = evt.srcElement,
              protoObjProto = target[' uCLp'],
              strval = '' + target[prop];
          var tokens = strval.trim().split(wsRE),
              resTokenList = target[prop === 'classList' ? ' uCL' : 'classList'];
          var oldLen = protoObjProto.length;

          a: for (var cI = 0, cLen = protoObjProto.length = tokens.length, sub = 0; cI !== cLen; ++cI) {
            for (var innerI = 0; innerI !== cI; ++innerI) {
              if (tokens[innerI] === tokens[cI]) {
                sub++;
                continue a;
              }
            }

            resTokenList[cI - sub] = tokens[cI];
          }

          for (var i = cLen - sub; i < oldLen; ++i) {
            delete resTokenList[i];
          } //remove trailing indexs


          if (prop !== 'classList') return;
          skipPropChange = 1, target.classList = resTokenList, target.className = strval;
          skipPropChange = 0, resTokenList.length = tokens.length - sub;
        }
      }

      function polyfillClassList(ele) {
        if (!ele || !('innerText' in ele)) throw TypeError('Illegal invocation');
        ele.detachEvent('onpropertychange', whenPropChanges); // prevent duplicate handler infinite loop

        allowTokenListConstruction = 1;

        try {
          var protoObj = function protoObj() {};

          protoObj.prototype = new DOMTokenList();
        } finally {
          allowTokenListConstruction = 0;
        }

        var protoObjProto = protoObj.prototype,
            resTokenList = new protoObj();

        a: for (var toks = ele.className.trim().split(wsRE), cI = 0, cLen = toks.length, sub = 0; cI !== cLen; ++cI) {
          for (var innerI = 0; innerI !== cI; ++innerI) {
            if (toks[innerI] === toks[cI]) {
              sub++;
              continue a;
            }
          }

          this[cI - sub] = toks[cI];
        }
        protoObjProto.length = cLen - sub, protoObjProto.value = ele.className, protoObjProto[' uCL'] = ele;

        if (defineProperty) {
          defineProperty(ele, 'classList', {
            // IE8 & IE9 allow defineProperty on the DOM
            enumerable: 1,
            get: function get() {
              return resTokenList;
            },
            configurable: 0,
            set: function set(newVal) {
              skipPropChange = 1, ele.className = protoObjProto.value = newVal += '', skipPropChange = 0;
              var toks = newVal.trim().split(wsRE),
                  oldLen = protoObjProto.length;

              a: for (var cI = 0, cLen = protoObjProto.length = toks.length, sub = 0; cI !== cLen; ++cI) {
                for (var innerI = 0; innerI !== cI; ++innerI) {
                  if (toks[innerI] === toks[cI]) {
                    sub++;
                    continue a;
                  }
                }

                resTokenList[cI - sub] = toks[cI];
              }

              for (var i = cLen - sub; i < oldLen; ++i) {
                delete resTokenList[i];
              } //remove trailing indexs

            }
          });
          defineProperty(ele, ' uCLp', {
            // for accessing the hidden prototype
            enumerable: 0,
            configurable: 0,
            writeable: 0,
            value: protoObj.prototype
          });
          defineProperty(protoObjProto, ' uCL', {
            enumerable: 0,
            configurable: 0,
            writeable: 0,
            value: ele
          });
        } else {
          ele.classList = resTokenList, ele[' uCL'] = resTokenList, ele[' uCLp'] = protoObj.prototype;
        }

        ele.attachEvent('onpropertychange', whenPropChanges);
      }

      try {
        // Much faster & cleaner version for IE8 & IE9:
        // Should work in IE8 because Element.prototype instanceof Node is true according to the specs
        window.Object.defineProperty(window.Element.prototype, 'classList', {
          enumerable: 1,
          get: function get(val) {
            if (!hasOwnProp.call(this, 'classList')) polyfillClassList(this);
            return this.classList;
          },
          configurable: 0,
          set: function set(val) {
            this.className = val;
          }
        });
      } catch (e) {
        // Less performant fallback for older browsers (IE 6-8):
        window[' uCL'] = polyfillClassList; // the below code ensures polyfillClassList is applied to all current and future elements in the doc.

        document.documentElement.firstChild.appendChild(document.createElement('style')).styleSheet.cssText = '_*{x-uCLp:expression(!this.hasOwnProperty("classList")&&window[" uCL"](this))}' + //  IE6
        '[class]{x-uCLp/**/:expression(!this.hasOwnProperty("classList")&&window[" uCL"](this))}'; //IE7-8
      }
    })(window) // 3. Patch in unsupported methods in DOMTokenList
    ;

    (function (DOMTokenListProto, testClass) {
      if (!DOMTokenListProto.item) DOMTokenListProto.item = function (i) {
        function NullCheck(n) {
          return n === void 0 ? null : n;
        }

        return NullCheck(this[i]);
      };
      if (!DOMTokenListProto.toggle || testClass.toggle('a', 0) !== false) DOMTokenListProto.toggle = function (val) {
        if (arguments.length > 1) return this[arguments[1] ? 'add' : 'remove'](val), !!arguments[1];
        var oldValue = this.value;
        return this.remove(oldValue), oldValue === this.value && (this.add(val), true);
        /*|| false*/
      };
      if (!DOMTokenListProto.replace || typeof testClass.replace('a', 'b') !== 'boolean') DOMTokenListProto.replace = function (oldToken, newToken) {
        checkIfValidClassListEntry('replace', oldToken), checkIfValidClassListEntry('replace', newToken);
        var oldValue = this.value;
        return this.remove(oldToken), this.value !== oldValue && (this.add(newToken), true);
      };
      if (!DOMTokenListProto.contains) DOMTokenListProto.contains = function (value) {
        for (var i = 0, Len = this.length; i !== Len; ++i) {
          if (this[i] === value) return true;
        }

        return false;
      };
      if (!DOMTokenListProto.forEach) DOMTokenListProto.forEach = function (f) {
        if (arguments.length === 1) for (var i = 0, Len = this.length; i !== Len; ++i) {
          f(this[i], i, this);
        } else for (var i = 0, Len = this.length, tArg = arguments[1]; i !== Len; ++i) {
          f.call(tArg, this[i], i, this);
        }
      };
      if (!DOMTokenListProto.entries) DOMTokenListProto.entries = function () {
        var nextIndex = 0,
            that = this;
        return {
          next: function next() {
            return nextIndex < that.length ? {
              value: [nextIndex, that[nextIndex]],
              done: false
            } : {
              done: true
            };
          }
        };
      };
      if (!DOMTokenListProto.values) DOMTokenListProto.values = function () {
        var nextIndex = 0,
            that = this;
        return {
          next: function next() {
            return nextIndex < that.length ? {
              value: that[nextIndex],
              done: false
            } : {
              done: true
            };
          }
        };
      };
      if (!DOMTokenListProto.keys) DOMTokenListProto.keys = function () {
        var nextIndex = 0,
            that = this;
        return {
          next: function next() {
            return nextIndex < that.length ? {
              value: nextIndex,
              done: false
            } : {
              done: true
            };
          }
        };
      };
    })(window.DOMTokenList.prototype, window.document.createElement('div').classList);
  })(window);

  /*
  object-assign
  (c) Sindre Sorhus
  @license MIT
  */
  /* eslint-disable no-unused-vars */

  var getOwnPropertySymbols = Object.getOwnPropertySymbols;
  var hasOwnProperty$1 = Object.prototype.hasOwnProperty;
  var propIsEnumerable = Object.prototype.propertyIsEnumerable;

  function toObject$1(val) {
    if (val === null || val === undefined) {
      throw new TypeError('Object.assign cannot be called with null or undefined');
    }

    return Object(val);
  }

  function shouldUseNative() {
    try {
      if (!Object.assign) {
        return false;
      } // Detect buggy property enumeration order in older V8 versions.
      // https://bugs.chromium.org/p/v8/issues/detail?id=4118


      var test1 = new String('abc'); // eslint-disable-line no-new-wrappers

      test1[5] = 'de';

      if (Object.getOwnPropertyNames(test1)[0] === '5') {
        return false;
      } // https://bugs.chromium.org/p/v8/issues/detail?id=3056


      var test2 = {};

      for (var i = 0; i < 10; i++) {
        test2['_' + String.fromCharCode(i)] = i;
      }

      var order2 = Object.getOwnPropertyNames(test2).map(function (n) {
        return test2[n];
      });

      if (order2.join('') !== '0123456789') {
        return false;
      } // https://bugs.chromium.org/p/v8/issues/detail?id=3056


      var test3 = {};
      'abcdefghijklmnopqrst'.split('').forEach(function (letter) {
        test3[letter] = letter;
      });

      if (Object.keys(Object.assign({}, test3)).join('') !== 'abcdefghijklmnopqrst') {
        return false;
      }

      return true;
    } catch (err) {
      // We don't expect any of the above to throw, but better to be safe.
      return false;
    }
  }

  var objectAssign = shouldUseNative() ? Object.assign : function (target, source) {
    var from;
    var to = toObject$1(target);
    var symbols;

    for (var s = 1; s < arguments.length; s++) {
      from = Object(arguments[s]);

      for (var key in from) {
        if (hasOwnProperty$1.call(from, key)) {
          to[key] = from[key];
        }
      }

      if (getOwnPropertySymbols) {
        symbols = getOwnPropertySymbols(from);

        for (var i = 0; i < symbols.length; i++) {
          if (propIsEnumerable.call(from, symbols[i])) {
            to[symbols[i]] = from[symbols[i]];
          }
        }
      }
    }

    return to;
  };

  var react_production_min = createCommonjsModule(function (module, exports) {

    var n = 60103,
        p = 60106;
    exports.Fragment = 60107;
    exports.StrictMode = 60108;
    exports.Profiler = 60114;
    var q = 60109,
        r = 60110,
        t = 60112;
    exports.Suspense = 60113;
    var u = 60115,
        v = 60116;

    if ("function" === typeof Symbol && Symbol.for) {
      var w = Symbol.for;
      n = w("react.element");
      p = w("react.portal");
      exports.Fragment = w("react.fragment");
      exports.StrictMode = w("react.strict_mode");
      exports.Profiler = w("react.profiler");
      q = w("react.provider");
      r = w("react.context");
      t = w("react.forward_ref");
      exports.Suspense = w("react.suspense");
      u = w("react.memo");
      v = w("react.lazy");
    }

    var x = "function" === typeof Symbol && Symbol.iterator;

    function y(a) {
      if (null === a || "object" !== typeof a) return null;
      a = x && a[x] || a["@@iterator"];
      return "function" === typeof a ? a : null;
    }

    function z(a) {
      for (var b = "https://reactjs.org/docs/error-decoder.html?invariant=" + a, c = 1; c < arguments.length; c++) b += "&args[]=" + encodeURIComponent(arguments[c]);

      return "Minified React error #" + a + "; visit " + b + " for the full message or use the non-minified dev environment for full errors and additional helpful warnings.";
    }

    var A = {
      isMounted: function () {
        return !1;
      },
      enqueueForceUpdate: function () {},
      enqueueReplaceState: function () {},
      enqueueSetState: function () {}
    },
        B = {};

    function C(a, b, c) {
      this.props = a;
      this.context = b;
      this.refs = B;
      this.updater = c || A;
    }

    C.prototype.isReactComponent = {};

    C.prototype.setState = function (a, b) {
      if ("object" !== typeof a && "function" !== typeof a && null != a) throw Error(z(85));
      this.updater.enqueueSetState(this, a, b, "setState");
    };

    C.prototype.forceUpdate = function (a) {
      this.updater.enqueueForceUpdate(this, a, "forceUpdate");
    };

    function D() {}

    D.prototype = C.prototype;

    function E(a, b, c) {
      this.props = a;
      this.context = b;
      this.refs = B;
      this.updater = c || A;
    }

    var F = E.prototype = new D();
    F.constructor = E;
    objectAssign(F, C.prototype);
    F.isPureReactComponent = !0;
    var G = {
      current: null
    },
        H = Object.prototype.hasOwnProperty,
        I = {
      key: !0,
      ref: !0,
      __self: !0,
      __source: !0
    };

    function J(a, b, c) {
      var e,
          d = {},
          k = null,
          h = null;
      if (null != b) for (e in void 0 !== b.ref && (h = b.ref), void 0 !== b.key && (k = "" + b.key), b) H.call(b, e) && !I.hasOwnProperty(e) && (d[e] = b[e]);
      var g = arguments.length - 2;
      if (1 === g) d.children = c;else if (1 < g) {
        for (var f = Array(g), m = 0; m < g; m++) f[m] = arguments[m + 2];

        d.children = f;
      }
      if (a && a.defaultProps) for (e in g = a.defaultProps, g) void 0 === d[e] && (d[e] = g[e]);
      return {
        $$typeof: n,
        type: a,
        key: k,
        ref: h,
        props: d,
        _owner: G.current
      };
    }

    function K(a, b) {
      return {
        $$typeof: n,
        type: a.type,
        key: b,
        ref: a.ref,
        props: a.props,
        _owner: a._owner
      };
    }

    function L(a) {
      return "object" === typeof a && null !== a && a.$$typeof === n;
    }

    function escape(a) {
      var b = {
        "=": "=0",
        ":": "=2"
      };
      return "$" + a.replace(/[=:]/g, function (a) {
        return b[a];
      });
    }

    var M = /\/+/g;

    function N(a, b) {
      return "object" === typeof a && null !== a && null != a.key ? escape("" + a.key) : b.toString(36);
    }

    function O(a, b, c, e, d) {
      var k = typeof a;
      if ("undefined" === k || "boolean" === k) a = null;
      var h = !1;
      if (null === a) h = !0;else switch (k) {
        case "string":
        case "number":
          h = !0;
          break;

        case "object":
          switch (a.$$typeof) {
            case n:
            case p:
              h = !0;
          }

      }
      if (h) return h = a, d = d(h), a = "" === e ? "." + N(h, 0) : e, Array.isArray(d) ? (c = "", null != a && (c = a.replace(M, "$&/") + "/"), O(d, b, c, "", function (a) {
        return a;
      })) : null != d && (L(d) && (d = K(d, c + (!d.key || h && h.key === d.key ? "" : ("" + d.key).replace(M, "$&/") + "/") + a)), b.push(d)), 1;
      h = 0;
      e = "" === e ? "." : e + ":";
      if (Array.isArray(a)) for (var g = 0; g < a.length; g++) {
        k = a[g];
        var f = e + N(k, g);
        h += O(k, b, c, f, d);
      } else if (f = y(a), "function" === typeof f) for (a = f.call(a), g = 0; !(k = a.next()).done;) k = k.value, f = e + N(k, g++), h += O(k, b, c, f, d);else if ("object" === k) throw b = "" + a, Error(z(31, "[object Object]" === b ? "object with keys {" + Object.keys(a).join(", ") + "}" : b));
      return h;
    }

    function P(a, b, c) {
      if (null == a) return a;
      var e = [],
          d = 0;
      O(a, e, "", "", function (a) {
        return b.call(c, a, d++);
      });
      return e;
    }

    function Q(a) {
      if (-1 === a._status) {
        var b = a._result;
        b = b();
        a._status = 0;
        a._result = b;
        b.then(function (b) {
          0 === a._status && (b = b.default, a._status = 1, a._result = b);
        }, function (b) {
          0 === a._status && (a._status = 2, a._result = b);
        });
      }

      if (1 === a._status) return a._result;
      throw a._result;
    }

    var R = {
      current: null
    };

    function S() {
      var a = R.current;
      if (null === a) throw Error(z(321));
      return a;
    }

    var T = {
      ReactCurrentDispatcher: R,
      ReactCurrentBatchConfig: {
        transition: 0
      },
      ReactCurrentOwner: G,
      IsSomeRendererActing: {
        current: !1
      },
      assign: objectAssign
    };
    exports.Children = {
      map: P,
      forEach: function (a, b, c) {
        P(a, function () {
          b.apply(this, arguments);
        }, c);
      },
      count: function (a) {
        var b = 0;
        P(a, function () {
          b++;
        });
        return b;
      },
      toArray: function (a) {
        return P(a, function (a) {
          return a;
        }) || [];
      },
      only: function (a) {
        if (!L(a)) throw Error(z(143));
        return a;
      }
    };
    exports.Component = C;
    exports.PureComponent = E;
    exports.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED = T;

    exports.cloneElement = function (a, b, c) {
      if (null === a || void 0 === a) throw Error(z(267, a));
      var e = objectAssign({}, a.props),
          d = a.key,
          k = a.ref,
          h = a._owner;

      if (null != b) {
        void 0 !== b.ref && (k = b.ref, h = G.current);
        void 0 !== b.key && (d = "" + b.key);
        if (a.type && a.type.defaultProps) var g = a.type.defaultProps;

        for (f in b) H.call(b, f) && !I.hasOwnProperty(f) && (e[f] = void 0 === b[f] && void 0 !== g ? g[f] : b[f]);
      }

      var f = arguments.length - 2;
      if (1 === f) e.children = c;else if (1 < f) {
        g = Array(f);

        for (var m = 0; m < f; m++) g[m] = arguments[m + 2];

        e.children = g;
      }
      return {
        $$typeof: n,
        type: a.type,
        key: d,
        ref: k,
        props: e,
        _owner: h
      };
    };

    exports.createContext = function (a, b) {
      void 0 === b && (b = null);
      a = {
        $$typeof: r,
        _calculateChangedBits: b,
        _currentValue: a,
        _currentValue2: a,
        _threadCount: 0,
        Provider: null,
        Consumer: null
      };
      a.Provider = {
        $$typeof: q,
        _context: a
      };
      return a.Consumer = a;
    };

    exports.createElement = J;

    exports.createFactory = function (a) {
      var b = J.bind(null, a);
      b.type = a;
      return b;
    };

    exports.createRef = function () {
      return {
        current: null
      };
    };

    exports.forwardRef = function (a) {
      return {
        $$typeof: t,
        render: a
      };
    };

    exports.isValidElement = L;

    exports.lazy = function (a) {
      return {
        $$typeof: v,
        _payload: {
          _status: -1,
          _result: a
        },
        _init: Q
      };
    };

    exports.memo = function (a, b) {
      return {
        $$typeof: u,
        type: a,
        compare: void 0 === b ? null : b
      };
    };

    exports.useCallback = function (a, b) {
      return S().useCallback(a, b);
    };

    exports.useContext = function (a, b) {
      return S().useContext(a, b);
    };

    exports.useDebugValue = function () {};

    exports.useEffect = function (a, b) {
      return S().useEffect(a, b);
    };

    exports.useImperativeHandle = function (a, b, c) {
      return S().useImperativeHandle(a, b, c);
    };

    exports.useLayoutEffect = function (a, b) {
      return S().useLayoutEffect(a, b);
    };

    exports.useMemo = function (a, b) {
      return S().useMemo(a, b);
    };

    exports.useReducer = function (a, b, c) {
      return S().useReducer(a, b, c);
    };

    exports.useRef = function (a) {
      return S().useRef(a);
    };

    exports.useState = function (a) {
      return S().useState(a);
    };

    exports.version = "17.0.2";
  });

  var react_development = createCommonjsModule(function (module, exports) {
  });

  var react = createCommonjsModule(function (module) {

    {
      module.exports = react_production_min;
    }
  });

  /******************************************************************************
  Copyright (c) Microsoft Corporation.

  Permission to use, copy, modify, and/or distribute this software for any
  purpose with or without fee is hereby granted.

  THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
  REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
  AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
  INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
  LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
  OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
  PERFORMANCE OF THIS SOFTWARE.
  ***************************************************************************** */

  /* global Reflect, Promise */
  var extendStatics = function (d, b) {
    extendStatics = Object.setPrototypeOf || {
      __proto__: []
    } instanceof Array && function (d, b) {
      d.__proto__ = b;
    } || function (d, b) {
      for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p];
    };

    return extendStatics(d, b);
  };

  function __extends(d, b) {
    if (typeof b !== "function" && b !== null) throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
    extendStatics(d, b);

    function __() {
      this.constructor = d;
    }

    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
  }
  var __assign = function () {
    __assign = Object.assign || function __assign(t) {
      for (var s, i = 1, n = arguments.length; i < n; i++) {
        s = arguments[i];

        for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
      }

      return t;
    };

    return __assign.apply(this, arguments);
  };
  function __rest(s, e) {
    var t = {};

    for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0) t[p] = s[p];

    if (s != null && typeof Object.getOwnPropertySymbols === "function") for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
      if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i])) t[p[i]] = s[p[i]];
    }
    return t;
  }
  function __spreadArray(to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
      if (ar || !(i in from)) {
        if (!ar) ar = Array.prototype.slice.call(from, 0, i);
        ar[i] = from[i];
      }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
  }

  /** @license React v16.13.1
   * react-is.production.min.js
   *
   * Copyright (c) Facebook, Inc. and its affiliates.
   *
   * This source code is licensed under the MIT license found in the
   * LICENSE file in the root directory of this source tree.
   */

  var b = "function" === typeof Symbol && Symbol.for,
      c = b ? Symbol.for("react.element") : 60103,
      d = b ? Symbol.for("react.portal") : 60106,
      e = b ? Symbol.for("react.fragment") : 60107,
      f$6 = b ? Symbol.for("react.strict_mode") : 60108,
      g = b ? Symbol.for("react.profiler") : 60114,
      h = b ? Symbol.for("react.provider") : 60109,
      k = b ? Symbol.for("react.context") : 60110,
      l = b ? Symbol.for("react.async_mode") : 60111,
      m = b ? Symbol.for("react.concurrent_mode") : 60111,
      n = b ? Symbol.for("react.forward_ref") : 60112,
      p = b ? Symbol.for("react.suspense") : 60113,
      q = b ? Symbol.for("react.suspense_list") : 60120,
      r = b ? Symbol.for("react.memo") : 60115,
      t = b ? Symbol.for("react.lazy") : 60116,
      v = b ? Symbol.for("react.block") : 60121,
      w = b ? Symbol.for("react.fundamental") : 60117,
      x = b ? Symbol.for("react.responder") : 60118,
      y = b ? Symbol.for("react.scope") : 60119;

  function z(a) {
    if ("object" === typeof a && null !== a) {
      var u = a.$$typeof;

      switch (u) {
        case c:
          switch (a = a.type, a) {
            case l:
            case m:
            case e:
            case g:
            case f$6:
            case p:
              return a;

            default:
              switch (a = a && a.$$typeof, a) {
                case k:
                case n:
                case t:
                case r:
                case h:
                  return a;

                default:
                  return u;
              }

          }

        case d:
          return u;
      }
    }
  }

  function A(a) {
    return z(a) === m;
  }

  var AsyncMode = l;
  var ConcurrentMode = m;
  var ContextConsumer = k;
  var ContextProvider = h;
  var Element = c;
  var ForwardRef = n;
  var Fragment = e;
  var Lazy = t;
  var Memo = r;
  var Portal = d;
  var Profiler = g;
  var StrictMode = f$6;
  var Suspense = p;

  var isAsyncMode = function (a) {
    return A(a) || z(a) === l;
  };

  var isConcurrentMode = A;

  var isContextConsumer = function (a) {
    return z(a) === k;
  };

  var isContextProvider = function (a) {
    return z(a) === h;
  };

  var isElement = function (a) {
    return "object" === typeof a && null !== a && a.$$typeof === c;
  };

  var isForwardRef = function (a) {
    return z(a) === n;
  };

  var isFragment = function (a) {
    return z(a) === e;
  };

  var isLazy = function (a) {
    return z(a) === t;
  };

  var isMemo = function (a) {
    return z(a) === r;
  };

  var isPortal = function (a) {
    return z(a) === d;
  };

  var isProfiler = function (a) {
    return z(a) === g;
  };

  var isStrictMode = function (a) {
    return z(a) === f$6;
  };

  var isSuspense = function (a) {
    return z(a) === p;
  };

  var isValidElementType = function (a) {
    return "string" === typeof a || "function" === typeof a || a === e || a === m || a === g || a === f$6 || a === p || a === q || "object" === typeof a && null !== a && (a.$$typeof === t || a.$$typeof === r || a.$$typeof === h || a.$$typeof === k || a.$$typeof === n || a.$$typeof === w || a.$$typeof === x || a.$$typeof === y || a.$$typeof === v);
  };

  var typeOf = z;
  var reactIs_production_min = {
    AsyncMode: AsyncMode,
    ConcurrentMode: ConcurrentMode,
    ContextConsumer: ContextConsumer,
    ContextProvider: ContextProvider,
    Element: Element,
    ForwardRef: ForwardRef,
    Fragment: Fragment,
    Lazy: Lazy,
    Memo: Memo,
    Portal: Portal,
    Profiler: Profiler,
    StrictMode: StrictMode,
    Suspense: Suspense,
    isAsyncMode: isAsyncMode,
    isConcurrentMode: isConcurrentMode,
    isContextConsumer: isContextConsumer,
    isContextProvider: isContextProvider,
    isElement: isElement,
    isForwardRef: isForwardRef,
    isFragment: isFragment,
    isLazy: isLazy,
    isMemo: isMemo,
    isPortal: isPortal,
    isProfiler: isProfiler,
    isStrictMode: isStrictMode,
    isSuspense: isSuspense,
    isValidElementType: isValidElementType,
    typeOf: typeOf
  };

  var reactIs_development = createCommonjsModule(function (module, exports) {
  });

  var reactIs = createCommonjsModule(function (module) {

    {
      module.exports = reactIs_production_min;
    }
  });

  /**
   * Cannot do Math.log(x) / Math.log(10) bc if IEEE floating point issue
   * @param x number
   */
  function invariant(condition, message, Err) {
    if (Err === void 0) {
      Err = Error;
    }

    if (!condition) {
      throw new Err(message);
    }
  }

  var ErrorKind;

  (function (ErrorKind) {
    /** Argument is unclosed (e.g. `{0`) */
    ErrorKind[ErrorKind["EXPECT_ARGUMENT_CLOSING_BRACE"] = 1] = "EXPECT_ARGUMENT_CLOSING_BRACE";
    /** Argument is empty (e.g. `{}`). */

    ErrorKind[ErrorKind["EMPTY_ARGUMENT"] = 2] = "EMPTY_ARGUMENT";
    /** Argument is malformed (e.g. `{foo!}``) */

    ErrorKind[ErrorKind["MALFORMED_ARGUMENT"] = 3] = "MALFORMED_ARGUMENT";
    /** Expect an argument type (e.g. `{foo,}`) */

    ErrorKind[ErrorKind["EXPECT_ARGUMENT_TYPE"] = 4] = "EXPECT_ARGUMENT_TYPE";
    /** Unsupported argument type (e.g. `{foo,foo}`) */

    ErrorKind[ErrorKind["INVALID_ARGUMENT_TYPE"] = 5] = "INVALID_ARGUMENT_TYPE";
    /** Expect an argument style (e.g. `{foo, number, }`) */

    ErrorKind[ErrorKind["EXPECT_ARGUMENT_STYLE"] = 6] = "EXPECT_ARGUMENT_STYLE";
    /** The number skeleton is invalid. */

    ErrorKind[ErrorKind["INVALID_NUMBER_SKELETON"] = 7] = "INVALID_NUMBER_SKELETON";
    /** The date time skeleton is invalid. */

    ErrorKind[ErrorKind["INVALID_DATE_TIME_SKELETON"] = 8] = "INVALID_DATE_TIME_SKELETON";
    /** Exepct a number skeleton following the `::` (e.g. `{foo, number, ::}`) */

    ErrorKind[ErrorKind["EXPECT_NUMBER_SKELETON"] = 9] = "EXPECT_NUMBER_SKELETON";
    /** Exepct a date time skeleton following the `::` (e.g. `{foo, date, ::}`) */

    ErrorKind[ErrorKind["EXPECT_DATE_TIME_SKELETON"] = 10] = "EXPECT_DATE_TIME_SKELETON";
    /** Unmatched apostrophes in the argument style (e.g. `{foo, number, 'test`) */

    ErrorKind[ErrorKind["UNCLOSED_QUOTE_IN_ARGUMENT_STYLE"] = 11] = "UNCLOSED_QUOTE_IN_ARGUMENT_STYLE";
    /** Missing select argument options (e.g. `{foo, select}`) */

    ErrorKind[ErrorKind["EXPECT_SELECT_ARGUMENT_OPTIONS"] = 12] = "EXPECT_SELECT_ARGUMENT_OPTIONS";
    /** Expecting an offset value in `plural` or `selectordinal` argument (e.g `{foo, plural, offset}`) */

    ErrorKind[ErrorKind["EXPECT_PLURAL_ARGUMENT_OFFSET_VALUE"] = 13] = "EXPECT_PLURAL_ARGUMENT_OFFSET_VALUE";
    /** Offset value in `plural` or `selectordinal` is invalid (e.g. `{foo, plural, offset: x}`) */

    ErrorKind[ErrorKind["INVALID_PLURAL_ARGUMENT_OFFSET_VALUE"] = 14] = "INVALID_PLURAL_ARGUMENT_OFFSET_VALUE";
    /** Expecting a selector in `select` argument (e.g `{foo, select}`) */

    ErrorKind[ErrorKind["EXPECT_SELECT_ARGUMENT_SELECTOR"] = 15] = "EXPECT_SELECT_ARGUMENT_SELECTOR";
    /** Expecting a selector in `plural` or `selectordinal` argument (e.g `{foo, plural}`) */

    ErrorKind[ErrorKind["EXPECT_PLURAL_ARGUMENT_SELECTOR"] = 16] = "EXPECT_PLURAL_ARGUMENT_SELECTOR";
    /** Expecting a message fragment after the `select` selector (e.g. `{foo, select, apple}`) */

    ErrorKind[ErrorKind["EXPECT_SELECT_ARGUMENT_SELECTOR_FRAGMENT"] = 17] = "EXPECT_SELECT_ARGUMENT_SELECTOR_FRAGMENT";
    /**
     * Expecting a message fragment after the `plural` or `selectordinal` selector
     * (e.g. `{foo, plural, one}`)
     */

    ErrorKind[ErrorKind["EXPECT_PLURAL_ARGUMENT_SELECTOR_FRAGMENT"] = 18] = "EXPECT_PLURAL_ARGUMENT_SELECTOR_FRAGMENT";
    /** Selector in `plural` or `selectordinal` is malformed (e.g. `{foo, plural, =x {#}}`) */

    ErrorKind[ErrorKind["INVALID_PLURAL_ARGUMENT_SELECTOR"] = 19] = "INVALID_PLURAL_ARGUMENT_SELECTOR";
    /**
     * Duplicate selectors in `plural` or `selectordinal` argument.
     * (e.g. {foo, plural, one {#} one {#}})
     */

    ErrorKind[ErrorKind["DUPLICATE_PLURAL_ARGUMENT_SELECTOR"] = 20] = "DUPLICATE_PLURAL_ARGUMENT_SELECTOR";
    /** Duplicate selectors in `select` argument.
     * (e.g. {foo, select, apple {apple} apple {apple}})
     */

    ErrorKind[ErrorKind["DUPLICATE_SELECT_ARGUMENT_SELECTOR"] = 21] = "DUPLICATE_SELECT_ARGUMENT_SELECTOR";
    /** Plural or select argument option must have `other` clause. */

    ErrorKind[ErrorKind["MISSING_OTHER_CLAUSE"] = 22] = "MISSING_OTHER_CLAUSE";
    /** The tag is malformed. (e.g. `<bold!>foo</bold!>) */

    ErrorKind[ErrorKind["INVALID_TAG"] = 23] = "INVALID_TAG";
    /** The tag name is invalid. (e.g. `<123>foo</123>`) */

    ErrorKind[ErrorKind["INVALID_TAG_NAME"] = 25] = "INVALID_TAG_NAME";
    /** The closing tag does not match the opening tag. (e.g. `<bold>foo</italic>`) */

    ErrorKind[ErrorKind["UNMATCHED_CLOSING_TAG"] = 26] = "UNMATCHED_CLOSING_TAG";
    /** The opening tag has unmatched closing tag. (e.g. `<bold>foo`) */

    ErrorKind[ErrorKind["UNCLOSED_TAG"] = 27] = "UNCLOSED_TAG";
  })(ErrorKind || (ErrorKind = {}));

  var TYPE;

  (function (TYPE) {
    /**
     * Raw text
     */
    TYPE[TYPE["literal"] = 0] = "literal";
    /**
     * Variable w/o any format, e.g `var` in `this is a {var}`
     */

    TYPE[TYPE["argument"] = 1] = "argument";
    /**
     * Variable w/ number format
     */

    TYPE[TYPE["number"] = 2] = "number";
    /**
     * Variable w/ date format
     */

    TYPE[TYPE["date"] = 3] = "date";
    /**
     * Variable w/ time format
     */

    TYPE[TYPE["time"] = 4] = "time";
    /**
     * Variable w/ select format
     */

    TYPE[TYPE["select"] = 5] = "select";
    /**
     * Variable w/ plural format
     */

    TYPE[TYPE["plural"] = 6] = "plural";
    /**
     * Only possible within plural argument.
     * This is the `#` symbol that will be substituted with the count.
     */

    TYPE[TYPE["pound"] = 7] = "pound";
    /**
     * XML-like tag
     */

    TYPE[TYPE["tag"] = 8] = "tag";
  })(TYPE || (TYPE = {}));

  var SKELETON_TYPE;

  (function (SKELETON_TYPE) {
    SKELETON_TYPE[SKELETON_TYPE["number"] = 0] = "number";
    SKELETON_TYPE[SKELETON_TYPE["dateTime"] = 1] = "dateTime";
  })(SKELETON_TYPE || (SKELETON_TYPE = {}));
  /**
   * Type Guards
   */


  function isLiteralElement(el) {
    return el.type === TYPE.literal;
  }
  function isArgumentElement(el) {
    return el.type === TYPE.argument;
  }
  function isNumberElement(el) {
    return el.type === TYPE.number;
  }
  function isDateElement(el) {
    return el.type === TYPE.date;
  }
  function isTimeElement(el) {
    return el.type === TYPE.time;
  }
  function isSelectElement(el) {
    return el.type === TYPE.select;
  }
  function isPluralElement(el) {
    return el.type === TYPE.plural;
  }
  function isPoundElement(el) {
    return el.type === TYPE.pound;
  }
  function isTagElement(el) {
    return el.type === TYPE.tag;
  }
  function isNumberSkeleton(el) {
    return !!(el && typeof el === 'object' && el.type === SKELETON_TYPE.number);
  }
  function isDateTimeSkeleton(el) {
    return !!(el && typeof el === 'object' && el.type === SKELETON_TYPE.dateTime);
  }

  // @generated from regex-gen.ts
  var SPACE_SEPARATOR_REGEX = /[ \xA0\u1680\u2000-\u200A\u202F\u205F\u3000]/;

  /**
   * https://unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table
   * Credit: https://github.com/caridy/intl-datetimeformat-pattern/blob/master/index.js
   * with some tweaks
   */
  var DATE_TIME_REGEX = /(?:[Eec]{1,6}|G{1,5}|[Qq]{1,5}|(?:[yYur]+|U{1,5})|[ML]{1,5}|d{1,2}|D{1,3}|F{1}|[abB]{1,5}|[hkHK]{1,2}|w{1,2}|W{1}|m{1,2}|s{1,2}|[zZOvVxX]{1,4})(?=([^']*'[^']*')*[^']*$)/g;
  /**
   * Parse Date time skeleton into Intl.DateTimeFormatOptions
   * Ref: https://unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table
   * @public
   * @param skeleton skeleton string
   */

  function parseDateTimeSkeleton(skeleton) {
    var result = {};
    skeleton.replace(DATE_TIME_REGEX, function (match) {
      var len = match.length;

      switch (match[0]) {
        // Era
        case 'G':
          result.era = len === 4 ? 'long' : len === 5 ? 'narrow' : 'short';
          break;
        // Year

        case 'y':
          result.year = len === 2 ? '2-digit' : 'numeric';
          break;

        case 'Y':
        case 'u':
        case 'U':
        case 'r':
          throw new RangeError('`Y/u/U/r` (year) patterns are not supported, use `y` instead');
        // Quarter

        case 'q':
        case 'Q':
          throw new RangeError('`q/Q` (quarter) patterns are not supported');
        // Month

        case 'M':
        case 'L':
          result.month = ['numeric', '2-digit', 'short', 'long', 'narrow'][len - 1];
          break;
        // Week

        case 'w':
        case 'W':
          throw new RangeError('`w/W` (week) patterns are not supported');

        case 'd':
          result.day = ['numeric', '2-digit'][len - 1];
          break;

        case 'D':
        case 'F':
        case 'g':
          throw new RangeError('`D/F/g` (day) patterns are not supported, use `d` instead');
        // Weekday

        case 'E':
          result.weekday = len === 4 ? 'short' : len === 5 ? 'narrow' : 'short';
          break;

        case 'e':
          if (len < 4) {
            throw new RangeError('`e..eee` (weekday) patterns are not supported');
          }

          result.weekday = ['short', 'long', 'narrow', 'short'][len - 4];
          break;

        case 'c':
          if (len < 4) {
            throw new RangeError('`c..ccc` (weekday) patterns are not supported');
          }

          result.weekday = ['short', 'long', 'narrow', 'short'][len - 4];
          break;
        // Period

        case 'a':
          // AM, PM
          result.hour12 = true;
          break;

        case 'b': // am, pm, noon, midnight

        case 'B':
          // flexible day periods
          throw new RangeError('`b/B` (period) patterns are not supported, use `a` instead');
        // Hour

        case 'h':
          result.hourCycle = 'h12';
          result.hour = ['numeric', '2-digit'][len - 1];
          break;

        case 'H':
          result.hourCycle = 'h23';
          result.hour = ['numeric', '2-digit'][len - 1];
          break;

        case 'K':
          result.hourCycle = 'h11';
          result.hour = ['numeric', '2-digit'][len - 1];
          break;

        case 'k':
          result.hourCycle = 'h24';
          result.hour = ['numeric', '2-digit'][len - 1];
          break;

        case 'j':
        case 'J':
        case 'C':
          throw new RangeError('`j/J/C` (hour) patterns are not supported, use `h/H/K/k` instead');
        // Minute

        case 'm':
          result.minute = ['numeric', '2-digit'][len - 1];
          break;
        // Second

        case 's':
          result.second = ['numeric', '2-digit'][len - 1];
          break;

        case 'S':
        case 'A':
          throw new RangeError('`S/A` (second) patterns are not supported, use `s` instead');
        // Zone

        case 'z':
          // 1..3, 4: specific non-location format
          result.timeZoneName = len < 4 ? 'short' : 'long';
          break;

        case 'Z': // 1..3, 4, 5: The ISO8601 varios formats

        case 'O': // 1, 4: miliseconds in day short, long

        case 'v': // 1, 4: generic non-location format

        case 'V': // 1, 2, 3, 4: time zone ID or city

        case 'X': // 1, 2, 3, 4: The ISO8601 varios formats

        case 'x':
          // 1, 2, 3, 4: The ISO8601 varios formats
          throw new RangeError('`Z/O/v/V/X/x` (timeZone) patterns are not supported, use `z` instead');
      }

      return '';
    });
    return result;
  }

  // @generated from regex-gen.ts
  var WHITE_SPACE_REGEX = /[\t-\r \x85\u200E\u200F\u2028\u2029]/i;

  function parseNumberSkeletonFromString(skeleton) {
    if (skeleton.length === 0) {
      throw new Error('Number skeleton cannot be empty');
    } // Parse the skeleton


    var stringTokens = skeleton.split(WHITE_SPACE_REGEX).filter(function (x) {
      return x.length > 0;
    });
    var tokens = [];

    for (var _i = 0, stringTokens_1 = stringTokens; _i < stringTokens_1.length; _i++) {
      var stringToken = stringTokens_1[_i];
      var stemAndOptions = stringToken.split('/');

      if (stemAndOptions.length === 0) {
        throw new Error('Invalid number skeleton');
      }

      var stem = stemAndOptions[0],
          options = stemAndOptions.slice(1);

      for (var _a = 0, options_1 = options; _a < options_1.length; _a++) {
        var option = options_1[_a];

        if (option.length === 0) {
          throw new Error('Invalid number skeleton');
        }
      }

      tokens.push({
        stem: stem,
        options: options
      });
    }

    return tokens;
  }

  function icuUnitToEcma(unit) {
    return unit.replace(/^(.*?)-/, '');
  }

  var FRACTION_PRECISION_REGEX = /^\.(?:(0+)(\*)?|(#+)|(0+)(#+))$/g;
  var SIGNIFICANT_PRECISION_REGEX = /^(@+)?(\+|#+)?[rs]?$/g;
  var INTEGER_WIDTH_REGEX = /(\*)(0+)|(#+)(0+)|(0+)/g;
  var CONCISE_INTEGER_WIDTH_REGEX = /^(0+)$/;

  function parseSignificantPrecision(str) {
    var result = {};

    if (str[str.length - 1] === 'r') {
      result.roundingPriority = 'morePrecision';
    } else if (str[str.length - 1] === 's') {
      result.roundingPriority = 'lessPrecision';
    }

    str.replace(SIGNIFICANT_PRECISION_REGEX, function (_, g1, g2) {
      // @@@ case
      if (typeof g2 !== 'string') {
        result.minimumSignificantDigits = g1.length;
        result.maximumSignificantDigits = g1.length;
      } // @@@+ case
      else if (g2 === '+') {
        result.minimumSignificantDigits = g1.length;
      } // .### case
      else if (g1[0] === '#') {
        result.maximumSignificantDigits = g1.length;
      } // .@@## or .@@@ case
      else {
        result.minimumSignificantDigits = g1.length;
        result.maximumSignificantDigits = g1.length + (typeof g2 === 'string' ? g2.length : 0);
      }

      return '';
    });
    return result;
  }

  function parseSign(str) {
    switch (str) {
      case 'sign-auto':
        return {
          signDisplay: 'auto'
        };

      case 'sign-accounting':
      case '()':
        return {
          currencySign: 'accounting'
        };

      case 'sign-always':
      case '+!':
        return {
          signDisplay: 'always'
        };

      case 'sign-accounting-always':
      case '()!':
        return {
          signDisplay: 'always',
          currencySign: 'accounting'
        };

      case 'sign-except-zero':
      case '+?':
        return {
          signDisplay: 'exceptZero'
        };

      case 'sign-accounting-except-zero':
      case '()?':
        return {
          signDisplay: 'exceptZero',
          currencySign: 'accounting'
        };

      case 'sign-never':
      case '+_':
        return {
          signDisplay: 'never'
        };
    }
  }

  function parseConciseScientificAndEngineeringStem(stem) {
    // Engineering
    var result;

    if (stem[0] === 'E' && stem[1] === 'E') {
      result = {
        notation: 'engineering'
      };
      stem = stem.slice(2);
    } else if (stem[0] === 'E') {
      result = {
        notation: 'scientific'
      };
      stem = stem.slice(1);
    }

    if (result) {
      var signDisplay = stem.slice(0, 2);

      if (signDisplay === '+!') {
        result.signDisplay = 'always';
        stem = stem.slice(2);
      } else if (signDisplay === '+?') {
        result.signDisplay = 'exceptZero';
        stem = stem.slice(2);
      }

      if (!CONCISE_INTEGER_WIDTH_REGEX.test(stem)) {
        throw new Error('Malformed concise eng/scientific notation');
      }

      result.minimumIntegerDigits = stem.length;
    }

    return result;
  }

  function parseNotationOptions(opt) {
    var result = {};
    var signOpts = parseSign(opt);

    if (signOpts) {
      return signOpts;
    }

    return result;
  }
  /**
   * https://github.com/unicode-org/icu/blob/master/docs/userguide/format_parse/numbers/skeletons.md#skeleton-stems-and-options
   */


  function parseNumberSkeleton(tokens) {
    var result = {};

    for (var _i = 0, tokens_1 = tokens; _i < tokens_1.length; _i++) {
      var token = tokens_1[_i];

      switch (token.stem) {
        case 'percent':
        case '%':
          result.style = 'percent';
          continue;

        case '%x100':
          result.style = 'percent';
          result.scale = 100;
          continue;

        case 'currency':
          result.style = 'currency';
          result.currency = token.options[0];
          continue;

        case 'group-off':
        case ',_':
          result.useGrouping = false;
          continue;

        case 'precision-integer':
        case '.':
          result.maximumFractionDigits = 0;
          continue;

        case 'measure-unit':
        case 'unit':
          result.style = 'unit';
          result.unit = icuUnitToEcma(token.options[0]);
          continue;

        case 'compact-short':
        case 'K':
          result.notation = 'compact';
          result.compactDisplay = 'short';
          continue;

        case 'compact-long':
        case 'KK':
          result.notation = 'compact';
          result.compactDisplay = 'long';
          continue;

        case 'scientific':
          result = __assign(__assign(__assign({}, result), {
            notation: 'scientific'
          }), token.options.reduce(function (all, opt) {
            return __assign(__assign({}, all), parseNotationOptions(opt));
          }, {}));
          continue;

        case 'engineering':
          result = __assign(__assign(__assign({}, result), {
            notation: 'engineering'
          }), token.options.reduce(function (all, opt) {
            return __assign(__assign({}, all), parseNotationOptions(opt));
          }, {}));
          continue;

        case 'notation-simple':
          result.notation = 'standard';
          continue;
        // https://github.com/unicode-org/icu/blob/master/icu4c/source/i18n/unicode/unumberformatter.h

        case 'unit-width-narrow':
          result.currencyDisplay = 'narrowSymbol';
          result.unitDisplay = 'narrow';
          continue;

        case 'unit-width-short':
          result.currencyDisplay = 'code';
          result.unitDisplay = 'short';
          continue;

        case 'unit-width-full-name':
          result.currencyDisplay = 'name';
          result.unitDisplay = 'long';
          continue;

        case 'unit-width-iso-code':
          result.currencyDisplay = 'symbol';
          continue;

        case 'scale':
          result.scale = parseFloat(token.options[0]);
          continue;
        // https://unicode-org.github.io/icu/userguide/format_parse/numbers/skeletons.html#integer-width

        case 'integer-width':
          if (token.options.length > 1) {
            throw new RangeError('integer-width stems only accept a single optional option');
          }

          token.options[0].replace(INTEGER_WIDTH_REGEX, function (_, g1, g2, g3, g4, g5) {
            if (g1) {
              result.minimumIntegerDigits = g2.length;
            } else if (g3 && g4) {
              throw new Error('We currently do not support maximum integer digits');
            } else if (g5) {
              throw new Error('We currently do not support exact integer digits');
            }

            return '';
          });
          continue;
      } // https://unicode-org.github.io/icu/userguide/format_parse/numbers/skeletons.html#integer-width


      if (CONCISE_INTEGER_WIDTH_REGEX.test(token.stem)) {
        result.minimumIntegerDigits = token.stem.length;
        continue;
      }

      if (FRACTION_PRECISION_REGEX.test(token.stem)) {
        // Precision
        // https://unicode-org.github.io/icu/userguide/format_parse/numbers/skeletons.html#fraction-precision
        // precision-integer case
        if (token.options.length > 1) {
          throw new RangeError('Fraction-precision stems only accept a single optional option');
        }

        token.stem.replace(FRACTION_PRECISION_REGEX, function (_, g1, g2, g3, g4, g5) {
          // .000* case (before ICU67 it was .000+)
          if (g2 === '*') {
            result.minimumFractionDigits = g1.length;
          } // .### case
          else if (g3 && g3[0] === '#') {
            result.maximumFractionDigits = g3.length;
          } // .00## case
          else if (g4 && g5) {
            result.minimumFractionDigits = g4.length;
            result.maximumFractionDigits = g4.length + g5.length;
          } else {
            result.minimumFractionDigits = g1.length;
            result.maximumFractionDigits = g1.length;
          }

          return '';
        });
        var opt = token.options[0]; // https://unicode-org.github.io/icu/userguide/format_parse/numbers/skeletons.html#trailing-zero-display

        if (opt === 'w') {
          result = __assign(__assign({}, result), {
            trailingZeroDisplay: 'stripIfInteger'
          });
        } else if (opt) {
          result = __assign(__assign({}, result), parseSignificantPrecision(opt));
        }

        continue;
      } // https://unicode-org.github.io/icu/userguide/format_parse/numbers/skeletons.html#significant-digits-precision


      if (SIGNIFICANT_PRECISION_REGEX.test(token.stem)) {
        result = __assign(__assign({}, result), parseSignificantPrecision(token.stem));
        continue;
      }

      var signOpts = parseSign(token.stem);

      if (signOpts) {
        result = __assign(__assign({}, result), signOpts);
      }

      var conciseScientificAndEngineeringOpts = parseConciseScientificAndEngineeringStem(token.stem);

      if (conciseScientificAndEngineeringOpts) {
        result = __assign(__assign({}, result), conciseScientificAndEngineeringOpts);
      }
    }

    return result;
  }

  // @generated from time-data-gen.ts
  // prettier-ignore  
  var timeData = {
    "AX": ["H"],
    "BQ": ["H"],
    "CP": ["H"],
    "CZ": ["H"],
    "DK": ["H"],
    "FI": ["H"],
    "ID": ["H"],
    "IS": ["H"],
    "ML": ["H"],
    "NE": ["H"],
    "RU": ["H"],
    "SE": ["H"],
    "SJ": ["H"],
    "SK": ["H"],
    "AS": ["h", "H"],
    "BT": ["h", "H"],
    "DJ": ["h", "H"],
    "ER": ["h", "H"],
    "GH": ["h", "H"],
    "IN": ["h", "H"],
    "LS": ["h", "H"],
    "PG": ["h", "H"],
    "PW": ["h", "H"],
    "SO": ["h", "H"],
    "TO": ["h", "H"],
    "VU": ["h", "H"],
    "WS": ["h", "H"],
    "001": ["H", "h"],
    "AL": ["h", "H", "hB"],
    "TD": ["h", "H", "hB"],
    "ca-ES": ["H", "h", "hB"],
    "CF": ["H", "h", "hB"],
    "CM": ["H", "h", "hB"],
    "fr-CA": ["H", "h", "hB"],
    "gl-ES": ["H", "h", "hB"],
    "it-CH": ["H", "h", "hB"],
    "it-IT": ["H", "h", "hB"],
    "LU": ["H", "h", "hB"],
    "NP": ["H", "h", "hB"],
    "PF": ["H", "h", "hB"],
    "SC": ["H", "h", "hB"],
    "SM": ["H", "h", "hB"],
    "SN": ["H", "h", "hB"],
    "TF": ["H", "h", "hB"],
    "VA": ["H", "h", "hB"],
    "CY": ["h", "H", "hb", "hB"],
    "GR": ["h", "H", "hb", "hB"],
    "CO": ["h", "H", "hB", "hb"],
    "DO": ["h", "H", "hB", "hb"],
    "KP": ["h", "H", "hB", "hb"],
    "KR": ["h", "H", "hB", "hb"],
    "NA": ["h", "H", "hB", "hb"],
    "PA": ["h", "H", "hB", "hb"],
    "PR": ["h", "H", "hB", "hb"],
    "VE": ["h", "H", "hB", "hb"],
    "AC": ["H", "h", "hb", "hB"],
    "AI": ["H", "h", "hb", "hB"],
    "BW": ["H", "h", "hb", "hB"],
    "BZ": ["H", "h", "hb", "hB"],
    "CC": ["H", "h", "hb", "hB"],
    "CK": ["H", "h", "hb", "hB"],
    "CX": ["H", "h", "hb", "hB"],
    "DG": ["H", "h", "hb", "hB"],
    "FK": ["H", "h", "hb", "hB"],
    "GB": ["H", "h", "hb", "hB"],
    "GG": ["H", "h", "hb", "hB"],
    "GI": ["H", "h", "hb", "hB"],
    "IE": ["H", "h", "hb", "hB"],
    "IM": ["H", "h", "hb", "hB"],
    "IO": ["H", "h", "hb", "hB"],
    "JE": ["H", "h", "hb", "hB"],
    "LT": ["H", "h", "hb", "hB"],
    "MK": ["H", "h", "hb", "hB"],
    "MN": ["H", "h", "hb", "hB"],
    "MS": ["H", "h", "hb", "hB"],
    "NF": ["H", "h", "hb", "hB"],
    "NG": ["H", "h", "hb", "hB"],
    "NR": ["H", "h", "hb", "hB"],
    "NU": ["H", "h", "hb", "hB"],
    "PN": ["H", "h", "hb", "hB"],
    "SH": ["H", "h", "hb", "hB"],
    "SX": ["H", "h", "hb", "hB"],
    "TA": ["H", "h", "hb", "hB"],
    "ZA": ["H", "h", "hb", "hB"],
    "af-ZA": ["H", "h", "hB", "hb"],
    "AR": ["H", "h", "hB", "hb"],
    "CL": ["H", "h", "hB", "hb"],
    "CR": ["H", "h", "hB", "hb"],
    "CU": ["H", "h", "hB", "hb"],
    "EA": ["H", "h", "hB", "hb"],
    "es-BO": ["H", "h", "hB", "hb"],
    "es-BR": ["H", "h", "hB", "hb"],
    "es-EC": ["H", "h", "hB", "hb"],
    "es-ES": ["H", "h", "hB", "hb"],
    "es-GQ": ["H", "h", "hB", "hb"],
    "es-PE": ["H", "h", "hB", "hb"],
    "GT": ["H", "h", "hB", "hb"],
    "HN": ["H", "h", "hB", "hb"],
    "IC": ["H", "h", "hB", "hb"],
    "KG": ["H", "h", "hB", "hb"],
    "KM": ["H", "h", "hB", "hb"],
    "LK": ["H", "h", "hB", "hb"],
    "MA": ["H", "h", "hB", "hb"],
    "MX": ["H", "h", "hB", "hb"],
    "NI": ["H", "h", "hB", "hb"],
    "PY": ["H", "h", "hB", "hb"],
    "SV": ["H", "h", "hB", "hb"],
    "UY": ["H", "h", "hB", "hb"],
    "JP": ["H", "h", "K"],
    "AD": ["H", "hB"],
    "AM": ["H", "hB"],
    "AO": ["H", "hB"],
    "AT": ["H", "hB"],
    "AW": ["H", "hB"],
    "BE": ["H", "hB"],
    "BF": ["H", "hB"],
    "BJ": ["H", "hB"],
    "BL": ["H", "hB"],
    "BR": ["H", "hB"],
    "CG": ["H", "hB"],
    "CI": ["H", "hB"],
    "CV": ["H", "hB"],
    "DE": ["H", "hB"],
    "EE": ["H", "hB"],
    "FR": ["H", "hB"],
    "GA": ["H", "hB"],
    "GF": ["H", "hB"],
    "GN": ["H", "hB"],
    "GP": ["H", "hB"],
    "GW": ["H", "hB"],
    "HR": ["H", "hB"],
    "IL": ["H", "hB"],
    "IT": ["H", "hB"],
    "KZ": ["H", "hB"],
    "MC": ["H", "hB"],
    "MD": ["H", "hB"],
    "MF": ["H", "hB"],
    "MQ": ["H", "hB"],
    "MZ": ["H", "hB"],
    "NC": ["H", "hB"],
    "NL": ["H", "hB"],
    "PM": ["H", "hB"],
    "PT": ["H", "hB"],
    "RE": ["H", "hB"],
    "RO": ["H", "hB"],
    "SI": ["H", "hB"],
    "SR": ["H", "hB"],
    "ST": ["H", "hB"],
    "TG": ["H", "hB"],
    "TR": ["H", "hB"],
    "WF": ["H", "hB"],
    "YT": ["H", "hB"],
    "BD": ["h", "hB", "H"],
    "PK": ["h", "hB", "H"],
    "AZ": ["H", "hB", "h"],
    "BA": ["H", "hB", "h"],
    "BG": ["H", "hB", "h"],
    "CH": ["H", "hB", "h"],
    "GE": ["H", "hB", "h"],
    "LI": ["H", "hB", "h"],
    "ME": ["H", "hB", "h"],
    "RS": ["H", "hB", "h"],
    "UA": ["H", "hB", "h"],
    "UZ": ["H", "hB", "h"],
    "XK": ["H", "hB", "h"],
    "AG": ["h", "hb", "H", "hB"],
    "AU": ["h", "hb", "H", "hB"],
    "BB": ["h", "hb", "H", "hB"],
    "BM": ["h", "hb", "H", "hB"],
    "BS": ["h", "hb", "H", "hB"],
    "CA": ["h", "hb", "H", "hB"],
    "DM": ["h", "hb", "H", "hB"],
    "en-001": ["h", "hb", "H", "hB"],
    "FJ": ["h", "hb", "H", "hB"],
    "FM": ["h", "hb", "H", "hB"],
    "GD": ["h", "hb", "H", "hB"],
    "GM": ["h", "hb", "H", "hB"],
    "GU": ["h", "hb", "H", "hB"],
    "GY": ["h", "hb", "H", "hB"],
    "JM": ["h", "hb", "H", "hB"],
    "KI": ["h", "hb", "H", "hB"],
    "KN": ["h", "hb", "H", "hB"],
    "KY": ["h", "hb", "H", "hB"],
    "LC": ["h", "hb", "H", "hB"],
    "LR": ["h", "hb", "H", "hB"],
    "MH": ["h", "hb", "H", "hB"],
    "MP": ["h", "hb", "H", "hB"],
    "MW": ["h", "hb", "H", "hB"],
    "NZ": ["h", "hb", "H", "hB"],
    "SB": ["h", "hb", "H", "hB"],
    "SG": ["h", "hb", "H", "hB"],
    "SL": ["h", "hb", "H", "hB"],
    "SS": ["h", "hb", "H", "hB"],
    "SZ": ["h", "hb", "H", "hB"],
    "TC": ["h", "hb", "H", "hB"],
    "TT": ["h", "hb", "H", "hB"],
    "UM": ["h", "hb", "H", "hB"],
    "US": ["h", "hb", "H", "hB"],
    "VC": ["h", "hb", "H", "hB"],
    "VG": ["h", "hb", "H", "hB"],
    "VI": ["h", "hb", "H", "hB"],
    "ZM": ["h", "hb", "H", "hB"],
    "BO": ["H", "hB", "h", "hb"],
    "EC": ["H", "hB", "h", "hb"],
    "ES": ["H", "hB", "h", "hb"],
    "GQ": ["H", "hB", "h", "hb"],
    "PE": ["H", "hB", "h", "hb"],
    "AE": ["h", "hB", "hb", "H"],
    "ar-001": ["h", "hB", "hb", "H"],
    "BH": ["h", "hB", "hb", "H"],
    "DZ": ["h", "hB", "hb", "H"],
    "EG": ["h", "hB", "hb", "H"],
    "EH": ["h", "hB", "hb", "H"],
    "HK": ["h", "hB", "hb", "H"],
    "IQ": ["h", "hB", "hb", "H"],
    "JO": ["h", "hB", "hb", "H"],
    "KW": ["h", "hB", "hb", "H"],
    "LB": ["h", "hB", "hb", "H"],
    "LY": ["h", "hB", "hb", "H"],
    "MO": ["h", "hB", "hb", "H"],
    "MR": ["h", "hB", "hb", "H"],
    "OM": ["h", "hB", "hb", "H"],
    "PH": ["h", "hB", "hb", "H"],
    "PS": ["h", "hB", "hb", "H"],
    "QA": ["h", "hB", "hb", "H"],
    "SA": ["h", "hB", "hb", "H"],
    "SD": ["h", "hB", "hb", "H"],
    "SY": ["h", "hB", "hb", "H"],
    "TN": ["h", "hB", "hb", "H"],
    "YE": ["h", "hB", "hb", "H"],
    "AF": ["H", "hb", "hB", "h"],
    "LA": ["H", "hb", "hB", "h"],
    "CN": ["H", "hB", "hb", "h"],
    "LV": ["H", "hB", "hb", "h"],
    "TL": ["H", "hB", "hb", "h"],
    "zu-ZA": ["H", "hB", "hb", "h"],
    "CD": ["hB", "H"],
    "IR": ["hB", "H"],
    "hi-IN": ["hB", "h", "H"],
    "kn-IN": ["hB", "h", "H"],
    "ml-IN": ["hB", "h", "H"],
    "te-IN": ["hB", "h", "H"],
    "KH": ["hB", "h", "H", "hb"],
    "ta-IN": ["hB", "h", "hb", "H"],
    "BN": ["hb", "hB", "h", "H"],
    "MY": ["hb", "hB", "h", "H"],
    "ET": ["hB", "hb", "h", "H"],
    "gu-IN": ["hB", "hb", "h", "H"],
    "mr-IN": ["hB", "hb", "h", "H"],
    "pa-IN": ["hB", "hb", "h", "H"],
    "TW": ["hB", "hb", "h", "H"],
    "KE": ["hB", "hb", "H", "h"],
    "MM": ["hB", "hb", "H", "h"],
    "TZ": ["hB", "hb", "H", "h"],
    "UG": ["hB", "hb", "H", "h"]
  };

  /**
   * Returns the best matching date time pattern if a date time skeleton
   * pattern is provided with a locale. Follows the Unicode specification:
   * https://www.unicode.org/reports/tr35/tr35-dates.html#table-mapping-requested-time-skeletons-to-patterns
   * @param skeleton date time skeleton pattern that possibly includes j, J or C
   * @param locale
   */

  function getBestPattern(skeleton, locale) {
    var skeletonCopy = '';

    for (var patternPos = 0; patternPos < skeleton.length; patternPos++) {
      var patternChar = skeleton.charAt(patternPos);

      if (patternChar === 'j') {
        var extraLength = 0;

        while (patternPos + 1 < skeleton.length && skeleton.charAt(patternPos + 1) === patternChar) {
          extraLength++;
          patternPos++;
        }

        var hourLen = 1 + (extraLength & 1);
        var dayPeriodLen = extraLength < 2 ? 1 : 3 + (extraLength >> 1);
        var dayPeriodChar = 'a';
        var hourChar = getDefaultHourSymbolFromLocale(locale);

        if (hourChar == 'H' || hourChar == 'k') {
          dayPeriodLen = 0;
        }

        while (dayPeriodLen-- > 0) {
          skeletonCopy += dayPeriodChar;
        }

        while (hourLen-- > 0) {
          skeletonCopy = hourChar + skeletonCopy;
        }
      } else if (patternChar === 'J') {
        skeletonCopy += 'H';
      } else {
        skeletonCopy += patternChar;
      }
    }

    return skeletonCopy;
  }
  /**
   * Maps the [hour cycle type](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/Locale/hourCycle)
   * of the given `locale` to the corresponding time pattern.
   * @param locale
   */

  function getDefaultHourSymbolFromLocale(locale) {
    var hourCycle = locale.hourCycle;

    if (hourCycle === undefined && // @ts-ignore hourCycle(s) is not identified yet
    locale.hourCycles && // @ts-ignore
    locale.hourCycles.length) {
      // @ts-ignore
      hourCycle = locale.hourCycles[0];
    }

    if (hourCycle) {
      switch (hourCycle) {
        case 'h24':
          return 'k';

        case 'h23':
          return 'H';

        case 'h12':
          return 'h';

        case 'h11':
          return 'K';

        default:
          throw new Error('Invalid hourCycle');
      }
    } // TODO: Once hourCycle is fully supported remove the following with data generation


    var languageTag = locale.language;
    var regionTag;

    if (languageTag !== 'root') {
      regionTag = locale.maximize().region;
    }

    var hourCycles = timeData[regionTag || ''] || timeData[languageTag || ''] || timeData["".concat(languageTag, "-001")] || timeData['001'];
    return hourCycles[0];
  }

  var _a;
  var SPACE_SEPARATOR_START_REGEX = new RegExp("^".concat(SPACE_SEPARATOR_REGEX.source, "*"));
  var SPACE_SEPARATOR_END_REGEX = new RegExp("".concat(SPACE_SEPARATOR_REGEX.source, "*$"));

  function createLocation(start, end) {
    return {
      start: start,
      end: end
    };
  } // #region Ponyfills
  // Consolidate these variables up top for easier toggling during debugging


  var hasNativeStartsWith = !!String.prototype.startsWith;
  var hasNativeFromCodePoint = !!String.fromCodePoint;
  var hasNativeFromEntries = !!Object.fromEntries;
  var hasNativeCodePointAt = !!String.prototype.codePointAt;
  var hasTrimStart = !!String.prototype.trimStart;
  var hasTrimEnd = !!String.prototype.trimEnd;
  var hasNativeIsSafeInteger = !!Number.isSafeInteger;
  var isSafeInteger = hasNativeIsSafeInteger ? Number.isSafeInteger : function (n) {
    return typeof n === 'number' && isFinite(n) && Math.floor(n) === n && Math.abs(n) <= 0x1fffffffffffff;
  }; // IE11 does not support y and u.

  var REGEX_SUPPORTS_U_AND_Y = true;

  try {
    var re = RE('([^\\p{White_Space}\\p{Pattern_Syntax}]*)', 'yu');
    /**
     * legacy Edge or Xbox One browser
     * Unicode flag support: supported
     * Pattern_Syntax support: not supported
     * See https://github.com/formatjs/formatjs/issues/2822
     */

    REGEX_SUPPORTS_U_AND_Y = ((_a = re.exec('a')) === null || _a === void 0 ? void 0 : _a[0]) === 'a';
  } catch (_) {
    REGEX_SUPPORTS_U_AND_Y = false;
  }

  var startsWith = hasNativeStartsWith ? // Native
  function startsWith(s, search, position) {
    return s.startsWith(search, position);
  } : // For IE11
  function startsWith(s, search, position) {
    return s.slice(position, position + search.length) === search;
  };
  var fromCodePoint = hasNativeFromCodePoint ? String.fromCodePoint : // IE11
  function fromCodePoint() {
    var codePoints = [];

    for (var _i = 0; _i < arguments.length; _i++) {
      codePoints[_i] = arguments[_i];
    }

    var elements = '';
    var length = codePoints.length;
    var i = 0;
    var code;

    while (length > i) {
      code = codePoints[i++];
      if (code > 0x10ffff) throw RangeError(code + ' is not a valid code point');
      elements += code < 0x10000 ? String.fromCharCode(code) : String.fromCharCode(((code -= 0x10000) >> 10) + 0xd800, code % 0x400 + 0xdc00);
    }

    return elements;
  };
  var fromEntries = // native
  hasNativeFromEntries ? Object.fromEntries : // Ponyfill
  function fromEntries(entries) {
    var obj = {};

    for (var _i = 0, entries_1 = entries; _i < entries_1.length; _i++) {
      var _a = entries_1[_i],
          k = _a[0],
          v = _a[1];
      obj[k] = v;
    }

    return obj;
  };
  var codePointAt = hasNativeCodePointAt ? // Native
  function codePointAt(s, index) {
    return s.codePointAt(index);
  } : // IE 11
  function codePointAt(s, index) {
    var size = s.length;

    if (index < 0 || index >= size) {
      return undefined;
    }

    var first = s.charCodeAt(index);
    var second;
    return first < 0xd800 || first > 0xdbff || index + 1 === size || (second = s.charCodeAt(index + 1)) < 0xdc00 || second > 0xdfff ? first : (first - 0xd800 << 10) + (second - 0xdc00) + 0x10000;
  };
  var trimStart = hasTrimStart ? // Native
  function trimStart(s) {
    return s.trimStart();
  } : // Ponyfill
  function trimStart(s) {
    return s.replace(SPACE_SEPARATOR_START_REGEX, '');
  };
  var trimEnd = hasTrimEnd ? // Native
  function trimEnd(s) {
    return s.trimEnd();
  } : // Ponyfill
  function trimEnd(s) {
    return s.replace(SPACE_SEPARATOR_END_REGEX, '');
  }; // Prevent minifier to translate new RegExp to literal form that might cause syntax error on IE11.

  function RE(s, flag) {
    return new RegExp(s, flag);
  } // #endregion


  var matchIdentifierAtIndex;

  if (REGEX_SUPPORTS_U_AND_Y) {
    // Native
    var IDENTIFIER_PREFIX_RE_1 = RE('([^\\p{White_Space}\\p{Pattern_Syntax}]*)', 'yu');

    matchIdentifierAtIndex = function matchIdentifierAtIndex(s, index) {
      var _a;

      IDENTIFIER_PREFIX_RE_1.lastIndex = index;
      var match = IDENTIFIER_PREFIX_RE_1.exec(s);
      return (_a = match[1]) !== null && _a !== void 0 ? _a : '';
    };
  } else {
    // IE11
    matchIdentifierAtIndex = function matchIdentifierAtIndex(s, index) {
      var match = [];

      while (true) {
        var c = codePointAt(s, index);

        if (c === undefined || _isWhiteSpace(c) || _isPatternSyntax(c)) {
          break;
        }

        match.push(c);
        index += c >= 0x10000 ? 2 : 1;
      }

      return fromCodePoint.apply(void 0, match);
    };
  }

  var Parser =
  /** @class */
  function () {
    function Parser(message, options) {
      if (options === void 0) {
        options = {};
      }

      this.message = message;
      this.position = {
        offset: 0,
        line: 1,
        column: 1
      };
      this.ignoreTag = !!options.ignoreTag;
      this.locale = options.locale;
      this.requiresOtherClause = !!options.requiresOtherClause;
      this.shouldParseSkeletons = !!options.shouldParseSkeletons;
    }

    Parser.prototype.parse = function () {
      if (this.offset() !== 0) {
        throw Error('parser can only be used once');
      }

      return this.parseMessage(0, '', false);
    };

    Parser.prototype.parseMessage = function (nestingLevel, parentArgType, expectingCloseTag) {
      var elements = [];

      while (!this.isEOF()) {
        var char = this.char();

        if (char === 123
        /* `{` */
        ) {
          var result = this.parseArgument(nestingLevel, expectingCloseTag);

          if (result.err) {
            return result;
          }

          elements.push(result.val);
        } else if (char === 125
        /* `}` */
        && nestingLevel > 0) {
          break;
        } else if (char === 35
        /* `#` */
        && (parentArgType === 'plural' || parentArgType === 'selectordinal')) {
          var position = this.clonePosition();
          this.bump();
          elements.push({
            type: TYPE.pound,
            location: createLocation(position, this.clonePosition())
          });
        } else if (char === 60
        /* `<` */
        && !this.ignoreTag && this.peek() === 47 // char code for '/'
        ) {
          if (expectingCloseTag) {
            break;
          } else {
            return this.error(ErrorKind.UNMATCHED_CLOSING_TAG, createLocation(this.clonePosition(), this.clonePosition()));
          }
        } else if (char === 60
        /* `<` */
        && !this.ignoreTag && _isAlpha(this.peek() || 0)) {
          var result = this.parseTag(nestingLevel, parentArgType);

          if (result.err) {
            return result;
          }

          elements.push(result.val);
        } else {
          var result = this.parseLiteral(nestingLevel, parentArgType);

          if (result.err) {
            return result;
          }

          elements.push(result.val);
        }
      }

      return {
        val: elements,
        err: null
      };
    };
    /**
     * A tag name must start with an ASCII lower/upper case letter. The grammar is based on the
     * [custom element name][] except that a dash is NOT always mandatory and uppercase letters
     * are accepted:
     *
     * ```
     * tag ::= "<" tagName (whitespace)* "/>" | "<" tagName (whitespace)* ">" message "</" tagName (whitespace)* ">"
     * tagName ::= [a-z] (PENChar)*
     * PENChar ::=
     *     "-" | "." | [0-9] | "_" | [a-z] | [A-Z] | #xB7 | [#xC0-#xD6] | [#xD8-#xF6] | [#xF8-#x37D] |
     *     [#x37F-#x1FFF] | [#x200C-#x200D] | [#x203F-#x2040] | [#x2070-#x218F] | [#x2C00-#x2FEF] |
     *     [#x3001-#xD7FF] | [#xF900-#xFDCF] | [#xFDF0-#xFFFD] | [#x10000-#xEFFFF]
     * ```
     *
     * [custom element name]: https://html.spec.whatwg.org/multipage/custom-elements.html#valid-custom-element-name
     * NOTE: We're a bit more lax here since HTML technically does not allow uppercase HTML element but we do
     * since other tag-based engines like React allow it
     */


    Parser.prototype.parseTag = function (nestingLevel, parentArgType) {
      var startPosition = this.clonePosition();
      this.bump(); // `<`

      var tagName = this.parseTagName();
      this.bumpSpace();

      if (this.bumpIf('/>')) {
        // Self closing tag
        return {
          val: {
            type: TYPE.literal,
            value: "<".concat(tagName, "/>"),
            location: createLocation(startPosition, this.clonePosition())
          },
          err: null
        };
      } else if (this.bumpIf('>')) {
        var childrenResult = this.parseMessage(nestingLevel + 1, parentArgType, true);

        if (childrenResult.err) {
          return childrenResult;
        }

        var children = childrenResult.val; // Expecting a close tag

        var endTagStartPosition = this.clonePosition();

        if (this.bumpIf('</')) {
          if (this.isEOF() || !_isAlpha(this.char())) {
            return this.error(ErrorKind.INVALID_TAG, createLocation(endTagStartPosition, this.clonePosition()));
          }

          var closingTagNameStartPosition = this.clonePosition();
          var closingTagName = this.parseTagName();

          if (tagName !== closingTagName) {
            return this.error(ErrorKind.UNMATCHED_CLOSING_TAG, createLocation(closingTagNameStartPosition, this.clonePosition()));
          }

          this.bumpSpace();

          if (!this.bumpIf('>')) {
            return this.error(ErrorKind.INVALID_TAG, createLocation(endTagStartPosition, this.clonePosition()));
          }

          return {
            val: {
              type: TYPE.tag,
              value: tagName,
              children: children,
              location: createLocation(startPosition, this.clonePosition())
            },
            err: null
          };
        } else {
          return this.error(ErrorKind.UNCLOSED_TAG, createLocation(startPosition, this.clonePosition()));
        }
      } else {
        return this.error(ErrorKind.INVALID_TAG, createLocation(startPosition, this.clonePosition()));
      }
    };
    /**
     * This method assumes that the caller has peeked ahead for the first tag character.
     */


    Parser.prototype.parseTagName = function () {
      var startOffset = this.offset();
      this.bump(); // the first tag name character

      while (!this.isEOF() && _isPotentialElementNameChar(this.char())) {
        this.bump();
      }

      return this.message.slice(startOffset, this.offset());
    };

    Parser.prototype.parseLiteral = function (nestingLevel, parentArgType) {
      var start = this.clonePosition();
      var value = '';

      while (true) {
        var parseQuoteResult = this.tryParseQuote(parentArgType);

        if (parseQuoteResult) {
          value += parseQuoteResult;
          continue;
        }

        var parseUnquotedResult = this.tryParseUnquoted(nestingLevel, parentArgType);

        if (parseUnquotedResult) {
          value += parseUnquotedResult;
          continue;
        }

        var parseLeftAngleResult = this.tryParseLeftAngleBracket();

        if (parseLeftAngleResult) {
          value += parseLeftAngleResult;
          continue;
        }

        break;
      }

      var location = createLocation(start, this.clonePosition());
      return {
        val: {
          type: TYPE.literal,
          value: value,
          location: location
        },
        err: null
      };
    };

    Parser.prototype.tryParseLeftAngleBracket = function () {
      if (!this.isEOF() && this.char() === 60
      /* `<` */
      && (this.ignoreTag || // If at the opening tag or closing tag position, bail.
      !_isAlphaOrSlash(this.peek() || 0))) {
        this.bump(); // `<`

        return '<';
      }

      return null;
    };
    /**
     * Starting with ICU 4.8, an ASCII apostrophe only starts quoted text if it immediately precedes
     * a character that requires quoting (that is, "only where needed"), and works the same in
     * nested messages as on the top level of the pattern. The new behavior is otherwise compatible.
     */


    Parser.prototype.tryParseQuote = function (parentArgType) {
      if (this.isEOF() || this.char() !== 39
      /* `'` */
      ) {
        return null;
      } // Parse escaped char following the apostrophe, or early return if there is no escaped char.
      // Check if is valid escaped character


      switch (this.peek()) {
        case 39
        /* `'` */
        :
          // double quote, should return as a single quote.
          this.bump();
          this.bump();
          return "'";
        // '{', '<', '>', '}'

        case 123:
        case 60:
        case 62:
        case 125:
          break;

        case 35:
          // '#'
          if (parentArgType === 'plural' || parentArgType === 'selectordinal') {
            break;
          }

          return null;

        default:
          return null;
      }

      this.bump(); // apostrophe

      var codePoints = [this.char()]; // escaped char

      this.bump(); // read chars until the optional closing apostrophe is found

      while (!this.isEOF()) {
        var ch = this.char();

        if (ch === 39
        /* `'` */
        ) {
          if (this.peek() === 39
          /* `'` */
          ) {
            codePoints.push(39); // Bump one more time because we need to skip 2 characters.

            this.bump();
          } else {
            // Optional closing apostrophe.
            this.bump();
            break;
          }
        } else {
          codePoints.push(ch);
        }

        this.bump();
      }

      return fromCodePoint.apply(void 0, codePoints);
    };

    Parser.prototype.tryParseUnquoted = function (nestingLevel, parentArgType) {
      if (this.isEOF()) {
        return null;
      }

      var ch = this.char();

      if (ch === 60
      /* `<` */
      || ch === 123
      /* `{` */
      || ch === 35
      /* `#` */
      && (parentArgType === 'plural' || parentArgType === 'selectordinal') || ch === 125
      /* `}` */
      && nestingLevel > 0) {
        return null;
      } else {
        this.bump();
        return fromCodePoint(ch);
      }
    };

    Parser.prototype.parseArgument = function (nestingLevel, expectingCloseTag) {
      var openingBracePosition = this.clonePosition();
      this.bump(); // `{`

      this.bumpSpace();

      if (this.isEOF()) {
        return this.error(ErrorKind.EXPECT_ARGUMENT_CLOSING_BRACE, createLocation(openingBracePosition, this.clonePosition()));
      }

      if (this.char() === 125
      /* `}` */
      ) {
        this.bump();
        return this.error(ErrorKind.EMPTY_ARGUMENT, createLocation(openingBracePosition, this.clonePosition()));
      } // argument name


      var value = this.parseIdentifierIfPossible().value;

      if (!value) {
        return this.error(ErrorKind.MALFORMED_ARGUMENT, createLocation(openingBracePosition, this.clonePosition()));
      }

      this.bumpSpace();

      if (this.isEOF()) {
        return this.error(ErrorKind.EXPECT_ARGUMENT_CLOSING_BRACE, createLocation(openingBracePosition, this.clonePosition()));
      }

      switch (this.char()) {
        // Simple argument: `{name}`
        case 125
        /* `}` */
        :
          {
            this.bump(); // `}`

            return {
              val: {
                type: TYPE.argument,
                // value does not include the opening and closing braces.
                value: value,
                location: createLocation(openingBracePosition, this.clonePosition())
              },
              err: null
            };
          }
        // Argument with options: `{name, format, ...}`

        case 44
        /* `,` */
        :
          {
            this.bump(); // `,`

            this.bumpSpace();

            if (this.isEOF()) {
              return this.error(ErrorKind.EXPECT_ARGUMENT_CLOSING_BRACE, createLocation(openingBracePosition, this.clonePosition()));
            }

            return this.parseArgumentOptions(nestingLevel, expectingCloseTag, value, openingBracePosition);
          }

        default:
          return this.error(ErrorKind.MALFORMED_ARGUMENT, createLocation(openingBracePosition, this.clonePosition()));
      }
    };
    /**
     * Advance the parser until the end of the identifier, if it is currently on
     * an identifier character. Return an empty string otherwise.
     */


    Parser.prototype.parseIdentifierIfPossible = function () {
      var startingPosition = this.clonePosition();
      var startOffset = this.offset();
      var value = matchIdentifierAtIndex(this.message, startOffset);
      var endOffset = startOffset + value.length;
      this.bumpTo(endOffset);
      var endPosition = this.clonePosition();
      var location = createLocation(startingPosition, endPosition);
      return {
        value: value,
        location: location
      };
    };

    Parser.prototype.parseArgumentOptions = function (nestingLevel, expectingCloseTag, value, openingBracePosition) {
      var _a; // Parse this range:
      // {name, type, style}
      //        ^---^


      var typeStartPosition = this.clonePosition();
      var argType = this.parseIdentifierIfPossible().value;
      var typeEndPosition = this.clonePosition();

      switch (argType) {
        case '':
          // Expecting a style string number, date, time, plural, selectordinal, or select.
          return this.error(ErrorKind.EXPECT_ARGUMENT_TYPE, createLocation(typeStartPosition, typeEndPosition));

        case 'number':
        case 'date':
        case 'time':
          {
            // Parse this range:
            // {name, number, style}
            //              ^-------^
            this.bumpSpace();
            var styleAndLocation = null;

            if (this.bumpIf(',')) {
              this.bumpSpace();
              var styleStartPosition = this.clonePosition();
              var result = this.parseSimpleArgStyleIfPossible();

              if (result.err) {
                return result;
              }

              var style = trimEnd(result.val);

              if (style.length === 0) {
                return this.error(ErrorKind.EXPECT_ARGUMENT_STYLE, createLocation(this.clonePosition(), this.clonePosition()));
              }

              var styleLocation = createLocation(styleStartPosition, this.clonePosition());
              styleAndLocation = {
                style: style,
                styleLocation: styleLocation
              };
            }

            var argCloseResult = this.tryParseArgumentClose(openingBracePosition);

            if (argCloseResult.err) {
              return argCloseResult;
            }

            var location_1 = createLocation(openingBracePosition, this.clonePosition()); // Extract style or skeleton

            if (styleAndLocation && startsWith(styleAndLocation === null || styleAndLocation === void 0 ? void 0 : styleAndLocation.style, '::', 0)) {
              // Skeleton starts with `::`.
              var skeleton = trimStart(styleAndLocation.style.slice(2));

              if (argType === 'number') {
                var result = this.parseNumberSkeletonFromString(skeleton, styleAndLocation.styleLocation);

                if (result.err) {
                  return result;
                }

                return {
                  val: {
                    type: TYPE.number,
                    value: value,
                    location: location_1,
                    style: result.val
                  },
                  err: null
                };
              } else {
                if (skeleton.length === 0) {
                  return this.error(ErrorKind.EXPECT_DATE_TIME_SKELETON, location_1);
                }

                var dateTimePattern = skeleton; // Get "best match" pattern only if locale is passed, if not, let it
                // pass as-is where `parseDateTimeSkeleton()` will throw an error
                // for unsupported patterns.

                if (this.locale) {
                  dateTimePattern = getBestPattern(skeleton, this.locale);
                }

                var style = {
                  type: SKELETON_TYPE.dateTime,
                  pattern: dateTimePattern,
                  location: styleAndLocation.styleLocation,
                  parsedOptions: this.shouldParseSkeletons ? parseDateTimeSkeleton(dateTimePattern) : {}
                };
                var type = argType === 'date' ? TYPE.date : TYPE.time;
                return {
                  val: {
                    type: type,
                    value: value,
                    location: location_1,
                    style: style
                  },
                  err: null
                };
              }
            } // Regular style or no style.


            return {
              val: {
                type: argType === 'number' ? TYPE.number : argType === 'date' ? TYPE.date : TYPE.time,
                value: value,
                location: location_1,
                style: (_a = styleAndLocation === null || styleAndLocation === void 0 ? void 0 : styleAndLocation.style) !== null && _a !== void 0 ? _a : null
              },
              err: null
            };
          }

        case 'plural':
        case 'selectordinal':
        case 'select':
          {
            // Parse this range:
            // {name, plural, options}
            //              ^---------^
            var typeEndPosition_1 = this.clonePosition();
            this.bumpSpace();

            if (!this.bumpIf(',')) {
              return this.error(ErrorKind.EXPECT_SELECT_ARGUMENT_OPTIONS, createLocation(typeEndPosition_1, __assign({}, typeEndPosition_1)));
            }

            this.bumpSpace(); // Parse offset:
            // {name, plural, offset:1, options}
            //                ^-----^
            //
            // or the first option:
            //
            // {name, plural, one {...} other {...}}
            //                ^--^

            var identifierAndLocation = this.parseIdentifierIfPossible();
            var pluralOffset = 0;

            if (argType !== 'select' && identifierAndLocation.value === 'offset') {
              if (!this.bumpIf(':')) {
                return this.error(ErrorKind.EXPECT_PLURAL_ARGUMENT_OFFSET_VALUE, createLocation(this.clonePosition(), this.clonePosition()));
              }

              this.bumpSpace();
              var result = this.tryParseDecimalInteger(ErrorKind.EXPECT_PLURAL_ARGUMENT_OFFSET_VALUE, ErrorKind.INVALID_PLURAL_ARGUMENT_OFFSET_VALUE);

              if (result.err) {
                return result;
              } // Parse another identifier for option parsing


              this.bumpSpace();
              identifierAndLocation = this.parseIdentifierIfPossible();
              pluralOffset = result.val;
            }

            var optionsResult = this.tryParsePluralOrSelectOptions(nestingLevel, argType, expectingCloseTag, identifierAndLocation);

            if (optionsResult.err) {
              return optionsResult;
            }

            var argCloseResult = this.tryParseArgumentClose(openingBracePosition);

            if (argCloseResult.err) {
              return argCloseResult;
            }

            var location_2 = createLocation(openingBracePosition, this.clonePosition());

            if (argType === 'select') {
              return {
                val: {
                  type: TYPE.select,
                  value: value,
                  options: fromEntries(optionsResult.val),
                  location: location_2
                },
                err: null
              };
            } else {
              return {
                val: {
                  type: TYPE.plural,
                  value: value,
                  options: fromEntries(optionsResult.val),
                  offset: pluralOffset,
                  pluralType: argType === 'plural' ? 'cardinal' : 'ordinal',
                  location: location_2
                },
                err: null
              };
            }
          }

        default:
          return this.error(ErrorKind.INVALID_ARGUMENT_TYPE, createLocation(typeStartPosition, typeEndPosition));
      }
    };

    Parser.prototype.tryParseArgumentClose = function (openingBracePosition) {
      // Parse: {value, number, ::currency/GBP }
      //
      if (this.isEOF() || this.char() !== 125
      /* `}` */
      ) {
        return this.error(ErrorKind.EXPECT_ARGUMENT_CLOSING_BRACE, createLocation(openingBracePosition, this.clonePosition()));
      }

      this.bump(); // `}`

      return {
        val: true,
        err: null
      };
    };
    /**
     * See: https://github.com/unicode-org/icu/blob/af7ed1f6d2298013dc303628438ec4abe1f16479/icu4c/source/common/messagepattern.cpp#L659
     */


    Parser.prototype.parseSimpleArgStyleIfPossible = function () {
      var nestedBraces = 0;
      var startPosition = this.clonePosition();

      while (!this.isEOF()) {
        var ch = this.char();

        switch (ch) {
          case 39
          /* `'` */
          :
            {
              // Treat apostrophe as quoting but include it in the style part.
              // Find the end of the quoted literal text.
              this.bump();
              var apostrophePosition = this.clonePosition();

              if (!this.bumpUntil("'")) {
                return this.error(ErrorKind.UNCLOSED_QUOTE_IN_ARGUMENT_STYLE, createLocation(apostrophePosition, this.clonePosition()));
              }

              this.bump();
              break;
            }

          case 123
          /* `{` */
          :
            {
              nestedBraces += 1;
              this.bump();
              break;
            }

          case 125
          /* `}` */
          :
            {
              if (nestedBraces > 0) {
                nestedBraces -= 1;
              } else {
                return {
                  val: this.message.slice(startPosition.offset, this.offset()),
                  err: null
                };
              }

              break;
            }

          default:
            this.bump();
            break;
        }
      }

      return {
        val: this.message.slice(startPosition.offset, this.offset()),
        err: null
      };
    };

    Parser.prototype.parseNumberSkeletonFromString = function (skeleton, location) {
      var tokens = [];

      try {
        tokens = parseNumberSkeletonFromString(skeleton);
      } catch (e) {
        return this.error(ErrorKind.INVALID_NUMBER_SKELETON, location);
      }

      return {
        val: {
          type: SKELETON_TYPE.number,
          tokens: tokens,
          location: location,
          parsedOptions: this.shouldParseSkeletons ? parseNumberSkeleton(tokens) : {}
        },
        err: null
      };
    };
    /**
     * @param nesting_level The current nesting level of messages.
     *     This can be positive when parsing message fragment in select or plural argument options.
     * @param parent_arg_type The parent argument's type.
     * @param parsed_first_identifier If provided, this is the first identifier-like selector of
     *     the argument. It is a by-product of a previous parsing attempt.
     * @param expecting_close_tag If true, this message is directly or indirectly nested inside
     *     between a pair of opening and closing tags. The nested message will not parse beyond
     *     the closing tag boundary.
     */


    Parser.prototype.tryParsePluralOrSelectOptions = function (nestingLevel, parentArgType, expectCloseTag, parsedFirstIdentifier) {
      var _a;

      var hasOtherClause = false;
      var options = [];
      var parsedSelectors = new Set();
      var selector = parsedFirstIdentifier.value,
          selectorLocation = parsedFirstIdentifier.location; // Parse:
      // one {one apple}
      // ^--^

      while (true) {
        if (selector.length === 0) {
          var startPosition = this.clonePosition();

          if (parentArgType !== 'select' && this.bumpIf('=')) {
            // Try parse `={number}` selector
            var result = this.tryParseDecimalInteger(ErrorKind.EXPECT_PLURAL_ARGUMENT_SELECTOR, ErrorKind.INVALID_PLURAL_ARGUMENT_SELECTOR);

            if (result.err) {
              return result;
            }

            selectorLocation = createLocation(startPosition, this.clonePosition());
            selector = this.message.slice(startPosition.offset, this.offset());
          } else {
            break;
          }
        } // Duplicate selector clauses


        if (parsedSelectors.has(selector)) {
          return this.error(parentArgType === 'select' ? ErrorKind.DUPLICATE_SELECT_ARGUMENT_SELECTOR : ErrorKind.DUPLICATE_PLURAL_ARGUMENT_SELECTOR, selectorLocation);
        }

        if (selector === 'other') {
          hasOtherClause = true;
        } // Parse:
        // one {one apple}
        //     ^----------^


        this.bumpSpace();
        var openingBracePosition = this.clonePosition();

        if (!this.bumpIf('{')) {
          return this.error(parentArgType === 'select' ? ErrorKind.EXPECT_SELECT_ARGUMENT_SELECTOR_FRAGMENT : ErrorKind.EXPECT_PLURAL_ARGUMENT_SELECTOR_FRAGMENT, createLocation(this.clonePosition(), this.clonePosition()));
        }

        var fragmentResult = this.parseMessage(nestingLevel + 1, parentArgType, expectCloseTag);

        if (fragmentResult.err) {
          return fragmentResult;
        }

        var argCloseResult = this.tryParseArgumentClose(openingBracePosition);

        if (argCloseResult.err) {
          return argCloseResult;
        }

        options.push([selector, {
          value: fragmentResult.val,
          location: createLocation(openingBracePosition, this.clonePosition())
        }]); // Keep track of the existing selectors

        parsedSelectors.add(selector); // Prep next selector clause.

        this.bumpSpace();
        _a = this.parseIdentifierIfPossible(), selector = _a.value, selectorLocation = _a.location;
      }

      if (options.length === 0) {
        return this.error(parentArgType === 'select' ? ErrorKind.EXPECT_SELECT_ARGUMENT_SELECTOR : ErrorKind.EXPECT_PLURAL_ARGUMENT_SELECTOR, createLocation(this.clonePosition(), this.clonePosition()));
      }

      if (this.requiresOtherClause && !hasOtherClause) {
        return this.error(ErrorKind.MISSING_OTHER_CLAUSE, createLocation(this.clonePosition(), this.clonePosition()));
      }

      return {
        val: options,
        err: null
      };
    };

    Parser.prototype.tryParseDecimalInteger = function (expectNumberError, invalidNumberError) {
      var sign = 1;
      var startingPosition = this.clonePosition();

      if (this.bumpIf('+')) ; else if (this.bumpIf('-')) {
        sign = -1;
      }

      var hasDigits = false;
      var decimal = 0;

      while (!this.isEOF()) {
        var ch = this.char();

        if (ch >= 48
        /* `0` */
        && ch <= 57
        /* `9` */
        ) {
          hasDigits = true;
          decimal = decimal * 10 + (ch - 48);
          this.bump();
        } else {
          break;
        }
      }

      var location = createLocation(startingPosition, this.clonePosition());

      if (!hasDigits) {
        return this.error(expectNumberError, location);
      }

      decimal *= sign;

      if (!isSafeInteger(decimal)) {
        return this.error(invalidNumberError, location);
      }

      return {
        val: decimal,
        err: null
      };
    };

    Parser.prototype.offset = function () {
      return this.position.offset;
    };

    Parser.prototype.isEOF = function () {
      return this.offset() === this.message.length;
    };

    Parser.prototype.clonePosition = function () {
      // This is much faster than `Object.assign` or spread.
      return {
        offset: this.position.offset,
        line: this.position.line,
        column: this.position.column
      };
    };
    /**
     * Return the code point at the current position of the parser.
     * Throws if the index is out of bound.
     */


    Parser.prototype.char = function () {
      var offset = this.position.offset;

      if (offset >= this.message.length) {
        throw Error('out of bound');
      }

      var code = codePointAt(this.message, offset);

      if (code === undefined) {
        throw Error("Offset ".concat(offset, " is at invalid UTF-16 code unit boundary"));
      }

      return code;
    };

    Parser.prototype.error = function (kind, location) {
      return {
        val: null,
        err: {
          kind: kind,
          message: this.message,
          location: location
        }
      };
    };
    /** Bump the parser to the next UTF-16 code unit. */


    Parser.prototype.bump = function () {
      if (this.isEOF()) {
        return;
      }

      var code = this.char();

      if (code === 10
      /* '\n' */
      ) {
        this.position.line += 1;
        this.position.column = 1;
        this.position.offset += 1;
      } else {
        this.position.column += 1; // 0 ~ 0x10000 -> unicode BMP, otherwise skip the surrogate pair.

        this.position.offset += code < 0x10000 ? 1 : 2;
      }
    };
    /**
     * If the substring starting at the current position of the parser has
     * the given prefix, then bump the parser to the character immediately
     * following the prefix and return true. Otherwise, don't bump the parser
     * and return false.
     */


    Parser.prototype.bumpIf = function (prefix) {
      if (startsWith(this.message, prefix, this.offset())) {
        for (var i = 0; i < prefix.length; i++) {
          this.bump();
        }

        return true;
      }

      return false;
    };
    /**
     * Bump the parser until the pattern character is found and return `true`.
     * Otherwise bump to the end of the file and return `false`.
     */


    Parser.prototype.bumpUntil = function (pattern) {
      var currentOffset = this.offset();
      var index = this.message.indexOf(pattern, currentOffset);

      if (index >= 0) {
        this.bumpTo(index);
        return true;
      } else {
        this.bumpTo(this.message.length);
        return false;
      }
    };
    /**
     * Bump the parser to the target offset.
     * If target offset is beyond the end of the input, bump the parser to the end of the input.
     */


    Parser.prototype.bumpTo = function (targetOffset) {
      if (this.offset() > targetOffset) {
        throw Error("targetOffset ".concat(targetOffset, " must be greater than or equal to the current offset ").concat(this.offset()));
      }

      targetOffset = Math.min(targetOffset, this.message.length);

      while (true) {
        var offset = this.offset();

        if (offset === targetOffset) {
          break;
        }

        if (offset > targetOffset) {
          throw Error("targetOffset ".concat(targetOffset, " is at invalid UTF-16 code unit boundary"));
        }

        this.bump();

        if (this.isEOF()) {
          break;
        }
      }
    };
    /** advance the parser through all whitespace to the next non-whitespace code unit. */


    Parser.prototype.bumpSpace = function () {
      while (!this.isEOF() && _isWhiteSpace(this.char())) {
        this.bump();
      }
    };
    /**
     * Peek at the *next* Unicode codepoint in the input without advancing the parser.
     * If the input has been exhausted, then this returns null.
     */


    Parser.prototype.peek = function () {
      if (this.isEOF()) {
        return null;
      }

      var code = this.char();
      var offset = this.offset();
      var nextCode = this.message.charCodeAt(offset + (code >= 0x10000 ? 2 : 1));
      return nextCode !== null && nextCode !== void 0 ? nextCode : null;
    };

    return Parser;
  }();
  /**
   * This check if codepoint is alphabet (lower & uppercase)
   * @param codepoint
   * @returns
   */

  function _isAlpha(codepoint) {
    return codepoint >= 97 && codepoint <= 122 || codepoint >= 65 && codepoint <= 90;
  }

  function _isAlphaOrSlash(codepoint) {
    return _isAlpha(codepoint) || codepoint === 47;
    /* '/' */
  }
  /** See `parseTag` function docs. */


  function _isPotentialElementNameChar(c) {
    return c === 45
    /* '-' */
    || c === 46
    /* '.' */
    || c >= 48 && c <= 57
    /* 0..9 */
    || c === 95
    /* '_' */
    || c >= 97 && c <= 122
    /** a..z */
    || c >= 65 && c <= 90
    /* A..Z */
    || c == 0xb7 || c >= 0xc0 && c <= 0xd6 || c >= 0xd8 && c <= 0xf6 || c >= 0xf8 && c <= 0x37d || c >= 0x37f && c <= 0x1fff || c >= 0x200c && c <= 0x200d || c >= 0x203f && c <= 0x2040 || c >= 0x2070 && c <= 0x218f || c >= 0x2c00 && c <= 0x2fef || c >= 0x3001 && c <= 0xd7ff || c >= 0xf900 && c <= 0xfdcf || c >= 0xfdf0 && c <= 0xfffd || c >= 0x10000 && c <= 0xeffff;
  }
  /**
   * Code point equivalent of regex `\p{White_Space}`.
   * From: https://www.unicode.org/Public/UCD/latest/ucd/PropList.txt
   */


  function _isWhiteSpace(c) {
    return c >= 0x0009 && c <= 0x000d || c === 0x0020 || c === 0x0085 || c >= 0x200e && c <= 0x200f || c === 0x2028 || c === 0x2029;
  }
  /**
   * Code point equivalent of regex `\p{Pattern_Syntax}`.
   * See https://www.unicode.org/Public/UCD/latest/ucd/PropList.txt
   */


  function _isPatternSyntax(c) {
    return c >= 0x0021 && c <= 0x0023 || c === 0x0024 || c >= 0x0025 && c <= 0x0027 || c === 0x0028 || c === 0x0029 || c === 0x002a || c === 0x002b || c === 0x002c || c === 0x002d || c >= 0x002e && c <= 0x002f || c >= 0x003a && c <= 0x003b || c >= 0x003c && c <= 0x003e || c >= 0x003f && c <= 0x0040 || c === 0x005b || c === 0x005c || c === 0x005d || c === 0x005e || c === 0x0060 || c === 0x007b || c === 0x007c || c === 0x007d || c === 0x007e || c === 0x00a1 || c >= 0x00a2 && c <= 0x00a5 || c === 0x00a6 || c === 0x00a7 || c === 0x00a9 || c === 0x00ab || c === 0x00ac || c === 0x00ae || c === 0x00b0 || c === 0x00b1 || c === 0x00b6 || c === 0x00bb || c === 0x00bf || c === 0x00d7 || c === 0x00f7 || c >= 0x2010 && c <= 0x2015 || c >= 0x2016 && c <= 0x2017 || c === 0x2018 || c === 0x2019 || c === 0x201a || c >= 0x201b && c <= 0x201c || c === 0x201d || c === 0x201e || c === 0x201f || c >= 0x2020 && c <= 0x2027 || c >= 0x2030 && c <= 0x2038 || c === 0x2039 || c === 0x203a || c >= 0x203b && c <= 0x203e || c >= 0x2041 && c <= 0x2043 || c === 0x2044 || c === 0x2045 || c === 0x2046 || c >= 0x2047 && c <= 0x2051 || c === 0x2052 || c === 0x2053 || c >= 0x2055 && c <= 0x205e || c >= 0x2190 && c <= 0x2194 || c >= 0x2195 && c <= 0x2199 || c >= 0x219a && c <= 0x219b || c >= 0x219c && c <= 0x219f || c === 0x21a0 || c >= 0x21a1 && c <= 0x21a2 || c === 0x21a3 || c >= 0x21a4 && c <= 0x21a5 || c === 0x21a6 || c >= 0x21a7 && c <= 0x21ad || c === 0x21ae || c >= 0x21af && c <= 0x21cd || c >= 0x21ce && c <= 0x21cf || c >= 0x21d0 && c <= 0x21d1 || c === 0x21d2 || c === 0x21d3 || c === 0x21d4 || c >= 0x21d5 && c <= 0x21f3 || c >= 0x21f4 && c <= 0x22ff || c >= 0x2300 && c <= 0x2307 || c === 0x2308 || c === 0x2309 || c === 0x230a || c === 0x230b || c >= 0x230c && c <= 0x231f || c >= 0x2320 && c <= 0x2321 || c >= 0x2322 && c <= 0x2328 || c === 0x2329 || c === 0x232a || c >= 0x232b && c <= 0x237b || c === 0x237c || c >= 0x237d && c <= 0x239a || c >= 0x239b && c <= 0x23b3 || c >= 0x23b4 && c <= 0x23db || c >= 0x23dc && c <= 0x23e1 || c >= 0x23e2 && c <= 0x2426 || c >= 0x2427 && c <= 0x243f || c >= 0x2440 && c <= 0x244a || c >= 0x244b && c <= 0x245f || c >= 0x2500 && c <= 0x25b6 || c === 0x25b7 || c >= 0x25b8 && c <= 0x25c0 || c === 0x25c1 || c >= 0x25c2 && c <= 0x25f7 || c >= 0x25f8 && c <= 0x25ff || c >= 0x2600 && c <= 0x266e || c === 0x266f || c >= 0x2670 && c <= 0x2767 || c === 0x2768 || c === 0x2769 || c === 0x276a || c === 0x276b || c === 0x276c || c === 0x276d || c === 0x276e || c === 0x276f || c === 0x2770 || c === 0x2771 || c === 0x2772 || c === 0x2773 || c === 0x2774 || c === 0x2775 || c >= 0x2794 && c <= 0x27bf || c >= 0x27c0 && c <= 0x27c4 || c === 0x27c5 || c === 0x27c6 || c >= 0x27c7 && c <= 0x27e5 || c === 0x27e6 || c === 0x27e7 || c === 0x27e8 || c === 0x27e9 || c === 0x27ea || c === 0x27eb || c === 0x27ec || c === 0x27ed || c === 0x27ee || c === 0x27ef || c >= 0x27f0 && c <= 0x27ff || c >= 0x2800 && c <= 0x28ff || c >= 0x2900 && c <= 0x2982 || c === 0x2983 || c === 0x2984 || c === 0x2985 || c === 0x2986 || c === 0x2987 || c === 0x2988 || c === 0x2989 || c === 0x298a || c === 0x298b || c === 0x298c || c === 0x298d || c === 0x298e || c === 0x298f || c === 0x2990 || c === 0x2991 || c === 0x2992 || c === 0x2993 || c === 0x2994 || c === 0x2995 || c === 0x2996 || c === 0x2997 || c === 0x2998 || c >= 0x2999 && c <= 0x29d7 || c === 0x29d8 || c === 0x29d9 || c === 0x29da || c === 0x29db || c >= 0x29dc && c <= 0x29fb || c === 0x29fc || c === 0x29fd || c >= 0x29fe && c <= 0x2aff || c >= 0x2b00 && c <= 0x2b2f || c >= 0x2b30 && c <= 0x2b44 || c >= 0x2b45 && c <= 0x2b46 || c >= 0x2b47 && c <= 0x2b4c || c >= 0x2b4d && c <= 0x2b73 || c >= 0x2b74 && c <= 0x2b75 || c >= 0x2b76 && c <= 0x2b95 || c === 0x2b96 || c >= 0x2b97 && c <= 0x2bff || c >= 0x2e00 && c <= 0x2e01 || c === 0x2e02 || c === 0x2e03 || c === 0x2e04 || c === 0x2e05 || c >= 0x2e06 && c <= 0x2e08 || c === 0x2e09 || c === 0x2e0a || c === 0x2e0b || c === 0x2e0c || c === 0x2e0d || c >= 0x2e0e && c <= 0x2e16 || c === 0x2e17 || c >= 0x2e18 && c <= 0x2e19 || c === 0x2e1a || c === 0x2e1b || c === 0x2e1c || c === 0x2e1d || c >= 0x2e1e && c <= 0x2e1f || c === 0x2e20 || c === 0x2e21 || c === 0x2e22 || c === 0x2e23 || c === 0x2e24 || c === 0x2e25 || c === 0x2e26 || c === 0x2e27 || c === 0x2e28 || c === 0x2e29 || c >= 0x2e2a && c <= 0x2e2e || c === 0x2e2f || c >= 0x2e30 && c <= 0x2e39 || c >= 0x2e3a && c <= 0x2e3b || c >= 0x2e3c && c <= 0x2e3f || c === 0x2e40 || c === 0x2e41 || c === 0x2e42 || c >= 0x2e43 && c <= 0x2e4f || c >= 0x2e50 && c <= 0x2e51 || c === 0x2e52 || c >= 0x2e53 && c <= 0x2e7f || c >= 0x3001 && c <= 0x3003 || c === 0x3008 || c === 0x3009 || c === 0x300a || c === 0x300b || c === 0x300c || c === 0x300d || c === 0x300e || c === 0x300f || c === 0x3010 || c === 0x3011 || c >= 0x3012 && c <= 0x3013 || c === 0x3014 || c === 0x3015 || c === 0x3016 || c === 0x3017 || c === 0x3018 || c === 0x3019 || c === 0x301a || c === 0x301b || c === 0x301c || c === 0x301d || c >= 0x301e && c <= 0x301f || c === 0x3020 || c === 0x3030 || c === 0xfd3e || c === 0xfd3f || c >= 0xfe45 && c <= 0xfe46;
  }

  function pruneLocation(els) {
    els.forEach(function (el) {
      delete el.location;

      if (isSelectElement(el) || isPluralElement(el)) {
        for (var k in el.options) {
          delete el.options[k].location;
          pruneLocation(el.options[k].value);
        }
      } else if (isNumberElement(el) && isNumberSkeleton(el.style)) {
        delete el.style.location;
      } else if ((isDateElement(el) || isTimeElement(el)) && isDateTimeSkeleton(el.style)) {
        delete el.style.location;
      } else if (isTagElement(el)) {
        pruneLocation(el.children);
      }
    });
  }

  function parse(message, opts) {
    if (opts === void 0) {
      opts = {};
    }

    opts = __assign({
      shouldParseSkeletons: true,
      requiresOtherClause: true
    }, opts);
    var result = new Parser(message, opts).parse();

    if (result.err) {
      var error = SyntaxError(ErrorKind[result.err.kind]); // @ts-expect-error Assign to error object

      error.location = result.err.location; // @ts-expect-error Assign to error object

      error.originalMessage = result.err.message;
      throw error;
    }

    if (!(opts === null || opts === void 0 ? void 0 : opts.captureLocation)) {
      pruneLocation(result.val);
    }

    return result.val;
  }

  //
  // Main
  //
  function memoize(fn, options) {
    var cache = options && options.cache ? options.cache : cacheDefault;
    var serializer = options && options.serializer ? options.serializer : serializerDefault;
    var strategy = options && options.strategy ? options.strategy : strategyDefault;
    return strategy(fn, {
      cache: cache,
      serializer: serializer
    });
  } //
  // Strategy
  //

  function isPrimitive(value) {
    return value == null || typeof value === 'number' || typeof value === 'boolean'; // || typeof value === "string" 'unsafe' primitive for our needs
  }

  function monadic(fn, cache, serializer, arg) {
    var cacheKey = isPrimitive(arg) ? arg : serializer(arg);
    var computedValue = cache.get(cacheKey);

    if (typeof computedValue === 'undefined') {
      computedValue = fn.call(this, arg);
      cache.set(cacheKey, computedValue);
    }

    return computedValue;
  }

  function variadic(fn, cache, serializer) {
    var args = Array.prototype.slice.call(arguments, 3);
    var cacheKey = serializer(args);
    var computedValue = cache.get(cacheKey);

    if (typeof computedValue === 'undefined') {
      computedValue = fn.apply(this, args);
      cache.set(cacheKey, computedValue);
    }

    return computedValue;
  }

  function assemble(fn, context, strategy, cache, serialize) {
    return strategy.bind(context, fn, cache, serialize);
  }

  function strategyDefault(fn, options) {
    var strategy = fn.length === 1 ? monadic : variadic;
    return assemble(fn, this, strategy, options.cache.create(), options.serializer);
  }

  function strategyVariadic(fn, options) {
    return assemble(fn, this, variadic, options.cache.create(), options.serializer);
  }

  function strategyMonadic(fn, options) {
    return assemble(fn, this, monadic, options.cache.create(), options.serializer);
  } //
  // Serializer
  //


  var serializerDefault = function () {
    return JSON.stringify(arguments);
  }; //
  // Cache
  //


  function ObjectWithoutPrototypeCache() {
    this.cache = Object.create(null);
  }

  ObjectWithoutPrototypeCache.prototype.get = function (key) {
    return this.cache[key];
  };

  ObjectWithoutPrototypeCache.prototype.set = function (key, value) {
    this.cache[key] = value;
  };

  var cacheDefault = {
    create: function create() {
      // @ts-ignore
      return new ObjectWithoutPrototypeCache();
    }
  };
  var strategies = {
    variadic: strategyVariadic,
    monadic: strategyMonadic
  };

  var ErrorCode;

  (function (ErrorCode) {
    // When we have a placeholder but no value to format
    ErrorCode["MISSING_VALUE"] = "MISSING_VALUE"; // When value supplied is invalid

    ErrorCode["INVALID_VALUE"] = "INVALID_VALUE"; // When we need specific Intl API but it's not available

    ErrorCode["MISSING_INTL_API"] = "MISSING_INTL_API";
  })(ErrorCode || (ErrorCode = {}));

  var FormatError =
  /** @class */
  function (_super) {
    __extends(FormatError, _super);

    function FormatError(msg, code, originalMessage) {
      var _this = _super.call(this, msg) || this;

      _this.code = code;
      _this.originalMessage = originalMessage;
      return _this;
    }

    FormatError.prototype.toString = function () {
      return "[formatjs Error: ".concat(this.code, "] ").concat(this.message);
    };

    return FormatError;
  }(Error);

  var InvalidValueError =
  /** @class */
  function (_super) {
    __extends(InvalidValueError, _super);

    function InvalidValueError(variableId, value, options, originalMessage) {
      return _super.call(this, "Invalid values for \"".concat(variableId, "\": \"").concat(value, "\". Options are \"").concat(Object.keys(options).join('", "'), "\""), ErrorCode.INVALID_VALUE, originalMessage) || this;
    }

    return InvalidValueError;
  }(FormatError);

  var InvalidValueTypeError =
  /** @class */
  function (_super) {
    __extends(InvalidValueTypeError, _super);

    function InvalidValueTypeError(value, type, originalMessage) {
      return _super.call(this, "Value for \"".concat(value, "\" must be of type ").concat(type), ErrorCode.INVALID_VALUE, originalMessage) || this;
    }

    return InvalidValueTypeError;
  }(FormatError);

  var MissingValueError =
  /** @class */
  function (_super) {
    __extends(MissingValueError, _super);

    function MissingValueError(variableId, originalMessage) {
      return _super.call(this, "The intl string context variable \"".concat(variableId, "\" was not provided to the string \"").concat(originalMessage, "\""), ErrorCode.MISSING_VALUE, originalMessage) || this;
    }

    return MissingValueError;
  }(FormatError);

  var PART_TYPE;

  (function (PART_TYPE) {
    PART_TYPE[PART_TYPE["literal"] = 0] = "literal";
    PART_TYPE[PART_TYPE["object"] = 1] = "object";
  })(PART_TYPE || (PART_TYPE = {}));

  function mergeLiteral(parts) {
    if (parts.length < 2) {
      return parts;
    }

    return parts.reduce(function (all, part) {
      var lastPart = all[all.length - 1];

      if (!lastPart || lastPart.type !== PART_TYPE.literal || part.type !== PART_TYPE.literal) {
        all.push(part);
      } else {
        lastPart.value += part.value;
      }

      return all;
    }, []);
  }

  function isFormatXMLElementFn(el) {
    return typeof el === 'function';
  } // TODO(skeleton): add skeleton support

  function formatToParts(els, locales, formatters, formats, values, currentPluralValue, // For debugging
  originalMessage) {
    // Hot path for straight simple msg translations
    if (els.length === 1 && isLiteralElement(els[0])) {
      return [{
        type: PART_TYPE.literal,
        value: els[0].value
      }];
    }

    var result = [];

    for (var _i = 0, els_1 = els; _i < els_1.length; _i++) {
      var el = els_1[_i]; // Exit early for string parts.

      if (isLiteralElement(el)) {
        result.push({
          type: PART_TYPE.literal,
          value: el.value
        });
        continue;
      } // TODO: should this part be literal type?
      // Replace `#` in plural rules with the actual numeric value.


      if (isPoundElement(el)) {
        if (typeof currentPluralValue === 'number') {
          result.push({
            type: PART_TYPE.literal,
            value: formatters.getNumberFormat(locales).format(currentPluralValue)
          });
        }

        continue;
      }

      var varName = el.value; // Enforce that all required values are provided by the caller.

      if (!(values && varName in values)) {
        throw new MissingValueError(varName, originalMessage);
      }

      var value = values[varName];

      if (isArgumentElement(el)) {
        if (!value || typeof value === 'string' || typeof value === 'number') {
          value = typeof value === 'string' || typeof value === 'number' ? String(value) : '';
        }

        result.push({
          type: typeof value === 'string' ? PART_TYPE.literal : PART_TYPE.object,
          value: value
        });
        continue;
      } // Recursively format plural and select parts' option — which can be a
      // nested pattern structure. The choosing of the option to use is
      // abstracted-by and delegated-to the part helper object.


      if (isDateElement(el)) {
        var style = typeof el.style === 'string' ? formats.date[el.style] : isDateTimeSkeleton(el.style) ? el.style.parsedOptions : undefined;
        result.push({
          type: PART_TYPE.literal,
          value: formatters.getDateTimeFormat(locales, style).format(value)
        });
        continue;
      }

      if (isTimeElement(el)) {
        var style = typeof el.style === 'string' ? formats.time[el.style] : isDateTimeSkeleton(el.style) ? el.style.parsedOptions : formats.time.medium;
        result.push({
          type: PART_TYPE.literal,
          value: formatters.getDateTimeFormat(locales, style).format(value)
        });
        continue;
      }

      if (isNumberElement(el)) {
        var style = typeof el.style === 'string' ? formats.number[el.style] : isNumberSkeleton(el.style) ? el.style.parsedOptions : undefined;

        if (style && style.scale) {
          value = value * (style.scale || 1);
        }

        result.push({
          type: PART_TYPE.literal,
          value: formatters.getNumberFormat(locales, style).format(value)
        });
        continue;
      }

      if (isTagElement(el)) {
        var children = el.children,
            value_1 = el.value;
        var formatFn = values[value_1];

        if (!isFormatXMLElementFn(formatFn)) {
          throw new InvalidValueTypeError(value_1, 'function', originalMessage);
        }

        var parts = formatToParts(children, locales, formatters, formats, values, currentPluralValue);
        var chunks = formatFn(parts.map(function (p) {
          return p.value;
        }));

        if (!Array.isArray(chunks)) {
          chunks = [chunks];
        }

        result.push.apply(result, chunks.map(function (c) {
          return {
            type: typeof c === 'string' ? PART_TYPE.literal : PART_TYPE.object,
            value: c
          };
        }));
      }

      if (isSelectElement(el)) {
        var opt = el.options[value] || el.options.other;

        if (!opt) {
          throw new InvalidValueError(el.value, value, Object.keys(el.options), originalMessage);
        }

        result.push.apply(result, formatToParts(opt.value, locales, formatters, formats, values));
        continue;
      }

      if (isPluralElement(el)) {
        var opt = el.options["=".concat(value)];

        if (!opt) {
          if (!Intl.PluralRules) {
            throw new FormatError("Intl.PluralRules is not available in this environment.\nTry polyfilling it using \"@formatjs/intl-pluralrules\"\n", ErrorCode.MISSING_INTL_API, originalMessage);
          }

          var rule = formatters.getPluralRules(locales, {
            type: el.pluralType
          }).select(value - (el.offset || 0));
          opt = el.options[rule] || el.options.other;
        }

        if (!opt) {
          throw new InvalidValueError(el.value, value, Object.keys(el.options), originalMessage);
        }

        result.push.apply(result, formatToParts(opt.value, locales, formatters, formats, values, value - (el.offset || 0)));
        continue;
      }
    }

    return mergeLiteral(result);
  }

  /*
  Copyright (c) 2014, Yahoo! Inc. All rights reserved.
  Copyrights licensed under the New BSD License.
  See the accompanying LICENSE file for terms.
  */

  function mergeConfig(c1, c2) {
    if (!c2) {
      return c1;
    }

    return __assign(__assign(__assign({}, c1 || {}), c2 || {}), Object.keys(c1).reduce(function (all, k) {
      all[k] = __assign(__assign({}, c1[k]), c2[k] || {});
      return all;
    }, {}));
  }

  function mergeConfigs(defaultConfig, configs) {
    if (!configs) {
      return defaultConfig;
    }

    return Object.keys(defaultConfig).reduce(function (all, k) {
      all[k] = mergeConfig(defaultConfig[k], configs[k]);
      return all;
    }, __assign({}, defaultConfig));
  }

  function createFastMemoizeCache(store) {
    return {
      create: function () {
        return {
          get: function (key) {
            return store[key];
          },
          set: function (key, value) {
            store[key] = value;
          }
        };
      }
    };
  }

  function createDefaultFormatters(cache) {
    if (cache === void 0) {
      cache = {
        number: {},
        dateTime: {},
        pluralRules: {}
      };
    }

    return {
      getNumberFormat: memoize(function () {
        var _a;

        var args = [];

        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }

        return new ((_a = Intl.NumberFormat).bind.apply(_a, __spreadArray([void 0], args, false)))();
      }, {
        cache: createFastMemoizeCache(cache.number),
        strategy: strategies.variadic
      }),
      getDateTimeFormat: memoize(function () {
        var _a;

        var args = [];

        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }

        return new ((_a = Intl.DateTimeFormat).bind.apply(_a, __spreadArray([void 0], args, false)))();
      }, {
        cache: createFastMemoizeCache(cache.dateTime),
        strategy: strategies.variadic
      }),
      getPluralRules: memoize(function () {
        var _a;

        var args = [];

        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }

        return new ((_a = Intl.PluralRules).bind.apply(_a, __spreadArray([void 0], args, false)))();
      }, {
        cache: createFastMemoizeCache(cache.pluralRules),
        strategy: strategies.variadic
      })
    };
  }

  var IntlMessageFormat =
  /** @class */
  function () {
    function IntlMessageFormat(message, locales, overrideFormats, opts) {
      var _this = this;

      if (locales === void 0) {
        locales = IntlMessageFormat.defaultLocale;
      }

      this.formatterCache = {
        number: {},
        dateTime: {},
        pluralRules: {}
      };

      this.format = function (values) {
        var parts = _this.formatToParts(values); // Hot path for straight simple msg translations


        if (parts.length === 1) {
          return parts[0].value;
        }

        var result = parts.reduce(function (all, part) {
          if (!all.length || part.type !== PART_TYPE.literal || typeof all[all.length - 1] !== 'string') {
            all.push(part.value);
          } else {
            all[all.length - 1] += part.value;
          }

          return all;
        }, []);

        if (result.length <= 1) {
          return result[0] || '';
        }

        return result;
      };

      this.formatToParts = function (values) {
        return formatToParts(_this.ast, _this.locales, _this.formatters, _this.formats, values, undefined, _this.message);
      };

      this.resolvedOptions = function () {
        return {
          locale: _this.resolvedLocale.toString()
        };
      };

      this.getAst = function () {
        return _this.ast;
      }; // Defined first because it's used to build the format pattern.


      this.locales = locales;
      this.resolvedLocale = IntlMessageFormat.resolveLocale(locales);

      if (typeof message === 'string') {
        this.message = message;

        if (!IntlMessageFormat.__parse) {
          throw new TypeError('IntlMessageFormat.__parse must be set to process `message` of type `string`');
        } // Parse string messages into an AST.


        this.ast = IntlMessageFormat.__parse(message, {
          ignoreTag: opts === null || opts === void 0 ? void 0 : opts.ignoreTag,
          locale: this.resolvedLocale
        });
      } else {
        this.ast = message;
      }

      if (!Array.isArray(this.ast)) {
        throw new TypeError('A message must be provided as a String or AST.');
      } // Creates a new object with the specified `formats` merged with the default
      // formats.


      this.formats = mergeConfigs(IntlMessageFormat.formats, overrideFormats);
      this.formatters = opts && opts.formatters || createDefaultFormatters(this.formatterCache);
    }

    Object.defineProperty(IntlMessageFormat, "defaultLocale", {
      get: function () {
        if (!IntlMessageFormat.memoizedDefaultLocale) {
          IntlMessageFormat.memoizedDefaultLocale = new Intl.NumberFormat().resolvedOptions().locale;
        }

        return IntlMessageFormat.memoizedDefaultLocale;
      },
      enumerable: false,
      configurable: true
    });
    IntlMessageFormat.memoizedDefaultLocale = null;

    IntlMessageFormat.resolveLocale = function (locales) {
      var supportedLocales = Intl.NumberFormat.supportedLocalesOf(locales);

      if (supportedLocales.length > 0) {
        return new Intl.Locale(supportedLocales[0]);
      }

      return new Intl.Locale(typeof locales === 'string' ? locales : locales[0]);
    };

    IntlMessageFormat.__parse = parse; // Default format options used as the prototype of the `formats` provided to the
    // constructor. These are used when constructing the internal Intl.NumberFormat
    // and Intl.DateTimeFormat instances.

    IntlMessageFormat.formats = {
      number: {
        integer: {
          maximumFractionDigits: 0
        },
        currency: {
          style: 'currency'
        },
        percent: {
          style: 'percent'
        }
      },
      date: {
        short: {
          month: 'numeric',
          day: 'numeric',
          year: '2-digit'
        },
        medium: {
          month: 'short',
          day: 'numeric',
          year: 'numeric'
        },
        long: {
          month: 'long',
          day: 'numeric',
          year: 'numeric'
        },
        full: {
          weekday: 'long',
          month: 'long',
          day: 'numeric',
          year: 'numeric'
        }
      },
      time: {
        short: {
          hour: 'numeric',
          minute: 'numeric'
        },
        medium: {
          hour: 'numeric',
          minute: 'numeric',
          second: 'numeric'
        },
        long: {
          hour: 'numeric',
          minute: 'numeric',
          second: 'numeric',
          timeZoneName: 'short'
        },
        full: {
          hour: 'numeric',
          minute: 'numeric',
          second: 'numeric',
          timeZoneName: 'short'
        }
      }
    };
    return IntlMessageFormat;
  }();

  var IntlErrorCode;

  (function (IntlErrorCode) {
    IntlErrorCode["FORMAT_ERROR"] = "FORMAT_ERROR";
    IntlErrorCode["UNSUPPORTED_FORMATTER"] = "UNSUPPORTED_FORMATTER";
    IntlErrorCode["INVALID_CONFIG"] = "INVALID_CONFIG";
    IntlErrorCode["MISSING_DATA"] = "MISSING_DATA";
    IntlErrorCode["MISSING_TRANSLATION"] = "MISSING_TRANSLATION";
  })(IntlErrorCode || (IntlErrorCode = {}));

  var IntlError =
  /** @class */
  function (_super) {
    __extends(IntlError, _super);

    function IntlError(code, message, exception) {
      var _this = this;

      var err = exception ? exception instanceof Error ? exception : new Error(String(exception)) : undefined;
      _this = _super.call(this, "[@formatjs/intl Error ".concat(code, "] ").concat(message, " \n").concat(err ? "\n".concat(err.message, "\n").concat(err.stack) : '')) || this;
      _this.code = code; // @ts-ignore just so we don't need to declare dep on @types/node

      if (typeof Error.captureStackTrace === 'function') {
        // @ts-ignore just so we don't need to declare dep on @types/node
        Error.captureStackTrace(_this, IntlError);
      }

      return _this;
    }

    return IntlError;
  }(Error);

  var UnsupportedFormatterError =
  /** @class */
  function (_super) {
    __extends(UnsupportedFormatterError, _super);

    function UnsupportedFormatterError(message, exception) {
      return _super.call(this, IntlErrorCode.UNSUPPORTED_FORMATTER, message, exception) || this;
    }

    return UnsupportedFormatterError;
  }(IntlError);

  var InvalidConfigError =
  /** @class */
  function (_super) {
    __extends(InvalidConfigError, _super);

    function InvalidConfigError(message, exception) {
      return _super.call(this, IntlErrorCode.INVALID_CONFIG, message, exception) || this;
    }

    return InvalidConfigError;
  }(IntlError);

  var MissingDataError =
  /** @class */
  function (_super) {
    __extends(MissingDataError, _super);

    function MissingDataError(message, exception) {
      return _super.call(this, IntlErrorCode.MISSING_DATA, message, exception) || this;
    }

    return MissingDataError;
  }(IntlError);

  var IntlFormatError =
  /** @class */
  function (_super) {
    __extends(IntlFormatError, _super);

    function IntlFormatError(message, locale, exception) {
      return _super.call(this, IntlErrorCode.FORMAT_ERROR, "".concat(message, " \nLocale: ").concat(locale, "\n"), exception) || this;
    }

    return IntlFormatError;
  }(IntlError);

  var MessageFormatError =
  /** @class */
  function (_super) {
    __extends(MessageFormatError, _super);

    function MessageFormatError(message, locale, descriptor, exception) {
      var _this = _super.call(this, "".concat(message, " \nMessageID: ").concat(descriptor === null || descriptor === void 0 ? void 0 : descriptor.id, "\nDefault Message: ").concat(descriptor === null || descriptor === void 0 ? void 0 : descriptor.defaultMessage, "\nDescription: ").concat(descriptor === null || descriptor === void 0 ? void 0 : descriptor.description, " \n"), locale, exception) || this;

      _this.descriptor = descriptor;
      return _this;
    }

    return MessageFormatError;
  }(IntlFormatError);

  var MissingTranslationError =
  /** @class */
  function (_super) {
    __extends(MissingTranslationError, _super);

    function MissingTranslationError(descriptor, locale) {
      var _this = _super.call(this, IntlErrorCode.MISSING_TRANSLATION, "Missing message: \"".concat(descriptor.id, "\" for locale \"").concat(locale, "\", using ").concat(descriptor.defaultMessage ? 'default message' : 'id', " as fallback.")) || this;

      _this.descriptor = descriptor;
      return _this;
    }

    return MissingTranslationError;
  }(IntlError);

  function filterProps(props, allowlist, defaults) {
    if (defaults === void 0) {
      defaults = {};
    }

    return allowlist.reduce(function (filtered, name) {
      if (name in props) {
        filtered[name] = props[name];
      } else if (name in defaults) {
        filtered[name] = defaults[name];
      }

      return filtered;
    }, {});
  }

  var defaultErrorHandler = function (error) {
  };

  var defaultWarnHandler = function (warning) {
  };

  var DEFAULT_INTL_CONFIG = {
    formats: {},
    messages: {},
    timeZone: undefined,
    defaultLocale: 'en',
    defaultFormats: {},
    fallbackOnEmptyString: true,
    onError: defaultErrorHandler,
    onWarn: defaultWarnHandler
  };
  function createIntlCache() {
    return {
      dateTime: {},
      number: {},
      message: {},
      relativeTime: {},
      pluralRules: {},
      list: {},
      displayNames: {}
    };
  }

  function createFastMemoizeCache$1(store) {
    return {
      create: function () {
        return {
          get: function (key) {
            return store[key];
          },
          set: function (key, value) {
            store[key] = value;
          }
        };
      }
    };
  }
  /**
   * Create intl formatters and populate cache
   * @param cache explicit cache to prevent leaking memory
   */


  function createFormatters(cache) {
    if (cache === void 0) {
      cache = createIntlCache();
    }

    var RelativeTimeFormat = Intl.RelativeTimeFormat;
    var ListFormat = Intl.ListFormat;
    var DisplayNames = Intl.DisplayNames;
    var getDateTimeFormat = memoize(function () {
      var _a;

      var args = [];

      for (var _i = 0; _i < arguments.length; _i++) {
        args[_i] = arguments[_i];
      }

      return new ((_a = Intl.DateTimeFormat).bind.apply(_a, __spreadArray([void 0], args, false)))();
    }, {
      cache: createFastMemoizeCache$1(cache.dateTime),
      strategy: strategies.variadic
    });
    var getNumberFormat = memoize(function () {
      var _a;

      var args = [];

      for (var _i = 0; _i < arguments.length; _i++) {
        args[_i] = arguments[_i];
      }

      return new ((_a = Intl.NumberFormat).bind.apply(_a, __spreadArray([void 0], args, false)))();
    }, {
      cache: createFastMemoizeCache$1(cache.number),
      strategy: strategies.variadic
    });
    var getPluralRules = memoize(function () {
      var _a;

      var args = [];

      for (var _i = 0; _i < arguments.length; _i++) {
        args[_i] = arguments[_i];
      }

      return new ((_a = Intl.PluralRules).bind.apply(_a, __spreadArray([void 0], args, false)))();
    }, {
      cache: createFastMemoizeCache$1(cache.pluralRules),
      strategy: strategies.variadic
    });
    return {
      getDateTimeFormat: getDateTimeFormat,
      getNumberFormat: getNumberFormat,
      getMessageFormat: memoize(function (message, locales, overrideFormats, opts) {
        return new IntlMessageFormat(message, locales, overrideFormats, __assign({
          formatters: {
            getNumberFormat: getNumberFormat,
            getDateTimeFormat: getDateTimeFormat,
            getPluralRules: getPluralRules
          }
        }, opts || {}));
      }, {
        cache: createFastMemoizeCache$1(cache.message),
        strategy: strategies.variadic
      }),
      getRelativeTimeFormat: memoize(function () {
        var args = [];

        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }

        return new (RelativeTimeFormat.bind.apply(RelativeTimeFormat, __spreadArray([void 0], args, false)))();
      }, {
        cache: createFastMemoizeCache$1(cache.relativeTime),
        strategy: strategies.variadic
      }),
      getPluralRules: getPluralRules,
      getListFormat: memoize(function () {
        var args = [];

        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }

        return new (ListFormat.bind.apply(ListFormat, __spreadArray([void 0], args, false)))();
      }, {
        cache: createFastMemoizeCache$1(cache.list),
        strategy: strategies.variadic
      }),
      getDisplayNames: memoize(function () {
        var args = [];

        for (var _i = 0; _i < arguments.length; _i++) {
          args[_i] = arguments[_i];
        }

        return new (DisplayNames.bind.apply(DisplayNames, __spreadArray([void 0], args, false)))();
      }, {
        cache: createFastMemoizeCache$1(cache.displayNames),
        strategy: strategies.variadic
      })
    };
  }
  function getNamedFormat(formats, type, name, onError) {
    var formatType = formats && formats[type];
    var format;

    if (formatType) {
      format = formatType[name];
    }

    if (format) {
      return format;
    }

    onError(new UnsupportedFormatterError("No ".concat(type, " format named: ").concat(name)));
  }

  function setTimeZoneInOptions(opts, timeZone) {
    return Object.keys(opts).reduce(function (all, k) {
      all[k] = __assign({
        timeZone: timeZone
      }, opts[k]);
      return all;
    }, {});
  }

  function deepMergeOptions(opts1, opts2) {
    var keys = Object.keys(__assign(__assign({}, opts1), opts2));
    return keys.reduce(function (all, k) {
      all[k] = __assign(__assign({}, opts1[k] || {}), opts2[k] || {});
      return all;
    }, {});
  }

  function deepMergeFormatsAndSetTimeZone(f1, timeZone) {
    if (!timeZone) {
      return f1;
    }

    var mfFormats = IntlMessageFormat.formats;
    return __assign(__assign(__assign({}, mfFormats), f1), {
      date: deepMergeOptions(setTimeZoneInOptions(mfFormats.date, timeZone), setTimeZoneInOptions(f1.date || {}, timeZone)),
      time: deepMergeOptions(setTimeZoneInOptions(mfFormats.time, timeZone), setTimeZoneInOptions(f1.time || {}, timeZone))
    });
  }

  function formatMessage(_a, state, messageDescriptor, values, opts) {
    var locale = _a.locale,
        formats = _a.formats,
        messages = _a.messages,
        defaultLocale = _a.defaultLocale,
        defaultFormats = _a.defaultFormats,
        fallbackOnEmptyString = _a.fallbackOnEmptyString,
        onError = _a.onError,
        timeZone = _a.timeZone,
        defaultRichTextElements = _a.defaultRichTextElements;

    if (messageDescriptor === void 0) {
      messageDescriptor = {
        id: ''
      };
    }

    var msgId = messageDescriptor.id,
        defaultMessage = messageDescriptor.defaultMessage; // `id` is a required field of a Message Descriptor.

    invariant(!!msgId, "[@formatjs/intl] An `id` must be provided to format a message. You can either:\n1. Configure your build toolchain with [babel-plugin-formatjs](https://formatjs.io/docs/tooling/babel-plugin)\nor [@formatjs/ts-transformer](https://formatjs.io/docs/tooling/ts-transformer) OR\n2. Configure your `eslint` config to include [eslint-plugin-formatjs](https://formatjs.io/docs/tooling/linter#enforce-id)\nto autofix this issue");
    var id = String(msgId);
    var message = // In case messages is Object.create(null)
    // e.g import('foo.json') from webpack)
    // See https://github.com/formatjs/formatjs/issues/1914
    messages && Object.prototype.hasOwnProperty.call(messages, id) && messages[id]; // IMPORTANT: Hot path if `message` is AST with a single literal node

    if (Array.isArray(message) && message.length === 1 && message[0].type === TYPE.literal) {
      return message[0].value;
    } // IMPORTANT: Hot path straight lookup for performance


    if (!values && message && typeof message === 'string' && !defaultRichTextElements) {
      return message.replace(/'\{(.*?)\}'/gi, "{$1}");
    }

    values = __assign(__assign({}, defaultRichTextElements), values || {});
    formats = deepMergeFormatsAndSetTimeZone(formats, timeZone);
    defaultFormats = deepMergeFormatsAndSetTimeZone(defaultFormats, timeZone);

    if (!message) {
      if (fallbackOnEmptyString === false && message === '') {
        return message;
      }

      if (!defaultMessage || locale && locale.toLowerCase() !== defaultLocale.toLowerCase()) {
        // This prevents warnings from littering the console in development
        // when no `messages` are passed into the <IntlProvider> for the
        // default locale.
        onError(new MissingTranslationError(messageDescriptor, locale));
      }

      if (defaultMessage) {
        try {
          var formatter = state.getMessageFormat(defaultMessage, defaultLocale, defaultFormats, opts);
          return formatter.format(values);
        } catch (e) {
          onError(new MessageFormatError("Error formatting default message for: \"".concat(id, "\", rendering default message verbatim"), locale, messageDescriptor, e));
          return typeof defaultMessage === 'string' ? defaultMessage : id;
        }
      }

      return id;
    } // We have the translated message


    try {
      var formatter = state.getMessageFormat(message, locale, formats, __assign({
        formatters: state
      }, opts || {}));
      return formatter.format(values);
    } catch (e) {
      onError(new MessageFormatError("Error formatting message: \"".concat(id, "\", using ").concat(defaultMessage ? 'default message' : 'id', " as fallback."), locale, messageDescriptor, e));
    }

    if (defaultMessage) {
      try {
        var formatter = state.getMessageFormat(defaultMessage, defaultLocale, defaultFormats, opts);
        return formatter.format(values);
      } catch (e) {
        onError(new MessageFormatError("Error formatting the default message for: \"".concat(id, "\", rendering message verbatim"), locale, messageDescriptor, e));
      }
    }

    if (typeof message === 'string') {
      return message;
    }

    if (typeof defaultMessage === 'string') {
      return defaultMessage;
    }

    return id;
  }

  var DATE_TIME_FORMAT_OPTIONS = ['localeMatcher', 'formatMatcher', 'timeZone', 'hour12', 'weekday', 'era', 'year', 'month', 'day', 'hour', 'minute', 'second', 'timeZoneName', 'hourCycle', 'dateStyle', 'timeStyle', 'calendar', // 'dayPeriod',
  'numberingSystem'];
  function getFormatter(_a, type, getDateTimeFormat, options) {
    var locale = _a.locale,
        formats = _a.formats,
        onError = _a.onError,
        timeZone = _a.timeZone;

    if (options === void 0) {
      options = {};
    }

    var format = options.format;

    var defaults = __assign(__assign({}, timeZone && {
      timeZone: timeZone
    }), format && getNamedFormat(formats, type, format, onError));

    var filteredOptions = filterProps(options, DATE_TIME_FORMAT_OPTIONS, defaults);

    if (type === 'time' && !filteredOptions.hour && !filteredOptions.minute && !filteredOptions.second && !filteredOptions.timeStyle && !filteredOptions.dateStyle) {
      // Add default formatting options if hour, minute, or second isn't defined.
      filteredOptions = __assign(__assign({}, filteredOptions), {
        hour: 'numeric',
        minute: 'numeric'
      });
    }

    return getDateTimeFormat(locale, filteredOptions);
  }
  function formatDate(config, getDateTimeFormat) {
    var _a = [];

    for (var _i = 2; _i < arguments.length; _i++) {
      _a[_i - 2] = arguments[_i];
    }

    var value = _a[0],
        _b = _a[1],
        options = _b === void 0 ? {} : _b;
    var date = typeof value === 'string' ? new Date(value || 0) : value;

    try {
      return getFormatter(config, 'date', getDateTimeFormat, options).format(date);
    } catch (e) {
      config.onError(new IntlError(IntlErrorCode.FORMAT_ERROR, 'Error formatting date.', e));
    }

    return String(date);
  }
  function formatTime(config, getDateTimeFormat) {
    var _a = [];

    for (var _i = 2; _i < arguments.length; _i++) {
      _a[_i - 2] = arguments[_i];
    }

    var value = _a[0],
        _b = _a[1],
        options = _b === void 0 ? {} : _b;
    var date = typeof value === 'string' ? new Date(value || 0) : value;

    try {
      return getFormatter(config, 'time', getDateTimeFormat, options).format(date);
    } catch (e) {
      config.onError(new IntlError(IntlErrorCode.FORMAT_ERROR, 'Error formatting time.', e));
    }

    return String(date);
  }
  function formatDateTimeRange(config, getDateTimeFormat) {
    var _a = [];

    for (var _i = 2; _i < arguments.length; _i++) {
      _a[_i - 2] = arguments[_i];
    }

    var from = _a[0],
        to = _a[1],
        _b = _a[2],
        options = _b === void 0 ? {} : _b;
    var timeZone = config.timeZone,
        locale = config.locale,
        onError = config.onError;
    var filteredOptions = filterProps(options, DATE_TIME_FORMAT_OPTIONS, timeZone ? {
      timeZone: timeZone
    } : {});

    try {
      return getDateTimeFormat(locale, filteredOptions).formatRange(from, to);
    } catch (e) {
      onError(new IntlError(IntlErrorCode.FORMAT_ERROR, 'Error formatting date time range.', e));
    }

    return String(from);
  }
  function formatDateToParts(config, getDateTimeFormat) {
    var _a = [];

    for (var _i = 2; _i < arguments.length; _i++) {
      _a[_i - 2] = arguments[_i];
    }

    var value = _a[0],
        _b = _a[1],
        options = _b === void 0 ? {} : _b;
    var date = typeof value === 'string' ? new Date(value || 0) : value;

    try {
      return getFormatter(config, 'date', getDateTimeFormat, options).formatToParts(date);
    } catch (e) {
      config.onError(new IntlError(IntlErrorCode.FORMAT_ERROR, 'Error formatting date.', e));
    }

    return [];
  }
  function formatTimeToParts(config, getDateTimeFormat) {
    var _a = [];

    for (var _i = 2; _i < arguments.length; _i++) {
      _a[_i - 2] = arguments[_i];
    }

    var value = _a[0],
        _b = _a[1],
        options = _b === void 0 ? {} : _b;
    var date = typeof value === 'string' ? new Date(value || 0) : value;

    try {
      return getFormatter(config, 'time', getDateTimeFormat, options).formatToParts(date);
    } catch (e) {
      config.onError(new IntlError(IntlErrorCode.FORMAT_ERROR, 'Error formatting time.', e));
    }

    return [];
  }

  var DISPLAY_NAMES_OPTONS = ['localeMatcher', 'style', 'type', 'fallback'];
  function formatDisplayName(_a, getDisplayNames, value, options) {
    var locale = _a.locale,
        onError = _a.onError;
    var DisplayNames = Intl.DisplayNames;

    if (!DisplayNames) {
      onError(new FormatError("Intl.DisplayNames is not available in this environment.\nTry polyfilling it using \"@formatjs/intl-displaynames\"\n", ErrorCode.MISSING_INTL_API));
    }

    var filteredOptions = filterProps(options, DISPLAY_NAMES_OPTONS);

    try {
      return getDisplayNames(locale, filteredOptions).of(value);
    } catch (e) {
      onError(new IntlError(IntlErrorCode.FORMAT_ERROR, 'Error formatting display name.', e));
    }
  }

  var LIST_FORMAT_OPTIONS = ['localeMatcher', 'type', 'style'];
  var now = Date.now();

  function generateToken(i) {
    return "".concat(now, "_").concat(i, "_").concat(now);
  }

  function formatList(opts, getListFormat, values, options) {
    if (options === void 0) {
      options = {};
    }

    var results = formatListToParts(opts, getListFormat, values, options).reduce(function (all, el) {
      var val = el.value;

      if (typeof val !== 'string') {
        all.push(val);
      } else if (typeof all[all.length - 1] === 'string') {
        all[all.length - 1] += val;
      } else {
        all.push(val);
      }

      return all;
    }, []);
    return results.length === 1 ? results[0] : results;
  }
  function formatListToParts(_a, getListFormat, values, options) {
    var locale = _a.locale,
        onError = _a.onError;

    if (options === void 0) {
      options = {};
    }

    var ListFormat = Intl.ListFormat;

    if (!ListFormat) {
      onError(new FormatError("Intl.ListFormat is not available in this environment.\nTry polyfilling it using \"@formatjs/intl-listformat\"\n", ErrorCode.MISSING_INTL_API));
    }

    var filteredOptions = filterProps(options, LIST_FORMAT_OPTIONS);

    try {
      var richValues_1 = {};
      var serializedValues = values.map(function (v, i) {
        if (typeof v === 'object') {
          var id = generateToken(i);
          richValues_1[id] = v;
          return id;
        }

        return String(v);
      });
      return getListFormat(locale, filteredOptions).formatToParts(serializedValues).map(function (part) {
        return part.type === 'literal' ? part : __assign(__assign({}, part), {
          value: richValues_1[part.value] || part.value
        });
      });
    } catch (e) {
      onError(new IntlError(IntlErrorCode.FORMAT_ERROR, 'Error formatting list.', e));
    } // @ts-ignore


    return values;
  }

  var PLURAL_FORMAT_OPTIONS = ['localeMatcher', 'type'];
  function formatPlural(_a, getPluralRules, value, options) {
    var locale = _a.locale,
        onError = _a.onError;

    if (options === void 0) {
      options = {};
    }

    if (!Intl.PluralRules) {
      onError(new FormatError("Intl.PluralRules is not available in this environment.\nTry polyfilling it using \"@formatjs/intl-pluralrules\"\n", ErrorCode.MISSING_INTL_API));
    }

    var filteredOptions = filterProps(options, PLURAL_FORMAT_OPTIONS);

    try {
      return getPluralRules(locale, filteredOptions).select(value);
    } catch (e) {
      onError(new IntlFormatError('Error formatting plural.', locale, e));
    }

    return 'other';
  }

  var RELATIVE_TIME_FORMAT_OPTIONS = ['numeric', 'style'];

  function getFormatter$1(_a, getRelativeTimeFormat, options) {
    var locale = _a.locale,
        formats = _a.formats,
        onError = _a.onError;

    if (options === void 0) {
      options = {};
    }

    var format = options.format;
    var defaults = !!format && getNamedFormat(formats, 'relative', format, onError) || {};
    var filteredOptions = filterProps(options, RELATIVE_TIME_FORMAT_OPTIONS, defaults);
    return getRelativeTimeFormat(locale, filteredOptions);
  }

  function formatRelativeTime(config, getRelativeTimeFormat, value, unit, options) {
    if (options === void 0) {
      options = {};
    }

    if (!unit) {
      unit = 'second';
    }

    var RelativeTimeFormat = Intl.RelativeTimeFormat;

    if (!RelativeTimeFormat) {
      config.onError(new FormatError("Intl.RelativeTimeFormat is not available in this environment.\nTry polyfilling it using \"@formatjs/intl-relativetimeformat\"\n", ErrorCode.MISSING_INTL_API));
    }

    try {
      return getFormatter$1(config, getRelativeTimeFormat, options).format(value, unit);
    } catch (e) {
      config.onError(new IntlFormatError('Error formatting relative time.', config.locale, e));
    }

    return String(value);
  }

  var NUMBER_FORMAT_OPTIONS = ['localeMatcher', 'style', 'currency', 'currencyDisplay', 'unit', 'unitDisplay', 'useGrouping', 'minimumIntegerDigits', 'minimumFractionDigits', 'maximumFractionDigits', 'minimumSignificantDigits', 'maximumSignificantDigits', // ES2020 NumberFormat
  'compactDisplay', 'currencyDisplay', 'currencySign', 'notation', 'signDisplay', 'unit', 'unitDisplay', 'numberingSystem'];
  function getFormatter$2(_a, getNumberFormat, options) {
    var locale = _a.locale,
        formats = _a.formats,
        onError = _a.onError;

    if (options === void 0) {
      options = {};
    }

    var format = options.format;
    var defaults = format && getNamedFormat(formats, 'number', format, onError) || {};
    var filteredOptions = filterProps(options, NUMBER_FORMAT_OPTIONS, defaults);
    return getNumberFormat(locale, filteredOptions);
  }
  function formatNumber(config, getNumberFormat, value, options) {
    if (options === void 0) {
      options = {};
    }

    try {
      return getFormatter$2(config, getNumberFormat, options).format(value);
    } catch (e) {
      config.onError(new IntlError(IntlErrorCode.FORMAT_ERROR, 'Error formatting number.', e));
    }

    return String(value);
  }
  function formatNumberToParts(config, getNumberFormat, value, options) {
    if (options === void 0) {
      options = {};
    }

    try {
      return getFormatter$2(config, getNumberFormat, options).formatToParts(value);
    } catch (e) {
      config.onError(new IntlError(IntlErrorCode.FORMAT_ERROR, 'Error formatting number.', e));
    }

    return [];
  }

  function messagesContainString(messages) {
    var firstMessage = messages ? messages[Object.keys(messages)[0]] : undefined;
    return typeof firstMessage === 'string';
  }

  function verifyConfigMessages(config) {
    if (config.onWarn && config.defaultRichTextElements && messagesContainString(config.messages || {})) {
      config.onWarn("[@formatjs/intl] \"defaultRichTextElements\" was specified but \"message\" was not pre-compiled. \nPlease consider using \"@formatjs/cli\" to pre-compile your messages for performance.\nFor more details see https://formatjs.io/docs/getting-started/message-distribution");
    }
  }
  /**
   * Create intl object
   * @param config intl config
   * @param cache cache for formatter instances to prevent memory leak
   */


  function createIntl(config, cache) {
    var formatters = createFormatters(cache);

    var resolvedConfig = __assign(__assign({}, DEFAULT_INTL_CONFIG), config);

    var locale = resolvedConfig.locale,
        defaultLocale = resolvedConfig.defaultLocale,
        onError = resolvedConfig.onError;

    if (!locale) {
      if (onError) {
        onError(new InvalidConfigError("\"locale\" was not configured, using \"".concat(defaultLocale, "\" as fallback. See https://formatjs.io/docs/react-intl/api#intlshape for more details")));
      } // Since there's no registered locale data for `locale`, this will
      // fallback to the `defaultLocale` to make sure things can render.
      // The `messages` are overridden to the `defaultProps` empty object
      // to maintain referential equality across re-renders. It's assumed
      // each <FormattedMessage> contains a `defaultMessage` prop.


      resolvedConfig.locale = resolvedConfig.defaultLocale || 'en';
    } else if (!Intl.NumberFormat.supportedLocalesOf(locale).length && onError) {
      onError(new MissingDataError("Missing locale data for locale: \"".concat(locale, "\" in Intl.NumberFormat. Using default locale: \"").concat(defaultLocale, "\" as fallback. See https://formatjs.io/docs/react-intl#runtime-requirements for more details")));
    } else if (!Intl.DateTimeFormat.supportedLocalesOf(locale).length && onError) {
      onError(new MissingDataError("Missing locale data for locale: \"".concat(locale, "\" in Intl.DateTimeFormat. Using default locale: \"").concat(defaultLocale, "\" as fallback. See https://formatjs.io/docs/react-intl#runtime-requirements for more details")));
    }

    verifyConfigMessages(resolvedConfig);
    return __assign(__assign({}, resolvedConfig), {
      formatters: formatters,
      formatNumber: formatNumber.bind(null, resolvedConfig, formatters.getNumberFormat),
      formatNumberToParts: formatNumberToParts.bind(null, resolvedConfig, formatters.getNumberFormat),
      formatRelativeTime: formatRelativeTime.bind(null, resolvedConfig, formatters.getRelativeTimeFormat),
      formatDate: formatDate.bind(null, resolvedConfig, formatters.getDateTimeFormat),
      formatDateToParts: formatDateToParts.bind(null, resolvedConfig, formatters.getDateTimeFormat),
      formatTime: formatTime.bind(null, resolvedConfig, formatters.getDateTimeFormat),
      formatDateTimeRange: formatDateTimeRange.bind(null, resolvedConfig, formatters.getDateTimeFormat),
      formatTimeToParts: formatTimeToParts.bind(null, resolvedConfig, formatters.getDateTimeFormat),
      formatPlural: formatPlural.bind(null, resolvedConfig, formatters.getPluralRules),
      formatMessage: formatMessage.bind(null, resolvedConfig, formatters),
      $t: formatMessage.bind(null, resolvedConfig, formatters),
      formatList: formatList.bind(null, resolvedConfig, formatters.getListFormat),
      formatListToParts: formatListToParts.bind(null, resolvedConfig, formatters.getListFormat),
      formatDisplayName: formatDisplayName.bind(null, resolvedConfig, formatters.getDisplayNames)
    });
  }

  function invariantIntlContext(intl) {
    invariant(intl, '[React Intl] Could not find required `intl` object. ' + '<IntlProvider> needs to exist in the component ancestry.');
  }
  var DEFAULT_INTL_CONFIG$1 = __assign(__assign({}, DEFAULT_INTL_CONFIG), {
    textComponent: react.Fragment
  });
  /**
   * Takes a `formatXMLElementFn`, and composes it in function, which passes
   * argument `parts` through, assigning unique key to each part, to prevent
   * "Each child in a list should have a unique "key"" React error.
   * @param formatXMLElementFn
   */

  function assignUniqueKeysToParts(formatXMLElementFn) {
    return function (parts) {
      // eslint-disable-next-line prefer-rest-params
      return formatXMLElementFn(react.Children.toArray(parts));
    };
  }
  function shallowEqual(objA, objB) {
    if (objA === objB) {
      return true;
    }

    if (!objA || !objB) {
      return false;
    }

    var aKeys = Object.keys(objA);
    var bKeys = Object.keys(objB);
    var len = aKeys.length;

    if (bKeys.length !== len) {
      return false;
    }

    for (var i = 0; i < len; i++) {
      var key = aKeys[i];

      if (objA[key] !== objB[key] || !Object.prototype.hasOwnProperty.call(objB, key)) {
        return false;
      }
    }

    return true;
  }

  var IntlContext = react.createContext(null);
  var IntlProvider = IntlContext.Provider;
  var Provider = IntlProvider;
  var Context = IntlContext;

  function useIntl() {
    var intl = react.useContext(Context);
    invariantIntlContext(intl);
    return intl;
  }

  var DisplayName;

  (function (DisplayName) {
    DisplayName["formatDate"] = "FormattedDate";
    DisplayName["formatTime"] = "FormattedTime";
    DisplayName["formatNumber"] = "FormattedNumber";
    DisplayName["formatList"] = "FormattedList"; // Note that this DisplayName is the locale display name, not to be confused with
    // the name of the enum, which is for React component display name in dev tools.

    DisplayName["formatDisplayName"] = "FormattedDisplayName";
  })(DisplayName || (DisplayName = {}));

  var DisplayNameParts;

  (function (DisplayNameParts) {
    DisplayNameParts["formatDate"] = "FormattedDateParts";
    DisplayNameParts["formatTime"] = "FormattedTimeParts";
    DisplayNameParts["formatNumber"] = "FormattedNumberParts";
    DisplayNameParts["formatList"] = "FormattedListParts";
  })(DisplayNameParts || (DisplayNameParts = {}));
  function createFormattedComponent(name) {
    var Component = function (props) {
      var intl = useIntl();

      var value = props.value,
          children = props.children,
          formatProps = __rest(props // TODO: fix TS type definition for localeMatcher upstream
      , ["value", "children"]); // TODO: fix TS type definition for localeMatcher upstream


      var formattedValue = intl[name](value, formatProps);

      if (typeof children === 'function') {
        return children(formattedValue);
      }

      var Text = intl.textComponent || react.Fragment;
      return react.createElement(Text, null, formattedValue);
    };

    Component.displayName = DisplayName[name];
    return Component;
  }

  /*
   * Copyright 2015, Yahoo Inc.
   * Copyrights licensed under the New BSD License.
   * See the accompanying LICENSE file for terms.
   */

  function processIntlConfig(config) {
    return {
      locale: config.locale,
      timeZone: config.timeZone,
      fallbackOnEmptyString: config.fallbackOnEmptyString,
      formats: config.formats,
      textComponent: config.textComponent,
      messages: config.messages,
      defaultLocale: config.defaultLocale,
      defaultFormats: config.defaultFormats,
      onError: config.onError,
      onWarn: config.onWarn,
      wrapRichTextChunksInFragment: config.wrapRichTextChunksInFragment,
      defaultRichTextElements: config.defaultRichTextElements
    };
  }

  function assignUniqueKeysToFormatXMLElementFnArgument(values) {
    if (!values) {
      return values;
    }

    return Object.keys(values).reduce(function (acc, k) {
      var v = values[k];
      acc[k] = isFormatXMLElementFn(v) ? assignUniqueKeysToParts(v) : v;
      return acc;
    }, {});
  }

  var formatMessage$1 = function (config, formatters, descriptor, rawValues) {
    var rest = [];

    for (var _i = 4; _i < arguments.length; _i++) {
      rest[_i - 4] = arguments[_i];
    }

    var values = assignUniqueKeysToFormatXMLElementFnArgument(rawValues);
    var chunks = formatMessage.apply(void 0, __spreadArray([config, formatters, descriptor, values], rest, false));

    if (Array.isArray(chunks)) {
      return react.Children.toArray(chunks);
    }

    return chunks;
  };
  /**
   * Create intl object
   * @param config intl config
   * @param cache cache for formatter instances to prevent memory leak
   */


  var createIntl$1 = function (_a, cache) {
    var rawDefaultRichTextElements = _a.defaultRichTextElements,
        config = __rest(_a, ["defaultRichTextElements"]);

    var defaultRichTextElements = assignUniqueKeysToFormatXMLElementFnArgument(rawDefaultRichTextElements);
    var coreIntl = createIntl(__assign(__assign(__assign({}, DEFAULT_INTL_CONFIG$1), config), {
      defaultRichTextElements: defaultRichTextElements
    }), cache);
    return __assign(__assign({}, coreIntl), {
      formatMessage: formatMessage$1.bind(null, {
        locale: coreIntl.locale,
        timeZone: coreIntl.timeZone,
        fallbackOnEmptyString: coreIntl.fallbackOnEmptyString,
        formats: coreIntl.formats,
        defaultLocale: coreIntl.defaultLocale,
        defaultFormats: coreIntl.defaultFormats,
        messages: coreIntl.messages,
        onError: coreIntl.onError,
        defaultRichTextElements: defaultRichTextElements
      }, coreIntl.formatters)
    });
  };

  var IntlProvider$1 =
  /** @class */
  function (_super) {
    __extends(IntlProvider, _super);

    function IntlProvider() {
      var _this = _super !== null && _super.apply(this, arguments) || this;

      _this.cache = createIntlCache();
      _this.state = {
        cache: _this.cache,
        intl: createIntl$1(processIntlConfig(_this.props), _this.cache),
        prevConfig: processIntlConfig(_this.props)
      };
      return _this;
    }

    IntlProvider.getDerivedStateFromProps = function (props, _a) {
      var prevConfig = _a.prevConfig,
          cache = _a.cache;
      var config = processIntlConfig(props);

      if (!shallowEqual(prevConfig, config)) {
        return {
          intl: createIntl$1(config, cache),
          prevConfig: config
        };
      }

      return null;
    };

    IntlProvider.prototype.render = function () {
      invariantIntlContext(this.state.intl);
      return react.createElement(Provider, {
        value: this.state.intl
      }, this.props.children);
    };

    IntlProvider.displayName = 'IntlProvider';
    IntlProvider.defaultProps = DEFAULT_INTL_CONFIG$1;
    return IntlProvider;
  }(react.PureComponent);

  /*
   * Copyright 2015, Yahoo Inc.
   * Copyrights licensed under the New BSD License.
   * See the accompanying LICENSE file for terms.
   */

  function areEqual(prevProps, nextProps) {
    var values = prevProps.values,
        otherProps = __rest(prevProps, ["values"]);

    var nextValues = nextProps.values,
        nextOtherProps = __rest(nextProps, ["values"]);

    return shallowEqual(nextValues, values) && shallowEqual(otherProps, nextOtherProps);
  }

  function FormattedMessage(props) {
    var intl = useIntl();
    var formatMessage = intl.formatMessage,
        _a = intl.textComponent,
        Text = _a === void 0 ? react.Fragment : _a;
    var id = props.id,
        description = props.description,
        defaultMessage = props.defaultMessage,
        values = props.values,
        children = props.children,
        _b = props.tagName,
        Component = _b === void 0 ? Text : _b,
        ignoreTag = props.ignoreTag;
    var descriptor = {
      id: id,
      description: description,
      defaultMessage: defaultMessage
    };
    var nodes = formatMessage(descriptor, values, {
      ignoreTag: ignoreTag
    });

    if (typeof children === 'function') {
      return children(Array.isArray(nodes) ? nodes : [nodes]);
    }

    if (Component) {
      return react.createElement(Component, null, react.Children.toArray(nodes));
    }

    return react.createElement(react.Fragment, null, nodes);
  }

  FormattedMessage.displayName = 'FormattedMessage';
  var MemoizedFormattedMessage = react.memo(FormattedMessage, areEqual);
  MemoizedFormattedMessage.displayName = 'MemoizedFormattedMessage';

  var FormattedDate = createFormattedComponent('formatDate');
  var FormattedTime = createFormattedComponent('formatTime'); // @ts-ignore issue w/ TS Intl types

  var FormattedNumber = createFormattedComponent('formatNumber');
  var FormattedList = createFormattedComponent('formatList');
  var FormattedDisplayName = createFormattedComponent('formatDisplayName');

  var messagesDE = {
  	"credit-features.credit-cost-display": "{creditCost} (APR {taegPercentage})",
  	"credit-features.information": "Ein Kredit verpflichtet Sie und muss zurückgezahlt werden. Prüfen Sie Ihre Rückzahlungsfähigkeit, bevor Sie sich verpflichten.",
  	"credit-features.total-credit-cost": "Davon Kreditkosten",
  	"eligibility-modal.bullet-1": "Wählen Sie <strong>Alma</strong> beim Check-out. ",
  	"eligibility-modal.bullet-2": "Lassen Sie sich führen und bestätigen Sie Ihre Zahlung in <strong>2 Minuten.</strong>",
  	"eligibility-modal.bullet-3": "<strong>Verlieren Sie nicht die Kontrolle,</strong> indem Sie Ihre Fälligkeitstermine nach Ihrem eigenen Tempo vor- oder zurückverlegen.",
  	"eligibility-modal.no-eligibility": "Ups, die Prüfung hat anscheinend nicht funktioniert.",
  	"eligibility-modal.p1x-bullet-1": "Wählen Sie <strong>Alma - Jetzt bezahlen</strong> zum Zeitpunkt der Zahlung.",
  	"eligibility-modal.p1x-bullet-2": "Tragen Sie Ihre <strong>Kreditkarteninformationen ein.</strong>",
  	"eligibility-modal.p1x-bullet-3": "<strong> Ihre Zahlung wird sofort bestätigt! </strong>",
  	"eligibility-modal.title-deferred-plan": "Zahlen Sie mit Alma in Raten oder später.",
  	"eligibility-modal.title-normal": "Zahlen Sie mit Alma in mehreren Raten.",
  	"eligibility-modal.title-pay-now": "Bezahlen Sie auf einmal mit Alma per Kreditkarte.",
  	"installments.today": "Heute",
  	"installments.total-amount": "Gesamtsumme",
  	"installments.total-fees": "Davon Kosten",
  	"payment-plan-strings.day-abbreviation": "T{deferredDays}",
  	"payment-plan-strings.default-message": "Bezahlen Sie zinsfrei in mehreren Raten mit Alma.",
  	"payment-plan-strings.deferred": "{totalAmount} zu zahlen am {dueDate}",
  	"payment-plan-strings.ineligible-greater-than-max": "Bis zu {maxAmount}",
  	"payment-plan-strings.ineligible-lower-than-min": "Ab {minAmount}",
  	"payment-plan-strings.month-abbreviation": "M{deferredMonths}",
  	"payment-plan-strings.multiple-installments": "{numberOfRemainingInstallments, plural, one {{firstInstallmentAmount} dann {numberOfRemainingInstallments} x {othersInstallmentAmount}} other {{firstInstallmentAmount} dann {numberOfRemainingInstallments} x {othersInstallmentAmount}}}",
  	"payment-plan-strings.multiple-installments-same-amount": "{installmentsCount} x {totalAmount}",
  	"payment-plan-strings.no-fee": "(0% Finanzierung)",
  	"payment-plan-strings.pay-now": "Zahlen Sie jetzt {totalAmount}",
  	"payment-plan-strings.pay.now.button": "Jetzt bezahlen"
  };

  var messagesEN = {
  	"credit-features.credit-cost-display": "{creditCost} (APR {taegPercentage})",
  	"credit-features.information": "A loan commits you and must be repaid. Check your ability to repay before committing yourself.",
  	"credit-features.total-credit-cost": "Of which cost of credit",
  	"eligibility-modal.bullet-1": "Choose <strong>Alma</strong> at checkout.",
  	"eligibility-modal.bullet-2": "Let us guide you and validate your payment in <strong>2 minutes.</strong>",
  	"eligibility-modal.bullet-3": "<strong>Stay in control</strong> by moving your deadlines forward or backward at your own pace.",
  	"eligibility-modal.no-eligibility": "Oops, looks like the simulation didn't work.",
  	"eligibility-modal.p1x-bullet-1": "Choose <strong>Alma - Pay now</strong> at checkout.",
  	"eligibility-modal.p1x-bullet-2": "Fill in your <strong>credit card information.</strong>",
  	"eligibility-modal.p1x-bullet-3": "<strong>The validation </strong> of your payment is instantaneous!",
  	"eligibility-modal.title-deferred-plan": "Pay in installments or later by credit card with Alma.",
  	"eligibility-modal.title-normal": "Pay in installments by credit card with Alma.",
  	"eligibility-modal.title-pay-now": "Pay cash by credit card with Alma.",
  	"installments.today": "Today",
  	"installments.total-amount": "Total",
  	"installments.total-fees": "Of which costs (incl. VAT)",
  	"payment-plan-strings.day-abbreviation": "D{deferredDays}",
  	"payment-plan-strings.default-message": "Pay in installments with Alma",
  	"payment-plan-strings.deferred": "{totalAmount} to pay the {dueDate}",
  	"payment-plan-strings.ineligible-greater-than-max": "Until {maxAmount}",
  	"payment-plan-strings.ineligible-lower-than-min": "From {minAmount}",
  	"payment-plan-strings.month-abbreviation": "M{deferredMonths}",
  	"payment-plan-strings.multiple-installments": "{numberOfRemainingInstallments, plural, one {{firstInstallmentAmount} then {numberOfRemainingInstallments} x {othersInstallmentAmount}} other {{firstInstallmentAmount} then {numberOfRemainingInstallments} x {othersInstallmentAmount}}}",
  	"payment-plan-strings.multiple-installments-same-amount": "{installmentsCount} x {totalAmount}",
  	"payment-plan-strings.no-fee": "(free of charge)",
  	"payment-plan-strings.pay-now": "Pay now {totalAmount}",
  	"payment-plan-strings.pay.now.button": "Pay now"
  };

  var messagesES = {
  	"credit-features.credit-cost-display": "{creditCost} (TAE {taegPercentage})",
  	"credit-features.information": "Un préstamo te compromete y debe ser devuelto. Comprueba tu capacidad financiera antes de comprometerte.",
  	"credit-features.total-credit-cost": "Coste de crédito (incl. en el total)",
  	"eligibility-modal.bullet-1": "Elige <strong>Alma</strong> como método de pago.",
  	"eligibility-modal.bullet-2": "Déjate guiar y válida tu pago en <strong>2 minutos.</strong>",
  	"eligibility-modal.bullet-3": "<strong>Mantén el control</strong> adelantando o retrasando los plazos a su propio ritmo.",
  	"eligibility-modal.no-eligibility": "Uy, parece que la simulación no ha funcionado.",
  	"eligibility-modal.p1x-bullet-1": "Elije <strong>Alma - Pagar ahora</strong> al momento de la compra.",
  	"eligibility-modal.p1x-bullet-2": "Introduzca los datos de tu tarjeta bancaria en <strong>.</strong>",
  	"eligibility-modal.p1x-bullet-3": "<strong>¡La validación </strong> de tu pago es instantánea!",
  	"eligibility-modal.title-deferred-plan": "Paga a plazos o posteriormente con tarjeta bancaria con Alma.",
  	"eligibility-modal.title-normal": "Pagar a plazos con tarjeta bancaria con Alma.",
  	"eligibility-modal.title-pay-now": "Paga de contado con tu tarjeta bancaria a través de alma.",
  	"installments.today": "Hoy",
  	"installments.total-amount": "Total",
  	"installments.total-fees": "De los cuales, costes (IVA incluido)",
  	"payment-plan-strings.day-abbreviation": "D{deferredDays}",
  	"payment-plan-strings.default-message": "Pagar a plazos con Alma",
  	"payment-plan-strings.deferred": "{totalAmount} a pagar el {dueDate}",
  	"payment-plan-strings.ineligible-greater-than-max": "Hasta {maxAmount}",
  	"payment-plan-strings.ineligible-lower-than-min": "Desde {minAmount}",
  	"payment-plan-strings.month-abbreviation": "M{deferredMonths}",
  	"payment-plan-strings.multiple-installments": "{numberOfRemainingInstallments, plural, one {{firstInstallmentAmount} hoy, después {numberOfRemainingInstallments} mensualidad de {othersInstallmentAmount}} other {{firstInstallmentAmount} hoy, después {numberOfRemainingInstallments} mensualidades de {othersInstallmentAmount}}}",
  	"payment-plan-strings.multiple-installments-same-amount": "{installmentsCount} plazos de {totalAmount}",
  	"payment-plan-strings.no-fee": "(sin intereses)",
  	"payment-plan-strings.pay-now": "Pague ahora {totalAmount}",
  	"payment-plan-strings.pay.now.button": "Pagar ahora"
  };

  var messagesFR = {
  	"credit-features.credit-cost-display": "{creditCost} (TAEG {taegPercentage})",
  	"credit-features.information": "Un crédit vous engage et doit être remboursé. Vérifiez vos capacités de remboursement avant de vous engager.",
  	"credit-features.total-credit-cost": "Dont coût du crédit",
  	"eligibility-modal.bullet-1": "Choisissez <strong>Alma</strong> au moment du paiement.",
  	"eligibility-modal.bullet-2": "Laissez-vous guider et validez votre paiement en <strong>2 minutes.</strong>",
  	"eligibility-modal.bullet-3": "<strong>Gardez le contrôle</strong> en avançant ou reculant vos échéances à votre rythme.",
  	"eligibility-modal.no-eligibility": "Oups, il semblerait que la simulation n'ait pas fonctionné.",
  	"eligibility-modal.p1x-bullet-1": "Choisissez <strong>Alma - Payer maintenant</strong> au moment du paiement.",
  	"eligibility-modal.p1x-bullet-2": "Renseignez les informations de votre <strong>carte bancaire.</strong>",
  	"eligibility-modal.p1x-bullet-3": "<strong>La validation </strong> de votre paiement est instantanée !",
  	"eligibility-modal.title-deferred-plan": "Payez en plusieurs fois ou plus tard par carte bancaire avec Alma.",
  	"eligibility-modal.title-normal": "Payez en plusieurs fois par carte bancaire avec Alma.",
  	"eligibility-modal.title-pay-now": "Payez comptant par carte bancaire avec Alma.",
  	"installments.today": "Aujourd'hui",
  	"installments.total-amount": "Total",
  	"installments.total-fees": "Dont frais (TTC)",
  	"payment-plan-strings.day-abbreviation": "J{deferredDays}",
  	"payment-plan-strings.default-message": "Payez en plusieurs fois avec Alma",
  	"payment-plan-strings.deferred": "{totalAmount} à payer le {dueDate}",
  	"payment-plan-strings.ineligible-greater-than-max": "Jusqu'à {maxAmount}",
  	"payment-plan-strings.ineligible-lower-than-min": "À partir de {minAmount}",
  	"payment-plan-strings.month-abbreviation": "M{deferredMonths}",
  	"payment-plan-strings.multiple-installments": "{numberOfRemainingInstallments, plural, one {{firstInstallmentAmount} puis {numberOfRemainingInstallments} x {othersInstallmentAmount}} other {{firstInstallmentAmount} puis {numberOfRemainingInstallments} x {othersInstallmentAmount}}}",
  	"payment-plan-strings.multiple-installments-same-amount": "{installmentsCount} x {totalAmount}",
  	"payment-plan-strings.no-fee": "(sans frais)",
  	"payment-plan-strings.pay-now": "Payer maintenant {totalAmount}",
  	"payment-plan-strings.pay.now.button": "Payer maintenant"
  };

  var messagesIT = {
  	"credit-features.credit-cost-display": "{creditCost} (TAEG {taegPercentage})",
  	"credit-features.information": "Un pagamento rateale ti impegna e deve essere ripagato. Verifica la tua disponibilità finanziaria prima di impegnarti.",
  	"credit-features.total-credit-cost": "Di cui commissioni",
  	"eligibility-modal.bullet-1": "Seleziona <strong>Alma</strong> come metodo di pagamento.",
  	"eligibility-modal.bullet-2": "Segui le istruzioni e completa l'acquisto <strong>in meno di 2 minuti.</strong>",
  	"eligibility-modal.bullet-3": "<strong>Gestisci il tuo piano di pagamento</strong> e modifica le scadenze secondo le tue esigenze.",
  	"eligibility-modal.no-eligibility": "Ops, sembra che qualcosa non abbia funzionato.",
  	"eligibility-modal.p1x-bullet-1": "Scegli <strong>Alma - Paga ora</strong> alla cassa.",
  	"eligibility-modal.p1x-bullet-2": "Inserisci i dati della tua <strong>carta bancaria.</strong>",
  	"eligibility-modal.p1x-bullet-3": "Ricevi la conferma di pagamento <strong>istantaneamente!</strong>",
  	"eligibility-modal.title-deferred-plan": "Paga a rate con Alma, senza registrazione.",
  	"eligibility-modal.title-normal": "Paga a rate con Alma, senza registrazione.",
  	"eligibility-modal.title-pay-now": "Paga ora con carta tramite Alma.",
  	"installments.today": "Oggi",
  	"installments.total-amount": "Totale",
  	"installments.total-fees": "Di cui commissioni",
  	"payment-plan-strings.day-abbreviation": "G{deferredDays}",
  	"payment-plan-strings.default-message": "Paga a rate con Alma, senza registrazione.",
  	"payment-plan-strings.deferred": "{totalAmount} da pagare il {dueDate}",
  	"payment-plan-strings.ineligible-greater-than-max": "Disponibile fino a {maxAmount}",
  	"payment-plan-strings.ineligible-lower-than-min": "Pagamento rateale disponibile a partire da {minAmount}",
  	"payment-plan-strings.month-abbreviation": "M{deferredMonths}",
  	"payment-plan-strings.multiple-installments": "{numberOfRemainingInstallments, plural, one {Oggi paghi {firstInstallmentAmount} poi tra {numberOfRemainingInstallments} mese {othersInstallmentAmount}} other {Oggi paghi {firstInstallmentAmount} poi {numberOfRemainingInstallments} rate mensili di {othersInstallmentAmount}}}",
  	"payment-plan-strings.multiple-installments-same-amount": "In {installmentsCount} rate mensili di {totalAmount}",
  	"payment-plan-strings.no-fee": "(senza interessi)",
  	"payment-plan-strings.pay-now": "Paga ora {totalAmount}",
  	"payment-plan-strings.pay.now.button": "Paga ora"
  };

  var messagesNL = {
  	"credit-features.credit-cost-display": "{creditCost} (gemiddeld rente percentage {taegPercentage})",
  	"credit-features.information": "Een lening is bindend en moet worden terugbetaald. Ga na of je het kunt terugbetalen voordat je jezelf vastlegt.",
  	"credit-features.total-credit-cost": "Waarvan kredietkosten",
  	"eligibility-modal.bullet-1": "Kies <strong>Alma</strong> bij het afrekenen om de eerste termijnbetaling te voldoen. Dit kan gemakkelijk via jouw favoriete betaalmethode.",
  	"eligibility-modal.bullet-2": "Betaal gemakkelijk <strong>binnen 1 minuut </strong>. Je hebt hier geen account voor nodig.",
  	"eligibility-modal.bullet-3": "<strong>Behoudt de controle </strong> en bepaal je eigen tempo. Bij Alma schuif je de deadlines gemakkelijk vooruit of achteruit. Zonder bijkomende kosten.",
  	"eligibility-modal.no-eligibility": "Oeps, het lijkt erop dat de simulatie niet werkte.",
  	"eligibility-modal.p1x-bullet-1": "Kies <strong>Alma - Betaal nu</strong> bij het afrekenen om de eerste termijnbetaling te voldoen. Dit kan gemakkelijk via jouw favoriete betaalmethode.",
  	"eligibility-modal.p1x-bullet-2": "Vul jouw <strong>gegevens </strong> in",
  	"eligibility-modal.p1x-bullet-3": "<strong>De bevestiging </strong> van jouw betaling is onmiddellijk!",
  	"eligibility-modal.title-deferred-plan": "Betaal in termijnen bij Alma - helemaal rentevrij.",
  	"eligibility-modal.title-normal": "Betaal in termijnen bij Alma - helemaal rentevrij.",
  	"eligibility-modal.title-pay-now": "Betaal met iDEAL of per creditcard bij Alma.",
  	"installments.today": "Vandaag",
  	"installments.total-amount": "Totaal",
  	"installments.total-fees": "Waarvan kosten (incl. BTW)",
  	"payment-plan-strings.day-abbreviation": "D{deferredDays}",
  	"payment-plan-strings.default-message": "Betaal in termijnen bij Alma - helemaal rentevrij",
  	"payment-plan-strings.deferred": "{totalAmount} te betalen op {dueDate}",
  	"payment-plan-strings.ineligible-greater-than-max": "Tot {maxAmount}",
  	"payment-plan-strings.ineligible-lower-than-min": "Vanaf {minAmount}",
  	"payment-plan-strings.month-abbreviation": "M{deferredMonths}",
  	"payment-plan-strings.multiple-installments": "{numberOfRemainingInstallments, plural, one {{firstInstallmentAmount} dan {numberOfRemainingInstallments} x {othersInstallmentAmount}} other {{firstInstallmentAmount} dan {numberOfRemainingInstallments} x {othersInstallmentAmount}}}",
  	"payment-plan-strings.multiple-installments-same-amount": "{installmentsCount} x {totalAmount}",
  	"payment-plan-strings.no-fee": "(zonder kosten)",
  	"payment-plan-strings.pay-now": "Betaal nu {totalAmount}",
  	"payment-plan-strings.pay.now.button": "Betaal nu"
  };

  var messagesPT = {
  	"credit-features.credit-cost-display": "{creditCost} (TAEG {taegPercentage})",
  	"credit-features.information": "Um crédito é um compromisso e deve ser reembolsado. Verifique a sua capacidade de pagar antes de se comprometer.",
  	"credit-features.total-credit-cost": "Incluindo comissões de crédito",
  	"eligibility-modal.bullet-1": "Seleccionar <strong>Alma - Pay Now</strong> no final da compra.",
  	"eligibility-modal.bullet-2": "Siga as instruções e valide o seu pagamento em <strong>2 minutos.</strong>",
  	"eligibility-modal.bullet-3": "<strong>Mantenha o controlo</strong> adiando as datas de pagamento ao seu próprio ritmo.",
  	"eligibility-modal.no-eligibility": "Ups, parece que a simulação não funcionou.",
  	"eligibility-modal.p1x-bullet-1": "Escolha <strong>Alma - Pague agora</strong> no checkout.",
  	"eligibility-modal.p1x-bullet-2": "Preencha os dados do seu <strong>cartão de bancário.</strong>",
  	"eligibility-modal.p1x-bullet-3": "<strong>A validação </strong> do seu pagamento é instantânea!",
  	"eligibility-modal.title-deferred-plan": "Pagar em prestações ou mais tarde por cartão de crédito com Alma.",
  	"eligibility-modal.title-normal": "Pagar em prestações por cartão bancário com a Alma.",
  	"eligibility-modal.title-pay-now": "Pague na integralidade com a Alma por cartão bancário.",
  	"installments.today": "Hoje",
  	"installments.total-amount": "Total",
  	"installments.total-fees": "Das quais comissões (incl. IVA)",
  	"payment-plan-strings.day-abbreviation": "D{deferredDays}",
  	"payment-plan-strings.default-message": "Pagamento em prestações com a Alma",
  	"payment-plan-strings.deferred": "{totalAmount} a pagar em {dueDate}",
  	"payment-plan-strings.ineligible-greater-than-max": "Até {maxAmount}",
  	"payment-plan-strings.ineligible-lower-than-min": "A partir de {minAmount}",
  	"payment-plan-strings.month-abbreviation": "M{deferredMonths}",
  	"payment-plan-strings.multiple-installments": "{numberOfRemainingInstallments, plural, one {{firstInstallmentAmount} depois {numberOfRemainingInstallments} x {othersInstallmentAmount}} other {{firstInstallmentAmount} depois {numberOfRemainingInstallments} x {othersInstallmentAmount}}}",
  	"payment-plan-strings.multiple-installments-same-amount": "{installmentsCount} x {totalAmount}",
  	"payment-plan-strings.no-fee": "(sem encargos)",
  	"payment-plan-strings.pay-now": "Pagar agora {totalAmount}",
  	"payment-plan-strings.pay.now.button": "Pagar agora"
  };

  var getTranslationsByLocale = function getTranslationsByLocale(locale) {
    // A CMS plugin can add LCID format like : 'fr-FR' instead of 'fr'.
    // Instead of specifying all possibilities we just remove the last part of the string.
    var merchantLocale = locale.slice(0, 2);

    switch (merchantLocale) {
      case Locale.fr:
        return messagesFR;

      case Locale.es:
        return messagesES;

      case Locale.it:
        return messagesIT;

      case Locale.de:
        return messagesDE;

      case Locale.pt:
        return messagesPT;

      case Locale.nl:
        return messagesNL;

      case Locale.en:
      default:
        return messagesEN;
    }
  };

  var Provider$1 = function Provider(_ref) {
    var children = _ref.children,
        locale = _ref.locale;
    return /*#__PURE__*/react.createElement(IntlProvider$1, {
      messages: getTranslationsByLocale(locale),
      locale: locale,
      defaultLocale: "en"
    }, children);
  };

  var scheduler_production_min = createCommonjsModule(function (module, exports) {

    var f, g, h, k;

    if ("object" === typeof performance && "function" === typeof performance.now) {
      var l = performance;

      exports.unstable_now = function () {
        return l.now();
      };
    } else {
      var p = Date,
          q = p.now();

      exports.unstable_now = function () {
        return p.now() - q;
      };
    }

    if ("undefined" === typeof window || "function" !== typeof MessageChannel) {
      var t = null,
          u = null,
          w = function () {
        if (null !== t) try {
          var a = exports.unstable_now();
          t(!0, a);
          t = null;
        } catch (b) {
          throw setTimeout(w, 0), b;
        }
      };

      f = function (a) {
        null !== t ? setTimeout(f, 0, a) : (t = a, setTimeout(w, 0));
      };

      g = function (a, b) {
        u = setTimeout(a, b);
      };

      h = function () {
        clearTimeout(u);
      };

      exports.unstable_shouldYield = function () {
        return !1;
      };

      k = exports.unstable_forceFrameRate = function () {};
    } else {
      var x = window.setTimeout,
          y = window.clearTimeout;

      if ("undefined" !== typeof console) {
        var z = window.cancelAnimationFrame;
        "function" !== typeof window.requestAnimationFrame && console.error("This browser doesn't support requestAnimationFrame. Make sure that you load a polyfill in older browsers. https://reactjs.org/link/react-polyfills");
        "function" !== typeof z && console.error("This browser doesn't support cancelAnimationFrame. Make sure that you load a polyfill in older browsers. https://reactjs.org/link/react-polyfills");
      }

      var A = !1,
          B = null,
          C = -1,
          D = 5,
          E = 0;

      exports.unstable_shouldYield = function () {
        return exports.unstable_now() >= E;
      };

      k = function () {};

      exports.unstable_forceFrameRate = function (a) {
        0 > a || 125 < a ? console.error("forceFrameRate takes a positive int between 0 and 125, forcing frame rates higher than 125 fps is not supported") : D = 0 < a ? Math.floor(1E3 / a) : 5;
      };

      var F = new MessageChannel(),
          G = F.port2;

      F.port1.onmessage = function () {
        if (null !== B) {
          var a = exports.unstable_now();
          E = a + D;

          try {
            B(!0, a) ? G.postMessage(null) : (A = !1, B = null);
          } catch (b) {
            throw G.postMessage(null), b;
          }
        } else A = !1;
      };

      f = function (a) {
        B = a;
        A || (A = !0, G.postMessage(null));
      };

      g = function (a, b) {
        C = x(function () {
          a(exports.unstable_now());
        }, b);
      };

      h = function () {
        y(C);
        C = -1;
      };
    }

    function H(a, b) {
      var c = a.length;
      a.push(b);

      a: for (;;) {
        var d = c - 1 >>> 1,
            e = a[d];
        if (void 0 !== e && 0 < I(e, b)) a[d] = b, a[c] = e, c = d;else break a;
      }
    }

    function J(a) {
      a = a[0];
      return void 0 === a ? null : a;
    }

    function K(a) {
      var b = a[0];

      if (void 0 !== b) {
        var c = a.pop();

        if (c !== b) {
          a[0] = c;

          a: for (var d = 0, e = a.length; d < e;) {
            var m = 2 * (d + 1) - 1,
                n = a[m],
                v = m + 1,
                r = a[v];
            if (void 0 !== n && 0 > I(n, c)) void 0 !== r && 0 > I(r, n) ? (a[d] = r, a[v] = c, d = v) : (a[d] = n, a[m] = c, d = m);else if (void 0 !== r && 0 > I(r, c)) a[d] = r, a[v] = c, d = v;else break a;
          }
        }

        return b;
      }

      return null;
    }

    function I(a, b) {
      var c = a.sortIndex - b.sortIndex;
      return 0 !== c ? c : a.id - b.id;
    }

    var L = [],
        M = [],
        N = 1,
        O = null,
        P = 3,
        Q = !1,
        R = !1,
        S = !1;

    function T(a) {
      for (var b = J(M); null !== b;) {
        if (null === b.callback) K(M);else if (b.startTime <= a) K(M), b.sortIndex = b.expirationTime, H(L, b);else break;
        b = J(M);
      }
    }

    function U(a) {
      S = !1;
      T(a);
      if (!R) if (null !== J(L)) R = !0, f(V);else {
        var b = J(M);
        null !== b && g(U, b.startTime - a);
      }
    }

    function V(a, b) {
      R = !1;
      S && (S = !1, h());
      Q = !0;
      var c = P;

      try {
        T(b);

        for (O = J(L); null !== O && (!(O.expirationTime > b) || a && !exports.unstable_shouldYield());) {
          var d = O.callback;

          if ("function" === typeof d) {
            O.callback = null;
            P = O.priorityLevel;
            var e = d(O.expirationTime <= b);
            b = exports.unstable_now();
            "function" === typeof e ? O.callback = e : O === J(L) && K(L);
            T(b);
          } else K(L);

          O = J(L);
        }

        if (null !== O) var m = !0;else {
          var n = J(M);
          null !== n && g(U, n.startTime - b);
          m = !1;
        }
        return m;
      } finally {
        O = null, P = c, Q = !1;
      }
    }

    var W = k;
    exports.unstable_IdlePriority = 5;
    exports.unstable_ImmediatePriority = 1;
    exports.unstable_LowPriority = 4;
    exports.unstable_NormalPriority = 3;
    exports.unstable_Profiling = null;
    exports.unstable_UserBlockingPriority = 2;

    exports.unstable_cancelCallback = function (a) {
      a.callback = null;
    };

    exports.unstable_continueExecution = function () {
      R || Q || (R = !0, f(V));
    };

    exports.unstable_getCurrentPriorityLevel = function () {
      return P;
    };

    exports.unstable_getFirstCallbackNode = function () {
      return J(L);
    };

    exports.unstable_next = function (a) {
      switch (P) {
        case 1:
        case 2:
        case 3:
          var b = 3;
          break;

        default:
          b = P;
      }

      var c = P;
      P = b;

      try {
        return a();
      } finally {
        P = c;
      }
    };

    exports.unstable_pauseExecution = function () {};

    exports.unstable_requestPaint = W;

    exports.unstable_runWithPriority = function (a, b) {
      switch (a) {
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
          break;

        default:
          a = 3;
      }

      var c = P;
      P = a;

      try {
        return b();
      } finally {
        P = c;
      }
    };

    exports.unstable_scheduleCallback = function (a, b, c) {
      var d = exports.unstable_now();
      "object" === typeof c && null !== c ? (c = c.delay, c = "number" === typeof c && 0 < c ? d + c : d) : c = d;

      switch (a) {
        case 1:
          var e = -1;
          break;

        case 2:
          e = 250;
          break;

        case 5:
          e = 1073741823;
          break;

        case 4:
          e = 1E4;
          break;

        default:
          e = 5E3;
      }

      e = c + e;
      a = {
        id: N++,
        callback: b,
        priorityLevel: a,
        startTime: c,
        expirationTime: e,
        sortIndex: -1
      };
      c > d ? (a.sortIndex = c, H(M, a), null === J(L) && a === J(M) && (S ? h() : S = !0, g(U, c - d))) : (a.sortIndex = e, H(L, a), R || Q || (R = !0, f(V)));
      return a;
    };

    exports.unstable_wrapCallback = function (a) {
      var b = P;
      return function () {
        var c = P;
        P = b;

        try {
          return a.apply(this, arguments);
        } finally {
          P = c;
        }
      };
    };
  });

  var scheduler_development = createCommonjsModule(function (module, exports) {
  });

  var scheduler = createCommonjsModule(function (module) {

    {
      module.exports = scheduler_production_min;
    }
  });

  function y$1(a) {
    for (var b = "https://reactjs.org/docs/error-decoder.html?invariant=" + a, c = 1; c < arguments.length; c++) b += "&args[]=" + encodeURIComponent(arguments[c]);

    return "Minified React error #" + a + "; visit " + b + " for the full message or use the non-minified dev environment for full errors and additional helpful warnings.";
  }

  if (!react) throw Error(y$1(227));
  var ba = new Set(),
      ca = {};

  function da(a, b) {
    ea(a, b);
    ea(a + "Capture", b);
  }

  function ea(a, b) {
    ca[a] = b;

    for (a = 0; a < b.length; a++) ba.add(b[a]);
  }

  var fa = !("undefined" === typeof window || "undefined" === typeof window.document || "undefined" === typeof window.document.createElement),
      ha = /^[:A-Z_a-z\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u02FF\u0370-\u037D\u037F-\u1FFF\u200C-\u200D\u2070-\u218F\u2C00-\u2FEF\u3001-\uD7FF\uF900-\uFDCF\uFDF0-\uFFFD][:A-Z_a-z\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u02FF\u0370-\u037D\u037F-\u1FFF\u200C-\u200D\u2070-\u218F\u2C00-\u2FEF\u3001-\uD7FF\uF900-\uFDCF\uFDF0-\uFFFD\-.0-9\u00B7\u0300-\u036F\u203F-\u2040]*$/,
      ia = Object.prototype.hasOwnProperty,
      ja = {},
      ka = {};

  function la(a) {
    if (ia.call(ka, a)) return !0;
    if (ia.call(ja, a)) return !1;
    if (ha.test(a)) return ka[a] = !0;
    ja[a] = !0;
    return !1;
  }

  function ma(a, b, c, d) {
    if (null !== c && 0 === c.type) return !1;

    switch (typeof b) {
      case "function":
      case "symbol":
        return !0;

      case "boolean":
        if (d) return !1;
        if (null !== c) return !c.acceptsBooleans;
        a = a.toLowerCase().slice(0, 5);
        return "data-" !== a && "aria-" !== a;

      default:
        return !1;
    }
  }

  function na(a, b, c, d) {
    if (null === b || "undefined" === typeof b || ma(a, b, c, d)) return !0;
    if (d) return !1;
    if (null !== c) switch (c.type) {
      case 3:
        return !b;

      case 4:
        return !1 === b;

      case 5:
        return isNaN(b);

      case 6:
        return isNaN(b) || 1 > b;
    }
    return !1;
  }

  function B(a, b, c, d, e, f, g) {
    this.acceptsBooleans = 2 === b || 3 === b || 4 === b;
    this.attributeName = d;
    this.attributeNamespace = e;
    this.mustUseProperty = c;
    this.propertyName = a;
    this.type = b;
    this.sanitizeURL = f;
    this.removeEmptyString = g;
  }

  var D = {};
  "children dangerouslySetInnerHTML defaultValue defaultChecked innerText suppressContentEditableWarning suppressHydrationWarning style".split(" ").forEach(function (a) {
    D[a] = new B(a, 0, !1, a, null, !1, !1);
  });
  [["acceptCharset", "accept-charset"], ["className", "class"], ["htmlFor", "for"], ["httpEquiv", "http-equiv"]].forEach(function (a) {
    var b = a[0];
    D[b] = new B(b, 1, !1, a[1], null, !1, !1);
  });
  ["contentEditable", "draggable", "spellCheck", "value"].forEach(function (a) {
    D[a] = new B(a, 2, !1, a.toLowerCase(), null, !1, !1);
  });
  ["autoReverse", "externalResourcesRequired", "focusable", "preserveAlpha"].forEach(function (a) {
    D[a] = new B(a, 2, !1, a, null, !1, !1);
  });
  "allowFullScreen async autoFocus autoPlay controls default defer disabled disablePictureInPicture disableRemotePlayback formNoValidate hidden loop noModule noValidate open playsInline readOnly required reversed scoped seamless itemScope".split(" ").forEach(function (a) {
    D[a] = new B(a, 3, !1, a.toLowerCase(), null, !1, !1);
  });
  ["checked", "multiple", "muted", "selected"].forEach(function (a) {
    D[a] = new B(a, 3, !0, a, null, !1, !1);
  });
  ["capture", "download"].forEach(function (a) {
    D[a] = new B(a, 4, !1, a, null, !1, !1);
  });
  ["cols", "rows", "size", "span"].forEach(function (a) {
    D[a] = new B(a, 6, !1, a, null, !1, !1);
  });
  ["rowSpan", "start"].forEach(function (a) {
    D[a] = new B(a, 5, !1, a.toLowerCase(), null, !1, !1);
  });
  var oa = /[\-:]([a-z])/g;

  function pa(a) {
    return a[1].toUpperCase();
  }

  "accent-height alignment-baseline arabic-form baseline-shift cap-height clip-path clip-rule color-interpolation color-interpolation-filters color-profile color-rendering dominant-baseline enable-background fill-opacity fill-rule flood-color flood-opacity font-family font-size font-size-adjust font-stretch font-style font-variant font-weight glyph-name glyph-orientation-horizontal glyph-orientation-vertical horiz-adv-x horiz-origin-x image-rendering letter-spacing lighting-color marker-end marker-mid marker-start overline-position overline-thickness paint-order panose-1 pointer-events rendering-intent shape-rendering stop-color stop-opacity strikethrough-position strikethrough-thickness stroke-dasharray stroke-dashoffset stroke-linecap stroke-linejoin stroke-miterlimit stroke-opacity stroke-width text-anchor text-decoration text-rendering underline-position underline-thickness unicode-bidi unicode-range units-per-em v-alphabetic v-hanging v-ideographic v-mathematical vector-effect vert-adv-y vert-origin-x vert-origin-y word-spacing writing-mode xmlns:xlink x-height".split(" ").forEach(function (a) {
    var b = a.replace(oa, pa);
    D[b] = new B(b, 1, !1, a, null, !1, !1);
  });
  "xlink:actuate xlink:arcrole xlink:role xlink:show xlink:title xlink:type".split(" ").forEach(function (a) {
    var b = a.replace(oa, pa);
    D[b] = new B(b, 1, !1, a, "http://www.w3.org/1999/xlink", !1, !1);
  });
  ["xml:base", "xml:lang", "xml:space"].forEach(function (a) {
    var b = a.replace(oa, pa);
    D[b] = new B(b, 1, !1, a, "http://www.w3.org/XML/1998/namespace", !1, !1);
  });
  ["tabIndex", "crossOrigin"].forEach(function (a) {
    D[a] = new B(a, 1, !1, a.toLowerCase(), null, !1, !1);
  });
  D.xlinkHref = new B("xlinkHref", 1, !1, "xlink:href", "http://www.w3.org/1999/xlink", !0, !1);
  ["src", "href", "action", "formAction"].forEach(function (a) {
    D[a] = new B(a, 1, !1, a.toLowerCase(), null, !0, !0);
  });

  function qa(a, b, c, d) {
    var e = D.hasOwnProperty(b) ? D[b] : null;
    var f = null !== e ? 0 === e.type : d ? !1 : !(2 < b.length) || "o" !== b[0] && "O" !== b[0] || "n" !== b[1] && "N" !== b[1] ? !1 : !0;
    f || (na(b, c, e, d) && (c = null), d || null === e ? la(b) && (null === c ? a.removeAttribute(b) : a.setAttribute(b, "" + c)) : e.mustUseProperty ? a[e.propertyName] = null === c ? 3 === e.type ? !1 : "" : c : (b = e.attributeName, d = e.attributeNamespace, null === c ? a.removeAttribute(b) : (e = e.type, c = 3 === e || 4 === e && !0 === c ? "" : "" + c, d ? a.setAttributeNS(d, b, c) : a.setAttribute(b, c))));
  }

  var ra = react.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED,
      sa = 60103,
      ta = 60106,
      ua = 60107,
      wa = 60108,
      xa = 60114,
      ya = 60109,
      za = 60110,
      Aa = 60112,
      Ba = 60113,
      Ca = 60120,
      Da = 60115,
      Ea = 60116,
      Fa = 60121,
      Ga = 60128,
      Ha = 60129,
      Ia = 60130,
      Ja = 60131;

  if ("function" === typeof Symbol && Symbol.for) {
    var E = Symbol.for;
    sa = E("react.element");
    ta = E("react.portal");
    ua = E("react.fragment");
    wa = E("react.strict_mode");
    xa = E("react.profiler");
    ya = E("react.provider");
    za = E("react.context");
    Aa = E("react.forward_ref");
    Ba = E("react.suspense");
    Ca = E("react.suspense_list");
    Da = E("react.memo");
    Ea = E("react.lazy");
    Fa = E("react.block");
    E("react.scope");
    Ga = E("react.opaque.id");
    Ha = E("react.debug_trace_mode");
    Ia = E("react.offscreen");
    Ja = E("react.legacy_hidden");
  }

  var Ka = "function" === typeof Symbol && Symbol.iterator;

  function La(a) {
    if (null === a || "object" !== typeof a) return null;
    a = Ka && a[Ka] || a["@@iterator"];
    return "function" === typeof a ? a : null;
  }

  var Ma;

  function Na(a) {
    if (void 0 === Ma) try {
      throw Error();
    } catch (c) {
      var b = c.stack.trim().match(/\n( *(at )?)/);
      Ma = b && b[1] || "";
    }
    return "\n" + Ma + a;
  }

  var Oa = !1;

  function Pa(a, b) {
    if (!a || Oa) return "";
    Oa = !0;
    var c = Error.prepareStackTrace;
    Error.prepareStackTrace = void 0;

    try {
      if (b) {
        if (b = function () {
          throw Error();
        }, Object.defineProperty(b.prototype, "props", {
          set: function () {
            throw Error();
          }
        }), "object" === typeof Reflect && Reflect.construct) {
          try {
            Reflect.construct(b, []);
          } catch (k) {
            var d = k;
          }

          Reflect.construct(a, [], b);
        } else {
          try {
            b.call();
          } catch (k) {
            d = k;
          }

          a.call(b.prototype);
        }
      } else {
        try {
          throw Error();
        } catch (k) {
          d = k;
        }

        a();
      }
    } catch (k) {
      if (k && d && "string" === typeof k.stack) {
        for (var e = k.stack.split("\n"), f = d.stack.split("\n"), g = e.length - 1, h = f.length - 1; 1 <= g && 0 <= h && e[g] !== f[h];) h--;

        for (; 1 <= g && 0 <= h; g--, h--) if (e[g] !== f[h]) {
          if (1 !== g || 1 !== h) {
            do if (g--, h--, 0 > h || e[g] !== f[h]) return "\n" + e[g].replace(" at new ", " at "); while (1 <= g && 0 <= h);
          }

          break;
        }
      }
    } finally {
      Oa = !1, Error.prepareStackTrace = c;
    }

    return (a = a ? a.displayName || a.name : "") ? Na(a) : "";
  }

  function Qa(a) {
    switch (a.tag) {
      case 5:
        return Na(a.type);

      case 16:
        return Na("Lazy");

      case 13:
        return Na("Suspense");

      case 19:
        return Na("SuspenseList");

      case 0:
      case 2:
      case 15:
        return a = Pa(a.type, !1), a;

      case 11:
        return a = Pa(a.type.render, !1), a;

      case 22:
        return a = Pa(a.type._render, !1), a;

      case 1:
        return a = Pa(a.type, !0), a;

      default:
        return "";
    }
  }

  function Ra(a) {
    if (null == a) return null;
    if ("function" === typeof a) return a.displayName || a.name || null;
    if ("string" === typeof a) return a;

    switch (a) {
      case ua:
        return "Fragment";

      case ta:
        return "Portal";

      case xa:
        return "Profiler";

      case wa:
        return "StrictMode";

      case Ba:
        return "Suspense";

      case Ca:
        return "SuspenseList";
    }

    if ("object" === typeof a) switch (a.$$typeof) {
      case za:
        return (a.displayName || "Context") + ".Consumer";

      case ya:
        return (a._context.displayName || "Context") + ".Provider";

      case Aa:
        var b = a.render;
        b = b.displayName || b.name || "";
        return a.displayName || ("" !== b ? "ForwardRef(" + b + ")" : "ForwardRef");

      case Da:
        return Ra(a.type);

      case Fa:
        return Ra(a._render);

      case Ea:
        b = a._payload;
        a = a._init;

        try {
          return Ra(a(b));
        } catch (c) {}

    }
    return null;
  }

  function Sa(a) {
    switch (typeof a) {
      case "boolean":
      case "number":
      case "object":
      case "string":
      case "undefined":
        return a;

      default:
        return "";
    }
  }

  function Ta(a) {
    var b = a.type;
    return (a = a.nodeName) && "input" === a.toLowerCase() && ("checkbox" === b || "radio" === b);
  }

  function Ua(a) {
    var b = Ta(a) ? "checked" : "value",
        c = Object.getOwnPropertyDescriptor(a.constructor.prototype, b),
        d = "" + a[b];

    if (!a.hasOwnProperty(b) && "undefined" !== typeof c && "function" === typeof c.get && "function" === typeof c.set) {
      var e = c.get,
          f = c.set;
      Object.defineProperty(a, b, {
        configurable: !0,
        get: function () {
          return e.call(this);
        },
        set: function (a) {
          d = "" + a;
          f.call(this, a);
        }
      });
      Object.defineProperty(a, b, {
        enumerable: c.enumerable
      });
      return {
        getValue: function () {
          return d;
        },
        setValue: function (a) {
          d = "" + a;
        },
        stopTracking: function () {
          a._valueTracker = null;
          delete a[b];
        }
      };
    }
  }

  function Va(a) {
    a._valueTracker || (a._valueTracker = Ua(a));
  }

  function Wa(a) {
    if (!a) return !1;
    var b = a._valueTracker;
    if (!b) return !0;
    var c = b.getValue();
    var d = "";
    a && (d = Ta(a) ? a.checked ? "true" : "false" : a.value);
    a = d;
    return a !== c ? (b.setValue(a), !0) : !1;
  }

  function Xa(a) {
    a = a || ("undefined" !== typeof document ? document : void 0);
    if ("undefined" === typeof a) return null;

    try {
      return a.activeElement || a.body;
    } catch (b) {
      return a.body;
    }
  }

  function Ya(a, b) {
    var c = b.checked;
    return objectAssign({}, b, {
      defaultChecked: void 0,
      defaultValue: void 0,
      value: void 0,
      checked: null != c ? c : a._wrapperState.initialChecked
    });
  }

  function Za(a, b) {
    var c = null == b.defaultValue ? "" : b.defaultValue,
        d = null != b.checked ? b.checked : b.defaultChecked;
    c = Sa(null != b.value ? b.value : c);
    a._wrapperState = {
      initialChecked: d,
      initialValue: c,
      controlled: "checkbox" === b.type || "radio" === b.type ? null != b.checked : null != b.value
    };
  }

  function $a(a, b) {
    b = b.checked;
    null != b && qa(a, "checked", b, !1);
  }

  function ab(a, b) {
    $a(a, b);
    var c = Sa(b.value),
        d = b.type;
    if (null != c) {
      if ("number" === d) {
        if (0 === c && "" === a.value || a.value != c) a.value = "" + c;
      } else a.value !== "" + c && (a.value = "" + c);
    } else if ("submit" === d || "reset" === d) {
      a.removeAttribute("value");
      return;
    }
    b.hasOwnProperty("value") ? bb(a, b.type, c) : b.hasOwnProperty("defaultValue") && bb(a, b.type, Sa(b.defaultValue));
    null == b.checked && null != b.defaultChecked && (a.defaultChecked = !!b.defaultChecked);
  }

  function cb(a, b, c) {
    if (b.hasOwnProperty("value") || b.hasOwnProperty("defaultValue")) {
      var d = b.type;
      if (!("submit" !== d && "reset" !== d || void 0 !== b.value && null !== b.value)) return;
      b = "" + a._wrapperState.initialValue;
      c || b === a.value || (a.value = b);
      a.defaultValue = b;
    }

    c = a.name;
    "" !== c && (a.name = "");
    a.defaultChecked = !!a._wrapperState.initialChecked;
    "" !== c && (a.name = c);
  }

  function bb(a, b, c) {
    if ("number" !== b || Xa(a.ownerDocument) !== a) null == c ? a.defaultValue = "" + a._wrapperState.initialValue : a.defaultValue !== "" + c && (a.defaultValue = "" + c);
  }

  function db(a) {
    var b = "";
    react.Children.forEach(a, function (a) {
      null != a && (b += a);
    });
    return b;
  }

  function eb(a, b) {
    a = objectAssign({
      children: void 0
    }, b);
    if (b = db(b.children)) a.children = b;
    return a;
  }

  function fb(a, b, c, d) {
    a = a.options;

    if (b) {
      b = {};

      for (var e = 0; e < c.length; e++) b["$" + c[e]] = !0;

      for (c = 0; c < a.length; c++) e = b.hasOwnProperty("$" + a[c].value), a[c].selected !== e && (a[c].selected = e), e && d && (a[c].defaultSelected = !0);
    } else {
      c = "" + Sa(c);
      b = null;

      for (e = 0; e < a.length; e++) {
        if (a[e].value === c) {
          a[e].selected = !0;
          d && (a[e].defaultSelected = !0);
          return;
        }

        null !== b || a[e].disabled || (b = a[e]);
      }

      null !== b && (b.selected = !0);
    }
  }

  function gb(a, b) {
    if (null != b.dangerouslySetInnerHTML) throw Error(y$1(91));
    return objectAssign({}, b, {
      value: void 0,
      defaultValue: void 0,
      children: "" + a._wrapperState.initialValue
    });
  }

  function hb(a, b) {
    var c = b.value;

    if (null == c) {
      c = b.children;
      b = b.defaultValue;

      if (null != c) {
        if (null != b) throw Error(y$1(92));

        if (Array.isArray(c)) {
          if (!(1 >= c.length)) throw Error(y$1(93));
          c = c[0];
        }

        b = c;
      }

      null == b && (b = "");
      c = b;
    }

    a._wrapperState = {
      initialValue: Sa(c)
    };
  }

  function ib(a, b) {
    var c = Sa(b.value),
        d = Sa(b.defaultValue);
    null != c && (c = "" + c, c !== a.value && (a.value = c), null == b.defaultValue && a.defaultValue !== c && (a.defaultValue = c));
    null != d && (a.defaultValue = "" + d);
  }

  function jb(a) {
    var b = a.textContent;
    b === a._wrapperState.initialValue && "" !== b && null !== b && (a.value = b);
  }

  var kb = {
    html: "http://www.w3.org/1999/xhtml",
    mathml: "http://www.w3.org/1998/Math/MathML",
    svg: "http://www.w3.org/2000/svg"
  };

  function lb(a) {
    switch (a) {
      case "svg":
        return "http://www.w3.org/2000/svg";

      case "math":
        return "http://www.w3.org/1998/Math/MathML";

      default:
        return "http://www.w3.org/1999/xhtml";
    }
  }

  function mb(a, b) {
    return null == a || "http://www.w3.org/1999/xhtml" === a ? lb(b) : "http://www.w3.org/2000/svg" === a && "foreignObject" === b ? "http://www.w3.org/1999/xhtml" : a;
  }

  var nb,
      ob = function (a) {
    return "undefined" !== typeof MSApp && MSApp.execUnsafeLocalFunction ? function (b, c, d, e) {
      MSApp.execUnsafeLocalFunction(function () {
        return a(b, c, d, e);
      });
    } : a;
  }(function (a, b) {
    if (a.namespaceURI !== kb.svg || "innerText" in a) a.innerText = b;else {
      nb = nb || document.createElement("div");
      nb.innerText = "<svg>" + b.valueOf().toString() + "</svg>";

      for (b = nb.firstChild; a.firstChild;) a.removeChild(a.firstChild);

      for (; b.firstChild;) a.appendChild(b.firstChild);
    }
  });

  function pb(a, b) {
    if (b) {
      var c = a.firstChild;

      if (c && c === a.lastChild && 3 === c.nodeType) {
        c.nodeValue = b;
        return;
      }
    }

    a.textContent = b;
  }

  var qb = {
    animationIterationCount: !0,
    borderImageOutset: !0,
    borderImageSlice: !0,
    borderImageWidth: !0,
    boxFlex: !0,
    boxFlexGroup: !0,
    boxOrdinalGroup: !0,
    columnCount: !0,
    columns: !0,
    flex: !0,
    flexGrow: !0,
    flexPositive: !0,
    flexShrink: !0,
    flexNegative: !0,
    flexOrder: !0,
    gridArea: !0,
    gridRow: !0,
    gridRowEnd: !0,
    gridRowSpan: !0,
    gridRowStart: !0,
    gridColumn: !0,
    gridColumnEnd: !0,
    gridColumnSpan: !0,
    gridColumnStart: !0,
    fontWeight: !0,
    lineClamp: !0,
    lineHeight: !0,
    opacity: !0,
    order: !0,
    orphans: !0,
    tabSize: !0,
    widows: !0,
    zIndex: !0,
    zoom: !0,
    fillOpacity: !0,
    floodOpacity: !0,
    stopOpacity: !0,
    strokeDasharray: !0,
    strokeDashoffset: !0,
    strokeMiterlimit: !0,
    strokeOpacity: !0,
    strokeWidth: !0
  },
      rb = ["Webkit", "ms", "Moz", "O"];
  Object.keys(qb).forEach(function (a) {
    rb.forEach(function (b) {
      b = b + a.charAt(0).toUpperCase() + a.substring(1);
      qb[b] = qb[a];
    });
  });

  function sb(a, b, c) {
    return null == b || "boolean" === typeof b || "" === b ? "" : c || "number" !== typeof b || 0 === b || qb.hasOwnProperty(a) && qb[a] ? ("" + b).trim() : b + "px";
  }

  function tb(a, b) {
    a = a.style;

    for (var c in b) if (b.hasOwnProperty(c)) {
      var d = 0 === c.indexOf("--"),
          e = sb(c, b[c], d);
      "float" === c && (c = "cssFloat");
      d ? a.setProperty(c, e) : a[c] = e;
    }
  }

  var ub = objectAssign({
    menuitem: !0
  }, {
    area: !0,
    base: !0,
    br: !0,
    col: !0,
    embed: !0,
    hr: !0,
    img: !0,
    input: !0,
    keygen: !0,
    link: !0,
    meta: !0,
    param: !0,
    source: !0,
    track: !0,
    wbr: !0
  });

  function vb(a, b) {
    if (b) {
      if (ub[a] && (null != b.children || null != b.dangerouslySetInnerHTML)) throw Error(y$1(137, a));

      if (null != b.dangerouslySetInnerHTML) {
        if (null != b.children) throw Error(y$1(60));
        if (!("object" === typeof b.dangerouslySetInnerHTML && "__html" in b.dangerouslySetInnerHTML)) throw Error(y$1(61));
      }

      if (null != b.style && "object" !== typeof b.style) throw Error(y$1(62));
    }
  }

  function wb(a, b) {
    if (-1 === a.indexOf("-")) return "string" === typeof b.is;

    switch (a) {
      case "annotation-xml":
      case "color-profile":
      case "font-face":
      case "font-face-src":
      case "font-face-uri":
      case "font-face-format":
      case "font-face-name":
      case "missing-glyph":
        return !1;

      default:
        return !0;
    }
  }

  function xb(a) {
    a = a.target || a.srcElement || window;
    a.correspondingUseElement && (a = a.correspondingUseElement);
    return 3 === a.nodeType ? a.parentNode : a;
  }

  var yb = null,
      zb = null,
      Ab = null;

  function Bb(a) {
    if (a = Cb(a)) {
      if ("function" !== typeof yb) throw Error(y$1(280));
      var b = a.stateNode;
      b && (b = Db(b), yb(a.stateNode, a.type, b));
    }
  }

  function Eb(a) {
    zb ? Ab ? Ab.push(a) : Ab = [a] : zb = a;
  }

  function Fb() {
    if (zb) {
      var a = zb,
          b = Ab;
      Ab = zb = null;
      Bb(a);
      if (b) for (a = 0; a < b.length; a++) Bb(b[a]);
    }
  }

  function Gb(a, b) {
    return a(b);
  }

  function Hb(a, b, c, d, e) {
    return a(b, c, d, e);
  }

  function Ib() {}

  var Jb = Gb,
      Kb = !1,
      Lb = !1;

  function Mb() {
    if (null !== zb || null !== Ab) Ib(), Fb();
  }

  function Nb(a, b, c) {
    if (Lb) return a(b, c);
    Lb = !0;

    try {
      return Jb(a, b, c);
    } finally {
      Lb = !1, Mb();
    }
  }

  function Ob(a, b) {
    var c = a.stateNode;
    if (null === c) return null;
    var d = Db(c);
    if (null === d) return null;
    c = d[b];

    a: switch (b) {
      case "onClick":
      case "onClickCapture":
      case "onDoubleClick":
      case "onDoubleClickCapture":
      case "onMouseDown":
      case "onMouseDownCapture":
      case "onMouseMove":
      case "onMouseMoveCapture":
      case "onMouseUp":
      case "onMouseUpCapture":
      case "onMouseEnter":
        (d = !d.disabled) || (a = a.type, d = !("button" === a || "input" === a || "select" === a || "textarea" === a));
        a = !d;
        break a;

      default:
        a = !1;
    }

    if (a) return null;
    if (c && "function" !== typeof c) throw Error(y$1(231, b, typeof c));
    return c;
  }

  var Pb = !1;
  if (fa) try {
    var Qb = {};
    Object.defineProperty(Qb, "passive", {
      get: function () {
        Pb = !0;
      }
    });
    window.addEventListener("test", Qb, Qb);
    window.removeEventListener("test", Qb, Qb);
  } catch (a) {
    Pb = !1;
  }

  function Rb(a, b, c, d, e, f, g, h, k) {
    var l = Array.prototype.slice.call(arguments, 3);

    try {
      b.apply(c, l);
    } catch (n) {
      this.onError(n);
    }
  }

  var Sb = !1,
      Tb = null,
      Ub = !1,
      Vb = null,
      Wb = {
    onError: function (a) {
      Sb = !0;
      Tb = a;
    }
  };

  function Xb(a, b, c, d, e, f, g, h, k) {
    Sb = !1;
    Tb = null;
    Rb.apply(Wb, arguments);
  }

  function Yb(a, b, c, d, e, f, g, h, k) {
    Xb.apply(this, arguments);

    if (Sb) {
      if (Sb) {
        var l = Tb;
        Sb = !1;
        Tb = null;
      } else throw Error(y$1(198));

      Ub || (Ub = !0, Vb = l);
    }
  }

  function Zb(a) {
    var b = a,
        c = a;
    if (a.alternate) for (; b.return;) b = b.return;else {
      a = b;

      do b = a, 0 !== (b.flags & 1026) && (c = b.return), a = b.return; while (a);
    }
    return 3 === b.tag ? c : null;
  }

  function $b(a) {
    if (13 === a.tag) {
      var b = a.memoizedState;
      null === b && (a = a.alternate, null !== a && (b = a.memoizedState));
      if (null !== b) return b.dehydrated;
    }

    return null;
  }

  function ac(a) {
    if (Zb(a) !== a) throw Error(y$1(188));
  }

  function bc(a) {
    var b = a.alternate;

    if (!b) {
      b = Zb(a);
      if (null === b) throw Error(y$1(188));
      return b !== a ? null : a;
    }

    for (var c = a, d = b;;) {
      var e = c.return;
      if (null === e) break;
      var f = e.alternate;

      if (null === f) {
        d = e.return;

        if (null !== d) {
          c = d;
          continue;
        }

        break;
      }

      if (e.child === f.child) {
        for (f = e.child; f;) {
          if (f === c) return ac(e), a;
          if (f === d) return ac(e), b;
          f = f.sibling;
        }

        throw Error(y$1(188));
      }

      if (c.return !== d.return) c = e, d = f;else {
        for (var g = !1, h = e.child; h;) {
          if (h === c) {
            g = !0;
            c = e;
            d = f;
            break;
          }

          if (h === d) {
            g = !0;
            d = e;
            c = f;
            break;
          }

          h = h.sibling;
        }

        if (!g) {
          for (h = f.child; h;) {
            if (h === c) {
              g = !0;
              c = f;
              d = e;
              break;
            }

            if (h === d) {
              g = !0;
              d = f;
              c = e;
              break;
            }

            h = h.sibling;
          }

          if (!g) throw Error(y$1(189));
        }
      }
      if (c.alternate !== d) throw Error(y$1(190));
    }

    if (3 !== c.tag) throw Error(y$1(188));
    return c.stateNode.current === c ? a : b;
  }

  function cc(a) {
    a = bc(a);
    if (!a) return null;

    for (var b = a;;) {
      if (5 === b.tag || 6 === b.tag) return b;
      if (b.child) b.child.return = b, b = b.child;else {
        if (b === a) break;

        for (; !b.sibling;) {
          if (!b.return || b.return === a) return null;
          b = b.return;
        }

        b.sibling.return = b.return;
        b = b.sibling;
      }
    }

    return null;
  }

  var ec,
      fc,
      gc,
      hc,
      ic = !1,
      jc = [],
      kc = null,
      lc = null,
      mc = null,
      nc = new Map(),
      oc = new Map(),
      pc = [],
      qc = "mousedown mouseup touchcancel touchend touchstart auxclick dblclick pointercancel pointerdown pointerup dragend dragstart drop compositionend compositionstart keydown keypress keyup input textInput copy cut paste click change contextmenu reset submit".split(" ");

  function rc(a, b, c, d, e) {
    return {
      blockedOn: a,
      domEventName: b,
      eventSystemFlags: c | 16,
      nativeEvent: e,
      targetContainers: [d]
    };
  }

  function sc(a, b) {
    switch (a) {
      case "focusin":
      case "focusout":
        kc = null;
        break;

      case "dragenter":
      case "dragleave":
        lc = null;
        break;

      case "mouseover":
      case "mouseout":
        mc = null;
        break;

      case "pointerover":
      case "pointerout":
        nc.delete(b.pointerId);
        break;

      case "gotpointercapture":
      case "lostpointercapture":
        oc.delete(b.pointerId);
    }
  }

  function tc(a, b, c, d, e, f) {
    if (null === a || a.nativeEvent !== f) return a = rc(b, c, d, e, f), null !== b && (b = Cb(b), null !== b && fc(b)), a;
    a.eventSystemFlags |= d;
    b = a.targetContainers;
    null !== e && -1 === b.indexOf(e) && b.push(e);
    return a;
  }

  function uc(a, b, c, d, e) {
    switch (b) {
      case "focusin":
        return kc = tc(kc, a, b, c, d, e), !0;

      case "dragenter":
        return lc = tc(lc, a, b, c, d, e), !0;

      case "mouseover":
        return mc = tc(mc, a, b, c, d, e), !0;

      case "pointerover":
        var f = e.pointerId;
        nc.set(f, tc(nc.get(f) || null, a, b, c, d, e));
        return !0;

      case "gotpointercapture":
        return f = e.pointerId, oc.set(f, tc(oc.get(f) || null, a, b, c, d, e)), !0;
    }

    return !1;
  }

  function vc(a) {
    var b = wc(a.target);

    if (null !== b) {
      var c = Zb(b);
      if (null !== c) if (b = c.tag, 13 === b) {
        if (b = $b(c), null !== b) {
          a.blockedOn = b;
          hc(a.lanePriority, function () {
            scheduler.unstable_runWithPriority(a.priority, function () {
              gc(c);
            });
          });
          return;
        }
      } else if (3 === b && c.stateNode.hydrate) {
        a.blockedOn = 3 === c.tag ? c.stateNode.containerInfo : null;
        return;
      }
    }

    a.blockedOn = null;
  }

  function xc(a) {
    if (null !== a.blockedOn) return !1;

    for (var b = a.targetContainers; 0 < b.length;) {
      var c = yc(a.domEventName, a.eventSystemFlags, b[0], a.nativeEvent);
      if (null !== c) return b = Cb(c), null !== b && fc(b), a.blockedOn = c, !1;
      b.shift();
    }

    return !0;
  }

  function zc(a, b, c) {
    xc(a) && c.delete(b);
  }

  function Ac() {
    for (ic = !1; 0 < jc.length;) {
      var a = jc[0];

      if (null !== a.blockedOn) {
        a = Cb(a.blockedOn);
        null !== a && ec(a);
        break;
      }

      for (var b = a.targetContainers; 0 < b.length;) {
        var c = yc(a.domEventName, a.eventSystemFlags, b[0], a.nativeEvent);

        if (null !== c) {
          a.blockedOn = c;
          break;
        }

        b.shift();
      }

      null === a.blockedOn && jc.shift();
    }

    null !== kc && xc(kc) && (kc = null);
    null !== lc && xc(lc) && (lc = null);
    null !== mc && xc(mc) && (mc = null);
    nc.forEach(zc);
    oc.forEach(zc);
  }

  function Bc(a, b) {
    a.blockedOn === b && (a.blockedOn = null, ic || (ic = !0, scheduler.unstable_scheduleCallback(scheduler.unstable_NormalPriority, Ac)));
  }

  function Cc(a) {
    function b(b) {
      return Bc(b, a);
    }

    if (0 < jc.length) {
      Bc(jc[0], a);

      for (var c = 1; c < jc.length; c++) {
        var d = jc[c];
        d.blockedOn === a && (d.blockedOn = null);
      }
    }

    null !== kc && Bc(kc, a);
    null !== lc && Bc(lc, a);
    null !== mc && Bc(mc, a);
    nc.forEach(b);
    oc.forEach(b);

    for (c = 0; c < pc.length; c++) d = pc[c], d.blockedOn === a && (d.blockedOn = null);

    for (; 0 < pc.length && (c = pc[0], null === c.blockedOn);) vc(c), null === c.blockedOn && pc.shift();
  }

  function Dc(a, b) {
    var c = {};
    c[a.toLowerCase()] = b.toLowerCase();
    c["Webkit" + a] = "webkit" + b;
    c["Moz" + a] = "moz" + b;
    return c;
  }

  var Ec = {
    animationend: Dc("Animation", "AnimationEnd"),
    animationiteration: Dc("Animation", "AnimationIteration"),
    animationstart: Dc("Animation", "AnimationStart"),
    transitionend: Dc("Transition", "TransitionEnd")
  },
      Fc = {},
      Gc = {};
  fa && (Gc = document.createElement("div").style, "AnimationEvent" in window || (delete Ec.animationend.animation, delete Ec.animationiteration.animation, delete Ec.animationstart.animation), "TransitionEvent" in window || delete Ec.transitionend.transition);

  function Hc(a) {
    if (Fc[a]) return Fc[a];
    if (!Ec[a]) return a;
    var b = Ec[a],
        c;

    for (c in b) if (b.hasOwnProperty(c) && c in Gc) return Fc[a] = b[c];

    return a;
  }

  var Ic = Hc("animationend"),
      Jc = Hc("animationiteration"),
      Kc = Hc("animationstart"),
      Lc = Hc("transitionend"),
      Mc = new Map(),
      Nc = new Map(),
      Oc = ["abort", "abort", Ic, "animationEnd", Jc, "animationIteration", Kc, "animationStart", "canplay", "canPlay", "canplaythrough", "canPlayThrough", "durationchange", "durationChange", "emptied", "emptied", "encrypted", "encrypted", "ended", "ended", "error", "error", "gotpointercapture", "gotPointerCapture", "load", "load", "loadeddata", "loadedData", "loadedmetadata", "loadedMetadata", "loadstart", "loadStart", "lostpointercapture", "lostPointerCapture", "playing", "playing", "progress", "progress", "seeking", "seeking", "stalled", "stalled", "suspend", "suspend", "timeupdate", "timeUpdate", Lc, "transitionEnd", "waiting", "waiting"];

  function Pc(a, b) {
    for (var c = 0; c < a.length; c += 2) {
      var d = a[c],
          e = a[c + 1];
      e = "on" + (e[0].toUpperCase() + e.slice(1));
      Nc.set(d, b);
      Mc.set(d, e);
      da(e, [d]);
    }
  }

  var Qc = scheduler.unstable_now;
  Qc();
  var F = 8;

  function Rc(a) {
    if (0 !== (1 & a)) return F = 15, 1;
    if (0 !== (2 & a)) return F = 14, 2;
    if (0 !== (4 & a)) return F = 13, 4;
    var b = 24 & a;
    if (0 !== b) return F = 12, b;
    if (0 !== (a & 32)) return F = 11, 32;
    b = 192 & a;
    if (0 !== b) return F = 10, b;
    if (0 !== (a & 256)) return F = 9, 256;
    b = 3584 & a;
    if (0 !== b) return F = 8, b;
    if (0 !== (a & 4096)) return F = 7, 4096;
    b = 4186112 & a;
    if (0 !== b) return F = 6, b;
    b = 62914560 & a;
    if (0 !== b) return F = 5, b;
    if (a & 67108864) return F = 4, 67108864;
    if (0 !== (a & 134217728)) return F = 3, 134217728;
    b = 805306368 & a;
    if (0 !== b) return F = 2, b;
    if (0 !== (1073741824 & a)) return F = 1, 1073741824;
    F = 8;
    return a;
  }

  function Sc(a) {
    switch (a) {
      case 99:
        return 15;

      case 98:
        return 10;

      case 97:
      case 96:
        return 8;

      case 95:
        return 2;

      default:
        return 0;
    }
  }

  function Tc(a) {
    switch (a) {
      case 15:
      case 14:
        return 99;

      case 13:
      case 12:
      case 11:
      case 10:
        return 98;

      case 9:
      case 8:
      case 7:
      case 6:
      case 4:
      case 5:
        return 97;

      case 3:
      case 2:
      case 1:
        return 95;

      case 0:
        return 90;

      default:
        throw Error(y$1(358, a));
    }
  }

  function Uc(a, b) {
    var c = a.pendingLanes;
    if (0 === c) return F = 0;
    var d = 0,
        e = 0,
        f = a.expiredLanes,
        g = a.suspendedLanes,
        h = a.pingedLanes;
    if (0 !== f) d = f, e = F = 15;else if (f = c & 134217727, 0 !== f) {
      var k = f & ~g;
      0 !== k ? (d = Rc(k), e = F) : (h &= f, 0 !== h && (d = Rc(h), e = F));
    } else f = c & ~g, 0 !== f ? (d = Rc(f), e = F) : 0 !== h && (d = Rc(h), e = F);
    if (0 === d) return 0;
    d = 31 - Vc(d);
    d = c & ((0 > d ? 0 : 1 << d) << 1) - 1;

    if (0 !== b && b !== d && 0 === (b & g)) {
      Rc(b);
      if (e <= F) return b;
      F = e;
    }

    b = a.entangledLanes;
    if (0 !== b) for (a = a.entanglements, b &= d; 0 < b;) c = 31 - Vc(b), e = 1 << c, d |= a[c], b &= ~e;
    return d;
  }

  function Wc(a) {
    a = a.pendingLanes & -1073741825;
    return 0 !== a ? a : a & 1073741824 ? 1073741824 : 0;
  }

  function Xc(a, b) {
    switch (a) {
      case 15:
        return 1;

      case 14:
        return 2;

      case 12:
        return a = Yc(24 & ~b), 0 === a ? Xc(10, b) : a;

      case 10:
        return a = Yc(192 & ~b), 0 === a ? Xc(8, b) : a;

      case 8:
        return a = Yc(3584 & ~b), 0 === a && (a = Yc(4186112 & ~b), 0 === a && (a = 512)), a;

      case 2:
        return b = Yc(805306368 & ~b), 0 === b && (b = 268435456), b;
    }

    throw Error(y$1(358, a));
  }

  function Yc(a) {
    return a & -a;
  }

  function Zc(a) {
    for (var b = [], c = 0; 31 > c; c++) b.push(a);

    return b;
  }

  function $c(a, b, c) {
    a.pendingLanes |= b;
    var d = b - 1;
    a.suspendedLanes &= d;
    a.pingedLanes &= d;
    a = a.eventTimes;
    b = 31 - Vc(b);
    a[b] = c;
  }

  var Vc = Math.clz32 ? Math.clz32 : ad,
      bd = Math.log,
      cd = Math.LN2;

  function ad(a) {
    return 0 === a ? 32 : 31 - (bd(a) / cd | 0) | 0;
  }

  var dd = scheduler.unstable_UserBlockingPriority,
      ed = scheduler.unstable_runWithPriority,
      fd = !0;

  function gd(a, b, c, d) {
    Kb || Ib();
    var e = hd,
        f = Kb;
    Kb = !0;

    try {
      Hb(e, a, b, c, d);
    } finally {
      (Kb = f) || Mb();
    }
  }

  function id$1(a, b, c, d) {
    ed(dd, hd.bind(null, a, b, c, d));
  }

  function hd(a, b, c, d) {
    if (fd) {
      var e;
      if ((e = 0 === (b & 4)) && 0 < jc.length && -1 < qc.indexOf(a)) a = rc(null, a, b, c, d), jc.push(a);else {
        var f = yc(a, b, c, d);
        if (null === f) e && sc(a, d);else {
          if (e) {
            if (-1 < qc.indexOf(a)) {
              a = rc(f, a, b, c, d);
              jc.push(a);
              return;
            }

            if (uc(f, a, b, c, d)) return;
            sc(a, d);
          }

          jd(a, b, d, null, c);
        }
      }
    }
  }

  function yc(a, b, c, d) {
    var e = xb(d);
    e = wc(e);

    if (null !== e) {
      var f = Zb(e);
      if (null === f) e = null;else {
        var g = f.tag;

        if (13 === g) {
          e = $b(f);
          if (null !== e) return e;
          e = null;
        } else if (3 === g) {
          if (f.stateNode.hydrate) return 3 === f.tag ? f.stateNode.containerInfo : null;
          e = null;
        } else f !== e && (e = null);
      }
    }

    jd(a, b, d, e, c);
    return null;
  }

  var kd = null,
      ld = null,
      md = null;

  function nd() {
    if (md) return md;
    var a,
        b = ld,
        c = b.length,
        d,
        e = "value" in kd ? kd.value : kd.textContent,
        f = e.length;

    for (a = 0; a < c && b[a] === e[a]; a++);

    var g = c - a;

    for (d = 1; d <= g && b[c - d] === e[f - d]; d++);

    return md = e.slice(a, 1 < d ? 1 - d : void 0);
  }

  function od(a) {
    var b = a.keyCode;
    "charCode" in a ? (a = a.charCode, 0 === a && 13 === b && (a = 13)) : a = b;
    10 === a && (a = 13);
    return 32 <= a || 13 === a ? a : 0;
  }

  function pd() {
    return !0;
  }

  function qd() {
    return !1;
  }

  function rd(a) {
    function b(b, d, e, f, g) {
      this._reactName = b;
      this._targetInst = e;
      this.type = d;
      this.nativeEvent = f;
      this.target = g;
      this.currentTarget = null;

      for (var c in a) a.hasOwnProperty(c) && (b = a[c], this[c] = b ? b(f) : f[c]);

      this.isDefaultPrevented = (null != f.defaultPrevented ? f.defaultPrevented : !1 === f.returnValue) ? pd : qd;
      this.isPropagationStopped = qd;
      return this;
    }

    objectAssign(b.prototype, {
      preventDefault: function () {
        this.defaultPrevented = !0;
        var a = this.nativeEvent;
        a && (a.preventDefault ? a.preventDefault() : "unknown" !== typeof a.returnValue && (a.returnValue = !1), this.isDefaultPrevented = pd);
      },
      stopPropagation: function () {
        var a = this.nativeEvent;
        a && (a.stopPropagation ? a.stopPropagation() : "unknown" !== typeof a.cancelBubble && (a.cancelBubble = !0), this.isPropagationStopped = pd);
      },
      persist: function () {},
      isPersistent: pd
    });
    return b;
  }

  var sd = {
    eventPhase: 0,
    bubbles: 0,
    cancelable: 0,
    timeStamp: function (a) {
      return a.timeStamp || Date.now();
    },
    defaultPrevented: 0,
    isTrusted: 0
  },
      td = rd(sd),
      ud = objectAssign({}, sd, {
    view: 0,
    detail: 0
  }),
      vd = rd(ud),
      wd,
      xd,
      yd,
      Ad = objectAssign({}, ud, {
    screenX: 0,
    screenY: 0,
    clientX: 0,
    clientY: 0,
    pageX: 0,
    pageY: 0,
    ctrlKey: 0,
    shiftKey: 0,
    altKey: 0,
    metaKey: 0,
    getModifierState: zd,
    button: 0,
    buttons: 0,
    relatedTarget: function (a) {
      return void 0 === a.relatedTarget ? a.fromElement === a.srcElement ? a.toElement : a.fromElement : a.relatedTarget;
    },
    movementX: function (a) {
      if ("movementX" in a) return a.movementX;
      a !== yd && (yd && "mousemove" === a.type ? (wd = a.screenX - yd.screenX, xd = a.screenY - yd.screenY) : xd = wd = 0, yd = a);
      return wd;
    },
    movementY: function (a) {
      return "movementY" in a ? a.movementY : xd;
    }
  }),
      Bd = rd(Ad),
      Cd = objectAssign({}, Ad, {
    dataTransfer: 0
  }),
      Dd = rd(Cd),
      Ed = objectAssign({}, ud, {
    relatedTarget: 0
  }),
      Fd = rd(Ed),
      Gd = objectAssign({}, sd, {
    animationName: 0,
    elapsedTime: 0,
    pseudoElement: 0
  }),
      Hd = rd(Gd),
      Id = objectAssign({}, sd, {
    clipboardData: function (a) {
      return "clipboardData" in a ? a.clipboardData : window.clipboardData;
    }
  }),
      Jd = rd(Id),
      Kd = objectAssign({}, sd, {
    data: 0
  }),
      Ld = rd(Kd),
      Md = {
    Esc: "Escape",
    Spacebar: " ",
    Left: "ArrowLeft",
    Up: "ArrowUp",
    Right: "ArrowRight",
    Down: "ArrowDown",
    Del: "Delete",
    Win: "OS",
    Menu: "ContextMenu",
    Apps: "ContextMenu",
    Scroll: "ScrollLock",
    MozPrintableKey: "Unidentified"
  },
      Nd = {
    8: "Backspace",
    9: "Tab",
    12: "Clear",
    13: "Enter",
    16: "Shift",
    17: "Control",
    18: "Alt",
    19: "Pause",
    20: "CapsLock",
    27: "Escape",
    32: " ",
    33: "PageUp",
    34: "PageDown",
    35: "End",
    36: "Home",
    37: "ArrowLeft",
    38: "ArrowUp",
    39: "ArrowRight",
    40: "ArrowDown",
    45: "Insert",
    46: "Delete",
    112: "F1",
    113: "F2",
    114: "F3",
    115: "F4",
    116: "F5",
    117: "F6",
    118: "F7",
    119: "F8",
    120: "F9",
    121: "F10",
    122: "F11",
    123: "F12",
    144: "NumLock",
    145: "ScrollLock",
    224: "Meta"
  },
      Od = {
    Alt: "altKey",
    Control: "ctrlKey",
    Meta: "metaKey",
    Shift: "shiftKey"
  };

  function Pd(a) {
    var b = this.nativeEvent;
    return b.getModifierState ? b.getModifierState(a) : (a = Od[a]) ? !!b[a] : !1;
  }

  function zd() {
    return Pd;
  }

  var Qd = objectAssign({}, ud, {
    key: function (a) {
      if (a.key) {
        var b = Md[a.key] || a.key;
        if ("Unidentified" !== b) return b;
      }

      return "keypress" === a.type ? (a = od(a), 13 === a ? "Enter" : String.fromCharCode(a)) : "keydown" === a.type || "keyup" === a.type ? Nd[a.keyCode] || "Unidentified" : "";
    },
    code: 0,
    location: 0,
    ctrlKey: 0,
    shiftKey: 0,
    altKey: 0,
    metaKey: 0,
    repeat: 0,
    locale: 0,
    getModifierState: zd,
    charCode: function (a) {
      return "keypress" === a.type ? od(a) : 0;
    },
    keyCode: function (a) {
      return "keydown" === a.type || "keyup" === a.type ? a.keyCode : 0;
    },
    which: function (a) {
      return "keypress" === a.type ? od(a) : "keydown" === a.type || "keyup" === a.type ? a.keyCode : 0;
    }
  }),
      Rd = rd(Qd),
      Sd = objectAssign({}, Ad, {
    pointerId: 0,
    width: 0,
    height: 0,
    pressure: 0,
    tangentialPressure: 0,
    tiltX: 0,
    tiltY: 0,
    twist: 0,
    pointerType: 0,
    isPrimary: 0
  }),
      Td = rd(Sd),
      Ud = objectAssign({}, ud, {
    touches: 0,
    targetTouches: 0,
    changedTouches: 0,
    altKey: 0,
    metaKey: 0,
    ctrlKey: 0,
    shiftKey: 0,
    getModifierState: zd
  }),
      Vd = rd(Ud),
      Wd = objectAssign({}, sd, {
    propertyName: 0,
    elapsedTime: 0,
    pseudoElement: 0
  }),
      Xd = rd(Wd),
      Yd = objectAssign({}, Ad, {
    deltaX: function (a) {
      return "deltaX" in a ? a.deltaX : "wheelDeltaX" in a ? -a.wheelDeltaX : 0;
    },
    deltaY: function (a) {
      return "deltaY" in a ? a.deltaY : "wheelDeltaY" in a ? -a.wheelDeltaY : "wheelDelta" in a ? -a.wheelDelta : 0;
    },
    deltaZ: 0,
    deltaMode: 0
  }),
      Zd = rd(Yd),
      $d = [9, 13, 27, 32],
      ae = fa && "CompositionEvent" in window,
      be = null;
  fa && "documentMode" in document && (be = document.documentMode);
  var ce = fa && "TextEvent" in window && !be,
      de = fa && (!ae || be && 8 < be && 11 >= be),
      ee = String.fromCharCode(32),
      fe = !1;

  function ge(a, b) {
    switch (a) {
      case "keyup":
        return -1 !== $d.indexOf(b.keyCode);

      case "keydown":
        return 229 !== b.keyCode;

      case "keypress":
      case "mousedown":
      case "focusout":
        return !0;

      default:
        return !1;
    }
  }

  function he(a) {
    a = a.detail;
    return "object" === typeof a && "data" in a ? a.data : null;
  }

  var ie = !1;

  function je(a, b) {
    switch (a) {
      case "compositionend":
        return he(b);

      case "keypress":
        if (32 !== b.which) return null;
        fe = !0;
        return ee;

      case "textInput":
        return a = b.data, a === ee && fe ? null : a;

      default:
        return null;
    }
  }

  function ke(a, b) {
    if (ie) return "compositionend" === a || !ae && ge(a, b) ? (a = nd(), md = ld = kd = null, ie = !1, a) : null;

    switch (a) {
      case "paste":
        return null;

      case "keypress":
        if (!(b.ctrlKey || b.altKey || b.metaKey) || b.ctrlKey && b.altKey) {
          if (b.char && 1 < b.char.length) return b.char;
          if (b.which) return String.fromCharCode(b.which);
        }

        return null;

      case "compositionend":
        return de && "ko" !== b.locale ? null : b.data;

      default:
        return null;
    }
  }

  var le = {
    color: !0,
    date: !0,
    datetime: !0,
    "datetime-local": !0,
    email: !0,
    month: !0,
    number: !0,
    password: !0,
    range: !0,
    search: !0,
    tel: !0,
    text: !0,
    time: !0,
    url: !0,
    week: !0
  };

  function me(a) {
    var b = a && a.nodeName && a.nodeName.toLowerCase();
    return "input" === b ? !!le[a.type] : "textarea" === b ? !0 : !1;
  }

  function ne(a, b, c, d) {
    Eb(d);
    b = oe(b, "onChange");
    0 < b.length && (c = new td("onChange", "change", null, c, d), a.push({
      event: c,
      listeners: b
    }));
  }

  var pe = null,
      qe = null;

  function re$1(a) {
    se(a, 0);
  }

  function te(a) {
    var b = ue(a);
    if (Wa(b)) return a;
  }

  function ve(a, b) {
    if ("change" === a) return b;
  }

  var we = !1;

  if (fa) {
    var xe;

    if (fa) {
      var ye = ("oninput" in document);

      if (!ye) {
        var ze = document.createElement("div");
        ze.setAttribute("oninput", "return;");
        ye = "function" === typeof ze.oninput;
      }

      xe = ye;
    } else xe = !1;

    we = xe && (!document.documentMode || 9 < document.documentMode);
  }

  function Ae() {
    pe && (pe.detachEvent("onpropertychange", Be), qe = pe = null);
  }

  function Be(a) {
    if ("value" === a.propertyName && te(qe)) {
      var b = [];
      ne(b, qe, a, xb(a));
      a = re$1;
      if (Kb) a(b);else {
        Kb = !0;

        try {
          Gb(a, b);
        } finally {
          Kb = !1, Mb();
        }
      }
    }
  }

  function Ce(a, b, c) {
    "focusin" === a ? (Ae(), pe = b, qe = c, pe.attachEvent("onpropertychange", Be)) : "focusout" === a && Ae();
  }

  function De(a) {
    if ("selectionchange" === a || "keyup" === a || "keydown" === a) return te(qe);
  }

  function Ee(a, b) {
    if ("click" === a) return te(b);
  }

  function Fe(a, b) {
    if ("input" === a || "change" === a) return te(b);
  }

  function Ge(a, b) {
    return a === b && (0 !== a || 1 / a === 1 / b) || a !== a && b !== b;
  }

  var He = "function" === typeof Object.is ? Object.is : Ge,
      Ie = Object.prototype.hasOwnProperty;

  function Je(a, b) {
    if (He(a, b)) return !0;
    if ("object" !== typeof a || null === a || "object" !== typeof b || null === b) return !1;
    var c = Object.keys(a),
        d = Object.keys(b);
    if (c.length !== d.length) return !1;

    for (d = 0; d < c.length; d++) if (!Ie.call(b, c[d]) || !He(a[c[d]], b[c[d]])) return !1;

    return !0;
  }

  function Ke(a) {
    for (; a && a.firstChild;) a = a.firstChild;

    return a;
  }

  function Le(a, b) {
    var c = Ke(a);
    a = 0;

    for (var d; c;) {
      if (3 === c.nodeType) {
        d = a + c.textContent.length;
        if (a <= b && d >= b) return {
          node: c,
          offset: b - a
        };
        a = d;
      }

      a: {
        for (; c;) {
          if (c.nextSibling) {
            c = c.nextSibling;
            break a;
          }

          c = c.parentNode;
        }

        c = void 0;
      }

      c = Ke(c);
    }
  }

  function Me(a, b) {
    return a && b ? a === b ? !0 : a && 3 === a.nodeType ? !1 : b && 3 === b.nodeType ? Me(a, b.parentNode) : "contains" in a ? a.contains(b) : a.compareDocumentPosition ? !!(a.compareDocumentPosition(b) & 16) : !1 : !1;
  }

  function Ne() {
    for (var a = window, b = Xa(); b instanceof a.HTMLIFrameElement;) {
      try {
        var c = "string" === typeof b.contentWindow.location.href;
      } catch (d) {
        c = !1;
      }

      if (c) a = b.contentWindow;else break;
      b = Xa(a.document);
    }

    return b;
  }

  function Oe(a) {
    var b = a && a.nodeName && a.nodeName.toLowerCase();
    return b && ("input" === b && ("text" === a.type || "search" === a.type || "tel" === a.type || "url" === a.type || "password" === a.type) || "textarea" === b || "true" === a.contentEditable);
  }

  var Pe = fa && "documentMode" in document && 11 >= document.documentMode,
      Qe = null,
      Re = null,
      Se = null,
      Te = !1;

  function Ue(a, b, c) {
    var d = c.window === c ? c.document : 9 === c.nodeType ? c : c.ownerDocument;
    Te || null == Qe || Qe !== Xa(d) || (d = Qe, "selectionStart" in d && Oe(d) ? d = {
      start: d.selectionStart,
      end: d.selectionEnd
    } : (d = (d.ownerDocument && d.ownerDocument.defaultView || window).getSelection(), d = {
      anchorNode: d.anchorNode,
      anchorOffset: d.anchorOffset,
      focusNode: d.focusNode,
      focusOffset: d.focusOffset
    }), Se && Je(Se, d) || (Se = d, d = oe(Re, "onSelect"), 0 < d.length && (b = new td("onSelect", "select", null, b, c), a.push({
      event: b,
      listeners: d
    }), b.target = Qe)));
  }

  Pc("cancel cancel click click close close contextmenu contextMenu copy copy cut cut auxclick auxClick dblclick doubleClick dragend dragEnd dragstart dragStart drop drop focusin focus focusout blur input input invalid invalid keydown keyDown keypress keyPress keyup keyUp mousedown mouseDown mouseup mouseUp paste paste pause pause play play pointercancel pointerCancel pointerdown pointerDown pointerup pointerUp ratechange rateChange reset reset seeked seeked submit submit touchcancel touchCancel touchend touchEnd touchstart touchStart volumechange volumeChange".split(" "), 0);
  Pc("drag drag dragenter dragEnter dragexit dragExit dragleave dragLeave dragover dragOver mousemove mouseMove mouseout mouseOut mouseover mouseOver pointermove pointerMove pointerout pointerOut pointerover pointerOver scroll scroll toggle toggle touchmove touchMove wheel wheel".split(" "), 1);
  Pc(Oc, 2);

  for (var Ve = "change selectionchange textInput compositionstart compositionend compositionupdate".split(" "), We = 0; We < Ve.length; We++) Nc.set(Ve[We], 0);

  ea("onMouseEnter", ["mouseout", "mouseover"]);
  ea("onMouseLeave", ["mouseout", "mouseover"]);
  ea("onPointerEnter", ["pointerout", "pointerover"]);
  ea("onPointerLeave", ["pointerout", "pointerover"]);
  da("onChange", "change click focusin focusout input keydown keyup selectionchange".split(" "));
  da("onSelect", "focusout contextmenu dragend focusin keydown keyup mousedown mouseup selectionchange".split(" "));
  da("onBeforeInput", ["compositionend", "keypress", "textInput", "paste"]);
  da("onCompositionEnd", "compositionend focusout keydown keypress keyup mousedown".split(" "));
  da("onCompositionStart", "compositionstart focusout keydown keypress keyup mousedown".split(" "));
  da("onCompositionUpdate", "compositionupdate focusout keydown keypress keyup mousedown".split(" "));
  var Xe = "abort canplay canplaythrough durationchange emptied encrypted ended error loadeddata loadedmetadata loadstart pause play playing progress ratechange seeked seeking stalled suspend timeupdate volumechange waiting".split(" "),
      Ye = new Set("cancel close invalid load scroll toggle".split(" ").concat(Xe));

  function Ze(a, b, c) {
    var d = a.type || "unknown-event";
    a.currentTarget = c;
    Yb(d, b, void 0, a);
    a.currentTarget = null;
  }

  function se(a, b) {
    b = 0 !== (b & 4);

    for (var c = 0; c < a.length; c++) {
      var d = a[c],
          e = d.event;
      d = d.listeners;

      a: {
        var f = void 0;
        if (b) for (var g = d.length - 1; 0 <= g; g--) {
          var h = d[g],
              k = h.instance,
              l = h.currentTarget;
          h = h.listener;
          if (k !== f && e.isPropagationStopped()) break a;
          Ze(e, h, l);
          f = k;
        } else for (g = 0; g < d.length; g++) {
          h = d[g];
          k = h.instance;
          l = h.currentTarget;
          h = h.listener;
          if (k !== f && e.isPropagationStopped()) break a;
          Ze(e, h, l);
          f = k;
        }
      }
    }

    if (Ub) throw a = Vb, Ub = !1, Vb = null, a;
  }

  function G(a, b) {
    var c = $e(b),
        d = a + "__bubble";
    c.has(d) || (af(b, a, 2, !1), c.add(d));
  }

  var bf = "_reactListening" + Math.random().toString(36).slice(2);

  function cf(a) {
    a[bf] || (a[bf] = !0, ba.forEach(function (b) {
      Ye.has(b) || df(b, !1, a, null);
      df(b, !0, a, null);
    }));
  }

  function df(a, b, c, d) {
    var e = 4 < arguments.length && void 0 !== arguments[4] ? arguments[4] : 0,
        f = c;
    "selectionchange" === a && 9 !== c.nodeType && (f = c.ownerDocument);

    if (null !== d && !b && Ye.has(a)) {
      if ("scroll" !== a) return;
      e |= 2;
      f = d;
    }

    var g = $e(f),
        h = a + "__" + (b ? "capture" : "bubble");
    g.has(h) || (b && (e |= 4), af(f, a, e, b), g.add(h));
  }

  function af(a, b, c, d) {
    var e = Nc.get(b);

    switch (void 0 === e ? 2 : e) {
      case 0:
        e = gd;
        break;

      case 1:
        e = id$1;
        break;

      default:
        e = hd;
    }

    c = e.bind(null, b, c, a);
    e = void 0;
    !Pb || "touchstart" !== b && "touchmove" !== b && "wheel" !== b || (e = !0);
    d ? void 0 !== e ? a.addEventListener(b, c, {
      capture: !0,
      passive: e
    }) : a.addEventListener(b, c, !0) : void 0 !== e ? a.addEventListener(b, c, {
      passive: e
    }) : a.addEventListener(b, c, !1);
  }

  function jd(a, b, c, d, e) {
    var f = d;
    if (0 === (b & 1) && 0 === (b & 2) && null !== d) a: for (;;) {
      if (null === d) return;
      var g = d.tag;

      if (3 === g || 4 === g) {
        var h = d.stateNode.containerInfo;
        if (h === e || 8 === h.nodeType && h.parentNode === e) break;
        if (4 === g) for (g = d.return; null !== g;) {
          var k = g.tag;
          if (3 === k || 4 === k) if (k = g.stateNode.containerInfo, k === e || 8 === k.nodeType && k.parentNode === e) return;
          g = g.return;
        }

        for (; null !== h;) {
          g = wc(h);
          if (null === g) return;
          k = g.tag;

          if (5 === k || 6 === k) {
            d = f = g;
            continue a;
          }

          h = h.parentNode;
        }
      }

      d = d.return;
    }
    Nb(function () {
      var d = f,
          e = xb(c),
          g = [];

      a: {
        var h = Mc.get(a);

        if (void 0 !== h) {
          var k = td,
              x = a;

          switch (a) {
            case "keypress":
              if (0 === od(c)) break a;

            case "keydown":
            case "keyup":
              k = Rd;
              break;

            case "focusin":
              x = "focus";
              k = Fd;
              break;

            case "focusout":
              x = "blur";
              k = Fd;
              break;

            case "beforeblur":
            case "afterblur":
              k = Fd;
              break;

            case "click":
              if (2 === c.button) break a;

            case "auxclick":
            case "dblclick":
            case "mousedown":
            case "mousemove":
            case "mouseup":
            case "mouseout":
            case "mouseover":
            case "contextmenu":
              k = Bd;
              break;

            case "drag":
            case "dragend":
            case "dragenter":
            case "dragexit":
            case "dragleave":
            case "dragover":
            case "dragstart":
            case "drop":
              k = Dd;
              break;

            case "touchcancel":
            case "touchend":
            case "touchmove":
            case "touchstart":
              k = Vd;
              break;

            case Ic:
            case Jc:
            case Kc:
              k = Hd;
              break;

            case Lc:
              k = Xd;
              break;

            case "scroll":
              k = vd;
              break;

            case "wheel":
              k = Zd;
              break;

            case "copy":
            case "cut":
            case "paste":
              k = Jd;
              break;

            case "gotpointercapture":
            case "lostpointercapture":
            case "pointercancel":
            case "pointerdown":
            case "pointermove":
            case "pointerout":
            case "pointerover":
            case "pointerup":
              k = Td;
          }

          var w = 0 !== (b & 4),
              z = !w && "scroll" === a,
              u = w ? null !== h ? h + "Capture" : null : h;
          w = [];

          for (var t = d, q; null !== t;) {
            q = t;
            var v = q.stateNode;
            5 === q.tag && null !== v && (q = v, null !== u && (v = Ob(t, u), null != v && w.push(ef(t, v, q))));
            if (z) break;
            t = t.return;
          }

          0 < w.length && (h = new k(h, x, null, c, e), g.push({
            event: h,
            listeners: w
          }));
        }
      }

      if (0 === (b & 7)) {
        a: {
          h = "mouseover" === a || "pointerover" === a;
          k = "mouseout" === a || "pointerout" === a;
          if (h && 0 === (b & 16) && (x = c.relatedTarget || c.fromElement) && (wc(x) || x[ff])) break a;

          if (k || h) {
            h = e.window === e ? e : (h = e.ownerDocument) ? h.defaultView || h.parentWindow : window;

            if (k) {
              if (x = c.relatedTarget || c.toElement, k = d, x = x ? wc(x) : null, null !== x && (z = Zb(x), x !== z || 5 !== x.tag && 6 !== x.tag)) x = null;
            } else k = null, x = d;

            if (k !== x) {
              w = Bd;
              v = "onMouseLeave";
              u = "onMouseEnter";
              t = "mouse";
              if ("pointerout" === a || "pointerover" === a) w = Td, v = "onPointerLeave", u = "onPointerEnter", t = "pointer";
              z = null == k ? h : ue(k);
              q = null == x ? h : ue(x);
              h = new w(v, t + "leave", k, c, e);
              h.target = z;
              h.relatedTarget = q;
              v = null;
              wc(e) === d && (w = new w(u, t + "enter", x, c, e), w.target = q, w.relatedTarget = z, v = w);
              z = v;
              if (k && x) b: {
                w = k;
                u = x;
                t = 0;

                for (q = w; q; q = gf(q)) t++;

                q = 0;

                for (v = u; v; v = gf(v)) q++;

                for (; 0 < t - q;) w = gf(w), t--;

                for (; 0 < q - t;) u = gf(u), q--;

                for (; t--;) {
                  if (w === u || null !== u && w === u.alternate) break b;
                  w = gf(w);
                  u = gf(u);
                }

                w = null;
              } else w = null;
              null !== k && hf(g, h, k, w, !1);
              null !== x && null !== z && hf(g, z, x, w, !0);
            }
          }
        }

        a: {
          h = d ? ue(d) : window;
          k = h.nodeName && h.nodeName.toLowerCase();
          if ("select" === k || "input" === k && "file" === h.type) var J = ve;else if (me(h)) {
            if (we) J = Fe;else {
              J = De;
              var K = Ce;
            }
          } else (k = h.nodeName) && "input" === k.toLowerCase() && ("checkbox" === h.type || "radio" === h.type) && (J = Ee);

          if (J && (J = J(a, d))) {
            ne(g, J, c, e);
            break a;
          }

          K && K(a, h, d);
          "focusout" === a && (K = h._wrapperState) && K.controlled && "number" === h.type && bb(h, "number", h.value);
        }

        K = d ? ue(d) : window;

        switch (a) {
          case "focusin":
            if (me(K) || "true" === K.contentEditable) Qe = K, Re = d, Se = null;
            break;

          case "focusout":
            Se = Re = Qe = null;
            break;

          case "mousedown":
            Te = !0;
            break;

          case "contextmenu":
          case "mouseup":
          case "dragend":
            Te = !1;
            Ue(g, c, e);
            break;

          case "selectionchange":
            if (Pe) break;

          case "keydown":
          case "keyup":
            Ue(g, c, e);
        }

        var Q;
        if (ae) b: {
          switch (a) {
            case "compositionstart":
              var L = "onCompositionStart";
              break b;

            case "compositionend":
              L = "onCompositionEnd";
              break b;

            case "compositionupdate":
              L = "onCompositionUpdate";
              break b;
          }

          L = void 0;
        } else ie ? ge(a, c) && (L = "onCompositionEnd") : "keydown" === a && 229 === c.keyCode && (L = "onCompositionStart");
        L && (de && "ko" !== c.locale && (ie || "onCompositionStart" !== L ? "onCompositionEnd" === L && ie && (Q = nd()) : (kd = e, ld = "value" in kd ? kd.value : kd.textContent, ie = !0)), K = oe(d, L), 0 < K.length && (L = new Ld(L, a, null, c, e), g.push({
          event: L,
          listeners: K
        }), Q ? L.data = Q : (Q = he(c), null !== Q && (L.data = Q))));
        if (Q = ce ? je(a, c) : ke(a, c)) d = oe(d, "onBeforeInput"), 0 < d.length && (e = new Ld("onBeforeInput", "beforeinput", null, c, e), g.push({
          event: e,
          listeners: d
        }), e.data = Q);
      }

      se(g, b);
    });
  }

  function ef(a, b, c) {
    return {
      instance: a,
      listener: b,
      currentTarget: c
    };
  }

  function oe(a, b) {
    for (var c = b + "Capture", d = []; null !== a;) {
      var e = a,
          f = e.stateNode;
      5 === e.tag && null !== f && (e = f, f = Ob(a, c), null != f && d.unshift(ef(a, f, e)), f = Ob(a, b), null != f && d.push(ef(a, f, e)));
      a = a.return;
    }

    return d;
  }

  function gf(a) {
    if (null === a) return null;

    do a = a.return; while (a && 5 !== a.tag);

    return a ? a : null;
  }

  function hf(a, b, c, d, e) {
    for (var f = b._reactName, g = []; null !== c && c !== d;) {
      var h = c,
          k = h.alternate,
          l = h.stateNode;
      if (null !== k && k === d) break;
      5 === h.tag && null !== l && (h = l, e ? (k = Ob(c, f), null != k && g.unshift(ef(c, k, h))) : e || (k = Ob(c, f), null != k && g.push(ef(c, k, h))));
      c = c.return;
    }

    0 !== g.length && a.push({
      event: b,
      listeners: g
    });
  }

  function jf() {}

  var kf = null,
      lf = null;

  function mf(a, b) {
    switch (a) {
      case "button":
      case "input":
      case "select":
      case "textarea":
        return !!b.autoFocus;
    }

    return !1;
  }

  function nf(a, b) {
    return "textarea" === a || "option" === a || "noscript" === a || "string" === typeof b.children || "number" === typeof b.children || "object" === typeof b.dangerouslySetInnerHTML && null !== b.dangerouslySetInnerHTML && null != b.dangerouslySetInnerHTML.__html;
  }

  var of = "function" === typeof setTimeout ? setTimeout : void 0,
      pf = "function" === typeof clearTimeout ? clearTimeout : void 0;

  function qf(a) {
    1 === a.nodeType ? a.textContent = "" : 9 === a.nodeType && (a = a.body, null != a && (a.textContent = ""));
  }

  function rf(a) {
    for (; null != a; a = a.nextSibling) {
      var b = a.nodeType;
      if (1 === b || 3 === b) break;
    }

    return a;
  }

  function sf(a) {
    a = a.previousSibling;

    for (var b = 0; a;) {
      if (8 === a.nodeType) {
        var c = a.data;

        if ("$" === c || "$!" === c || "$?" === c) {
          if (0 === b) return a;
          b--;
        } else "/$" === c && b++;
      }

      a = a.previousSibling;
    }

    return null;
  }

  var tf = 0;

  function uf(a) {
    return {
      $$typeof: Ga,
      toString: a,
      valueOf: a
    };
  }

  var vf = Math.random().toString(36).slice(2),
      wf = "__reactFiber$" + vf,
      xf = "__reactProps$" + vf,
      ff = "__reactContainer$" + vf,
      yf = "__reactEvents$" + vf;

  function wc(a) {
    var b = a[wf];
    if (b) return b;

    for (var c = a.parentNode; c;) {
      if (b = c[ff] || c[wf]) {
        c = b.alternate;
        if (null !== b.child || null !== c && null !== c.child) for (a = sf(a); null !== a;) {
          if (c = a[wf]) return c;
          a = sf(a);
        }
        return b;
      }

      a = c;
      c = a.parentNode;
    }

    return null;
  }

  function Cb(a) {
    a = a[wf] || a[ff];
    return !a || 5 !== a.tag && 6 !== a.tag && 13 !== a.tag && 3 !== a.tag ? null : a;
  }

  function ue(a) {
    if (5 === a.tag || 6 === a.tag) return a.stateNode;
    throw Error(y$1(33));
  }

  function Db(a) {
    return a[xf] || null;
  }

  function $e(a) {
    var b = a[yf];
    void 0 === b && (b = a[yf] = new Set());
    return b;
  }

  var zf = [],
      Af = -1;

  function Bf(a) {
    return {
      current: a
    };
  }

  function H(a) {
    0 > Af || (a.current = zf[Af], zf[Af] = null, Af--);
  }

  function I(a, b) {
    Af++;
    zf[Af] = a.current;
    a.current = b;
  }

  var Cf = {},
      M = Bf(Cf),
      N = Bf(!1),
      Df = Cf;

  function Ef(a, b) {
    var c = a.type.contextTypes;
    if (!c) return Cf;
    var d = a.stateNode;
    if (d && d.__reactInternalMemoizedUnmaskedChildContext === b) return d.__reactInternalMemoizedMaskedChildContext;
    var e = {},
        f;

    for (f in c) e[f] = b[f];

    d && (a = a.stateNode, a.__reactInternalMemoizedUnmaskedChildContext = b, a.__reactInternalMemoizedMaskedChildContext = e);
    return e;
  }

  function Ff(a) {
    a = a.childContextTypes;
    return null !== a && void 0 !== a;
  }

  function Gf() {
    H(N);
    H(M);
  }

  function Hf(a, b, c) {
    if (M.current !== Cf) throw Error(y$1(168));
    I(M, b);
    I(N, c);
  }

  function If(a, b, c) {
    var d = a.stateNode;
    a = b.childContextTypes;
    if ("function" !== typeof d.getChildContext) return c;
    d = d.getChildContext();

    for (var e in d) if (!(e in a)) throw Error(y$1(108, Ra(b) || "Unknown", e));

    return objectAssign({}, c, d);
  }

  function Jf(a) {
    a = (a = a.stateNode) && a.__reactInternalMemoizedMergedChildContext || Cf;
    Df = M.current;
    I(M, a);
    I(N, N.current);
    return !0;
  }

  function Kf(a, b, c) {
    var d = a.stateNode;
    if (!d) throw Error(y$1(169));
    c ? (a = If(a, b, Df), d.__reactInternalMemoizedMergedChildContext = a, H(N), H(M), I(M, a)) : H(N);
    I(N, c);
  }

  var Lf = null,
      Mf = null,
      Nf = scheduler.unstable_runWithPriority,
      Of = scheduler.unstable_scheduleCallback,
      Pf = scheduler.unstable_cancelCallback,
      Qf = scheduler.unstable_shouldYield,
      Rf = scheduler.unstable_requestPaint,
      Sf = scheduler.unstable_now,
      Tf = scheduler.unstable_getCurrentPriorityLevel,
      Uf = scheduler.unstable_ImmediatePriority,
      Vf = scheduler.unstable_UserBlockingPriority,
      Wf = scheduler.unstable_NormalPriority,
      Xf = scheduler.unstable_LowPriority,
      Yf = scheduler.unstable_IdlePriority,
      Zf = {},
      $f = void 0 !== Rf ? Rf : function () {},
      ag = null,
      bg = null,
      cg = !1,
      dg = Sf(),
      O = 1E4 > dg ? Sf : function () {
    return Sf() - dg;
  };

  function eg() {
    switch (Tf()) {
      case Uf:
        return 99;

      case Vf:
        return 98;

      case Wf:
        return 97;

      case Xf:
        return 96;

      case Yf:
        return 95;

      default:
        throw Error(y$1(332));
    }
  }

  function fg(a) {
    switch (a) {
      case 99:
        return Uf;

      case 98:
        return Vf;

      case 97:
        return Wf;

      case 96:
        return Xf;

      case 95:
        return Yf;

      default:
        throw Error(y$1(332));
    }
  }

  function gg(a, b) {
    a = fg(a);
    return Nf(a, b);
  }

  function hg(a, b, c) {
    a = fg(a);
    return Of(a, b, c);
  }

  function ig() {
    if (null !== bg) {
      var a = bg;
      bg = null;
      Pf(a);
    }

    jg();
  }

  function jg() {
    if (!cg && null !== ag) {
      cg = !0;
      var a = 0;

      try {
        var b = ag;
        gg(99, function () {
          for (; a < b.length; a++) {
            var c = b[a];

            do c = c(!0); while (null !== c);
          }
        });
        ag = null;
      } catch (c) {
        throw null !== ag && (ag = ag.slice(a + 1)), Of(Uf, ig), c;
      } finally {
        cg = !1;
      }
    }
  }

  var kg = ra.ReactCurrentBatchConfig;

  function lg(a, b) {
    if (a && a.defaultProps) {
      b = objectAssign({}, b);
      a = a.defaultProps;

      for (var c in a) void 0 === b[c] && (b[c] = a[c]);

      return b;
    }

    return b;
  }

  var mg = Bf(null),
      ng = null,
      og = null,
      pg = null;

  function qg() {
    pg = og = ng = null;
  }

  function rg(a) {
    var b = mg.current;
    H(mg);
    a.type._context._currentValue = b;
  }

  function sg(a, b) {
    for (; null !== a;) {
      var c = a.alternate;
      if ((a.childLanes & b) === b) {
        if (null === c || (c.childLanes & b) === b) break;else c.childLanes |= b;
      } else a.childLanes |= b, null !== c && (c.childLanes |= b);
      a = a.return;
    }
  }

  function tg(a, b) {
    ng = a;
    pg = og = null;
    a = a.dependencies;
    null !== a && null !== a.firstContext && (0 !== (a.lanes & b) && (ug = !0), a.firstContext = null);
  }

  function vg(a, b) {
    if (pg !== a && !1 !== b && 0 !== b) {
      if ("number" !== typeof b || 1073741823 === b) pg = a, b = 1073741823;
      b = {
        context: a,
        observedBits: b,
        next: null
      };

      if (null === og) {
        if (null === ng) throw Error(y$1(308));
        og = b;
        ng.dependencies = {
          lanes: 0,
          firstContext: b,
          responders: null
        };
      } else og = og.next = b;
    }

    return a._currentValue;
  }

  var wg = !1;

  function xg(a) {
    a.updateQueue = {
      baseState: a.memoizedState,
      firstBaseUpdate: null,
      lastBaseUpdate: null,
      shared: {
        pending: null
      },
      effects: null
    };
  }

  function yg(a, b) {
    a = a.updateQueue;
    b.updateQueue === a && (b.updateQueue = {
      baseState: a.baseState,
      firstBaseUpdate: a.firstBaseUpdate,
      lastBaseUpdate: a.lastBaseUpdate,
      shared: a.shared,
      effects: a.effects
    });
  }

  function zg(a, b) {
    return {
      eventTime: a,
      lane: b,
      tag: 0,
      payload: null,
      callback: null,
      next: null
    };
  }

  function Ag(a, b) {
    a = a.updateQueue;

    if (null !== a) {
      a = a.shared;
      var c = a.pending;
      null === c ? b.next = b : (b.next = c.next, c.next = b);
      a.pending = b;
    }
  }

  function Bg(a, b) {
    var c = a.updateQueue,
        d = a.alternate;

    if (null !== d && (d = d.updateQueue, c === d)) {
      var e = null,
          f = null;
      c = c.firstBaseUpdate;

      if (null !== c) {
        do {
          var g = {
            eventTime: c.eventTime,
            lane: c.lane,
            tag: c.tag,
            payload: c.payload,
            callback: c.callback,
            next: null
          };
          null === f ? e = f = g : f = f.next = g;
          c = c.next;
        } while (null !== c);

        null === f ? e = f = b : f = f.next = b;
      } else e = f = b;

      c = {
        baseState: d.baseState,
        firstBaseUpdate: e,
        lastBaseUpdate: f,
        shared: d.shared,
        effects: d.effects
      };
      a.updateQueue = c;
      return;
    }

    a = c.lastBaseUpdate;
    null === a ? c.firstBaseUpdate = b : a.next = b;
    c.lastBaseUpdate = b;
  }

  function Cg(a, b, c, d) {
    var e = a.updateQueue;
    wg = !1;
    var f = e.firstBaseUpdate,
        g = e.lastBaseUpdate,
        h = e.shared.pending;

    if (null !== h) {
      e.shared.pending = null;
      var k = h,
          l = k.next;
      k.next = null;
      null === g ? f = l : g.next = l;
      g = k;
      var n = a.alternate;

      if (null !== n) {
        n = n.updateQueue;
        var A = n.lastBaseUpdate;
        A !== g && (null === A ? n.firstBaseUpdate = l : A.next = l, n.lastBaseUpdate = k);
      }
    }

    if (null !== f) {
      A = e.baseState;
      g = 0;
      n = l = k = null;

      do {
        h = f.lane;
        var p = f.eventTime;

        if ((d & h) === h) {
          null !== n && (n = n.next = {
            eventTime: p,
            lane: 0,
            tag: f.tag,
            payload: f.payload,
            callback: f.callback,
            next: null
          });

          a: {
            var C = a,
                x = f;
            h = b;
            p = c;

            switch (x.tag) {
              case 1:
                C = x.payload;

                if ("function" === typeof C) {
                  A = C.call(p, A, h);
                  break a;
                }

                A = C;
                break a;

              case 3:
                C.flags = C.flags & -4097 | 64;

              case 0:
                C = x.payload;
                h = "function" === typeof C ? C.call(p, A, h) : C;
                if (null === h || void 0 === h) break a;
                A = objectAssign({}, A, h);
                break a;

              case 2:
                wg = !0;
            }
          }

          null !== f.callback && (a.flags |= 32, h = e.effects, null === h ? e.effects = [f] : h.push(f));
        } else p = {
          eventTime: p,
          lane: h,
          tag: f.tag,
          payload: f.payload,
          callback: f.callback,
          next: null
        }, null === n ? (l = n = p, k = A) : n = n.next = p, g |= h;

        f = f.next;
        if (null === f) if (h = e.shared.pending, null === h) break;else f = h.next, h.next = null, e.lastBaseUpdate = h, e.shared.pending = null;
      } while (1);

      null === n && (k = A);
      e.baseState = k;
      e.firstBaseUpdate = l;
      e.lastBaseUpdate = n;
      Dg |= g;
      a.lanes = g;
      a.memoizedState = A;
    }
  }

  function Eg(a, b, c) {
    a = b.effects;
    b.effects = null;
    if (null !== a) for (b = 0; b < a.length; b++) {
      var d = a[b],
          e = d.callback;

      if (null !== e) {
        d.callback = null;
        d = c;
        if ("function" !== typeof e) throw Error(y$1(191, e));
        e.call(d);
      }
    }
  }

  var Fg = new react.Component().refs;

  function Gg(a, b, c, d) {
    b = a.memoizedState;
    c = c(d, b);
    c = null === c || void 0 === c ? b : objectAssign({}, b, c);
    a.memoizedState = c;
    0 === a.lanes && (a.updateQueue.baseState = c);
  }

  var Kg = {
    isMounted: function (a) {
      return (a = a._reactInternals) ? Zb(a) === a : !1;
    },
    enqueueSetState: function (a, b, c) {
      a = a._reactInternals;
      var d = Hg(),
          e = Ig(a),
          f = zg(d, e);
      f.payload = b;
      void 0 !== c && null !== c && (f.callback = c);
      Ag(a, f);
      Jg(a, e, d);
    },
    enqueueReplaceState: function (a, b, c) {
      a = a._reactInternals;
      var d = Hg(),
          e = Ig(a),
          f = zg(d, e);
      f.tag = 1;
      f.payload = b;
      void 0 !== c && null !== c && (f.callback = c);
      Ag(a, f);
      Jg(a, e, d);
    },
    enqueueForceUpdate: function (a, b) {
      a = a._reactInternals;
      var c = Hg(),
          d = Ig(a),
          e = zg(c, d);
      e.tag = 2;
      void 0 !== b && null !== b && (e.callback = b);
      Ag(a, e);
      Jg(a, d, c);
    }
  };

  function Lg(a, b, c, d, e, f, g) {
    a = a.stateNode;
    return "function" === typeof a.shouldComponentUpdate ? a.shouldComponentUpdate(d, f, g) : b.prototype && b.prototype.isPureReactComponent ? !Je(c, d) || !Je(e, f) : !0;
  }

  function Mg(a, b, c) {
    var d = !1,
        e = Cf;
    var f = b.contextType;
    "object" === typeof f && null !== f ? f = vg(f) : (e = Ff(b) ? Df : M.current, d = b.contextTypes, f = (d = null !== d && void 0 !== d) ? Ef(a, e) : Cf);
    b = new b(c, f);
    a.memoizedState = null !== b.state && void 0 !== b.state ? b.state : null;
    b.updater = Kg;
    a.stateNode = b;
    b._reactInternals = a;
    d && (a = a.stateNode, a.__reactInternalMemoizedUnmaskedChildContext = e, a.__reactInternalMemoizedMaskedChildContext = f);
    return b;
  }

  function Ng(a, b, c, d) {
    a = b.state;
    "function" === typeof b.componentWillReceiveProps && b.componentWillReceiveProps(c, d);
    "function" === typeof b.UNSAFE_componentWillReceiveProps && b.UNSAFE_componentWillReceiveProps(c, d);
    b.state !== a && Kg.enqueueReplaceState(b, b.state, null);
  }

  function Og(a, b, c, d) {
    var e = a.stateNode;
    e.props = c;
    e.state = a.memoizedState;
    e.refs = Fg;
    xg(a);
    var f = b.contextType;
    "object" === typeof f && null !== f ? e.context = vg(f) : (f = Ff(b) ? Df : M.current, e.context = Ef(a, f));
    Cg(a, c, e, d);
    e.state = a.memoizedState;
    f = b.getDerivedStateFromProps;
    "function" === typeof f && (Gg(a, b, f, c), e.state = a.memoizedState);
    "function" === typeof b.getDerivedStateFromProps || "function" === typeof e.getSnapshotBeforeUpdate || "function" !== typeof e.UNSAFE_componentWillMount && "function" !== typeof e.componentWillMount || (b = e.state, "function" === typeof e.componentWillMount && e.componentWillMount(), "function" === typeof e.UNSAFE_componentWillMount && e.UNSAFE_componentWillMount(), b !== e.state && Kg.enqueueReplaceState(e, e.state, null), Cg(a, c, e, d), e.state = a.memoizedState);
    "function" === typeof e.componentDidMount && (a.flags |= 4);
  }

  var Pg = Array.isArray;

  function Qg(a, b, c) {
    a = c.ref;

    if (null !== a && "function" !== typeof a && "object" !== typeof a) {
      if (c._owner) {
        c = c._owner;

        if (c) {
          if (1 !== c.tag) throw Error(y$1(309));
          var d = c.stateNode;
        }

        if (!d) throw Error(y$1(147, a));
        var e = "" + a;
        if (null !== b && null !== b.ref && "function" === typeof b.ref && b.ref._stringRef === e) return b.ref;

        b = function (a) {
          var b = d.refs;
          b === Fg && (b = d.refs = {});
          null === a ? delete b[e] : b[e] = a;
        };

        b._stringRef = e;
        return b;
      }

      if ("string" !== typeof a) throw Error(y$1(284));
      if (!c._owner) throw Error(y$1(290, a));
    }

    return a;
  }

  function Rg(a, b) {
    if ("textarea" !== a.type) throw Error(y$1(31, "[object Object]" === Object.prototype.toString.call(b) ? "object with keys {" + Object.keys(b).join(", ") + "}" : b));
  }

  function Sg(a) {
    function b(b, c) {
      if (a) {
        var d = b.lastEffect;
        null !== d ? (d.nextEffect = c, b.lastEffect = c) : b.firstEffect = b.lastEffect = c;
        c.nextEffect = null;
        c.flags = 8;
      }
    }

    function c(c, d) {
      if (!a) return null;

      for (; null !== d;) b(c, d), d = d.sibling;

      return null;
    }

    function d(a, b) {
      for (a = new Map(); null !== b;) null !== b.key ? a.set(b.key, b) : a.set(b.index, b), b = b.sibling;

      return a;
    }

    function e(a, b) {
      a = Tg(a, b);
      a.index = 0;
      a.sibling = null;
      return a;
    }

    function f(b, c, d) {
      b.index = d;
      if (!a) return c;
      d = b.alternate;
      if (null !== d) return d = d.index, d < c ? (b.flags = 2, c) : d;
      b.flags = 2;
      return c;
    }

    function g(b) {
      a && null === b.alternate && (b.flags = 2);
      return b;
    }

    function h(a, b, c, d) {
      if (null === b || 6 !== b.tag) return b = Ug(c, a.mode, d), b.return = a, b;
      b = e(b, c);
      b.return = a;
      return b;
    }

    function k(a, b, c, d) {
      if (null !== b && b.elementType === c.type) return d = e(b, c.props), d.ref = Qg(a, b, c), d.return = a, d;
      d = Vg(c.type, c.key, c.props, null, a.mode, d);
      d.ref = Qg(a, b, c);
      d.return = a;
      return d;
    }

    function l(a, b, c, d) {
      if (null === b || 4 !== b.tag || b.stateNode.containerInfo !== c.containerInfo || b.stateNode.implementation !== c.implementation) return b = Wg(c, a.mode, d), b.return = a, b;
      b = e(b, c.children || []);
      b.return = a;
      return b;
    }

    function n(a, b, c, d, f) {
      if (null === b || 7 !== b.tag) return b = Xg(c, a.mode, d, f), b.return = a, b;
      b = e(b, c);
      b.return = a;
      return b;
    }

    function A(a, b, c) {
      if ("string" === typeof b || "number" === typeof b) return b = Ug("" + b, a.mode, c), b.return = a, b;

      if ("object" === typeof b && null !== b) {
        switch (b.$$typeof) {
          case sa:
            return c = Vg(b.type, b.key, b.props, null, a.mode, c), c.ref = Qg(a, null, b), c.return = a, c;

          case ta:
            return b = Wg(b, a.mode, c), b.return = a, b;
        }

        if (Pg(b) || La(b)) return b = Xg(b, a.mode, c, null), b.return = a, b;
        Rg(a, b);
      }

      return null;
    }

    function p(a, b, c, d) {
      var e = null !== b ? b.key : null;
      if ("string" === typeof c || "number" === typeof c) return null !== e ? null : h(a, b, "" + c, d);

      if ("object" === typeof c && null !== c) {
        switch (c.$$typeof) {
          case sa:
            return c.key === e ? c.type === ua ? n(a, b, c.props.children, d, e) : k(a, b, c, d) : null;

          case ta:
            return c.key === e ? l(a, b, c, d) : null;
        }

        if (Pg(c) || La(c)) return null !== e ? null : n(a, b, c, d, null);
        Rg(a, c);
      }

      return null;
    }

    function C(a, b, c, d, e) {
      if ("string" === typeof d || "number" === typeof d) return a = a.get(c) || null, h(b, a, "" + d, e);

      if ("object" === typeof d && null !== d) {
        switch (d.$$typeof) {
          case sa:
            return a = a.get(null === d.key ? c : d.key) || null, d.type === ua ? n(b, a, d.props.children, e, d.key) : k(b, a, d, e);

          case ta:
            return a = a.get(null === d.key ? c : d.key) || null, l(b, a, d, e);
        }

        if (Pg(d) || La(d)) return a = a.get(c) || null, n(b, a, d, e, null);
        Rg(b, d);
      }

      return null;
    }

    function x(e, g, h, k) {
      for (var l = null, t = null, u = g, z = g = 0, q = null; null !== u && z < h.length; z++) {
        u.index > z ? (q = u, u = null) : q = u.sibling;
        var n = p(e, u, h[z], k);

        if (null === n) {
          null === u && (u = q);
          break;
        }

        a && u && null === n.alternate && b(e, u);
        g = f(n, g, z);
        null === t ? l = n : t.sibling = n;
        t = n;
        u = q;
      }

      if (z === h.length) return c(e, u), l;

      if (null === u) {
        for (; z < h.length; z++) u = A(e, h[z], k), null !== u && (g = f(u, g, z), null === t ? l = u : t.sibling = u, t = u);

        return l;
      }

      for (u = d(e, u); z < h.length; z++) q = C(u, e, z, h[z], k), null !== q && (a && null !== q.alternate && u.delete(null === q.key ? z : q.key), g = f(q, g, z), null === t ? l = q : t.sibling = q, t = q);

      a && u.forEach(function (a) {
        return b(e, a);
      });
      return l;
    }

    function w(e, g, h, k) {
      var l = La(h);
      if ("function" !== typeof l) throw Error(y$1(150));
      h = l.call(h);
      if (null == h) throw Error(y$1(151));

      for (var t = l = null, u = g, z = g = 0, q = null, n = h.next(); null !== u && !n.done; z++, n = h.next()) {
        u.index > z ? (q = u, u = null) : q = u.sibling;
        var w = p(e, u, n.value, k);

        if (null === w) {
          null === u && (u = q);
          break;
        }

        a && u && null === w.alternate && b(e, u);
        g = f(w, g, z);
        null === t ? l = w : t.sibling = w;
        t = w;
        u = q;
      }

      if (n.done) return c(e, u), l;

      if (null === u) {
        for (; !n.done; z++, n = h.next()) n = A(e, n.value, k), null !== n && (g = f(n, g, z), null === t ? l = n : t.sibling = n, t = n);

        return l;
      }

      for (u = d(e, u); !n.done; z++, n = h.next()) n = C(u, e, z, n.value, k), null !== n && (a && null !== n.alternate && u.delete(null === n.key ? z : n.key), g = f(n, g, z), null === t ? l = n : t.sibling = n, t = n);

      a && u.forEach(function (a) {
        return b(e, a);
      });
      return l;
    }

    return function (a, d, f, h) {
      var k = "object" === typeof f && null !== f && f.type === ua && null === f.key;
      k && (f = f.props.children);
      var l = "object" === typeof f && null !== f;
      if (l) switch (f.$$typeof) {
        case sa:
          a: {
            l = f.key;

            for (k = d; null !== k;) {
              if (k.key === l) {
                switch (k.tag) {
                  case 7:
                    if (f.type === ua) {
                      c(a, k.sibling);
                      d = e(k, f.props.children);
                      d.return = a;
                      a = d;
                      break a;
                    }

                    break;

                  default:
                    if (k.elementType === f.type) {
                      c(a, k.sibling);
                      d = e(k, f.props);
                      d.ref = Qg(a, k, f);
                      d.return = a;
                      a = d;
                      break a;
                    }

                }

                c(a, k);
                break;
              } else b(a, k);

              k = k.sibling;
            }

            f.type === ua ? (d = Xg(f.props.children, a.mode, h, f.key), d.return = a, a = d) : (h = Vg(f.type, f.key, f.props, null, a.mode, h), h.ref = Qg(a, d, f), h.return = a, a = h);
          }

          return g(a);

        case ta:
          a: {
            for (k = f.key; null !== d;) {
              if (d.key === k) {
                if (4 === d.tag && d.stateNode.containerInfo === f.containerInfo && d.stateNode.implementation === f.implementation) {
                  c(a, d.sibling);
                  d = e(d, f.children || []);
                  d.return = a;
                  a = d;
                  break a;
                } else {
                  c(a, d);
                  break;
                }
              } else b(a, d);
              d = d.sibling;
            }

            d = Wg(f, a.mode, h);
            d.return = a;
            a = d;
          }

          return g(a);
      }
      if ("string" === typeof f || "number" === typeof f) return f = "" + f, null !== d && 6 === d.tag ? (c(a, d.sibling), d = e(d, f), d.return = a, a = d) : (c(a, d), d = Ug(f, a.mode, h), d.return = a, a = d), g(a);
      if (Pg(f)) return x(a, d, f, h);
      if (La(f)) return w(a, d, f, h);
      l && Rg(a, f);
      if ("undefined" === typeof f && !k) switch (a.tag) {
        case 1:
        case 22:
        case 0:
        case 11:
        case 15:
          throw Error(y$1(152, Ra(a.type) || "Component"));
      }
      return c(a, d);
    };
  }

  var Yg = Sg(!0),
      Zg = Sg(!1),
      $g = {},
      ah = Bf($g),
      bh = Bf($g),
      ch = Bf($g);

  function dh(a) {
    if (a === $g) throw Error(y$1(174));
    return a;
  }

  function eh(a, b) {
    I(ch, b);
    I(bh, a);
    I(ah, $g);
    a = b.nodeType;

    switch (a) {
      case 9:
      case 11:
        b = (b = b.documentElement) ? b.namespaceURI : mb(null, "");
        break;

      default:
        a = 8 === a ? b.parentNode : b, b = a.namespaceURI || null, a = a.tagName, b = mb(b, a);
    }

    H(ah);
    I(ah, b);
  }

  function fh() {
    H(ah);
    H(bh);
    H(ch);
  }

  function gh(a) {
    dh(ch.current);
    var b = dh(ah.current);
    var c = mb(b, a.type);
    b !== c && (I(bh, a), I(ah, c));
  }

  function hh(a) {
    bh.current === a && (H(ah), H(bh));
  }

  var P = Bf(0);

  function ih(a) {
    for (var b = a; null !== b;) {
      if (13 === b.tag) {
        var c = b.memoizedState;
        if (null !== c && (c = c.dehydrated, null === c || "$?" === c.data || "$!" === c.data)) return b;
      } else if (19 === b.tag && void 0 !== b.memoizedProps.revealOrder) {
        if (0 !== (b.flags & 64)) return b;
      } else if (null !== b.child) {
        b.child.return = b;
        b = b.child;
        continue;
      }

      if (b === a) break;

      for (; null === b.sibling;) {
        if (null === b.return || b.return === a) return null;
        b = b.return;
      }

      b.sibling.return = b.return;
      b = b.sibling;
    }

    return null;
  }

  var jh = null,
      kh = null,
      lh = !1;

  function mh(a, b) {
    var c = nh(5, null, null, 0);
    c.elementType = "DELETED";
    c.type = "DELETED";
    c.stateNode = b;
    c.return = a;
    c.flags = 8;
    null !== a.lastEffect ? (a.lastEffect.nextEffect = c, a.lastEffect = c) : a.firstEffect = a.lastEffect = c;
  }

  function oh(a, b) {
    switch (a.tag) {
      case 5:
        var c = a.type;
        b = 1 !== b.nodeType || c.toLowerCase() !== b.nodeName.toLowerCase() ? null : b;
        return null !== b ? (a.stateNode = b, !0) : !1;

      case 6:
        return b = "" === a.pendingProps || 3 !== b.nodeType ? null : b, null !== b ? (a.stateNode = b, !0) : !1;

      case 13:
        return !1;

      default:
        return !1;
    }
  }

  function ph(a) {
    if (lh) {
      var b = kh;

      if (b) {
        var c = b;

        if (!oh(a, b)) {
          b = rf(c.nextSibling);

          if (!b || !oh(a, b)) {
            a.flags = a.flags & -1025 | 2;
            lh = !1;
            jh = a;
            return;
          }

          mh(jh, c);
        }

        jh = a;
        kh = rf(b.firstChild);
      } else a.flags = a.flags & -1025 | 2, lh = !1, jh = a;
    }
  }

  function qh(a) {
    for (a = a.return; null !== a && 5 !== a.tag && 3 !== a.tag && 13 !== a.tag;) a = a.return;

    jh = a;
  }

  function rh(a) {
    if (a !== jh) return !1;
    if (!lh) return qh(a), lh = !0, !1;
    var b = a.type;
    if (5 !== a.tag || "head" !== b && "body" !== b && !nf(b, a.memoizedProps)) for (b = kh; b;) mh(a, b), b = rf(b.nextSibling);
    qh(a);

    if (13 === a.tag) {
      a = a.memoizedState;
      a = null !== a ? a.dehydrated : null;
      if (!a) throw Error(y$1(317));

      a: {
        a = a.nextSibling;

        for (b = 0; a;) {
          if (8 === a.nodeType) {
            var c = a.data;

            if ("/$" === c) {
              if (0 === b) {
                kh = rf(a.nextSibling);
                break a;
              }

              b--;
            } else "$" !== c && "$!" !== c && "$?" !== c || b++;
          }

          a = a.nextSibling;
        }

        kh = null;
      }
    } else kh = jh ? rf(a.stateNode.nextSibling) : null;

    return !0;
  }

  function sh() {
    kh = jh = null;
    lh = !1;
  }

  var th = [];

  function uh() {
    for (var a = 0; a < th.length; a++) th[a]._workInProgressVersionPrimary = null;

    th.length = 0;
  }

  var vh = ra.ReactCurrentDispatcher,
      wh = ra.ReactCurrentBatchConfig,
      xh = 0,
      R = null,
      S = null,
      T = null,
      yh = !1,
      zh = !1;

  function Ah() {
    throw Error(y$1(321));
  }

  function Bh(a, b) {
    if (null === b) return !1;

    for (var c = 0; c < b.length && c < a.length; c++) if (!He(a[c], b[c])) return !1;

    return !0;
  }

  function Ch(a, b, c, d, e, f) {
    xh = f;
    R = b;
    b.memoizedState = null;
    b.updateQueue = null;
    b.lanes = 0;
    vh.current = null === a || null === a.memoizedState ? Dh : Eh;
    a = c(d, e);

    if (zh) {
      f = 0;

      do {
        zh = !1;
        if (!(25 > f)) throw Error(y$1(301));
        f += 1;
        T = S = null;
        b.updateQueue = null;
        vh.current = Fh;
        a = c(d, e);
      } while (zh);
    }

    vh.current = Gh;
    b = null !== S && null !== S.next;
    xh = 0;
    T = S = R = null;
    yh = !1;
    if (b) throw Error(y$1(300));
    return a;
  }

  function Hh() {
    var a = {
      memoizedState: null,
      baseState: null,
      baseQueue: null,
      queue: null,
      next: null
    };
    null === T ? R.memoizedState = T = a : T = T.next = a;
    return T;
  }

  function Ih() {
    if (null === S) {
      var a = R.alternate;
      a = null !== a ? a.memoizedState : null;
    } else a = S.next;

    var b = null === T ? R.memoizedState : T.next;
    if (null !== b) T = b, S = a;else {
      if (null === a) throw Error(y$1(310));
      S = a;
      a = {
        memoizedState: S.memoizedState,
        baseState: S.baseState,
        baseQueue: S.baseQueue,
        queue: S.queue,
        next: null
      };
      null === T ? R.memoizedState = T = a : T = T.next = a;
    }
    return T;
  }

  function Jh(a, b) {
    return "function" === typeof b ? b(a) : b;
  }

  function Kh(a) {
    var b = Ih(),
        c = b.queue;
    if (null === c) throw Error(y$1(311));
    c.lastRenderedReducer = a;
    var d = S,
        e = d.baseQueue,
        f = c.pending;

    if (null !== f) {
      if (null !== e) {
        var g = e.next;
        e.next = f.next;
        f.next = g;
      }

      d.baseQueue = e = f;
      c.pending = null;
    }

    if (null !== e) {
      e = e.next;
      d = d.baseState;
      var h = g = f = null,
          k = e;

      do {
        var l = k.lane;
        if ((xh & l) === l) null !== h && (h = h.next = {
          lane: 0,
          action: k.action,
          eagerReducer: k.eagerReducer,
          eagerState: k.eagerState,
          next: null
        }), d = k.eagerReducer === a ? k.eagerState : a(d, k.action);else {
          var n = {
            lane: l,
            action: k.action,
            eagerReducer: k.eagerReducer,
            eagerState: k.eagerState,
            next: null
          };
          null === h ? (g = h = n, f = d) : h = h.next = n;
          R.lanes |= l;
          Dg |= l;
        }
        k = k.next;
      } while (null !== k && k !== e);

      null === h ? f = d : h.next = g;
      He(d, b.memoizedState) || (ug = !0);
      b.memoizedState = d;
      b.baseState = f;
      b.baseQueue = h;
      c.lastRenderedState = d;
    }

    return [b.memoizedState, c.dispatch];
  }

  function Lh(a) {
    var b = Ih(),
        c = b.queue;
    if (null === c) throw Error(y$1(311));
    c.lastRenderedReducer = a;
    var d = c.dispatch,
        e = c.pending,
        f = b.memoizedState;

    if (null !== e) {
      c.pending = null;
      var g = e = e.next;

      do f = a(f, g.action), g = g.next; while (g !== e);

      He(f, b.memoizedState) || (ug = !0);
      b.memoizedState = f;
      null === b.baseQueue && (b.baseState = f);
      c.lastRenderedState = f;
    }

    return [f, d];
  }

  function Mh(a, b, c) {
    var d = b._getVersion;
    d = d(b._source);
    var e = b._workInProgressVersionPrimary;
    if (null !== e) a = e === d;else if (a = a.mutableReadLanes, a = (xh & a) === a) b._workInProgressVersionPrimary = d, th.push(b);
    if (a) return c(b._source);
    th.push(b);
    throw Error(y$1(350));
  }

  function Nh(a, b, c, d) {
    var e = U;
    if (null === e) throw Error(y$1(349));
    var f = b._getVersion,
        g = f(b._source),
        h = vh.current,
        k = h.useState(function () {
      return Mh(e, b, c);
    }),
        l = k[1],
        n = k[0];
    k = T;
    var A = a.memoizedState,
        p = A.refs,
        C = p.getSnapshot,
        x = A.source;
    A = A.subscribe;
    var w = R;
    a.memoizedState = {
      refs: p,
      source: b,
      subscribe: d
    };
    h.useEffect(function () {
      p.getSnapshot = c;
      p.setSnapshot = l;
      var a = f(b._source);

      if (!He(g, a)) {
        a = c(b._source);
        He(n, a) || (l(a), a = Ig(w), e.mutableReadLanes |= a & e.pendingLanes);
        a = e.mutableReadLanes;
        e.entangledLanes |= a;

        for (var d = e.entanglements, h = a; 0 < h;) {
          var k = 31 - Vc(h),
              v = 1 << k;
          d[k] |= a;
          h &= ~v;
        }
      }
    }, [c, b, d]);
    h.useEffect(function () {
      return d(b._source, function () {
        var a = p.getSnapshot,
            c = p.setSnapshot;

        try {
          c(a(b._source));
          var d = Ig(w);
          e.mutableReadLanes |= d & e.pendingLanes;
        } catch (q) {
          c(function () {
            throw q;
          });
        }
      });
    }, [b, d]);
    He(C, c) && He(x, b) && He(A, d) || (a = {
      pending: null,
      dispatch: null,
      lastRenderedReducer: Jh,
      lastRenderedState: n
    }, a.dispatch = l = Oh.bind(null, R, a), k.queue = a, k.baseQueue = null, n = Mh(e, b, c), k.memoizedState = k.baseState = n);
    return n;
  }

  function Ph(a, b, c) {
    var d = Ih();
    return Nh(d, a, b, c);
  }

  function Qh(a) {
    var b = Hh();
    "function" === typeof a && (a = a());
    b.memoizedState = b.baseState = a;
    a = b.queue = {
      pending: null,
      dispatch: null,
      lastRenderedReducer: Jh,
      lastRenderedState: a
    };
    a = a.dispatch = Oh.bind(null, R, a);
    return [b.memoizedState, a];
  }

  function Rh(a, b, c, d) {
    a = {
      tag: a,
      create: b,
      destroy: c,
      deps: d,
      next: null
    };
    b = R.updateQueue;
    null === b ? (b = {
      lastEffect: null
    }, R.updateQueue = b, b.lastEffect = a.next = a) : (c = b.lastEffect, null === c ? b.lastEffect = a.next = a : (d = c.next, c.next = a, a.next = d, b.lastEffect = a));
    return a;
  }

  function Sh(a) {
    var b = Hh();
    a = {
      current: a
    };
    return b.memoizedState = a;
  }

  function Th() {
    return Ih().memoizedState;
  }

  function Uh(a, b, c, d) {
    var e = Hh();
    R.flags |= a;
    e.memoizedState = Rh(1 | b, c, void 0, void 0 === d ? null : d);
  }

  function Vh(a, b, c, d) {
    var e = Ih();
    d = void 0 === d ? null : d;
    var f = void 0;

    if (null !== S) {
      var g = S.memoizedState;
      f = g.destroy;

      if (null !== d && Bh(d, g.deps)) {
        Rh(b, c, f, d);
        return;
      }
    }

    R.flags |= a;
    e.memoizedState = Rh(1 | b, c, f, d);
  }

  function Wh(a, b) {
    return Uh(516, 4, a, b);
  }

  function Xh(a, b) {
    return Vh(516, 4, a, b);
  }

  function Yh(a, b) {
    return Vh(4, 2, a, b);
  }

  function Zh(a, b) {
    if ("function" === typeof b) return a = a(), b(a), function () {
      b(null);
    };
    if (null !== b && void 0 !== b) return a = a(), b.current = a, function () {
      b.current = null;
    };
  }

  function $h(a, b, c) {
    c = null !== c && void 0 !== c ? c.concat([a]) : null;
    return Vh(4, 2, Zh.bind(null, b, a), c);
  }

  function ai() {}

  function bi(a, b) {
    var c = Ih();
    b = void 0 === b ? null : b;
    var d = c.memoizedState;
    if (null !== d && null !== b && Bh(b, d[1])) return d[0];
    c.memoizedState = [a, b];
    return a;
  }

  function ci(a, b) {
    var c = Ih();
    b = void 0 === b ? null : b;
    var d = c.memoizedState;
    if (null !== d && null !== b && Bh(b, d[1])) return d[0];
    a = a();
    c.memoizedState = [a, b];
    return a;
  }

  function di(a, b) {
    var c = eg();
    gg(98 > c ? 98 : c, function () {
      a(!0);
    });
    gg(97 < c ? 97 : c, function () {
      var c = wh.transition;
      wh.transition = 1;

      try {
        a(!1), b();
      } finally {
        wh.transition = c;
      }
    });
  }

  function Oh(a, b, c) {
    var d = Hg(),
        e = Ig(a),
        f = {
      lane: e,
      action: c,
      eagerReducer: null,
      eagerState: null,
      next: null
    },
        g = b.pending;
    null === g ? f.next = f : (f.next = g.next, g.next = f);
    b.pending = f;
    g = a.alternate;
    if (a === R || null !== g && g === R) zh = yh = !0;else {
      if (0 === a.lanes && (null === g || 0 === g.lanes) && (g = b.lastRenderedReducer, null !== g)) try {
        var h = b.lastRenderedState,
            k = g(h, c);
        f.eagerReducer = g;
        f.eagerState = k;
        if (He(k, h)) return;
      } catch (l) {} finally {}
      Jg(a, e, d);
    }
  }

  var Gh = {
    readContext: vg,
    useCallback: Ah,
    useContext: Ah,
    useEffect: Ah,
    useImperativeHandle: Ah,
    useLayoutEffect: Ah,
    useMemo: Ah,
    useReducer: Ah,
    useRef: Ah,
    useState: Ah,
    useDebugValue: Ah,
    useDeferredValue: Ah,
    useTransition: Ah,
    useMutableSource: Ah,
    useOpaqueIdentifier: Ah,
    unstable_isNewReconciler: !1
  },
      Dh = {
    readContext: vg,
    useCallback: function (a, b) {
      Hh().memoizedState = [a, void 0 === b ? null : b];
      return a;
    },
    useContext: vg,
    useEffect: Wh,
    useImperativeHandle: function (a, b, c) {
      c = null !== c && void 0 !== c ? c.concat([a]) : null;
      return Uh(4, 2, Zh.bind(null, b, a), c);
    },
    useLayoutEffect: function (a, b) {
      return Uh(4, 2, a, b);
    },
    useMemo: function (a, b) {
      var c = Hh();
      b = void 0 === b ? null : b;
      a = a();
      c.memoizedState = [a, b];
      return a;
    },
    useReducer: function (a, b, c) {
      var d = Hh();
      b = void 0 !== c ? c(b) : b;
      d.memoizedState = d.baseState = b;
      a = d.queue = {
        pending: null,
        dispatch: null,
        lastRenderedReducer: a,
        lastRenderedState: b
      };
      a = a.dispatch = Oh.bind(null, R, a);
      return [d.memoizedState, a];
    },
    useRef: Sh,
    useState: Qh,
    useDebugValue: ai,
    useDeferredValue: function (a) {
      var b = Qh(a),
          c = b[0],
          d = b[1];
      Wh(function () {
        var b = wh.transition;
        wh.transition = 1;

        try {
          d(a);
        } finally {
          wh.transition = b;
        }
      }, [a]);
      return c;
    },
    useTransition: function () {
      var a = Qh(!1),
          b = a[0];
      a = di.bind(null, a[1]);
      Sh(a);
      return [a, b];
    },
    useMutableSource: function (a, b, c) {
      var d = Hh();
      d.memoizedState = {
        refs: {
          getSnapshot: b,
          setSnapshot: null
        },
        source: a,
        subscribe: c
      };
      return Nh(d, a, b, c);
    },
    useOpaqueIdentifier: function () {
      if (lh) {
        var a = !1,
            b = uf(function () {
          a || (a = !0, c("r:" + (tf++).toString(36)));
          throw Error(y$1(355));
        }),
            c = Qh(b)[1];
        0 === (R.mode & 2) && (R.flags |= 516, Rh(5, function () {
          c("r:" + (tf++).toString(36));
        }, void 0, null));
        return b;
      }

      b = "r:" + (tf++).toString(36);
      Qh(b);
      return b;
    },
    unstable_isNewReconciler: !1
  },
      Eh = {
    readContext: vg,
    useCallback: bi,
    useContext: vg,
    useEffect: Xh,
    useImperativeHandle: $h,
    useLayoutEffect: Yh,
    useMemo: ci,
    useReducer: Kh,
    useRef: Th,
    useState: function () {
      return Kh(Jh);
    },
    useDebugValue: ai,
    useDeferredValue: function (a) {
      var b = Kh(Jh),
          c = b[0],
          d = b[1];
      Xh(function () {
        var b = wh.transition;
        wh.transition = 1;

        try {
          d(a);
        } finally {
          wh.transition = b;
        }
      }, [a]);
      return c;
    },
    useTransition: function () {
      var a = Kh(Jh)[0];
      return [Th().current, a];
    },
    useMutableSource: Ph,
    useOpaqueIdentifier: function () {
      return Kh(Jh)[0];
    },
    unstable_isNewReconciler: !1
  },
      Fh = {
    readContext: vg,
    useCallback: bi,
    useContext: vg,
    useEffect: Xh,
    useImperativeHandle: $h,
    useLayoutEffect: Yh,
    useMemo: ci,
    useReducer: Lh,
    useRef: Th,
    useState: function () {
      return Lh(Jh);
    },
    useDebugValue: ai,
    useDeferredValue: function (a) {
      var b = Lh(Jh),
          c = b[0],
          d = b[1];
      Xh(function () {
        var b = wh.transition;
        wh.transition = 1;

        try {
          d(a);
        } finally {
          wh.transition = b;
        }
      }, [a]);
      return c;
    },
    useTransition: function () {
      var a = Lh(Jh)[0];
      return [Th().current, a];
    },
    useMutableSource: Ph,
    useOpaqueIdentifier: function () {
      return Lh(Jh)[0];
    },
    unstable_isNewReconciler: !1
  },
      ei = ra.ReactCurrentOwner,
      ug = !1;

  function fi(a, b, c, d) {
    b.child = null === a ? Zg(b, null, c, d) : Yg(b, a.child, c, d);
  }

  function gi(a, b, c, d, e) {
    c = c.render;
    var f = b.ref;
    tg(b, e);
    d = Ch(a, b, c, d, f, e);
    if (null !== a && !ug) return b.updateQueue = a.updateQueue, b.flags &= -517, a.lanes &= ~e, hi(a, b, e);
    b.flags |= 1;
    fi(a, b, d, e);
    return b.child;
  }

  function ii(a, b, c, d, e, f) {
    if (null === a) {
      var g = c.type;
      if ("function" === typeof g && !ji(g) && void 0 === g.defaultProps && null === c.compare && void 0 === c.defaultProps) return b.tag = 15, b.type = g, ki(a, b, g, d, e, f);
      a = Vg(c.type, null, d, b, b.mode, f);
      a.ref = b.ref;
      a.return = b;
      return b.child = a;
    }

    g = a.child;
    if (0 === (e & f) && (e = g.memoizedProps, c = c.compare, c = null !== c ? c : Je, c(e, d) && a.ref === b.ref)) return hi(a, b, f);
    b.flags |= 1;
    a = Tg(g, d);
    a.ref = b.ref;
    a.return = b;
    return b.child = a;
  }

  function ki(a, b, c, d, e, f) {
    if (null !== a && Je(a.memoizedProps, d) && a.ref === b.ref) if (ug = !1, 0 !== (f & e)) 0 !== (a.flags & 16384) && (ug = !0);else return b.lanes = a.lanes, hi(a, b, f);
    return li(a, b, c, d, f);
  }

  function mi(a, b, c) {
    var d = b.pendingProps,
        e = d.children,
        f = null !== a ? a.memoizedState : null;
    if ("hidden" === d.mode || "unstable-defer-without-hiding" === d.mode) {
      if (0 === (b.mode & 4)) b.memoizedState = {
        baseLanes: 0
      }, ni(b, c);else if (0 !== (c & 1073741824)) b.memoizedState = {
        baseLanes: 0
      }, ni(b, null !== f ? f.baseLanes : c);else return a = null !== f ? f.baseLanes | c : c, b.lanes = b.childLanes = 1073741824, b.memoizedState = {
        baseLanes: a
      }, ni(b, a), null;
    } else null !== f ? (d = f.baseLanes | c, b.memoizedState = null) : d = c, ni(b, d);
    fi(a, b, e, c);
    return b.child;
  }

  function oi(a, b) {
    var c = b.ref;
    if (null === a && null !== c || null !== a && a.ref !== c) b.flags |= 128;
  }

  function li(a, b, c, d, e) {
    var f = Ff(c) ? Df : M.current;
    f = Ef(b, f);
    tg(b, e);
    c = Ch(a, b, c, d, f, e);
    if (null !== a && !ug) return b.updateQueue = a.updateQueue, b.flags &= -517, a.lanes &= ~e, hi(a, b, e);
    b.flags |= 1;
    fi(a, b, c, e);
    return b.child;
  }

  function pi(a, b, c, d, e) {
    if (Ff(c)) {
      var f = !0;
      Jf(b);
    } else f = !1;

    tg(b, e);
    if (null === b.stateNode) null !== a && (a.alternate = null, b.alternate = null, b.flags |= 2), Mg(b, c, d), Og(b, c, d, e), d = !0;else if (null === a) {
      var g = b.stateNode,
          h = b.memoizedProps;
      g.props = h;
      var k = g.context,
          l = c.contextType;
      "object" === typeof l && null !== l ? l = vg(l) : (l = Ff(c) ? Df : M.current, l = Ef(b, l));
      var n = c.getDerivedStateFromProps,
          A = "function" === typeof n || "function" === typeof g.getSnapshotBeforeUpdate;
      A || "function" !== typeof g.UNSAFE_componentWillReceiveProps && "function" !== typeof g.componentWillReceiveProps || (h !== d || k !== l) && Ng(b, g, d, l);
      wg = !1;
      var p = b.memoizedState;
      g.state = p;
      Cg(b, d, g, e);
      k = b.memoizedState;
      h !== d || p !== k || N.current || wg ? ("function" === typeof n && (Gg(b, c, n, d), k = b.memoizedState), (h = wg || Lg(b, c, h, d, p, k, l)) ? (A || "function" !== typeof g.UNSAFE_componentWillMount && "function" !== typeof g.componentWillMount || ("function" === typeof g.componentWillMount && g.componentWillMount(), "function" === typeof g.UNSAFE_componentWillMount && g.UNSAFE_componentWillMount()), "function" === typeof g.componentDidMount && (b.flags |= 4)) : ("function" === typeof g.componentDidMount && (b.flags |= 4), b.memoizedProps = d, b.memoizedState = k), g.props = d, g.state = k, g.context = l, d = h) : ("function" === typeof g.componentDidMount && (b.flags |= 4), d = !1);
    } else {
      g = b.stateNode;
      yg(a, b);
      h = b.memoizedProps;
      l = b.type === b.elementType ? h : lg(b.type, h);
      g.props = l;
      A = b.pendingProps;
      p = g.context;
      k = c.contextType;
      "object" === typeof k && null !== k ? k = vg(k) : (k = Ff(c) ? Df : M.current, k = Ef(b, k));
      var C = c.getDerivedStateFromProps;
      (n = "function" === typeof C || "function" === typeof g.getSnapshotBeforeUpdate) || "function" !== typeof g.UNSAFE_componentWillReceiveProps && "function" !== typeof g.componentWillReceiveProps || (h !== A || p !== k) && Ng(b, g, d, k);
      wg = !1;
      p = b.memoizedState;
      g.state = p;
      Cg(b, d, g, e);
      var x = b.memoizedState;
      h !== A || p !== x || N.current || wg ? ("function" === typeof C && (Gg(b, c, C, d), x = b.memoizedState), (l = wg || Lg(b, c, l, d, p, x, k)) ? (n || "function" !== typeof g.UNSAFE_componentWillUpdate && "function" !== typeof g.componentWillUpdate || ("function" === typeof g.componentWillUpdate && g.componentWillUpdate(d, x, k), "function" === typeof g.UNSAFE_componentWillUpdate && g.UNSAFE_componentWillUpdate(d, x, k)), "function" === typeof g.componentDidUpdate && (b.flags |= 4), "function" === typeof g.getSnapshotBeforeUpdate && (b.flags |= 256)) : ("function" !== typeof g.componentDidUpdate || h === a.memoizedProps && p === a.memoizedState || (b.flags |= 4), "function" !== typeof g.getSnapshotBeforeUpdate || h === a.memoizedProps && p === a.memoizedState || (b.flags |= 256), b.memoizedProps = d, b.memoizedState = x), g.props = d, g.state = x, g.context = k, d = l) : ("function" !== typeof g.componentDidUpdate || h === a.memoizedProps && p === a.memoizedState || (b.flags |= 4), "function" !== typeof g.getSnapshotBeforeUpdate || h === a.memoizedProps && p === a.memoizedState || (b.flags |= 256), d = !1);
    }
    return qi(a, b, c, d, f, e);
  }

  function qi(a, b, c, d, e, f) {
    oi(a, b);
    var g = 0 !== (b.flags & 64);
    if (!d && !g) return e && Kf(b, c, !1), hi(a, b, f);
    d = b.stateNode;
    ei.current = b;
    var h = g && "function" !== typeof c.getDerivedStateFromError ? null : d.render();
    b.flags |= 1;
    null !== a && g ? (b.child = Yg(b, a.child, null, f), b.child = Yg(b, null, h, f)) : fi(a, b, h, f);
    b.memoizedState = d.state;
    e && Kf(b, c, !0);
    return b.child;
  }

  function ri(a) {
    var b = a.stateNode;
    b.pendingContext ? Hf(a, b.pendingContext, b.pendingContext !== b.context) : b.context && Hf(a, b.context, !1);
    eh(a, b.containerInfo);
  }

  var si = {
    dehydrated: null,
    retryLane: 0
  };

  function ti(a, b, c) {
    var d = b.pendingProps,
        e = P.current,
        f = !1,
        g;
    (g = 0 !== (b.flags & 64)) || (g = null !== a && null === a.memoizedState ? !1 : 0 !== (e & 2));
    g ? (f = !0, b.flags &= -65) : null !== a && null === a.memoizedState || void 0 === d.fallback || !0 === d.unstable_avoidThisFallback || (e |= 1);
    I(P, e & 1);

    if (null === a) {
      void 0 !== d.fallback && ph(b);
      a = d.children;
      e = d.fallback;
      if (f) return a = ui(b, a, e, c), b.child.memoizedState = {
        baseLanes: c
      }, b.memoizedState = si, a;
      if ("number" === typeof d.unstable_expectedLoadTime) return a = ui(b, a, e, c), b.child.memoizedState = {
        baseLanes: c
      }, b.memoizedState = si, b.lanes = 33554432, a;
      c = vi({
        mode: "visible",
        children: a
      }, b.mode, c, null);
      c.return = b;
      return b.child = c;
    }

    if (null !== a.memoizedState) {
      if (f) return d = wi(a, b, d.children, d.fallback, c), f = b.child, e = a.child.memoizedState, f.memoizedState = null === e ? {
        baseLanes: c
      } : {
        baseLanes: e.baseLanes | c
      }, f.childLanes = a.childLanes & ~c, b.memoizedState = si, d;
      c = xi(a, b, d.children, c);
      b.memoizedState = null;
      return c;
    }

    if (f) return d = wi(a, b, d.children, d.fallback, c), f = b.child, e = a.child.memoizedState, f.memoizedState = null === e ? {
      baseLanes: c
    } : {
      baseLanes: e.baseLanes | c
    }, f.childLanes = a.childLanes & ~c, b.memoizedState = si, d;
    c = xi(a, b, d.children, c);
    b.memoizedState = null;
    return c;
  }

  function ui(a, b, c, d) {
    var e = a.mode,
        f = a.child;
    b = {
      mode: "hidden",
      children: b
    };
    0 === (e & 2) && null !== f ? (f.childLanes = 0, f.pendingProps = b) : f = vi(b, e, 0, null);
    c = Xg(c, e, d, null);
    f.return = a;
    c.return = a;
    f.sibling = c;
    a.child = f;
    return c;
  }

  function xi(a, b, c, d) {
    var e = a.child;
    a = e.sibling;
    c = Tg(e, {
      mode: "visible",
      children: c
    });
    0 === (b.mode & 2) && (c.lanes = d);
    c.return = b;
    c.sibling = null;
    null !== a && (a.nextEffect = null, a.flags = 8, b.firstEffect = b.lastEffect = a);
    return b.child = c;
  }

  function wi(a, b, c, d, e) {
    var f = b.mode,
        g = a.child;
    a = g.sibling;
    var h = {
      mode: "hidden",
      children: c
    };
    0 === (f & 2) && b.child !== g ? (c = b.child, c.childLanes = 0, c.pendingProps = h, g = c.lastEffect, null !== g ? (b.firstEffect = c.firstEffect, b.lastEffect = g, g.nextEffect = null) : b.firstEffect = b.lastEffect = null) : c = Tg(g, h);
    null !== a ? d = Tg(a, d) : (d = Xg(d, f, e, null), d.flags |= 2);
    d.return = b;
    c.return = b;
    c.sibling = d;
    b.child = c;
    return d;
  }

  function yi(a, b) {
    a.lanes |= b;
    var c = a.alternate;
    null !== c && (c.lanes |= b);
    sg(a.return, b);
  }

  function zi(a, b, c, d, e, f) {
    var g = a.memoizedState;
    null === g ? a.memoizedState = {
      isBackwards: b,
      rendering: null,
      renderingStartTime: 0,
      last: d,
      tail: c,
      tailMode: e,
      lastEffect: f
    } : (g.isBackwards = b, g.rendering = null, g.renderingStartTime = 0, g.last = d, g.tail = c, g.tailMode = e, g.lastEffect = f);
  }

  function Ai(a, b, c) {
    var d = b.pendingProps,
        e = d.revealOrder,
        f = d.tail;
    fi(a, b, d.children, c);
    d = P.current;
    if (0 !== (d & 2)) d = d & 1 | 2, b.flags |= 64;else {
      if (null !== a && 0 !== (a.flags & 64)) a: for (a = b.child; null !== a;) {
        if (13 === a.tag) null !== a.memoizedState && yi(a, c);else if (19 === a.tag) yi(a, c);else if (null !== a.child) {
          a.child.return = a;
          a = a.child;
          continue;
        }
        if (a === b) break a;

        for (; null === a.sibling;) {
          if (null === a.return || a.return === b) break a;
          a = a.return;
        }

        a.sibling.return = a.return;
        a = a.sibling;
      }
      d &= 1;
    }
    I(P, d);
    if (0 === (b.mode & 2)) b.memoizedState = null;else switch (e) {
      case "forwards":
        c = b.child;

        for (e = null; null !== c;) a = c.alternate, null !== a && null === ih(a) && (e = c), c = c.sibling;

        c = e;
        null === c ? (e = b.child, b.child = null) : (e = c.sibling, c.sibling = null);
        zi(b, !1, e, c, f, b.lastEffect);
        break;

      case "backwards":
        c = null;
        e = b.child;

        for (b.child = null; null !== e;) {
          a = e.alternate;

          if (null !== a && null === ih(a)) {
            b.child = e;
            break;
          }

          a = e.sibling;
          e.sibling = c;
          c = e;
          e = a;
        }

        zi(b, !0, c, null, f, b.lastEffect);
        break;

      case "together":
        zi(b, !1, null, null, void 0, b.lastEffect);
        break;

      default:
        b.memoizedState = null;
    }
    return b.child;
  }

  function hi(a, b, c) {
    null !== a && (b.dependencies = a.dependencies);
    Dg |= b.lanes;

    if (0 !== (c & b.childLanes)) {
      if (null !== a && b.child !== a.child) throw Error(y$1(153));

      if (null !== b.child) {
        a = b.child;
        c = Tg(a, a.pendingProps);
        b.child = c;

        for (c.return = b; null !== a.sibling;) a = a.sibling, c = c.sibling = Tg(a, a.pendingProps), c.return = b;

        c.sibling = null;
      }

      return b.child;
    }

    return null;
  }

  var Bi, Ci, Di, Ei;

  Bi = function (a, b) {
    for (var c = b.child; null !== c;) {
      if (5 === c.tag || 6 === c.tag) a.appendChild(c.stateNode);else if (4 !== c.tag && null !== c.child) {
        c.child.return = c;
        c = c.child;
        continue;
      }
      if (c === b) break;

      for (; null === c.sibling;) {
        if (null === c.return || c.return === b) return;
        c = c.return;
      }

      c.sibling.return = c.return;
      c = c.sibling;
    }
  };

  Ci = function () {};

  Di = function (a, b, c, d) {
    var e = a.memoizedProps;

    if (e !== d) {
      a = b.stateNode;
      dh(ah.current);
      var f = null;

      switch (c) {
        case "input":
          e = Ya(a, e);
          d = Ya(a, d);
          f = [];
          break;

        case "option":
          e = eb(a, e);
          d = eb(a, d);
          f = [];
          break;

        case "select":
          e = objectAssign({}, e, {
            value: void 0
          });
          d = objectAssign({}, d, {
            value: void 0
          });
          f = [];
          break;

        case "textarea":
          e = gb(a, e);
          d = gb(a, d);
          f = [];
          break;

        default:
          "function" !== typeof e.onClick && "function" === typeof d.onClick && (a.onclick = jf);
      }

      vb(c, d);
      var g;
      c = null;

      for (l in e) if (!d.hasOwnProperty(l) && e.hasOwnProperty(l) && null != e[l]) if ("style" === l) {
        var h = e[l];

        for (g in h) h.hasOwnProperty(g) && (c || (c = {}), c[g] = "");
      } else "dangerouslySetInnerHTML" !== l && "children" !== l && "suppressContentEditableWarning" !== l && "suppressHydrationWarning" !== l && "autoFocus" !== l && (ca.hasOwnProperty(l) ? f || (f = []) : (f = f || []).push(l, null));

      for (l in d) {
        var k = d[l];
        h = null != e ? e[l] : void 0;
        if (d.hasOwnProperty(l) && k !== h && (null != k || null != h)) if ("style" === l) {
          if (h) {
            for (g in h) !h.hasOwnProperty(g) || k && k.hasOwnProperty(g) || (c || (c = {}), c[g] = "");

            for (g in k) k.hasOwnProperty(g) && h[g] !== k[g] && (c || (c = {}), c[g] = k[g]);
          } else c || (f || (f = []), f.push(l, c)), c = k;
        } else "dangerouslySetInnerHTML" === l ? (k = k ? k.__html : void 0, h = h ? h.__html : void 0, null != k && h !== k && (f = f || []).push(l, k)) : "children" === l ? "string" !== typeof k && "number" !== typeof k || (f = f || []).push(l, "" + k) : "suppressContentEditableWarning" !== l && "suppressHydrationWarning" !== l && (ca.hasOwnProperty(l) ? (null != k && "onScroll" === l && G("scroll", a), f || h === k || (f = [])) : "object" === typeof k && null !== k && k.$$typeof === Ga ? k.toString() : (f = f || []).push(l, k));
      }

      c && (f = f || []).push("style", c);
      var l = f;
      if (b.updateQueue = l) b.flags |= 4;
    }
  };

  Ei = function (a, b, c, d) {
    c !== d && (b.flags |= 4);
  };

  function Fi(a, b) {
    if (!lh) switch (a.tailMode) {
      case "hidden":
        b = a.tail;

        for (var c = null; null !== b;) null !== b.alternate && (c = b), b = b.sibling;

        null === c ? a.tail = null : c.sibling = null;
        break;

      case "collapsed":
        c = a.tail;

        for (var d = null; null !== c;) null !== c.alternate && (d = c), c = c.sibling;

        null === d ? b || null === a.tail ? a.tail = null : a.tail.sibling = null : d.sibling = null;
    }
  }

  function Gi(a, b, c) {
    var d = b.pendingProps;

    switch (b.tag) {
      case 2:
      case 16:
      case 15:
      case 0:
      case 11:
      case 7:
      case 8:
      case 12:
      case 9:
      case 14:
        return null;

      case 1:
        return Ff(b.type) && Gf(), null;

      case 3:
        fh();
        H(N);
        H(M);
        uh();
        d = b.stateNode;
        d.pendingContext && (d.context = d.pendingContext, d.pendingContext = null);
        if (null === a || null === a.child) rh(b) ? b.flags |= 4 : d.hydrate || (b.flags |= 256);
        Ci(b);
        return null;

      case 5:
        hh(b);
        var e = dh(ch.current);
        c = b.type;
        if (null !== a && null != b.stateNode) Di(a, b, c, d, e), a.ref !== b.ref && (b.flags |= 128);else {
          if (!d) {
            if (null === b.stateNode) throw Error(y$1(166));
            return null;
          }

          a = dh(ah.current);

          if (rh(b)) {
            d = b.stateNode;
            c = b.type;
            var f = b.memoizedProps;
            d[wf] = b;
            d[xf] = f;

            switch (c) {
              case "dialog":
                G("cancel", d);
                G("close", d);
                break;

              case "iframe":
              case "object":
              case "embed":
                G("load", d);
                break;

              case "video":
              case "audio":
                for (a = 0; a < Xe.length; a++) G(Xe[a], d);

                break;

              case "source":
                G("error", d);
                break;

              case "img":
              case "image":
              case "link":
                G("error", d);
                G("load", d);
                break;

              case "details":
                G("toggle", d);
                break;

              case "input":
                Za(d, f);
                G("invalid", d);
                break;

              case "select":
                d._wrapperState = {
                  wasMultiple: !!f.multiple
                };
                G("invalid", d);
                break;

              case "textarea":
                hb(d, f), G("invalid", d);
            }

            vb(c, f);
            a = null;

            for (var g in f) f.hasOwnProperty(g) && (e = f[g], "children" === g ? "string" === typeof e ? d.textContent !== e && (a = ["children", e]) : "number" === typeof e && d.textContent !== "" + e && (a = ["children", "" + e]) : ca.hasOwnProperty(g) && null != e && "onScroll" === g && G("scroll", d));

            switch (c) {
              case "input":
                Va(d);
                cb(d, f, !0);
                break;

              case "textarea":
                Va(d);
                jb(d);
                break;

              case "select":
              case "option":
                break;

              default:
                "function" === typeof f.onClick && (d.onclick = jf);
            }

            d = a;
            b.updateQueue = d;
            null !== d && (b.flags |= 4);
          } else {
            g = 9 === e.nodeType ? e : e.ownerDocument;
            a === kb.html && (a = lb(c));
            a === kb.html ? "script" === c ? (a = g.createElement("div"), a.innerText = "<script>\x3c/script>", a = a.removeChild(a.firstChild)) : "string" === typeof d.is ? a = g.createElement(c, {
              is: d.is
            }) : (a = g.createElement(c), "select" === c && (g = a, d.multiple ? g.multiple = !0 : d.size && (g.size = d.size))) : a = g.createElementNS(a, c);
            a[wf] = b;
            a[xf] = d;
            Bi(a, b, !1, !1);
            b.stateNode = a;
            g = wb(c, d);

            switch (c) {
              case "dialog":
                G("cancel", a);
                G("close", a);
                e = d;
                break;

              case "iframe":
              case "object":
              case "embed":
                G("load", a);
                e = d;
                break;

              case "video":
              case "audio":
                for (e = 0; e < Xe.length; e++) G(Xe[e], a);

                e = d;
                break;

              case "source":
                G("error", a);
                e = d;
                break;

              case "img":
              case "image":
              case "link":
                G("error", a);
                G("load", a);
                e = d;
                break;

              case "details":
                G("toggle", a);
                e = d;
                break;

              case "input":
                Za(a, d);
                e = Ya(a, d);
                G("invalid", a);
                break;

              case "option":
                e = eb(a, d);
                break;

              case "select":
                a._wrapperState = {
                  wasMultiple: !!d.multiple
                };
                e = objectAssign({}, d, {
                  value: void 0
                });
                G("invalid", a);
                break;

              case "textarea":
                hb(a, d);
                e = gb(a, d);
                G("invalid", a);
                break;

              default:
                e = d;
            }

            vb(c, e);
            var h = e;

            for (f in h) if (h.hasOwnProperty(f)) {
              var k = h[f];
              "style" === f ? tb(a, k) : "dangerouslySetInnerHTML" === f ? (k = k ? k.__html : void 0, null != k && ob(a, k)) : "children" === f ? "string" === typeof k ? ("textarea" !== c || "" !== k) && pb(a, k) : "number" === typeof k && pb(a, "" + k) : "suppressContentEditableWarning" !== f && "suppressHydrationWarning" !== f && "autoFocus" !== f && (ca.hasOwnProperty(f) ? null != k && "onScroll" === f && G("scroll", a) : null != k && qa(a, f, k, g));
            }

            switch (c) {
              case "input":
                Va(a);
                cb(a, d, !1);
                break;

              case "textarea":
                Va(a);
                jb(a);
                break;

              case "option":
                null != d.value && a.setAttribute("value", "" + Sa(d.value));
                break;

              case "select":
                a.multiple = !!d.multiple;
                f = d.value;
                null != f ? fb(a, !!d.multiple, f, !1) : null != d.defaultValue && fb(a, !!d.multiple, d.defaultValue, !0);
                break;

              default:
                "function" === typeof e.onClick && (a.onclick = jf);
            }

            mf(c, d) && (b.flags |= 4);
          }

          null !== b.ref && (b.flags |= 128);
        }
        return null;

      case 6:
        if (a && null != b.stateNode) Ei(a, b, a.memoizedProps, d);else {
          if ("string" !== typeof d && null === b.stateNode) throw Error(y$1(166));
          c = dh(ch.current);
          dh(ah.current);
          rh(b) ? (d = b.stateNode, c = b.memoizedProps, d[wf] = b, d.nodeValue !== c && (b.flags |= 4)) : (d = (9 === c.nodeType ? c : c.ownerDocument).createTextNode(d), d[wf] = b, b.stateNode = d);
        }
        return null;

      case 13:
        H(P);
        d = b.memoizedState;
        if (0 !== (b.flags & 64)) return b.lanes = c, b;
        d = null !== d;
        c = !1;
        null === a ? void 0 !== b.memoizedProps.fallback && rh(b) : c = null !== a.memoizedState;
        if (d && !c && 0 !== (b.mode & 2)) if (null === a && !0 !== b.memoizedProps.unstable_avoidThisFallback || 0 !== (P.current & 1)) 0 === V && (V = 3);else {
          if (0 === V || 3 === V) V = 4;
          null === U || 0 === (Dg & 134217727) && 0 === (Hi & 134217727) || Ii(U, W);
        }
        if (d || c) b.flags |= 4;
        return null;

      case 4:
        return fh(), Ci(b), null === a && cf(b.stateNode.containerInfo), null;

      case 10:
        return rg(b), null;

      case 17:
        return Ff(b.type) && Gf(), null;

      case 19:
        H(P);
        d = b.memoizedState;
        if (null === d) return null;
        f = 0 !== (b.flags & 64);
        g = d.rendering;
        if (null === g) {
          if (f) Fi(d, !1);else {
            if (0 !== V || null !== a && 0 !== (a.flags & 64)) for (a = b.child; null !== a;) {
              g = ih(a);

              if (null !== g) {
                b.flags |= 64;
                Fi(d, !1);
                f = g.updateQueue;
                null !== f && (b.updateQueue = f, b.flags |= 4);
                null === d.lastEffect && (b.firstEffect = null);
                b.lastEffect = d.lastEffect;
                d = c;

                for (c = b.child; null !== c;) f = c, a = d, f.flags &= 2, f.nextEffect = null, f.firstEffect = null, f.lastEffect = null, g = f.alternate, null === g ? (f.childLanes = 0, f.lanes = a, f.child = null, f.memoizedProps = null, f.memoizedState = null, f.updateQueue = null, f.dependencies = null, f.stateNode = null) : (f.childLanes = g.childLanes, f.lanes = g.lanes, f.child = g.child, f.memoizedProps = g.memoizedProps, f.memoizedState = g.memoizedState, f.updateQueue = g.updateQueue, f.type = g.type, a = g.dependencies, f.dependencies = null === a ? null : {
                  lanes: a.lanes,
                  firstContext: a.firstContext
                }), c = c.sibling;

                I(P, P.current & 1 | 2);
                return b.child;
              }

              a = a.sibling;
            }
            null !== d.tail && O() > Ji && (b.flags |= 64, f = !0, Fi(d, !1), b.lanes = 33554432);
          }
        } else {
          if (!f) if (a = ih(g), null !== a) {
            if (b.flags |= 64, f = !0, c = a.updateQueue, null !== c && (b.updateQueue = c, b.flags |= 4), Fi(d, !0), null === d.tail && "hidden" === d.tailMode && !g.alternate && !lh) return b = b.lastEffect = d.lastEffect, null !== b && (b.nextEffect = null), null;
          } else 2 * O() - d.renderingStartTime > Ji && 1073741824 !== c && (b.flags |= 64, f = !0, Fi(d, !1), b.lanes = 33554432);
          d.isBackwards ? (g.sibling = b.child, b.child = g) : (c = d.last, null !== c ? c.sibling = g : b.child = g, d.last = g);
        }
        return null !== d.tail ? (c = d.tail, d.rendering = c, d.tail = c.sibling, d.lastEffect = b.lastEffect, d.renderingStartTime = O(), c.sibling = null, b = P.current, I(P, f ? b & 1 | 2 : b & 1), c) : null;

      case 23:
      case 24:
        return Ki(), null !== a && null !== a.memoizedState !== (null !== b.memoizedState) && "unstable-defer-without-hiding" !== d.mode && (b.flags |= 4), null;
    }

    throw Error(y$1(156, b.tag));
  }

  function Li(a) {
    switch (a.tag) {
      case 1:
        Ff(a.type) && Gf();
        var b = a.flags;
        return b & 4096 ? (a.flags = b & -4097 | 64, a) : null;

      case 3:
        fh();
        H(N);
        H(M);
        uh();
        b = a.flags;
        if (0 !== (b & 64)) throw Error(y$1(285));
        a.flags = b & -4097 | 64;
        return a;

      case 5:
        return hh(a), null;

      case 13:
        return H(P), b = a.flags, b & 4096 ? (a.flags = b & -4097 | 64, a) : null;

      case 19:
        return H(P), null;

      case 4:
        return fh(), null;

      case 10:
        return rg(a), null;

      case 23:
      case 24:
        return Ki(), null;

      default:
        return null;
    }
  }

  function Mi(a, b) {
    try {
      var c = "",
          d = b;

      do c += Qa(d), d = d.return; while (d);

      var e = c;
    } catch (f) {
      e = "\nError generating stack: " + f.message + "\n" + f.stack;
    }

    return {
      value: a,
      source: b,
      stack: e
    };
  }

  function Ni(a, b) {
    try {
      console.error(b.value);
    } catch (c) {
      setTimeout(function () {
        throw c;
      });
    }
  }

  var Oi = "function" === typeof WeakMap ? WeakMap : Map;

  function Pi(a, b, c) {
    c = zg(-1, c);
    c.tag = 3;
    c.payload = {
      element: null
    };
    var d = b.value;

    c.callback = function () {
      Qi || (Qi = !0, Ri = d);
      Ni(a, b);
    };

    return c;
  }

  function Si(a, b, c) {
    c = zg(-1, c);
    c.tag = 3;
    var d = a.type.getDerivedStateFromError;

    if ("function" === typeof d) {
      var e = b.value;

      c.payload = function () {
        Ni(a, b);
        return d(e);
      };
    }

    var f = a.stateNode;
    null !== f && "function" === typeof f.componentDidCatch && (c.callback = function () {
      "function" !== typeof d && (null === Ti ? Ti = new Set([this]) : Ti.add(this), Ni(a, b));
      var c = b.stack;
      this.componentDidCatch(b.value, {
        componentStack: null !== c ? c : ""
      });
    });
    return c;
  }

  var Ui = "function" === typeof WeakSet ? WeakSet : Set;

  function Vi(a) {
    var b = a.ref;
    if (null !== b) if ("function" === typeof b) try {
      b(null);
    } catch (c) {
      Wi(a, c);
    } else b.current = null;
  }

  function Xi(a, b) {
    switch (b.tag) {
      case 0:
      case 11:
      case 15:
      case 22:
        return;

      case 1:
        if (b.flags & 256 && null !== a) {
          var c = a.memoizedProps,
              d = a.memoizedState;
          a = b.stateNode;
          b = a.getSnapshotBeforeUpdate(b.elementType === b.type ? c : lg(b.type, c), d);
          a.__reactInternalSnapshotBeforeUpdate = b;
        }

        return;

      case 3:
        b.flags & 256 && qf(b.stateNode.containerInfo);
        return;

      case 5:
      case 6:
      case 4:
      case 17:
        return;
    }

    throw Error(y$1(163));
  }

  function Yi(a, b, c) {
    switch (c.tag) {
      case 0:
      case 11:
      case 15:
      case 22:
        b = c.updateQueue;
        b = null !== b ? b.lastEffect : null;

        if (null !== b) {
          a = b = b.next;

          do {
            if (3 === (a.tag & 3)) {
              var d = a.create;
              a.destroy = d();
            }

            a = a.next;
          } while (a !== b);
        }

        b = c.updateQueue;
        b = null !== b ? b.lastEffect : null;

        if (null !== b) {
          a = b = b.next;

          do {
            var e = a;
            d = e.next;
            e = e.tag;
            0 !== (e & 4) && 0 !== (e & 1) && (Zi(c, a), $i(c, a));
            a = d;
          } while (a !== b);
        }

        return;

      case 1:
        a = c.stateNode;
        c.flags & 4 && (null === b ? a.componentDidMount() : (d = c.elementType === c.type ? b.memoizedProps : lg(c.type, b.memoizedProps), a.componentDidUpdate(d, b.memoizedState, a.__reactInternalSnapshotBeforeUpdate)));
        b = c.updateQueue;
        null !== b && Eg(c, b, a);
        return;

      case 3:
        b = c.updateQueue;

        if (null !== b) {
          a = null;
          if (null !== c.child) switch (c.child.tag) {
            case 5:
              a = c.child.stateNode;
              break;

            case 1:
              a = c.child.stateNode;
          }
          Eg(c, b, a);
        }

        return;

      case 5:
        a = c.stateNode;
        null === b && c.flags & 4 && mf(c.type, c.memoizedProps) && a.focus();
        return;

      case 6:
        return;

      case 4:
        return;

      case 12:
        return;

      case 13:
        null === c.memoizedState && (c = c.alternate, null !== c && (c = c.memoizedState, null !== c && (c = c.dehydrated, null !== c && Cc(c))));
        return;

      case 19:
      case 17:
      case 20:
      case 21:
      case 23:
      case 24:
        return;
    }

    throw Error(y$1(163));
  }

  function aj(a, b) {
    for (var c = a;;) {
      if (5 === c.tag) {
        var d = c.stateNode;
        if (b) d = d.style, "function" === typeof d.setProperty ? d.setProperty("display", "none", "important") : d.display = "none";else {
          d = c.stateNode;
          var e = c.memoizedProps.style;
          e = void 0 !== e && null !== e && e.hasOwnProperty("display") ? e.display : null;
          d.style.display = sb("display", e);
        }
      } else if (6 === c.tag) c.stateNode.nodeValue = b ? "" : c.memoizedProps;else if ((23 !== c.tag && 24 !== c.tag || null === c.memoizedState || c === a) && null !== c.child) {
        c.child.return = c;
        c = c.child;
        continue;
      }

      if (c === a) break;

      for (; null === c.sibling;) {
        if (null === c.return || c.return === a) return;
        c = c.return;
      }

      c.sibling.return = c.return;
      c = c.sibling;
    }
  }

  function bj(a, b) {
    if (Mf && "function" === typeof Mf.onCommitFiberUnmount) try {
      Mf.onCommitFiberUnmount(Lf, b);
    } catch (f) {}

    switch (b.tag) {
      case 0:
      case 11:
      case 14:
      case 15:
      case 22:
        a = b.updateQueue;

        if (null !== a && (a = a.lastEffect, null !== a)) {
          var c = a = a.next;

          do {
            var d = c,
                e = d.destroy;
            d = d.tag;
            if (void 0 !== e) if (0 !== (d & 4)) Zi(b, c);else {
              d = b;

              try {
                e();
              } catch (f) {
                Wi(d, f);
              }
            }
            c = c.next;
          } while (c !== a);
        }

        break;

      case 1:
        Vi(b);
        a = b.stateNode;
        if ("function" === typeof a.componentWillUnmount) try {
          a.props = b.memoizedProps, a.state = b.memoizedState, a.componentWillUnmount();
        } catch (f) {
          Wi(b, f);
        }
        break;

      case 5:
        Vi(b);
        break;

      case 4:
        cj(a, b);
    }
  }

  function dj(a) {
    a.alternate = null;
    a.child = null;
    a.dependencies = null;
    a.firstEffect = null;
    a.lastEffect = null;
    a.memoizedProps = null;
    a.memoizedState = null;
    a.pendingProps = null;
    a.return = null;
    a.updateQueue = null;
  }

  function ej(a) {
    return 5 === a.tag || 3 === a.tag || 4 === a.tag;
  }

  function fj(a) {
    a: {
      for (var b = a.return; null !== b;) {
        if (ej(b)) break a;
        b = b.return;
      }

      throw Error(y$1(160));
    }

    var c = b;
    b = c.stateNode;

    switch (c.tag) {
      case 5:
        var d = !1;
        break;

      case 3:
        b = b.containerInfo;
        d = !0;
        break;

      case 4:
        b = b.containerInfo;
        d = !0;
        break;

      default:
        throw Error(y$1(161));
    }

    c.flags & 16 && (pb(b, ""), c.flags &= -17);

    a: b: for (c = a;;) {
      for (; null === c.sibling;) {
        if (null === c.return || ej(c.return)) {
          c = null;
          break a;
        }

        c = c.return;
      }

      c.sibling.return = c.return;

      for (c = c.sibling; 5 !== c.tag && 6 !== c.tag && 18 !== c.tag;) {
        if (c.flags & 2) continue b;
        if (null === c.child || 4 === c.tag) continue b;else c.child.return = c, c = c.child;
      }

      if (!(c.flags & 2)) {
        c = c.stateNode;
        break a;
      }
    }

    d ? gj(a, c, b) : hj(a, c, b);
  }

  function gj(a, b, c) {
    var d = a.tag,
        e = 5 === d || 6 === d;
    if (e) a = e ? a.stateNode : a.stateNode.instance, b ? 8 === c.nodeType ? c.parentNode.insertBefore(a, b) : c.insertBefore(a, b) : (8 === c.nodeType ? (b = c.parentNode, b.insertBefore(a, c)) : (b = c, b.appendChild(a)), c = c._reactRootContainer, null !== c && void 0 !== c || null !== b.onclick || (b.onclick = jf));else if (4 !== d && (a = a.child, null !== a)) for (gj(a, b, c), a = a.sibling; null !== a;) gj(a, b, c), a = a.sibling;
  }

  function hj(a, b, c) {
    var d = a.tag,
        e = 5 === d || 6 === d;
    if (e) a = e ? a.stateNode : a.stateNode.instance, b ? c.insertBefore(a, b) : c.appendChild(a);else if (4 !== d && (a = a.child, null !== a)) for (hj(a, b, c), a = a.sibling; null !== a;) hj(a, b, c), a = a.sibling;
  }

  function cj(a, b) {
    for (var c = b, d = !1, e, f;;) {
      if (!d) {
        d = c.return;

        a: for (;;) {
          if (null === d) throw Error(y$1(160));
          e = d.stateNode;

          switch (d.tag) {
            case 5:
              f = !1;
              break a;

            case 3:
              e = e.containerInfo;
              f = !0;
              break a;

            case 4:
              e = e.containerInfo;
              f = !0;
              break a;
          }

          d = d.return;
        }

        d = !0;
      }

      if (5 === c.tag || 6 === c.tag) {
        a: for (var g = a, h = c, k = h;;) if (bj(g, k), null !== k.child && 4 !== k.tag) k.child.return = k, k = k.child;else {
          if (k === h) break a;

          for (; null === k.sibling;) {
            if (null === k.return || k.return === h) break a;
            k = k.return;
          }

          k.sibling.return = k.return;
          k = k.sibling;
        }

        f ? (g = e, h = c.stateNode, 8 === g.nodeType ? g.parentNode.removeChild(h) : g.removeChild(h)) : e.removeChild(c.stateNode);
      } else if (4 === c.tag) {
        if (null !== c.child) {
          e = c.stateNode.containerInfo;
          f = !0;
          c.child.return = c;
          c = c.child;
          continue;
        }
      } else if (bj(a, c), null !== c.child) {
        c.child.return = c;
        c = c.child;
        continue;
      }

      if (c === b) break;

      for (; null === c.sibling;) {
        if (null === c.return || c.return === b) return;
        c = c.return;
        4 === c.tag && (d = !1);
      }

      c.sibling.return = c.return;
      c = c.sibling;
    }
  }

  function ij(a, b) {
    switch (b.tag) {
      case 0:
      case 11:
      case 14:
      case 15:
      case 22:
        var c = b.updateQueue;
        c = null !== c ? c.lastEffect : null;

        if (null !== c) {
          var d = c = c.next;

          do 3 === (d.tag & 3) && (a = d.destroy, d.destroy = void 0, void 0 !== a && a()), d = d.next; while (d !== c);
        }

        return;

      case 1:
        return;

      case 5:
        c = b.stateNode;

        if (null != c) {
          d = b.memoizedProps;
          var e = null !== a ? a.memoizedProps : d;
          a = b.type;
          var f = b.updateQueue;
          b.updateQueue = null;

          if (null !== f) {
            c[xf] = d;
            "input" === a && "radio" === d.type && null != d.name && $a(c, d);
            wb(a, e);
            b = wb(a, d);

            for (e = 0; e < f.length; e += 2) {
              var g = f[e],
                  h = f[e + 1];
              "style" === g ? tb(c, h) : "dangerouslySetInnerHTML" === g ? ob(c, h) : "children" === g ? pb(c, h) : qa(c, g, h, b);
            }

            switch (a) {
              case "input":
                ab(c, d);
                break;

              case "textarea":
                ib(c, d);
                break;

              case "select":
                a = c._wrapperState.wasMultiple, c._wrapperState.wasMultiple = !!d.multiple, f = d.value, null != f ? fb(c, !!d.multiple, f, !1) : a !== !!d.multiple && (null != d.defaultValue ? fb(c, !!d.multiple, d.defaultValue, !0) : fb(c, !!d.multiple, d.multiple ? [] : "", !1));
            }
          }
        }

        return;

      case 6:
        if (null === b.stateNode) throw Error(y$1(162));
        b.stateNode.nodeValue = b.memoizedProps;
        return;

      case 3:
        c = b.stateNode;
        c.hydrate && (c.hydrate = !1, Cc(c.containerInfo));
        return;

      case 12:
        return;

      case 13:
        null !== b.memoizedState && (jj = O(), aj(b.child, !0));
        kj(b);
        return;

      case 19:
        kj(b);
        return;

      case 17:
        return;

      case 23:
      case 24:
        aj(b, null !== b.memoizedState);
        return;
    }

    throw Error(y$1(163));
  }

  function kj(a) {
    var b = a.updateQueue;

    if (null !== b) {
      a.updateQueue = null;
      var c = a.stateNode;
      null === c && (c = a.stateNode = new Ui());
      b.forEach(function (b) {
        var d = lj.bind(null, a, b);
        c.has(b) || (c.add(b), b.then(d, d));
      });
    }
  }

  var nj = Math.ceil,
      oj = ra.ReactCurrentDispatcher,
      pj = ra.ReactCurrentOwner,
      X = 0,
      U = null,
      Y = null,
      W = 0,
      qj = 0,
      rj = Bf(0),
      V = 0,
      sj = null,
      tj = 0,
      Dg = 0,
      Hi = 0,
      uj = 0,
      vj = null,
      jj = 0,
      Ji = Infinity;

  function wj() {
    Ji = O() + 500;
  }

  var Z = null,
      Qi = !1,
      Ri = null,
      Ti = null,
      xj = !1,
      yj = null,
      zj = 90,
      Aj = [],
      Bj = [],
      Cj = null,
      Dj = 0,
      Ej = null,
      Fj = -1,
      Gj = 0,
      Hj = 0;

  function Hg() {
    return 0 !== (X & 48) ? O() : -1 !== Fj ? Fj : Fj = O();
  }

  function Ig(a) {
    a = a.mode;
    if (0 === (a & 2)) return 1;
    if (0 === (a & 4)) return 99 === eg() ? 1 : 2;
    0 === Gj && (Gj = tj);

    if (0 !== kg.transition) {
      0 !== Hj && (Hj = null !== vj ? vj.pendingLanes : 0);
      a = Gj;
      var b = 4186112 & ~Hj;
      b &= -b;
      0 === b && (a = 4186112 & ~a, b = a & -a, 0 === b && (b = 8192));
      return b;
    }

    a = eg();
    0 !== (X & 4) && 98 === a ? a = Xc(12, Gj) : (a = Sc(a), a = Xc(a, Gj));
    return a;
  }

  function Jg(a, b, c) {
    if (50 < Dj) throw Dj = 0, Ej = null, Error(y$1(185));
    a = Kj(a, b);
    if (null === a) return null;
    $c(a, b, c);
    a === U && (Hi |= b, 4 === V && Ii(a, W));
    var d = eg();
    1 === b ? 0 !== (X & 8) && 0 === (X & 48) ? Lj(a) : (Mj(a, c), 0 === X && (wj(), ig())) : (0 === (X & 4) || 98 !== d && 99 !== d || (null === Cj ? Cj = new Set([a]) : Cj.add(a)), Mj(a, c));
    vj = a;
  }

  function Kj(a, b) {
    a.lanes |= b;
    var c = a.alternate;
    null !== c && (c.lanes |= b);
    c = a;

    for (a = a.return; null !== a;) a.childLanes |= b, c = a.alternate, null !== c && (c.childLanes |= b), c = a, a = a.return;

    return 3 === c.tag ? c.stateNode : null;
  }

  function Mj(a, b) {
    for (var c = a.callbackNode, d = a.suspendedLanes, e = a.pingedLanes, f = a.expirationTimes, g = a.pendingLanes; 0 < g;) {
      var h = 31 - Vc(g),
          k = 1 << h,
          l = f[h];

      if (-1 === l) {
        if (0 === (k & d) || 0 !== (k & e)) {
          l = b;
          Rc(k);
          var n = F;
          f[h] = 10 <= n ? l + 250 : 6 <= n ? l + 5E3 : -1;
        }
      } else l <= b && (a.expiredLanes |= k);

      g &= ~k;
    }

    d = Uc(a, a === U ? W : 0);
    b = F;
    if (0 === d) null !== c && (c !== Zf && Pf(c), a.callbackNode = null, a.callbackPriority = 0);else {
      if (null !== c) {
        if (a.callbackPriority === b) return;
        c !== Zf && Pf(c);
      }

      15 === b ? (c = Lj.bind(null, a), null === ag ? (ag = [c], bg = Of(Uf, jg)) : ag.push(c), c = Zf) : 14 === b ? c = hg(99, Lj.bind(null, a)) : (c = Tc(b), c = hg(c, Nj.bind(null, a)));
      a.callbackPriority = b;
      a.callbackNode = c;
    }
  }

  function Nj(a) {
    Fj = -1;
    Hj = Gj = 0;
    if (0 !== (X & 48)) throw Error(y$1(327));
    var b = a.callbackNode;
    if (Oj() && a.callbackNode !== b) return null;
    var c = Uc(a, a === U ? W : 0);
    if (0 === c) return null;
    var d = c;
    var e = X;
    X |= 16;
    var f = Pj();
    if (U !== a || W !== d) wj(), Qj(a, d);

    do try {
      Rj();
      break;
    } catch (h) {
      Sj(a, h);
    } while (1);

    qg();
    oj.current = f;
    X = e;
    null !== Y ? d = 0 : (U = null, W = 0, d = V);
    if (0 !== (tj & Hi)) Qj(a, 0);else if (0 !== d) {
      2 === d && (X |= 64, a.hydrate && (a.hydrate = !1, qf(a.containerInfo)), c = Wc(a), 0 !== c && (d = Tj(a, c)));
      if (1 === d) throw b = sj, Qj(a, 0), Ii(a, c), Mj(a, O()), b;
      a.finishedWork = a.current.alternate;
      a.finishedLanes = c;

      switch (d) {
        case 0:
        case 1:
          throw Error(y$1(345));

        case 2:
          Uj(a);
          break;

        case 3:
          Ii(a, c);

          if ((c & 62914560) === c && (d = jj + 500 - O(), 10 < d)) {
            if (0 !== Uc(a, 0)) break;
            e = a.suspendedLanes;

            if ((e & c) !== c) {
              Hg();
              a.pingedLanes |= a.suspendedLanes & e;
              break;
            }

            a.timeoutHandle = of(Uj.bind(null, a), d);
            break;
          }

          Uj(a);
          break;

        case 4:
          Ii(a, c);
          if ((c & 4186112) === c) break;
          d = a.eventTimes;

          for (e = -1; 0 < c;) {
            var g = 31 - Vc(c);
            f = 1 << g;
            g = d[g];
            g > e && (e = g);
            c &= ~f;
          }

          c = e;
          c = O() - c;
          c = (120 > c ? 120 : 480 > c ? 480 : 1080 > c ? 1080 : 1920 > c ? 1920 : 3E3 > c ? 3E3 : 4320 > c ? 4320 : 1960 * nj(c / 1960)) - c;

          if (10 < c) {
            a.timeoutHandle = of(Uj.bind(null, a), c);
            break;
          }

          Uj(a);
          break;

        case 5:
          Uj(a);
          break;

        default:
          throw Error(y$1(329));
      }
    }
    Mj(a, O());
    return a.callbackNode === b ? Nj.bind(null, a) : null;
  }

  function Ii(a, b) {
    b &= ~uj;
    b &= ~Hi;
    a.suspendedLanes |= b;
    a.pingedLanes &= ~b;

    for (a = a.expirationTimes; 0 < b;) {
      var c = 31 - Vc(b),
          d = 1 << c;
      a[c] = -1;
      b &= ~d;
    }
  }

  function Lj(a) {
    if (0 !== (X & 48)) throw Error(y$1(327));
    Oj();

    if (a === U && 0 !== (a.expiredLanes & W)) {
      var b = W;
      var c = Tj(a, b);
      0 !== (tj & Hi) && (b = Uc(a, b), c = Tj(a, b));
    } else b = Uc(a, 0), c = Tj(a, b);

    0 !== a.tag && 2 === c && (X |= 64, a.hydrate && (a.hydrate = !1, qf(a.containerInfo)), b = Wc(a), 0 !== b && (c = Tj(a, b)));
    if (1 === c) throw c = sj, Qj(a, 0), Ii(a, b), Mj(a, O()), c;
    a.finishedWork = a.current.alternate;
    a.finishedLanes = b;
    Uj(a);
    Mj(a, O());
    return null;
  }

  function Vj() {
    if (null !== Cj) {
      var a = Cj;
      Cj = null;
      a.forEach(function (a) {
        a.expiredLanes |= 24 & a.pendingLanes;
        Mj(a, O());
      });
    }

    ig();
  }

  function Wj(a, b) {
    var c = X;
    X |= 1;

    try {
      return a(b);
    } finally {
      X = c, 0 === X && (wj(), ig());
    }
  }

  function Xj(a, b) {
    var c = X;
    X &= -2;
    X |= 8;

    try {
      return a(b);
    } finally {
      X = c, 0 === X && (wj(), ig());
    }
  }

  function ni(a, b) {
    I(rj, qj);
    qj |= b;
    tj |= b;
  }

  function Ki() {
    qj = rj.current;
    H(rj);
  }

  function Qj(a, b) {
    a.finishedWork = null;
    a.finishedLanes = 0;
    var c = a.timeoutHandle;
    -1 !== c && (a.timeoutHandle = -1, pf(c));
    if (null !== Y) for (c = Y.return; null !== c;) {
      var d = c;

      switch (d.tag) {
        case 1:
          d = d.type.childContextTypes;
          null !== d && void 0 !== d && Gf();
          break;

        case 3:
          fh();
          H(N);
          H(M);
          uh();
          break;

        case 5:
          hh(d);
          break;

        case 4:
          fh();
          break;

        case 13:
          H(P);
          break;

        case 19:
          H(P);
          break;

        case 10:
          rg(d);
          break;

        case 23:
        case 24:
          Ki();
      }

      c = c.return;
    }
    U = a;
    Y = Tg(a.current, null);
    W = qj = tj = b;
    V = 0;
    sj = null;
    uj = Hi = Dg = 0;
  }

  function Sj(a, b) {
    do {
      var c = Y;

      try {
        qg();
        vh.current = Gh;

        if (yh) {
          for (var d = R.memoizedState; null !== d;) {
            var e = d.queue;
            null !== e && (e.pending = null);
            d = d.next;
          }

          yh = !1;
        }

        xh = 0;
        T = S = R = null;
        zh = !1;
        pj.current = null;

        if (null === c || null === c.return) {
          V = 1;
          sj = b;
          Y = null;
          break;
        }

        a: {
          var f = a,
              g = c.return,
              h = c,
              k = b;
          b = W;
          h.flags |= 2048;
          h.firstEffect = h.lastEffect = null;

          if (null !== k && "object" === typeof k && "function" === typeof k.then) {
            var l = k;

            if (0 === (h.mode & 2)) {
              var n = h.alternate;
              n ? (h.updateQueue = n.updateQueue, h.memoizedState = n.memoizedState, h.lanes = n.lanes) : (h.updateQueue = null, h.memoizedState = null);
            }

            var A = 0 !== (P.current & 1),
                p = g;

            do {
              var C;

              if (C = 13 === p.tag) {
                var x = p.memoizedState;
                if (null !== x) C = null !== x.dehydrated ? !0 : !1;else {
                  var w = p.memoizedProps;
                  C = void 0 === w.fallback ? !1 : !0 !== w.unstable_avoidThisFallback ? !0 : A ? !1 : !0;
                }
              }

              if (C) {
                var z = p.updateQueue;

                if (null === z) {
                  var u = new Set();
                  u.add(l);
                  p.updateQueue = u;
                } else z.add(l);

                if (0 === (p.mode & 2)) {
                  p.flags |= 64;
                  h.flags |= 16384;
                  h.flags &= -2981;
                  if (1 === h.tag) if (null === h.alternate) h.tag = 17;else {
                    var t = zg(-1, 1);
                    t.tag = 2;
                    Ag(h, t);
                  }
                  h.lanes |= 1;
                  break a;
                }

                k = void 0;
                h = b;
                var q = f.pingCache;
                null === q ? (q = f.pingCache = new Oi(), k = new Set(), q.set(l, k)) : (k = q.get(l), void 0 === k && (k = new Set(), q.set(l, k)));

                if (!k.has(h)) {
                  k.add(h);
                  var v = Yj.bind(null, f, l, h);
                  l.then(v, v);
                }

                p.flags |= 4096;
                p.lanes = b;
                break a;
              }

              p = p.return;
            } while (null !== p);

            k = Error((Ra(h.type) || "A React component") + " suspended while rendering, but no fallback UI was specified.\n\nAdd a <Suspense fallback=...> component higher in the tree to provide a loading indicator or placeholder to display.");
          }

          5 !== V && (V = 2);
          k = Mi(k, h);
          p = g;

          do {
            switch (p.tag) {
              case 3:
                f = k;
                p.flags |= 4096;
                b &= -b;
                p.lanes |= b;
                var J = Pi(p, f, b);
                Bg(p, J);
                break a;

              case 1:
                f = k;
                var K = p.type,
                    Q = p.stateNode;

                if (0 === (p.flags & 64) && ("function" === typeof K.getDerivedStateFromError || null !== Q && "function" === typeof Q.componentDidCatch && (null === Ti || !Ti.has(Q)))) {
                  p.flags |= 4096;
                  b &= -b;
                  p.lanes |= b;
                  var L = Si(p, f, b);
                  Bg(p, L);
                  break a;
                }

            }

            p = p.return;
          } while (null !== p);
        }

        Zj(c);
      } catch (va) {
        b = va;
        Y === c && null !== c && (Y = c = c.return);
        continue;
      }

      break;
    } while (1);
  }

  function Pj() {
    var a = oj.current;
    oj.current = Gh;
    return null === a ? Gh : a;
  }

  function Tj(a, b) {
    var c = X;
    X |= 16;
    var d = Pj();
    U === a && W === b || Qj(a, b);

    do try {
      ak();
      break;
    } catch (e) {
      Sj(a, e);
    } while (1);

    qg();
    X = c;
    oj.current = d;
    if (null !== Y) throw Error(y$1(261));
    U = null;
    W = 0;
    return V;
  }

  function ak() {
    for (; null !== Y;) bk(Y);
  }

  function Rj() {
    for (; null !== Y && !Qf();) bk(Y);
  }

  function bk(a) {
    var b = ck(a.alternate, a, qj);
    a.memoizedProps = a.pendingProps;
    null === b ? Zj(a) : Y = b;
    pj.current = null;
  }

  function Zj(a) {
    var b = a;

    do {
      var c = b.alternate;
      a = b.return;

      if (0 === (b.flags & 2048)) {
        c = Gi(c, b, qj);

        if (null !== c) {
          Y = c;
          return;
        }

        c = b;

        if (24 !== c.tag && 23 !== c.tag || null === c.memoizedState || 0 !== (qj & 1073741824) || 0 === (c.mode & 4)) {
          for (var d = 0, e = c.child; null !== e;) d |= e.lanes | e.childLanes, e = e.sibling;

          c.childLanes = d;
        }

        null !== a && 0 === (a.flags & 2048) && (null === a.firstEffect && (a.firstEffect = b.firstEffect), null !== b.lastEffect && (null !== a.lastEffect && (a.lastEffect.nextEffect = b.firstEffect), a.lastEffect = b.lastEffect), 1 < b.flags && (null !== a.lastEffect ? a.lastEffect.nextEffect = b : a.firstEffect = b, a.lastEffect = b));
      } else {
        c = Li(b);

        if (null !== c) {
          c.flags &= 2047;
          Y = c;
          return;
        }

        null !== a && (a.firstEffect = a.lastEffect = null, a.flags |= 2048);
      }

      b = b.sibling;

      if (null !== b) {
        Y = b;
        return;
      }

      Y = b = a;
    } while (null !== b);

    0 === V && (V = 5);
  }

  function Uj(a) {
    var b = eg();
    gg(99, dk.bind(null, a, b));
    return null;
  }

  function dk(a, b) {
    do Oj(); while (null !== yj);

    if (0 !== (X & 48)) throw Error(y$1(327));
    var c = a.finishedWork;
    if (null === c) return null;
    a.finishedWork = null;
    a.finishedLanes = 0;
    if (c === a.current) throw Error(y$1(177));
    a.callbackNode = null;
    var d = c.lanes | c.childLanes,
        e = d,
        f = a.pendingLanes & ~e;
    a.pendingLanes = e;
    a.suspendedLanes = 0;
    a.pingedLanes = 0;
    a.expiredLanes &= e;
    a.mutableReadLanes &= e;
    a.entangledLanes &= e;
    e = a.entanglements;

    for (var g = a.eventTimes, h = a.expirationTimes; 0 < f;) {
      var k = 31 - Vc(f),
          l = 1 << k;
      e[k] = 0;
      g[k] = -1;
      h[k] = -1;
      f &= ~l;
    }

    null !== Cj && 0 === (d & 24) && Cj.has(a) && Cj.delete(a);
    a === U && (Y = U = null, W = 0);
    1 < c.flags ? null !== c.lastEffect ? (c.lastEffect.nextEffect = c, d = c.firstEffect) : d = c : d = c.firstEffect;

    if (null !== d) {
      e = X;
      X |= 32;
      pj.current = null;
      kf = fd;
      g = Ne();

      if (Oe(g)) {
        if ("selectionStart" in g) h = {
          start: g.selectionStart,
          end: g.selectionEnd
        };else a: if (h = (h = g.ownerDocument) && h.defaultView || window, (l = h.getSelection && h.getSelection()) && 0 !== l.rangeCount) {
          h = l.anchorNode;
          f = l.anchorOffset;
          k = l.focusNode;
          l = l.focusOffset;

          try {
            h.nodeType, k.nodeType;
          } catch (va) {
            h = null;
            break a;
          }

          var n = 0,
              A = -1,
              p = -1,
              C = 0,
              x = 0,
              w = g,
              z = null;

          b: for (;;) {
            for (var u;;) {
              w !== h || 0 !== f && 3 !== w.nodeType || (A = n + f);
              w !== k || 0 !== l && 3 !== w.nodeType || (p = n + l);
              3 === w.nodeType && (n += w.nodeValue.length);
              if (null === (u = w.firstChild)) break;
              z = w;
              w = u;
            }

            for (;;) {
              if (w === g) break b;
              z === h && ++C === f && (A = n);
              z === k && ++x === l && (p = n);
              if (null !== (u = w.nextSibling)) break;
              w = z;
              z = w.parentNode;
            }

            w = u;
          }

          h = -1 === A || -1 === p ? null : {
            start: A,
            end: p
          };
        } else h = null;
        h = h || {
          start: 0,
          end: 0
        };
      } else h = null;

      lf = {
        focusedElem: g,
        selectionRange: h
      };
      fd = !1;
      Z = d;

      do try {
        ek();
      } catch (va) {
        if (null === Z) throw Error(y$1(330));
        Wi(Z, va);
        Z = Z.nextEffect;
      } while (null !== Z);
      Z = d;

      do try {
        for (g = a; null !== Z;) {
          var t = Z.flags;
          t & 16 && pb(Z.stateNode, "");

          if (t & 128) {
            var q = Z.alternate;

            if (null !== q) {
              var v = q.ref;
              null !== v && ("function" === typeof v ? v(null) : v.current = null);
            }
          }

          switch (t & 1038) {
            case 2:
              fj(Z);
              Z.flags &= -3;
              break;

            case 6:
              fj(Z);
              Z.flags &= -3;
              ij(Z.alternate, Z);
              break;

            case 1024:
              Z.flags &= -1025;
              break;

            case 1028:
              Z.flags &= -1025;
              ij(Z.alternate, Z);
              break;

            case 4:
              ij(Z.alternate, Z);
              break;

            case 8:
              h = Z;
              cj(g, h);
              var J = h.alternate;
              dj(h);
              null !== J && dj(J);
          }

          Z = Z.nextEffect;
        }
      } catch (va) {
        if (null === Z) throw Error(y$1(330));
        Wi(Z, va);
        Z = Z.nextEffect;
      } while (null !== Z);

      v = lf;
      q = Ne();
      t = v.focusedElem;
      g = v.selectionRange;

      if (q !== t && t && t.ownerDocument && Me(t.ownerDocument.documentElement, t)) {
        null !== g && Oe(t) && (q = g.start, v = g.end, void 0 === v && (v = q), "selectionStart" in t ? (t.selectionStart = q, t.selectionEnd = Math.min(v, t.value.length)) : (v = (q = t.ownerDocument || document) && q.defaultView || window, v.getSelection && (v = v.getSelection(), h = t.textContent.length, J = Math.min(g.start, h), g = void 0 === g.end ? J : Math.min(g.end, h), !v.extend && J > g && (h = g, g = J, J = h), h = Le(t, J), f = Le(t, g), h && f && (1 !== v.rangeCount || v.anchorNode !== h.node || v.anchorOffset !== h.offset || v.focusNode !== f.node || v.focusOffset !== f.offset) && (q = q.createRange(), q.setStart(h.node, h.offset), v.removeAllRanges(), J > g ? (v.addRange(q), v.extend(f.node, f.offset)) : (q.setEnd(f.node, f.offset), v.addRange(q))))));
        q = [];

        for (v = t; v = v.parentNode;) 1 === v.nodeType && q.push({
          element: v,
          left: v.scrollLeft,
          top: v.scrollTop
        });

        "function" === typeof t.focus && t.focus();

        for (t = 0; t < q.length; t++) v = q[t], v.element.scrollLeft = v.left, v.element.scrollTop = v.top;
      }

      fd = !!kf;
      lf = kf = null;
      a.current = c;
      Z = d;

      do try {
        for (t = a; null !== Z;) {
          var K = Z.flags;
          K & 36 && Yi(t, Z.alternate, Z);

          if (K & 128) {
            q = void 0;
            var Q = Z.ref;

            if (null !== Q) {
              var L = Z.stateNode;

              switch (Z.tag) {
                case 5:
                  q = L;
                  break;

                default:
                  q = L;
              }

              "function" === typeof Q ? Q(q) : Q.current = q;
            }
          }

          Z = Z.nextEffect;
        }
      } catch (va) {
        if (null === Z) throw Error(y$1(330));
        Wi(Z, va);
        Z = Z.nextEffect;
      } while (null !== Z);

      Z = null;
      $f();
      X = e;
    } else a.current = c;

    if (xj) xj = !1, yj = a, zj = b;else for (Z = d; null !== Z;) b = Z.nextEffect, Z.nextEffect = null, Z.flags & 8 && (K = Z, K.sibling = null, K.stateNode = null), Z = b;
    d = a.pendingLanes;
    0 === d && (Ti = null);
    1 === d ? a === Ej ? Dj++ : (Dj = 0, Ej = a) : Dj = 0;
    c = c.stateNode;
    if (Mf && "function" === typeof Mf.onCommitFiberRoot) try {
      Mf.onCommitFiberRoot(Lf, c, void 0, 64 === (c.current.flags & 64));
    } catch (va) {}
    Mj(a, O());
    if (Qi) throw Qi = !1, a = Ri, Ri = null, a;
    if (0 !== (X & 8)) return null;
    ig();
    return null;
  }

  function ek() {
    for (; null !== Z;) {
      var a = Z.alternate;
      var b = Z.flags;
      0 !== (b & 256) && Xi(a, Z);
      0 === (b & 512) || xj || (xj = !0, hg(97, function () {
        Oj();
        return null;
      }));
      Z = Z.nextEffect;
    }
  }

  function Oj() {
    if (90 !== zj) {
      var a = 97 < zj ? 97 : zj;
      zj = 90;
      return gg(a, fk);
    }

    return !1;
  }

  function $i(a, b) {
    Aj.push(b, a);
    xj || (xj = !0, hg(97, function () {
      Oj();
      return null;
    }));
  }

  function Zi(a, b) {
    Bj.push(b, a);
    xj || (xj = !0, hg(97, function () {
      Oj();
      return null;
    }));
  }

  function fk() {
    if (null === yj) return !1;
    var a = yj;
    yj = null;
    if (0 !== (X & 48)) throw Error(y$1(331));
    var b = X;
    X |= 32;
    var c = Bj;
    Bj = [];

    for (var d = 0; d < c.length; d += 2) {
      var e = c[d],
          f = c[d + 1],
          g = e.destroy;
      e.destroy = void 0;
      if ("function" === typeof g) try {
        g();
      } catch (k) {
        if (null === f) throw Error(y$1(330));
        Wi(f, k);
      }
    }

    c = Aj;
    Aj = [];

    for (d = 0; d < c.length; d += 2) {
      e = c[d];
      f = c[d + 1];

      try {
        var h = e.create;
        e.destroy = h();
      } catch (k) {
        if (null === f) throw Error(y$1(330));
        Wi(f, k);
      }
    }

    for (h = a.current.firstEffect; null !== h;) a = h.nextEffect, h.nextEffect = null, h.flags & 8 && (h.sibling = null, h.stateNode = null), h = a;

    X = b;
    ig();
    return !0;
  }

  function gk(a, b, c) {
    b = Mi(c, b);
    b = Pi(a, b, 1);
    Ag(a, b);
    b = Hg();
    a = Kj(a, 1);
    null !== a && ($c(a, 1, b), Mj(a, b));
  }

  function Wi(a, b) {
    if (3 === a.tag) gk(a, a, b);else for (var c = a.return; null !== c;) {
      if (3 === c.tag) {
        gk(c, a, b);
        break;
      } else if (1 === c.tag) {
        var d = c.stateNode;

        if ("function" === typeof c.type.getDerivedStateFromError || "function" === typeof d.componentDidCatch && (null === Ti || !Ti.has(d))) {
          a = Mi(b, a);
          var e = Si(c, a, 1);
          Ag(c, e);
          e = Hg();
          c = Kj(c, 1);
          if (null !== c) $c(c, 1, e), Mj(c, e);else if ("function" === typeof d.componentDidCatch && (null === Ti || !Ti.has(d))) try {
            d.componentDidCatch(b, a);
          } catch (f) {}
          break;
        }
      }

      c = c.return;
    }
  }

  function Yj(a, b, c) {
    var d = a.pingCache;
    null !== d && d.delete(b);
    b = Hg();
    a.pingedLanes |= a.suspendedLanes & c;
    U === a && (W & c) === c && (4 === V || 3 === V && (W & 62914560) === W && 500 > O() - jj ? Qj(a, 0) : uj |= c);
    Mj(a, b);
  }

  function lj(a, b) {
    var c = a.stateNode;
    null !== c && c.delete(b);
    b = 0;
    0 === b && (b = a.mode, 0 === (b & 2) ? b = 1 : 0 === (b & 4) ? b = 99 === eg() ? 1 : 2 : (0 === Gj && (Gj = tj), b = Yc(62914560 & ~Gj), 0 === b && (b = 4194304)));
    c = Hg();
    a = Kj(a, b);
    null !== a && ($c(a, b, c), Mj(a, c));
  }

  var ck;

  ck = function (a, b, c) {
    var d = b.lanes;
    if (null !== a) {
      if (a.memoizedProps !== b.pendingProps || N.current) ug = !0;else if (0 !== (c & d)) ug = 0 !== (a.flags & 16384) ? !0 : !1;else {
        ug = !1;

        switch (b.tag) {
          case 3:
            ri(b);
            sh();
            break;

          case 5:
            gh(b);
            break;

          case 1:
            Ff(b.type) && Jf(b);
            break;

          case 4:
            eh(b, b.stateNode.containerInfo);
            break;

          case 10:
            d = b.memoizedProps.value;
            var e = b.type._context;
            I(mg, e._currentValue);
            e._currentValue = d;
            break;

          case 13:
            if (null !== b.memoizedState) {
              if (0 !== (c & b.child.childLanes)) return ti(a, b, c);
              I(P, P.current & 1);
              b = hi(a, b, c);
              return null !== b ? b.sibling : null;
            }

            I(P, P.current & 1);
            break;

          case 19:
            d = 0 !== (c & b.childLanes);

            if (0 !== (a.flags & 64)) {
              if (d) return Ai(a, b, c);
              b.flags |= 64;
            }

            e = b.memoizedState;
            null !== e && (e.rendering = null, e.tail = null, e.lastEffect = null);
            I(P, P.current);
            if (d) break;else return null;

          case 23:
          case 24:
            return b.lanes = 0, mi(a, b, c);
        }

        return hi(a, b, c);
      }
    } else ug = !1;
    b.lanes = 0;

    switch (b.tag) {
      case 2:
        d = b.type;
        null !== a && (a.alternate = null, b.alternate = null, b.flags |= 2);
        a = b.pendingProps;
        e = Ef(b, M.current);
        tg(b, c);
        e = Ch(null, b, d, a, e, c);
        b.flags |= 1;

        if ("object" === typeof e && null !== e && "function" === typeof e.render && void 0 === e.$$typeof) {
          b.tag = 1;
          b.memoizedState = null;
          b.updateQueue = null;

          if (Ff(d)) {
            var f = !0;
            Jf(b);
          } else f = !1;

          b.memoizedState = null !== e.state && void 0 !== e.state ? e.state : null;
          xg(b);
          var g = d.getDerivedStateFromProps;
          "function" === typeof g && Gg(b, d, g, a);
          e.updater = Kg;
          b.stateNode = e;
          e._reactInternals = b;
          Og(b, d, a, c);
          b = qi(null, b, d, !0, f, c);
        } else b.tag = 0, fi(null, b, e, c), b = b.child;

        return b;

      case 16:
        e = b.elementType;

        a: {
          null !== a && (a.alternate = null, b.alternate = null, b.flags |= 2);
          a = b.pendingProps;
          f = e._init;
          e = f(e._payload);
          b.type = e;
          f = b.tag = hk(e);
          a = lg(e, a);

          switch (f) {
            case 0:
              b = li(null, b, e, a, c);
              break a;

            case 1:
              b = pi(null, b, e, a, c);
              break a;

            case 11:
              b = gi(null, b, e, a, c);
              break a;

            case 14:
              b = ii(null, b, e, lg(e.type, a), d, c);
              break a;
          }

          throw Error(y$1(306, e, ""));
        }

        return b;

      case 0:
        return d = b.type, e = b.pendingProps, e = b.elementType === d ? e : lg(d, e), li(a, b, d, e, c);

      case 1:
        return d = b.type, e = b.pendingProps, e = b.elementType === d ? e : lg(d, e), pi(a, b, d, e, c);

      case 3:
        ri(b);
        d = b.updateQueue;
        if (null === a || null === d) throw Error(y$1(282));
        d = b.pendingProps;
        e = b.memoizedState;
        e = null !== e ? e.element : null;
        yg(a, b);
        Cg(b, d, null, c);
        d = b.memoizedState.element;
        if (d === e) sh(), b = hi(a, b, c);else {
          e = b.stateNode;
          if (f = e.hydrate) kh = rf(b.stateNode.containerInfo.firstChild), jh = b, f = lh = !0;

          if (f) {
            a = e.mutableSourceEagerHydrationData;
            if (null != a) for (e = 0; e < a.length; e += 2) f = a[e], f._workInProgressVersionPrimary = a[e + 1], th.push(f);
            c = Zg(b, null, d, c);

            for (b.child = c; c;) c.flags = c.flags & -3 | 1024, c = c.sibling;
          } else fi(a, b, d, c), sh();

          b = b.child;
        }
        return b;

      case 5:
        return gh(b), null === a && ph(b), d = b.type, e = b.pendingProps, f = null !== a ? a.memoizedProps : null, g = e.children, nf(d, e) ? g = null : null !== f && nf(d, f) && (b.flags |= 16), oi(a, b), fi(a, b, g, c), b.child;

      case 6:
        return null === a && ph(b), null;

      case 13:
        return ti(a, b, c);

      case 4:
        return eh(b, b.stateNode.containerInfo), d = b.pendingProps, null === a ? b.child = Yg(b, null, d, c) : fi(a, b, d, c), b.child;

      case 11:
        return d = b.type, e = b.pendingProps, e = b.elementType === d ? e : lg(d, e), gi(a, b, d, e, c);

      case 7:
        return fi(a, b, b.pendingProps, c), b.child;

      case 8:
        return fi(a, b, b.pendingProps.children, c), b.child;

      case 12:
        return fi(a, b, b.pendingProps.children, c), b.child;

      case 10:
        a: {
          d = b.type._context;
          e = b.pendingProps;
          g = b.memoizedProps;
          f = e.value;
          var h = b.type._context;
          I(mg, h._currentValue);
          h._currentValue = f;
          if (null !== g) if (h = g.value, f = He(h, f) ? 0 : ("function" === typeof d._calculateChangedBits ? d._calculateChangedBits(h, f) : 1073741823) | 0, 0 === f) {
            if (g.children === e.children && !N.current) {
              b = hi(a, b, c);
              break a;
            }
          } else for (h = b.child, null !== h && (h.return = b); null !== h;) {
            var k = h.dependencies;

            if (null !== k) {
              g = h.child;

              for (var l = k.firstContext; null !== l;) {
                if (l.context === d && 0 !== (l.observedBits & f)) {
                  1 === h.tag && (l = zg(-1, c & -c), l.tag = 2, Ag(h, l));
                  h.lanes |= c;
                  l = h.alternate;
                  null !== l && (l.lanes |= c);
                  sg(h.return, c);
                  k.lanes |= c;
                  break;
                }

                l = l.next;
              }
            } else g = 10 === h.tag ? h.type === b.type ? null : h.child : h.child;

            if (null !== g) g.return = h;else for (g = h; null !== g;) {
              if (g === b) {
                g = null;
                break;
              }

              h = g.sibling;

              if (null !== h) {
                h.return = g.return;
                g = h;
                break;
              }

              g = g.return;
            }
            h = g;
          }
          fi(a, b, e.children, c);
          b = b.child;
        }

        return b;

      case 9:
        return e = b.type, f = b.pendingProps, d = f.children, tg(b, c), e = vg(e, f.unstable_observedBits), d = d(e), b.flags |= 1, fi(a, b, d, c), b.child;

      case 14:
        return e = b.type, f = lg(e, b.pendingProps), f = lg(e.type, f), ii(a, b, e, f, d, c);

      case 15:
        return ki(a, b, b.type, b.pendingProps, d, c);

      case 17:
        return d = b.type, e = b.pendingProps, e = b.elementType === d ? e : lg(d, e), null !== a && (a.alternate = null, b.alternate = null, b.flags |= 2), b.tag = 1, Ff(d) ? (a = !0, Jf(b)) : a = !1, tg(b, c), Mg(b, d, e), Og(b, d, e, c), qi(null, b, d, !0, a, c);

      case 19:
        return Ai(a, b, c);

      case 23:
        return mi(a, b, c);

      case 24:
        return mi(a, b, c);
    }

    throw Error(y$1(156, b.tag));
  };

  function ik(a, b, c, d) {
    this.tag = a;
    this.key = c;
    this.sibling = this.child = this.return = this.stateNode = this.type = this.elementType = null;
    this.index = 0;
    this.ref = null;
    this.pendingProps = b;
    this.dependencies = this.memoizedState = this.updateQueue = this.memoizedProps = null;
    this.mode = d;
    this.flags = 0;
    this.lastEffect = this.firstEffect = this.nextEffect = null;
    this.childLanes = this.lanes = 0;
    this.alternate = null;
  }

  function nh(a, b, c, d) {
    return new ik(a, b, c, d);
  }

  function ji(a) {
    a = a.prototype;
    return !(!a || !a.isReactComponent);
  }

  function hk(a) {
    if ("function" === typeof a) return ji(a) ? 1 : 0;

    if (void 0 !== a && null !== a) {
      a = a.$$typeof;
      if (a === Aa) return 11;
      if (a === Da) return 14;
    }

    return 2;
  }

  function Tg(a, b) {
    var c = a.alternate;
    null === c ? (c = nh(a.tag, b, a.key, a.mode), c.elementType = a.elementType, c.type = a.type, c.stateNode = a.stateNode, c.alternate = a, a.alternate = c) : (c.pendingProps = b, c.type = a.type, c.flags = 0, c.nextEffect = null, c.firstEffect = null, c.lastEffect = null);
    c.childLanes = a.childLanes;
    c.lanes = a.lanes;
    c.child = a.child;
    c.memoizedProps = a.memoizedProps;
    c.memoizedState = a.memoizedState;
    c.updateQueue = a.updateQueue;
    b = a.dependencies;
    c.dependencies = null === b ? null : {
      lanes: b.lanes,
      firstContext: b.firstContext
    };
    c.sibling = a.sibling;
    c.index = a.index;
    c.ref = a.ref;
    return c;
  }

  function Vg(a, b, c, d, e, f) {
    var g = 2;
    d = a;
    if ("function" === typeof a) ji(a) && (g = 1);else if ("string" === typeof a) g = 5;else a: switch (a) {
      case ua:
        return Xg(c.children, e, f, b);

      case Ha:
        g = 8;
        e |= 16;
        break;

      case wa:
        g = 8;
        e |= 1;
        break;

      case xa:
        return a = nh(12, c, b, e | 8), a.elementType = xa, a.type = xa, a.lanes = f, a;

      case Ba:
        return a = nh(13, c, b, e), a.type = Ba, a.elementType = Ba, a.lanes = f, a;

      case Ca:
        return a = nh(19, c, b, e), a.elementType = Ca, a.lanes = f, a;

      case Ia:
        return vi(c, e, f, b);

      case Ja:
        return a = nh(24, c, b, e), a.elementType = Ja, a.lanes = f, a;

      default:
        if ("object" === typeof a && null !== a) switch (a.$$typeof) {
          case ya:
            g = 10;
            break a;

          case za:
            g = 9;
            break a;

          case Aa:
            g = 11;
            break a;

          case Da:
            g = 14;
            break a;

          case Ea:
            g = 16;
            d = null;
            break a;

          case Fa:
            g = 22;
            break a;
        }
        throw Error(y$1(130, null == a ? a : typeof a, ""));
    }
    b = nh(g, c, b, e);
    b.elementType = a;
    b.type = d;
    b.lanes = f;
    return b;
  }

  function Xg(a, b, c, d) {
    a = nh(7, a, d, b);
    a.lanes = c;
    return a;
  }

  function vi(a, b, c, d) {
    a = nh(23, a, d, b);
    a.elementType = Ia;
    a.lanes = c;
    return a;
  }

  function Ug(a, b, c) {
    a = nh(6, a, null, b);
    a.lanes = c;
    return a;
  }

  function Wg(a, b, c) {
    b = nh(4, null !== a.children ? a.children : [], a.key, b);
    b.lanes = c;
    b.stateNode = {
      containerInfo: a.containerInfo,
      pendingChildren: null,
      implementation: a.implementation
    };
    return b;
  }

  function jk(a, b, c) {
    this.tag = b;
    this.containerInfo = a;
    this.finishedWork = this.pingCache = this.current = this.pendingChildren = null;
    this.timeoutHandle = -1;
    this.pendingContext = this.context = null;
    this.hydrate = c;
    this.callbackNode = null;
    this.callbackPriority = 0;
    this.eventTimes = Zc(0);
    this.expirationTimes = Zc(-1);
    this.entangledLanes = this.finishedLanes = this.mutableReadLanes = this.expiredLanes = this.pingedLanes = this.suspendedLanes = this.pendingLanes = 0;
    this.entanglements = Zc(0);
    this.mutableSourceEagerHydrationData = null;
  }

  function kk(a, b, c) {
    var d = 3 < arguments.length && void 0 !== arguments[3] ? arguments[3] : null;
    return {
      $$typeof: ta,
      key: null == d ? null : "" + d,
      children: a,
      containerInfo: b,
      implementation: c
    };
  }

  function lk(a, b, c, d) {
    var e = b.current,
        f = Hg(),
        g = Ig(e);

    a: if (c) {
      c = c._reactInternals;

      b: {
        if (Zb(c) !== c || 1 !== c.tag) throw Error(y$1(170));
        var h = c;

        do {
          switch (h.tag) {
            case 3:
              h = h.stateNode.context;
              break b;

            case 1:
              if (Ff(h.type)) {
                h = h.stateNode.__reactInternalMemoizedMergedChildContext;
                break b;
              }

          }

          h = h.return;
        } while (null !== h);

        throw Error(y$1(171));
      }

      if (1 === c.tag) {
        var k = c.type;

        if (Ff(k)) {
          c = If(c, k, h);
          break a;
        }
      }

      c = h;
    } else c = Cf;

    null === b.context ? b.context = c : b.pendingContext = c;
    b = zg(f, g);
    b.payload = {
      element: a
    };
    d = void 0 === d ? null : d;
    null !== d && (b.callback = d);
    Ag(e, b);
    Jg(e, g, f);
    return g;
  }

  function mk(a) {
    a = a.current;
    if (!a.child) return null;

    switch (a.child.tag) {
      case 5:
        return a.child.stateNode;

      default:
        return a.child.stateNode;
    }
  }

  function nk(a, b) {
    a = a.memoizedState;

    if (null !== a && null !== a.dehydrated) {
      var c = a.retryLane;
      a.retryLane = 0 !== c && c < b ? c : b;
    }
  }

  function ok(a, b) {
    nk(a, b);
    (a = a.alternate) && nk(a, b);
  }

  function pk() {
    return null;
  }

  function qk(a, b, c) {
    var d = null != c && null != c.hydrationOptions && c.hydrationOptions.mutableSources || null;
    c = new jk(a, b, null != c && !0 === c.hydrate);
    b = nh(3, null, null, 2 === b ? 7 : 1 === b ? 3 : 0);
    c.current = b;
    b.stateNode = c;
    xg(b);
    a[ff] = c.current;
    cf(8 === a.nodeType ? a.parentNode : a);
    if (d) for (a = 0; a < d.length; a++) {
      b = d[a];
      var e = b._getVersion;
      e = e(b._source);
      null == c.mutableSourceEagerHydrationData ? c.mutableSourceEagerHydrationData = [b, e] : c.mutableSourceEagerHydrationData.push(b, e);
    }
    this._internalRoot = c;
  }

  qk.prototype.render = function (a) {
    lk(a, this._internalRoot, null, null);
  };

  qk.prototype.unmount = function () {
    var a = this._internalRoot,
        b = a.containerInfo;
    lk(null, a, null, function () {
      b[ff] = null;
    });
  };

  function rk(a) {
    return !(!a || 1 !== a.nodeType && 9 !== a.nodeType && 11 !== a.nodeType && (8 !== a.nodeType || " react-mount-point-unstable " !== a.nodeValue));
  }

  function sk(a, b) {
    b || (b = a ? 9 === a.nodeType ? a.documentElement : a.firstChild : null, b = !(!b || 1 !== b.nodeType || !b.hasAttribute("data-reactroot")));
    if (!b) for (var c; c = a.lastChild;) a.removeChild(c);
    return new qk(a, 0, b ? {
      hydrate: !0
    } : void 0);
  }

  function tk(a, b, c, d, e) {
    var f = c._reactRootContainer;

    if (f) {
      var g = f._internalRoot;

      if ("function" === typeof e) {
        var h = e;

        e = function () {
          var a = mk(g);
          h.call(a);
        };
      }

      lk(b, g, a, e);
    } else {
      f = c._reactRootContainer = sk(c, d);
      g = f._internalRoot;

      if ("function" === typeof e) {
        var k = e;

        e = function () {
          var a = mk(g);
          k.call(a);
        };
      }

      Xj(function () {
        lk(b, g, a, e);
      });
    }

    return mk(g);
  }

  ec = function (a) {
    if (13 === a.tag) {
      var b = Hg();
      Jg(a, 4, b);
      ok(a, 4);
    }
  };

  fc = function (a) {
    if (13 === a.tag) {
      var b = Hg();
      Jg(a, 67108864, b);
      ok(a, 67108864);
    }
  };

  gc = function (a) {
    if (13 === a.tag) {
      var b = Hg(),
          c = Ig(a);
      Jg(a, c, b);
      ok(a, c);
    }
  };

  hc = function (a, b) {
    return b();
  };

  yb = function (a, b, c) {
    switch (b) {
      case "input":
        ab(a, c);
        b = c.name;

        if ("radio" === c.type && null != b) {
          for (c = a; c.parentNode;) c = c.parentNode;

          c = c.querySelectorAll("input[name=" + JSON.stringify("" + b) + '][type="radio"]');

          for (b = 0; b < c.length; b++) {
            var d = c[b];

            if (d !== a && d.form === a.form) {
              var e = Db(d);
              if (!e) throw Error(y$1(90));
              Wa(d);
              ab(d, e);
            }
          }
        }

        break;

      case "textarea":
        ib(a, c);
        break;

      case "select":
        b = c.value, null != b && fb(a, !!c.multiple, b, !1);
    }
  };

  Gb = Wj;

  Hb = function (a, b, c, d, e) {
    var f = X;
    X |= 4;

    try {
      return gg(98, a.bind(null, b, c, d, e));
    } finally {
      X = f, 0 === X && (wj(), ig());
    }
  };

  Ib = function () {
    0 === (X & 49) && (Vj(), Oj());
  };

  Jb = function (a, b) {
    var c = X;
    X |= 2;

    try {
      return a(b);
    } finally {
      X = c, 0 === X && (wj(), ig());
    }
  };

  function uk(a, b) {
    var c = 2 < arguments.length && void 0 !== arguments[2] ? arguments[2] : null;
    if (!rk(b)) throw Error(y$1(200));
    return kk(a, b, null, c);
  }

  var vk = {
    Events: [Cb, ue, Db, Eb, Fb, Oj, {
      current: !1
    }]
  },
      wk = {
    findFiberByHostInstance: wc,
    bundleType: 0,
    version: "17.0.2",
    rendererPackageName: "react-dom"
  };
  var xk = {
    bundleType: wk.bundleType,
    version: wk.version,
    rendererPackageName: wk.rendererPackageName,
    rendererConfig: wk.rendererConfig,
    overrideHookState: null,
    overrideHookStateDeletePath: null,
    overrideHookStateRenamePath: null,
    overrideProps: null,
    overridePropsDeletePath: null,
    overridePropsRenamePath: null,
    setSuspenseHandler: null,
    scheduleUpdate: null,
    currentDispatcherRef: ra.ReactCurrentDispatcher,
    findHostInstanceByFiber: function (a) {
      a = cc(a);
      return null === a ? null : a.stateNode;
    },
    findFiberByHostInstance: wk.findFiberByHostInstance || pk,
    findHostInstancesForRefresh: null,
    scheduleRefresh: null,
    scheduleRoot: null,
    setRefreshHandler: null,
    getCurrentFiber: null
  };

  if ("undefined" !== typeof __REACT_DEVTOOLS_GLOBAL_HOOK__) {
    var yk = __REACT_DEVTOOLS_GLOBAL_HOOK__;
    if (!yk.isDisabled && yk.supportsFiber) try {
      Lf = yk.inject(xk), Mf = yk;
    } catch (a) {}
  }

  var __SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED = vk;
  var createPortal = uk;

  var findDOMNode = function (a) {
    if (null == a) return null;
    if (1 === a.nodeType) return a;
    var b = a._reactInternals;

    if (void 0 === b) {
      if ("function" === typeof a.render) throw Error(y$1(188));
      throw Error(y$1(268, Object.keys(a)));
    }

    a = cc(b);
    a = null === a ? null : a.stateNode;
    return a;
  };

  var flushSync = function (a, b) {
    var c = X;
    if (0 !== (c & 48)) return a(b);
    X |= 1;

    try {
      if (a) return gg(99, a.bind(null, b));
    } finally {
      X = c, ig();
    }
  };

  var hydrate = function (a, b, c) {
    if (!rk(b)) throw Error(y$1(200));
    return tk(null, a, b, !0, c);
  };

  var render = function (a, b, c) {
    if (!rk(b)) throw Error(y$1(200));
    return tk(null, a, b, !1, c);
  };

  var unmountComponentAtNode = function (a) {
    if (!rk(a)) throw Error(y$1(40));
    return a._reactRootContainer ? (Xj(function () {
      tk(null, null, a, !1, function () {
        a._reactRootContainer = null;
        a[ff] = null;
      });
    }), !0) : !1;
  };

  var unstable_batchedUpdates = Wj;

  var unstable_createPortal = function (a, b) {
    return uk(a, b, 2 < arguments.length && void 0 !== arguments[2] ? arguments[2] : null);
  };

  var unstable_renderSubtreeIntoContainer = function (a, b, c, d) {
    if (!rk(c)) throw Error(y$1(200));
    if (null == a || void 0 === a._reactInternals) throw Error(y$1(38));
    return tk(a, b, c, !1, d);
  };

  var version$1 = "17.0.2";
  var reactDom_production_min = {
    __SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED: __SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED,
    createPortal: createPortal,
    findDOMNode: findDOMNode,
    flushSync: flushSync,
    hydrate: hydrate,
    render: render,
    unmountComponentAtNode: unmountComponentAtNode,
    unstable_batchedUpdates: unstable_batchedUpdates,
    unstable_createPortal: unstable_createPortal,
    unstable_renderSubtreeIntoContainer: unstable_renderSubtreeIntoContainer,
    version: version$1
  };

  /** @license React v0.20.2
   * scheduler-tracing.production.min.js
   *
   * Copyright (c) Facebook, Inc. and its affiliates.
   *
   * This source code is licensed under the MIT license found in the
   * LICENSE file in the root directory of this source tree.
   */

  var b$1 = 0;
  var __interactionsRef = null;
  var __subscriberRef = null;

  var unstable_clear = function (a) {
    return a();
  };

  var unstable_getCurrent = function () {
    return null;
  };

  var unstable_getThreadID = function () {
    return ++b$1;
  };

  var unstable_subscribe = function () {};

  var unstable_trace = function (a, d, c) {
    return c();
  };

  var unstable_unsubscribe = function () {};

  var unstable_wrap = function (a) {
    return a;
  };

  var schedulerTracing_production_min = {
    __interactionsRef: __interactionsRef,
    __subscriberRef: __subscriberRef,
    unstable_clear: unstable_clear,
    unstable_getCurrent: unstable_getCurrent,
    unstable_getThreadID: unstable_getThreadID,
    unstable_subscribe: unstable_subscribe,
    unstable_trace: unstable_trace,
    unstable_unsubscribe: unstable_unsubscribe,
    unstable_wrap: unstable_wrap
  };

  var schedulerTracing_development = createCommonjsModule(function (module, exports) {
  });

  var tracing = createCommonjsModule(function (module) {

    {
      module.exports = schedulerTracing_production_min;
    }
  });

  var reactDom_development = createCommonjsModule(function (module, exports) {
  });

  var reactDom = createCommonjsModule(function (module) {

    function checkDCE() {
      /* global __REACT_DEVTOOLS_GLOBAL_HOOK__ */
      if (typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ === 'undefined' || typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE !== 'function') {
        return;
      }

      try {
        // Verify that the code above has been dead code eliminated (DCE'd).
        __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE(checkDCE);
      } catch (err) {
        // DevTools shouldn't crash React, no matter what.
        // We should still report in case we break this code.
        console.error(err);
      }
    }

    {
      // DCE check should happen before ReactDOM bundle executes so that
      // DevTools can report bad minification during injection.
      checkDCE();
      module.exports = reactDom_production_min;
    }
  });

  function _extends() {
    _extends = Object.assign ? Object.assign.bind() : function (target) {
      for (var i = 1; i < arguments.length; i++) {
        var source = arguments[i];

        for (var key in source) {
          if (Object.prototype.hasOwnProperty.call(source, key)) {
            target[key] = source[key];
          }
        }
      }

      return target;
    };
    return _extends.apply(this, arguments);
  }

  function _objectWithoutPropertiesLoose(source, excluded) {
    if (source == null) return {};
    var target = {};
    var sourceKeys = Object.keys(source);
    var key, i;

    for (i = 0; i < sourceKeys.length; i++) {
      key = sourceKeys[i];
      if (excluded.indexOf(key) >= 0) continue;
      target[key] = source[key];
    }

    return target;
  }

  var fetchFromApi = function fetchFromApi(url, data, headers) {
    if (url === void 0) {
      url = '';
    }

    try {
      return Promise.resolve(fetch(url, {
        method: 'POST',
        headers: _extends({
          'Content-Type': 'application/json'
        }, headers),
        body: JSON.stringify(data)
      })).then(function (response) {
        return response.json();
      });
    } catch (e) {
      return Promise.reject(e);
    }
  };

  var isPlanEligible = function isPlanEligible(plan, configPlan) {
    if (!plan.eligible) {
      return false;
    }

    return configPlan ? plan.purchase_amount >= (configPlan == null ? void 0 : configPlan.minAmount) && plan.purchase_amount <= (configPlan == null ? void 0 : configPlan.maxAmount) : false;
  };

  var getPaymentPlanBoundaries = function getPaymentPlanBoundaries(plan, configPlan) {
    var _plan$constraints;

    // When the plan is not eligible, the purchase amount constraints is given from the merchant config
    var purchaseAmountConstraints = (_plan$constraints = plan.constraints) == null ? void 0 : _plan$constraints.purchase_amount;

    if (purchaseAmountConstraints && configPlan) {
      return {
        minAmount: Math.max(configPlan.minAmount, purchaseAmountConstraints == null ? void 0 : purchaseAmountConstraints.minimum),
        maxAmount: Math.min(configPlan.maxAmount, purchaseAmountConstraints == null ? void 0 : purchaseAmountConstraints.maximum)
      };
    }

    return configPlan != null ? configPlan : {};
  };

  var filterEligibility = function filterEligibility(eligibilities, configPlans) {
    // Remove P1X if no configuration is provided
    if (!configPlans) {
      return eligibilities.filter(function (plan) {
        return !(plan.installments_count === 1 && plan.deferred_days === 0 && plan.deferred_months === 0);
      });
    } // Else check if the plan is eligible regarding the related configPlan


    return eligibilities.map(function (plan) {
      var eligibilityDeferredDays = (plan.deferred_months ? plan.deferred_months : 0) * 30 + (plan.deferred_days ? plan.deferred_days : 0); // find the related configPlan

      var relatedConfigPlan = configPlans.find(function (configPlan) {
        var configPlanDeferredDays = (configPlan.deferredMonths ? configPlan.deferredMonths : 0) * 30 + (configPlan.deferredDays ? configPlan.deferredDays : 0);
        return plan.installments_count === configPlan.installmentsCount && eligibilityDeferredDays === configPlanDeferredDays;
      });
      return _extends({}, plan, {
        eligible: isPlanEligible(plan, relatedConfigPlan)
      }, getPaymentPlanBoundaries(plan, relatedConfigPlan));
    });
  };

  var useFetchEligibility = function useFetchEligibility(purchaseAmount, _ref, plans, customerBillingCountry, customerShippingCountry) {
    var domain = _ref.domain,
        merchantId = _ref.merchantId;

    var _useState = react.useState([]),
        eligibility = _useState[0],
        setEligibility = _useState[1];

    var _useState2 = react.useState(apiStatus.PENDING),
        status = _useState2[0],
        setStatus = _useState2[1];

    var configInstallments = plans == null ? void 0 : plans.map(function (plan) {
      return {
        installments_count: plan.installmentsCount,
        deferred_days: plan == null ? void 0 : plan.deferredDays,
        deferred_months: plan == null ? void 0 : plan.deferredMonths
      };
    });
    react.useEffect(function () {
      if (status === apiStatus.PENDING) {
        var billing_address = null;

        if (customerBillingCountry) {
          billing_address = {
            country: customerBillingCountry
          };
        }

        var shipping_address = null;

        if (customerShippingCountry) {
          shipping_address = {
            country: customerShippingCountry
          };
        }

        fetchFromApi(domain + '/v2/payments/eligibility', {
          purchase_amount: purchaseAmount,
          queries: configInstallments,
          billing_address: billing_address,
          shipping_address: shipping_address
        }, {
          Authorization: "Alma-Merchant-Auth " + merchantId,
          'X-Alma-Agent': "Alma Widget/" + "v3.3.10"
        }).then(function (res) {
          setEligibility(res);
          setStatus(apiStatus.SUCCESS);
        })["catch"](function () {
          setStatus(apiStatus.FAILED);
        });
      }
    }, [status]);
    return [filterEligibility(eligibility, plans), status];
  };

  var classnames = createCommonjsModule(function (module) {
    /*!
      Copyright (c) 2018 Jed Watson.
      Licensed under the MIT License (MIT), see
      http://jedwatson.github.io/classnames
    */

    /* global define */
    (function () {

      var hasOwn = {}.hasOwnProperty;

      function classNames() {
        var classes = [];

        for (var i = 0; i < arguments.length; i++) {
          var arg = arguments[i];
          if (!arg) continue;
          var argType = typeof arg;

          if (argType === 'string' || argType === 'number') {
            classes.push(arg);
          } else if (Array.isArray(arg)) {
            if (arg.length) {
              var inner = classNames.apply(null, arg);

              if (inner) {
                classes.push(inner);
              }
            }
          } else if (argType === 'object') {
            if (arg.toString === Object.prototype.toString) {
              for (var key in arg) {
                if (hasOwn.call(arg, key) && arg[key]) {
                  classes.push(key);
                }
              }
            } else {
              classes.push(arg.toString());
            }
          }
        }

        return classes.join(' ');
      }

      if ( module.exports) {
        classNames.default = classNames;
        module.exports = classNames;
      } else {
        window.classNames = classNames;
      }
    })();
  });

  var s = {"container":"_GTTk7","total":"_34s2g","fees":"_W7qIA","creditCost":"_24KmE"};

  /**
   * Prefix classes to avoid name collisions.
   */
  var prefix = 'alma-eligibility-modal';
  /**
   * Class names for the **eligibility modale** widget.
   * Those classes are intended to be used by the **merchant developer**.
   */

  var STATIC_CUSTOMISATION_CLASSES = {
    desktopModal: prefix + '-desktop-modal',
    mobileModal: prefix + '-mobile-modal',
    leftSide: prefix + '-left-side',
    bullet: prefix + '-bullet',
    rightSide: prefix + '-right-side',
    title: prefix + '-title',
    info: prefix + '-info',
    infoMessage: prefix + '-info-message',
    eligibilityOptions: prefix + '-eligibility-options',
    activeOption: prefix + '-active-option',
    closeButton: prefix + '-close-button',
    scheduleDetails: prefix + '-schedule-details',
    scheduleTotal: prefix + '-schedule-total',
    scheduleCredit: prefix + '-schedule-credit',
    cardContainer: prefix + '-card-logos',
    summary: prefix + '-summary'
  };

  var TotalBlock = function TotalBlock(_ref) {
    var currentPlan = _ref.currentPlan;
    var intl = useIntl();
    var total = priceFromCents(currentPlan.purchase_amount + currentPlan.customer_total_cost_amount);
    var creditCost = priceFromCents(currentPlan.customer_total_cost_amount);
    var TAEG = (currentPlan == null ? void 0 : currentPlan.annual_interest_rate) && currentPlan.annual_interest_rate / 10000 || 0;
    var customerFees = priceFromCents(currentPlan.customer_total_cost_amount);
    var isCredit = currentPlan.installments_count > 4;
    return /*#__PURE__*/react.createElement("div", {
      className: classnames(s.container, STATIC_CUSTOMISATION_CLASSES.summary),
      "data-testid": "modal-summary"
    }, /*#__PURE__*/react.createElement("div", {
      className: classnames(s.total, STATIC_CUSTOMISATION_CLASSES.scheduleTotal)
    }, /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      tagName: "div",
      id: "installments.total-amount",
      defaultMessage: "Total"
    }), /*#__PURE__*/react.createElement(FormattedNumber, {
      value: total || 0,
      style: "currency",
      currency: "EUR"
    })), /*#__PURE__*/react.createElement("div", {
      className: classnames(s.fees, STATIC_CUSTOMISATION_CLASSES.scheduleCredit)
    }, isCredit ? /*#__PURE__*/react.createElement(react.Fragment, null, /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      id: "credit-features.total-credit-cost",
      defaultMessage: "Dont co\xFBt du cr\xE9dit"
    }), /*#__PURE__*/react.createElement("span", {
      className: s.creditCost
    }, /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      id: "credit-features.credit-cost-display",
      defaultMessage: "{creditCost} (TAEG {taegPercentage})",
      values: {
        creditCost: intl.formatNumber(creditCost, {
          style: 'currency',
          currency: 'EUR'
        }),
        taegPercentage: intl.formatNumber(TAEG, {
          style: 'percent',
          maximumFractionDigits: 2
        })
      }
    }))) : /*#__PURE__*/react.createElement(react.Fragment, null, /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      id: "installments.total-fees",
      defaultMessage: "Dont frais (TTC)",
      tagName: "div"
    }), /*#__PURE__*/react.createElement("div", null, /*#__PURE__*/react.createElement(FormattedNumber, {
      value: customerFees,
      style: "currency",
      currency: "EUR"
    })))));
  };

  var s$1 = {"loadingIndicator":"_31lrj","bounce":"_3NtDa"};

  var LoadingIndicator = function LoadingIndicator(_ref) {
    var className = _ref.className;
    return /*#__PURE__*/react.createElement("div", {
      className: classnames(s$1.loadingIndicator, className),
      "data-testid": "loader"
    }, /*#__PURE__*/react.createElement("svg", {
      width: "120",
      height: "134",
      viewBox: "0 0 120 134",
      fill: "none",
      xmlns: "http://www.w3.org/2000/svg"
    }, /*#__PURE__*/react.createElement("path", {
      d: "M83.8164 41.0325C79.1708 22.8241 69.3458 17 59.9939 17C50.642 17 40.8171 22.8241 36.1715 41.0325L16 117H35.8804C39.119 104.311 49.1016 97.2436 59.9939 97.2436C70.8863 97.2436 80.8689 104.324 84.1075 117H104L83.8164 41.0325ZM59.9939 79.5428C53.6623 79.5428 47.925 82.0552 43.6918 86.1283L55.0936 41.9207C56.1853 37.6953 57.7985 36.3503 60.0061 36.3503C62.2136 36.3503 63.8269 37.6953 64.9185 41.9207L76.3082 86.1283C72.075 82.0552 66.3256 79.5428 59.9939 79.5428Z",
      fill: "#FA5022"
    })));
  };

  /** @license React v16.13.1
   * react-is.production.min.js
   *
   * Copyright (c) Facebook, Inc. and its affiliates.
   *
   * This source code is licensed under the MIT license found in the
   * LICENSE file in the root directory of this source tree.
   */

  var b$2 = "function" === typeof Symbol && Symbol.for,
      c$1 = b$2 ? Symbol.for("react.element") : 60103,
      d$1 = b$2 ? Symbol.for("react.portal") : 60106,
      e$1 = b$2 ? Symbol.for("react.fragment") : 60107,
      f$7 = b$2 ? Symbol.for("react.strict_mode") : 60108,
      g$1 = b$2 ? Symbol.for("react.profiler") : 60114,
      h$1 = b$2 ? Symbol.for("react.provider") : 60109,
      k$1 = b$2 ? Symbol.for("react.context") : 60110,
      l$1 = b$2 ? Symbol.for("react.async_mode") : 60111,
      m$1 = b$2 ? Symbol.for("react.concurrent_mode") : 60111,
      n$1 = b$2 ? Symbol.for("react.forward_ref") : 60112,
      p$1 = b$2 ? Symbol.for("react.suspense") : 60113,
      q$1 = b$2 ? Symbol.for("react.suspense_list") : 60120,
      r$1 = b$2 ? Symbol.for("react.memo") : 60115,
      t$1 = b$2 ? Symbol.for("react.lazy") : 60116,
      v$1 = b$2 ? Symbol.for("react.block") : 60121,
      w$1 = b$2 ? Symbol.for("react.fundamental") : 60117,
      x$1 = b$2 ? Symbol.for("react.responder") : 60118,
      y$2 = b$2 ? Symbol.for("react.scope") : 60119;

  function z$1(a) {
    if ("object" === typeof a && null !== a) {
      var u = a.$$typeof;

      switch (u) {
        case c$1:
          switch (a = a.type, a) {
            case l$1:
            case m$1:
            case e$1:
            case g$1:
            case f$7:
            case p$1:
              return a;

            default:
              switch (a = a && a.$$typeof, a) {
                case k$1:
                case n$1:
                case t$1:
                case r$1:
                case h$1:
                  return a;

                default:
                  return u;
              }

          }

        case d$1:
          return u;
      }
    }
  }

  function A$1(a) {
    return z$1(a) === m$1;
  }

  var AsyncMode$1 = l$1;
  var ConcurrentMode$1 = m$1;
  var ContextConsumer$1 = k$1;
  var ContextProvider$1 = h$1;
  var Element$1 = c$1;
  var ForwardRef$1 = n$1;
  var Fragment$1 = e$1;
  var Lazy$1 = t$1;
  var Memo$1 = r$1;
  var Portal$1 = d$1;
  var Profiler$1 = g$1;
  var StrictMode$1 = f$7;
  var Suspense$1 = p$1;

  var isAsyncMode$1 = function (a) {
    return A$1(a) || z$1(a) === l$1;
  };

  var isConcurrentMode$1 = A$1;

  var isContextConsumer$1 = function (a) {
    return z$1(a) === k$1;
  };

  var isContextProvider$1 = function (a) {
    return z$1(a) === h$1;
  };

  var isElement$1 = function (a) {
    return "object" === typeof a && null !== a && a.$$typeof === c$1;
  };

  var isForwardRef$1 = function (a) {
    return z$1(a) === n$1;
  };

  var isFragment$1 = function (a) {
    return z$1(a) === e$1;
  };

  var isLazy$1 = function (a) {
    return z$1(a) === t$1;
  };

  var isMemo$1 = function (a) {
    return z$1(a) === r$1;
  };

  var isPortal$1 = function (a) {
    return z$1(a) === d$1;
  };

  var isProfiler$1 = function (a) {
    return z$1(a) === g$1;
  };

  var isStrictMode$1 = function (a) {
    return z$1(a) === f$7;
  };

  var isSuspense$1 = function (a) {
    return z$1(a) === p$1;
  };

  var isValidElementType$1 = function (a) {
    return "string" === typeof a || "function" === typeof a || a === e$1 || a === m$1 || a === g$1 || a === f$7 || a === p$1 || a === q$1 || "object" === typeof a && null !== a && (a.$$typeof === t$1 || a.$$typeof === r$1 || a.$$typeof === h$1 || a.$$typeof === k$1 || a.$$typeof === n$1 || a.$$typeof === w$1 || a.$$typeof === x$1 || a.$$typeof === y$2 || a.$$typeof === v$1);
  };

  var typeOf$1 = z$1;
  var reactIs_production_min$1 = {
    AsyncMode: AsyncMode$1,
    ConcurrentMode: ConcurrentMode$1,
    ContextConsumer: ContextConsumer$1,
    ContextProvider: ContextProvider$1,
    Element: Element$1,
    ForwardRef: ForwardRef$1,
    Fragment: Fragment$1,
    Lazy: Lazy$1,
    Memo: Memo$1,
    Portal: Portal$1,
    Profiler: Profiler$1,
    StrictMode: StrictMode$1,
    Suspense: Suspense$1,
    isAsyncMode: isAsyncMode$1,
    isConcurrentMode: isConcurrentMode$1,
    isContextConsumer: isContextConsumer$1,
    isContextProvider: isContextProvider$1,
    isElement: isElement$1,
    isForwardRef: isForwardRef$1,
    isFragment: isFragment$1,
    isLazy: isLazy$1,
    isMemo: isMemo$1,
    isPortal: isPortal$1,
    isProfiler: isProfiler$1,
    isStrictMode: isStrictMode$1,
    isSuspense: isSuspense$1,
    isValidElementType: isValidElementType$1,
    typeOf: typeOf$1
  };

  var reactIs_development$1 = createCommonjsModule(function (module, exports) {
  });

  var reactIs$1 = createCommonjsModule(function (module) {

    {
      module.exports = reactIs_production_min$1;
    }
  });

  /**
   * Copyright (c) 2013-present, Facebook, Inc.
   *
   * This source code is licensed under the MIT license found in the
   * LICENSE file in the root directory of this source tree.
   */

  var ReactPropTypesSecret = 'SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED';
  var ReactPropTypesSecret_1 = ReactPropTypesSecret;

  function emptyFunction() {}

  function emptyFunctionWithReset() {}

  emptyFunctionWithReset.resetWarningCache = emptyFunction;

  var factoryWithThrowingShims = function () {
    function shim(props, propName, componentName, location, propFullName, secret) {
      if (secret === ReactPropTypesSecret_1) {
        // It is still safe when called from React.
        return;
      }

      var err = new Error('Calling PropTypes validators directly is not supported by the `prop-types` package. ' + 'Use PropTypes.checkPropTypes() to call them. ' + 'Read more at http://fb.me/use-check-prop-types');
      err.name = 'Invariant Violation';
      throw err;
    }
    shim.isRequired = shim;

    function getShim() {
      return shim;
    }
    // Keep this list in sync with production version in `./factoryWithTypeCheckers.js`.

    var ReactPropTypes = {
      array: shim,
      bigint: shim,
      bool: shim,
      func: shim,
      number: shim,
      object: shim,
      string: shim,
      symbol: shim,
      any: shim,
      arrayOf: getShim,
      element: shim,
      elementType: shim,
      instanceOf: getShim,
      node: shim,
      objectOf: getShim,
      oneOf: getShim,
      oneOfType: getShim,
      shape: getShim,
      exact: getShim,
      checkPropTypes: emptyFunctionWithReset,
      resetWarningCache: emptyFunction
    };
    ReactPropTypes.PropTypes = ReactPropTypes;
    return ReactPropTypes;
  };

  var propTypes = createCommonjsModule(function (module) {
    /**
     * Copyright (c) 2013-present, Facebook, Inc.
     *
     * This source code is licensed under the MIT license found in the
     * LICENSE file in the root directory of this source tree.
     */
    {
      // By explicitly using `prop-types` you are opting into new production behavior.
      // http://fb.me/prop-types-in-prod
      module.exports = factoryWithThrowingShims();
    }
  });

  var tabbable_1 = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.default = findTabbableDescendants;
    /*!
     * Adapted from jQuery UI core
     *
     * http://jqueryui.com
     *
     * Copyright 2014 jQuery Foundation and other contributors
     * Released under the MIT license.
     * http://jquery.org/license
     *
     * http://api.jqueryui.com/category/ui-core/
     */

    var tabbableNode = /input|select|textarea|button|object|iframe/;

    function hidesContents(element) {
      var zeroSize = element.offsetWidth <= 0 && element.offsetHeight <= 0; // If the node is empty, this is good enough

      if (zeroSize && !element.innerText) return true;

      try {
        // Otherwise we need to check some styles
        var style = window.getComputedStyle(element);
        return zeroSize ? style.getPropertyValue("overflow") !== "visible" || // if 'overflow: visible' set, check if there is actually any overflow
        element.scrollWidth <= 0 && element.scrollHeight <= 0 : style.getPropertyValue("display") == "none";
      } catch (exception) {
        // eslint-disable-next-line no-console
        console.warn("Failed to inspect element style");
        return false;
      }
    }

    function visible(element) {
      var parentElement = element;
      var rootNode = element.getRootNode && element.getRootNode();

      while (parentElement) {
        if (parentElement === document.body) break; // if we are not hidden yet, skip to checking outside the Web Component

        if (rootNode && parentElement === rootNode) parentElement = rootNode.host.parentNode;
        if (hidesContents(parentElement)) return false;
        parentElement = parentElement.parentNode;
      }

      return true;
    }

    function focusable(element, isTabIndexNotNaN) {
      var nodeName = element.nodeName.toLowerCase();
      var res = tabbableNode.test(nodeName) && !element.disabled || (nodeName === "a" ? element.href || isTabIndexNotNaN : isTabIndexNotNaN);
      return res && visible(element);
    }

    function tabbable(element) {
      var tabIndex = element.getAttribute("tabindex");
      if (tabIndex === null) tabIndex = undefined;
      var isTabIndexNaN = isNaN(tabIndex);
      return (isTabIndexNaN || tabIndex >= 0) && focusable(element, !isTabIndexNaN);
    }

    function findTabbableDescendants(element) {
      var descendants = [].slice.call(element.querySelectorAll("*"), 0).reduce(function (finished, el) {
        return finished.concat(!el.shadowRoot ? [el] : findTabbableDescendants(el.shadowRoot));
      }, []);
      return descendants.filter(tabbable);
    }

    module.exports = exports["default"];
  });

  var focusManager = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.resetState = resetState;
    exports.log = log;
    exports.handleBlur = handleBlur;
    exports.handleFocus = handleFocus;
    exports.markForFocusLater = markForFocusLater;
    exports.returnFocus = returnFocus;
    exports.popWithoutFocus = popWithoutFocus;
    exports.setupScopedFocus = setupScopedFocus;
    exports.teardownScopedFocus = teardownScopedFocus;

    var _tabbable2 = _interopRequireDefault(tabbable_1);

    function _interopRequireDefault(obj) {
      return obj && obj.__esModule ? obj : {
        default: obj
      };
    }

    var focusLaterElements = [];
    var modalElement = null;
    var needToFocus = false;
    /* eslint-disable no-console */

    /* istanbul ignore next */

    function resetState() {
      focusLaterElements = [];
    }
    /* istanbul ignore next */


    function log() {
    }
    /* eslint-enable no-console */


    function handleBlur() {
      needToFocus = true;
    }

    function handleFocus() {
      if (needToFocus) {
        needToFocus = false;

        if (!modalElement) {
          return;
        } // need to see how jQuery shims document.on('focusin') so we don't need the
        // setTimeout, firefox doesn't support focusin, if it did, we could focus
        // the element outside of a setTimeout. Side-effect of this implementation
        // is that the document.body gets focus, and then we focus our element right
        // after, seems fine.


        setTimeout(function () {
          if (modalElement.contains(document.activeElement)) {
            return;
          }

          var el = (0, _tabbable2.default)(modalElement)[0] || modalElement;
          el.focus();
        }, 0);
      }
    }

    function markForFocusLater() {
      focusLaterElements.push(document.activeElement);
    }
    /* eslint-disable no-console */


    function returnFocus() {
      var preventScroll = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      var toFocus = null;

      try {
        if (focusLaterElements.length !== 0) {
          toFocus = focusLaterElements.pop();
          toFocus.focus({
            preventScroll: preventScroll
          });
        }

        return;
      } catch (e) {
        console.warn(["You tried to return focus to", toFocus, "but it is not in the DOM anymore"].join(" "));
      }
    }
    /* eslint-enable no-console */


    function popWithoutFocus() {
      focusLaterElements.length > 0 && focusLaterElements.pop();
    }

    function setupScopedFocus(element) {
      modalElement = element;

      if (window.addEventListener) {
        window.addEventListener("blur", handleBlur, false);
        document.addEventListener("focus", handleFocus, true);
      } else {
        window.attachEvent("onBlur", handleBlur);
        document.attachEvent("onFocus", handleFocus);
      }
    }

    function teardownScopedFocus() {
      modalElement = null;

      if (window.addEventListener) {
        window.removeEventListener("blur", handleBlur);
        document.removeEventListener("focus", handleFocus);
      } else {
        window.detachEvent("onBlur", handleBlur);
        document.detachEvent("onFocus", handleFocus);
      }
    }
  });

  var scopeTab_1 = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.default = scopeTab;

    var _tabbable2 = _interopRequireDefault(tabbable_1);

    function _interopRequireDefault(obj) {
      return obj && obj.__esModule ? obj : {
        default: obj
      };
    }

    function getActiveElement() {
      var el = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : document;
      return el.activeElement.shadowRoot ? getActiveElement(el.activeElement.shadowRoot) : el.activeElement;
    }

    function scopeTab(node, event) {
      var tabbable = (0, _tabbable2.default)(node);

      if (!tabbable.length) {
        // Do nothing, since there are no elements that can receive focus.
        event.preventDefault();
        return;
      }

      var target = void 0;
      var shiftKey = event.shiftKey;
      var head = tabbable[0];
      var tail = tabbable[tabbable.length - 1];
      var activeElement = getActiveElement(); // proceed with default browser behavior on tab.
      // Focus on last element on shift + tab.

      if (node === activeElement) {
        if (!shiftKey) return;
        target = tail;
      }

      if (tail === activeElement && !shiftKey) {
        target = head;
      }

      if (head === activeElement && shiftKey) {
        target = tail;
      }

      if (target) {
        event.preventDefault();
        target.focus();
        return;
      } // Safari radio issue.
      //
      // Safari does not move the focus to the radio button,
      // so we need to force it to really walk through all elements.
      //
      // This is very error prone, since we are trying to guess
      // if it is a safari browser from the first occurence between
      // chrome or safari.
      //
      // The chrome user agent contains the first ocurrence
      // as the 'chrome/version' and later the 'safari/version'.


      var checkSafari = /(\bChrome\b|\bSafari\b)\//.exec(navigator.userAgent);
      var isSafariDesktop = checkSafari != null && checkSafari[1] != "Chrome" && /\biPod\b|\biPad\b/g.exec(navigator.userAgent) == null; // If we are not in safari desktop, let the browser control
      // the focus

      if (!isSafariDesktop) return;
      var x = tabbable.indexOf(activeElement);

      if (x > -1) {
        x += shiftKey ? -1 : 1;
      }

      target = tabbable[x]; // If the tabbable element does not exist,
      // focus head/tail based on shiftKey

      if (typeof target === "undefined") {
        event.preventDefault();
        target = shiftKey ? tail : head;
        target.focus();
        return;
      }

      event.preventDefault();
      target.focus();
    }

    module.exports = exports["default"];
  });

  /**
   * Copyright (c) 2014-present, Facebook, Inc.
   *
   * This source code is licensed under the MIT license found in the
   * LICENSE file in the root directory of this source tree.
   */

  var warning = function () {};

  var warning_1 = warning;

  var exenv = createCommonjsModule(function (module) {
    /*!
      Copyright (c) 2015 Jed Watson.
      Based on code that is Copyright 2013-2015, Facebook, Inc.
      All rights reserved.
    */

    /* global define */
    (function () {

      var canUseDOM = !!(typeof window !== 'undefined' && window.document && window.document.createElement);
      var ExecutionEnvironment = {
        canUseDOM: canUseDOM,
        canUseWorkers: typeof Worker !== 'undefined',
        canUseEventListeners: canUseDOM && !!(window.addEventListener || window.attachEvent),
        canUseViewport: canUseDOM && !!window.screen
      };

      if ( module.exports) {
        module.exports = ExecutionEnvironment;
      } else {
        window.ExecutionEnvironment = ExecutionEnvironment;
      }
    })();
  });

  var safeHTMLElement = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.canUseDOM = exports.SafeNodeList = exports.SafeHTMLCollection = undefined;

    var _exenv2 = _interopRequireDefault(exenv);

    function _interopRequireDefault(obj) {
      return obj && obj.__esModule ? obj : {
        default: obj
      };
    }

    var EE = _exenv2.default;
    var SafeHTMLElement = EE.canUseDOM ? window.HTMLElement : {};
    var SafeHTMLCollection = exports.SafeHTMLCollection = EE.canUseDOM ? window.HTMLCollection : {};
    var SafeNodeList = exports.SafeNodeList = EE.canUseDOM ? window.NodeList : {};
    var canUseDOM = exports.canUseDOM = EE.canUseDOM;
    exports.default = SafeHTMLElement;
  });

  var ariaAppHider = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.resetState = resetState;
    exports.log = log;
    exports.assertNodeList = assertNodeList;
    exports.setElement = setElement;
    exports.validateElement = validateElement;
    exports.hide = hide;
    exports.show = show;
    exports.documentNotReadyOrSSRTesting = documentNotReadyOrSSRTesting;

    var _warning2 = _interopRequireDefault(warning_1);

    function _interopRequireDefault(obj) {
      return obj && obj.__esModule ? obj : {
        default: obj
      };
    }

    var globalElement = null;
    /* eslint-disable no-console */

    /* istanbul ignore next */

    function resetState() {
      if (globalElement) {
        if (globalElement.removeAttribute) {
          globalElement.removeAttribute("aria-hidden");
        } else if (globalElement.length != null) {
          globalElement.forEach(function (element) {
            return element.removeAttribute("aria-hidden");
          });
        } else {
          document.querySelectorAll(globalElement).forEach(function (element) {
            return element.removeAttribute("aria-hidden");
          });
        }
      }

      globalElement = null;
    }
    /* istanbul ignore next */


    function log() {
    }
    /* eslint-enable no-console */


    function assertNodeList(nodeList, selector) {
      if (!nodeList || !nodeList.length) {
        throw new Error("react-modal: No elements were found for selector " + selector + ".");
      }
    }

    function setElement(element) {
      var useElement = element;

      if (typeof useElement === "string" && safeHTMLElement.canUseDOM) {
        var el = document.querySelectorAll(useElement);
        assertNodeList(el, useElement);
        useElement = el;
      }

      globalElement = useElement || globalElement;
      return globalElement;
    }

    function validateElement(appElement) {
      var el = appElement || globalElement;

      if (el) {
        return Array.isArray(el) || el instanceof HTMLCollection || el instanceof NodeList ? el : [el];
      } else {
        (0, _warning2.default)(false, ["react-modal: App element is not defined.", "Please use `Modal.setAppElement(el)` or set `appElement={el}`.", "This is needed so screen readers don't see main content", "when modal is opened. It is not recommended, but you can opt-out", "by setting `ariaHideApp={false}`."].join(" "));
        return [];
      }
    }

    function hide(appElement) {
      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        for (var _iterator = validateElement(appElement)[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          var el = _step.value;
          el.setAttribute("aria-hidden", "true");
        }
      } catch (err) {
        _didIteratorError = true;
        _iteratorError = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion && _iterator.return) {
            _iterator.return();
          }
        } finally {
          if (_didIteratorError) {
            throw _iteratorError;
          }
        }
      }
    }

    function show(appElement) {
      var _iteratorNormalCompletion2 = true;
      var _didIteratorError2 = false;
      var _iteratorError2 = undefined;

      try {
        for (var _iterator2 = validateElement(appElement)[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
          var el = _step2.value;
          el.removeAttribute("aria-hidden");
        }
      } catch (err) {
        _didIteratorError2 = true;
        _iteratorError2 = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion2 && _iterator2.return) {
            _iterator2.return();
          }
        } finally {
          if (_didIteratorError2) {
            throw _iteratorError2;
          }
        }
      }
    }

    function documentNotReadyOrSSRTesting() {
      globalElement = null;
    }
  });

  var classList = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.resetState = resetState;
    exports.log = log;
    var htmlClassList = {};
    var docBodyClassList = {};
    /* eslint-disable no-console */

    /* istanbul ignore next */

    function removeClass(at, cls) {
      at.classList.remove(cls);
    }
    /* istanbul ignore next */


    function resetState() {
      var htmlElement = document.getElementsByTagName("html")[0];

      for (var cls in htmlClassList) {
        removeClass(htmlElement, htmlClassList[cls]);
      }

      var body = document.body;

      for (var _cls in docBodyClassList) {
        removeClass(body, docBodyClassList[_cls]);
      }

      htmlClassList = {};
      docBodyClassList = {};
    }
    /* istanbul ignore next */


    function log() {
    }
    /* eslint-enable no-console */

    /**
     * Track the number of reference of a class.
     * @param {object} poll The poll to receive the reference.
     * @param {string} className The class name.
     * @return {string}
     */


    var incrementReference = function incrementReference(poll, className) {
      if (!poll[className]) {
        poll[className] = 0;
      }

      poll[className] += 1;
      return className;
    };
    /**
     * Drop the reference of a class.
     * @param {object} poll The poll to receive the reference.
     * @param {string} className The class name.
     * @return {string}
     */


    var decrementReference = function decrementReference(poll, className) {
      if (poll[className]) {
        poll[className] -= 1;
      }

      return className;
    };
    /**
     * Track a class and add to the given class list.
     * @param {Object} classListRef A class list of an element.
     * @param {Object} poll         The poll to be used.
     * @param {Array}  classes      The list of classes to be tracked.
     */


    var trackClass = function trackClass(classListRef, poll, classes) {
      classes.forEach(function (className) {
        incrementReference(poll, className);
        classListRef.add(className);
      });
    };
    /**
     * Untrack a class and remove from the given class list if the reference
     * reaches 0.
     * @param {Object} classListRef A class list of an element.
     * @param {Object} poll         The poll to be used.
     * @param {Array}  classes      The list of classes to be untracked.
     */


    var untrackClass = function untrackClass(classListRef, poll, classes) {
      classes.forEach(function (className) {
        decrementReference(poll, className);
        poll[className] === 0 && classListRef.remove(className);
      });
    };
    /**
     * Public inferface to add classes to the document.body.
     * @param {string} bodyClass The class string to be added.
     *                           It may contain more then one class
     *                           with ' ' as separator.
     */


    var add = exports.add = function add(element, classString) {
      return trackClass(element.classList, element.nodeName.toLowerCase() == "html" ? htmlClassList : docBodyClassList, classString.split(" "));
    };
    /**
     * Public inferface to remove classes from the document.body.
     * @param {string} bodyClass The class string to be added.
     *                           It may contain more then one class
     *                           with ' ' as separator.
     */


    var remove = exports.remove = function remove(element, classString) {
      return untrackClass(element.classList, element.nodeName.toLowerCase() == "html" ? htmlClassList : docBodyClassList, classString.split(" "));
    };
  });

  var portalOpenInstances_1 = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.log = log;
    exports.resetState = resetState;

    function _classCallCheck(instance, Constructor) {
      if (!(instance instanceof Constructor)) {
        throw new TypeError("Cannot call a class as a function");
      }
    } // Tracks portals that are open and emits events to subscribers


    var PortalOpenInstances = function PortalOpenInstances() {
      var _this = this;

      _classCallCheck(this, PortalOpenInstances);

      this.register = function (openInstance) {
        if (_this.openInstances.indexOf(openInstance) !== -1) {

          return;
        }

        _this.openInstances.push(openInstance);

        _this.emit("register");
      };

      this.deregister = function (openInstance) {
        var index = _this.openInstances.indexOf(openInstance);

        if (index === -1) {

          return;
        }

        _this.openInstances.splice(index, 1);

        _this.emit("deregister");
      };

      this.subscribe = function (callback) {
        _this.subscribers.push(callback);
      };

      this.emit = function (eventType) {
        _this.subscribers.forEach(function (subscriber) {
          return subscriber(eventType, // shallow copy to avoid accidental mutation
          _this.openInstances.slice());
        });
      };

      this.openInstances = [];
      this.subscribers = [];
    };

    var portalOpenInstances = new PortalOpenInstances();
    /* eslint-disable no-console */

    /* istanbul ignore next */

    function log() {
      console.log("portalOpenInstances ----------");
      console.log(portalOpenInstances.openInstances.length);
      portalOpenInstances.openInstances.forEach(function (p) {
        return console.log(p);
      });
      console.log("end portalOpenInstances ----------");
    }
    /* istanbul ignore next */


    function resetState() {
      portalOpenInstances = new PortalOpenInstances();
    }
    /* eslint-enable no-console */


    exports.default = portalOpenInstances;
  });

  var bodyTrap_1 = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.resetState = resetState;
    exports.log = log;

    var _portalOpenInstances2 = _interopRequireDefault(portalOpenInstances_1);

    function _interopRequireDefault(obj) {
      return obj && obj.__esModule ? obj : {
        default: obj
      };
    } // Body focus trap see Issue #742


    var before = void 0,
        after = void 0,
        instances = [];
    /* eslint-disable no-console */

    /* istanbul ignore next */

    function resetState() {
      var _arr = [before, after];

      for (var _i = 0; _i < _arr.length; _i++) {
        var item = _arr[_i];
        if (!item) continue;
        item.parentNode && item.parentNode.removeChild(item);
      }

      before = after = null;
      instances = [];
    }
    /* istanbul ignore next */


    function log() {
      console.log("bodyTrap ----------");
      console.log(instances.length);
      var _arr2 = [before, after];

      for (var _i2 = 0; _i2 < _arr2.length; _i2++) {
        var item = _arr2[_i2];
        var check = item || {};
        console.log(check.nodeName, check.className, check.id);
      }

      console.log("edn bodyTrap ----------");
    }
    /* eslint-enable no-console */


    function focusContent() {
      if (instances.length === 0) {

        return;
      }

      instances[instances.length - 1].focusContent();
    }

    function bodyTrap(eventType, openInstances) {
      if (!before && !after) {
        before = document.createElement("div");
        before.setAttribute("data-react-modal-body-trap", "");
        before.style.position = "absolute";
        before.style.opacity = "0";
        before.setAttribute("tabindex", "0");
        before.addEventListener("focus", focusContent);
        after = before.cloneNode();
        after.addEventListener("focus", focusContent);
      }

      instances = openInstances;

      if (instances.length > 0) {
        // Add focus trap
        if (document.body.firstChild !== before) {
          document.body.insertBefore(before, document.body.firstChild);
        }

        if (document.body.lastChild !== after) {
          document.body.appendChild(after);
        }
      } else {
        // Remove focus trap
        if (before.parentElement) {
          before.parentElement.removeChild(before);
        }

        if (after.parentElement) {
          after.parentElement.removeChild(after);
        }
      }
    }

    _portalOpenInstances2.default.subscribe(bodyTrap);
  });

  var ModalPortal_1 = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });

    var _extends = Object.assign || function (target) {
      for (var i = 1; i < arguments.length; i++) {
        var source = arguments[i];

        for (var key in source) {
          if (Object.prototype.hasOwnProperty.call(source, key)) {
            target[key] = source[key];
          }
        }
      }

      return target;
    };

    var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) {
      return typeof obj;
    } : function (obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };

    var _createClass = function () {
      function defineProperties(target, props) {
        for (var i = 0; i < props.length; i++) {
          var descriptor = props[i];
          descriptor.enumerable = descriptor.enumerable || false;
          descriptor.configurable = true;
          if ("value" in descriptor) descriptor.writable = true;
          Object.defineProperty(target, descriptor.key, descriptor);
        }
      }

      return function (Constructor, protoProps, staticProps) {
        if (protoProps) defineProperties(Constructor.prototype, protoProps);
        if (staticProps) defineProperties(Constructor, staticProps);
        return Constructor;
      };
    }();

    var _propTypes2 = _interopRequireDefault(propTypes);

    var focusManager$1 = _interopRequireWildcard(focusManager);

    var _scopeTab2 = _interopRequireDefault(scopeTab_1);

    var ariaAppHider$1 = _interopRequireWildcard(ariaAppHider);

    var classList$1 = _interopRequireWildcard(classList);

    var _safeHTMLElement2 = _interopRequireDefault(safeHTMLElement);

    var _portalOpenInstances2 = _interopRequireDefault(portalOpenInstances_1);

    function _interopRequireWildcard(obj) {
      if (obj && obj.__esModule) {
        return obj;
      } else {
        var newObj = {};

        if (obj != null) {
          for (var key in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key];
          }
        }

        newObj.default = obj;
        return newObj;
      }
    }

    function _interopRequireDefault(obj) {
      return obj && obj.__esModule ? obj : {
        default: obj
      };
    }

    function _classCallCheck(instance, Constructor) {
      if (!(instance instanceof Constructor)) {
        throw new TypeError("Cannot call a class as a function");
      }
    }

    function _possibleConstructorReturn(self, call) {
      if (!self) {
        throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
      }

      return call && (typeof call === "object" || typeof call === "function") ? call : self;
    }

    function _inherits(subClass, superClass) {
      if (typeof superClass !== "function" && superClass !== null) {
        throw new TypeError("Super expression must either be null or a function, not " + typeof superClass);
      }

      subClass.prototype = Object.create(superClass && superClass.prototype, {
        constructor: {
          value: subClass,
          enumerable: false,
          writable: true,
          configurable: true
        }
      });
      if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass;
    } // so that our CSS is statically analyzable


    var CLASS_NAMES = {
      overlay: "ReactModal__Overlay",
      content: "ReactModal__Content"
    };
    var TAB_KEY = 9;
    var ESC_KEY = 27;
    var ariaHiddenInstances = 0;

    var ModalPortal = function (_Component) {
      _inherits(ModalPortal, _Component);

      function ModalPortal(props) {
        _classCallCheck(this, ModalPortal);

        var _this = _possibleConstructorReturn(this, (ModalPortal.__proto__ || Object.getPrototypeOf(ModalPortal)).call(this, props));

        _this.setOverlayRef = function (overlay) {
          _this.overlay = overlay;
          _this.props.overlayRef && _this.props.overlayRef(overlay);
        };

        _this.setContentRef = function (content) {
          _this.content = content;
          _this.props.contentRef && _this.props.contentRef(content);
        };

        _this.afterClose = function () {
          var _this$props = _this.props,
              appElement = _this$props.appElement,
              ariaHideApp = _this$props.ariaHideApp,
              htmlOpenClassName = _this$props.htmlOpenClassName,
              bodyOpenClassName = _this$props.bodyOpenClassName; // Remove classes.

          bodyOpenClassName && classList$1.remove(document.body, bodyOpenClassName);
          htmlOpenClassName && classList$1.remove(document.getElementsByTagName("html")[0], htmlOpenClassName); // Reset aria-hidden attribute if all modals have been removed

          if (ariaHideApp && ariaHiddenInstances > 0) {
            ariaHiddenInstances -= 1;

            if (ariaHiddenInstances === 0) {
              ariaAppHider$1.show(appElement);
            }
          }

          if (_this.props.shouldFocusAfterRender) {
            if (_this.props.shouldReturnFocusAfterClose) {
              focusManager$1.returnFocus(_this.props.preventScroll);
              focusManager$1.teardownScopedFocus();
            } else {
              focusManager$1.popWithoutFocus();
            }
          }

          if (_this.props.onAfterClose) {
            _this.props.onAfterClose();
          }

          _portalOpenInstances2.default.deregister(_this);
        };

        _this.open = function () {
          _this.beforeOpen();

          if (_this.state.afterOpen && _this.state.beforeClose) {
            clearTimeout(_this.closeTimer);

            _this.setState({
              beforeClose: false
            });
          } else {
            if (_this.props.shouldFocusAfterRender) {
              focusManager$1.setupScopedFocus(_this.node);
              focusManager$1.markForFocusLater();
            }

            _this.setState({
              isOpen: true
            }, function () {
              _this.openAnimationFrame = requestAnimationFrame(function () {
                _this.setState({
                  afterOpen: true
                });

                if (_this.props.isOpen && _this.props.onAfterOpen) {
                  _this.props.onAfterOpen({
                    overlayEl: _this.overlay,
                    contentEl: _this.content
                  });
                }
              });
            });
          }
        };

        _this.close = function () {
          if (_this.props.closeTimeoutMS > 0) {
            _this.closeWithTimeout();
          } else {
            _this.closeWithoutTimeout();
          }
        };

        _this.focusContent = function () {
          return _this.content && !_this.contentHasFocus() && _this.content.focus({
            preventScroll: true
          });
        };

        _this.closeWithTimeout = function () {
          var closesAt = Date.now() + _this.props.closeTimeoutMS;

          _this.setState({
            beforeClose: true,
            closesAt: closesAt
          }, function () {
            _this.closeTimer = setTimeout(_this.closeWithoutTimeout, _this.state.closesAt - Date.now());
          });
        };

        _this.closeWithoutTimeout = function () {
          _this.setState({
            beforeClose: false,
            isOpen: false,
            afterOpen: false,
            closesAt: null
          }, _this.afterClose);
        };

        _this.handleKeyDown = function (event) {
          if (event.keyCode === TAB_KEY) {
            (0, _scopeTab2.default)(_this.content, event);
          }

          if (_this.props.shouldCloseOnEsc && event.keyCode === ESC_KEY) {
            event.stopPropagation();

            _this.requestClose(event);
          }
        };

        _this.handleOverlayOnClick = function (event) {
          if (_this.shouldClose === null) {
            _this.shouldClose = true;
          }

          if (_this.shouldClose && _this.props.shouldCloseOnOverlayClick) {
            if (_this.ownerHandlesClose()) {
              _this.requestClose(event);
            } else {
              _this.focusContent();
            }
          }

          _this.shouldClose = null;
        };

        _this.handleContentOnMouseUp = function () {
          _this.shouldClose = false;
        };

        _this.handleOverlayOnMouseDown = function (event) {
          if (!_this.props.shouldCloseOnOverlayClick && event.target == _this.overlay) {
            event.preventDefault();
          }
        };

        _this.handleContentOnClick = function () {
          _this.shouldClose = false;
        };

        _this.handleContentOnMouseDown = function () {
          _this.shouldClose = false;
        };

        _this.requestClose = function (event) {
          return _this.ownerHandlesClose() && _this.props.onRequestClose(event);
        };

        _this.ownerHandlesClose = function () {
          return _this.props.onRequestClose;
        };

        _this.shouldBeClosed = function () {
          return !_this.state.isOpen && !_this.state.beforeClose;
        };

        _this.contentHasFocus = function () {
          return document.activeElement === _this.content || _this.content.contains(document.activeElement);
        };

        _this.buildClassName = function (which, additional) {
          var classNames = (typeof additional === "undefined" ? "undefined" : _typeof(additional)) === "object" ? additional : {
            base: CLASS_NAMES[which],
            afterOpen: CLASS_NAMES[which] + "--after-open",
            beforeClose: CLASS_NAMES[which] + "--before-close"
          };
          var className = classNames.base;

          if (_this.state.afterOpen) {
            className = className + " " + classNames.afterOpen;
          }

          if (_this.state.beforeClose) {
            className = className + " " + classNames.beforeClose;
          }

          return typeof additional === "string" && additional ? className + " " + additional : className;
        };

        _this.attributesFromObject = function (prefix, items) {
          return Object.keys(items).reduce(function (acc, name) {
            acc[prefix + "-" + name] = items[name];
            return acc;
          }, {});
        };

        _this.state = {
          afterOpen: false,
          beforeClose: false
        };
        _this.shouldClose = null;
        _this.moveFromContentToOverlay = null;
        return _this;
      }

      _createClass(ModalPortal, [{
        key: "componentDidMount",
        value: function componentDidMount() {
          if (this.props.isOpen) {
            this.open();
          }
        }
      }, {
        key: "componentDidUpdate",
        value: function componentDidUpdate(prevProps, prevState) {

          if (this.props.isOpen && !prevProps.isOpen) {
            this.open();
          } else if (!this.props.isOpen && prevProps.isOpen) {
            this.close();
          } // Focus only needs to be set once when the modal is being opened


          if (this.props.shouldFocusAfterRender && this.state.isOpen && !prevState.isOpen) {
            this.focusContent();
          }
        }
      }, {
        key: "componentWillUnmount",
        value: function componentWillUnmount() {
          if (this.state.isOpen) {
            this.afterClose();
          }

          clearTimeout(this.closeTimer);
          cancelAnimationFrame(this.openAnimationFrame);
        }
      }, {
        key: "beforeOpen",
        value: function beforeOpen() {
          var _props = this.props,
              appElement = _props.appElement,
              ariaHideApp = _props.ariaHideApp,
              htmlOpenClassName = _props.htmlOpenClassName,
              bodyOpenClassName = _props.bodyOpenClassName; // Add classes.

          bodyOpenClassName && classList$1.add(document.body, bodyOpenClassName);
          htmlOpenClassName && classList$1.add(document.getElementsByTagName("html")[0], htmlOpenClassName);

          if (ariaHideApp) {
            ariaHiddenInstances += 1;
            ariaAppHider$1.hide(appElement);
          }

          _portalOpenInstances2.default.register(this);
        } // Don't steal focus from inner elements

      }, {
        key: "render",
        value: function render() {
          var _props2 = this.props,
              id = _props2.id,
              className = _props2.className,
              overlayClassName = _props2.overlayClassName,
              defaultStyles = _props2.defaultStyles,
              children = _props2.children;
          var contentStyles = className ? {} : defaultStyles.content;
          var overlayStyles = overlayClassName ? {} : defaultStyles.overlay;

          if (this.shouldBeClosed()) {
            return null;
          }

          var overlayProps = {
            ref: this.setOverlayRef,
            className: this.buildClassName("overlay", overlayClassName),
            style: _extends({}, overlayStyles, this.props.style.overlay),
            onClick: this.handleOverlayOnClick,
            onMouseDown: this.handleOverlayOnMouseDown
          };

          var contentProps = _extends({
            id: id,
            ref: this.setContentRef,
            style: _extends({}, contentStyles, this.props.style.content),
            className: this.buildClassName("content", className),
            tabIndex: "-1",
            onKeyDown: this.handleKeyDown,
            onMouseDown: this.handleContentOnMouseDown,
            onMouseUp: this.handleContentOnMouseUp,
            onClick: this.handleContentOnClick,
            role: this.props.role,
            "aria-label": this.props.contentLabel
          }, this.attributesFromObject("aria", _extends({
            modal: true
          }, this.props.aria)), this.attributesFromObject("data", this.props.data || {}), {
            "data-testid": this.props.testId
          });

          var contentElement = this.props.contentElement(contentProps, children);
          return this.props.overlayElement(overlayProps, contentElement);
        }
      }]);

      return ModalPortal;
    }(react.Component);

    ModalPortal.defaultProps = {
      style: {
        overlay: {},
        content: {}
      },
      defaultStyles: {}
    };
    ModalPortal.propTypes = {
      isOpen: _propTypes2.default.bool.isRequired,
      defaultStyles: _propTypes2.default.shape({
        content: _propTypes2.default.object,
        overlay: _propTypes2.default.object
      }),
      style: _propTypes2.default.shape({
        content: _propTypes2.default.object,
        overlay: _propTypes2.default.object
      }),
      className: _propTypes2.default.oneOfType([_propTypes2.default.string, _propTypes2.default.object]),
      overlayClassName: _propTypes2.default.oneOfType([_propTypes2.default.string, _propTypes2.default.object]),
      bodyOpenClassName: _propTypes2.default.string,
      htmlOpenClassName: _propTypes2.default.string,
      ariaHideApp: _propTypes2.default.bool,
      appElement: _propTypes2.default.oneOfType([_propTypes2.default.instanceOf(_safeHTMLElement2.default), _propTypes2.default.instanceOf(safeHTMLElement.SafeHTMLCollection), _propTypes2.default.instanceOf(safeHTMLElement.SafeNodeList), _propTypes2.default.arrayOf(_propTypes2.default.instanceOf(_safeHTMLElement2.default))]),
      onAfterOpen: _propTypes2.default.func,
      onAfterClose: _propTypes2.default.func,
      onRequestClose: _propTypes2.default.func,
      closeTimeoutMS: _propTypes2.default.number,
      shouldFocusAfterRender: _propTypes2.default.bool,
      shouldCloseOnOverlayClick: _propTypes2.default.bool,
      shouldReturnFocusAfterClose: _propTypes2.default.bool,
      preventScroll: _propTypes2.default.bool,
      role: _propTypes2.default.string,
      contentLabel: _propTypes2.default.string,
      aria: _propTypes2.default.object,
      data: _propTypes2.default.object,
      children: _propTypes2.default.node,
      shouldCloseOnEsc: _propTypes2.default.bool,
      overlayRef: _propTypes2.default.func,
      contentRef: _propTypes2.default.func,
      id: _propTypes2.default.string,
      overlayElement: _propTypes2.default.func,
      contentElement: _propTypes2.default.func,
      testId: _propTypes2.default.string
    };
    exports.default = ModalPortal;
    module.exports = exports["default"];
  });

  /**
   * Copyright (c) 2013-present, Facebook, Inc.
   *
   * This source code is licensed under the MIT license found in the
   * LICENSE file in the root directory of this source tree.
   */
  function componentWillMount() {
    // Call this.constructor.gDSFP to support sub-classes.
    var state = this.constructor.getDerivedStateFromProps(this.props, this.state);

    if (state !== null && state !== undefined) {
      this.setState(state);
    }
  }

  function componentWillReceiveProps(nextProps) {
    // Call this.constructor.gDSFP to support sub-classes.
    // Use the setState() updater to ensure state isn't stale in certain edge cases.
    function updater(prevState) {
      var state = this.constructor.getDerivedStateFromProps(nextProps, prevState);
      return state !== null && state !== undefined ? state : null;
    } // Binding "this" is important for shallow renderer support.


    this.setState(updater.bind(this));
  }

  function componentWillUpdate(nextProps, nextState) {
    try {
      var prevProps = this.props;
      var prevState = this.state;
      this.props = nextProps;
      this.state = nextState;
      this.__reactInternalSnapshotFlag = true;
      this.__reactInternalSnapshot = this.getSnapshotBeforeUpdate(prevProps, prevState);
    } finally {
      this.props = prevProps;
      this.state = prevState;
    }
  } // React may warn about cWM/cWRP/cWU methods being deprecated.
  // Add a flag to suppress these warnings for this special case.


  componentWillMount.__suppressDeprecationWarning = true;
  componentWillReceiveProps.__suppressDeprecationWarning = true;
  componentWillUpdate.__suppressDeprecationWarning = true;

  function polyfill(Component) {
    var prototype = Component.prototype;

    if (!prototype || !prototype.isReactComponent) {
      throw new Error('Can only polyfill class components');
    }

    if (typeof Component.getDerivedStateFromProps !== 'function' && typeof prototype.getSnapshotBeforeUpdate !== 'function') {
      return Component;
    } // If new component APIs are defined, "unsafe" lifecycles won't be called.
    // Error if any of these lifecycles are present,
    // Because they would work differently between older and newer (16.3+) versions of React.


    var foundWillMountName = null;
    var foundWillReceivePropsName = null;
    var foundWillUpdateName = null;

    if (typeof prototype.componentWillMount === 'function') {
      foundWillMountName = 'componentWillMount';
    } else if (typeof prototype.UNSAFE_componentWillMount === 'function') {
      foundWillMountName = 'UNSAFE_componentWillMount';
    }

    if (typeof prototype.componentWillReceiveProps === 'function') {
      foundWillReceivePropsName = 'componentWillReceiveProps';
    } else if (typeof prototype.UNSAFE_componentWillReceiveProps === 'function') {
      foundWillReceivePropsName = 'UNSAFE_componentWillReceiveProps';
    }

    if (typeof prototype.componentWillUpdate === 'function') {
      foundWillUpdateName = 'componentWillUpdate';
    } else if (typeof prototype.UNSAFE_componentWillUpdate === 'function') {
      foundWillUpdateName = 'UNSAFE_componentWillUpdate';
    }

    if (foundWillMountName !== null || foundWillReceivePropsName !== null || foundWillUpdateName !== null) {
      var componentName = Component.displayName || Component.name;
      var newApiName = typeof Component.getDerivedStateFromProps === 'function' ? 'getDerivedStateFromProps()' : 'getSnapshotBeforeUpdate()';
      throw Error('Unsafe legacy lifecycles will not be called for components using new component APIs.\n\n' + componentName + ' uses ' + newApiName + ' but also contains the following legacy lifecycles:' + (foundWillMountName !== null ? '\n  ' + foundWillMountName : '') + (foundWillReceivePropsName !== null ? '\n  ' + foundWillReceivePropsName : '') + (foundWillUpdateName !== null ? '\n  ' + foundWillUpdateName : '') + '\n\nThe above lifecycles should be removed. Learn more about this warning here:\n' + 'https://fb.me/react-async-component-lifecycle-hooks');
    } // React <= 16.2 does not support static getDerivedStateFromProps.
    // As a workaround, use cWM and cWRP to invoke the new static lifecycle.
    // Newer versions of React will ignore these lifecycles if gDSFP exists.


    if (typeof Component.getDerivedStateFromProps === 'function') {
      prototype.componentWillMount = componentWillMount;
      prototype.componentWillReceiveProps = componentWillReceiveProps;
    } // React <= 16.2 does not support getSnapshotBeforeUpdate.
    // As a workaround, use cWU to invoke the new lifecycle.
    // Newer versions of React will ignore that lifecycle if gSBU exists.


    if (typeof prototype.getSnapshotBeforeUpdate === 'function') {
      if (typeof prototype.componentDidUpdate !== 'function') {
        throw new Error('Cannot polyfill getSnapshotBeforeUpdate() for components that do not define componentDidUpdate() on the prototype');
      }

      prototype.componentWillUpdate = componentWillUpdate;
      var componentDidUpdate = prototype.componentDidUpdate;

      prototype.componentDidUpdate = function componentDidUpdatePolyfill(prevProps, prevState, maybeSnapshot) {
        // 16.3+ will not execute our will-update method;
        // It will pass a snapshot value to did-update though.
        // Older versions will require our polyfilled will-update value.
        // We need to handle both cases, but can't just check for the presence of "maybeSnapshot",
        // Because for <= 15.x versions this might be a "prevContext" object.
        // We also can't just check "__reactInternalSnapshot",
        // Because get-snapshot might return a falsy value.
        // So check for the explicit __reactInternalSnapshotFlag flag to determine behavior.
        var snapshot = this.__reactInternalSnapshotFlag ? this.__reactInternalSnapshot : maybeSnapshot;
        componentDidUpdate.call(this, prevProps, prevState, snapshot);
      };
    }

    return Component;
  }

  var reactLifecyclesCompat_es = {
    __proto__: null,
    polyfill: polyfill
  };

  var Modal_1 = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.bodyOpenClassName = exports.portalClassName = undefined;

    var _extends = Object.assign || function (target) {
      for (var i = 1; i < arguments.length; i++) {
        var source = arguments[i];

        for (var key in source) {
          if (Object.prototype.hasOwnProperty.call(source, key)) {
            target[key] = source[key];
          }
        }
      }

      return target;
    };

    var _createClass = function () {
      function defineProperties(target, props) {
        for (var i = 0; i < props.length; i++) {
          var descriptor = props[i];
          descriptor.enumerable = descriptor.enumerable || false;
          descriptor.configurable = true;
          if ("value" in descriptor) descriptor.writable = true;
          Object.defineProperty(target, descriptor.key, descriptor);
        }
      }

      return function (Constructor, protoProps, staticProps) {
        if (protoProps) defineProperties(Constructor.prototype, protoProps);
        if (staticProps) defineProperties(Constructor, staticProps);
        return Constructor;
      };
    }();

    var _react2 = _interopRequireDefault(react);

    var _reactDom2 = _interopRequireDefault(reactDom);

    var _propTypes2 = _interopRequireDefault(propTypes);

    var _ModalPortal2 = _interopRequireDefault(ModalPortal_1);

    var ariaAppHider$1 = _interopRequireWildcard(ariaAppHider);

    var _safeHTMLElement2 = _interopRequireDefault(safeHTMLElement);

    function _interopRequireWildcard(obj) {
      if (obj && obj.__esModule) {
        return obj;
      } else {
        var newObj = {};

        if (obj != null) {
          for (var key in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key];
          }
        }

        newObj.default = obj;
        return newObj;
      }
    }

    function _interopRequireDefault(obj) {
      return obj && obj.__esModule ? obj : {
        default: obj
      };
    }

    function _classCallCheck(instance, Constructor) {
      if (!(instance instanceof Constructor)) {
        throw new TypeError("Cannot call a class as a function");
      }
    }

    function _possibleConstructorReturn(self, call) {
      if (!self) {
        throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
      }

      return call && (typeof call === "object" || typeof call === "function") ? call : self;
    }

    function _inherits(subClass, superClass) {
      if (typeof superClass !== "function" && superClass !== null) {
        throw new TypeError("Super expression must either be null or a function, not " + typeof superClass);
      }

      subClass.prototype = Object.create(superClass && superClass.prototype, {
        constructor: {
          value: subClass,
          enumerable: false,
          writable: true,
          configurable: true
        }
      });
      if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass;
    }

    var portalClassName = exports.portalClassName = "ReactModalPortal";
    var bodyOpenClassName = exports.bodyOpenClassName = "ReactModal__Body--open";
    var isReact16 = safeHTMLElement.canUseDOM && _reactDom2.default.createPortal !== undefined;

    var createHTMLElement = function createHTMLElement(name) {
      return document.createElement(name);
    };

    var getCreatePortal = function getCreatePortal() {
      return isReact16 ? _reactDom2.default.createPortal : _reactDom2.default.unstable_renderSubtreeIntoContainer;
    };

    function getParentElement(parentSelector) {
      return parentSelector();
    }

    var Modal = function (_Component) {
      _inherits(Modal, _Component);

      function Modal() {
        var _ref;

        var _temp, _this, _ret;

        _classCallCheck(this, Modal);

        for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
          args[_key] = arguments[_key];
        }

        return _ret = (_temp = (_this = _possibleConstructorReturn(this, (_ref = Modal.__proto__ || Object.getPrototypeOf(Modal)).call.apply(_ref, [this].concat(args))), _this), _this.removePortal = function () {
          !isReact16 && _reactDom2.default.unmountComponentAtNode(_this.node);
          var parent = getParentElement(_this.props.parentSelector);

          if (parent && parent.contains(_this.node)) {
            parent.removeChild(_this.node);
          } else {
            // eslint-disable-next-line no-console
            console.warn('React-Modal: "parentSelector" prop did not returned any DOM ' + "element. Make sure that the parent element is unmounted to " + "avoid any memory leaks.");
          }
        }, _this.portalRef = function (ref) {
          _this.portal = ref;
        }, _this.renderPortal = function (props) {
          var createPortal = getCreatePortal();
          var portal = createPortal(_this, _react2.default.createElement(_ModalPortal2.default, _extends({
            defaultStyles: Modal.defaultStyles
          }, props)), _this.node);

          _this.portalRef(portal);
        }, _temp), _possibleConstructorReturn(_this, _ret);
      }

      _createClass(Modal, [{
        key: "componentDidMount",
        value: function componentDidMount() {
          if (!safeHTMLElement.canUseDOM) return;

          if (!isReact16) {
            this.node = createHTMLElement("div");
          }

          this.node.className = this.props.portalClassName;
          var parent = getParentElement(this.props.parentSelector);
          parent.appendChild(this.node);
          !isReact16 && this.renderPortal(this.props);
        }
      }, {
        key: "getSnapshotBeforeUpdate",
        value: function getSnapshotBeforeUpdate(prevProps) {
          var prevParent = getParentElement(prevProps.parentSelector);
          var nextParent = getParentElement(this.props.parentSelector);
          return {
            prevParent: prevParent,
            nextParent: nextParent
          };
        }
      }, {
        key: "componentDidUpdate",
        value: function componentDidUpdate(prevProps, _, snapshot) {
          if (!safeHTMLElement.canUseDOM) return;
          var _props = this.props,
              isOpen = _props.isOpen,
              portalClassName = _props.portalClassName;

          if (prevProps.portalClassName !== portalClassName) {
            this.node.className = portalClassName;
          }

          var prevParent = snapshot.prevParent,
              nextParent = snapshot.nextParent;

          if (nextParent !== prevParent) {
            prevParent.removeChild(this.node);
            nextParent.appendChild(this.node);
          } // Stop unnecessary renders if modal is remaining closed


          if (!prevProps.isOpen && !isOpen) return;
          !isReact16 && this.renderPortal(this.props);
        }
      }, {
        key: "componentWillUnmount",
        value: function componentWillUnmount() {
          if (!safeHTMLElement.canUseDOM || !this.node || !this.portal) return;
          var state = this.portal.state;
          var now = Date.now();
          var closesAt = state.isOpen && this.props.closeTimeoutMS && (state.closesAt || now + this.props.closeTimeoutMS);

          if (closesAt) {
            if (!state.beforeClose) {
              this.portal.closeWithTimeout();
            }

            setTimeout(this.removePortal, closesAt - now);
          } else {
            this.removePortal();
          }
        }
      }, {
        key: "render",
        value: function render() {
          if (!safeHTMLElement.canUseDOM || !isReact16) {
            return null;
          }

          if (!this.node && isReact16) {
            this.node = createHTMLElement("div");
          }

          var createPortal = getCreatePortal();
          return createPortal(_react2.default.createElement(_ModalPortal2.default, _extends({
            ref: this.portalRef,
            defaultStyles: Modal.defaultStyles
          }, this.props)), this.node);
        }
      }], [{
        key: "setAppElement",
        value: function setAppElement(element) {
          ariaAppHider$1.setElement(element);
        }
        /* eslint-disable react/no-unused-prop-types */

        /* eslint-enable react/no-unused-prop-types */

      }]);

      return Modal;
    }(react.Component);

    Modal.propTypes = {
      isOpen: _propTypes2.default.bool.isRequired,
      style: _propTypes2.default.shape({
        content: _propTypes2.default.object,
        overlay: _propTypes2.default.object
      }),
      portalClassName: _propTypes2.default.string,
      bodyOpenClassName: _propTypes2.default.string,
      htmlOpenClassName: _propTypes2.default.string,
      className: _propTypes2.default.oneOfType([_propTypes2.default.string, _propTypes2.default.shape({
        base: _propTypes2.default.string.isRequired,
        afterOpen: _propTypes2.default.string.isRequired,
        beforeClose: _propTypes2.default.string.isRequired
      })]),
      overlayClassName: _propTypes2.default.oneOfType([_propTypes2.default.string, _propTypes2.default.shape({
        base: _propTypes2.default.string.isRequired,
        afterOpen: _propTypes2.default.string.isRequired,
        beforeClose: _propTypes2.default.string.isRequired
      })]),
      appElement: _propTypes2.default.oneOfType([_propTypes2.default.instanceOf(_safeHTMLElement2.default), _propTypes2.default.instanceOf(safeHTMLElement.SafeHTMLCollection), _propTypes2.default.instanceOf(safeHTMLElement.SafeNodeList), _propTypes2.default.arrayOf(_propTypes2.default.instanceOf(_safeHTMLElement2.default))]),
      onAfterOpen: _propTypes2.default.func,
      onRequestClose: _propTypes2.default.func,
      closeTimeoutMS: _propTypes2.default.number,
      ariaHideApp: _propTypes2.default.bool,
      shouldFocusAfterRender: _propTypes2.default.bool,
      shouldCloseOnOverlayClick: _propTypes2.default.bool,
      shouldReturnFocusAfterClose: _propTypes2.default.bool,
      preventScroll: _propTypes2.default.bool,
      parentSelector: _propTypes2.default.func,
      aria: _propTypes2.default.object,
      data: _propTypes2.default.object,
      role: _propTypes2.default.string,
      contentLabel: _propTypes2.default.string,
      shouldCloseOnEsc: _propTypes2.default.bool,
      overlayRef: _propTypes2.default.func,
      contentRef: _propTypes2.default.func,
      id: _propTypes2.default.string,
      overlayElement: _propTypes2.default.func,
      contentElement: _propTypes2.default.func
    };
    Modal.defaultProps = {
      isOpen: false,
      portalClassName: portalClassName,
      bodyOpenClassName: bodyOpenClassName,
      role: "dialog",
      ariaHideApp: true,
      closeTimeoutMS: 0,
      shouldFocusAfterRender: true,
      shouldCloseOnEsc: true,
      shouldCloseOnOverlayClick: true,
      shouldReturnFocusAfterClose: true,
      preventScroll: false,
      parentSelector: function parentSelector() {
        return document.body;
      },
      overlayElement: function overlayElement(props, contentEl) {
        return _react2.default.createElement("div", props, contentEl);
      },
      contentElement: function contentElement(props, children) {
        return _react2.default.createElement("div", props, children);
      }
    };
    Modal.defaultStyles = {
      overlay: {
        position: "fixed",
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        backgroundColor: "rgba(255, 255, 255, 0.75)"
      },
      content: {
        position: "absolute",
        top: "40px",
        left: "40px",
        right: "40px",
        bottom: "40px",
        border: "1px solid #ccc",
        background: "#fff",
        overflow: "auto",
        WebkitOverflowScrolling: "touch",
        borderRadius: "4px",
        outline: "none",
        padding: "20px"
      }
    };
    (0, reactLifecyclesCompat_es.polyfill)(Modal);

    exports.default = Modal;
  });

  var lib = createCommonjsModule(function (module, exports) {

    Object.defineProperty(exports, "__esModule", {
      value: true
    });

    var _Modal2 = _interopRequireDefault(Modal_1);

    function _interopRequireDefault(obj) {
      return obj && obj.__esModule ? obj : {
        default: obj
      };
    }

    exports.default = _Modal2.default;
    module.exports = exports["default"];
  });
  var Modal = /*@__PURE__*/unwrapExports(lib);

  var noScroll = createCommonjsModule(function (module) {
    (function (root) {
      var isOn = false;
      var scrollbarSize;
      var scrollTop;

      function getScrollbarSize() {
        if (typeof scrollbarSize !== 'undefined') return scrollbarSize;
        var doc = document.documentElement;
        var dummyScroller = document.createElement('div');
        dummyScroller.setAttribute('style', 'width:99px;height:99px;' + 'position:absolute;top:-9999px;overflow:scroll;');
        doc.appendChild(dummyScroller);
        scrollbarSize = dummyScroller.offsetWidth - dummyScroller.clientWidth;
        doc.removeChild(dummyScroller);
        return scrollbarSize;
      }

      function hasScrollbar() {
        return document.documentElement.scrollHeight > window.innerHeight;
      }

      function on(options) {
        if (typeof document === 'undefined' || isOn) return;
        var doc = document.documentElement;
        scrollTop = window.pageYOffset;

        if (hasScrollbar()) {
          doc.style.width = 'calc(100% - ' + getScrollbarSize() + 'px)';
        } else {
          doc.style.width = '100%';
        }

        doc.style.position = 'fixed';
        doc.style.top = -scrollTop + 'px';
        doc.style.overflow = 'hidden';
        isOn = true;
      }

      function off() {
        if (typeof document === 'undefined' || !isOn) return;
        var doc = document.documentElement;
        doc.style.width = '';
        doc.style.position = '';
        doc.style.top = '';
        doc.style.overflow = '';
        window.scroll(0, scrollTop);
        isOn = false;
      }

      function toggle() {
        if (isOn) {
          off();
          return;
        }

        on();
      }

      var noScroll = {
        on: on,
        off: off,
        toggle: toggle
      };

      {
        module.exports = noScroll;
      }
    })();
  });

  var s$2 = {"modal":"_D8SjB","content":"_ocM9x","contentScrollable":"_1GP2F","overlay":"_1yxCb","header":"_12LLh","closeButton":"_3YRro"};

  function CrossIcon(_ref) {
    var _ref$color = _ref.color,
        color = _ref$color === void 0 ? '#fff' : _ref$color,
        className = _ref.className;
    return /*#__PURE__*/react.createElement("svg", {
      className: className,
      width: "16",
      height: "16",
      viewBox: "0 0 16 16",
      fill: "none",
      xmlns: "http://www.w3.org/2000/svg"
    }, /*#__PURE__*/react.createElement("path", {
      d: "M12.5964 11.404L9.41445 8.22205L12.5964 5.04007C12.7732 4.86329 12.7732 4.50974 12.5964 4.33296L11.8893 3.62585C11.6904 3.42698 11.359 3.44908 11.1822 3.62585L8.00023 6.80783L4.81825 3.62585C4.61938 3.42698 4.28792 3.44908 4.11114 3.62585L3.40404 4.33296C3.20516 4.53183 3.20516 4.84119 3.40404 5.04007L6.58602 8.22205L3.40404 11.404C3.20516 11.6029 3.20517 11.9123 3.40404 12.1111L4.11115 12.8182C4.28792 12.995 4.61938 13.0171 4.81825 12.8182L8.00023 9.63626L11.1822 12.8182C11.359 12.995 11.6904 13.0171 11.8893 12.8182L12.5964 12.1111C12.7732 11.9344 12.7732 11.5808 12.5964 11.404Z",
      fill: color
    }));
  }

  var _excluded = ["children", "isOpen", "onClose", "className", "contentClassName", "scrollable"];

  var ControlledModal = function ControlledModal(_ref) {
    var _cx;

    var children = _ref.children,
        isOpen = _ref.isOpen,
        onClose = _ref.onClose,
        className = _ref.className,
        contentClassName = _ref.contentClassName,
        _ref$scrollable = _ref.scrollable,
        scrollable = _ref$scrollable === void 0 ? false : _ref$scrollable,
        props = _objectWithoutPropertiesLoose(_ref, _excluded);

    /* istanbul ignore next */
    Modal.setAppElement('body');
    return /*#__PURE__*/react.createElement(Modal, Object.assign({
      className: classnames(s$2.modal, className),
      overlayClassName: s$2.overlay,
      onAfterOpen: function onAfterOpen() {
        noScroll.on();
      },
      onAfterClose: function onAfterClose() {
        noScroll.off();
      },
      onRequestClose: onClose,
      isOpen: isOpen,
      shouldCloseOnOverlayClick: true,
      shouldCloseOnEsc: true
    }, props), /*#__PURE__*/react.createElement("div", {
      className: s$2.header
    }, /*#__PURE__*/react.createElement("button", {
      onClick: onClose,
      className: classnames(s$2.closeButton, STATIC_CUSTOMISATION_CLASSES.closeButton),
      "data-testid": "modal-close-button"
    }, /*#__PURE__*/react.createElement(CrossIcon, null))), /*#__PURE__*/react.createElement("div", {
      className: classnames(s$2.content, contentClassName, (_cx = {}, _cx[s$2.contentScrollable] = scrollable, _cx))
    }, children));
  };

  var reactResponsive = createCommonjsModule(function (module, exports) {
    (function webpackUniversalModuleDefinition(root, factory) {
      module.exports = factory(react);
    })(commonjsGlobal, __WEBPACK_EXTERNAL_MODULE_react__ => {
      return (
        /******/
        (() => {
          // webpackBootstrap

          /******/
          var __webpack_modules__ = {
            /***/
            "./node_modules/css-mediaquery/index.js":
            /*!**********************************************!*\
              !*** ./node_modules/css-mediaquery/index.js ***!
              \**********************************************/

            /***/
            (__unused_webpack_module, exports) => {
              /*
              Copyright (c) 2014, Yahoo! Inc. All rights reserved.
              Copyrights licensed under the New BSD License.
              See the accompanying LICENSE file for terms.
              */

              exports.match = matchQuery;
              exports.parse = parseQuery; // -----------------------------------------------------------------------------

              var RE_MEDIA_QUERY = /(?:(only|not)?\s*([^\s\(\)]+)(?:\s*and)?\s*)?(.+)?/i,
                  RE_MQ_EXPRESSION = /\(\s*([^\s\:\)]+)\s*(?:\:\s*([^\s\)]+))?\s*\)/,
                  RE_MQ_FEATURE = /^(?:(min|max)-)?(.+)/,
                  RE_LENGTH_UNIT = /(em|rem|px|cm|mm|in|pt|pc)?$/,
                  RE_RESOLUTION_UNIT = /(dpi|dpcm|dppx)?$/;

              function matchQuery(mediaQuery, values) {
                return parseQuery(mediaQuery).some(function (query) {
                  var inverse = query.inverse; // Either the parsed or specified `type` is "all", or the types must be
                  // equal for a match.

                  var typeMatch = query.type === 'all' || values.type === query.type; // Quit early when `type` doesn't match, but take "not" into account.

                  if (typeMatch && inverse || !(typeMatch || inverse)) {
                    return false;
                  }

                  var expressionsMatch = query.expressions.every(function (expression) {
                    var feature = expression.feature,
                        modifier = expression.modifier,
                        expValue = expression.value,
                        value = values[feature]; // Missing or falsy values don't match.

                    if (!value) {
                      return false;
                    }

                    switch (feature) {
                      case 'orientation':
                      case 'scan':
                        return value.toLowerCase() === expValue.toLowerCase();

                      case 'width':
                      case 'height':
                      case 'device-width':
                      case 'device-height':
                        expValue = toPx(expValue);
                        value = toPx(value);
                        break;

                      case 'resolution':
                        expValue = toDpi(expValue);
                        value = toDpi(value);
                        break;

                      case 'aspect-ratio':
                      case 'device-aspect-ratio':
                      case
                      /* Deprecated */
                      'device-pixel-ratio':
                        expValue = toDecimal(expValue);
                        value = toDecimal(value);
                        break;

                      case 'grid':
                      case 'color':
                      case 'color-index':
                      case 'monochrome':
                        expValue = parseInt(expValue, 10) || 1;
                        value = parseInt(value, 10) || 0;
                        break;
                    }

                    switch (modifier) {
                      case 'min':
                        return value >= expValue;

                      case 'max':
                        return value <= expValue;

                      default:
                        return value === expValue;
                    }
                  });
                  return expressionsMatch && !inverse || !expressionsMatch && inverse;
                });
              }

              function parseQuery(mediaQuery) {
                return mediaQuery.split(',').map(function (query) {
                  query = query.trim();
                  var captures = query.match(RE_MEDIA_QUERY),
                      modifier = captures[1],
                      type = captures[2],
                      expressions = captures[3] || '',
                      parsed = {};
                  parsed.inverse = !!modifier && modifier.toLowerCase() === 'not';
                  parsed.type = type ? type.toLowerCase() : 'all'; // Split expressions into a list.

                  expressions = expressions.match(/\([^\)]+\)/g) || [];
                  parsed.expressions = expressions.map(function (expression) {
                    var captures = expression.match(RE_MQ_EXPRESSION),
                        feature = captures[1].toLowerCase().match(RE_MQ_FEATURE);
                    return {
                      modifier: feature[1],
                      feature: feature[2],
                      value: captures[2]
                    };
                  });
                  return parsed;
                });
              } // -- Utilities ----------------------------------------------------------------


              function toDecimal(ratio) {
                var decimal = Number(ratio),
                    numbers;

                if (!decimal) {
                  numbers = ratio.match(/^(\d+)\s*\/\s*(\d+)$/);
                  decimal = numbers[1] / numbers[2];
                }

                return decimal;
              }

              function toDpi(resolution) {
                var value = parseFloat(resolution),
                    units = String(resolution).match(RE_RESOLUTION_UNIT)[1];

                switch (units) {
                  case 'dpcm':
                    return value / 2.54;

                  case 'dppx':
                    return value * 96;

                  default:
                    return value;
                }
              }

              function toPx(length) {
                var value = parseFloat(length),
                    units = String(length).match(RE_LENGTH_UNIT)[1];

                switch (units) {
                  case 'em':
                    return value * 16;

                  case 'rem':
                    return value * 16;

                  case 'cm':
                    return value * 96 / 2.54;

                  case 'mm':
                    return value * 96 / 2.54 / 10;

                  case 'in':
                    return value * 96;

                  case 'pt':
                    return value * 72;

                  case 'pc':
                    return value * 72 / 12;

                  default:
                    return value;
                }
              }
              /***/

            },

            /***/
            "./node_modules/hyphenate-style-name/index.js":
            /*!****************************************************!*\
              !*** ./node_modules/hyphenate-style-name/index.js ***!
              \****************************************************/

            /***/
            (__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

              __webpack_require__.r(__webpack_exports__);
              /* harmony export */


              __webpack_require__.d(__webpack_exports__, {
                /* harmony export */
                "default": () => __WEBPACK_DEFAULT_EXPORT__
                /* harmony export */

              });
              /* eslint-disable no-var, prefer-template */


              var uppercasePattern = /[A-Z]/g;
              var msPattern = /^ms-/;
              var cache = {};

              function toHyphenLower(match) {
                return '-' + match.toLowerCase();
              }

              function hyphenateStyleName(name) {
                if (cache.hasOwnProperty(name)) {
                  return cache[name];
                }

                var hName = name.replace(uppercasePattern, toHyphenLower);
                return cache[name] = msPattern.test(hName) ? '-' + hName : hName;
              }
              /* harmony default export */


              const __WEBPACK_DEFAULT_EXPORT__ = hyphenateStyleName;
              /***/
            },

            /***/
            "./node_modules/matchmediaquery/index.js":
            /*!***********************************************!*\
              !*** ./node_modules/matchmediaquery/index.js ***!
              \***********************************************/

            /***/
            (module, __unused_webpack_exports, __webpack_require__) => {

              var staticMatch = __webpack_require__(
              /*! css-mediaquery */
              "./node_modules/css-mediaquery/index.js").match;

              var dynamicMatch = typeof window !== 'undefined' ? window.matchMedia : null; // our fake MediaQueryList

              function Mql(query, values, forceStatic) {
                var self = this;

                if (dynamicMatch && !forceStatic) {
                  var mql = dynamicMatch.call(window, query);
                  this.matches = mql.matches;
                  this.media = mql.media; // TODO: is there a time it makes sense to remove this listener?

                  mql.addListener(update);
                } else {
                  this.matches = staticMatch(query, values);
                  this.media = query;
                }

                this.addListener = addListener;
                this.removeListener = removeListener;
                this.dispose = dispose;

                function addListener(listener) {
                  if (mql) {
                    mql.addListener(listener);
                  }
                }

                function removeListener(listener) {
                  if (mql) {
                    mql.removeListener(listener);
                  }
                } // update ourselves!


                function update(evt) {
                  self.matches = evt.matches;
                  self.media = evt.media;
                }

                function dispose() {
                  if (mql) {
                    mql.removeListener(update);
                  }
                }
              }

              function matchMedia(query, values, forceStatic) {
                return new Mql(query, values, forceStatic);
              }

              module.exports = matchMedia;
              /***/
            },

            /***/
            "./node_modules/object-assign/index.js":
            /*!*********************************************!*\
              !*** ./node_modules/object-assign/index.js ***!
              \*********************************************/

            /***/
            module => {
              /*
              object-assign
              (c) Sindre Sorhus
              @license MIT
              */

              /* eslint-disable no-unused-vars */

              var getOwnPropertySymbols = Object.getOwnPropertySymbols;
              var hasOwnProperty = Object.prototype.hasOwnProperty;
              var propIsEnumerable = Object.prototype.propertyIsEnumerable;

              function toObject(val) {
                if (val === null || val === undefined) {
                  throw new TypeError('Object.assign cannot be called with null or undefined');
                }

                return Object(val);
              }

              function shouldUseNative() {
                try {
                  if (!Object.assign) {
                    return false;
                  } // Detect buggy property enumeration order in older V8 versions.
                  // https://bugs.chromium.org/p/v8/issues/detail?id=4118


                  var test1 = new String('abc'); // eslint-disable-line no-new-wrappers

                  test1[5] = 'de';

                  if (Object.getOwnPropertyNames(test1)[0] === '5') {
                    return false;
                  } // https://bugs.chromium.org/p/v8/issues/detail?id=3056


                  var test2 = {};

                  for (var i = 0; i < 10; i++) {
                    test2['_' + String.fromCharCode(i)] = i;
                  }

                  var order2 = Object.getOwnPropertyNames(test2).map(function (n) {
                    return test2[n];
                  });

                  if (order2.join('') !== '0123456789') {
                    return false;
                  } // https://bugs.chromium.org/p/v8/issues/detail?id=3056


                  var test3 = {};
                  'abcdefghijklmnopqrst'.split('').forEach(function (letter) {
                    test3[letter] = letter;
                  });

                  if (Object.keys(Object.assign({}, test3)).join('') !== 'abcdefghijklmnopqrst') {
                    return false;
                  }

                  return true;
                } catch (err) {
                  // We don't expect any of the above to throw, but better to be safe.
                  return false;
                }
              }

              module.exports = shouldUseNative() ? Object.assign : function (target, source) {
                var from;
                var to = toObject(target);
                var symbols;

                for (var s = 1; s < arguments.length; s++) {
                  from = Object(arguments[s]);

                  for (var key in from) {
                    if (hasOwnProperty.call(from, key)) {
                      to[key] = from[key];
                    }
                  }

                  if (getOwnPropertySymbols) {
                    symbols = getOwnPropertySymbols(from);

                    for (var i = 0; i < symbols.length; i++) {
                      if (propIsEnumerable.call(from, symbols[i])) {
                        to[symbols[i]] = from[symbols[i]];
                      }
                    }
                  }
                }

                return to;
              };
              /***/
            },

            /***/
            "./node_modules/prop-types/checkPropTypes.js":
            /*!***************************************************!*\
              !*** ./node_modules/prop-types/checkPropTypes.js ***!
              \***************************************************/

            /***/
            (module, __unused_webpack_exports, __webpack_require__) => {
              /**
               * Copyright (c) 2013-present, Facebook, Inc.
               *
               * This source code is licensed under the MIT license found in the
               * LICENSE file in the root directory of this source tree.
               */

              var printWarning = function () {};

              {
                var ReactPropTypesSecret = __webpack_require__(
                /*! ./lib/ReactPropTypesSecret */
                "./node_modules/prop-types/lib/ReactPropTypesSecret.js");

                var loggedTypeFailures = {};

                var has = __webpack_require__(
                /*! ./lib/has */
                "./node_modules/prop-types/lib/has.js");

                printWarning = function (text) {
                  var message = 'Warning: ' + text;

                  if (typeof console !== 'undefined') {
                    console.error(message);
                  }

                  try {
                    // --- Welcome to debugging React ---
                    // This error was thrown as a convenience so that you can use this stack
                    // to find the callsite that caused this warning to fire.
                    throw new Error(message);
                  } catch (x) {
                    /**/
                  }
                };
              }
              /**
               * Assert that the values match with the type specs.
               * Error messages are memorized and will only be shown once.
               *
               * @param {object} typeSpecs Map of name to a ReactPropType
               * @param {object} values Runtime values that need to be type-checked
               * @param {string} location e.g. "prop", "context", "child context"
               * @param {string} componentName Name of the component for error messages.
               * @param {?Function} getStack Returns the component stack.
               * @private
               */


              function checkPropTypes(typeSpecs, values, location, componentName, getStack) {
                {
                  for (var typeSpecName in typeSpecs) {
                    if (has(typeSpecs, typeSpecName)) {
                      var error; // Prop type validation may throw. In case they do, we don't want to
                      // fail the render phase where it didn't fail before. So we log it.
                      // After these have been cleaned up, we'll let them throw.

                      try {
                        // This is intentionally an invariant that gets caught. It's the same
                        // behavior as without this statement except with a better message.
                        if (typeof typeSpecs[typeSpecName] !== 'function') {
                          var err = Error((componentName || 'React class') + ': ' + location + ' type `' + typeSpecName + '` is invalid; ' + 'it must be a function, usually from the `prop-types` package, but received `' + typeof typeSpecs[typeSpecName] + '`.' + 'This often happens because of typos such as `PropTypes.function` instead of `PropTypes.func`.');
                          err.name = 'Invariant Violation';
                          throw err;
                        }

                        error = typeSpecs[typeSpecName](values, typeSpecName, componentName, location, null, ReactPropTypesSecret);
                      } catch (ex) {
                        error = ex;
                      }

                      if (error && !(error instanceof Error)) {
                        printWarning((componentName || 'React class') + ': type specification of ' + location + ' `' + typeSpecName + '` is invalid; the type checker ' + 'function must return `null` or an `Error` but returned a ' + typeof error + '. ' + 'You may have forgotten to pass an argument to the type checker ' + 'creator (arrayOf, instanceOf, objectOf, oneOf, oneOfType, and ' + 'shape all require an argument).');
                      }

                      if (error instanceof Error && !(error.message in loggedTypeFailures)) {
                        // Only monitor this failure once because there tends to be a lot of the
                        // same error.
                        loggedTypeFailures[error.message] = true;
                        var stack = getStack ? getStack() : '';
                        printWarning('Failed ' + location + ' type: ' + error.message + (stack != null ? stack : ''));
                      }
                    }
                  }
                }
              }
              /**
               * Resets warning cache when testing.
               *
               * @private
               */


              checkPropTypes.resetWarningCache = function () {
                {
                  loggedTypeFailures = {};
                }
              };

              module.exports = checkPropTypes;
              /***/
            },

            /***/
            "./node_modules/prop-types/factoryWithTypeCheckers.js":
            /*!************************************************************!*\
              !*** ./node_modules/prop-types/factoryWithTypeCheckers.js ***!
              \************************************************************/

            /***/
            (module, __unused_webpack_exports, __webpack_require__) => {
              /**
               * Copyright (c) 2013-present, Facebook, Inc.
               *
               * This source code is licensed under the MIT license found in the
               * LICENSE file in the root directory of this source tree.
               */

              var ReactIs = __webpack_require__(
              /*! react-is */
              "./node_modules/react-is/index.js");

              var assign = __webpack_require__(
              /*! object-assign */
              "./node_modules/object-assign/index.js");

              var ReactPropTypesSecret = __webpack_require__(
              /*! ./lib/ReactPropTypesSecret */
              "./node_modules/prop-types/lib/ReactPropTypesSecret.js");

              var has = __webpack_require__(
              /*! ./lib/has */
              "./node_modules/prop-types/lib/has.js");

              var checkPropTypes = __webpack_require__(
              /*! ./checkPropTypes */
              "./node_modules/prop-types/checkPropTypes.js");

              var printWarning = function () {};

              {
                printWarning = function (text) {
                  var message = 'Warning: ' + text;

                  if (typeof console !== 'undefined') {
                    console.error(message);
                  }

                  try {
                    // --- Welcome to debugging React ---
                    // This error was thrown as a convenience so that you can use this stack
                    // to find the callsite that caused this warning to fire.
                    throw new Error(message);
                  } catch (x) {}
                };
              }

              function emptyFunctionThatReturnsNull() {
                return null;
              }

              module.exports = function (isValidElement, throwOnDirectAccess) {
                /* global Symbol */
                var ITERATOR_SYMBOL = typeof Symbol === 'function' && Symbol.iterator;
                var FAUX_ITERATOR_SYMBOL = '@@iterator'; // Before Symbol spec.

                /**
                 * Returns the iterator method function contained on the iterable object.
                 *
                 * Be sure to invoke the function with the iterable as context:
                 *
                 *     var iteratorFn = getIteratorFn(myIterable);
                 *     if (iteratorFn) {
                 *       var iterator = iteratorFn.call(myIterable);
                 *       ...
                 *     }
                 *
                 * @param {?object} maybeIterable
                 * @return {?function}
                 */

                function getIteratorFn(maybeIterable) {
                  var iteratorFn = maybeIterable && (ITERATOR_SYMBOL && maybeIterable[ITERATOR_SYMBOL] || maybeIterable[FAUX_ITERATOR_SYMBOL]);

                  if (typeof iteratorFn === 'function') {
                    return iteratorFn;
                  }
                }
                /**
                 * Collection of methods that allow declaration and validation of props that are
                 * supplied to React components. Example usage:
                 *
                 *   var Props = require('ReactPropTypes');
                 *   var MyArticle = React.createClass({
                 *     propTypes: {
                 *       // An optional string prop named "description".
                 *       description: Props.string,
                 *
                 *       // A required enum prop named "category".
                 *       category: Props.oneOf(['News','Photos']).isRequired,
                 *
                 *       // A prop named "dialog" that requires an instance of Dialog.
                 *       dialog: Props.instanceOf(Dialog).isRequired
                 *     },
                 *     render: function() { ... }
                 *   });
                 *
                 * A more formal specification of how these methods are used:
                 *
                 *   type := array|bool|func|object|number|string|oneOf([...])|instanceOf(...)
                 *   decl := ReactPropTypes.{type}(.isRequired)?
                 *
                 * Each and every declaration produces a function with the same signature. This
                 * allows the creation of custom validation functions. For example:
                 *
                 *  var MyLink = React.createClass({
                 *    propTypes: {
                 *      // An optional string or URI prop named "href".
                 *      href: function(props, propName, componentName) {
                 *        var propValue = props[propName];
                 *        if (propValue != null && typeof propValue !== 'string' &&
                 *            !(propValue instanceof URI)) {
                 *          return new Error(
                 *            'Expected a string or an URI for ' + propName + ' in ' +
                 *            componentName
                 *          );
                 *        }
                 *      }
                 *    },
                 *    render: function() {...}
                 *  });
                 *
                 * @internal
                 */


                var ANONYMOUS = '<<anonymous>>'; // Important!
                // Keep this list in sync with production version in `./factoryWithThrowingShims.js`.

                var ReactPropTypes = {
                  array: createPrimitiveTypeChecker('array'),
                  bigint: createPrimitiveTypeChecker('bigint'),
                  bool: createPrimitiveTypeChecker('boolean'),
                  func: createPrimitiveTypeChecker('function'),
                  number: createPrimitiveTypeChecker('number'),
                  object: createPrimitiveTypeChecker('object'),
                  string: createPrimitiveTypeChecker('string'),
                  symbol: createPrimitiveTypeChecker('symbol'),
                  any: createAnyTypeChecker(),
                  arrayOf: createArrayOfTypeChecker,
                  element: createElementTypeChecker(),
                  elementType: createElementTypeTypeChecker(),
                  instanceOf: createInstanceTypeChecker,
                  node: createNodeChecker(),
                  objectOf: createObjectOfTypeChecker,
                  oneOf: createEnumTypeChecker,
                  oneOfType: createUnionTypeChecker,
                  shape: createShapeTypeChecker,
                  exact: createStrictShapeTypeChecker
                };
                /**
                 * inlined Object.is polyfill to avoid requiring consumers ship their own
                 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/is
                 */

                /*eslint-disable no-self-compare*/

                function is(x, y) {
                  // SameValue algorithm
                  if (x === y) {
                    // Steps 1-5, 7-10
                    // Steps 6.b-6.e: +0 != -0
                    return x !== 0 || 1 / x === 1 / y;
                  } else {
                    // Step 6.a: NaN == NaN
                    return x !== x && y !== y;
                  }
                }
                /*eslint-enable no-self-compare*/

                /**
                 * We use an Error-like object for backward compatibility as people may call
                 * PropTypes directly and inspect their output. However, we don't use real
                 * Errors anymore. We don't inspect their stack anyway, and creating them
                 * is prohibitively expensive if they are created too often, such as what
                 * happens in oneOfType() for any type before the one that matched.
                 */


                function PropTypeError(message, data) {
                  this.message = message;
                  this.data = data && typeof data === 'object' ? data : {};
                  this.stack = '';
                } // Make `instanceof Error` still work for returned errors.


                PropTypeError.prototype = Error.prototype;

                function createChainableTypeChecker(validate) {
                  {
                    var manualPropTypeCallCache = {};
                    var manualPropTypeWarningCount = 0;
                  }

                  function checkType(isRequired, props, propName, componentName, location, propFullName, secret) {
                    componentName = componentName || ANONYMOUS;
                    propFullName = propFullName || propName;

                    if (secret !== ReactPropTypesSecret) {
                      if (throwOnDirectAccess) {
                        // New behavior only for users of `prop-types` package
                        var err = new Error('Calling PropTypes validators directly is not supported by the `prop-types` package. ' + 'Use `PropTypes.checkPropTypes()` to call them. ' + 'Read more at http://fb.me/use-check-prop-types');
                        err.name = 'Invariant Violation';
                        throw err;
                      } else if ( typeof console !== 'undefined') {
                        // Old behavior for people using React.PropTypes
                        var cacheKey = componentName + ':' + propName;

                        if (!manualPropTypeCallCache[cacheKey] && // Avoid spamming the console because they are often not actionable except for lib authors
                        manualPropTypeWarningCount < 3) {
                          printWarning('You are manually calling a React.PropTypes validation ' + 'function for the `' + propFullName + '` prop on `' + componentName + '`. This is deprecated ' + 'and will throw in the standalone `prop-types` package. ' + 'You may be seeing this warning due to a third-party PropTypes ' + 'library. See https://fb.me/react-warning-dont-call-proptypes ' + 'for details.');
                          manualPropTypeCallCache[cacheKey] = true;
                          manualPropTypeWarningCount++;
                        }
                      }
                    }

                    if (props[propName] == null) {
                      if (isRequired) {
                        if (props[propName] === null) {
                          return new PropTypeError('The ' + location + ' `' + propFullName + '` is marked as required ' + ('in `' + componentName + '`, but its value is `null`.'));
                        }

                        return new PropTypeError('The ' + location + ' `' + propFullName + '` is marked as required in ' + ('`' + componentName + '`, but its value is `undefined`.'));
                      }

                      return null;
                    } else {
                      return validate(props, propName, componentName, location, propFullName);
                    }
                  }

                  var chainedCheckType = checkType.bind(null, false);
                  chainedCheckType.isRequired = checkType.bind(null, true);
                  return chainedCheckType;
                }

                function createPrimitiveTypeChecker(expectedType) {
                  function validate(props, propName, componentName, location, propFullName, secret) {
                    var propValue = props[propName];
                    var propType = getPropType(propValue);

                    if (propType !== expectedType) {
                      // `propValue` being instance of, say, date/regexp, pass the 'object'
                      // check, but we can offer a more precise error message here rather than
                      // 'of type `object`'.
                      var preciseType = getPreciseType(propValue);
                      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + preciseType + '` supplied to `' + componentName + '`, expected ') + ('`' + expectedType + '`.'), {
                        expectedType: expectedType
                      });
                    }

                    return null;
                  }

                  return createChainableTypeChecker(validate);
                }

                function createAnyTypeChecker() {
                  return createChainableTypeChecker(emptyFunctionThatReturnsNull);
                }

                function createArrayOfTypeChecker(typeChecker) {
                  function validate(props, propName, componentName, location, propFullName) {
                    if (typeof typeChecker !== 'function') {
                      return new PropTypeError('Property `' + propFullName + '` of component `' + componentName + '` has invalid PropType notation inside arrayOf.');
                    }

                    var propValue = props[propName];

                    if (!Array.isArray(propValue)) {
                      var propType = getPropType(propValue);
                      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + propType + '` supplied to `' + componentName + '`, expected an array.'));
                    }

                    for (var i = 0; i < propValue.length; i++) {
                      var error = typeChecker(propValue, i, componentName, location, propFullName + '[' + i + ']', ReactPropTypesSecret);

                      if (error instanceof Error) {
                        return error;
                      }
                    }

                    return null;
                  }

                  return createChainableTypeChecker(validate);
                }

                function createElementTypeChecker() {
                  function validate(props, propName, componentName, location, propFullName) {
                    var propValue = props[propName];

                    if (!isValidElement(propValue)) {
                      var propType = getPropType(propValue);
                      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + propType + '` supplied to `' + componentName + '`, expected a single ReactElement.'));
                    }

                    return null;
                  }

                  return createChainableTypeChecker(validate);
                }

                function createElementTypeTypeChecker() {
                  function validate(props, propName, componentName, location, propFullName) {
                    var propValue = props[propName];

                    if (!ReactIs.isValidElementType(propValue)) {
                      var propType = getPropType(propValue);
                      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + propType + '` supplied to `' + componentName + '`, expected a single ReactElement type.'));
                    }

                    return null;
                  }

                  return createChainableTypeChecker(validate);
                }

                function createInstanceTypeChecker(expectedClass) {
                  function validate(props, propName, componentName, location, propFullName) {
                    if (!(props[propName] instanceof expectedClass)) {
                      var expectedClassName = expectedClass.name || ANONYMOUS;
                      var actualClassName = getClassName(props[propName]);
                      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + actualClassName + '` supplied to `' + componentName + '`, expected ') + ('instance of `' + expectedClassName + '`.'));
                    }

                    return null;
                  }

                  return createChainableTypeChecker(validate);
                }

                function createEnumTypeChecker(expectedValues) {
                  if (!Array.isArray(expectedValues)) {
                    {
                      if (arguments.length > 1) {
                        printWarning('Invalid arguments supplied to oneOf, expected an array, got ' + arguments.length + ' arguments. ' + 'A common mistake is to write oneOf(x, y, z) instead of oneOf([x, y, z]).');
                      } else {
                        printWarning('Invalid argument supplied to oneOf, expected an array.');
                      }
                    }

                    return emptyFunctionThatReturnsNull;
                  }

                  function validate(props, propName, componentName, location, propFullName) {
                    var propValue = props[propName];

                    for (var i = 0; i < expectedValues.length; i++) {
                      if (is(propValue, expectedValues[i])) {
                        return null;
                      }
                    }

                    var valuesString = JSON.stringify(expectedValues, function replacer(key, value) {
                      var type = getPreciseType(value);

                      if (type === 'symbol') {
                        return String(value);
                      }

                      return value;
                    });
                    return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of value `' + String(propValue) + '` ' + ('supplied to `' + componentName + '`, expected one of ' + valuesString + '.'));
                  }

                  return createChainableTypeChecker(validate);
                }

                function createObjectOfTypeChecker(typeChecker) {
                  function validate(props, propName, componentName, location, propFullName) {
                    if (typeof typeChecker !== 'function') {
                      return new PropTypeError('Property `' + propFullName + '` of component `' + componentName + '` has invalid PropType notation inside objectOf.');
                    }

                    var propValue = props[propName];
                    var propType = getPropType(propValue);

                    if (propType !== 'object') {
                      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type ' + ('`' + propType + '` supplied to `' + componentName + '`, expected an object.'));
                    }

                    for (var key in propValue) {
                      if (has(propValue, key)) {
                        var error = typeChecker(propValue, key, componentName, location, propFullName + '.' + key, ReactPropTypesSecret);

                        if (error instanceof Error) {
                          return error;
                        }
                      }
                    }

                    return null;
                  }

                  return createChainableTypeChecker(validate);
                }

                function createUnionTypeChecker(arrayOfTypeCheckers) {
                  if (!Array.isArray(arrayOfTypeCheckers)) {
                     printWarning('Invalid argument supplied to oneOfType, expected an instance of array.') ;
                    return emptyFunctionThatReturnsNull;
                  }

                  for (var i = 0; i < arrayOfTypeCheckers.length; i++) {
                    var checker = arrayOfTypeCheckers[i];

                    if (typeof checker !== 'function') {
                      printWarning('Invalid argument supplied to oneOfType. Expected an array of check functions, but ' + 'received ' + getPostfixForTypeWarning(checker) + ' at index ' + i + '.');
                      return emptyFunctionThatReturnsNull;
                    }
                  }

                  function validate(props, propName, componentName, location, propFullName) {
                    var expectedTypes = [];

                    for (var i = 0; i < arrayOfTypeCheckers.length; i++) {
                      var checker = arrayOfTypeCheckers[i];
                      var checkerResult = checker(props, propName, componentName, location, propFullName, ReactPropTypesSecret);

                      if (checkerResult == null) {
                        return null;
                      }

                      if (checkerResult.data && has(checkerResult.data, 'expectedType')) {
                        expectedTypes.push(checkerResult.data.expectedType);
                      }
                    }

                    var expectedTypesMessage = expectedTypes.length > 0 ? ', expected one of type [' + expectedTypes.join(', ') + ']' : '';
                    return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` supplied to ' + ('`' + componentName + '`' + expectedTypesMessage + '.'));
                  }

                  return createChainableTypeChecker(validate);
                }

                function createNodeChecker() {
                  function validate(props, propName, componentName, location, propFullName) {
                    if (!isNode(props[propName])) {
                      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` supplied to ' + ('`' + componentName + '`, expected a ReactNode.'));
                    }

                    return null;
                  }

                  return createChainableTypeChecker(validate);
                }

                function invalidValidatorError(componentName, location, propFullName, key, type) {
                  return new PropTypeError((componentName || 'React class') + ': ' + location + ' type `' + propFullName + '.' + key + '` is invalid; ' + 'it must be a function, usually from the `prop-types` package, but received `' + type + '`.');
                }

                function createShapeTypeChecker(shapeTypes) {
                  function validate(props, propName, componentName, location, propFullName) {
                    var propValue = props[propName];
                    var propType = getPropType(propValue);

                    if (propType !== 'object') {
                      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type `' + propType + '` ' + ('supplied to `' + componentName + '`, expected `object`.'));
                    }

                    for (var key in shapeTypes) {
                      var checker = shapeTypes[key];

                      if (typeof checker !== 'function') {
                        return invalidValidatorError(componentName, location, propFullName, key, getPreciseType(checker));
                      }

                      var error = checker(propValue, key, componentName, location, propFullName + '.' + key, ReactPropTypesSecret);

                      if (error) {
                        return error;
                      }
                    }

                    return null;
                  }

                  return createChainableTypeChecker(validate);
                }

                function createStrictShapeTypeChecker(shapeTypes) {
                  function validate(props, propName, componentName, location, propFullName) {
                    var propValue = props[propName];
                    var propType = getPropType(propValue);

                    if (propType !== 'object') {
                      return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` of type `' + propType + '` ' + ('supplied to `' + componentName + '`, expected `object`.'));
                    } // We need to check all keys in case some are required but missing from props.


                    var allKeys = assign({}, props[propName], shapeTypes);

                    for (var key in allKeys) {
                      var checker = shapeTypes[key];

                      if (has(shapeTypes, key) && typeof checker !== 'function') {
                        return invalidValidatorError(componentName, location, propFullName, key, getPreciseType(checker));
                      }

                      if (!checker) {
                        return new PropTypeError('Invalid ' + location + ' `' + propFullName + '` key `' + key + '` supplied to `' + componentName + '`.' + '\nBad object: ' + JSON.stringify(props[propName], null, '  ') + '\nValid keys: ' + JSON.stringify(Object.keys(shapeTypes), null, '  '));
                      }

                      var error = checker(propValue, key, componentName, location, propFullName + '.' + key, ReactPropTypesSecret);

                      if (error) {
                        return error;
                      }
                    }

                    return null;
                  }

                  return createChainableTypeChecker(validate);
                }

                function isNode(propValue) {
                  switch (typeof propValue) {
                    case 'number':
                    case 'string':
                    case 'undefined':
                      return true;

                    case 'boolean':
                      return !propValue;

                    case 'object':
                      if (Array.isArray(propValue)) {
                        return propValue.every(isNode);
                      }

                      if (propValue === null || isValidElement(propValue)) {
                        return true;
                      }

                      var iteratorFn = getIteratorFn(propValue);

                      if (iteratorFn) {
                        var iterator = iteratorFn.call(propValue);
                        var step;

                        if (iteratorFn !== propValue.entries) {
                          while (!(step = iterator.next()).done) {
                            if (!isNode(step.value)) {
                              return false;
                            }
                          }
                        } else {
                          // Iterator will provide entry [k,v] tuples rather than values.
                          while (!(step = iterator.next()).done) {
                            var entry = step.value;

                            if (entry) {
                              if (!isNode(entry[1])) {
                                return false;
                              }
                            }
                          }
                        }
                      } else {
                        return false;
                      }

                      return true;

                    default:
                      return false;
                  }
                }

                function isSymbol(propType, propValue) {
                  // Native Symbol.
                  if (propType === 'symbol') {
                    return true;
                  } // falsy value can't be a Symbol


                  if (!propValue) {
                    return false;
                  } // 19.4.3.5 Symbol.prototype[@@toStringTag] === 'Symbol'


                  if (propValue['@@toStringTag'] === 'Symbol') {
                    return true;
                  } // Fallback for non-spec compliant Symbols which are polyfilled.


                  if (typeof Symbol === 'function' && propValue instanceof Symbol) {
                    return true;
                  }

                  return false;
                } // Equivalent of `typeof` but with special handling for array and regexp.


                function getPropType(propValue) {
                  var propType = typeof propValue;

                  if (Array.isArray(propValue)) {
                    return 'array';
                  }

                  if (propValue instanceof RegExp) {
                    // Old webkits (at least until Android 4.0) return 'function' rather than
                    // 'object' for typeof a RegExp. We'll normalize this here so that /bla/
                    // passes PropTypes.object.
                    return 'object';
                  }

                  if (isSymbol(propType, propValue)) {
                    return 'symbol';
                  }

                  return propType;
                } // This handles more types than `getPropType`. Only used for error messages.
                // See `createPrimitiveTypeChecker`.


                function getPreciseType(propValue) {
                  if (typeof propValue === 'undefined' || propValue === null) {
                    return '' + propValue;
                  }

                  var propType = getPropType(propValue);

                  if (propType === 'object') {
                    if (propValue instanceof Date) {
                      return 'date';
                    } else if (propValue instanceof RegExp) {
                      return 'regexp';
                    }
                  }

                  return propType;
                } // Returns a string that is postfixed to a warning about an invalid type.
                // For example, "undefined" or "of type array"


                function getPostfixForTypeWarning(value) {
                  var type = getPreciseType(value);

                  switch (type) {
                    case 'array':
                    case 'object':
                      return 'an ' + type;

                    case 'boolean':
                    case 'date':
                    case 'regexp':
                      return 'a ' + type;

                    default:
                      return type;
                  }
                } // Returns class name of the object, if any.


                function getClassName(propValue) {
                  if (!propValue.constructor || !propValue.constructor.name) {
                    return ANONYMOUS;
                  }

                  return propValue.constructor.name;
                }

                ReactPropTypes.checkPropTypes = checkPropTypes;
                ReactPropTypes.resetWarningCache = checkPropTypes.resetWarningCache;
                ReactPropTypes.PropTypes = ReactPropTypes;
                return ReactPropTypes;
              };
              /***/

            },

            /***/
            "./node_modules/prop-types/index.js":
            /*!******************************************!*\
              !*** ./node_modules/prop-types/index.js ***!
              \******************************************/

            /***/
            (module, __unused_webpack_exports, __webpack_require__) => {
              /**
               * Copyright (c) 2013-present, Facebook, Inc.
               *
               * This source code is licensed under the MIT license found in the
               * LICENSE file in the root directory of this source tree.
               */
              {
                var ReactIs = __webpack_require__(
                /*! react-is */
                "./node_modules/react-is/index.js"); // By explicitly using `prop-types` you are opting into new development behavior.
                // http://fb.me/prop-types-in-prod


                var throwOnDirectAccess = true;
                module.exports = __webpack_require__(
                /*! ./factoryWithTypeCheckers */
                "./node_modules/prop-types/factoryWithTypeCheckers.js")(ReactIs.isElement, throwOnDirectAccess);
              }
              /***/

            },

            /***/
            "./node_modules/prop-types/lib/ReactPropTypesSecret.js":
            /*!*************************************************************!*\
              !*** ./node_modules/prop-types/lib/ReactPropTypesSecret.js ***!
              \*************************************************************/

            /***/
            module => {
              /**
               * Copyright (c) 2013-present, Facebook, Inc.
               *
               * This source code is licensed under the MIT license found in the
               * LICENSE file in the root directory of this source tree.
               */

              var ReactPropTypesSecret = 'SECRET_DO_NOT_PASS_THIS_OR_YOU_WILL_BE_FIRED';
              module.exports = ReactPropTypesSecret;
              /***/
            },

            /***/
            "./node_modules/prop-types/lib/has.js":
            /*!********************************************!*\
              !*** ./node_modules/prop-types/lib/has.js ***!
              \********************************************/

            /***/
            module => {
              module.exports = Function.call.bind(Object.prototype.hasOwnProperty);
              /***/
            },

            /***/
            "./node_modules/react-is/cjs/react-is.development.js":
            /*!***********************************************************!*\
              !*** ./node_modules/react-is/cjs/react-is.development.js ***!
              \***********************************************************/

            /***/
            (__unused_webpack_module, exports) => {
              /** @license React v16.13.1
               * react-is.development.js
               *
               * Copyright (c) Facebook, Inc. and its affiliates.
               *
               * This source code is licensed under the MIT license found in the
               * LICENSE file in the root directory of this source tree.
               */

              {
                (function () {
                  // nor polyfill, then a plain number is used for performance.

                  var hasSymbol = typeof Symbol === 'function' && Symbol.for;
                  var REACT_ELEMENT_TYPE = hasSymbol ? Symbol.for('react.element') : 0xeac7;
                  var REACT_PORTAL_TYPE = hasSymbol ? Symbol.for('react.portal') : 0xeaca;
                  var REACT_FRAGMENT_TYPE = hasSymbol ? Symbol.for('react.fragment') : 0xeacb;
                  var REACT_STRICT_MODE_TYPE = hasSymbol ? Symbol.for('react.strict_mode') : 0xeacc;
                  var REACT_PROFILER_TYPE = hasSymbol ? Symbol.for('react.profiler') : 0xead2;
                  var REACT_PROVIDER_TYPE = hasSymbol ? Symbol.for('react.provider') : 0xeacd;
                  var REACT_CONTEXT_TYPE = hasSymbol ? Symbol.for('react.context') : 0xeace; // TODO: We don't use AsyncMode or ConcurrentMode anymore. They were temporary
                  // (unstable) APIs that have been removed. Can we remove the symbols?

                  var REACT_ASYNC_MODE_TYPE = hasSymbol ? Symbol.for('react.async_mode') : 0xeacf;
                  var REACT_CONCURRENT_MODE_TYPE = hasSymbol ? Symbol.for('react.concurrent_mode') : 0xeacf;
                  var REACT_FORWARD_REF_TYPE = hasSymbol ? Symbol.for('react.forward_ref') : 0xead0;
                  var REACT_SUSPENSE_TYPE = hasSymbol ? Symbol.for('react.suspense') : 0xead1;
                  var REACT_SUSPENSE_LIST_TYPE = hasSymbol ? Symbol.for('react.suspense_list') : 0xead8;
                  var REACT_MEMO_TYPE = hasSymbol ? Symbol.for('react.memo') : 0xead3;
                  var REACT_LAZY_TYPE = hasSymbol ? Symbol.for('react.lazy') : 0xead4;
                  var REACT_BLOCK_TYPE = hasSymbol ? Symbol.for('react.block') : 0xead9;
                  var REACT_FUNDAMENTAL_TYPE = hasSymbol ? Symbol.for('react.fundamental') : 0xead5;
                  var REACT_RESPONDER_TYPE = hasSymbol ? Symbol.for('react.responder') : 0xead6;
                  var REACT_SCOPE_TYPE = hasSymbol ? Symbol.for('react.scope') : 0xead7;

                  function isValidElementType(type) {
                    return typeof type === 'string' || typeof type === 'function' || // Note: its typeof might be other than 'symbol' or 'number' if it's a polyfill.
                    type === REACT_FRAGMENT_TYPE || type === REACT_CONCURRENT_MODE_TYPE || type === REACT_PROFILER_TYPE || type === REACT_STRICT_MODE_TYPE || type === REACT_SUSPENSE_TYPE || type === REACT_SUSPENSE_LIST_TYPE || typeof type === 'object' && type !== null && (type.$$typeof === REACT_LAZY_TYPE || type.$$typeof === REACT_MEMO_TYPE || type.$$typeof === REACT_PROVIDER_TYPE || type.$$typeof === REACT_CONTEXT_TYPE || type.$$typeof === REACT_FORWARD_REF_TYPE || type.$$typeof === REACT_FUNDAMENTAL_TYPE || type.$$typeof === REACT_RESPONDER_TYPE || type.$$typeof === REACT_SCOPE_TYPE || type.$$typeof === REACT_BLOCK_TYPE);
                  }

                  function typeOf(object) {
                    if (typeof object === 'object' && object !== null) {
                      var $$typeof = object.$$typeof;

                      switch ($$typeof) {
                        case REACT_ELEMENT_TYPE:
                          var type = object.type;

                          switch (type) {
                            case REACT_ASYNC_MODE_TYPE:
                            case REACT_CONCURRENT_MODE_TYPE:
                            case REACT_FRAGMENT_TYPE:
                            case REACT_PROFILER_TYPE:
                            case REACT_STRICT_MODE_TYPE:
                            case REACT_SUSPENSE_TYPE:
                              return type;

                            default:
                              var $$typeofType = type && type.$$typeof;

                              switch ($$typeofType) {
                                case REACT_CONTEXT_TYPE:
                                case REACT_FORWARD_REF_TYPE:
                                case REACT_LAZY_TYPE:
                                case REACT_MEMO_TYPE:
                                case REACT_PROVIDER_TYPE:
                                  return $$typeofType;

                                default:
                                  return $$typeof;
                              }

                          }

                        case REACT_PORTAL_TYPE:
                          return $$typeof;
                      }
                    }

                    return undefined;
                  } // AsyncMode is deprecated along with isAsyncMode


                  var AsyncMode = REACT_ASYNC_MODE_TYPE;
                  var ConcurrentMode = REACT_CONCURRENT_MODE_TYPE;
                  var ContextConsumer = REACT_CONTEXT_TYPE;
                  var ContextProvider = REACT_PROVIDER_TYPE;
                  var Element = REACT_ELEMENT_TYPE;
                  var ForwardRef = REACT_FORWARD_REF_TYPE;
                  var Fragment = REACT_FRAGMENT_TYPE;
                  var Lazy = REACT_LAZY_TYPE;
                  var Memo = REACT_MEMO_TYPE;
                  var Portal = REACT_PORTAL_TYPE;
                  var Profiler = REACT_PROFILER_TYPE;
                  var StrictMode = REACT_STRICT_MODE_TYPE;
                  var Suspense = REACT_SUSPENSE_TYPE;
                  var hasWarnedAboutDeprecatedIsAsyncMode = false; // AsyncMode should be deprecated

                  function isAsyncMode(object) {
                    {
                      if (!hasWarnedAboutDeprecatedIsAsyncMode) {
                        hasWarnedAboutDeprecatedIsAsyncMode = true; // Using console['warn'] to evade Babel and ESLint

                        console['warn']('The ReactIs.isAsyncMode() alias has been deprecated, ' + 'and will be removed in React 17+. Update your code to use ' + 'ReactIs.isConcurrentMode() instead. It has the exact same API.');
                      }
                    }
                    return isConcurrentMode(object) || typeOf(object) === REACT_ASYNC_MODE_TYPE;
                  }

                  function isConcurrentMode(object) {
                    return typeOf(object) === REACT_CONCURRENT_MODE_TYPE;
                  }

                  function isContextConsumer(object) {
                    return typeOf(object) === REACT_CONTEXT_TYPE;
                  }

                  function isContextProvider(object) {
                    return typeOf(object) === REACT_PROVIDER_TYPE;
                  }

                  function isElement(object) {
                    return typeof object === 'object' && object !== null && object.$$typeof === REACT_ELEMENT_TYPE;
                  }

                  function isForwardRef(object) {
                    return typeOf(object) === REACT_FORWARD_REF_TYPE;
                  }

                  function isFragment(object) {
                    return typeOf(object) === REACT_FRAGMENT_TYPE;
                  }

                  function isLazy(object) {
                    return typeOf(object) === REACT_LAZY_TYPE;
                  }

                  function isMemo(object) {
                    return typeOf(object) === REACT_MEMO_TYPE;
                  }

                  function isPortal(object) {
                    return typeOf(object) === REACT_PORTAL_TYPE;
                  }

                  function isProfiler(object) {
                    return typeOf(object) === REACT_PROFILER_TYPE;
                  }

                  function isStrictMode(object) {
                    return typeOf(object) === REACT_STRICT_MODE_TYPE;
                  }

                  function isSuspense(object) {
                    return typeOf(object) === REACT_SUSPENSE_TYPE;
                  }

                  exports.AsyncMode = AsyncMode;
                  exports.ConcurrentMode = ConcurrentMode;
                  exports.ContextConsumer = ContextConsumer;
                  exports.ContextProvider = ContextProvider;
                  exports.Element = Element;
                  exports.ForwardRef = ForwardRef;
                  exports.Fragment = Fragment;
                  exports.Lazy = Lazy;
                  exports.Memo = Memo;
                  exports.Portal = Portal;
                  exports.Profiler = Profiler;
                  exports.StrictMode = StrictMode;
                  exports.Suspense = Suspense;
                  exports.isAsyncMode = isAsyncMode;
                  exports.isConcurrentMode = isConcurrentMode;
                  exports.isContextConsumer = isContextConsumer;
                  exports.isContextProvider = isContextProvider;
                  exports.isElement = isElement;
                  exports.isForwardRef = isForwardRef;
                  exports.isFragment = isFragment;
                  exports.isLazy = isLazy;
                  exports.isMemo = isMemo;
                  exports.isPortal = isPortal;
                  exports.isProfiler = isProfiler;
                  exports.isStrictMode = isStrictMode;
                  exports.isSuspense = isSuspense;
                  exports.isValidElementType = isValidElementType;
                  exports.typeOf = typeOf;
                })();
              }
              /***/

            },

            /***/
            "./node_modules/react-is/index.js":
            /*!****************************************!*\
              !*** ./node_modules/react-is/index.js ***!
              \****************************************/

            /***/
            (module, __unused_webpack_exports, __webpack_require__) => {

              {
                module.exports = __webpack_require__(
                /*! ./cjs/react-is.development.js */
                "./node_modules/react-is/cjs/react-is.development.js");
              }
              /***/

            },

            /***/
            "./node_modules/shallow-equal/dist/index.esm.js":
            /*!******************************************************!*\
              !*** ./node_modules/shallow-equal/dist/index.esm.js ***!
              \******************************************************/

            /***/
            (__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

              __webpack_require__.r(__webpack_exports__);
              /* harmony export */


              __webpack_require__.d(__webpack_exports__, {
                /* harmony export */
                "shallowEqualArrays": () =>
                /* binding */
                shallowEqualArrays,

                /* harmony export */
                "shallowEqualObjects": () =>
                /* binding */
                shallowEqualObjects
                /* harmony export */

              });

              function shallowEqualObjects(objA, objB) {
                if (objA === objB) {
                  return true;
                }

                if (!objA || !objB) {
                  return false;
                }

                var aKeys = Object.keys(objA);
                var bKeys = Object.keys(objB);
                var len = aKeys.length;

                if (bKeys.length !== len) {
                  return false;
                }

                for (var i = 0; i < len; i++) {
                  var key = aKeys[i];

                  if (objA[key] !== objB[key] || !Object.prototype.hasOwnProperty.call(objB, key)) {
                    return false;
                  }
                }

                return true;
              }

              function shallowEqualArrays(arrA, arrB) {
                if (arrA === arrB) {
                  return true;
                }

                if (!arrA || !arrB) {
                  return false;
                }

                var len = arrA.length;

                if (arrB.length !== len) {
                  return false;
                }

                for (var i = 0; i < len; i++) {
                  if (arrA[i] !== arrB[i]) {
                    return false;
                  }
                }

                return true;
              }
              /***/

            },

            /***/
            "./src/Component.ts":
            /*!**************************!*\
              !*** ./src/Component.ts ***!
              \**************************/

            /***/
            function (__unused_webpack_module, exports, __webpack_require__) {

              var __rest = this && this.__rest || function (s, e) {
                var t = {};

                for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0) t[p] = s[p];

                if (s != null && typeof Object.getOwnPropertySymbols === "function") for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
                  if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i])) t[p[i]] = s[p[i]];
                }
                return t;
              };

              var __importDefault = this && this.__importDefault || function (mod) {
                return mod && mod.__esModule ? mod : {
                  "default": mod
                };
              };

              Object.defineProperty(exports, "__esModule", {
                value: true
              });

              var useMediaQuery_1 = __importDefault(__webpack_require__(
              /*! ./useMediaQuery */
              "./src/useMediaQuery.ts"));

              var MediaQuery = function (_a) {
                var children = _a.children,
                    device = _a.device,
                    onChange = _a.onChange,
                    settings = __rest(_a, ["children", "device", "onChange"]);

                var matches = (0, useMediaQuery_1.default)(settings, device, onChange);

                if (typeof children === 'function') {
                  return children(matches);
                }

                return matches ? children : null;
              };

              exports["default"] = MediaQuery;
              /***/
            },

            /***/
            "./src/Context.ts":
            /*!************************!*\
              !*** ./src/Context.ts ***!
              \************************/

            /***/
            (__unused_webpack_module, exports, __webpack_require__) => {

              Object.defineProperty(exports, "__esModule", {
                value: true
              });

              var react_1 = __webpack_require__(
              /*! react */
              "react");

              var Context = (0, react_1.createContext)(undefined);
              exports["default"] = Context;
              /***/
            },

            /***/
            "./src/index.ts":
            /*!**********************!*\
              !*** ./src/index.ts ***!
              \**********************/

            /***/
            function (__unused_webpack_module, exports, __webpack_require__) {

              var __importDefault = this && this.__importDefault || function (mod) {
                return mod && mod.__esModule ? mod : {
                  "default": mod
                };
              };

              Object.defineProperty(exports, "__esModule", {
                value: true
              });
              exports.Context = exports.toQuery = exports.useMediaQuery = exports["default"] = void 0;

              var useMediaQuery_1 = __importDefault(__webpack_require__(
              /*! ./useMediaQuery */
              "./src/useMediaQuery.ts"));

              exports.useMediaQuery = useMediaQuery_1.default;

              var Component_1 = __importDefault(__webpack_require__(
              /*! ./Component */
              "./src/Component.ts"));

              exports["default"] = Component_1.default;

              var toQuery_1 = __importDefault(__webpack_require__(
              /*! ./toQuery */
              "./src/toQuery.ts"));

              exports.toQuery = toQuery_1.default;

              var Context_1 = __importDefault(__webpack_require__(
              /*! ./Context */
              "./src/Context.ts"));

              exports.Context = Context_1.default;
              /***/
            },

            /***/
            "./src/mediaQuery.ts":
            /*!***************************!*\
              !*** ./src/mediaQuery.ts ***!
              \***************************/

            /***/
            function (__unused_webpack_module, exports, __webpack_require__) {

              var __assign = this && this.__assign || function () {
                __assign = Object.assign || function (t) {
                  for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];

                    for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
                  }

                  return t;
                };

                return __assign.apply(this, arguments);
              };

              var __rest = this && this.__rest || function (s, e) {
                var t = {};

                for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0) t[p] = s[p];

                if (s != null && typeof Object.getOwnPropertySymbols === "function") for (var i = 0, p = Object.getOwnPropertySymbols(s); i < p.length; i++) {
                  if (e.indexOf(p[i]) < 0 && Object.prototype.propertyIsEnumerable.call(s, p[i])) t[p[i]] = s[p[i]];
                }
                return t;
              };

              var __importDefault = this && this.__importDefault || function (mod) {
                return mod && mod.__esModule ? mod : {
                  "default": mod
                };
              };

              Object.defineProperty(exports, "__esModule", {
                value: true
              });

              var prop_types_1 = __importDefault(__webpack_require__(
              /*! prop-types */
              "./node_modules/prop-types/index.js"));

              var stringOrNumber = prop_types_1.default.oneOfType([prop_types_1.default.string, prop_types_1.default.number]); // media types

              var types = {
                all: prop_types_1.default.bool,
                grid: prop_types_1.default.bool,
                aural: prop_types_1.default.bool,
                braille: prop_types_1.default.bool,
                handheld: prop_types_1.default.bool,
                print: prop_types_1.default.bool,
                projection: prop_types_1.default.bool,
                screen: prop_types_1.default.bool,
                tty: prop_types_1.default.bool,
                tv: prop_types_1.default.bool,
                embossed: prop_types_1.default.bool
              }; // properties that match media queries

              var matchers = {
                orientation: prop_types_1.default.oneOf(['portrait', 'landscape']),
                scan: prop_types_1.default.oneOf(['progressive', 'interlace']),
                aspectRatio: prop_types_1.default.string,
                deviceAspectRatio: prop_types_1.default.string,
                height: stringOrNumber,
                deviceHeight: stringOrNumber,
                width: stringOrNumber,
                deviceWidth: stringOrNumber,
                color: prop_types_1.default.bool,
                colorIndex: prop_types_1.default.bool,
                monochrome: prop_types_1.default.bool,
                resolution: stringOrNumber,
                type: Object.keys(types)
              }; // eslint-disable-next-line @typescript-eslint/no-unused-vars

              var featureMatchers = __rest(matchers // media features
              , ["type"]); // media features


              var features = __assign({
                minAspectRatio: prop_types_1.default.string,
                maxAspectRatio: prop_types_1.default.string,
                minDeviceAspectRatio: prop_types_1.default.string,
                maxDeviceAspectRatio: prop_types_1.default.string,
                minHeight: stringOrNumber,
                maxHeight: stringOrNumber,
                minDeviceHeight: stringOrNumber,
                maxDeviceHeight: stringOrNumber,
                minWidth: stringOrNumber,
                maxWidth: stringOrNumber,
                minDeviceWidth: stringOrNumber,
                maxDeviceWidth: stringOrNumber,
                minColor: prop_types_1.default.number,
                maxColor: prop_types_1.default.number,
                minColorIndex: prop_types_1.default.number,
                maxColorIndex: prop_types_1.default.number,
                minMonochrome: prop_types_1.default.number,
                maxMonochrome: prop_types_1.default.number,
                minResolution: stringOrNumber,
                maxResolution: stringOrNumber
              }, featureMatchers);

              var all = __assign(__assign({}, types), features);

              exports["default"] = {
                all: all,
                types: types,
                matchers: matchers,
                features: features
              };
              /***/
            },

            /***/
            "./src/toQuery.ts":
            /*!************************!*\
              !*** ./src/toQuery.ts ***!
              \************************/

            /***/
            function (__unused_webpack_module, exports, __webpack_require__) {

              var __importDefault = this && this.__importDefault || function (mod) {
                return mod && mod.__esModule ? mod : {
                  "default": mod
                };
              };

              Object.defineProperty(exports, "__esModule", {
                value: true
              });

              var hyphenate_style_name_1 = __importDefault(__webpack_require__(
              /*! hyphenate-style-name */
              "./node_modules/hyphenate-style-name/index.js"));

              var mediaQuery_1 = __importDefault(__webpack_require__(
              /*! ./mediaQuery */
              "./src/mediaQuery.ts"));

              var negate = function (cond) {
                return "not ".concat(cond);
              };

              var keyVal = function (k, v) {
                var realKey = (0, hyphenate_style_name_1.default)(k); // px shorthand

                if (typeof v === 'number') {
                  v = "".concat(v, "px");
                }

                if (v === true) {
                  return realKey;
                }

                if (v === false) {
                  return negate(realKey);
                }

                return "(".concat(realKey, ": ").concat(v, ")");
              };

              var join = function (conds) {
                return conds.join(' and ');
              };

              var toQuery = function (obj) {
                var rules = [];
                Object.keys(mediaQuery_1.default.all).forEach(function (k) {
                  var v = obj[k];

                  if (v != null) {
                    rules.push(keyVal(k, v));
                  }
                });
                return join(rules);
              };

              exports["default"] = toQuery;
              /***/
            },

            /***/
            "./src/useMediaQuery.ts":
            /*!******************************!*\
              !*** ./src/useMediaQuery.ts ***!
              \******************************/

            /***/
            function (__unused_webpack_module, exports, __webpack_require__) {

              var __importDefault = this && this.__importDefault || function (mod) {
                return mod && mod.__esModule ? mod : {
                  "default": mod
                };
              };

              Object.defineProperty(exports, "__esModule", {
                value: true
              });

              var react_1 = __webpack_require__(
              /*! react */
              "react");

              var matchmediaquery_1 = __importDefault(__webpack_require__(
              /*! matchmediaquery */
              "./node_modules/matchmediaquery/index.js"));

              var hyphenate_style_name_1 = __importDefault(__webpack_require__(
              /*! hyphenate-style-name */
              "./node_modules/hyphenate-style-name/index.js"));

              var shallow_equal_1 = __webpack_require__(
              /*! shallow-equal */
              "./node_modules/shallow-equal/dist/index.esm.js");

              var toQuery_1 = __importDefault(__webpack_require__(
              /*! ./toQuery */
              "./src/toQuery.ts"));

              var Context_1 = __importDefault(__webpack_require__(
              /*! ./Context */
              "./src/Context.ts"));

              var makeQuery = function (settings) {
                return settings.query || (0, toQuery_1.default)(settings);
              };

              var hyphenateKeys = function (obj) {
                if (!obj) return undefined;
                var keys = Object.keys(obj);
                return keys.reduce(function (result, key) {
                  result[(0, hyphenate_style_name_1.default)(key)] = obj[key];
                  return result;
                }, {});
              };

              var useIsUpdate = function () {
                var ref = (0, react_1.useRef)(false);
                (0, react_1.useEffect)(function () {
                  ref.current = true;
                }, []);
                return ref.current;
              };

              var useDevice = function (deviceFromProps) {
                var deviceFromContext = (0, react_1.useContext)(Context_1.default);

                var getDevice = function () {
                  return hyphenateKeys(deviceFromProps) || hyphenateKeys(deviceFromContext);
                };

                var _a = (0, react_1.useState)(getDevice),
                    device = _a[0],
                    setDevice = _a[1];

                (0, react_1.useEffect)(function () {
                  var newDevice = getDevice();

                  if (!(0, shallow_equal_1.shallowEqualObjects)(device, newDevice)) {
                    setDevice(newDevice);
                  }
                }, [deviceFromProps, deviceFromContext]);
                return device;
              };

              var useQuery = function (settings) {
                var getQuery = function () {
                  return makeQuery(settings);
                };

                var _a = (0, react_1.useState)(getQuery),
                    query = _a[0],
                    setQuery = _a[1];

                (0, react_1.useEffect)(function () {
                  var newQuery = getQuery();

                  if (query !== newQuery) {
                    setQuery(newQuery);
                  }
                }, [settings]);
                return query;
              };

              var useMatchMedia = function (query, device) {
                var getMatchMedia = function () {
                  return (0, matchmediaquery_1.default)(query, device || {}, !!device);
                };

                var _a = (0, react_1.useState)(getMatchMedia),
                    mq = _a[0],
                    setMq = _a[1];

                var isUpdate = useIsUpdate();
                (0, react_1.useEffect)(function () {
                  if (isUpdate) {
                    // skip on mounting, it has already been set
                    var newMq_1 = getMatchMedia();
                    setMq(newMq_1);
                    return function () {
                      if (newMq_1) {
                        newMq_1.dispose();
                      }
                    };
                  }
                }, [query, device]);
                return mq;
              };

              var useMatches = function (mediaQuery) {
                var _a = (0, react_1.useState)(mediaQuery.matches),
                    matches = _a[0],
                    setMatches = _a[1];

                (0, react_1.useEffect)(function () {
                  var updateMatches = function (ev) {
                    setMatches(ev.matches);
                  };

                  mediaQuery.addListener(updateMatches);
                  setMatches(mediaQuery.matches);
                  return function () {
                    mediaQuery.removeListener(updateMatches);
                  };
                }, [mediaQuery]);
                return matches;
              };

              var useMediaQuery = function (settings, device, onChange) {
                var deviceSettings = useDevice(device);
                var query = useQuery(settings);
                if (!query) throw new Error('Invalid or missing MediaQuery!');
                var mq = useMatchMedia(query, deviceSettings);
                var matches = useMatches(mq);
                var isUpdate = useIsUpdate();
                (0, react_1.useEffect)(function () {
                  if (isUpdate && onChange) {
                    onChange(matches);
                  }
                }, [matches]);
                (0, react_1.useEffect)(function () {
                  return function () {
                    if (mq) {
                      mq.dispose();
                    }
                  };
                }, []);
                return matches;
              };

              exports["default"] = useMediaQuery;
              /***/
            },

            /***/
            "react":
            /*!**************************************************************************************!*\
              !*** external {"commonjs":"react","commonjs2":"react","amd":"react","root":"React"} ***!
              \**************************************************************************************/

            /***/
            module => {

              module.exports = __WEBPACK_EXTERNAL_MODULE_react__;
              /***/
            }
            /******/

          };
          /************************************************************************/

          /******/
          // The module cache

          /******/

          var __webpack_module_cache__ = {};
          /******/

          /******/
          // The require function

          /******/

          function __webpack_require__(moduleId) {
            /******/
            // Check if module is in cache

            /******/
            var cachedModule = __webpack_module_cache__[moduleId];
            /******/

            if (cachedModule !== undefined) {
              /******/
              return cachedModule.exports;
              /******/
            }
            /******/
            // Create a new module (and put it into the cache)

            /******/


            var module = __webpack_module_cache__[moduleId] = {
              /******/
              // no module.id needed

              /******/
              // no module.loaded needed

              /******/
              exports: {}
              /******/

            };
            /******/

            /******/
            // Execute the module function

            /******/

            __webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
            /******/

            /******/
            // Return the exports of the module

            /******/


            return module.exports;
            /******/
          }
          /******/

          /************************************************************************/

          /******/

          /* webpack/runtime/define property getters */

          /******/


          (() => {
            /******/
            // define getter functions for harmony exports

            /******/
            __webpack_require__.d = (exports, definition) => {
              /******/
              for (var key in definition) {
                /******/
                if (__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
                  /******/
                  Object.defineProperty(exports, key, {
                    enumerable: true,
                    get: definition[key]
                  });
                  /******/
                }
                /******/

              }
              /******/

            };
            /******/

          })();
          /******/

          /******/

          /* webpack/runtime/hasOwnProperty shorthand */

          /******/


          (() => {
            /******/
            __webpack_require__.o = (obj, prop) => Object.prototype.hasOwnProperty.call(obj, prop);
            /******/

          })();
          /******/

          /******/

          /* webpack/runtime/make namespace object */

          /******/


          (() => {
            /******/
            // define __esModule on exports

            /******/
            __webpack_require__.r = exports => {
              /******/
              if (typeof Symbol !== 'undefined' && Symbol.toStringTag) {
                /******/
                Object.defineProperty(exports, Symbol.toStringTag, {
                  value: 'Module'
                });
                /******/
              }
              /******/


              Object.defineProperty(exports, '__esModule', {
                value: true
              });
              /******/
            };
            /******/

          })();
          /******/

          /************************************************************************/

          /******/

          /******/
          // startup

          /******/
          // Load entry module and return exports

          /******/
          // This entry module is referenced by other modules so it can't be inlined

          /******/


          var __webpack_exports__ = __webpack_require__("./src/index.ts");
          /******/

          /******/


          return __webpack_exports__;
          /******/
        })()
      );
    });
  });
  var reactResponsive$1 = /*@__PURE__*/unwrapExports(reactResponsive);

  function requiredArgs(required, args) {
    if (args.length < required) {
      throw new TypeError(required + ' argument' + (required > 1 ? 's' : '') + ' required, but only ' + args.length + ' present');
    }
  }

  /**
   * @name toDate
   * @category Common Helpers
   * @summary Convert the given argument to an instance of Date.
   *
   * @description
   * Convert the given argument to an instance of Date.
   *
   * If the argument is an instance of Date, the function returns its clone.
   *
   * If the argument is a number, it is treated as a timestamp.
   *
   * If the argument is none of the above, the function returns Invalid Date.
   *
   * **Note**: *all* Date arguments passed to any *date-fns* function is processed by `toDate`.
   *
   * @param {Date|Number} argument - the value to convert
   * @returns {Date} the parsed date in the local time zone
   * @throws {TypeError} 1 argument required
   *
   * @example
   * // Clone the date:
   * const result = toDate(new Date(2014, 1, 11, 11, 30, 30))
   * //=> Tue Feb 11 2014 11:30:30
   *
   * @example
   * // Convert the timestamp to date:
   * const result = toDate(1392098430000)
   * //=> Tue Feb 11 2014 11:30:30
   */

  function toDate(argument) {
    requiredArgs(1, arguments);
    var argStr = Object.prototype.toString.call(argument); // Clone the date

    if (argument instanceof Date || typeof argument === 'object' && argStr === '[object Date]') {
      // Prevent the date to lose the milliseconds when passed to new Date() in IE10
      return new Date(argument.getTime());
    } else if (typeof argument === 'number' || argStr === '[object Number]') {
      return new Date(argument);
    } else {
      if ((typeof argument === 'string' || argStr === '[object String]') && typeof console !== 'undefined') {
        // eslint-disable-next-line no-console
        console.warn("Starting with v2.0.0-beta.1 date-fns doesn't accept strings as date arguments. Please use `parseISO` to parse strings. See: https://git.io/fjule"); // eslint-disable-next-line no-console

        console.warn(new Error().stack);
      }

      return new Date(NaN);
    }
  }

  /**
   * @name startOfDay
   * @category Day Helpers
   * @summary Return the start of a day for the given date.
   *
   * @description
   * Return the start of a day for the given date.
   * The result will be in the local timezone.
   *
   * ### v2.0.0 breaking changes:
   *
   * - [Changes that are common for the whole library](https://github.com/date-fns/date-fns/blob/master/docs/upgradeGuide.md#Common-Changes).
   *
   * @param {Date|Number} date - the original date
   * @returns {Date} the start of a day
   * @throws {TypeError} 1 argument required
   *
   * @example
   * // The start of a day for 2 September 2014 11:55:00:
   * const result = startOfDay(new Date(2014, 8, 2, 11, 55, 0))
   * //=> Tue Sep 02 2014 00:00:00
   */

  function startOfDay(dirtyDate) {
    requiredArgs(1, arguments);
    var date = toDate(dirtyDate);
    date.setHours(0, 0, 0, 0);
    return date;
  }

  /**
   * Days in 1 week.
   *
   * @name daysInWeek
   * @constant
   * @type {number}
   * @default
   */
  /**
   * Milliseconds in 1 second
   *
   * @name millisecondsInSecond
   * @constant
   * @type {number}
   * @default
   */

  var millisecondsInSecond = 1000;

  /**
   * @name isSameDay
   * @category Day Helpers
   * @summary Are the given dates in the same day (and year and month)?
   *
   * @description
   * Are the given dates in the same day (and year and month)?
   *
   * ### v2.0.0 breaking changes:
   *
   * - [Changes that are common for the whole library](https://github.com/date-fns/date-fns/blob/master/docs/upgradeGuide.md#Common-Changes).
   *
   * @param {Date|Number} dateLeft - the first date to check
   * @param {Date|Number} dateRight - the second date to check
   * @returns {Boolean} the dates are in the same day (and year and month)
   * @throws {TypeError} 2 arguments required
   *
   * @example
   * // Are 4 September 06:00:00 and 4 September 18:00:00 in the same day?
   * var result = isSameDay(new Date(2014, 8, 4, 6, 0), new Date(2014, 8, 4, 18, 0))
   * //=> true
   * 
   * @example
   * // Are 4 September and 4 October in the same day?
   * var result = isSameDay(new Date(2014, 8, 4), new Date(2014, 9, 4))
   * //=> false
   * 
   * @example
   * // Are 4 September, 2014 and 4 September, 2015 in the same day?
   * var result = isSameDay(new Date(2014, 8, 4), new Date(2015, 8, 4))
   * //=> false
   */

  function isSameDay(dirtyDateLeft, dirtyDateRight) {
    requiredArgs(2, arguments);
    var dateLeftStartOfDay = startOfDay(dirtyDateLeft);
    var dateRightStartOfDay = startOfDay(dirtyDateRight);
    return dateLeftStartOfDay.getTime() === dateRightStartOfDay.getTime();
  }

  /**
   * @name isToday
   * @category Day Helpers
   * @summary Is the given date today?
   * @pure false
   *
   * @description
   * Is the given date today?
   *
   * > ⚠️ Please note that this function is not present in the FP submodule as
   * > it uses `Date.now()` internally hence impure and can't be safely curried.
   *
   * ### v2.0.0 breaking changes:
   *
   * - [Changes that are common for the whole library](https://github.com/date-fns/date-fns/blob/master/docs/upgradeGuide.md#Common-Changes).
   *
   * @param {Date|Number} date - the date to check
   * @returns {Boolean} the date is today
   * @throws {TypeError} 1 argument required
   *
   * @example
   * // If today is 6 October 2014, is 6 October 14:00:00 today?
   * var result = isToday(new Date(2014, 9, 6, 14, 0))
   * //=> true
   */

  function isToday(dirtyDate) {
    requiredArgs(1, arguments);
    return isSameDay(dirtyDate, Date.now());
  }

  /**
   * @name secondsToMilliseconds
   * @category Conversion Helpers
   * @summary Convert seconds to milliseconds.
   *
   * @description
   * Convert a number of seconds to a full number of milliseconds.
   *
   * @param {number} seconds - number of seconds to be converted
   *
   * @returns {number} the number of seconds converted in milliseconds
   * @throws {TypeError} 1 argument required
   *
   * @example
   * // Convert 2 seconds into milliseconds
   * const result = secondsToMilliseconds(2)
   * //=> 2000
   */

  function secondsToMilliseconds(seconds) {
    requiredArgs(1, arguments);
    return seconds * millisecondsInSecond;
  }

  var paymentPlanShorthandName = function paymentPlanShorthandName(payment) {
    var deferred_days = payment.deferred_days,
        deferred_months = payment.deferred_months,
        installmentsCount = payment.installments_count;

    if (installmentsCount === 1 && !deferred_days && !deferred_months) {
      return /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "payment-plan-strings.pay.now.button",
        defaultMessage: "Payer maintenant"
      });
    }

    if (installmentsCount === 1 && deferred_days) {
      return /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "payment-plan-strings.day-abbreviation",
        defaultMessage: "J{deferredDays}",
        values: {
          deferredDays: "+" + deferred_days
        }
      });
    }

    if (installmentsCount === 1 && deferred_months) {
      return /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "payment-plan-strings.month-abbreviation",
        defaultMessage: "M{deferredMonths}",
        values: {
          deferredMonths: "+" + deferred_months
        }
      });
    } else {
      return installmentsCount + "x";
    }
  };

  var withNoFee = function withNoFee(payment) {
    var _payment$payment_plan;

    if ((_payment$payment_plan = payment.payment_plan) != null && _payment$payment_plan.every(function (plan) {
      return plan.customer_fee === 0 && plan.customer_interest === 0;
    })) {
      return /*#__PURE__*/react.createElement(react.Fragment, null, ' ', /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "payment-plan-strings.no-fee",
        defaultMessage: '(sans frais)'
      }));
    }
  };

  var paymentPlanInfoText = function paymentPlanInfoText(payment) {
    var deferred_days = payment.deferred_days,
        deferred_months = payment.deferred_months,
        installmentsCount = payment.installments_count,
        eligible = payment.eligible,
        purchaseAmount = payment.purchase_amount,
        _payment$minAmount = payment.minAmount,
        minAmount = _payment$minAmount === void 0 ? 0 : _payment$minAmount,
        _payment$maxAmount = payment.maxAmount,
        maxAmount = _payment$maxAmount === void 0 ? 0 : _payment$maxAmount,
        payment_plan = payment.payment_plan;
    var deferredDaysCount = deferred_days + deferred_months * 30;

    if (!eligible) {
      return purchaseAmount > maxAmount ? /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "payment-plan-strings.ineligible-greater-than-max",
        defaultMessage: "Jusqu'\xE0 {maxAmount}",
        values: {
          maxAmount: /*#__PURE__*/react.createElement(FormattedNumber, {
            value: priceFromCents(maxAmount),
            style: "currency",
            currency: "EUR"
          })
        }
      }) : /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "payment-plan-strings.ineligible-lower-than-min",
        defaultMessage: "\xC0 partir de {minAmount}",
        values: {
          minAmount: /*#__PURE__*/react.createElement(FormattedNumber, {
            value: priceFromCents(minAmount),
            style: "currency",
            currency: "EUR"
          })
        }
      });
    } else if (!payment_plan) {
      /* This error should never happen. We added this condition to avoid a typescript warning on
           payment_plan possibly undefined. As far as we know, it only happens when the plan is not
           eligible, which is checked above. */
      throw Error("No payment plan provided for payment in " + installmentsCount + " installments. Please contact us if you see this error.");
    } else if (deferredDaysCount !== 0 && installmentsCount === 1) {
      return /*#__PURE__*/react.createElement(react.Fragment, null, /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "payment-plan-strings.deferred",
        defaultMessage: "{totalAmount} \xE0 payer le {dueDate}",
        values: {
          totalAmount: /*#__PURE__*/react.createElement(FormattedNumber, {
            value: priceFromCents(payment_plan[0].total_amount),
            style: "currency",
            currency: "EUR"
          }),
          dueDate: /*#__PURE__*/react.createElement(FormattedDate, {
            value: secondsToMilliseconds(payment_plan[0].due_date),
            day: "numeric",
            month: "long",
            year: "numeric"
          })
        }
      }), withNoFee(payment));
    } else if (installmentsCount > 0) {
      var areInstallmentsOfSameAmount = payment_plan == null ? void 0 : payment_plan.every(function (installment, index) {
        return index === 0 || installment.total_amount === payment_plan[0].total_amount;
      });

      if (isP1X(payment)) {
        return /*#__PURE__*/react.createElement(react.Fragment, null, /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
          id: "payment-plan-strings.pay-now",
          defaultMessage: "Payer maintenant {totalAmount}",
          values: {
            totalAmount: /*#__PURE__*/react.createElement(FormattedNumber, {
              value: priceFromCents(payment_plan[0].total_amount),
              style: "currency",
              currency: "EUR"
            }),
            installmentsCount: installmentsCount
          }
        }), withNoFee(payment));
      }

      if (areInstallmentsOfSameAmount) {
        return /*#__PURE__*/react.createElement(react.Fragment, null, /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
          id: "payment-plan-strings.multiple-installments-same-amount",
          defaultMessage: "{installmentsCount} x {totalAmount}",
          values: {
            totalAmount: /*#__PURE__*/react.createElement(FormattedNumber, {
              value: priceFromCents(payment_plan[0].total_amount),
              style: "currency",
              currency: "EUR"
            }),
            installmentsCount: installmentsCount
          }
        }), withNoFee(payment));
      }

      return /*#__PURE__*/react.createElement(react.Fragment, null, /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "payment-plan-strings.multiple-installments",
        defaultMessage: "{numberOfRemainingInstallments, plural, one {{firstInstallmentAmount} puis {numberOfRemainingInstallments} x {othersInstallmentAmount}} other {{firstInstallmentAmount} puis {numberOfRemainingInstallments} x {othersInstallmentAmount}}}",
        values: {
          firstInstallmentAmount: /*#__PURE__*/react.createElement(FormattedNumber, {
            value: priceFromCents(payment_plan[0].total_amount),
            style: "currency",
            currency: "EUR"
          }),
          numberOfRemainingInstallments: installmentsCount - 1,
          othersInstallmentAmount: /*#__PURE__*/react.createElement(FormattedNumber, {
            value: priceFromCents(payment_plan[1].total_amount),
            style: "currency",
            currency: "EUR"
          })
        }
      }), withNoFee(payment));
    }

    return /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      id: "payment-plan-strings.default-message",
      defaultMessage: "Payez en plusieurs fois avec Alma"
    });
  };

  var s$3 = {"buttons":"_1l2Oa","active":"_3rue7","textButton":"_2T78a"};

  var EligibilityPlansButtons = function EligibilityPlansButtons(_ref) {
    var eligibilityPlans = _ref.eligibilityPlans,
        currentPlanIndex = _ref.currentPlanIndex,
        setCurrentPlanIndex = _ref.setCurrentPlanIndex;
    return /*#__PURE__*/react.createElement("div", {
      className: classnames(s$3.buttons, STATIC_CUSTOMISATION_CLASSES.eligibilityOptions)
    }, eligibilityPlans.map(function (eligibilityPlan, index) {
      var _cx;

      return /*#__PURE__*/react.createElement("button", {
        key: index,
        className: classnames((_cx = {}, _cx[classnames(s$3.active, STATIC_CUSTOMISATION_CLASSES.activeOption)] = index === currentPlanIndex, _cx)),
        onClick: function onClick() {
          return setCurrentPlanIndex(index);
        }
      }, /*#__PURE__*/react.createElement("span", {
        className: s$3.textButton
      }, paymentPlanShorthandName(eligibilityPlan)));
    }));
  };

  var s$4 = {"installment":"_z2Uiv","date":"_2lJQy","dot":"_1Z9wr","isCurrent":"_2Nmkl","bold":"_ezY-3"};

  var Installment = function Installment(_ref) {
    var _cx, _cx2;

    var installment = _ref.installment,
        index = _ref.index;
    return /*#__PURE__*/react.createElement("div", {
      className: s$4.installment,
      "data-testid": "installment-" + index
    }, /*#__PURE__*/react.createElement("div", {
      className: s$4.date
    }, /*#__PURE__*/react.createElement("div", {
      className: classnames(s$4.dot, (_cx = {}, _cx[s$4.isCurrent] = index === 0, _cx))
    }), isToday(installment.due_date * 1000) ? /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      id: "installments.today",
      defaultMessage: "Aujourd'hui",
      tagName: "strong"
    }) : /*#__PURE__*/react.createElement(FormattedDate, {
      value: installment.due_date * 1000,
      day: "numeric",
      month: "long",
      year: "numeric"
    })), /*#__PURE__*/react.createElement("div", {
      className: classnames((_cx2 = {}, _cx2[s$4.bold] = index === 0, _cx2))
    }, /*#__PURE__*/react.createElement(FormattedNumber, {
      value: installment.total_amount / 100,
      style: "currency",
      currency: "EUR"
    })));
  };

  var s$5 = {"schedule":"_MPKjS","creditInfo":"_3a7er"};

  var Schedule = function Schedule(_ref) {
    var currentPlan = _ref.currentPlan;
    var isCredit = currentPlan.installments_count > 4;
    return /*#__PURE__*/react.createElement(react.Fragment, null, /*#__PURE__*/react.createElement("div", {
      className: classnames(s$5.schedule, STATIC_CUSTOMISATION_CLASSES.scheduleDetails),
      "data-testid": "modal-installments-element"
    }, ((currentPlan == null ? void 0 : currentPlan.payment_plan) || []).map(function (installment, index) {
      return /*#__PURE__*/react.createElement(Installment, {
        key: installment.due_date * 1000,
        installment: installment,
        index: index
      });
    }), isCredit && /*#__PURE__*/react.createElement("div", {
      className: s$5.creditInfo
    }, /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      id: "credit-features.information",
      defaultMessage: "Un cr\xE9dit vous engage et doit \xEAtre rembours\xE9. V\xE9rifiez vos capacit\xE9s de remboursement avant de vous engager."
    }))));
  };

  var _excluded$1 = ["color"];
  var AlmaLogo = function AlmaLogo(_ref) {
    var _ref$color = _ref.color,
        color = _ref$color === void 0 ? '#FA5022' : _ref$color,
        svgProps = _objectWithoutPropertiesLoose(_ref, _excluded$1);

    return /*#__PURE__*/react.createElement("svg", Object.assign({
      width: "50",
      height: "25",
      viewBox: "0 0 360 109",
      fill: "none",
      xmlns: "http://www.w3.org/2000/svg"
    }, svgProps), /*#__PURE__*/react.createElement("path", {
      d: "M333.24 28.3462V38.4459C327.504 31.1018 319.176 26.5132 309.288 26.5132C290.208 26.5132 275.424 43.5497 275.424 64.5757C275.424 85.6018 290.208 102.638 309.288 102.638C319.872 102.638 328.668 97.3908 334.416 89.1241V100.817H352.668V28.3462H333.24ZM314.028 84.4876C303.42 84.4876 294.828 75.574 294.828 64.5757C294.828 53.5775 303.42 44.6639 314.028 44.6639C324.636 44.6639 333.228 53.5775 333.228 64.5757C333.228 75.574 324.636 84.4876 314.028 84.4876ZM109.5 8.23073H128.916V100.805H109.5V8.23073ZM151.248 59.7356C151.248 39.8117 163.5 26.5252 180.468 26.5252C191.004 26.5252 199.332 31.1976 204.348 39.1648C209.376 31.1976 217.692 26.5252 228.228 26.5252C245.196 26.5252 257.448 39.8117 257.448 59.7356V100.817H238.032V57.639C238.032 49.8635 232.872 44.7957 226.044 44.7957C219.216 44.7957 214.056 49.8755 214.056 57.639V100.817H194.64V57.639C194.64 49.8635 189.48 44.7957 182.652 44.7957C175.824 44.7957 170.664 49.8755 170.664 57.639V100.817H151.248V59.7356ZM74.34 29.101C69.744 11.9088 60.0241 6.40967 50.772 6.40967C41.5201 6.40967 31.8 11.9088 27.204 29.101L7.24805 100.829H26.916C30.12 88.8485 39.996 82.1753 50.772 82.1753C61.548 82.1753 71.424 88.8605 74.6281 100.829H94.3081L74.34 29.101ZM50.772 65.4623C44.508 65.4623 38.8321 67.8345 34.6441 71.6803L45.924 29.9397C47.0041 25.9501 48.6001 24.6802 50.784 24.6802C52.9681 24.6802 54.5641 25.9501 55.6441 29.9397L66.912 71.6803C62.724 67.8345 57.036 65.4623 50.772 65.4623Z",
      fill: color
    }));
  };

  var AmexCard = function AmexCard() {
    return /*#__PURE__*/react.createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "24",
      height: "16",
      viewBox: "0 0 24 16"
    }, /*#__PURE__*/react.createElement("defs", null, /*#__PURE__*/react.createElement("linearGradient", {
      id: "cardamex",
      x1: "10.914%",
      x2: "87.432%",
      y1: "86.279%",
      y2: "15.035%"
    }, /*#__PURE__*/react.createElement("stop", {
      offset: "0%",
      stopColor: "#3FA9F5"
    }), /*#__PURE__*/react.createElement("stop", {
      offset: "100%",
      stopColor: "#0071BC"
    }))), /*#__PURE__*/react.createElement("g", {
      fill: "none",
      fillRule: "evenodd"
    }, /*#__PURE__*/react.createElement("path", {
      fill: "url(#cardamex)",
      fillRule: "nonzero",
      d: "M22.559 16H1.379C.627 16 0 15.392 0 14.662V1.338C0 .608.627 0 1.379 0H22.62C23.373 0 24 .608 24 1.338v13.324c-.063.73-.627 1.338-1.441 1.338z"
    }), /*#__PURE__*/react.createElement("g", {
      fill: "#FFF"
    }, /*#__PURE__*/react.createElement("path", {
      d: "M10.115 8.711L8.889 6.044H7.295v3.852L5.517 6.044H4.17l-1.778 4.03h1.103l.368-.889h2.023l.368.89h2.084V7.11l1.349 2.963h.92l1.348-2.904v2.904h.98v-4.03H11.28l-1.165 2.667zm-5.272-.474H4.23l.613-1.422v.06l.613 1.362h-.613z"
    }), /*#__PURE__*/react.createElement("path", {
      fillRule: "nonzero",
      d: "M19.372 7.94l.735-.77 1.042-1.185h-1.287L18.697 7.23l-1.164-1.245h-3.985v3.97h3.923l1.226-1.303 1.165 1.304h1.287L20.107 8.71l-.735-.77zM16.92 9.186h-2.33v-.77h2.268v-.77H14.59v-.712h2.33L18.084 8 16.92 9.185z"
    }))));
  };

  var VisaCard = function VisaCard() {
    return /*#__PURE__*/react.createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "24",
      height: "16",
      viewBox: "0 0 24 16"
    }, /*#__PURE__*/react.createElement("g", {
      fill: "none",
      fillRule: "nonzero"
    }, /*#__PURE__*/react.createElement("path", {
      fill: "#FCFCFC",
      d: "M22.684 16H1.38C.627 16 0 15.39 0 14.656V1.344C0 .61.627 0 1.379 0H22.62C23.373 0 24 .61 24 1.344v13.374c0 .671-.564 1.282-1.316 1.282z"
    }), ' ', /*#__PURE__*/react.createElement("path", {
      fill: "#005098",
      d: "M8.889 10.726l.948-5.393h1.482l-.949 5.393zM15.704 5.452a3.658 3.658 0 0 0-1.304-.237c-1.481 0-2.489.71-2.489 1.778 0 .77.711 1.185 1.304 1.481.592.237.77.415.77.652 0 .355-.474.533-.889.533-.592 0-.889-.059-1.363-.296l-.177-.06-.178 1.186c.355.178 1.007.296 1.659.296 1.54 0 2.548-.71 2.548-1.837 0-.592-.415-1.067-1.244-1.481-.534-.237-.83-.415-.83-.652 0-.237.237-.474.83-.474.474 0 .83.118 1.126.178l.118.059.119-1.126M19.496 5.333H18.37c-.355 0-.592.119-.77.474l-2.193 4.919h1.541s.237-.652.296-.83h1.897c.059.178.178.83.178.83h1.362l-1.185-5.393zM17.66 8.77l.593-1.481s.118-.296.178-.533l.118.474s.296 1.303.356 1.54h-1.245zM7.644 5.333L6.222 9.007l-.178-.77C5.748 7.348 4.92 6.459 4.03 5.985l1.303 4.682h1.541l2.311-5.393h-1.54"
    }), /*#__PURE__*/react.createElement("path", {
      fill: "#F6A500",
      d: "M4.919 5.333h-2.37v.119c1.836.474 3.08 1.54 3.555 2.844L5.57 5.807c-.118-.414-.355-.474-.651-.474"
    }), /*#__PURE__*/react.createElement("path", {
      fill: "#0A5296",
      d: "M23.937 3.23H0V1.316C0 .598.627 0 1.379 0H22.62C23.373 0 24 .598 24 1.316V3.23h-.063z"
    }), /*#__PURE__*/react.createElement("path", {
      fill: "#F4A428",
      d: "M.063 13H24v1.8c0 .655-.625 1.2-1.375 1.2H1.375C.625 16 0 15.455 0 14.8V13h.063z"
    })));
  };

  var MasterCard = function MasterCard() {
    return /*#__PURE__*/react.createElement("svg", {
      width: "24px",
      height: "16px",
      viewBox: "0 0 24 16",
      version: "1.1",
      xmlns: "http://www.w3.org/2000/svg"
    }, /*#__PURE__*/react.createElement("g", {
      id: "Parcours-1C-B",
      stroke: "none",
      strokeWidth: "1",
      fill: "none",
      fillRule: "evenodd"
    }, /*#__PURE__*/react.createElement("g", {
      id: "mobile-1C-Paiement-V1",
      transform: "translate(-269.000000, -313.000000)"
    }, /*#__PURE__*/react.createElement("g", {
      id: "mastercard",
      transform: "translate(269.000000, 313.000000)"
    }, /*#__PURE__*/react.createElement("rect", {
      id: "Rectangle",
      fill: "#FFFFFF",
      x: "0",
      y: "0",
      width: "24",
      height: "16",
      rx: "1"
    }), /*#__PURE__*/react.createElement("g", {
      id: "Group-6",
      transform: "translate(4.000000, 3.000000)"
    }, /*#__PURE__*/react.createElement("circle", {
      id: "Oval-4-Copy",
      fill: "#EA001B",
      cx: "5.05263158",
      cy: "5.05263158",
      r: "5.05263158"
    }), /*#__PURE__*/react.createElement("circle", {
      id: "Oval-4",
      fillOpacity: "0.25",
      fill: "#F79F1A",
      cx: "10.9473684",
      cy: "5.05263158",
      r: "5.05263158"
    }), /*#__PURE__*/react.createElement("circle", {
      id: "Oval-4-Copy-2",
      fillOpacity: "0.9",
      fill: "#F79F1A",
      cx: "10.9473684",
      cy: "5.05263158",
      r: "5.05263158"
    }), /*#__PURE__*/react.createElement("circle", {
      id: "Oval-4-Copy-3",
      fillOpacity: "0.3",
      fill: "#EA001B",
      cx: "5.05263158",
      cy: "5.05263158",
      r: "5.05263158"
    }))))));
  };

  var CbCard = function CbCard() {
    return /*#__PURE__*/react.createElement("svg", {
      xmlns: "http://www.w3.org/2000/svg",
      width: "24",
      height: "16",
      viewBox: "0 0 24 16"
    }, /*#__PURE__*/react.createElement("defs", null, /*#__PURE__*/react.createElement("linearGradient", {
      id: "cardcb",
      x1: "5.842%",
      x2: "95.393%",
      y1: "81.753%",
      y2: "17.344%"
    }, /*#__PURE__*/react.createElement("stop", {
      offset: "0%",
      stopColor: "#39B54A"
    }), ' ', /*#__PURE__*/react.createElement("stop", {
      offset: "100%",
      stopColor: "#0A5296"
    }))), /*#__PURE__*/react.createElement("g", {
      fill: "none",
      fillRule: "nonzero"
    }, /*#__PURE__*/react.createElement("path", {
      fill: "url(#cardcb)",
      d: "M22.621 16H1.38C.627 16 0 15.392 0 14.662V1.338C0 .608.627 0 1.379 0H22.62C23.373 0 24 .608 24 1.338v13.324c-.063.73-.627 1.338-1.379 1.338z"
    }), /*#__PURE__*/react.createElement("g", {
      fill: "#FFF"
    }, /*#__PURE__*/react.createElement("path", {
      d: "M19.094 4.03h-6.437V8h6.498c1.165 0 2.084-.889 2.084-2.015-.06-1.066-.98-1.955-2.145-1.955zM19.094 8.593h-6.437v3.97h6.498c1.165 0 2.084-.889 2.084-2.015-.06-1.067-.98-1.955-2.145-1.955zM7.017 8.06h4.966c-.245-2.371-2.391-4.267-4.966-4.267-2.758 0-5.027 2.074-5.027 4.681s2.269 4.682 5.027 4.682c2.698 0 4.904-2.015 5.027-4.563H7.017v-.534z"
    }))));
  };

  var s$6 = {"cardContainer":"_1N3yO"};

  var Cards = function Cards(_ref) {
    var cards = _ref.cards;
    // We transform to a Set and back to avoid duplicate values (ex : amex, amex)
    var uniqueCards = Array.from(new Set(cards));
    return /*#__PURE__*/react.createElement("div", {
      "data-testid": "card-logos",
      className: classnames(s$6.cardContainer, STATIC_CUSTOMISATION_CLASSES.cardContainer)
    }, uniqueCards.map(function (card) {
      return /*#__PURE__*/react.createElement("div", {
        key: card,
        className: s$6.card,
        "data-testid": "card-logo-" + card
      }, card === 'cb' && /*#__PURE__*/react.createElement(CbCard, null), card === 'amex' && /*#__PURE__*/react.createElement(AmexCard, null), card === 'mastercard' && /*#__PURE__*/react.createElement(MasterCard, null), card === 'visa' && /*#__PURE__*/react.createElement(VisaCard, null));
    }));
  };

  var s$7 = {"list":"_180ro","listItem":"_1HqCO","bullet":"_3B8wx"};

  var Info = function Info(_ref) {
    var isCurrentPlanP1X = _ref.isCurrentPlanP1X;
    return (
      /*#__PURE__*/
      // TODO: refactor this component into subcomponent to factorise code and be cleaner
      react.createElement("div", {
        className: classnames(s$7.list, STATIC_CUSTOMISATION_CLASSES.info),
        "data-testid": "modal-info-element"
      }, /*#__PURE__*/react.createElement("div", {
        className: s$7.listItem
      }, /*#__PURE__*/react.createElement("div", {
        className: classnames(s$7.bullet, STATIC_CUSTOMISATION_CLASSES.bullet)
      }, "1"), /*#__PURE__*/react.createElement("div", {
        className: STATIC_CUSTOMISATION_CLASSES.infoMessage
      }, isCurrentPlanP1X ? /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "eligibility-modal.p1x-bullet-1",
        defaultMessage: "Choisissez <strong>Alma - Payer maintenant</strong> au moment du paiement.",
        values: {
          strong: function strong() {
            return /*#__PURE__*/react.createElement("strong", null, [].slice.call(arguments));
          }
        }
      }) : /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "eligibility-modal.bullet-1",
        defaultMessage: "Choisissez <strong>Alma</strong> au moment du paiement.",
        values: {
          strong: function strong() {
            return /*#__PURE__*/react.createElement("strong", null, [].slice.call(arguments));
          }
        }
      }))), /*#__PURE__*/react.createElement("div", {
        className: s$7.listItem
      }, /*#__PURE__*/react.createElement("div", {
        className: classnames(s$7.bullet, STATIC_CUSTOMISATION_CLASSES.bullet)
      }, "2"), /*#__PURE__*/react.createElement("div", {
        className: STATIC_CUSTOMISATION_CLASSES.infoMessage
      }, isCurrentPlanP1X ? /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "eligibility-modal.p1x-bullet-2",
        defaultMessage: "Renseignez les informations de votre <strong>carte bancaire.</strong>",
        values: {
          strong: function strong() {
            return /*#__PURE__*/react.createElement("strong", null, [].slice.call(arguments));
          }
        }
      }) : /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "eligibility-modal.bullet-2",
        defaultMessage: "Laissez-vous guider et validez votre paiement en <strong>2 minutes.</strong>",
        values: {
          strong: function strong() {
            return /*#__PURE__*/react.createElement("strong", null, [].slice.call(arguments));
          }
        }
      }))), /*#__PURE__*/react.createElement("div", {
        className: s$7.listItem
      }, /*#__PURE__*/react.createElement("div", {
        className: classnames(s$7.bullet, STATIC_CUSTOMISATION_CLASSES.bullet)
      }, "3"), /*#__PURE__*/react.createElement("div", {
        className: STATIC_CUSTOMISATION_CLASSES.infoMessage
      }, isCurrentPlanP1X ? /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "eligibility-modal.p1x-bullet-3",
        defaultMessage: "<strong>La validation </strong> de votre paiement est instantan\xE9e !",
        values: {
          strong: function strong() {
            return /*#__PURE__*/react.createElement("strong", null, [].slice.call(arguments));
          }
        }
      }) : /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
        id: "eligibility-modal.bullet-3",
        defaultMessage: "<strong>Gardez le contr\xF4le</strong> en avan\xE7ant ou reculant vos \xE9ch\xE9ances \xE0 votre rythme.",
        values: {
          strong: function strong() {
            return /*#__PURE__*/react.createElement("strong", null, [].slice.call(arguments));
          }
        }
      }))))
    );
  };

  var s$8 = {"title":"_3ERx-"};

  var Title = function Title(_ref) {
    var isSomePlanDeferred = _ref.isSomePlanDeferred,
        isCurrentPlanP1X = _ref.isCurrentPlanP1X;
    return /*#__PURE__*/react.createElement("div", {
      className: classnames(s$8.title, STATIC_CUSTOMISATION_CLASSES.title),
      "data-testid": "modal-title-element"
    }, isSomePlanDeferred ? /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      id: "eligibility-modal.title-deferred-plan",
      defaultMessage: "Payez en plusieurs fois ou plus tard par carte bancaire avec Alma."
    }) : isCurrentPlanP1X ? /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      id: "eligibility-modal.title-pay-now",
      defaultMessage: "Payez comptant par carte bancaire avec Alma."
    }) : /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      id: "eligibility-modal.title-normal",
      defaultMessage: "Payez en plusieurs fois par carte bancaire avec Alma."
    }));
  };

  var s$9 = {"container":"_21g6u","block":"_3zaP5","left":"_2SBRC","logo":"_xW3wt"};

  var DesktopModal = function DesktopModal(_ref) {
    var children = _ref.children,
        isSomePlanDeferred = _ref.isSomePlanDeferred,
        cards = _ref.cards,
        isCurrentPlanP1X = _ref.isCurrentPlanP1X;
    return /*#__PURE__*/react.createElement("div", {
      className: classnames(s$9.container, STATIC_CUSTOMISATION_CLASSES.desktopModal),
      "data-testid": "modal-container"
    }, /*#__PURE__*/react.createElement("aside", {
      className: classnames([s$9.block, s$9.left, STATIC_CUSTOMISATION_CLASSES.leftSide])
    }, /*#__PURE__*/react.createElement(Title, {
      isSomePlanDeferred: isSomePlanDeferred,
      isCurrentPlanP1X: isCurrentPlanP1X
    }), /*#__PURE__*/react.createElement(Info, {
      isCurrentPlanP1X: isCurrentPlanP1X
    }), cards && /*#__PURE__*/react.createElement(Cards, {
      cards: cards
    }), /*#__PURE__*/react.createElement(AlmaLogo, {
      className: s$9.logo,
      width: "75"
    })), /*#__PURE__*/react.createElement("div", {
      className: classnames(s$9.block, STATIC_CUSTOMISATION_CLASSES.rightSide)
    }, children));
  };

  var s$a = {"noEligibility":"_17qNJ","loader":"_2oTJq","scheduleArea":"_2u9rj","verticalLine":"_VRdAU"};

  var s$b = {"container":"_2G7Ch","logo":"_2779r"};

  var MobileModal = function MobileModal(_ref) {
    var children = _ref.children,
        isSomePlanDeferred = _ref.isSomePlanDeferred,
        cards = _ref.cards,
        isCurrentPlanP1X = _ref.isCurrentPlanP1X;
    return /*#__PURE__*/react.createElement("div", {
      className: classnames(s$b.container, STATIC_CUSTOMISATION_CLASSES.mobileModal),
      "data-testid": "modal-container"
    }, /*#__PURE__*/react.createElement(Title, {
      isSomePlanDeferred: isSomePlanDeferred,
      isCurrentPlanP1X: isCurrentPlanP1X
    }), children, /*#__PURE__*/react.createElement(Info, {
      isCurrentPlanP1X: isCurrentPlanP1X
    }), cards && /*#__PURE__*/react.createElement(Cards, {
      cards: cards
    }), /*#__PURE__*/react.createElement(AlmaLogo, {
      className: s$b.logo,
      width: "75"
    }));
  };

  var EligibilityModal = function EligibilityModal(_ref) {
    var initialPlanIndex = _ref.initialPlanIndex,
        onClose = _ref.onClose,
        eligibilityPlans = _ref.eligibilityPlans,
        status = _ref.status,
        cards = _ref.cards;

    var _useState = react.useState(initialPlanIndex || 0),
        currentPlanIndex = _useState[0],
        setCurrentPlanIndex = _useState[1];

    var isBigScreen = reactResponsive.useMediaQuery({
      minWidth: desktopWidth
    });
    var ModalComponent = isBigScreen ? DesktopModal : MobileModal;
    var eligiblePlans = eligibilityPlans.filter(function (plan) {
      return plan.eligible;
    });
    var currentPlan = eligiblePlans[currentPlanIndex];
    var isSomePlanDeferred = eligibilityPlans.some(function (plan) {
      return plan.deferred_days > 0 || plan.deferred_months > 0;
    });
    return /*#__PURE__*/react.createElement(ControlledModal, {
      onClose: onClose,
      ariaHideApp: false,
      scrollable: true,
      isOpen: true
    }, /*#__PURE__*/react.createElement(ModalComponent, {
      isSomePlanDeferred: isSomePlanDeferred,
      cards: cards,
      isCurrentPlanP1X: isP1X(currentPlan)
    }, status === apiStatus.PENDING && /*#__PURE__*/react.createElement("div", {
      className: s$a.loader
    }, /*#__PURE__*/react.createElement(LoadingIndicator, null)), status === apiStatus.SUCCESS && eligiblePlans.length === 0 && /*#__PURE__*/react.createElement("div", {
      className: s$a.noEligibility
    }, /*#__PURE__*/react.createElement(MemoizedFormattedMessage, {
      id: "eligibility-modal.no-eligibility",
      defaultMessage: "Oups, il semblerait que la simulation n'ait pas fonctionn\xE9."
    })), status === apiStatus.SUCCESS && eligiblePlans.length >= 1 && /*#__PURE__*/react.createElement(react.Fragment, null, /*#__PURE__*/react.createElement(EligibilityPlansButtons, {
      eligibilityPlans: eligiblePlans,
      currentPlanIndex: currentPlanIndex,
      setCurrentPlanIndex: setCurrentPlanIndex
    }), /*#__PURE__*/react.createElement("div", {
      className: s$a.scheduleArea
    }, /*#__PURE__*/react.createElement("div", {
      className: s$a.verticalLine
    }), /*#__PURE__*/react.createElement(Schedule, {
      currentPlan: currentPlan
    }), /*#__PURE__*/react.createElement(TotalBlock, {
      currentPlan: currentPlan
    })))));
  };

  /**
   * This component allows to display only the modal, without PaymentPlans.
   */

  var ModalContainer = function ModalContainer(_ref) {
    var purchaseAmount = _ref.purchaseAmount,
        apiData = _ref.apiData,
        configPlans = _ref.configPlans,
        customerBillingCountry = _ref.customerBillingCountry,
        customerShippingCountry = _ref.customerShippingCountry,
        onClose = _ref.onClose,
        cards = _ref.cards;

    var _useFetchEligibility = useFetchEligibility(purchaseAmount, apiData, configPlans, customerBillingCountry, customerShippingCountry),
        eligibilityPlans = _useFetchEligibility[0],
        status = _useFetchEligibility[1];

    return /*#__PURE__*/react.createElement(EligibilityModal, {
      initialPlanIndex: 0,
      onClose: onClose,
      eligibilityPlans: eligibilityPlans,
      status: status,
      cards: cards
    });
  };

  var s$c = {"loadingIndicator":"_2SwwZ","line1":"_2qODo","line2":"_2YO01"};

  var Loader = function Loader(_ref) {
    var className = _ref.className;
    return /*#__PURE__*/react.createElement("div", {
      className: classnames(s$c.loadingIndicator, className),
      "data-testid": "loader"
    }, /*#__PURE__*/react.createElement("div", {
      className: s$c.line1
    }), /*#__PURE__*/react.createElement("div", {
      className: s$c.line2
    }));
  };

  var useButtonAnimation = function useButtonAnimation(iterateValues, transitionDelay) {
    var _useState = react.useState(0),
        current = _useState[0],
        setCurrent = _useState[1];

    var _useState2 = react.useState(true),
        update = _useState2[0],
        setUpdate = _useState2[1];

    react.useEffect(function () {
      var timeout;
      var isMounted = true;

      if (transitionDelay === -1) {
        return;
      }

      if (iterateValues.length !== 0) {
        if (!iterateValues.includes(current) && update) setCurrent(iterateValues[0]);
        timeout = setTimeout(function () {
          if (update && isMounted) {
            setCurrent(iterateValues[iterateValues.includes(current) ? (iterateValues.indexOf(current) + 1) % iterateValues.length : 0]);
          }
        }, transitionDelay);
      }

      return function () {
        isMounted = false;
        clearTimeout(timeout);
      };
    }, [iterateValues, current]);
    return {
      current: current,
      onHover: function onHover(current) {
        setCurrent(current);
        setUpdate(false);
      },
      onLeave: function onLeave() {
        setUpdate(true);
      }
    };
  };

  /**
   * It returns the **index** of the **first eligible plan** that matches the default installments count
   *
   * @param {number | number[]} suggestedPaymentPlan
   * @param {EligibilityPlan[]} eligibilityPlans
   * @returns number (index of the first eligible plan that matches the default installments count)
   */
  var getIndexOfActivePlan = function getIndexOfActivePlan(_ref) {
    var suggestedPaymentPlan = _ref.suggestedPaymentPlan,
        eligibilityPlans = _ref.eligibilityPlans;
    var suggestedPaymentPlanArray = Array.isArray(suggestedPaymentPlan) ? suggestedPaymentPlan : [suggestedPaymentPlan];

    var _loop = function _loop(index) {
      var installmentsCount = suggestedPaymentPlanArray[index];
      var planFound = eligibilityPlans.findIndex(function (plan) {
        return plan.installments_count === installmentsCount && plan.eligible && // Remove the PayLater plans from the plan to target, we will code a better solution later
        // To differentiate P1X from PayLater while using the `suggestedPaymentPlan` property.
        !plan.deferred_days && !plan.deferred_months;
      });

      if (planFound !== -1) {
        return {
          v: planFound
        };
      }
    };

    for (var index in suggestedPaymentPlanArray) {
      var _ret = _loop(index);

      if (typeof _ret === "object") return _ret.v;
    }

    return 0;
  };

  /**
   * Prefix classes to avoid name collisions.
   */
  var prefix$1 = 'alma-payment-plans';
  /**
   * Class names for the **payment plans** widget.
   * Those classes are intended to be used by the **merchant developer**.
   */

  var STATIC_CUSTOMISATION_CLASSES$1 = {
    container: prefix$1 + '-container',
    eligibilityLine: prefix$1 + '-eligibility-line',
    eligibilityOptions: prefix$1 + '-eligibility-options',
    notEligibleOption: prefix$1 + '-not-eligible-option',
    paymentInfo: prefix$1 + '-payment-info',
    activeOption: prefix$1 + '-active-option'
  };

  var s$d = {"widgetButton":"_TSkFv","logo":"_LJ4nZ","primaryContainer":"_bMClc","paymentPlans":"_17c_S","plan":"_2Kqjn","active":"_3dG_J","monochrome":"_2hx83","notEligible":"_3O1bg","hideBorder":"_3_qcn","info":"_25GrF","loader":"_30j1O","pending":"_1ZDMS","clickable":"_UksZa","unClickable":"_1lr-q"};

  var VERY_LONG_TIME_IN_MS = 1000 * 3600;
  var DEFAULT_TRANSITION_TIME = 5500;

  var PaymentPlanWidget = function PaymentPlanWidget(_ref) {
    var _cx, _cx3;

    var apiData = _ref.apiData,
        configPlans = _ref.configPlans,
        hideIfNotEligible = _ref.hideIfNotEligible,
        monochrome = _ref.monochrome,
        purchaseAmount = _ref.purchaseAmount,
        suggestedPaymentPlan = _ref.suggestedPaymentPlan,
        cards = _ref.cards,
        customerBillingCountry = _ref.customerBillingCountry,
        customerShippingCountry = _ref.customerShippingCountry,
        transitionDelay = _ref.transitionDelay,
        _ref$hideBorder = _ref.hideBorder,
        hideBorder = _ref$hideBorder === void 0 ? false : _ref$hideBorder,
        onModalClose = _ref.onModalClose;

    var _useFetchEligibility = useFetchEligibility(purchaseAmount, apiData, configPlans, customerBillingCountry, customerShippingCountry),
        eligibilityPlans = _useFetchEligibility[0],
        status = _useFetchEligibility[1];

    var eligiblePlans = eligibilityPlans.filter(function (plan) {
      return plan.eligible;
    });
    var activePlanIndex = getIndexOfActivePlan({
      eligibilityPlans: eligibilityPlans,
      suggestedPaymentPlan: suggestedPaymentPlan != null ? suggestedPaymentPlan : 0
    });
    var isSuggestedPaymentPlanSpecified = suggestedPaymentPlan !== undefined; // 👈  The merchant decided to focus a tab

    var isTransitionSpecified = transitionDelay !== undefined; // 👈  The merchant has specified a transition time

    var _useState = react.useState(false),
        isOpen = _useState[0],
        setIsOpen = _useState[1];

    var openModal = function openModal() {
      return setIsOpen(true);
    };

    var closeModal = function closeModal(event) {
      setIsOpen(false);
      onModalClose && onModalClose(event);
    };

    var eligiblePlanKeys = eligibilityPlans.reduce(function (acc, plan, index) {
      return plan.eligible ? [].concat(acc, [index]) : acc;
    }, []);
    /**
     * If merchand specify a suggestedPaymentPlan and no transition, we set a very long transition delay.
     * Otherwise, we set the transition delay specified by the merchant.
     * If none of those properties are specified, we set a default transition delay.
     * @returns
     */

    var realTransitionTime = function realTransitionTime() {
      if (isTransitionSpecified) {
        return transitionDelay != null ? transitionDelay : DEFAULT_TRANSITION_TIME;
      }

      if (isSuggestedPaymentPlanSpecified) {
        return VERY_LONG_TIME_IN_MS;
      }

      return DEFAULT_TRANSITION_TIME;
    };

    var _useButtonAnimation = useButtonAnimation(eligiblePlanKeys, realTransitionTime()),
        current = _useButtonAnimation.current,
        onHover = _useButtonAnimation.onHover,
        onLeave = _useButtonAnimation.onLeave;

    react.useEffect(function () {
      // When API has given a response AND the marchand set an active plan by default.
      if (status === apiStatus.SUCCESS && isSuggestedPaymentPlanSpecified) {
        onHover(activePlanIndex); // We select the first active plan possible

        onLeave(); // We need to call onLeave to reset the animation
      }
    }, [status]);
    /**
     * It takes a plan index and returns the index of that plan within the eligible plans
     *
     * @param {number} planIndex - The index of the plan that the user has selected.
     * @returns The index of the planKey in the eligiblePlanKeys array.
     */

    var getIndexWithinEligiblePlans = function getIndexWithinEligiblePlans(planIndex) {
      var index = eligiblePlanKeys.findIndex(function (planKey) {
        return planKey === planIndex;
      });
      return index === -1 ? 0 : index;
    };

    if (status === apiStatus.PENDING) {
      return /*#__PURE__*/react.createElement("div", {
        className: classnames(s$d.widgetButton, s$d.pending)
      }, /*#__PURE__*/react.createElement(Loader, null));
    }

    if (hideIfNotEligible && eligiblePlans.length === 0 || eligibilityPlans.length === 0 || status === apiStatus.FAILED) {
      return null;
    }

    var handleOpenModal = function handleOpenModal(e) {
      e.preventDefault();

      if (eligiblePlans.length > 0) {
        openModal();
      }
    };

    return /*#__PURE__*/react.createElement(react.Fragment, null, /*#__PURE__*/react.createElement("div", {
      onClick: handleOpenModal,
      className: classnames(s$d.widgetButton, (_cx = {}, _cx[s$d.clickable] = eligiblePlans.length > 0, _cx[s$d.unClickable] = eligiblePlans.length === 0, _cx[s$d.hideBorder] = hideBorder, _cx), STATIC_CUSTOMISATION_CLASSES$1.container),
      "data-testid": "widget-button"
    }, /*#__PURE__*/react.createElement("div", {
      className: classnames(s$d.primaryContainer, STATIC_CUSTOMISATION_CLASSES$1.eligibilityLine)
    }, /*#__PURE__*/react.createElement(AlmaLogo, {
      className: s$d.logo,
      color: monochrome ? 'var(--off-black)' : undefined
    }), /*#__PURE__*/react.createElement("div", {
      className: classnames(s$d.paymentPlans, STATIC_CUSTOMISATION_CLASSES$1.eligibilityOptions)
    }, eligibilityPlans.map(function (eligibilityPlan, key) {
      var _cx2;

      var isCurrent = key === current;
      return /*#__PURE__*/react.createElement("div", {
        key: key,
        onMouseEnter: function onMouseEnter() {
          return onHover(key);
        },
        onTouchStart: function onTouchStart() {
          return onHover(key);
        },
        onMouseOut: onLeave,
        onTouchEnd: onLeave,
        className: classnames(s$d.plan, (_cx2 = {}, _cx2[classnames(s$d.active, STATIC_CUSTOMISATION_CLASSES$1.activeOption)] = isCurrent, _cx2[s$d.monochrome] = monochrome && isCurrent, _cx2[classnames(s$d.notEligible, STATIC_CUSTOMISATION_CLASSES$1.notEligibleOption)] = !eligibilityPlan.eligible, _cx2))
      }, paymentPlanShorthandName(eligibilityPlan));
    }))), /*#__PURE__*/react.createElement("div", {
      className: classnames(s$d.info, (_cx3 = {}, _cx3[classnames(s$d.notEligible, STATIC_CUSTOMISATION_CLASSES$1.notEligibleOption)] = eligibilityPlans[current] && !eligibilityPlans[current].eligible, _cx3), STATIC_CUSTOMISATION_CLASSES$1.paymentInfo)
    }, eligibilityPlans.length !== 0 && paymentPlanInfoText(eligibilityPlans[current]))), isOpen && /*#__PURE__*/react.createElement(EligibilityModal, {
      initialPlanIndex: getIndexWithinEligiblePlans(current),
      onClose: closeModal,
      eligibilityPlans: eligiblePlans,
      status: status,
      cards: cards
    }));
  };

  var WidgetsController = /*#__PURE__*/function () {
    function WidgetsController(apiData) {
      this.apiData = apiData;
    }

    var _proto = WidgetsController.prototype;

    _proto.add = function add(widget, options) {
      var _this = this;

      var containerDiv = document.querySelector(options.container);

      if (containerDiv) {
        reactDom.unmountComponentAtNode(containerDiv);
      }

      if (widget === widgetTypes.PaymentPlans) {
        var container = options.container,
            purchaseAmount = options.purchaseAmount,
            plans = options.plans,
            transitionDelay = options.transitionDelay,
            hideIfNotEligible = options.hideIfNotEligible,
            _options$hideBorder = options.hideBorder,
            hideBorder = _options$hideBorder === void 0 ? false : _options$hideBorder,
            _options$monochrome = options.monochrome,
            monochrome = _options$monochrome === void 0 ? true : _options$monochrome,
            suggestedPaymentPlan = options.suggestedPaymentPlan,
            customerBillingCountry = options.customerBillingCountry,
            customerShippingCountry = options.customerShippingCountry,
            _options$locale = options.locale,
            locale = _options$locale === void 0 ? Locale.en : _options$locale,
            cards = options.cards,
            onModalClose = options.onModalClose;

        if (containerDiv) {
          reactDom.render( /*#__PURE__*/react.createElement(Provider$1, {
            locale: locale
          }, /*#__PURE__*/react.createElement(PaymentPlanWidget, {
            apiData: this.apiData,
            configPlans: plans,
            hideIfNotEligible: hideIfNotEligible,
            monochrome: monochrome,
            purchaseAmount: purchaseAmount,
            suggestedPaymentPlan: suggestedPaymentPlan,
            cards: cards,
            customerBillingCountry: customerBillingCountry,
            customerShippingCountry: customerShippingCountry,
            transitionDelay: transitionDelay,
            hideBorder: hideBorder,
            onModalClose: onModalClose
          })), document.querySelector(container));
        }
      }

      if (widget === widgetTypes.Modal) {
        var _container = options.container,
            clickableSelector = options.clickableSelector,
            _purchaseAmount = options.purchaseAmount,
            _plans = options.plans,
            _options$locale2 = options.locale,
            _locale = _options$locale2 === void 0 ? Locale.en : _options$locale2,
            _customerBillingCountry = options.customerBillingCountry,
            _customerShippingCountry = options.customerShippingCountry,
            _cards = options.cards,
            onClose = options.onClose;

        var close = function close(event) {
          if (!containerDiv) return;
          reactDom.unmountComponentAtNode(containerDiv);
          onClose && onClose(event);
        };

        var renderModal = function renderModal() {
          reactDom.render( /*#__PURE__*/react.createElement(Provider$1, {
            locale: _locale
          }, /*#__PURE__*/react.createElement(ModalContainer, {
            purchaseAmount: _purchaseAmount,
            apiData: _this.apiData,
            configPlans: _plans,
            customerBillingCountry: _customerBillingCountry,
            customerShippingCountry: _customerShippingCountry,
            onClose: close,
            cards: _cards
          })), document.querySelector(_container));
        }; // if clickableSelector is provided, add an onClick event handler to open the Modal.


        if (clickableSelector) {
          var _document$querySelect;

          (_document$querySelect = document.querySelector(clickableSelector)) == null ? void 0 : _document$querySelect.addEventListener('click', renderModal, false);
        }

        return {
          open: renderModal,
          close: close
        };
      }
    };

    return WidgetsController;
  }();

  (function (ApiMode) {
    ApiMode["LIVE"] = "https://api.getalma.eu";
    ApiMode["TEST"] = "https://api.sandbox.getalma.eu";
  })(exports.ApiMode || (exports.ApiMode = {}));

  (function (Widgets) {
    function initialize(merchantId, mode) {
      return new WidgetsController({
        domain: mode,
        merchantId: merchantId
      });
    }

    Widgets.initialize = initialize;
    Widgets.PaymentPlans = widgetTypes.PaymentPlans;
    Widgets.Modal = widgetTypes.Modal;
  })(exports.Widgets || (exports.Widgets = {})); // eslint-disable-next-line @typescript-eslint/no-namespace

  (function (Utils) {
    Utils.priceToCents = priceToCents;
    Utils.priceFromCents = priceFromCents;
    Utils.formatCents = formatCents;
  })(exports.Utils || (exports.Utils = {}));

})));
//# sourceMappingURL=widgets-wc.umd.js.map