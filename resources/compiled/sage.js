if("undefined"==typeof _sageInitialized){const a={t:[],i:-(_sageInitialized=1),o:[],u:(n,t=100)=>{let i;return(...e)=>{clearTimeout(i),i=setTimeout(()=>n(...e),t)}},l:function(){let t=a.o.length;for(;t--;){let e=a.o[t],n=e.g;(window.scrollY<=n._||window.scrollY>=n.v)&&(a.o.splice(t,1),n.remove(),e.g=null)}var e=document.querySelectorAll("._sage");for(t=e.length;t--;){const n=e[t];if(!a.p(n))return;let o=0;n.querySelectorAll("._sage-clone").forEach(function(e){o+=e.offsetHeight}),n.querySelectorAll("dt._sage-parent._sage-show:not(._sage-clone)").forEach(function(e){if(!a.o.includes(e)){const t=e.nextElementSibling;if(a.p(t)){const i=e.cloneNode(!0);var n=t.getBoundingClientRect();i._=n.top+window.scrollY,i.v=n.bottom+window.scrollY,i.style.width=n.width+"px",i.style.top=o+"px",i.style.position="fixed",i.style.opacity=.9,i.classList.add("_sage-clone"),o+=e.offsetHeight,e.g=i,a.o.push(e),e.after(i)}}});break}},p:function(e){e=e.getBoundingClientRect();return 0!==e.height&&(!(e.top>=window.innerHeight)&&(!(0<e.top)&&!(e.bottom<25)))},m:function(e){const n=window.getSelection(),t=document.createRange();t.selectNodeContents(e),n.removeAllRanges(),n.addRange(t)},h:function(e,n){Array.prototype.slice.call(document.querySelectorAll(e),0).forEach(n)},T:function(e,n="_sage-show"){return!!e.classList&&e.classList.contains(n)},k:function(e,n="_sage-show"){e.classList.add(n)},A:function(e,n="_sage-show"){return e.classList.remove(n),e},next:function(e){for(;(e=e.nextElementSibling)&&"DD"!==e.tagName;);return e},toggle:function(e,n){(n=void 0===n?a.T(e):n)?a.A(e):a.k(e);let t=a.next(e);t&&1===t.childElementCount&&(t=t.children[0])&&a.T(t,"_sage-parent")&&a.toggle(t,n)},I:function(e,n){const t=a.next(e),i=t.getElementsByClassName("_sage-parent");let o=i.length;for(void 0===n&&(n=a.T(e));o--;)a.toggle(i[o],n);a.toggle(e,n)},N:function(e){var n=document.getElementsByClassName("_sage-parent");let t=n.length;for(;t--;)e?a.k(n[t]):a.A(n[t])},P:function(){document.querySelectorAll("._sage-trace>dd>._sage-tabs>._sage-active-tab").forEach(function(e){e=e.nextSibling;e&&a.C(e)})},C:function(e){let n,t=e,i=0;for(a.A(e.parentNode.getElementsByClassName("_sage-active-tab")[0],"_sage-active-tab"),a.k(e,"_sage-active-tab");t=t.previousSibling;)1===t.nodeType&&i++;n=e.parentNode.nextSibling.childNodes;for(let e=0;e<n.length;e++)n[e].style.display=e===i?"block":"none"},D:function(n){for(;;){if(a.T(n,"_sage-clone")){let e=n.offsetHeight;return"0px"!==n.style.top&&(e*=2),window.scroll(0,n._-e),!1}if(!(n=n.parentNode)||a.T(n,"_sage"))break}return!!n},F:function(){a.t=[],a.h("._sage nav, ._sage-tabs>li:not(._sage-active-tab)",function(e){0===e.offsetWidth&&0===e.offsetHeight||a.t.push(e)})},tag:function(e){return"<"+e+">"},O:function(e){let n;(n=window.open())&&(n.document.open(),n.document.write(a.tag("html")+a.tag("head")+"<title>Sage ☯ ("+(new Date).toISOString()+")</title>"+a.tag('meta charset="utf-8"')+document.getElementsByClassName("_sage-js")[0].outerHTML+document.getElementsByClassName("_sage-css")[0].outerHTML+a.tag("/head")+a.tag("body")+'<input style="width: 100%" placeholder="Take some notes!"><div class="_sage">'+e.parentNode.outerHTML+"</div>"+a.tag("/body")),n.document.close())},R:function(e,t,n){const i=e.tBodies[0],o=new Intl.Collator(void 0,{numeric:!0,sensitivity:"base"}),a=void 0===n.V?1:n.V;n.V=-1*a,[].slice.call(e.tBodies[0].rows).sort(function(e,n){return a*o.compare(e.cells[t].textContent,n.cells[t].textContent)}).forEach(function(e){i.appendChild(e)})},S:{L:function(e){var n="_sage-focused",t=document.querySelector("."+n);if(t&&a.A(t,n),-1!==e){t=a.t[e];a.k(t,n);const i=function(e){return e.offsetTop+(e.offsetParent?i(e.offsetParent):0)};n=i(t)-window.innerHeight/2;window.scrollTo(0,n)}a.i=e},j:function(e,n){return e?--n<0&&(n=a.t.length-1):++n>=a.t.length&&(n=0),a.S.L(n),!1}}};window.addEventListener("click",function(e){let n=e.target,t=n.tagName;if(a.D(n)){if("DFN"===t)a.m(n),n=n.parentNode;else if("VAR"===t)n=n.parentNode,t=n.tagName;else if("TH"===t)return e.ctrlKey||a.R(n.parentNode.parentNode.parentNode,n.cellIndex,n),!1;if("LI"===t&&a.T(n.parentNode,"_sage-tabs"))return a.T(n,"_sage-active-tab")||(a.C(n),-1!==a.i&&a.F()),!1;if("NAV"===t)return"FOOTER"===n.parentNode.tagName?(n=n.parentNode,a.toggle(n)):setTimeout(function(){0<parseInt(n.B,10)?n.B--:(a.I(n.parentNode),-1!==a.i&&a.F())},300),e.stopPropagation(),!1;if(a.T(n,"_sage-parent"))return a.toggle(n),-1!==a.i&&a.F(),!1;if(a.T(n,"_sage-ide-link"))return e.preventDefault(),fetch(n.href),!1;if(a.T(n,"_sage-popup-trigger")){let e=n.parentNode;if("FOOTER"===e.tagName)e=e.previousSibling;else for(;e&&!a.T(e,"_sage-parent");)e=e.parentNode;a.O(e)}else"PRE"===t&&3===e.detail&&a.m(n)}},!1),window.addEventListener("dblclick",function(e){const n=e.target;a.D(n)&&"NAV"===n.tagName&&(n.B=2,a.N(a.T(n)),-1!==a.i&&a.F(),e.stopPropagation())},!1),window.onkeydown=function(n){var t=n.keyCode;let i=a.i;if(70===t&&n.ctrlKey)return a.N(!0),void a.P(!0);if(!(["INPUT","TEXTAREA"].includes(n.target.tagName)||n.altKey||n.ctrlKey))if(9===t)a.S.L(-1);else{if(68===t)return-1===i?(a.F(),a.S.j(!1,i)):(a.S.L(-1),!1);if(-1!==i){if(38===t)return a.S.j(!0,i);if(40===t)return a.S.j(!1,i);let e=a.t[i];if("LI"===e.tagName){if(32===t||13===t)return a.C(e),a.F(),a.S.j(!0,i);if(39===t)return a.S.j(!1,i);if(37===t)return a.S.j(!0,i)}if("FOOTER"===(e=e.parentNode).tagName&&(32===t||13===t))return a.toggle(e),!1;if(32===t||13===t)return a.toggle(e),a.F(),!1;if(39===t||37===t){n=37===t;if(a.T(e))a.I(e,n);else{if(n){for(;(e=e.parentNode)&&"DD"!==e.tagName;);if(e){e=e.previousElementSibling,i=-1;for(var o=e.querySelector("nav");o!==a.t[++i];);a.S.L(i)}else e=a.t[i].parentNode}a.toggle(e,n)}return a.F(),!1}}}},window.addEventListener("load",function(){const e=Array.prototype.slice.call(document.querySelectorAll("._sage-microtime"),0);let t=1/0,i=-1/0;e.forEach(function(e){e=parseFloat(e.innerHTML);t>e&&(t=e),i<e&&(i=e)}),e.forEach(function(e){var n=1-(parseFloat(e.innerHTML)-t)/(i-t);e.style.background="hsl("+Math.round(120*n)+",60%,70%)"})}),window.addEventListener("scroll",a.u(a.l))}