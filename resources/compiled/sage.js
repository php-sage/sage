if("undefined"==typeof _sageInitialized){const r={t:[],i:-(_sageInitialized=1),o:function(e){const n=window.getSelection(),t=document.createRange();t.selectNodeContents(e),n.removeAllRanges(),n.addRange(t)},u:function(e,n){Array.prototype.slice.call(document.querySelectorAll(e),0).forEach(n)},l:function(e,n){return!!e.classList&&e.classList.contains(n=void 0===n?"_sage-show":n)},g:function(e,n){e.classList.add(n=void 0===n?"_sage-show":n)},v:function(e,n){return e.classList.remove(n=void 0===n?"_sage-show":n),e},next:function(e){for(;"dd"!==(e=e.nextElementSibling).nodeName.toLowerCase(););return e},toggle:function(e,n){var t=r.next(e);(n=void 0===n?r.l(e):n)?r.v(e):r.g(e),1===t.childNodes.length&&(t=t.childNodes[0].childNodes[0])&&r.l(t,"_sage-parent")&&r.toggle(t,n)},_:function(e,n){const t=r.next(e),i=t.getElementsByClassName("_sage-parent");let o=i.length;for(void 0===n&&(n=r.l(e));o--;)r.toggle(i[o],n);r.toggle(e,n)},p:function(e){var n=document.getElementsByClassName("_sage-parent");let t=n.length;for(var i=r.l(e.parentNode);t--;)r.toggle(n[t],i)},h:function(e){let n,t=e,i=0;for(e.parentNode.getElementsByClassName("_sage-active-tab")[0].className="",e.className="_sage-active-tab";t=t.previousSibling;)1===t.nodeType&&i++;n=e.parentNode.nextSibling.childNodes;for(let e=0;e<n.length;e++)e===i?(n[e].style.display="block",1===n[e].childNodes.length&&(t=n[e].childNodes[0].childNodes[0],r.l(t,"_sage-parent")&&r.toggle(t,!1))):n[e].style.display="none"},m:function(e){for(;(e=e.parentNode)&&!r.l(e,"_sage"););return!!e},k:function(){r.t=[],r.u("._sage nav, ._sage-tabs>li:not(._sage-active-tab)",function(e){0===e.offsetWidth&&0===e.offsetHeight||r.t.push(e)})},tag:function(e){return"<"+e+">"},C:function(e){let n;(n=window.open())&&(n.document.open(),n.document.write(r.tag("html")+r.tag("head")+"<title>Sage ☯ ("+(new Date).toISOString()+")</title>"+r.tag('meta charset="utf-8"')+document.getElementsByClassName("-_sage-js")[0].outerHTML+document.getElementsByClassName("-_sage-css")[0].outerHTML+r.tag("/head")+r.tag("body")+'<input style="width: 100%" placeholder="Take some notes!"><div class="_sage">'+e.parentNode.outerHTML+"</div>"+r.tag("/body")),n.document.close())},T:function(e,t,n){const i=e.tBodies[0],o=new Intl.Collator(void 0,{numeric:!0,sensitivity:"base"}),r=void 0===n.I?1:n.I;n.I=-1*r,[].slice.call(e.tBodies[0].rows).sort(function(e,n){return r*o.compare(e.cells[t].textContent,n.cells[t].textContent)}).forEach(function(e){i.appendChild(e)})},A:{P:function(e){var n="_sage-focused",t=document.querySelector("."+n);if(t&&r.v(t,n),-1!==e){t=r.t[e];r.g(t,n);const i=function(e){return e.offsetTop+(e.offsetParent?i(e.offsetParent):0)};t=i(t)-window.innerHeight/2;window.scrollTo(0,t)}r.i=e},F:function(e,n){return e?--n<0&&(n=r.t.length-1):++n>=r.t.length&&(n=0),r.A.P(n),!1}}};window.addEventListener("click",function(e){let n=e.target,t=n.nodeName.toLowerCase();if(r.m(n)){if("dfn"===t)r.o(n),n=n.parentNode;else if("var"===t)n=n.parentNode,t=n.nodeName.toLowerCase();else if("th"===t)return e.ctrlKey||r.T(n.parentNode.parentNode.parentNode,n.cellIndex,n),!1;if("li"===t&&"_sage-tabs"===n.parentNode.className)return"_sage-active-tab"!==n.className&&(r.h(n),-1!==r.i&&r.k()),!1;if("nav"===t)return"footer"===n.parentNode.nodeName.toLowerCase()?(n=n.parentNode,r.l(n)?r.v(n):r.g(n)):setTimeout(function(){0<parseInt(n.M,10)?n.M--:(r._(n.parentNode),-1!==r.i&&r.k())},300),e.stopPropagation(),!1;if(r.l(n,"_sage-parent"))return r.toggle(n),-1!==r.i&&r.k(),!1;if(r.l(n,"_sage-ide-link")){e.preventDefault();const i=new XMLHttpRequest;return i.open("GET",n.href),i.send(null),!1}if(r.l(n,"_sage-popup-trigger")){let e=n.parentNode;if("footer"===e.nodeName.toLowerCase())e=e.previousSibling;else for(;e&&!r.l(e,"_sage-parent");)e=e.parentNode;r.C(e)}else"pre"===t&&3===e.detail&&r.o(n)}},!1),window.addEventListener("dblclick",function(e){const n=e.target;r.m(n)&&"nav"===n.nodeName.toLowerCase()&&(n.M=2,r.p(n),-1!==r.i&&r.k(),e.stopPropagation())},!1),window.onkeydown=function(t){if(t.target===document.body&&!t.altKey&&!t.ctrlKey){var i=t.keyCode,t=t.shiftKey;let n=r.i;if(68===i)return-1===n?(r.k(),r.A.F(!1,n)):(r.A.P(-1),!1);if(-1!==n){if(9===i)return r.A.F(t,n);if(38===i)return r.A.F(!0,n);if(40===i)return r.A.F(!1,n);let e=r.t[n];if("li"===e.nodeName.toLowerCase()){if(32===i||13===i)return r.h(e),r.k(),r.A.F(!0,n);if(39===i)return r.A.F(!1,n);if(37===i)return r.A.F(!0,n)}if(e=e.parentNode,32===i||13===i)return r.toggle(e),r.k(),!1;if(39===i||37===i){i=37===i;if(r.l(e))r._(e,i);else{if(i){for(;e=e.parentNode,e&&"dd"!==e.nodeName.toLowerCase(););if(e){e=e.previousElementSibling,n=-1;for(var o=e.querySelector("nav");o!==r.t[++n];);r.A.P(n)}else e=r.t[n].parentNode}r.toggle(e,i)}return r.k(),!1}}}},window.addEventListener("load",function(e){const r=Array.prototype.slice.call(document.querySelectorAll("._sage-microtime"),0);r.forEach(function(e){var n=parseFloat(e.innerHTML);let t=1/0,i=-1/0,o;r.forEach(function(e){e=parseFloat(e.innerHTML);t>e&&(t=e),i<e&&(i=e)}),o=1-(n-t)/(i-t),e.style.background="hsl("+Math.round(120*o)+",60%,70%)"})})}