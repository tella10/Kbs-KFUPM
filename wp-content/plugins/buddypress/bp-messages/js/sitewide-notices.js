parcelRequire=function(e,r,t,n){var i,o="function"==typeof parcelRequire&&parcelRequire,u="function"==typeof require&&require;function f(t,n){if(!r[t]){if(!e[t]){var i="function"==typeof parcelRequire&&parcelRequire;if(!n&&i)return i(t,!0);if(o)return o(t,!0);if(u&&"string"==typeof t)return u(t);var c=new Error("Cannot find module '"+t+"'");throw c.code="MODULE_NOT_FOUND",c}p.resolve=function(r){return e[t][1][r]||r},p.cache={};var l=r[t]=new f.Module(t);e[t][0].call(l.exports,p,l,l.exports,this)}return r[t].exports;function p(e){return f(p.resolve(e))}}f.isParcelRequire=!0,f.Module=function(e){this.id=e,this.bundle=f,this.exports={}},f.modules=e,f.cache=r,f.parent=o,f.register=function(r,t){e[r]=[function(e,r){r.exports=t},{}]};for(var c=0;c<t.length;c++)try{f(t[c])}catch(e){i||(i=e)}if(t.length){var l=f(t[t.length-1]);"object"==typeof exports&&"undefined"!=typeof module?module.exports=l:"function"==typeof define&&define.amd?define(function(){return l}):n&&(this[n]=l)}if(parcelRequire=f,i)throw i;return f}({"nIsC":[function(require,module,exports) {
function e(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function t(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}function n(e,n,o){return n&&t(e.prototype,n),o&&t(e,o),Object.defineProperty(e,"prototype",{writable:!1}),e}var o=function(){function t(n){e(this,t);var o=n.path,i=n.dismissPath,r=n.root,c=n.nonce;this.path=o,this.dismissPath=i,this.root=r,this.nonce=c}return n(t,[{key:"start",value:function(){var e=this;document.querySelectorAll(".bp-sitewide-notice-block a.dismiss-notice").forEach(function(t){t.addEventListener("click",function(t){t.preventDefault(),fetch(e.root+e.dismissPath,{method:"POST",headers:{"X-WP-Nonce":e.nonce}}).then(function(e){return e.json()}).then(function(e){void 0!==e&&void 0!==e.dismissed&&e.dismissed&&document.querySelectorAll(".bp-sitewide-notice-block").forEach(function(e){e.remove()})})})})}}]),t}(),i=window.bpSitewideNoticeBlockSettings||{},r=new o(i);"loading"===document.readyState?document.addEventListener("DOMContentLoaded",r.start()):r.start();
},{}]},{},["nIsC"], null)
//# sourceMappingURL=/bp-messages/js/sitewide-notices.js.map