var PageClass = function () {
    if(this === window){
        return new PageClass();
    }

    this.states = {};
    this.elements = {};
};

PageClass.prototype.helpers = {
    inObj: function (obj, key) {
        var self = this;

        if(self.isObj(obj) && obj.hasOwnProperty(key) || key in obj) {
            return true;
        }

        if(self.isArray(obj)) {
            return obj.some(function (item) {
                if(self.isObj(item) || self.isArray(item)) {
                    return self.inObj(item, key);
                }

                return item === key || isNaN(item) && isNaN(key);
            });
        }

        return Object.keys(obj).some(function (k) {
            if(self.isObj(obj[k]) || self.isArray(obj[k])) {
                return self.inObj(obj[k], key);
            }
        });
    },

    getType: function (el) {
        return Object.prototype.toString.call(el).split(' ')[1].toLowerCase().slice(0, -1);
    },

    propertyPath: function (obj, prop) {
        var self = this;

        if(this.isObj(obj) && !this.isEmptyObj(obj) || this.isArray(obj) && !this.isEmptyArray(obj) && prop) {
            for(var key in obj) {
                if (prop === key) {
                    return prop;
                } else if (self.isObj(obj[key]) || self.isArray(obj[key]) && (self.inObj(obj[key], prop))) {
                    var result = self.propertyPath(obj[key], prop);

                    if(result) {
                        return key + '.' + result;
                    }
                } else {
                    continue;
                }
            }
        }

        return '';
    },

    getProp: function (obj, prop) {
        if((this.isObj(obj) && !this.isEmptyObj(obj) && this.inObj(obj, prop)) || (this.isArray(obj) && !this.isEmptyArray(obj) && this.inObj(obj, prop))) {

            if(arguments.length > 2) {
                for (var i = 1; i < arguments.length; i++) {
                    obj = this.getProp(obj, arguments[i]);
                }

                return obj;
            }

            return this.propertyPath(obj, prop).split('.').reduce(function (acc, key) {
                return acc[key];
            }, obj);
        }
    },

    objValues: function (obj) {
        var self = this;

        if(this.isObj(obj) && !this.isEmptyObj(obj)) {
            return Object.keys(obj).reduce(function (accumulator, keyNext) {
                return accumulator.concat(self.getType( keyNext ) === 'string' && obj.propertyIsEnumerable(keyNext) ? [obj[keyNext]] : [])
            }, []);
        } else {
            return [];
        }
    },


    objEntries: function (obj) {
        var self = this;

        if(this.isObj(obj) && !this.isEmptyObj(obj)) {
            return Object.keys(obj).reduce(function (accumulator, keyNext) {
                return accumulator.concat(self.getType( keyNext ) === 'string' && obj.propertyIsEnumerable(keyNext) ? [[keyNext, obj[keyNext]]] : [])
            }, []);
        } else {
            return [];
        }
    },

    eachObj: function (obj, callback) {
        if(this.isObj(obj) && !this.isEmptyObj(obj) && this.isFunction(callback)){
            var i = 0;
            for(var key in obj){
                callback(key, obj[key], i);

                i++;
            }
        }
    },

    isJson: function(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    },

    toJson: function (obj) {
        if(this.isset(obj) && obj){
            return JSON.stringify(obj);
        }
    },

    fromJson: function (obj) {
        if(this.isJson(obj)){
            return JSON.parse(obj);
        }
    },

    isFunction: function (callback) {
        return this.getType(callback) === 'function';
    },

    isBoolean: function (bool) {
        return this.getType(bool) === 'boolean';
    },

    isNum: function (val) {
        return typeof val === 'number'
            && Number.isFinite(val);
    },

    isEmptyObj: function (obj) {
        if(this.isObj(obj)){
            for(var key in obj){
                return false
            }

            return true;
        }

        return null;
    },

    isObj: function (obj) {
        return this.getType(obj) === 'object';
    },

    isArray: function (arr) {
        return Array.isArray(arr);
    },

    toArray: function (elem) {
        if(elem.length !== undefined && (elem instanceof NodeList || elem instanceof HTMLCollection || elem instanceof DOMTokenList || this.getType(elem) === 'arguments')){
            return Array.prototype.slice.call(elem);
        }
    },

    isEmptyArray: function (arr) {
        if(this.isArray(arr)) return !arr.length;
    },

    inArrayOld: function (arr, key, prop) {
        if(this.isArray(arr) && (!prop && !this.isArray(key))) {
            return arr.indexOf(key) !== -1;
        }
    },

    /**
     * inArray([1,2,3],1) // true; inArray([1,NaN,2,3], NaN) // true
     * @param arr
     * @param key
     * @param index
     * @returns {boolean}
     */

    inArray: function (arr, key, index) {
        if(this.isArray(arr) && !this.isEmptyArray(arr)) {
            var num = index || 0;
            var length = arr.length;

            var k = Math.max(num >= 0 ? num : length - Math.abs(num), 0);

            function condition(x, y) {
                return x === y || (typeof x === 'number' && typeof y === 'number' && isNaN(x) && isNaN(y));
            }

            while (k < length) {
                if(condition(arr[k], key)) {
                    return true;
                }

                k++;
            }

            return false;
        }
    },

    isString: function (str) {
        return this.getType(str) === 'string';
    },

    isEmptyString: function (str) {
        if(this.isString(str)){
            return (str.length === 0 || str === '');
        }
    },

    unique: function (arr) {
        if(this.isArray(arr)){
            var newArr = [], elem;

            for(var i = 0; i < arr.length; i++){
                elem = arr[i];

                if(!this.inArray(newArr, elem)) newArr.push(elem);
                else continue;
            }

            return newArr;
        }
    },

    isError: function (err) {
        return err instanceof Error;
    },

    filter: function (arr, callback) {
        if(this.isArray(arr) && !this.isEmptyArray(arr) && this.isFunction(callback)){
            var res = [];

            for(var i = 0; i < arr.length; i++){
                if(callback(arr[i], i, arr)){
                    res.push(arr[i]);
                }
            }

            return res;
        }
    },

    map: function (arr, callback) {
        if(this.isArray(arr) && this.isFunction(callback)){
            for(var i = 0; i < arr.length; i++){
                arr[i] = callback(arr[i], i, arr);
            }

            return arr;
        }
    },

    each: function (arr, callback) {
        if(this.isArray(arr)){
            if(this.isFunction(callback)){
                for(var i = 0; i < arr.length; i++){
                    callback(arr[i], i, arr);
                }
            }else{
                throw new Error('Second argument must be a function');
            }
        } else if (this.isObj(arr)) {
            this.eachObj(arr, callback);
        }else{
            throw new Error('First argument must be an array or object');
        }
    },

    isset: function (elem) {
        if(this.isArray(elem)){
            return !this.isEmptyArray(elem);
        }else if(this.isObj(elem)){
            return !this.isEmptyObj(elem);
        }else if(this.isString(elem)){
            return !this.isEmptyString(elem);
        }else{
            return elem !== undefined;
        }
    },

    /**
     * assign({a:1}, {b:2}, {c:3}) // {a:1, b:2, c:3}
     * @param target
     * @returns {*}
     */

    assign: function(target){
        if (target === undefined || target === null) {
            throw new TypeError('Невозможно сконвертировать первый аргумент в объект');
        }

        var to = Object(target);

        for (var i = 1; i < arguments.length; i++) {

            var next = arguments[i];

            if (next === undefined || next === null) {
                continue;
            }

            var keysArray = Object.keys(Object(next));

            for (var nextIndex = 0, len = keysArray.length; nextIndex < len; nextIndex++) {

                var nextKey = keysArray[nextIndex];
                var desc = Object.getOwnPropertyDescriptor(next, nextKey);

                if (desc !== undefined && desc.enumerable) {
                    to[nextKey] = next[nextKey];
                }
            }
        }

        return to;
    },

    objectLength: function(obj){
        if(this.isObj(obj)){
            return Object.keys(obj).length;
        }
    },

    /**
     * inArrayOfObjects([{a:1, b:2, c:3}],{c:3}] // true
     * @param array
     * @param obj
     * @returns {boolean}
     */

    inArrayOfObjects: function (array, obj) {
        if(this.isArray(array) && !this.isEmptyArray(array) && this.isObj(obj) && !this.isEmptyObj(obj)) {
            var self = this;

            return array.some(function (o) {
                return Object.keys(obj).every(function (key) {
                    return (self.isObj(o[key]) && !self.isEmptyObj(o[key])) ? self.hasKeysFromObject(obj[key], o[key]) : (self.inObj(o, key) && o[key] === obj[key]);
                });
            });
        }
    },

    /**
     * isEqualObjects({a:1},{a:1}) // true, isEqualObjects({a:1, b:2},{a:1}) // false
     * @param obj1
     * @param obj2
     * @returns {boolean}
     */

    isEqualObjects: function(obj1, obj2) {
        var length1 = this.objectLength(obj1);
        var length2 = this.objectLength(obj2);
        var self = this;

        if(length1 === length2) {
            return Object.keys(obj1).every(function(key) {
                if(self.isObj(obj1[key]) && !self.isEmptyObj(obj1[key]) && self.isObj(obj2[key]) && !self.isEmptyObj(obj2[key])) {
                    return self.isEqualObjects(obj1[key], obj2[key]);
                }

                return obj1[key] === obj2[key];
            });
        } else {
            return false;
        }
    },

    hasKeysFromObject: function(obj1, obj2) {
        var self = this;

        if(this.isObj(obj1) && !this.isEmptyObj(obj1) && this.isObj(obj2) && !this.isEmptyObj(obj2)) {
            return Object.keys(obj1).every(function(key) {
                if(self.isObj(obj1[key]) && !self.isEmptyObj(obj1[key]) && self.isObj(obj2[key]) && !self.isEmptyObj(obj2[key])) {
                    return self.hasKeysFromObject(obj1[key], obj2[key]);
                }

                return obj1[key] === obj2[key];
            });
        } else {
            return false;
        }
    },

    location: {
        name: window.location.pathname,

        has: function (chunk) {
            var addSlashes = require('addSlashes');
            var r = new RegExp('^' + addSlashes( chunk ) + '$', 'ig');

            return !!(this.name.match(r));
        },

        host: function () {
            return this.location.origin;
        }
    },

    uploadImg: function(img, addTo) {
        if(img.files && img.files[0]) {
            var reader = new FileReader();

            reader.onload = function(event){

                if(addTo) {
                    addTo.attr('src', event.target.result);
                } else {
                    $(img).parent().find('img').attr('src', event.target.result).parent().show();
                }
            };

            reader.readAsDataURL(img.files[0]);
        }
    },

    addSlashes: function (str) {
        return (str + '')
            .replace(/[\\"']/g, '\\$&')
            .replace(/\u0000/g, '\\0')
            .replace(/\*/g, '\\*')
            .replace(/\./g, '\\.')
            .replace(/\\/g, '\\\\');
    }
};

/**
 * служит хранилищем для элементов страницы
 * если переданы и key и value - записывает в elements класса PageClass ключ key со значением value
 * если передан только key - проверяет, если key - массив с ключами - вернет несколько элементов
 * иначе вернет элемент, записанный под этим ключем
 * если не переданы ни key ни value - вернет объект elements
 * @param key
 * @param value
 * @returns {*}
 */

PageClass.prototype.el = function (key, value) {
    var isArray = require('isArray'),
        isEmptyArray = require('isEmptyArray'),
        isString = require('isString'),
        isEmptyString = require('isEmptyString');

    if(key === undefined && value === undefined) {
        return this.elements;
    }

    if(key !== undefined && value === undefined) {
        if(isArray(key) && !isEmptyArray(key)) {
            var self = this;
            var arr = [];

            key.forEach(function (string) {
                if(self.elements[string].get().length > 1) {
                    arr = [].concat(arr, self.elements[string].get());
                } else {
                    arr.push(self.elements[string].get(0))
                }
            });

            return $(arr);
        } else if (isString(key) && !isEmptyString(key)) {
            return this.elements[key];
        }
    }

    this.elements[key] = value;

    return this;
};

/**
 * @param state
 * @returns {*}
 *
 * разбивает строку по разделителю "." (точка), после чего внутри объекта states ищет нужную функцию
 */

PageClass.prototype.state = function (state) {
    if(typeof state !== 'undefined') {
        var keys = state.split('.');
        var object = {};
        var key;

        var isString = require('isString'),
            isArray = require('isArray'),
            isEmptyArray = require('isEmptyArray'),
            inObj = require('inObj');

        if(isString(state) && isArray(keys) && !isEmptyArray(keys)){
            if(keys.length > 1){

                if(inObj(this.states, keys[0])) {
                    object = this.states[keys[0]];

                    for(var i = 0; i < keys.length; i++){
                        key = keys[i];

                        if(inObj(object, key)){
                            object = object[key];
                        }
                    }

                    return object;
                }
            } else {
                if(inObj(this.states, state)){
                    return this.states[state];
                }
            }
        }
    }
};

PageClass.prototype.page = function (path, callback) {
    var location = require('location'),
        isFunction = require('isFunction');

    if((location.has(path) || path === '*') && isFunction(callback)) {
        callback.call(this);
    }
};

PageClass.prototype.setVars = function () {

    var self = this;

    this.page('/', function () {

    });

    return this;
};

PageClass.prototype.setStates = function () {
    var self = this;
    
    this.page('/', function () {

    });

    return this;
};

PageClass.prototype.init = function () {
    this.setVars().setStates();

    return this;
};

function require(name) {
    return typeof page.helpers[name] === 'function' ? page.helpers[name].bind(page.helpers) : page.helpers[name] !== undefined ? page.helpers[name] : page.helpers;
}

var page = new PageClass();

$(document).ready(function () {
    page.init();
});