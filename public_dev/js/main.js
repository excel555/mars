/* Zepto v1.1.6 - zepto event ajax form ie - zeptojs.com/license */
var Zepto=function(){function L(t){return null==t?String(t):j[S.call(t)]||"object"}function Z(t){return"function"==L(t)}function _(t){return null!=t&&t==t.window}function $(t){return null!=t&&t.nodeType==t.DOCUMENT_NODE}function D(t){return"object"==L(t)}function M(t){return D(t)&&!_(t)&&Object.getPrototypeOf(t)==Object.prototype}function R(t){return"number"==typeof t.length}function k(t){return s.call(t,function(t){return null!=t})}function z(t){return t.length>0?n.fn.concat.apply([],t):t}function F(t){return t.replace(/::/g,"/").replace(/([A-Z]+)([A-Z][a-z])/g,"$1_$2").replace(/([a-z\d])([A-Z])/g,"$1_$2").replace(/_/g,"-").toLowerCase()}function q(t){return t in f?f[t]:f[t]=new RegExp("(^|\\s)"+t+"(\\s|$)")}function H(t,e){return"number"!=typeof e||c[F(t)]?e:e+"px"}function I(t){var e,n;return u[t]||(e=a.createElement(t),a.body.appendChild(e),n=getComputedStyle(e,"").getPropertyValue("display"),e.parentNode.removeChild(e),"none"==n&&(n="block"),u[t]=n),u[t]}function V(t){return"children"in t?o.call(t.children):n.map(t.childNodes,function(t){return 1==t.nodeType?t:void 0})}function B(n,i,r){for(e in i)r&&(M(i[e])||A(i[e]))?(M(i[e])&&!M(n[e])&&(n[e]={}),A(i[e])&&!A(n[e])&&(n[e]=[]),B(n[e],i[e],r)):i[e]!==t&&(n[e]=i[e])}function U(t,e){return null==e?n(t):n(t).filter(e)}function J(t,e,n,i){return Z(e)?e.call(t,n,i):e}function X(t,e,n){null==n?t.removeAttribute(e):t.setAttribute(e,n)}function W(e,n){var i=e.className||"",r=i&&i.baseVal!==t;return n===t?r?i.baseVal:i:void(r?i.baseVal=n:e.className=n)}function Y(t){try{return t?"true"==t||("false"==t?!1:"null"==t?null:+t+""==t?+t:/^[\[\{]/.test(t)?n.parseJSON(t):t):t}catch(e){return t}}function G(t,e){e(t);for(var n=0,i=t.childNodes.length;i>n;n++)G(t.childNodes[n],e)}var t,e,n,i,C,N,r=[],o=r.slice,s=r.filter,a=window.document,u={},f={},c={"column-count":1,columns:1,"font-weight":1,"line-height":1,opacity:1,"z-index":1,zoom:1},l=/^\s*<(\w+|!)[^>]*>/,h=/^<(\w+)\s*\/?>(?:<\/\1>|)$/,p=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/gi,d=/^(?:body|html)$/i,m=/([A-Z])/g,g=["val","css","html","text","data","width","height","offset"],v=["after","prepend","before","append"],y=a.createElement("table"),x=a.createElement("tr"),b={tr:a.createElement("tbody"),tbody:y,thead:y,tfoot:y,td:x,th:x,"*":a.createElement("div")},w=/complete|loaded|interactive/,E=/^[\w-]*$/,j={},S=j.toString,T={},O=a.createElement("div"),P={tabindex:"tabIndex",readonly:"readOnly","for":"htmlFor","class":"className",maxlength:"maxLength",cellspacing:"cellSpacing",cellpadding:"cellPadding",rowspan:"rowSpan",colspan:"colSpan",usemap:"useMap",frameborder:"frameBorder",contenteditable:"contentEditable"},A=Array.isArray||function(t){return t instanceof Array};return T.matches=function(t,e){if(!e||!t||1!==t.nodeType)return!1;var n=t.webkitMatchesSelector||t.mozMatchesSelector||t.oMatchesSelector||t.matchesSelector;if(n)return n.call(t,e);var i,r=t.parentNode,o=!r;return o&&(r=O).appendChild(t),i=~T.qsa(r,e).indexOf(t),o&&O.removeChild(t),i},C=function(t){return t.replace(/-+(.)?/g,function(t,e){return e?e.toUpperCase():""})},N=function(t){return s.call(t,function(e,n){return t.indexOf(e)==n})},T.fragment=function(e,i,r){var s,u,f;return h.test(e)&&(s=n(a.createElement(RegExp.$1))),s||(e.replace&&(e=e.replace(p,"<$1></$2>")),i===t&&(i=l.test(e)&&RegExp.$1),i in b||(i="*"),f=b[i],f.innerHTML=""+e,s=n.each(o.call(f.childNodes),function(){f.removeChild(this)})),M(r)&&(u=n(s),n.each(r,function(t,e){g.indexOf(t)>-1?u[t](e):u.attr(t,e)})),s},T.Z=function(t,e){return t=t||[],t.__proto__=n.fn,t.selector=e||"",t},T.isZ=function(t){return t instanceof T.Z},T.init=function(e,i){var r;if(!e)return T.Z();if("string"==typeof e)if(e=e.trim(),"<"==e[0]&&l.test(e))r=T.fragment(e,RegExp.$1,i),e=null;else{if(i!==t)return n(i).find(e);r=T.qsa(a,e)}else{if(Z(e))return n(a).ready(e);if(T.isZ(e))return e;if(A(e))r=k(e);else if(D(e))r=[e],e=null;else if(l.test(e))r=T.fragment(e.trim(),RegExp.$1,i),e=null;else{if(i!==t)return n(i).find(e);r=T.qsa(a,e)}}return T.Z(r,e)},n=function(t,e){return T.init(t,e)},n.extend=function(t){var e,n=o.call(arguments,1);return"boolean"==typeof t&&(e=t,t=n.shift()),n.forEach(function(n){B(t,n,e)}),t},T.qsa=function(t,e){var n,i="#"==e[0],r=!i&&"."==e[0],s=i||r?e.slice(1):e,a=E.test(s);return $(t)&&a&&i?(n=t.getElementById(s))?[n]:[]:1!==t.nodeType&&9!==t.nodeType?[]:o.call(a&&!i?r?t.getElementsByClassName(s):t.getElementsByTagName(e):t.querySelectorAll(e))},n.contains=a.documentElement.contains?function(t,e){return t!==e&&t.contains(e)}:function(t,e){for(;e&&(e=e.parentNode);)if(e===t)return!0;return!1},n.type=L,n.isFunction=Z,n.isWindow=_,n.isArray=A,n.isPlainObject=M,n.isEmptyObject=function(t){var e;for(e in t)return!1;return!0},n.inArray=function(t,e,n){return r.indexOf.call(e,t,n)},n.camelCase=C,n.trim=function(t){return null==t?"":String.prototype.trim.call(t)},n.uuid=0,n.support={},n.expr={},n.map=function(t,e){var n,r,o,i=[];if(R(t))for(r=0;r<t.length;r++)n=e(t[r],r),null!=n&&i.push(n);else for(o in t)n=e(t[o],o),null!=n&&i.push(n);return z(i)},n.each=function(t,e){var n,i;if(R(t)){for(n=0;n<t.length;n++)if(e.call(t[n],n,t[n])===!1)return t}else for(i in t)if(e.call(t[i],i,t[i])===!1)return t;return t},n.grep=function(t,e){return s.call(t,e)},window.JSON&&(n.parseJSON=JSON.parse),n.each("Boolean Number String Function Array Date RegExp Object Error".split(" "),function(t,e){j["[object "+e+"]"]=e.toLowerCase()}),n.fn={forEach:r.forEach,reduce:r.reduce,push:r.push,sort:r.sort,indexOf:r.indexOf,concat:r.concat,map:function(t){return n(n.map(this,function(e,n){return t.call(e,n,e)}))},slice:function(){return n(o.apply(this,arguments))},ready:function(t){return w.test(a.readyState)&&a.body?t(n):a.addEventListener("DOMContentLoaded",function(){t(n)},!1),this},get:function(e){return e===t?o.call(this):this[e>=0?e:e+this.length]},toArray:function(){return this.get()},size:function(){return this.length},remove:function(){return this.each(function(){null!=this.parentNode&&this.parentNode.removeChild(this)})},each:function(t){return r.every.call(this,function(e,n){return t.call(e,n,e)!==!1}),this},filter:function(t){return Z(t)?this.not(this.not(t)):n(s.call(this,function(e){return T.matches(e,t)}))},add:function(t,e){return n(N(this.concat(n(t,e))))},is:function(t){return this.length>0&&T.matches(this[0],t)},not:function(e){var i=[];if(Z(e)&&e.call!==t)this.each(function(t){e.call(this,t)||i.push(this)});else{var r="string"==typeof e?this.filter(e):R(e)&&Z(e.item)?o.call(e):n(e);this.forEach(function(t){r.indexOf(t)<0&&i.push(t)})}return n(i)},has:function(t){return this.filter(function(){return D(t)?n.contains(this,t):n(this).find(t).size()})},eq:function(t){return-1===t?this.slice(t):this.slice(t,+t+1)},first:function(){var t=this[0];return t&&!D(t)?t:n(t)},last:function(){var t=this[this.length-1];return t&&!D(t)?t:n(t)},find:function(t){var e,i=this;return e=t?"object"==typeof t?n(t).filter(function(){var t=this;return r.some.call(i,function(e){return n.contains(e,t)})}):1==this.length?n(T.qsa(this[0],t)):this.map(function(){return T.qsa(this,t)}):n()},closest:function(t,e){var i=this[0],r=!1;for("object"==typeof t&&(r=n(t));i&&!(r?r.indexOf(i)>=0:T.matches(i,t));)i=i!==e&&!$(i)&&i.parentNode;return n(i)},parents:function(t){for(var e=[],i=this;i.length>0;)i=n.map(i,function(t){return(t=t.parentNode)&&!$(t)&&e.indexOf(t)<0?(e.push(t),t):void 0});return U(e,t)},parent:function(t){return U(N(this.pluck("parentNode")),t)},children:function(t){return U(this.map(function(){return V(this)}),t)},contents:function(){return this.map(function(){return o.call(this.childNodes)})},siblings:function(t){return U(this.map(function(t,e){return s.call(V(e.parentNode),function(t){return t!==e})}),t)},empty:function(){return this.each(function(){this.innerHTML=""})},pluck:function(t){return n.map(this,function(e){return e[t]})},show:function(){return this.each(function(){"none"==this.style.display&&(this.style.display=""),"none"==getComputedStyle(this,"").getPropertyValue("display")&&(this.style.display=I(this.nodeName))})},replaceWith:function(t){return this.before(t).remove()},wrap:function(t){var e=Z(t);if(this[0]&&!e)var i=n(t).get(0),r=i.parentNode||this.length>1;return this.each(function(o){n(this).wrapAll(e?t.call(this,o):r?i.cloneNode(!0):i)})},wrapAll:function(t){if(this[0]){n(this[0]).before(t=n(t));for(var e;(e=t.children()).length;)t=e.first();n(t).append(this)}return this},wrapInner:function(t){var e=Z(t);return this.each(function(i){var r=n(this),o=r.contents(),s=e?t.call(this,i):t;o.length?o.wrapAll(s):r.append(s)})},unwrap:function(){return this.parent().each(function(){n(this).replaceWith(n(this).children())}),this},clone:function(){return this.map(function(){return this.cloneNode(!0)})},hide:function(){return this.css("display","none")},toggle:function(e){return this.each(function(){var i=n(this);(e===t?"none"==i.css("display"):e)?i.show():i.hide()})},prev:function(t){return n(this.pluck("previousElementSibling")).filter(t||"*")},next:function(t){return n(this.pluck("nextElementSibling")).filter(t||"*")},html:function(t){return 0 in arguments?this.each(function(e){var i=this.innerHTML;n(this).empty().append(J(this,t,e,i))}):0 in this?this[0].innerHTML:null},text:function(t){return 0 in arguments?this.each(function(e){var n=J(this,t,e,this.textContent);this.textContent=null==n?"":""+n}):0 in this?this[0].textContent:null},attr:function(n,i){var r;return"string"!=typeof n||1 in arguments?this.each(function(t){if(1===this.nodeType)if(D(n))for(e in n)X(this,e,n[e]);else X(this,n,J(this,i,t,this.getAttribute(n)))}):this.length&&1===this[0].nodeType?!(r=this[0].getAttribute(n))&&n in this[0]?this[0][n]:r:t},removeAttr:function(t){return this.each(function(){1===this.nodeType&&t.split(" ").forEach(function(t){X(this,t)},this)})},prop:function(t,e){return t=P[t]||t,1 in arguments?this.each(function(n){this[t]=J(this,e,n,this[t])}):this[0]&&this[0][t]},data:function(e,n){var i="data-"+e.replace(m,"-$1").toLowerCase(),r=1 in arguments?this.attr(i,n):this.attr(i);return null!==r?Y(r):t},val:function(t){return 0 in arguments?this.each(function(e){this.value=J(this,t,e,this.value)}):this[0]&&(this[0].multiple?n(this[0]).find("option").filter(function(){return this.selected}).pluck("value"):this[0].value)},offset:function(t){if(t)return this.each(function(e){var i=n(this),r=J(this,t,e,i.offset()),o=i.offsetParent().offset(),s={top:r.top-o.top,left:r.left-o.left};"static"==i.css("position")&&(s.position="relative"),i.css(s)});if(!this.length)return null;var e=this[0].getBoundingClientRect();return{left:e.left+window.pageXOffset,top:e.top+window.pageYOffset,width:Math.round(e.width),height:Math.round(e.height)}},css:function(t,i){if(arguments.length<2){var r,o=this[0];if(!o)return;if(r=getComputedStyle(o,""),"string"==typeof t)return o.style[C(t)]||r.getPropertyValue(t);if(A(t)){var s={};return n.each(t,function(t,e){s[e]=o.style[C(e)]||r.getPropertyValue(e)}),s}}var a="";if("string"==L(t))i||0===i?a=F(t)+":"+H(t,i):this.each(function(){this.style.removeProperty(F(t))});else for(e in t)t[e]||0===t[e]?a+=F(e)+":"+H(e,t[e])+";":this.each(function(){this.style.removeProperty(F(e))});return this.each(function(){this.style.cssText+=";"+a})},index:function(t){return t?this.indexOf(n(t)[0]):this.parent().children().indexOf(this[0])},hasClass:function(t){return t?r.some.call(this,function(t){return this.test(W(t))},q(t)):!1},addClass:function(t){return t?this.each(function(e){if("className"in this){i=[];var r=W(this),o=J(this,t,e,r);o.split(/\s+/g).forEach(function(t){n(this).hasClass(t)||i.push(t)},this),i.length&&W(this,r+(r?" ":"")+i.join(" "))}}):this},removeClass:function(e){return this.each(function(n){if("className"in this){if(e===t)return W(this,"");i=W(this),J(this,e,n,i).split(/\s+/g).forEach(function(t){i=i.replace(q(t)," ")}),W(this,i.trim())}})},toggleClass:function(e,i){return e?this.each(function(r){var o=n(this),s=J(this,e,r,W(this));s.split(/\s+/g).forEach(function(e){(i===t?!o.hasClass(e):i)?o.addClass(e):o.removeClass(e)})}):this},scrollTop:function(e){if(this.length){var n="scrollTop"in this[0];return e===t?n?this[0].scrollTop:this[0].pageYOffset:this.each(n?function(){this.scrollTop=e}:function(){this.scrollTo(this.scrollX,e)})}},scrollLeft:function(e){if(this.length){var n="scrollLeft"in this[0];return e===t?n?this[0].scrollLeft:this[0].pageXOffset:this.each(n?function(){this.scrollLeft=e}:function(){this.scrollTo(e,this.scrollY)})}},position:function(){if(this.length){var t=this[0],e=this.offsetParent(),i=this.offset(),r=d.test(e[0].nodeName)?{top:0,left:0}:e.offset();return i.top-=parseFloat(n(t).css("margin-top"))||0,i.left-=parseFloat(n(t).css("margin-left"))||0,r.top+=parseFloat(n(e[0]).css("border-top-width"))||0,r.left+=parseFloat(n(e[0]).css("border-left-width"))||0,{top:i.top-r.top,left:i.left-r.left}}},offsetParent:function(){return this.map(function(){for(var t=this.offsetParent||a.body;t&&!d.test(t.nodeName)&&"static"==n(t).css("position");)t=t.offsetParent;return t})}},n.fn.detach=n.fn.remove,["width","height"].forEach(function(e){var i=e.replace(/./,function(t){return t[0].toUpperCase()});n.fn[e]=function(r){var o,s=this[0];return r===t?_(s)?s["inner"+i]:$(s)?s.documentElement["scroll"+i]:(o=this.offset())&&o[e]:this.each(function(t){s=n(this),s.css(e,J(this,r,t,s[e]()))})}}),v.forEach(function(t,e){var i=e%2;n.fn[t]=function(){var t,o,r=n.map(arguments,function(e){return t=L(e),"object"==t||"array"==t||null==e?e:T.fragment(e)}),s=this.length>1;return r.length<1?this:this.each(function(t,u){o=i?u:u.parentNode,u=0==e?u.nextSibling:1==e?u.firstChild:2==e?u:null;var f=n.contains(a.documentElement,o);r.forEach(function(t){if(s)t=t.cloneNode(!0);else if(!o)return n(t).remove();o.insertBefore(t,u),f&&G(t,function(t){null==t.nodeName||"SCRIPT"!==t.nodeName.toUpperCase()||t.type&&"text/javascript"!==t.type||t.src||window.eval.call(window,t.innerHTML)})})})},n.fn[i?t+"To":"insert"+(e?"Before":"After")]=function(e){return n(e)[t](this),this}}),T.Z.prototype=n.fn,T.uniq=N,T.deserializeValue=Y,n.zepto=T,n}();window.Zepto=Zepto,void 0===window.$&&(window.$=Zepto),function(t){function l(t){return t._zid||(t._zid=e++)}function h(t,e,n,i){if(e=p(e),e.ns)var r=d(e.ns);return(s[l(t)]||[]).filter(function(t){return!(!t||e.e&&t.e!=e.e||e.ns&&!r.test(t.ns)||n&&l(t.fn)!==l(n)||i&&t.sel!=i)})}function p(t){var e=(""+t).split(".");return{e:e[0],ns:e.slice(1).sort().join(" ")}}function d(t){return new RegExp("(?:^| )"+t.replace(" "," .* ?")+"(?: |$)")}function m(t,e){return t.del&&!u&&t.e in f||!!e}function g(t){return c[t]||u&&f[t]||t}function v(e,i,r,o,a,u,f){var h=l(e),d=s[h]||(s[h]=[]);i.split(/\s/).forEach(function(i){if("ready"==i)return t(document).ready(r);var s=p(i);s.fn=r,s.sel=a,s.e in c&&(r=function(e){var n=e.relatedTarget;return!n||n!==this&&!t.contains(this,n)?s.fn.apply(this,arguments):void 0}),s.del=u;var l=u||r;s.proxy=function(t){if(t=j(t),!t.isImmediatePropagationStopped()){t.data=o;var i=l.apply(e,t._args==n?[t]:[t].concat(t._args));return i===!1&&(t.preventDefault(),t.stopPropagation()),i}},s.i=d.length,d.push(s),"addEventListener"in e&&e.addEventListener(g(s.e),s.proxy,m(s,f))})}function y(t,e,n,i,r){var o=l(t);(e||"").split(/\s/).forEach(function(e){h(t,e,n,i).forEach(function(e){delete s[o][e.i],"removeEventListener"in t&&t.removeEventListener(g(e.e),e.proxy,m(e,r))})})}function j(e,i){return(i||!e.isDefaultPrevented)&&(i||(i=e),t.each(E,function(t,n){var r=i[t];e[t]=function(){return this[n]=x,r&&r.apply(i,arguments)},e[n]=b}),(i.defaultPrevented!==n?i.defaultPrevented:"returnValue"in i?i.returnValue===!1:i.getPreventDefault&&i.getPreventDefault())&&(e.isDefaultPrevented=x)),e}function S(t){var e,i={originalEvent:t};for(e in t)w.test(e)||t[e]===n||(i[e]=t[e]);return j(i,t)}var n,e=1,i=Array.prototype.slice,r=t.isFunction,o=function(t){return"string"==typeof t},s={},a={},u="onfocusin"in window,f={focus:"focusin",blur:"focusout"},c={mouseenter:"mouseover",mouseleave:"mouseout"};a.click=a.mousedown=a.mouseup=a.mousemove="MouseEvents",t.event={add:v,remove:y},t.proxy=function(e,n){var s=2 in arguments&&i.call(arguments,2);if(r(e)){var a=function(){return e.apply(n,s?s.concat(i.call(arguments)):arguments)};return a._zid=l(e),a}if(o(n))return s?(s.unshift(e[n],e),t.proxy.apply(null,s)):t.proxy(e[n],e);throw new TypeError("expected function")},t.fn.bind=function(t,e,n){return this.on(t,e,n)},t.fn.unbind=function(t,e){return this.off(t,e)},t.fn.one=function(t,e,n,i){return this.on(t,e,n,i,1)};var x=function(){return!0},b=function(){return!1},w=/^([A-Z]|returnValue$|layer[XY]$)/,E={preventDefault:"isDefaultPrevented",stopImmediatePropagation:"isImmediatePropagationStopped",stopPropagation:"isPropagationStopped"};t.fn.delegate=function(t,e,n){return this.on(e,t,n)},t.fn.undelegate=function(t,e,n){return this.off(e,t,n)},t.fn.live=function(e,n){return t(document.body).delegate(this.selector,e,n),this},t.fn.die=function(e,n){return t(document.body).undelegate(this.selector,e,n),this},t.fn.on=function(e,s,a,u,f){var c,l,h=this;return e&&!o(e)?(t.each(e,function(t,e){h.on(t,s,a,e,f)}),h):(o(s)||r(u)||u===!1||(u=a,a=s,s=n),(r(a)||a===!1)&&(u=a,a=n),u===!1&&(u=b),h.each(function(n,r){f&&(c=function(t){return y(r,t.type,u),u.apply(this,arguments)}),s&&(l=function(e){var n,o=t(e.target).closest(s,r).get(0);return o&&o!==r?(n=t.extend(S(e),{currentTarget:o,liveFired:r}),(c||u).apply(o,[n].concat(i.call(arguments,1)))):void 0}),v(r,e,u,a,s,l||c)}))},t.fn.off=function(e,i,s){var a=this;return e&&!o(e)?(t.each(e,function(t,e){a.off(t,i,e)}),a):(o(i)||r(s)||s===!1||(s=i,i=n),s===!1&&(s=b),a.each(function(){y(this,e,s,i)}))},t.fn.trigger=function(e,n){return e=o(e)||t.isPlainObject(e)?t.Event(e):j(e),e._args=n,this.each(function(){e.type in f&&"function"==typeof this[e.type]?this[e.type]():"dispatchEvent"in this?this.dispatchEvent(e):t(this).triggerHandler(e,n)})},t.fn.triggerHandler=function(e,n){var i,r;return this.each(function(s,a){i=S(o(e)?t.Event(e):e),i._args=n,i.target=a,t.each(h(a,e.type||e),function(t,e){return r=e.proxy(i),i.isImmediatePropagationStopped()?!1:void 0})}),r},"focusin focusout focus blur load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select keydown keypress keyup error".split(" ").forEach(function(e){t.fn[e]=function(t){return 0 in arguments?this.bind(e,t):this.trigger(e)}}),t.Event=function(t,e){o(t)||(e=t,t=e.type);var n=document.createEvent(a[t]||"Events"),i=!0;if(e)for(var r in e)"bubbles"==r?i=!!e[r]:n[r]=e[r];return n.initEvent(t,i,!0),j(n)}}(Zepto),function(t){function h(e,n,i){var r=t.Event(n);return t(e).trigger(r,i),!r.isDefaultPrevented()}function p(t,e,i,r){return t.global?h(e||n,i,r):void 0}function d(e){e.global&&0===t.active++&&p(e,null,"ajaxStart")}function m(e){e.global&&!--t.active&&p(e,null,"ajaxStop")}function g(t,e){var n=e.context;return e.beforeSend.call(n,t,e)===!1||p(e,n,"ajaxBeforeSend",[t,e])===!1?!1:void p(e,n,"ajaxSend",[t,e])}function v(t,e,n,i){var r=n.context,o="success";n.success.call(r,t,o,e),i&&i.resolveWith(r,[t,o,e]),p(n,r,"ajaxSuccess",[e,n,t]),x(o,e,n)}function y(t,e,n,i,r){var o=i.context;i.error.call(o,n,e,t),r&&r.rejectWith(o,[n,e,t]),p(i,o,"ajaxError",[n,i,t||e]),x(e,n,i)}function x(t,e,n){var i=n.context;n.complete.call(i,e,t),p(n,i,"ajaxComplete",[e,n]),m(n)}function b(){}function w(t){return t&&(t=t.split(";",2)[0]),t&&(t==f?"html":t==u?"json":s.test(t)?"script":a.test(t)&&"xml")||"text"}function E(t,e){return""==e?t:(t+"&"+e).replace(/[&?]{1,2}/,"?")}function j(e){e.processData&&e.data&&"string"!=t.type(e.data)&&(e.data=t.param(e.data,e.traditional)),!e.data||e.type&&"GET"!=e.type.toUpperCase()||(e.url=E(e.url,e.data),e.data=void 0)}function S(e,n,i,r){return t.isFunction(n)&&(r=i,i=n,n=void 0),t.isFunction(i)||(r=i,i=void 0),{url:e,data:n,success:i,dataType:r}}function C(e,n,i,r){var o,s=t.isArray(n),a=t.isPlainObject(n);t.each(n,function(n,u){o=t.type(u),r&&(n=i?r:r+"["+(a||"object"==o||"array"==o?n:"")+"]"),!r&&s?e.add(u.name,u.value):"array"==o||!i&&"object"==o?C(e,u,i,n):e.add(n,u)})}var i,r,e=0,n=window.document,o=/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,s=/^(?:text|application)\/javascript/i,a=/^(?:text|application)\/xml/i,u="application/json",f="text/mars",c=/^\s*$/,l=n.createElement("a");l.href=window.location.href,t.active=0,t.ajaxJSONP=function(i,r){if(!("type"in i))return t.ajax(i);var f,h,o=i.jsonpCallback,s=(t.isFunction(o)?o():o)||"jsonp"+ ++e,a=n.createElement("script"),u=window[s],c=function(e){t(a).triggerHandler("error",e||"abort")},l={abort:c};return r&&r.promise(l),t(a).on("load error",function(e,n){clearTimeout(h),t(a).off().remove(),"error"!=e.type&&f?v(f[0],l,i,r):y(null,n||"error",l,i,r),window[s]=u,f&&t.isFunction(u)&&u(f[0]),u=f=void 0}),g(l,i)===!1?(c("abort"),l):(window[s]=function(){f=arguments},a.src=i.url.replace(/\?(.+)=\?/,"?$1="+s),n.head.appendChild(a),i.timeout>0&&(h=setTimeout(function(){c("timeout")},i.timeout)),l)},t.ajaxSettings={type:"GET",beforeSend:b,success:b,error:b,complete:b,context:null,global:!0,xhr:function(){return new window.XMLHttpRequest},accepts:{script:"text/javascript, application/javascript, application/x-javascript",json:u,xml:"application/xml, text/xml",html:f,text:"text/plain"},crossDomain:!1,timeout:0,processData:!0,cache:!0},t.ajax=function(e){var a,o=t.extend({},e||{}),s=t.Deferred&&t.Deferred();for(i in t.ajaxSettings)void 0===o[i]&&(o[i]=t.ajaxSettings[i]);d(o),o.crossDomain||(a=n.createElement("a"),a.href=o.url,a.href=a.href,o.crossDomain=l.protocol+"//"+l.host!=a.protocol+"//"+a.host),o.url||(o.url=window.location.toString()),j(o);var u=o.dataType,f=/\?.+=\?/.test(o.url);if(f&&(u="jsonp"),o.cache!==!1&&(e&&e.cache===!0||"script"!=u&&"jsonp"!=u)||(o.url=E(o.url,"_="+Date.now())),"jsonp"==u)return f||(o.url=E(o.url,o.jsonp?o.jsonp+"=?":o.jsonp===!1?"":"callback=?")),t.ajaxJSONP(o,s);var C,h=o.accepts[u],p={},m=function(t,e){p[t.toLowerCase()]=[t,e]},x=/^([\w-]+:)\/\//.test(o.url)?RegExp.$1:window.location.protocol,S=o.xhr(),T=S.setRequestHeader;if(s&&s.promise(S),o.crossDomain||m("X-Requested-With","XMLHttpRequest"),m("Accept",h||"*/*"),(h=o.mimeType||h)&&(h.indexOf(",")>-1&&(h=h.split(",",2)[0]),S.overrideMimeType&&S.overrideMimeType(h)),(o.contentType||o.contentType!==!1&&o.data&&"GET"!=o.type.toUpperCase())&&m("Content-Type",o.contentType||"application/x-www-form-urlencoded"),o.headers)for(r in o.headers)m(r,o.headers[r]);if(S.setRequestHeader=m,S.onreadystatechange=function(){if(4==S.readyState){S.onreadystatechange=b,clearTimeout(C);var e,n=!1;if(S.status>=200&&S.status<300||304==S.status||0==S.status&&"file:"==x){u=u||w(o.mimeType||S.getResponseHeader("content-type")),e=S.responseText;try{"script"==u?(1,eval)(e):"xml"==u?e=S.responseXML:"json"==u&&(e=c.test(e)?null:t.parseJSON(e))}catch(i){n=i}n?y(n,"parsererror",S,o,s):v(e,S,o,s)}else y(S.statusText||null,S.status?"error":"abort",S,o,s)}},g(S,o)===!1)return S.abort(),y(null,"abort",S,o,s),S;if(o.xhrFields)for(r in o.xhrFields)S[r]=o.xhrFields[r];var N="async"in o?o.async:!0;S.open(o.type,o.url,N,o.username,o.password);for(r in p)T.apply(S,p[r]);return o.timeout>0&&(C=setTimeout(function(){S.onreadystatechange=b,S.abort(),y(null,"timeout",S,o,s)},o.timeout)),S.send(o.data?o.data:null),S},t.get=function(){return t.ajax(S.apply(null,arguments))},t.post=function(){var e=S.apply(null,arguments);return e.type="POST",t.ajax(e)},t.getJSON=function(){var e=S.apply(null,arguments);return e.dataType="json",t.ajax(e)},t.fn.load=function(e,n,i){if(!this.length)return this;var a,r=this,s=e.split(/\s/),u=S(e,n,i),f=u.success;return s.length>1&&(u.url=s[0],a=s[1]),u.success=function(e){r.html(a?t("<div>").html(e.replace(o,"")).find(a):e),f&&f.apply(r,arguments)},t.ajax(u),this};var T=encodeURIComponent;t.param=function(e,n){var i=[];return i.add=function(e,n){t.isFunction(n)&&(n=n()),null==n&&(n=""),this.push(T(e)+"="+T(n))},C(i,e,n),i.join("&").replace(/%20/g,"+")}}(Zepto),function(t){t.fn.serializeArray=function(){var e,n,i=[],r=function(t){return t.forEach?t.forEach(r):void i.push({name:e,value:t})};return this[0]&&t.each(this[0].elements,function(i,o){n=o.type,e=o.name,e&&"fieldset"!=o.nodeName.toLowerCase()&&!o.disabled&&"submit"!=n&&"reset"!=n&&"button"!=n&&"file"!=n&&("radio"!=n&&"checkbox"!=n||o.checked)&&r(t(o).val())}),i},t.fn.serialize=function(){var t=[];return this.serializeArray().forEach(function(e){t.push(encodeURIComponent(e.name)+"="+encodeURIComponent(e.value))}),t.join("&")},t.fn.submit=function(e){if(0 in arguments)this.bind("submit",e);else if(this.length){var n=t.Event("submit");this.eq(0).trigger(n),n.isDefaultPrevented()||this.get(0).submit()}return this}}(Zepto),function(t){"__proto__"in{}||t.extend(t.zepto,{Z:function(e,n){return e=e||[],t.extend(e,t.fn),e.selector=n||"",e.__Z=!0,e},isZ:function(e){return"array"===t.type(e)&&"__Z"in e}});try{getComputedStyle(void 0)}catch(e){var n=getComputedStyle;window.getComputedStyle=function(t){try{return n(t)}catch(e){return null}}}}(Zepto);

//     Zepto.js
//     (c) 2010-2016 Thomas Fuchs
//     Zepto.js may be freely distributed under the MIT license.

;(function($, undefined){
    var prefix = '', eventPrefix,
        vendors = { Webkit: 'webkit', Moz: '', O: 'o' },
        testEl = document.createElement('div'),
        supportedTransforms = /^((translate|rotate|scale)(X|Y|Z|3d)?|matrix(3d)?|perspective|skew(X|Y)?)$/i,
        transform,
        transitionProperty, transitionDuration, transitionTiming, transitionDelay,
        animationName, animationDuration, animationTiming, animationDelay,
        cssReset = {}

    function dasherize(str) { return str.replace(/([A-Z])/g, '-$1').toLowerCase() }
    function normalizeEvent(name) { return eventPrefix ? eventPrefix + name : name.toLowerCase() }

    if (testEl.style.transform === undefined) $.each(vendors, function(vendor, event){
        if (testEl.style[vendor + 'TransitionProperty'] !== undefined) {
            prefix = '-' + vendor.toLowerCase() + '-'
            eventPrefix = event
            return false
        }
    })

    transform = prefix + 'transform'
    cssReset[transitionProperty = prefix + 'transition-property'] =
        cssReset[transitionDuration = prefix + 'transition-duration'] =
            cssReset[transitionDelay    = prefix + 'transition-delay'] =
                cssReset[transitionTiming   = prefix + 'transition-timing-function'] =
                    cssReset[animationName      = prefix + 'animation-name'] =
                        cssReset[animationDuration  = prefix + 'animation-duration'] =
                            cssReset[animationDelay     = prefix + 'animation-delay'] =
                                cssReset[animationTiming    = prefix + 'animation-timing-function'] = ''

    $.fx = {
        off: (eventPrefix === undefined && testEl.style.transitionProperty === undefined),
        speeds: { _default: 400, fast: 200, slow: 600 },
        cssPrefix: prefix,
        transitionEnd: normalizeEvent('TransitionEnd'),
        animationEnd: normalizeEvent('AnimationEnd')
    }

    $.fn.animate = function(properties, duration, ease, callback, delay){
        if ($.isFunction(duration))
            callback = duration, ease = undefined, duration = undefined
        if ($.isFunction(ease))
            callback = ease, ease = undefined
        if ($.isPlainObject(duration))
            ease = duration.easing, callback = duration.complete, delay = duration.delay, duration = duration.duration
        if (duration) duration = (typeof duration == 'number' ? duration :
                ($.fx.speeds[duration] || $.fx.speeds._default)) / 1000
        if (delay) delay = parseFloat(delay) / 1000
        return this.anim(properties, duration, ease, callback, delay)
    }

    $.fn.anim = function(properties, duration, ease, callback, delay){
        var key, cssValues = {}, cssProperties, transforms = '',
            that = this, wrappedCallback, endEvent = $.fx.transitionEnd,
            fired = false

        if (duration === undefined) duration = $.fx.speeds._default / 1000
        if (delay === undefined) delay = 0
        if ($.fx.off) duration = 0

        if (typeof properties == 'string') {
            // keyframe animation
            cssValues[animationName] = properties
            cssValues[animationDuration] = duration + 's'
            cssValues[animationDelay] = delay + 's'
            cssValues[animationTiming] = (ease || 'linear')
            endEvent = $.fx.animationEnd
        } else {
            cssProperties = []
            // CSS transitions
            for (key in properties)
                if (supportedTransforms.test(key)) transforms += key + '(' + properties[key] + ') '
                else cssValues[key] = properties[key], cssProperties.push(dasherize(key))

            if (transforms) cssValues[transform] = transforms, cssProperties.push(transform)
            if (duration > 0 && typeof properties === 'object') {
                cssValues[transitionProperty] = cssProperties.join(', ')
                cssValues[transitionDuration] = duration + 's'
                cssValues[transitionDelay] = delay + 's'
                cssValues[transitionTiming] = (ease || 'linear')
            }
        }

        wrappedCallback = function(event){
            if (typeof event !== 'undefined') {
                if (event.target !== event.currentTarget) return // makes sure the event didn't bubble from "below"
                $(event.target).unbind(endEvent, wrappedCallback)
            } else
                $(this).unbind(endEvent, wrappedCallback) // triggered by setTimeout

            fired = true
            $(this).css(cssReset)
            callback && callback.call(this)
        }
        if (duration > 0){
            this.bind(endEvent, wrappedCallback)
            // transitionEnd is not always firing on older Android phones
            // so make sure it gets fired
            setTimeout(function(){
                if (fired) return
                wrappedCallback.call(that)
            }, ((duration + delay) * 1000) + 25)
        }

        // trigger page reflow so new elements can animate
        this.size() && this.get(0).clientLeft

        this.css(cssValues)

        if (duration <= 0) setTimeout(function() {
            that.each(function(){ wrappedCallback.call(this) })
        }, 0)

        return this
    }

    testEl = null
})(Zepto);
/* Zepto plugin : slide transition v1.0
 https://github.com/Ilycite/zepto-slide-transition/
 */
(function(a){a.fn.slideDown=function(g){var c=this.css("position");this.show();this.css({position:"absolute",visibility:"hidden"});var e=this.css("margin-top");var h=this.css("margin-bottom");var d=this.css("padding-top");var f=this.css("padding-bottom");var b=this.css("height");this.css({position:c,visibility:"visible",overflow:"hidden",height:0,marginTop:0,marginBottom:0,paddingTop:0,paddingBottom:0});this.animate({height:b,marginTop:e,marginBottom:h,paddingTop:d,paddingBottom:f},g)};a.fn.slideUp=function(h){if(this.height()>0){var g=this;var c=g.css("position");var b=g.css("height");var e=g.css("margin-top");var i=g.css("margin-bottom");var d=g.css("padding-top");var f=g.css("padding-bottom");this.css({visibility:"visible",overflow:"hidden",height:b,marginTop:e,marginBottom:i,paddingTop:d,paddingBottom:f});g.animate({height:0,marginTop:0,marginBottom:0,paddingTop:0,paddingBottom:0},{duration:h,queue:false,complete:function(){g.hide();g.css({visibility:"visible",overflow:"hidden",height:b,marginTop:e,marginBottom:i,paddingTop:d,paddingBottom:f})}})}};a.fn.slideToggle=function(b){if(this.height()==0){this.slideDown()}else{this.slideUp()}}})(Zepto);
!function(){"use strict";function t(e,o){function i(t,e){return function(){return t.apply(e,arguments)}}var r;if(o=o||{},this.trackingClick=!1,this.trackingClickStart=0,this.targetElement=null,this.touchStartX=0,this.touchStartY=0,this.lastTouchIdentifier=0,this.touchBoundary=o.touchBoundary||10,this.layer=e,this.tapDelay=o.tapDelay||200,this.tapTimeout=o.tapTimeout||700,!t.notNeeded(e)){for(var a=["onMouse","onClick","onTouchStart","onTouchMove","onTouchEnd","onTouchCancel"],c=this,s=0,u=a.length;u>s;s++)c[a[s]]=i(c[a[s]],c);n&&(e.addEventListener("mouseover",this.onMouse,!0),e.addEventListener("mousedown",this.onMouse,!0),e.addEventListener("mouseup",this.onMouse,!0)),e.addEventListener("click",this.onClick,!0),e.addEventListener("touchstart",this.onTouchStart,!1),e.addEventListener("touchmove",this.onTouchMove,!1),e.addEventListener("touchend",this.onTouchEnd,!1),e.addEventListener("touchcancel",this.onTouchCancel,!1),Event.prototype.stopImmediatePropagation||(e.removeEventListener=function(t,n,o){var i=Node.prototype.removeEventListener;"click"===t?i.call(e,t,n.hijacked||n,o):i.call(e,t,n,o)},e.addEventListener=function(t,n,o){var i=Node.prototype.addEventListener;"click"===t?i.call(e,t,n.hijacked||(n.hijacked=function(t){t.propagationStopped||n(t)}),o):i.call(e,t,n,o)}),"function"==typeof e.onclick&&(r=e.onclick,e.addEventListener("click",function(t){r(t)},!1),e.onclick=null)}}var e=navigator.userAgent.indexOf("Windows Phone")>=0,n=navigator.userAgent.indexOf("Android")>0&&!e,o=/iP(ad|hone|od)/.test(navigator.userAgent)&&!e,i=o&&/OS 4_\d(_\d)?/.test(navigator.userAgent),r=o&&/OS [6-7]_\d/.test(navigator.userAgent),a=navigator.userAgent.indexOf("BB10")>0;t.prototype.needsClick=function(t){switch(t.nodeName.toLowerCase()){case"button":case"select":case"textarea":if(t.disabled)return!0;break;case"input":if(o&&"file"===t.type||t.disabled)return!0;break;case"label":case"iframe":case"video":return!0}return/\bneedsclick\b/.test(t.className)},t.prototype.needsFocus=function(t){switch(t.nodeName.toLowerCase()){case"textarea":return!0;case"select":return!n;case"input":switch(t.type){case"button":case"checkbox":case"file":case"image":case"radio":case"submit":return!1}return!t.disabled&&!t.readOnly;default:return/\bneedsfocus\b/.test(t.className)}},t.prototype.sendClick=function(t,e){var n,o;document.activeElement&&document.activeElement!==t&&document.activeElement.blur(),o=e.changedTouches[0],n=document.createEvent("MouseEvents"),n.initMouseEvent(this.determineEventType(t),!0,!0,window,1,o.screenX,o.screenY,o.clientX,o.clientY,!1,!1,!1,!1,0,null),n.forwardedTouchEvent=!0,t.dispatchEvent(n)},t.prototype.determineEventType=function(t){return n&&"select"===t.tagName.toLowerCase()?"mousedown":"click"},t.prototype.focus=function(t){var e;o&&t.setSelectionRange&&0!==t.type.indexOf("date")&&"time"!==t.type&&"month"!==t.type?(e=t.value.length,t.setSelectionRange(e,e)):t.focus()},t.prototype.updateScrollParent=function(t){var e,n;if(e=t.fastClickScrollParent,!e||!e.contains(t)){n=t;do{if(n.scrollHeight>n.offsetHeight){e=n,t.fastClickScrollParent=n;break}n=n.parentElement}while(n)}e&&(e.fastClickLastScrollTop=e.scrollTop)},t.prototype.getTargetElementFromEventTarget=function(t){return t.nodeType===Node.TEXT_NODE?t.parentNode:t},t.prototype.onTouchStart=function(t){var e,n,r;if(t.targetTouches.length>1)return!0;if(e=this.getTargetElementFromEventTarget(t.target),n=t.targetTouches[0],o){if(r=window.getSelection(),r.rangeCount&&!r.isCollapsed)return!0;if(!i){if(n.identifier&&n.identifier===this.lastTouchIdentifier)return t.preventDefault(),!1;this.lastTouchIdentifier=n.identifier,this.updateScrollParent(e)}}return this.trackingClick=!0,this.trackingClickStart=t.timeStamp,this.targetElement=e,this.touchStartX=n.pageX,this.touchStartY=n.pageY,t.timeStamp-this.lastClickTime<this.tapDelay&&t.preventDefault(),!0},t.prototype.touchHasMoved=function(t){var e=t.changedTouches[0],n=this.touchBoundary;return Math.abs(e.pageX-this.touchStartX)>n||Math.abs(e.pageY-this.touchStartY)>n?!0:!1},t.prototype.onTouchMove=function(t){return this.trackingClick?((this.targetElement!==this.getTargetElementFromEventTarget(t.target)||this.touchHasMoved(t))&&(this.trackingClick=!1,this.targetElement=null),!0):!0},t.prototype.findControl=function(t){return void 0!==t.control?t.control:t.htmlFor?document.getElementById(t.htmlFor):t.querySelector("button, input:not([type=hidden]), keygen, meter, output, progress, select, textarea")},t.prototype.onTouchEnd=function(t){var e,a,c,s,u,l=this.targetElement;if(!this.trackingClick)return!0;if(t.timeStamp-this.lastClickTime<this.tapDelay)return this.cancelNextClick=!0,!0;if(t.timeStamp-this.trackingClickStart>this.tapTimeout)return!0;if(this.cancelNextClick=!1,this.lastClickTime=t.timeStamp,a=this.trackingClickStart,this.trackingClick=!1,this.trackingClickStart=0,r&&(u=t.changedTouches[0],l=document.elementFromPoint(u.pageX-window.pageXOffset,u.pageY-window.pageYOffset)||l,l.fastClickScrollParent=this.targetElement.fastClickScrollParent),c=l.tagName.toLowerCase(),"label"===c){if(e=this.findControl(l)){if(this.focus(l),n)return!1;l=e}}else if(this.needsFocus(l))return t.timeStamp-a>100||o&&window.top!==window&&"input"===c?(this.targetElement=null,!1):(this.focus(l),this.sendClick(l,t),o&&"select"===c||(this.targetElement=null,t.preventDefault()),!1);return o&&!i&&(s=l.fastClickScrollParent,s&&s.fastClickLastScrollTop!==s.scrollTop)?!0:(this.needsClick(l)||(t.preventDefault(),this.sendClick(l,t)),!1)},t.prototype.onTouchCancel=function(){this.trackingClick=!1,this.targetElement=null},t.prototype.onMouse=function(t){return this.targetElement?t.forwardedTouchEvent?!0:t.cancelable&&(!this.needsClick(this.targetElement)||this.cancelNextClick)?(t.stopImmediatePropagation?t.stopImmediatePropagation():t.propagationStopped=!0,t.stopPropagation(),t.preventDefault(),!1):!0:!0},t.prototype.onClick=function(t){var e;return this.trackingClick?(this.targetElement=null,this.trackingClick=!1,!0):"submit"===t.target.type&&0===t.detail?!0:(e=this.onMouse(t),e||(this.targetElement=null),e)},t.prototype.destroy=function(){var t=this.layer;n&&(t.removeEventListener("mouseover",this.onMouse,!0),t.removeEventListener("mousedown",this.onMouse,!0),t.removeEventListener("mouseup",this.onMouse,!0)),t.removeEventListener("click",this.onClick,!0),t.removeEventListener("touchstart",this.onTouchStart,!1),t.removeEventListener("touchmove",this.onTouchMove,!1),t.removeEventListener("touchend",this.onTouchEnd,!1),t.removeEventListener("touchcancel",this.onTouchCancel,!1)},t.notNeeded=function(t){var e,o,i,r;if("undefined"==typeof window.ontouchstart)return!0;if(o=+(/Chrome\/([0-9]+)/.exec(navigator.userAgent)||[,0])[1]){if(!n)return!0;if(e=document.querySelector("meta[name=viewport]")){if(-1!==e.content.indexOf("user-scalable=no"))return!0;if(o>31&&document.documentElement.scrollWidth<=window.outerWidth)return!0}}if(a&&(i=navigator.userAgent.match(/Version\/([0-9]*)\.([0-9]*)/),i[1]>=10&&i[2]>=3&&(e=document.querySelector("meta[name=viewport]")))){if(-1!==e.content.indexOf("user-scalable=no"))return!0;if(document.documentElement.scrollWidth<=window.outerWidth)return!0}return"none"===t.style.msTouchAction||"manipulation"===t.style.touchAction?!0:(r=+(/Firefox\/([0-9]+)/.exec(navigator.userAgent)||[,0])[1],r>=27&&(e=document.querySelector("meta[name=viewport]"),e&&(-1!==e.content.indexOf("user-scalable=no")||document.documentElement.scrollWidth<=window.outerWidth))?!0:"none"===t.style.touchAction||"manipulation"===t.style.touchAction?!0:!1)},t.attach=function(e,n){return new t(e,n)},"function"==typeof define&&"object"==typeof define.amd&&define.amd?define(function(){return t}):"undefined"!=typeof module&&module.exports?(module.exports=t.attach,module.exports.FastClick=t):window.FastClick=t}();
/*!
 * artTemplate - Template Engine
 * https://github.com/aui/artTemplate
 * Released under the MIT, BSD, and GPL Licenses
 */
 

/**
 * 模板引擎路由函数
 * 若第二个参数类型为 Object 则执行 render 方法, 否则 compile 方法
 * @name    template
 * @param   {String}            模板ID (可选)
 * @param   {Object, String}    数据或者模板字符串
 * @return  {String, Function}  渲染好的HTML字符串或者渲染方法
 */
var template = function (id, content) {
    return template[
        typeof content === 'object' ? 'render' : 'compile'
    ].apply(template, arguments);
};




(function (exports, global) {


'use strict';
exports.version = '2.0.1'; 
exports.openTag = '<%';     // 设置逻辑语法开始标签
exports.closeTag = '%>';    // 设置逻辑语法结束标签
exports.isEscape = true;    // HTML字符编码输出开关
exports.isCompress = false; // 剔除渲染后HTML多余的空白开关
exports.parser = null;      // 自定义语法插件接口



/**
 * 渲染模板
 * @name    template.render
 * @param   {String}    模板ID
 * @param   {Object}    数据
 * @return  {String}    渲染好的HTML字符串
 */
exports.render = function (id, data) {

    var cache = _getCache(id);
    
    if (cache === undefined) {

        return _debug({
            id: id,
            name: 'Render Error',
            message: 'No Template'
        });
        
    }
    
    return cache(data); 
};



/**
 * 编译模板
 * 2012-6-6:
 * define 方法名改为 compile,
 * 与 Node Express 保持一致,
 * 感谢 TooBug 提供帮助!
 * @name    template.compile
 * @param   {String}    模板ID (可选)
 * @param   {String}    模板字符串
 * @return  {Function}  渲染方法
 */
exports.compile = function (id, source) {
    
    var params = arguments;
    var isDebug = params[2];
    var anonymous = 'anonymous';
    
    if (typeof source !== 'string') {
        isDebug = params[1];
        source = params[0];
        id = anonymous;
    }

    
    try {
        
        var Render = _compile(source, isDebug);
        
    } catch (e) {
    
        e.id = id || source;
        e.name = 'Syntax Error';

        return _debug(e);
        
    }
    
    
    function render (data) {
        
        try {
            
            return new Render(data) + '';
            
        } catch (e) {
            
            if (!isDebug) {
                return exports.compile(id, source, true)(data);
            }

            e.id = id || source;
            e.name = 'Render Error';
            e.source = source;
            
            return _debug(e);
            
        }
        
    }
    

    render.prototype = Render.prototype;
    render.toString = function () {
        return Render.toString();
    };
    
    
    if (id !== anonymous) {
        _cache[id] = render;
    }

    
    return render;

};




/**
 * 添加模板辅助方法
 * @name    template.helper
 * @param   {String}    名称
 * @param   {Function}  方法
 */
exports.helper = function (name, helper) {
    exports.prototype[name] = helper;
};




/**
 * 模板错误事件
 * @name    template.onerror
 * @event
 */
exports.onerror = function (e) {
    var content = '[template]:\n'
        + e.id
        + '\n\n[name]:\n'
        + e.name;

    if (e.message) {
        content += '\n\n[message]:\n'
        + e.message;
    }
    
    if (e.line) {
        content += '\n\n[line]:\n'
        + e.line;
        content += '\n\n[source]:\n'
        + e.source.split(/\n/)[e.line - 1].replace(/^[\s\t]+/, '');
    }
    
    if (e.temp) {
        content += '\n\n[temp]:\n'
        + e.temp;
    }
    
    if (global.console) {
        console.error(content);
    }
};



// 编译好的函数缓存
var _cache = {};



// 获取模板缓存
var _getCache = function (id) {

    var cache = _cache[id];
    
    if (cache === undefined && 'document' in global) {
        var elem = document.getElementById(id);
        
        if (elem) {
            var source = elem.value || elem.innerHTML;
            return exports.compile(id, source.replace(/^\s*|\s*$/g, ''));
        }
        
    } else if (_cache.hasOwnProperty(id)) {
    
        return cache;
    }
};



// 模板调试器
var _debug = function (e) {

    exports.onerror(e);
    
    function error () {
        return error + '';
    }
    
    error.toString = function () {
        return '{Template Error}';
    };
    
    return error;
};



// 模板编译器
var _compile = (function () {


    // 辅助方法集合
    exports.prototype = {
        $render: exports.render,
        $escape: function (content) {

            return typeof content === 'string'
            ? content.replace(/&(?![\w#]+;)|[<>"']/g, function (s) {
                return {
                    "<": "&#60;",
                    ">": "&#62;",
                    '"': "&#34;",
                    "'": "&#39;",
                    "&": "&#38;"
                }[s];
            })
            : content;
        },
        $string: function (value) {

            if (typeof value === 'string' || typeof value === 'number') {

                return value;

            } else if (typeof value === 'function') {

                return value();

            } else {

                return '';

            }

        }
    };


    var arrayforEach = Array.prototype.forEach || function (block, thisObject) {
        var len = this.length >>> 0;
        
        for (var i = 0; i < len; i++) {
            if (i in this) {
                block.call(thisObject, this[i], i, this);
            }
        }
        
    };


    // 数组迭代
    var forEach = function (array, callback) {
        arrayforEach.call(array, callback);
    };


    // 静态分析模板变量
    var KEYWORDS =
        // 关键字
        'break,case,catch,continue,debugger,default,delete,do,else,false'
        + ',finally,for,function,if,in,instanceof,new,null,return,switch,this'
        + ',throw,true,try,typeof,var,void,while,with'
        
        // 保留字
        + ',abstract,boolean,byte,char,class,const,double,enum,export,extends'
        + ',final,float,goto,implements,import,int,interface,long,native'
        + ',package,private,protected,mars,short,static,super,synchronized'
        + ',throws,transient,volatile'
        
        // ECMA 5 - use strict
        + ',arguments,let,yield'

        + ',undefined';
    var REMOVE_RE = /\/\*(?:.|\n)*?\*\/|\/\/[^\n]*\n|\/\/[^\n]*$|'[^']*'|"[^"]*"|[\s\t\n]*\.[\s\t\n]*[$\w\.]+/g;
    var SPLIT_RE = /[^\w$]+/g;
    var KEYWORDS_RE = new RegExp(["\\b" + KEYWORDS.replace(/,/g, '\\b|\\b') + "\\b"].join('|'), 'g');
    var NUMBER_RE = /\b\d[^,]*/g;
    var BOUNDARY_RE = /^,+|,+$/g;
    var getVariable = function (code) {

        code = code
        .replace(REMOVE_RE, '')
        .replace(SPLIT_RE, ',')
        .replace(KEYWORDS_RE, '')
        .replace(NUMBER_RE, '')
        .replace(BOUNDARY_RE, '');

        code = code ? code.split(/,+/) : [];

        return code;
    };


    return function (source, isDebug) {
        
        var openTag = exports.openTag;
        var closeTag = exports.closeTag;
        var parser = exports.parser;

        
        var code = source;
        var tempCode = '';
        var line = 1;
        var uniq = {$data:true,$helpers:true,$out:true,$line:true};
        var helpers = exports.prototype;
        var prototype = {};

        
        var variables = "var $helpers=this,"
        + (isDebug ? "$line=0," : "");

        var isNewEngine = ''.trim;// '__proto__' in {}
        var replaces = isNewEngine
        ? ["$out='';", "$out+=", ";", "$out"]
        : ["$out=[];", "$out.push(", ");", "$out.join('')"];

        var concat = isNewEngine
            ? "if(content!==undefined){$out+=content;return content}"
            : "$out.push(content);";
              
        var print = "function(content){" + concat + "}";

        var include = "function(id,data){"
        +     "if(data===undefined){data=$data}"
        +     "var content=$helpers.$render(id,data);"
        +     concat
        + "}";
        
        
        // html与逻辑语法分离
        forEach(code.split(openTag), function (code, i) {
            code = code.split(closeTag);
            
            var $0 = code[0];
            var $1 = code[1];
            
            // code: [mars]
            if (code.length === 1) {
                
                tempCode += html($0);
             
            // code: [logic, mars]
            } else {
                
                tempCode += logic($0);
                
                if ($1) {
                    tempCode += html($1);
                }
            }
            

        });
        
        
        
        code = tempCode;
        
        
        // 调试语句
        if (isDebug) {
            code = 'try{' + code + '}catch(e){'
            +       'e.line=$line;'
            +       'throw e'
            + '}';
        }
        
        
        code = "'use strict';"
        + variables + replaces[0] + code
        + 'return new String(' + replaces[3] + ')';
        
        
        try {
            
            var Render = new Function('$data', code);
            Render.prototype = prototype;

            return Render;
            
        } catch (e) {
            e.temp = 'function anonymous($data) {' + code + '}';
            throw e;
        }



        
        // 处理 HTML 语句
        function html (code) {
            
            // 记录行号
            line += code.split(/\n/).length - 1;

            if (exports.isCompress) {
                code = code.replace(/[\n\r\t\s]+/g, ' ');
            }
            
            code = code
            // 单引号与反斜杠转义(因为编译后的函数默认使用单引号，因此双引号无需转义)
            .replace(/('|\\)/g, '\\$1')
            // 换行符转义(windows + linux)
            .replace(/\r/g, '\\r')
            .replace(/\n/g, '\\n');
            
            code = replaces[1] + "'" + code + "'" + replaces[2];
            
            return code + '\n';
        }
        
        
        // 处理逻辑语句
        function logic (code) {

            var thisLine = line;
           
            if (parser) {
            
                 // 语法转换插件钩子
                code = parser(code);
                
            } else if (isDebug) {
            
                // 记录行号
                code = code.replace(/\n/g, function () {
                    line ++;
                    return '$line=' + line +  ';';
                });
                
            }
            
            
            // 输出语句. 转义: <%=value%> 不转义:<%==value%>
            if (code.indexOf('=') === 0) {

                var isEscape = code.indexOf('==') !== 0;

                code = code.replace(/^=*|[\s;]*$/g, '');

                if (isEscape && exports.isEscape) {

                    // 转义处理，但排除辅助方法
                    var name = code.replace(/\s*\([^\)]+\)/, '');
                    if (
                        !helpers.hasOwnProperty(name)
                        && !/^(include|print)$/.test(name)
                    ) {
                        code = '$escape($string(' + code + '))';
                    }

                } else {
                    code = '$string(' + code + ')';
                }
                

                code = replaces[1] + code + replaces[2];

            }
            
            if (isDebug) {
                code = '$line=' + thisLine + ';' + code;
            }
            
            getKey(code);
            
            return code + '\n';
        }
        
        
        // 提取模板中的变量名
        function getKey (code) {
            
            code = getVariable(code);
            
            // 分词
            forEach(code, function (name) {
             
                // 除重
                if (!uniq.hasOwnProperty(name)) {
                    setValue(name);
                    uniq[name] = true;
                }
                
            });
            
        }
        
        
        // 声明模板变量
        // 赋值优先级:
        // 内置特权方法(include, print) > 私有模板辅助方法 > 数据 > 公用模板辅助方法
        function setValue (name) {

            var value;

            if (name === 'print') {

                value = print;

            } else if (name === 'include') {
                
                prototype['$render'] = helpers['$render'];
                value = include;
                
            } else {

                value = '$data.' + name;

                if (helpers.hasOwnProperty(name)) {

                    prototype[name] = helpers[name];

                    if (name.indexOf('$') === 0) {
                        value = '$helpers.' + name;
                    } else {
                        value = value
                        + '===undefined?$helpers.' + name + ':' + value;
                    }
                }
                
                
            }
            
            variables += name + '=' + value + ',';
        }
        
        
    };
})();




})(template, this);


// RequireJS || SeaJS
if (typeof define === 'function') {
    define(function(require, exports, module) {
        module.exports = template; 
    });
// NodeJS
} else if (typeof exports !== 'undefined') {
    module.exports = template;
}

/**
 * [js-md5]{@link https://github.com/emn178/js-md5}
 *
 * @namespace md5
 * @version 0.4.2
 * @author Chen, Yi-Cyuan [emn178@gmail.com]
 * @copyright Chen, Yi-Cyuan 2014-2017
 * @license MIT
 */
!function(){"use strict";function t(t){if(t)c[0]=c[16]=c[1]=c[2]=c[3]=c[4]=c[5]=c[6]=c[7]=c[8]=c[9]=c[10]=c[11]=c[12]=c[13]=c[14]=c[15]=0,this.blocks=c,this.buffer8=i;else if(n){var r=new ArrayBuffer(68);this.buffer8=new Uint8Array(r),this.blocks=new Uint32Array(r)}else this.blocks=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];this.h0=this.h1=this.h2=this.h3=this.start=this.bytes=0,this.finalized=this.hashed=!1,this.first=!0}var r="object"==typeof window?window:{},e=!r.JS_MD5_NO_NODE_JS&&"object"==typeof process&&process.versions&&process.versions.node;e&&(r=global);var i,h=!r.JS_MD5_NO_COMMON_JS&&"object"==typeof module&&module.exports,s="function"==typeof define&&define.amd,n=!r.JS_MD5_NO_ARRAY_BUFFER&&"undefined"!=typeof ArrayBuffer,f="0123456789abcdef".split(""),o=[128,32768,8388608,-2147483648],a=[0,8,16,24],u=["hex","array","digest","buffer","arrayBuffer"],c=[];if(n){var p=new ArrayBuffer(68);i=new Uint8Array(p),c=new Uint32Array(p)}var d=function(r){return function(e){return new t(!0).update(e)[r]()}},y=function(){var r=d("hex");e&&(r=l(r)),r.create=function(){return new t},r.update=function(t){return r.create().update(t)};for(var i=0;i<u.length;++i){var h=u[i];r[h]=d(h)}return r},l=function(t){var r=require("crypto"),e=require("buffer").Buffer,i=function(i){if("string"==typeof i)return r.createHash("md5").update(i,"utf8").digest("hex");if(i.constructor===ArrayBuffer)i=new Uint8Array(i);else if(void 0===i.length)return t(i);return r.createHash("md5").update(new e(i)).digest("hex")};return i};t.prototype.update=function(t){if(!this.finalized){var e="string"!=typeof t;e&&t.constructor==r.ArrayBuffer&&(t=new Uint8Array(t));for(var i,h,s=0,f=t.length||0,o=this.blocks,u=this.buffer8;f>s;){if(this.hashed&&(this.hashed=!1,o[0]=o[16],o[16]=o[1]=o[2]=o[3]=o[4]=o[5]=o[6]=o[7]=o[8]=o[9]=o[10]=o[11]=o[12]=o[13]=o[14]=o[15]=0),e)if(n)for(h=this.start;f>s&&64>h;++s)u[h++]=t[s];else for(h=this.start;f>s&&64>h;++s)o[h>>2]|=t[s]<<a[3&h++];else if(n)for(h=this.start;f>s&&64>h;++s)i=t.charCodeAt(s),128>i?u[h++]=i:2048>i?(u[h++]=192|i>>6,u[h++]=128|63&i):55296>i||i>=57344?(u[h++]=224|i>>12,u[h++]=128|i>>6&63,u[h++]=128|63&i):(i=65536+((1023&i)<<10|1023&t.charCodeAt(++s)),u[h++]=240|i>>18,u[h++]=128|i>>12&63,u[h++]=128|i>>6&63,u[h++]=128|63&i);else for(h=this.start;f>s&&64>h;++s)i=t.charCodeAt(s),128>i?o[h>>2]|=i<<a[3&h++]:2048>i?(o[h>>2]|=(192|i>>6)<<a[3&h++],o[h>>2]|=(128|63&i)<<a[3&h++]):55296>i||i>=57344?(o[h>>2]|=(224|i>>12)<<a[3&h++],o[h>>2]|=(128|i>>6&63)<<a[3&h++],o[h>>2]|=(128|63&i)<<a[3&h++]):(i=65536+((1023&i)<<10|1023&t.charCodeAt(++s)),o[h>>2]|=(240|i>>18)<<a[3&h++],o[h>>2]|=(128|i>>12&63)<<a[3&h++],o[h>>2]|=(128|i>>6&63)<<a[3&h++],o[h>>2]|=(128|63&i)<<a[3&h++]);this.lastByteIndex=h,this.bytes+=h-this.start,h>=64?(this.start=h-64,this.hash(),this.hashed=!0):this.start=h}return this}},t.prototype.finalize=function(){if(!this.finalized){this.finalized=!0;var t=this.blocks,r=this.lastByteIndex;t[r>>2]|=o[3&r],r>=56&&(this.hashed||this.hash(),t[0]=t[16],t[16]=t[1]=t[2]=t[3]=t[4]=t[5]=t[6]=t[7]=t[8]=t[9]=t[10]=t[11]=t[12]=t[13]=t[14]=t[15]=0),t[14]=this.bytes<<3,this.hash()}},t.prototype.hash=function(){var t,r,e,i,h,s,n=this.blocks;this.first?(t=n[0]-680876937,t=(t<<7|t>>>25)-271733879<<0,i=(-1732584194^2004318071&t)+n[1]-117830708,i=(i<<12|i>>>20)+t<<0,e=(-271733879^i&(-271733879^t))+n[2]-1126478375,e=(e<<17|e>>>15)+i<<0,r=(t^e&(i^t))+n[3]-1316259209,r=(r<<22|r>>>10)+e<<0):(t=this.h0,r=this.h1,e=this.h2,i=this.h3,t+=(i^r&(e^i))+n[0]-680876936,t=(t<<7|t>>>25)+r<<0,i+=(e^t&(r^e))+n[1]-389564586,i=(i<<12|i>>>20)+t<<0,e+=(r^i&(t^r))+n[2]+606105819,e=(e<<17|e>>>15)+i<<0,r+=(t^e&(i^t))+n[3]-1044525330,r=(r<<22|r>>>10)+e<<0),t+=(i^r&(e^i))+n[4]-176418897,t=(t<<7|t>>>25)+r<<0,i+=(e^t&(r^e))+n[5]+1200080426,i=(i<<12|i>>>20)+t<<0,e+=(r^i&(t^r))+n[6]-1473231341,e=(e<<17|e>>>15)+i<<0,r+=(t^e&(i^t))+n[7]-45705983,r=(r<<22|r>>>10)+e<<0,t+=(i^r&(e^i))+n[8]+1770035416,t=(t<<7|t>>>25)+r<<0,i+=(e^t&(r^e))+n[9]-1958414417,i=(i<<12|i>>>20)+t<<0,e+=(r^i&(t^r))+n[10]-42063,e=(e<<17|e>>>15)+i<<0,r+=(t^e&(i^t))+n[11]-1990404162,r=(r<<22|r>>>10)+e<<0,t+=(i^r&(e^i))+n[12]+1804603682,t=(t<<7|t>>>25)+r<<0,i+=(e^t&(r^e))+n[13]-40341101,i=(i<<12|i>>>20)+t<<0,e+=(r^i&(t^r))+n[14]-1502002290,e=(e<<17|e>>>15)+i<<0,r+=(t^e&(i^t))+n[15]+1236535329,r=(r<<22|r>>>10)+e<<0,t+=(e^i&(r^e))+n[1]-165796510,t=(t<<5|t>>>27)+r<<0,i+=(r^e&(t^r))+n[6]-1069501632,i=(i<<9|i>>>23)+t<<0,e+=(t^r&(i^t))+n[11]+643717713,e=(e<<14|e>>>18)+i<<0,r+=(i^t&(e^i))+n[0]-373897302,r=(r<<20|r>>>12)+e<<0,t+=(e^i&(r^e))+n[5]-701558691,t=(t<<5|t>>>27)+r<<0,i+=(r^e&(t^r))+n[10]+38016083,i=(i<<9|i>>>23)+t<<0,e+=(t^r&(i^t))+n[15]-660478335,e=(e<<14|e>>>18)+i<<0,r+=(i^t&(e^i))+n[4]-405537848,r=(r<<20|r>>>12)+e<<0,t+=(e^i&(r^e))+n[9]+568446438,t=(t<<5|t>>>27)+r<<0,i+=(r^e&(t^r))+n[14]-1019803690,i=(i<<9|i>>>23)+t<<0,e+=(t^r&(i^t))+n[3]-187363961,e=(e<<14|e>>>18)+i<<0,r+=(i^t&(e^i))+n[8]+1163531501,r=(r<<20|r>>>12)+e<<0,t+=(e^i&(r^e))+n[13]-1444681467,t=(t<<5|t>>>27)+r<<0,i+=(r^e&(t^r))+n[2]-51403784,i=(i<<9|i>>>23)+t<<0,e+=(t^r&(i^t))+n[7]+1735328473,e=(e<<14|e>>>18)+i<<0,r+=(i^t&(e^i))+n[12]-1926607734,r=(r<<20|r>>>12)+e<<0,h=r^e,t+=(h^i)+n[5]-378558,t=(t<<4|t>>>28)+r<<0,i+=(h^t)+n[8]-2022574463,i=(i<<11|i>>>21)+t<<0,s=i^t,e+=(s^r)+n[11]+1839030562,e=(e<<16|e>>>16)+i<<0,r+=(s^e)+n[14]-35309556,r=(r<<23|r>>>9)+e<<0,h=r^e,t+=(h^i)+n[1]-1530992060,t=(t<<4|t>>>28)+r<<0,i+=(h^t)+n[4]+1272893353,i=(i<<11|i>>>21)+t<<0,s=i^t,e+=(s^r)+n[7]-155497632,e=(e<<16|e>>>16)+i<<0,r+=(s^e)+n[10]-1094730640,r=(r<<23|r>>>9)+e<<0,h=r^e,t+=(h^i)+n[13]+681279174,t=(t<<4|t>>>28)+r<<0,i+=(h^t)+n[0]-358537222,i=(i<<11|i>>>21)+t<<0,s=i^t,e+=(s^r)+n[3]-722521979,e=(e<<16|e>>>16)+i<<0,r+=(s^e)+n[6]+76029189,r=(r<<23|r>>>9)+e<<0,h=r^e,t+=(h^i)+n[9]-640364487,t=(t<<4|t>>>28)+r<<0,i+=(h^t)+n[12]-421815835,i=(i<<11|i>>>21)+t<<0,s=i^t,e+=(s^r)+n[15]+530742520,e=(e<<16|e>>>16)+i<<0,r+=(s^e)+n[2]-995338651,r=(r<<23|r>>>9)+e<<0,t+=(e^(r|~i))+n[0]-198630844,t=(t<<6|t>>>26)+r<<0,i+=(r^(t|~e))+n[7]+1126891415,i=(i<<10|i>>>22)+t<<0,e+=(t^(i|~r))+n[14]-1416354905,e=(e<<15|e>>>17)+i<<0,r+=(i^(e|~t))+n[5]-57434055,r=(r<<21|r>>>11)+e<<0,t+=(e^(r|~i))+n[12]+1700485571,t=(t<<6|t>>>26)+r<<0,i+=(r^(t|~e))+n[3]-1894986606,i=(i<<10|i>>>22)+t<<0,e+=(t^(i|~r))+n[10]-1051523,e=(e<<15|e>>>17)+i<<0,r+=(i^(e|~t))+n[1]-2054922799,r=(r<<21|r>>>11)+e<<0,t+=(e^(r|~i))+n[8]+1873313359,t=(t<<6|t>>>26)+r<<0,i+=(r^(t|~e))+n[15]-30611744,i=(i<<10|i>>>22)+t<<0,e+=(t^(i|~r))+n[6]-1560198380,e=(e<<15|e>>>17)+i<<0,r+=(i^(e|~t))+n[13]+1309151649,r=(r<<21|r>>>11)+e<<0,t+=(e^(r|~i))+n[4]-145523070,t=(t<<6|t>>>26)+r<<0,i+=(r^(t|~e))+n[11]-1120210379,i=(i<<10|i>>>22)+t<<0,e+=(t^(i|~r))+n[2]+718787259,e=(e<<15|e>>>17)+i<<0,r+=(i^(e|~t))+n[9]-343485551,r=(r<<21|r>>>11)+e<<0,this.first?(this.h0=t+1732584193<<0,this.h1=r-271733879<<0,this.h2=e-1732584194<<0,this.h3=i+271733878<<0,this.first=!1):(this.h0=this.h0+t<<0,this.h1=this.h1+r<<0,this.h2=this.h2+e<<0,this.h3=this.h3+i<<0)},t.prototype.hex=function(){this.finalize();var t=this.h0,r=this.h1,e=this.h2,i=this.h3;return f[t>>4&15]+f[15&t]+f[t>>12&15]+f[t>>8&15]+f[t>>20&15]+f[t>>16&15]+f[t>>28&15]+f[t>>24&15]+f[r>>4&15]+f[15&r]+f[r>>12&15]+f[r>>8&15]+f[r>>20&15]+f[r>>16&15]+f[r>>28&15]+f[r>>24&15]+f[e>>4&15]+f[15&e]+f[e>>12&15]+f[e>>8&15]+f[e>>20&15]+f[e>>16&15]+f[e>>28&15]+f[e>>24&15]+f[i>>4&15]+f[15&i]+f[i>>12&15]+f[i>>8&15]+f[i>>20&15]+f[i>>16&15]+f[i>>28&15]+f[i>>24&15]},t.prototype.toString=t.prototype.hex,t.prototype.digest=function(){this.finalize();var t=this.h0,r=this.h1,e=this.h2,i=this.h3;return[255&t,t>>8&255,t>>16&255,t>>24&255,255&r,r>>8&255,r>>16&255,r>>24&255,255&e,e>>8&255,e>>16&255,e>>24&255,255&i,i>>8&255,i>>16&255,i>>24&255]},t.prototype.array=t.prototype.digest,t.prototype.arrayBuffer=function(){this.finalize();var t=new ArrayBuffer(16),r=new Uint32Array(t);return r[0]=this.h0,r[1]=this.h1,r[2]=this.h2,r[3]=this.h3,t},t.prototype.buffer=t.prototype.arrayBuffer;var b=y();h?module.exports=b:(r.md5=b,s&&define(function(){return b}))}();

/**
 * 配置信息
 * 把配置信息分为基础配置、提示信息
 */
(function() {
    var config = {
        HOST_API: location.protocol + '//' + location.host + '/index.php/api',
        alipayAuthUrl: "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=APPID&scope=auth_user&redirect_uri=REDIRECT_URI", //授权跳转地址
        wechatAuthUrl: "https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE&connect_redirect=1#wechat_redirect", //微信授权跳转地址
        alipayAppId: "2017032806445211", //2017052207307682 2017032806445211
        wechatAppId: "wx3450e6a290582989", //
        platformKey: "ce6u8wD8Wf7fWufRp",
        page:1,
        pageSize:10,
        ratioOfMainPic: 3 / 2, //商品图片的宽高比
        ratioOfAdPic: 2 / 1, //广告图片的宽高比
        smsDuration: 60 //发短信计时间隔，秒
    };
    config.DEF_IMG_URL = location.protocol + '//' + location.host + "/public/img/shop.jpg"
    //接口配置
    config.API_GLOBAL_AD = config.HOST_API + '/global/get';

    //auto login
    config.API_ALIPAY_ACCOUNT_AUTOLOGIN = config.HOST_API + '/account/auth_alipay_login';
    config.API_WECHAT_ACCOUNT_AUTOLOGIN = config.HOST_API + '/dalang/auth_wechat_login';
    config.API_GET_CONFIG = config.HOST_API + '/dalang/get_config_by_device_id';


    //bind
    config.API_SIGN = config.HOST_API + '/dalang/goto_sign';

    //orders
    config.API_ORDERS = config.HOST_API + '/dalang/list_order';
    config.API_ORDER_DETAIL = config.HOST_API + '/dalang/get_detail';
    config.API_ORDER_REFUND = config.HOST_API + '/dalang/refund';
    config.API_ORDER_PAY = config.HOST_API + '/dalang/pay_by_manual';

    config.API_OPEN_DOOR = config.HOST_API + '/device/open_door';
    config.API_FD_BOX_STATUS = config.HOST_API + '/dalang/box_status';
    config.API_DEVICE_ORDER_AMOUNT = config.HOST_API + '/dalang/order_ad';

    config.ORDER_STATUS = { //订单状态
        '-1': '已取消',
        '0': '未支付',
        '2': '支付中',
        '1': '已支付',
        '3': '退款申请中',
        '4': '退款成功',
        '5': '退款申请驳回'
    };

    //不需要开通免密的登录
    config.NO_MIANMI = new Array(
        "coupon.html",
        "clear.html",
        "warn_msg"
    )


    window.config = config;
})();;

/**
 * 封装异步请求
 * 包含通用的四大类请求
 * paging
 * detail
 * submit
 * custom
 */
(function() {
    var NODATA = '<div class="nodata">暂无数据。</div>',
        NOMOREDATA = '<div class="nodata">没有更多数据。</div>',
        SYSTEMERROR = '<div class="nodata">服务器异常。</div>',
        DATAERROR = '<div class="nodata">数据错误。</div>',
        csrftoken,
        loadingDom = $('#rby-loading');

    function general_sign(data,token){
        var query = '';
        var arr = new Array();
        for(var i in data){
            data[i] = data[i] === undefined ? '' : data[i];
            arr.push(i);
        }
        var data_sort = arr.sort();
        for(i=0;i<data_sort.length;i++){
            query += data_sort[i]+'='+data[data_sort[i]]+'&';
        }
        var platform = config.platformKey;
        var tmp = md5(query+platform);
        return md5(tmp.substring(0,tmp.length-1)+"q");
    }
    /**
     * 接口基类
     */
    function Api(options) {
        this.options = options || {};
        this.timeout = 15000; //请求超时时间
        this.cache = true; //是否缓存
        this.defaultListTmpl = 'rby-list-tmpl';
        this.defaultListEle = '#rby-list';
        this.defaultDetailTmpl = 'rby-detail-tmpl';
        this.defaultDetailEle = '#rby-detail';
        this.isLoading = false; //是否正在请求
        this.hasNext = true; //是否有下一页
        this.queue = {}; //请求队列
        this.tempPage = {}; //分页dom
        this.onEnd = function() {}; //当请求都完成
    }

    Api.prototype._init = function() {
        var spinnings = this.spinnings;

        return this;
    }

    /**
     * 分页查询，获取列表类型数据，自动绑定分页，当数据为空时提示无数据，当接口异常或解析错误提示服务器异常
     *
     * @param options-请求参数
     * *****
     * url 请求URL
     * data 请求数据 {} $(form)
     * type 请求类型 GET POST
     * renderFor 渲染模板
     * renderEle 渲染容器
     * showLoading 是否显示loading提示 true false
     * *****
     * pagingDom 分页容器
     * pagingMode 分页形式 'number'、'next'、'' 默认 number
     * key 分页数据的关键字 默认'body' '-1'时整个返回值为分页数据
     * *****
     * @param callback-请求成功后执行的回调方法
     * @param callbackError-请求失败后执行的回调方法
     */
    Api.prototype.paging = function(options, callback, callbackError) {
        var that = this,
            isFirst = options.data.page == 1, //是否第一次请求
            opt = { //默认配置
                renderFor: this.defaultListTmpl,
                renderEle: this.defaultListEle,
                pagingDom: '.pagination',
                pagingMode: 'next',
                timeKey: 'createAt',
                key: 'body',
                showLoading: true,
                logtype: 'paging'
            };

        extend(options, opt);

        if (options.pagingMode == 'number') {
            $(options.renderEle).html('正在加载中...');
            $(options.pagingDom).hide();
        } else if (options.pagingMode == 'next') {
            var np = findByKey(that.tempPage, options.url);
            var next = $('#np-' + np),
                nextStr = '<div id="np-' + np + '" class="nextpage">正在加载中...</div>';

            if (next.length == 0) {
                $(options.renderEle).after(nextStr);
                next = $('#np-' + np);
            }
            next.html('正在加载中...').addClass('disabled');

            if (isFirst) {
                //查第一页数据一定清空当前容器
                $(options.renderEle).html('');
            }
        }

        that.ajaxSend(options, function(response, textStatus, jqXHR) {
            var body = response;
            //var body = response[options.key];

            if (options.key == '-1') {
                //设置key=-1，所有返回值为分页数据
                body = response;
            }

            if (!that.isSusPagingData(body)) {
                $(options.renderEle).html(DATAERROR);
                return;
            }

            if (options.pagingMode == 'number') {
                if (!body || body.length == 0) {
                    //数据没有结果显示无数据提示
                    if (isFirst) {
                        $(options.renderEle).html(NODATA);
                    }
                } else {
                    that.render(options.renderEle, options.renderFor, body);
                }

                initPagination(response.pageInfo, options.pagingDom);
            } else if (options.pagingMode == 'next') {
                if (body.length == 0) {
                    //数据没有结果显示无数据提示
                    if (isFirst) {
                        next.hide();
                        $(options.renderEle).html(NODATA);
                    }
                } else {
                    that.hasNext = body.length == options.data.page_size;
                    next.show();
                    that.render(options.renderEle, options.renderFor, body, !isFirst);
                    if (!that.hasNext) {
                        //没有下一页显示无更多数据提示
                        next.html(NOMOREDATA);
                    } else {
                        next.html('正在加载更多').removeClass('disabled');
                        // options.nextButton && next.html(options.nextButton.text || '加载更多');
                    }
                }
            }

            if (typeof callback == 'function') {
                callback(response);
            }
        }, function(textStatus, data) {
            $(options.renderEle).html(SYSTEMERROR);
            next.hide();
            if (typeof callbackError == 'function') {
                callbackError(textStatus, data);
            }
        });
        //异步 分页导航 模板渲染 绑定分页事件 = 分页
    };

    /**
     * 详情查询
     *
     * @param options-请求参数
     * *****
     * url 请求URL
     * data 请求数据 {} $(form)
     * type 请求类型 GET POST
     * renderFor 渲染模板
     * renderEle 渲染容器
     * showLoading 是否显示loading提示 true false
     * *****
     * @param callback-请求成功后执行的回调方法
     * @param callbackError-请求失败后执行的回调方法
     */
    Api.prototype.detail = function(options, callback, callbackError) {
        var that = this,
            opt = { //默认配置
                renderFor: this.defaultDetailTmpl,
                renderEle: this.defaultDetailEle,
                key: '',
                showLoading: true,
                logtype: 'detail'
            };

        extend(options, opt);

        if (options.showLoading) {
            $(options.renderEle).html('<div class="loading">加载中...</div>');
        }

        that.ajaxSend(options, function(response, textStatus, jqXHR) {
            if (response.error) {
                $(options.renderEle).html(response.error);
                return;
            }
            var data = response || {};
            if (data) {
                render(options.renderEle, options.renderFor, data);
            }
            if (typeof callback == 'function') {
                callback(response);
            }
        }, callbackError);
    };

    /**
     * 表单提交
     *
     * @param options-请求参数
     * *****
     * url 请求URL
     * data 请求数据 {} $(form)
     * type 请求类型 GET POST
     * showLoading 是否显示loading提示 true false
     * *****
     * @param callback-请求成功后执行的回调方法
     * @param callbackError-请求失败后执行的回调方法
     */
    Api.prototype.submit = function(options, callback, callbackError) {
        var formData,
            that = this,
            isForm = !!options.data.length,
            btnSubmit,
            opt = {
                type: 'POST',
                showLoading: true,
                logtype: 'submit'
            };

        extend(options, opt);

        if (isForm) {
            formData = options.data.serializeArray();
            btnSubmit = options.data.find('[type="submit"]');
            btnSubmit.attr('disabled', true);
        } else {
            formData = options.data;
        }
        options.data = formData;

        that.ajaxSend(options, function(response, textStatus, jqXHR) {
            if (isForm) {
                btnSubmit.removeAttr('disabled');
            }
            if (typeof callback == 'function') {
                callback(response);
            }
        }, function(jqXHR, textStatus, errorThrown) {
            if (isForm) {
                btnSubmit.removeAttr('disabled');
            }
            if (typeof callbackError == 'function') {
                callbackError(jqXHR, textStatus, errorThrown);
            }
        });
    };

    /**
     * 自定义查询
     *
     * @param options-封装请求url，请求数据，请求类型
     * @param callback-请求成功后执行的回调方法
     * @param callbackError-请求失败后执行的回调方法
     */
    Api.prototype.custom = function(options, callback, callbackError) {
        var that = this,
            opt = {
                logtype: 'custom'
            };

        extend(options, opt);

        that.ajaxSend(options, callback, callbackError);
    };

    /**
     * jquery.ajax
     */
    Api.prototype.ajaxSend = function(options, callback, callbackError) {
        var that = this;
        that.isLoading = true;
        that.queue[options.url] = true;
        //Tools.alert("token: " + token);
        if (options.showLoading) {
            // $(options.renderEle).hide();
            loadingDom.show();
        }

        //TODO 一般这里需要添加不同的请求参数
        options = options || {};

        if (typeof options.contentType == undefined) {
            options.contentType = 'application/json'
        }
        if (typeof options.processData == undefined) {
            options.processData = true;
        }
        $.ajax({
            url: options.url,
            data: options.data,
            type: options.type || 'GET',
            beforeSend: function(request) {
                request.setRequestHeader("token", Cookie.get("token"));
                request.setRequestHeader("platform", 'wap');
                request.setRequestHeader("sign", general_sign(options.data));
            },
            dataType: 'json',
            timeout: that.timeout,
            cache: that.cache,
            contentType: options.contentType,
            processData: options.processData,
            success: function(response, textStatus, jqXHR) {
                Tools.alert("success data:" + JSON.stringify(response).substring(0, 200));
                that.isLoading = false;
                delete(that.queue[options.url]);

                if (typeof callback == 'function') {
                    callback(response);
                }
                if (isEmpety(that.queue) && typeof that.onEnd == 'function') {
                    that.onEnd.call(this);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                Tools.alert("error data: " + JSON.stringify(jqXHR.response));
                that.isLoading = false;
                delete(that.queue[options.url]);

                var obj;
                if(jqXHR.responseText){
                    obj = JSON.parse(jqXHR.responseText);
                }
                if (jqXHR.status == 401 || jqXHR.status == '401') {
                    //若接口提示未登录，自动登录
                    common.login();
                    return;
                }


                logged(options.logtype, textStatus, options.url);
                if (typeof callbackError == 'function') {
                    //callbackError(textStatus, {});
                    callbackError(obj, {});
                }

                if (isEmpety(that.queue) && typeof that.onEnd == 'function') {
                    that.onEnd.call(this);
                }
            },
            complete: function(xhr, status) {
                setTimeout(function() {
                    loadingDom.hide();
                }, 100)
                $(options.renderEle).show();
            }
        });
    }

    /**
     * 数据渲染到模板
     * @param renderEle-渲染容器
     * @param renderFor-渲染模版
     * @param data-数据
     * @param isAppend-是否追加
     */
    function render(renderEle, renderFor, data, isAppend) {
        if ($('#' + renderFor).length > 0 && data) {
            if (typeof data.length != 'undefined') {
                data = {
                    'list': data
                };
            }
            var result = tmpl(renderFor, data);
            if (isAppend) {
                $(renderEle).append(result);
            } else {
                $(renderEle).html(result);
            }
        }
    }

    /**
     * 使用模板
     * @param renderFor 模板名称
     * @data 数据
     */
    function tmpl(renderFor, data) {
        return template.render(renderFor, data);
    }

    /**
     * 记录接口的错误日志
     * @param type-接口请求类型
     * @param message-错误内容
     * @param url-错误地址
     */
    function logged(type, message, url) {
        log('[' + type + '] ' + message + ':' + url, 2);
    }

    /**
     * 判断对象是否为空
     * @param  {[type]}
     * @return {Boolean}
     */
    function isEmpety(obj) {
        var flag = true;
        for (var i in obj) {
            flag = false;
            break;
        }

        return flag;
    }

    /**
     * 验证key是否存在obj中
     * @param  obj 要验证的对象
     * @param  key 要验证的关键字
     */
    function findByKey(obj, key) {
        var arr = [],
            tar;
        for (var i in obj) {
            arr.push(obj[i]);
            if (key == i) {
                tar = obj[i];
            }
        }

        if (arr.length == 0) return obj[key] = 1;
        if (tar) return tar;
        arr = arr.sort();
        return obj[key] = arr[arr.length - 1] + 1;
    }

    /**
     * 初始化数字分页
     * @param  data 分页数据
     * current 当前页
     * size 每页条数
     * count 总记录数
     * @param  dom 分页的容器
     */
    function initPagination(data, dom) {
        if (!data) return; //数据错误不初始化

        var d = {
            current_page: data.current,
            per_page: data.size,
            total: data.count
        };

        d.current_page = parseInt(d.current_page);
        d.total = parseInt(d.total);
        d.per_page = parseInt(d.per_page);
        d.total = Math.ceil(d.total / d.per_page);

        d.prev_page = d.current_page == 1 ? 1 : d.current_page - 1;
        d.next_page = d.current_page == d.total ? d.current_page : d.current_page + 1;
        var start = d.current_page - 2,
            end = d.current_page + 2;

        if (d.total <= 5) {
            start = 1;
            end = d.total;
        } else {
            if (start < 1) {
                start = 1;
                end = start + 4;
            }
            if (end > d.total) {
                end = d.total;
                start = d.total - 4;
            }
        }

        var result = '';

        result += '<dl><dt' + (d.prev_page == 1 ? ' class="disabled"' : '') + '><a href="#' + d.prev_page + '"><img src="images/arrow_left.gif"></a></dt><dd>';
        for (var i = start; i <= end; i++) {
            result += '<a href="#' + i + '"' + (d.current_page == i ? ' class="active"' : '') + '>' + i + '</a>';
        }
        result += '</dd><dt class="ari' + (d.next_page >= d.total ? ' disabled' : '') + '"><a href="#' + d.next_page + '"><img src="images/arrow_left.gif"></a></dt></dl>';

        $(dom).html(result).show();
    }

    /**
     * 扩展参数
     * @param  options 被扩展参数
     * @param  opt 扩展参数
     */
    function extend(options, opt) {
        options = options || {};
        for (var i in opt) {
            options[i] = typeof options[i] == 'undefined' ? opt[i] : options[i];
        }
    }

    /**
     * 是否正确的分页数据
     * @param  data 分页数据
     * @return {Boolean}
     */
    function isSusPagingData(data) {
        return typeof data == 'object' && typeof data.length != undefined;
    }

    //抛出公用方法，保持模板调用入口唯一
    Api.prototype.render = render;
    Api.prototype.logged = logged;
    Api.prototype.isSusPagingData = isSusPagingData;

    window.Ajax = new Api();
})();; //debug
var log = function(m) {
    if (typeof console != 'undefined') {
        console.log(m);
    }
};

/**
 * 本地cookie读写
 */
(function() {
    var Cookie = {
        get: function(sname) {
            var sre = "(?:;)?" + sname + "=([^;]*);?";
            var ore = new RegExp(sre);
            if (ore.test(document.cookie)) {
                try {
                    return unescape(RegExp["$1"]); // decodeURIComponent(RegExp["$1"]);
                } catch (e) {
                    return null;
                }
            } else {
                return null;
            }
        },

        /**
         * 设置Cookie
         * @param {[String]}
         * @param {[String]}
         * @param {[Number]} 天数
         * @param {[Number]} 小时数
         * @param {[Number]} 分钟数
         * @param {[Number]} 秒数
         */
        _set: function(c_name, value, days, hours, minutes, seconds) {
            var expires = null;
            if (typeof days == 'number' && typeof hours == 'number' && typeof minutes == 'number' && typeof seconds == 'number') {
                if (days == 0 && hours == 0 && minutes == 0 && seconds == 0) {
                    expires = null;
                } else {
                    expires = this.getExpDate(days, hours, minutes, seconds);
                }
            } else {
                expires = days || this.getExpDate(7, 0, 0, 0);
            }
            document.cookie = c_name + "=" + escape(value) + ((expires == null) ? "" : ";expires=" + expires) + "; path=/";
        },

        /**
         * 设置Cookie
         * @param {[type]} name
         * @param {[type]} value
         * @param {[type]} 天数，默认7天、0不设置、-1移除
         */
        set: function(c_name, value, days) {
            if(null == days){
                this._set(c_name, value, 7, 0, 0, 0);
            }else{
                this._set(c_name, value, days, 0, 0, 0);
            }
        },

        remove: function(key) {
            this.set(key, '', -1);
        },
        //获取过期时间，d天数、h小时、m分钟、s秒
        getExpDate: function(d, h, m, s) {
            var r = new Date;
            if (typeof d == "number" && typeof h == "number" && typeof m == "number" && typeof s == 'number')
                return r.setDate(r.getDate() + parseInt(d)), r.setHours(r
                        .getHours() + parseInt(h)), r.setMinutes(r.getMinutes() + parseInt(m)), r.setSeconds(r.getSeconds() + parseInt(s)),
                    r.toGMTString()
        }
    };
    window.Cookie = Cookie;
})();;
/**
 * 自定义验证，用于简单的规格验证
 */
(function() {
    String.prototype.isSpaces = function() {
        for (var i = 0; i < this.length; i += 1) {
            var ch = this.charAt(i);
            if (ch != ' ' && ch != "\n" && ch != "\t" && ch != "\r") {
                return false;
            }
        }
        return true;
    };

    String.prototype.isValidMail = function() {
        return (new RegExp(
            /^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/)
            .test(this));
    };

    String.prototype.isPhone = function() {
        return (new RegExp(/^1\d{10}?$/).test(this));
    };

    String.prototype.isEmpty = function() {
        return (/^\s*$/.test(this));
    };

    String.prototype.isValidPwd = function() {
        return (new RegExp(/^([_]|[a-zA-Z0-9@]){6,16}$/).test(this));
    };

    String.prototype.isPostCode = function() {
        return (new RegExp(/^\d{6}?$/).test(this));
    };
})();;
/**
 * 自定义弹出页，依赖jquery
 */
(function(window) {
    var tempPage = 0; //打开页面的计数，
    var SecondPage = function(options) {
        var that = this;

        if (typeof options == 'object') {
            for (var i in options) {
                that[i] = options[i];
            }
        } else if (typeof options == 'string') {
            that.targetPage = $(options);
        }
        that.coverDom = that.coverDom || $('#sidebar-bg');

        //默认点击遮罩层关闭
        that.coverDom.click(function(e) {
            e.preventDefault();
            that.closeSidebar();
        })
    }

    SecondPage.prototype = {
        targetPage: undefined, //当前页面DOM
        coverDom: undefined, //遮罩层
        beforeOpen: function() {}, //打开之前
        afterClose: function() {}, //关闭之后
        openSidebar: function(fn) {
            var container = $(window),
                w = container.width(),
                h = container.height(),
                clientH = this.targetPage.height(),
                that = this;
            that.coverDom.show();
            that.targetPage.show()
                .css({
                    'width': w
                    // 'height': h
                });
            setTimeout(function() {
                that.targetPage.addClass('open');
            }, 100)
            tempPage++;
            if (!$('body').hasClass('move')) {
                $('body').addClass('move')
                    .css({
                        'width': document.documentElement.clientWidth,
                        'height': document.documentElement.clientHeight,
                        'overflow': 'hidden'
                    });
            }
            fn && fn();
            that.beforeOpen && that.beforeOpen();
        },

        closeSidebar: function(fn) {
            var that = this;
            this.targetPage.removeClass('open');
            tempPage--;
            setTimeout(function() {
                that.coverDom.hide();
                that.targetPage.hide();
                hasOpend = false;
                if (tempPage <= 0) {
                    $('body').removeClass('move')
                        .css({
                            'width': 'auto',
                            'height': 'auto',
                            'overflow': 'inherit'
                        });
                }
                fn && fn();
                that.afterClose && that.afterClose();
            }, 220);
        }
    }

    window.SecondPage = SecondPage;
})(window);;
/**
 * 本地存储扩展
 */
(function() {
    var Storage = {
        AUTH: 'FLV-AUTH',
        ACCOUNT: 'FLV-ACCOUNT',
        REMEMBER: 'FLV-REMEMBER',
        LOGIN_HISTORY: 'LH',
        AREA: 'FLV-AREA',
        get: function(key, isSession) {
            if (!this.isLocalStorage()) {
                return;
            }
            var value = this.getStorage(isSession).getItem(key);
            if (value) {
                return JSON.parse(value);
            } else {
                return undefined;
            }
        },
        set: function(key, value, isSession) {
            if (!this.isLocalStorage()) {
                return;
            }
            value = JSON.stringify(value);
            this.getStorage(isSession).setItem(key, value);
        },
        remove: function(key, isSession) {
            if (!this.isLocalStorage()) {
                return;
            }
            this.getStorage(isSession).removeItem(key);
        },
        getStorage: function(isSession) {
            return isSession ? sessionStorage : localStorage;
        },
        isLocalStorage: function() {
            try {
                if (!window.localStorage) {
                    log('不支持本地存储');
                    return false;
                }
                return true;
            } catch (e) {
                log('本地存储已关闭');
                return false;
            }
        }
    };

    window.Storage = Storage;
})();;
/**
 * 扩展模板帮助方法
 * 依赖artTemplate，tools
 */
(function(template) {
    if (!template) return;

    template.openTag = "<!--[";
    template.closeTag = "]-->";

    // 模板帮助方法，绝对化图片地址
    template.helper('$absImg', function(content, defaultValue) {
        return Tools.absImg(content, defaultValue);
    });

    // 模板帮助方法，转换时间戳成字符串
    template.helper('$formatDate', function(content, type, defaultValue) {
        return Tools.formatDate(content, type, defaultValue || '--');
    });

    //模板帮助方法，编码url参数
    template.helper('$encodeUrl', function(content) {
        return encodeURIComponent(content);
    });

    //模板帮助方法，格式化货币
    template.helper('$formatCurrency', function(content, defaultValue, unit) {
        return Tools.formatCurrency(content, defaultValue, unit);
    });

    //模板帮助方法，\r\n替换换行
    template.helper('$convertRN', function(content) {
        if (!content) {
            return '--';
        }
        return content.replace(/\r\n/gi, '<br/>');
    });

    //模板帮助方法，根据序列值添加样式名
    template.helper('$addClassByIdx', function(i, v, className) {
        if (i == v) {
            return className || '';
        }
    });

    //模板帮助方法，截取内容长度添加省略号
    template.helper('$ellipsis', function(content, length) {
        var v = content.replace(/[^\x00-\xff]/g, '__').length;
        if (v / 2 > length) {
            return content.substring(0, length) + '...';
        }
        return content;
    });

    //模板帮助方法， 从时间字符串中截取日期，限定字符串yyyy-MM-dd...
    template.helper('$getDateFromStr', function(content) {
        if (!content || content.length == 0) {
            return;
        }

        var len = content.length > 10 ? 10 : content.length;
        return content.substring(0, len);
    });

    //模板帮助方法，转换价格
    template.helper('$rbyFormatCurrency', function(content) {
        return Tools.rbyFormatCurrency(content);
    });

    //模板帮助方法，根据条件添加样式
    template.helper('$addClassByCondition', function(condition, className, className2) {
        if (condition) {
            return className || '';
        } else {
            return className2 || '';
        }
    });

    //模板帮助方法，获取订单状态值
    template.helper('$getOrderStatus', function(content, type) {
        return config.ORDER_STATUS[content] || '--';
    });

    //模板帮助方法，
    template.helper('$addClassForGoods', function(data, className, className2) {
        if (!data || isNaN(data.limitNum) || isNaN(data.store) || isNaN(data.quantity)) return '';
        data.limitNum = parseInt(data.limitNum);
        data.store = parseInt(data.store);
        data.quantity = parseInt(data.quantity);
        if (data.quantity >= Math.min(data.limitNum, data.store)) {
            return className || '';
        } else {
            return className2 || '';
        }
    });

    // 模板帮助方法，格式化倒计时
    template.helper('$getCountDown', function(data, other) {
        if (typeof data == 'object') {
            return Tools.getRunTime(data.serverTime, data.endTime);
        } else {
            return Tools.getRunTime(data, other);
        }
    });

    // 模板帮助方法，转换微信头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。
    template.helper('$absWechatIcon', function(content) {
        if (!content || content.indexOf('http://') != 0) return '../content/images/common/headicon.png';
        //http://wx.qlogo.cn/mmopen/xxx/0
        var arr = content.split('/');
        arr[arr.length - 1] = '96';
        return arr.join('/');
    });
})(window.template);
/**
 * 工具类，包括自定义提示框、格式化日期、格式化货币、获取查询字符串、格式化表单等
 **/
(function() {
    var that = this,
        preventDefault, panel, panelBg, delay, count = 0,
        toastPanel, temp;

    //自定义提示框，依赖jquery
    var TipPanel = function(el, options) {
        var that = this;

        that.panel = el || $('#rby-panel');
        that.panelBg = panelBg || $('#rby-panel-bg');
        that.panelTitle = that.panel.find('.panel-title');
        that.panelTips = that.panel.find('.panel-tips');
        that.btnOk = that.panel.find('.btn-primary');
        that.btnCancel = that.panel.find('.btn-default');
        that.panelText = that.panel.find('.panel-text');
        that.panelTick = that.panel.find('.panel-tick');
        that.panelImg = that.panel.find('.panel-img');

        that.options = {
            type: 'error',
            tick: 0,
            okText: '确定',
            cancelText: '取消',
            showTitle: false,
            showTips: false
        };

        //关闭
        that.panel.on('click', '.btn-primary', function(e) {
            e.preventDefault();
            that.hide(true);
        });

        //取消
        that.panel.on('click', '.btn-default', function(e) {
            e.preventDefault();
            that.hide();
        });
        //提示
        that.panel.on('click', '.panel-tips', function(e) {
            e.preventDefault();
            that.hide(true);
        });

    };

    TipPanel.prototype = {
        delay: undefined,
        count: 0,
        setOptions: function(options) {
            var that = this;

            for (i in options) that.options[i] = options[i];

            if (that.options.showTitle) {
                that.panelTitle.show();
            } else {
                that.panelTitle.hide();
            }
            if (that.options.showTips) {
                that.panelTips.show();
                that.btnOk.hide();
            } else {
                that.panelTips.hide();
            }
            if (that.options.okText) {
                that.btnOk.text(that.options.okText);
            }
            if (that.options.cancelText) {
                that.btnCancel.text(that.options.cancelText);
            }
            if (that.options.tipsText) {
                that.panelTips.html(that.options.tipsText);
            }
            if (that.options.titleText) {
                that.panelTitle.text(that.options.titleText);
            }
            if(that.options.panelImg){
                that.panelImg.attr('src',that.options.panelImg);
            }
            if (that.options.type == 'confirm') {
                that.btnOk.show();
                that.btnCancel.show();
            } else if (that.options.cancelText){
                that.btnCancel.show();
            }else{
                that.btnCancel.hide();
            }
            that.panelText.html(that.options.message);
            that.panel.css('margin-top', -(that.panel.height() / 2)).show();
            that.panelBg.show();

            if (that.options.tick > 1000) {
                that.panelTick.text(that.options.tick / 1000);
                that.delay = setInterval(function() {
                    if (that.count < that.options.tick - 1000) {
                        that.count = count + 1000;
                        that.panelTick.text((that.options.tick - count) / 1000);
                    } else {
                        that._end();
                        that.count = 0;
                        clearInterval(that.delay);
                    }
                }, 1000);
            } else if (that.options.tick <= 1000 && that.options.tick > 0) {
                that.delay = setTimeout(function() {
                    that._end();
                }, that.options.tick);
            }
        },
        _end: function() {
            var that = this;

            that.panel.hide();
            that.panelBg.hide();

            if (typeof that.options.tipsCallback == 'function') {
                that.options.tipsCallback();
                that.options.tipsCallback = undefined;
            } else if (typeof that.options.yesCallback == 'function') {
                that.options.yesCallback();
                that.options.yesCallback = undefined;
            }
            if (typeof that.options.noCallback == 'function') {
                that.options.noCallback();
            }
        },
        show: function() {

        },
        hide: function(yesClick) {
            var that = this;

            if (that.delay) {
                clearTimeout(that.delay);
            }
            if (!that.panel) {
                return;
            }
            that.panel.hide();
            that.panelBg.hide();

            if (yesClick) {
                typeof that.options.yesCallback == 'function' && that.options.yesCallback();
                typeof that.options.tipsCallback == 'function' && that.options.tipsCallback();
            } else {
                typeof that.options.noCallback == 'function' && that.options.noCallback();
            }
            that.options.yesCallback = undefined;
            that.options.noCallback = undefined;
            that.options.tipsCallback = undefined;
        }
    }

    //按指定格式格式化日期
    function format(date, pattern) {
        var that = date;
        var o = {
            "M+": that.getMonth() + 1,
            "d+": that.getDate(),
            "h+": that.getHours(),
            "m+": that.getMinutes(),
            "s+": that.getSeconds(),
            "q+": Math.floor((that.getMonth() + 3) / 3),
            "S": that.getMilliseconds()
        };
        if (/(y+)/.test(pattern)) {
            pattern = pattern.replace(RegExp.$1, (that.getFullYear() + "")
                .substr(4 - RegExp.$1.length));
        }
        for (var k in o) {
            if (new RegExp("(" + k + ")").test(pattern)) {
                pattern = pattern.replace(RegExp.$1, RegExp.$1.length == 1 ? o[k] : ("00" + o[k]).substr(("" + o[k]).length));
            }
        }
        return pattern;
    };

    var Tools = {
        //绝对化图片地址
        absImg: function(content, defaultValue) {
            if (!content) {
                // 测试时使用相对
                return defaultValue || config.DEF_IMG_URL;
            }
            if (content && content.indexOf('http') == 0) {
                return content;
            }
            return config.HOST_IMAGE + content;
        },
        //时间戳格式化
        formatDate: function(content, type, defaultValue) {
            var pattern = type || "yyyy-MM-dd hh:mm";
            if (isNaN(content) || content == null) {
                return defaultValue || content;
            } else if (typeof(content) == 'object') {
                var y = dd.getFullYear(),
                    m = dd.getMonth() + 1,
                    d = dd.getDate();
                if (m < 10) {
                    m = '0' + m;
                }
                var yearMonthDay = y + "-" + m + "-" + d;
                var parts = yearMonthDay.match(/(\d+)/g);
                var date = new Date(parts[0], parts[1] - 1, parts[2]);
                return format(date, pattern);
            } else {
                if (content.length == 10)
                    content = content + '000';
                var date = new Date(parseInt(content));
                return format(date, pattern);
            }
        },
        // 货币格式化，2050.5=>2,050.5
        formatCurrency: function(content, defaultValue, unit) {
            if (!content) {
                return defaultValue || '--';
            }

            content = content + ''; //转字符串

            var prefix, subfix, idx = content.indexOf('.');
            if (idx > 0) {
                prefix = content.substring(0, idx);
                subfix = content.substring(idx, content.length);
            } else {
                prefix = content;
                subfix = '';
            }

            var mod = prefix.toString().length % 3;
            var sup = '';
            if (mod == 1) {
                sup = '00';
            } else if (mod == 2) {
                sup = '0';
            }

            prefix = sup + prefix;
            prefix = prefix.replace(/(\d{3})/g, '$1,');
            prefix = prefix.substring(0, prefix.length - 1);
            if (sup.length > 0) {
                prefix = prefix.replace(sup, '');
            }
            if (subfix) {
                if (subfix.length == 2) {
                    subfix += '0';
                } else if (subfix.length == 1) {
                    subfix += '00';
                }
                subfix = subfix.substring(0, 3);
            }
            return prefix + subfix;
        },
        strToDate: function(str) { //字符串转日期，yyyy-MM-dd hh:mm:ss
            var tempStrs = str.split(" ");
            var dateStrs = tempStrs[0].split("-");
            var year = parseInt(dateStrs[0], 10);
            var month = parseInt(dateStrs[1], 10) - 1;
            var day = parseInt(dateStrs[2], 10);

            var timeStrs = tempStrs[1].split(":");
            var hour = parseInt(timeStrs[0], 10);
            var minute = parseInt(timeStrs[1], 10) - 1;
            var second = parseInt(timeStrs[2], 10);
            var date = new Date(year, month, day, hour, minute, second);
            return date;
        },
        // 倒计时  9527
        getRunTime2: function(systemTime, endTime) {
            if (!systemTime || isNaN(systemTime) || !endTime || isNaN(endTime)) {
                return '数据错误';
            }
            var showTime = parseInt(endTime) - parseInt(systemTime);
            if (showTime <= 0) {
                return '已结束';
            }
            var nD = Math.floor(showTime / (60 * 60 * 24));
            var nH = Math.floor(showTime / (60 * 60)) % 24;
            var nM = Math.floor(showTime / 60) % 60;
            var nS = Math.floor(showTime) % 60;

            return nD + '天' + Tools.checkTime(nH) + '小时' + Tools.checkTime(nM) + '分钟' + Tools.checkTime(nS) + '秒';
        },
        getRunTime: function(systemTime, endTime) {
            if (!systemTime || isNaN(systemTime) || !endTime || isNaN(endTime)) {
                return '数据错误';
            }
            var showTime = parseInt(endTime) - parseInt(systemTime);
            if (showTime <= 0) {
                return '已结束';
                // showTime = 0;
            }
            var nD = Math.floor(showTime / (60 * 60 * 24));
            var nH = Math.floor(showTime / (60 * 60)) % 24;
            var nM = Math.floor(showTime / 60) % 60;
            var nS = Math.floor(showTime) % 60;

            return '剩余 <span><em>' + Tools.checkTime(nD) + '</em>天<em>' + Tools.checkTime(nH) + '</em>时<em>' + Tools.checkTime(nM) + '</em>分<em>' + Tools.checkTime(nS) + '</em>秒</span> 结束';
        },
        checkTime: function(i) { //时分秒为个位，用0补齐
            if (i < 10) {
                i = "0" + i;
            }
            return i;
        },

        //获取URL参数
        getQueryValue: function(key) {
            var q = location.search,
                keyValuePairs = new Array();

            if (q.length > 1) {
                var idx = q.indexOf('?');
                q = q.substring(idx + 1, q.length);
            } else {
                q = null;
            }

            if (q) {
                for (var i = 0; i < q.split("&").length; i++) {
                    keyValuePairs[i] = q.split("&")[i];
                }
            }

            for (var j = 0; j < keyValuePairs.length; j++) {
                if (keyValuePairs[j].split("=")[0] == key) {
                    // 这里需要解码，url传递中文时location.href获取的是编码后的值
                    // 但FireFox下的url编码有问题
                    return decodeURI(keyValuePairs[j].split("=")[1]);

                }
            }
            return '';
        },
        // 获取窗口尺寸，包括滚动条
        getWindow: function() {
            return {
                width: window.innerWidth,
                height: window.innerHeight
            };
        },
        // 获取文档尺寸，不包括滚动条但是高度是文档的高度
        getDocument: function() {
            var doc = document.documentElement || document.body;
            return {
                width: doc.clientWidth,
                height: doc.clientHeight
            };
        },
        // 获取屏幕尺寸
        getScreen: function() {
            return {
                width: screen.width,
                height: screen.height
            };
        },
        // 显示、禁用滚动条
        showOrHideScrollBar: function(isShow) {
            preventDefault = preventDefault || function(e) {
                    e.preventDefault();
                };
            (document.documentElement || document.body).style.overflow = isShow ? 'auto' : 'hidden';
            // 手机浏览器中滚动条禁用取消默认touchmove事件
            if (isShow) {
                // 注意这里remove的事件必须和add的是同一个
                document.removeEventListener('touchmove', preventDefault, false);
            } else {
                document.addEventListener('touchmove', preventDefault, false);
            }
        },
        // 显示对话框
        showDialog: function() {},
        // 显示着遮罩层
        showOverlay: function() {},
        // 显示确认框
        showConfirm: function(msg, yesCallback, noCallback) {
            var opt = {};
            if (typeof msg == 'object') {
                opt = msg;
            } else {
                opt.message = msg;
                opt.yesCallback = yesCallback;
                opt.noCallback = noCallback;
            }
            opt.type = 'confirm';
            opt.showTitle = true;
            opt.showTip = false;
            opt.titleText = '提示';

            panel = panel || new TipPanel();
            panel.setOptions(opt);
        },
        // 显示提示
        showAlert: function(msg, tick, callback) {
            var opt = {};
            if (typeof msg == 'object') {
                opt = msg;
            } else {
                opt = {
                    showTips:true,
                    tipsText:"我知道了",
                    showTitle:true,
                    message: msg,
                    titleText:"提示",
                    cancelText:false,
                    panelImg:'img/tip.png',
                    tipsCallback:callback,
                    tick :tick
                }
            }
            // opt.type = 'alert';
            panel = panel || new TipPanel();
            panel.setOptions(opt);
        },
        // 显示加载框
        showLoading: function() {
            $('#rby-loading').show();
        },
        hideLoading: function() {
            $('#rby-loading').hide();
        },
        hidePanel: function(yesClick) {
            panel && panel.hide(yesClick);
        },
        showToast: function(msg, tick) {
            toastPanel = toastPanel || $('#rby-toast');
            tick = tick || 1000;

            if (delay) {
                clearTimeout(delay);
            }

            toastPanel.find('span').text(msg);
            toastPanel.show();
            delay = setTimeout(function() {
                toastPanel.hide();
            }, tick);
        },
        isIPad: function() {
            return (/iPad/gi).test(navigator.appVersion);
        },
        isIos: function() {
            return (/iphone|iPad/gi).test(navigator.appVersion);
        },
        isAndroid: function() {
            return (/android/gi).test(navigator.appVersion);
        },
        isWeChatBrowser: function() {
            var e = navigator.userAgent.toLowerCase();
            return "micromessenger" == e.match(/MicroMessenger/i) ? !0 : !1
        },
        isRbyAppBrowser: function() {
            var e = navigator.userAgent.toLowerCase();
            return "rbyapp" == e.match(/rbyapp/i) ? !0 : !1
        },
        isAlipayBrowser: function() {
            var e = navigator.userAgent.toLowerCase();
            return "alipay" == e.match(/Alipay/i) ? !0 : !1
        },
        isFDBrowser: function() {
            var e = navigator.userAgent.toLowerCase();
            return "fd" == e.match(/FD/i) ? !0 : !1
        },
        returnUserAgent:function () {
            if(this.isAlipayBrowser()) {
                return "alipay";
            } else if(this.isWeChatBrowser()){
                return "wechat";
            } else if(this.isFDBrowser()) {
                return "fruitday-app";
            }else if(this.isGatApp()){
                return 'gat';
            }else if(this.isCmbApp()){
                return 'cmb';
            }else{
                return "fruitday-web";
            }
        },
        isFruitdayAppBrowser: function() {
            var e = navigator.userAgent;
            return ("FD_iPhone" == e.match(/FD_iPhone/i) || "FD_Android" == e.match(/FD_Android/i)) ? !0 : !1
        },
        isFruitdayAndroidBrowser: function() {
            var e = navigator.userAgent;
            return ("FD_Android" == e.match(/FD_Android/i)) ? !0 : !1
        },
        isFruitdayiOSBrowser: function() {
            var e = navigator.userAgent;
            return ("FD_iPhone" == e.match(/FD_iPhone/i)) ? !0 : !1
        },
        isGatApp: function() {
            var e = navigator.userAgent;
            return ("GatApp" == e.match(/GatApp/i)) ? !0 : !1
        },
        isCmbApp: function() {
            var e = navigator.userAgent;
            return ("MPBank" == e.match(/MPBank/i)) ? !0 : !1
        },
        // 将form中的值转换为键值对
        formJson: function(form) {
            var o = {};
            var a = $(form).serializeArray();
            $.each(a, function() {
                if (o[this.name] !== undefined) {
                    if (!o[this.name].push) {
                        o[this.name] = [o[this.name]];
                    }
                    o[this.name].push(this.value || '');
                } else {
                    o[this.name] = this.value || '';
                }
            });
            return o;
        },
        alert: function(e) {
            if(Cookie.get("DevDebug") == 1){
                alert(e);
            }else{
                console.log(e);
            }
        },
        _GET: function() {
            var e = location.search,
                o = {};
            if ("" === e || void 0 === e) return o;
            e = e.substr(1).split("&");
            for (var n in e) {
                var t = e[n].split("=");
                o[t[0]] = t[1]
            }
            return o.from && delete o.code, o
        },
        removeParamFromUrl: function(e) {
            var o = Tools._GET();
            for (var n in e) delete o[e[n]];
            return location.pathname + Tools.buildUrlParamString(o)
        },
        buildUrlParamString: function(e) {
            var o = "";
            for (var n in e) o += n + "=" + e[n] + "&";
            o = o.slice(0, o.length - 1);
            var t = "" === o || void 0 === o;
            return t ? "" : "?" + o
        },
        //格式化价格，显示两位小数，当两位小数都为0是省略
        rbyFormatCurrency: function(content) {
            if (!content || isNaN(content)) return content;

            var v = parseFloat(content),
                result = v.toFixed(2);
            if (result.indexOf('.00') >= 0) {
                result = parseFloat(content).toFixed(0);
            }
            return result;
        }
    };

    window.Tools = Tools;
})();

/**
 * TODO 根据具体业务逻辑修改 test watch
 */
(function () {

    var common = {};

    //获取登录的id
    common.getId = function () {
        var auth = Cookie.get(Storage.AUTH);
        return auth;
    };

    //获取微信号
    common.getOpenId = function () {
        return Cookie.get(Storage.OPENID);
    };

    //自动登录
    common.login = function () {

        if(!Tools.isAlipayBrowser()){
            location.href = "../public/err_scan.html";
            return;
        }
        var appId = config.alipayAppId,
            code = Tools.getQueryValue('auth_code');
        if (common.isLogining) return;//过滤多次的登录请求
        var url = config.API_ALIPAY_ACCOUNT_AUTOLOGIN;
        common.isLogining = true;
        Tools.alert("code: " + code);
        if (void 0 === code || "" == code) {
            //尤其注意：由于授权操作安全等级较高，所以在发起授权请求时，微信会对授权链接做正则强匹配校验，如果链接的参数顺序不对，授权页面将无法正常访问
            //?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE&connect_redirect=1#wechat_redirect
            var n = location.origin + Tools.removeParamFromUrl(["from", "code", "share_id", "isappinstalled", "state", "m", "c", "a","from_wxpay"]);

            var t = config.alipayAuthUrl;

                t = t.replace('APPID', appId).replace('REDIRECT_URI', encodeURIComponent(n));

            //document.write(t);
            Tools.alert("url: " + t);
            location.href = t;
        } else {
            var f_url = Tools.removeParamFromUrl(["auth_code"]);
            Tools.alert("url : " + f_url);
            var no_mianmi = 0;
            for(var c = 0;c <config.NO_MIANMI.length;c++){
                if(f_url.indexOf(config.NO_MIANMI[c]) > 0){
                    no_mianmi = 1;
                    break;
                }
            }
            Cookie.set('hasLoad', '', -1);//若有登录，需清空弹出窗口的记录标志
            Cookie.remove("BoxToken", -1);
            Ajax.custom({
                url: url,
                type: 'POST',
                showLoading: true,
                data: {
                    auth_code: code,
                    device_id: Cookie.get("deviceId"),
                    mianmi:no_mianmi
                }
            }, function (response) {
                console.log(response);
                common.isLogining = false;
                var o = response.user;
                Cookie.remove("token");
                Cookie.remove("a_s_token");
                Cookie.set("token", response.token, null);
                //Cookie.set("deviceBind", response.token, null);
                if (o.mobile && parseInt(o.mobile) > 0) {
                    Cookie.set("mobile", o.mobile, null);
                } else {
                    Cookie.set("mobile", '', null);
                }
                Cookie.set("UserSN", o.id, null);
                Tools.alert("UserSN: " + Cookie.get("UserSN"));
                Tools.alert("deviceBind: " + response.token + " " + Cookie.get("deviceBind"));
                location.href = Tools.removeParamFromUrl(["auth_code"]);
                location.href = Tools.removeParamFromUrl(["code"]);
            }, function (e) {
                if (e.message.substring(0, 4) == "http") {
                    location.href = e.message;
                    return;
                }
                location.href = Tools.removeParamFromUrl(["auth_code"]);
                location.href = Tools.removeParamFromUrl(["code"]);
                Tools.showToast(e.message || '服务器异常');
                common.isLogining = false;
            })
        }
    }

    //检查当前登录状态
    common.checkLoginStatus = function (fn) {

        common.init = fn;
        var auth_code= Tools._GET().auth_code || 0;//判断关爱通的登录凭证
        Tools.alert(auth_code);
        var userSn = Cookie.get("UserSN");
        if (userSn) {
            Tools.alert("good token & app id");
            //确保登录后在加载数据
            fn && fn();
        } else {
            common.login();
        }
    }


    var onlyFirst = false; // 倒计时标志，确保只初始化一次

    /**
     * 自定义倒计时
     * @return {[type]} [description]
     */
    common.initCountDown = function (serverTime, sel) {
        if (onlyFirst) {
            return;
        }
        onlyFirst = true;

        var tick = 0,
            serverTime = parseInt(serverTime);
        setInterval(function () {
            $(sel).each(function (i, d) {
                var endTime = $(this).attr('data-end');
                $(this).text(Tools.getRunTime(serverTime + tick, endTime));
            })
            tick++;
        }, 1000)
    }

    /**
     * 自定义延迟加载图片
     * @param  {[type]} sel 图片选择器
     * @return {[type]}
     */
    common.lazyload = function (sel) {
        var dh = $(document).height(), //内容的高度
            wh = $(window).height(), //窗口的高度
            st = 0; //滚动的高度

        $(window).scroll(function () {
            st = $(window).scrollTop();
            init();
        })

        setTimeout(init, 200);

        function init() {
            $(sel).each(function (i, d) {
                if ($(this).hasClass('loaded')) return;

                var d = $(d).offset();
                if (d.top > st && d.top < (st + wh)) {
                    $(this).attr('src', $(this).attr('data-src')).addClass('loaded');
                }
            })
        }
    }

    //点击加载下一页
    $(document).on('click', '.nextpage', function (response) {
        if ($(this).hasClass('disabled')) return;
        config.page++;
        common.getList && common.getList();
    })

    //滚动到底自动加载下一页
    $(window).scroll(function () {
        if ($('.nextpage').length == 0 || $('.nextpage').hasClass('disabled')) return;

        var st = $(window).scrollTop(),
            wh = $(window).height(), //窗口的高度
            d = $('.nextpage').offset();

        if (d.top < (st + wh)) {
            config.page++;
            common.getList && common.getList();
        }
    })

    function general_sign(data,token){
        console.log(data);
        var query = '';

        var arr = new Array();
        for(var i in data){
            arr.push(i);
        }
        var data_sort = arr.sort();
        for(i=0;i<data_sort.length;i++){
            query += data_sort[i]+'='+data[data_sort[i]]+'&';
        }
        var platform = config.platformKey;
        var tmp = md5(query+platform);
        return md5(tmp.substring(0,tmp.length-1)+"q");
    }

    function getWeChatJsSdkSignature(e, t, r) {

        var n = $.ajax({
                url: config.HOST_API + "/account/getjsapisign",
                data: {
                    noncestr: e,
                    timestamp: t,
                    url: (r) //签名需要是未编码的地址，如果接口没有解析直接传值
                },
                beforeSend: function(request) {
                    request.setRequestHeader("platform", 'wap');
                    request.setRequestHeader("sign", general_sign({
                        noncestr: e,
                        timestamp: t,
                        url: (r) //签名需要是未编码的地址，如果接口没有解析直接传值
                    }));
                },
                timeout: 3e3,
                async: !1
            }).responseText,
            a = JSON.parse(n);
        return a.signature
    }

    function getShareIdUrl(e) {
        var t = Cookie.get("UserSN"),
            r = e;
        return r = r.indexOf("?") <= 0 ? r + "?share_id=" + t : r.indexOf("share_id=") <= 0 ? r + "&share_id=" + t : changeURLArg(r, "share_id", t)
    }

    function changeURLArg(url, arg, arg_val) {
        var pattern = arg + "=([^&]*)",
            replaceText = arg + "=" + arg_val;
        if (url.match(pattern)) {
            var tmp = "/(" + arg + "=)([^&]*)/gi";
            return tmp = url.replace(eval(tmp), replaceText)
        }
        return url.match("[?]") ? url + "&" + replaceText : url + "?" + replaceText
    }

    function wechat_share_config() {
        var e = config.wechatAppId,
            t = "734618974",
            r = Math.floor(Date.now() / 1000), //签名需要10位的时间戳
            n = getWeChatJsSdkSignature(t, r, document.URL, e);
        wx.config({
            debug: !1,
            appId: e,
            timestamp: r,
            nonceStr: t,
            signature: n,
            jsApiList: ["onMenuShareTimeline", "onMenuShareAppMessage","scanQRCode"]
        }), wx.error(function () {
        })
    }

    function wechat_share_override(e, t, r) {
        if (typeof wx == 'undefined') return;

        wechat_share_config();
        var n = getShareIdUrl(r);
        wx.ready(function () {
            wx.onMenuShareTimeline({
                title: e.title,
                link: n,
                imgUrl: e.imgUrl,
                success: function () {
                },
                cancel: function () {
                }
            }), wx.onMenuShareAppMessage({
                title: t.title,
                desc: t.desc,
                link: n,
                imgUrl: t.imgUrl,
                type: "link",
                success: function () {
                },
                cancel: function () {
                }
            })
        })

        common.share.timeline = e;
        common.share.app = t;
        common.share.url = r;
    }

    common.wx_scanQRCode = function (succFn) {
        if (typeof wx == 'undefined') return;
        var qr = '';
        wechat_share_config();
        wx.ready(function () {
            wx.scanQRCode({
                needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                success: function (res) {
                    Tools.alert("bar:"+res.resultStr);
                    succFn(res.resultStr);
                }
            });
        });
    }
    common.aliScan = function (succFn,failFn) {
        if (typeof Ali == 'undefined') return;
        if(Tools.isAlipayBrowser()){
            if((Ali.alipayVersion).slice(0,3)>=8.1){
                Ali.scan({
                    type: 'qr'
                }, function(result) {
                    Tools.alert(result);
                    if(result.errorCode){
                        //没有扫码的情况
                        //errorCode=10，用户取消
                        //errorCode=11，操作失败
                        if(failFn){
                            failFn(result);
                        }
                    }else{
                        //成功扫码的情况
                        if(result.barCode !== undefined){
                            Tools.alert('条码是：'+result.barCode);
                            succFn(result.barCode);
                        }else if(result.qrCode !== undefined){
                            Tools.alert('二维码是：'+result.qrCode);
                            succFn(result.qrCode);
                        }else if(result.cardNumber !== undefined){
                            Tools.alert('银行卡号是：'+result.cardNumber);
                        }else{
                            Tools.alert(result);
                        }
                    }
                });
            }else{
                alert('请在钱包8.1以上版本运行');
            }
        }else{
            Tools.showAlert('请使用支付宝钱包扫一扫');
        }
    }


    common.share = {
        override: function (e, t, r) {
            var n = void 0 == r ? document.URL : r;
            wechat_share_override(e, t, n)
        },
        default_send: function () {
            var e = {
                    title: Config.C("share_text").default.title,
                    imgUrl: "http://assets.yqphh.com/assets/images/logo.jpg"
                },
                t = {
                    title: MasterConfig.C("shop_name") + "商城",
                    desc: Config.C("share_text").default.desc,
                    imgUrl: "http://assets.yqphh.com/assets/images/logo.jpg"
                };
            wechat_share_override(e, t, MasterConfig.C("baseMobileUrl"))
        }
    };

    //获取您正在参与的活动
    common.getJoiningActivity = function () {
        if ($('#rby-join').length == 0 || Cookie.get('hasLoad') == '1') return;
        Cookie.set('hasLoad', 1, 0);

        Ajax.custom({
            url: config.HOST_API + '/index/getPopList'
        }, function (response) {
            var data = response.body;
            if (data && data.length > 0) {
                Ajax.render('#rby-join-list', 'rby-join-list-tmpl', data);
                $('#rby-join').css({
                    'height': 310,
                    'margin-top': -310 / 2
                }).show();
                $('#rby-cover-bg').show();
            }
        })
    }

    //关闭参与活动界面
    function closeResult() {
        $('#rby-cover-bg').hide();
        $('#rby-join').hide();
    }

    $('.join-close').click(function (e) {
        closeResult();
    })

    $('#rby-cover-bg').click(function (e) {
        closeResult();
    })

    if (document.getElementById('rby-cover-bg')) {
        //取消遮罩层的默认滑动
        document.getElementById('rby-cover-bg').addEventListener('touchmove', function (e) {
            e.preventDefault();
        }, true);
    }

    //测试用，团购加入购物车
    common.abcLogin = function (id) {
        Ajax.custom({
            url: config.HOST_API + "/account/test_login",
            data: {
                id: id
            }
        }, function (response) {

            console.log(response);
            common.isLogining = false;
            var o = response.user;
            //Cookie.set("deviceBind", response.token, null);
            //Cookie.set("OpenId", o.open_id, null);
            Cookie.set("token", response.token, null);
            Cookie.set("UserSN", o.id, 0);
            Cookie.set("mobile", o.mobile, null);
            Tools.alert("UserSN: " + Cookie.get("UserSN"));
            location.href = 'person.html'
        }, function (e) {
            Tools.showAlert(e.message || '服务器异常');
            common.isLogining = false;
        })
    }

    /**
     * 口碑网页支付
     * @param tradeNO
     * @param sucFn
     * @param failFn
     */
    common.alipayPayForIsv = function (tradeNO,sucFn,failFn) {
        // 通过传入交易号唤起快捷调用方式(注意tradeNO大小写严格)
        AlipayJSBridge.call("tradePay", {
            tradeNO: tradeNO
        }, function (data) {
            if ("9000" == data.resultCode) {
                if(sucFn){
                    sucFn(data);
                } else if(failFn){
                    failFn(data);
                }
            }
        });
    }

    window.common = common;

    if ('FastClick' in window)
        FastClick.attach(document.body);

    if (Tools.isFruitdayAppBrowser()) {
        // 设置顶部导航
        $('.back .icon-morehome').hide().click(function (e) {
            e.preventDefault();
        });
        $('.back .icon-searchhomedel').hide().click(function (e) {
            e.preventDefault();
        });
    }
    if (Tools.isFruitdayiOSBrowser()) {
        $('body').addClass('has-app');
    }

})();
