parcelRequire=function(e,r,t,n){var i,o="function"==typeof parcelRequire&&parcelRequire,u="function"==typeof require&&require;function f(t,n){if(!r[t]){if(!e[t]){var i="function"==typeof parcelRequire&&parcelRequire;if(!n&&i)return i(t,!0);if(o)return o(t,!0);if(u&&"string"==typeof t)return u(t);var c=new Error("Cannot find module '"+t+"'");throw c.code="MODULE_NOT_FOUND",c}p.resolve=function(r){return e[t][1][r]||r},p.cache={};var l=r[t]=new f.Module(t);e[t][0].call(l.exports,p,l,l.exports,this)}return r[t].exports;function p(e){return f(p.resolve(e))}}f.isParcelRequire=!0,f.Module=function(e){this.id=e,this.bundle=f,this.exports={}},f.modules=e,f.cache=r,f.parent=o,f.register=function(r,t){e[r]=[function(e,r){r.exports=t},{}]};for(var c=0;c<t.length;c++)try{f(t[c])}catch(e){i||(i=e)}if(t.length){var l=f(t[t.length-1]);"object"==typeof exports&&"undefined"!=typeof module?module.exports=l:"function"==typeof define&&define.amd?define(function(){return l}):n&&(this[n]=l)}if(parcelRequire=f,i)throw i;return f}({"DCTP":[function(require,module,exports) {
function e(e){if(Array.isArray(e))return e}module.exports=e,module.exports.__esModule=!0,module.exports.default=module.exports;
},{}],"LoeL":[function(require,module,exports) {
function l(l,e){var r=null==l?null:"undefined"!=typeof Symbol&&l[Symbol.iterator]||l["@@iterator"];if(null!=r){var t,o,u=[],n=!0,a=!1;try{for(r=r.call(l);!(n=(t=r.next()).done)&&(u.push(t.value),!e||u.length!==e);n=!0);}catch(d){a=!0,o=d}finally{try{n||null==r.return||r.return()}finally{if(a)throw o}}return u}}module.exports=l,module.exports.__esModule=!0,module.exports.default=module.exports;
},{}],"jEQo":[function(require,module,exports) {
function e(e,o){(null==o||o>e.length)&&(o=e.length);for(var l=0,r=new Array(o);l<o;l++)r[l]=e[l];return r}module.exports=e,module.exports.__esModule=!0,module.exports.default=module.exports;
},{}],"Dbv9":[function(require,module,exports) {
var r=require("./arrayLikeToArray.js");function e(e,t){if(e){if("string"==typeof e)return r(e,t);var o=Object.prototype.toString.call(e).slice(8,-1);return"Object"===o&&e.constructor&&(o=e.constructor.name),"Map"===o||"Set"===o?Array.from(e):"Arguments"===o||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(o)?r(e,t):void 0}}module.exports=e,module.exports.__esModule=!0,module.exports.default=module.exports;
},{"./arrayLikeToArray.js":"jEQo"}],"MWEO":[function(require,module,exports) {
function e(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}module.exports=e,module.exports.__esModule=!0,module.exports.default=module.exports;
},{}],"DERy":[function(require,module,exports) {
var e=require("./arrayWithHoles.js"),r=require("./iterableToArrayLimit.js"),o=require("./unsupportedIterableToArray.js"),t=require("./nonIterableRest.js");function u(u,s){return e(u)||r(u,s)||o(u,s)||t()}module.exports=u,module.exports.__esModule=!0,module.exports.default=module.exports;
},{"./arrayWithHoles.js":"DCTP","./iterableToArrayLimit.js":"LoeL","./unsupportedIterableToArray.js":"Dbv9","./nonIterableRest.js":"MWEO"}],"Sjre":[function(require,module,exports) {
"use strict";Object.defineProperty(exports,"__esModule",{value:!0}),exports.default=void 0;var e=t(require("@babel/runtime/helpers/slicedToArray"));function t(e){return e&&e.__esModule?e:{default:e}}var r=wp,i=r.element,l=i.createElement,o=i.Fragment,a=i.useState,n=r.i18n.__,s=r.components,d=s.Placeholder,u=s.Disabled,c=s.SandBox,p=s.Button,b=s.ExternalLink,m=s.Spinner,y=s.ToolbarGroup,v=s.ToolbarButton,h=r.compose.compose,f=r.data.withSelect,_=r.blockEditor,g=_.RichText,w=_.BlockControls,k=bp,E=k.blockData.embedScriptURL,x=function(t){var r=t.attributes,i=t.setAttributes,s=t.isSelected,h=t.preview,f=t.fetching,_=r.url,k=r.caption,x=n("BuddyPress Activity URL","buddypress"),P=a(_),L=(0,e.default)(P,2),N=L[0],R=L[1],S=a(!_),B=(0,e.default)(S,2),T=B[0],U=B[1],A=l(w,null,l(y,null,l(v,{icon:"edit",title:n("Edit URL","buddypress"),onClick:function(e){e&&e.preventDefault(),U(!0)}})));return T?l(d,{icon:"buddicons-activity",label:x,className:"wp-block-embed",instructions:n("Paste the link to the activity content you want to display on your site.","buddypress")},l("form",{onSubmit:function(e){e&&e.preventDefault(),U(!1),i({url:N})}},l("input",{type:"url",value:N||"",className:"components-placeholder__input","aria-label":x,placeholder:n("Enter URL to embed here…","buddypress"),onChange:function(e){return R(e.target.value)}}),l(p,{isPrimary:!0,type:"submit"},n("Embed","buddypress"))),l("div",{className:"components-placeholder__learn-more"},l(b,{href:n("https://codex.buddypress.org/activity-embeds/")},n("Learn more about activity embeds","buddypress")))):f?l("div",{className:"wp-block-embed is-loading"},l(m,null),l("p",null,n("Embedding…","buddypress"))):h&&h.x_buddypress&&"activity"===h.x_buddypress?l(o,null,!T&&A,l("figure",{className:"wp-block-embed is-type-bp-activity"},l("div",{className:"wp-block-embed__wrapper"},l(u,null,l(c,{html:h&&h.html?h.html:"",scripts:[E]}))),(!g.isEmpty(k)||s)&&l(g,{tagName:"figcaption",placeholder:n("Write caption…","buddypress"),value:k,onChange:function(e){return i({caption:e})},inlineToolbar:!0}))):l(o,null,A,l(d,{icon:"buddicons-activity",label:x},l("p",{className:"components-placeholder__error"},n("The URL you provided is not a permalink to a public BuddyPress Activity. Please use another URL.","buddypress"))))},P=h([f(function(e,t){var r=t.attributes.url,i=e("core"),l=i.getEmbedPreview,o=i.isRequestingEmbedPreview;return{preview:!!r&&l(r),fetching:!!r&&o(r)}})])(x),L=P;exports.default=L;
},{"@babel/runtime/helpers/slicedToArray":"DERy"}],"zmBI":[function(require,module,exports) {
"use strict";Object.defineProperty(exports,"__esModule",{value:!0}),exports.default=void 0;var e=wp,t=e.blockEditor.RichText,a=e.element.createElement,r=function(e){var r=e.attributes,i=r.url,c=r.caption;return i?a("figure",{className:"wp-block-embed is-type-bp-activity"},a("div",{className:"wp-block-embed__wrapper"},"\n".concat(i,"\n")),!t.isEmpty(c)&&a(t.Content,{tagName:"figcaption",value:c})):null},i=r;exports.default=i;
},{}],"hBDw":[function(require,module,exports) {
"use strict";var t=i(require("./embed-activity/edit")),e=i(require("./embed-activity/save"));function i(t){return t&&t.__esModule?t:{default:t}}var r=wp,s=r.i18n.__,d=r.blocks.registerBlockType;d("bp/embed-activity",{title:s("Embed an activity","buddypress"),description:s("Add a block that displays the activity content pulled from this or other community sites.","buddypress"),icon:{background:"#fff",foreground:"#d84800",src:"buddicons-activity"},category:"buddypress",attributes:{url:{type:"string"},caption:{type:"string",source:"html",selector:"figcaption"}},supports:{align:!0},edit:t.default,save:e.default});
},{"./embed-activity/edit":"Sjre","./embed-activity/save":"zmBI"}]},{},["hBDw"], null)
//# sourceMappingURL=/bp-activity/js/blocks/embed-activity.js.map