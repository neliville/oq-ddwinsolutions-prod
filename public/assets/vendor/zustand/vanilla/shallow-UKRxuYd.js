/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/zustand@5.0.13/esm/vanilla/shallow.mjs
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
const e=e=>Symbol.iterator in e,t=e=>"entries"in e,n=(e,t)=>{const n=e instanceof Map?e:new Map(e.entries()),r=t instanceof Map?t:new Map(t.entries());if(n.size!==r.size)return!1;for(const[e,t]of n)if(!r.has(e)||!Object.is(t,r.get(e)))return!1;return!0};function r(r,o){return!!Object.is(r,o)||"object"==typeof r&&null!==r&&"object"==typeof o&&null!==o&&(Object.getPrototypeOf(r)===Object.getPrototypeOf(o)&&(e(r)&&e(o)?t(r)&&t(o)?n(r,o):((e,t)=>{const n=e[Symbol.iterator](),r=t[Symbol.iterator]();let o=n.next(),i=r.next();for(;!o.done&&!i.done;){if(!Object.is(o.value,i.value))return!1;o=n.next(),i=r.next()}return!!o.done&&!!i.done})(r,o):n({entries:()=>Object.entries(r)},{entries:()=>Object.entries(o)})))}export{r as shallow};export default null;
