/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/zustand@5.0.13/esm/react.mjs
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import t from"react";import{createStore as e}from"zustand/vanilla";const n=t=>t;function a(e,a=n){const s=t.useSyncExternalStore(e.subscribe,t.useCallback((()=>a(e.getState())),[e,a]),t.useCallback((()=>a(e.getInitialState())),[e,a]));return t.useDebugValue(s),s}const s=t=>{const n=e(t),s=t=>a(n,t);return Object.assign(s,n),s},r=t=>t?s(t):s;export{r as create,a as useStore};export default null;
