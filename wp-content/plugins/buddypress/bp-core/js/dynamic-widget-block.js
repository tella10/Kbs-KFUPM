parcelRequire=function(e,r,t,n){var i,o="function"==typeof parcelRequire&&parcelRequire,u="function"==typeof require&&require;function f(t,n){if(!r[t]){if(!e[t]){var i="function"==typeof parcelRequire&&parcelRequire;if(!n&&i)return i(t,!0);if(o)return o(t,!0);if(u&&"string"==typeof t)return u(t);var c=new Error("Cannot find module '"+t+"'");throw c.code="MODULE_NOT_FOUND",c}p.resolve=function(r){return e[t][1][r]||r},p.cache={};var l=r[t]=new f.Module(t);e[t][0].call(l.exports,p,l,l.exports,this)}return r[t].exports;function p(e){return f(p.resolve(e))}}f.isParcelRequire=!0,f.Module=function(e){this.id=e,this.bundle=f,this.exports={}},f.modules=e,f.cache=r,f.parent=o,f.register=function(r,t){e[r]=[function(e,r){r.exports=t},{}]};for(var c=0;c<t.length;c++)try{f(t[c])}catch(e){i||(i=e)}if(t.length){var l=f(t[t.length-1]);"object"==typeof exports&&"undefined"!=typeof module?module.exports=l:"function"==typeof define&&define.amd?define(function(){return l}):n&&(this[n]=l)}if(parcelRequire=f,i)throw i;return f}({"eNhW":[function(require,module,exports) {
function e(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function t(e,t){for(var o=0;o<t.length;o++){var n=t[o];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}function o(e,o,n){return o&&t(e.prototype,o),n&&t(e,n),Object.defineProperty(e,"prototype",{writable:!1}),e}var n=wp,i=n.url.addQueryArgs,s=lodash,r=s.template;window.bp=window.bp||{},bp.dynamicWidgetBlock=function(){function t(o,n){var i=this;e(this,t);var s=o.path,r=o.root,a=o.nonce;this.path=s,this.root=r,this.nonce=a,this.blocks=n,this.blocks.forEach(function(e,t){var o=(e.query_args||"active").type,n=(e.preloaded||[]).body;i.blocks[t].items={active:[],newest:[],popular:[],alphabetical:[]},!i.blocks[t].items[o].length&&n&&n.length&&(i.blocks[t].items[o]=n)})}return o(t,[{key:"useTemplate",value:function(e){return r(document.querySelector("#tmpl-"+e).innerHTML,{evaluate:/<#([\s\S]+?)#>/g,interpolate:/\{\{\{([\s\S]+?)\}\}\}/g,escape:/\{\{([^\}]+?)\}\}(?!\})/g,variable:"data"})}},{key:"loop",value:function(){}},{key:"getItems",value:function(){var e=this,t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"active",o=arguments.length>1&&void 0!==arguments[1]?arguments[1]:0;this.blocks[o].query_args.type=t,this.blocks[o].items[t].length?this.loop(this.blocks[o].items[t],this.blocks[o].selector,t):fetch(i(this.root+this.path,this.blocks[o].query_args),{method:"GET",headers:{"X-WP-Nonce":this.nonce}}).then(function(e){return e.json()}).then(function(n){e.blocks[o].items[t]=n,e.loop(e.blocks[o].items[t],e.blocks[o].selector,t)})}}]),t}();
},{}]},{},["eNhW"], null)
//# sourceMappingURL=/bp-core/js/dynamic-widget-block.js.map