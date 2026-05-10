/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/zustand@4.5.7/esm/traditional.mjs
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import t from"react";import e from"use-sync-external-store/shim/with-selector.js";import{createStore as r}from"zustand/vanilla";const{useDebugValue:n}=t,{useSyncExternalStoreWithSelector:s}=e,o=t=>t;function a(t,e=o,r){const a=s(t.subscribe,t.getState,t.getServerState||t.getInitialState,e,r);return n(a),a}const m=(t,e)=>{const n=r(t),s=(t,r=e)=>a(n,t,r);return Object.assign(s,n),s},c=(t,e)=>t?m(t,e):m;export{c as createWithEqualityFn,a as useStoreWithEqualityFn};export default null;
