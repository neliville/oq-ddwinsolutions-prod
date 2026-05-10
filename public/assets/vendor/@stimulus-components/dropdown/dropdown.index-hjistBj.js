/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/@stimulus-components/dropdown@3.0.0/dist/stimulus-dropdown.mjs
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import{Controller as t}from"@hotwired/stimulus";import{useTransition as e}from"stimulus-use";const s=class extends t{connect(){e(this,{element:this.menuTarget})}toggle(){this.toggleTransition()}hide(t){!this.element.contains(t.target)&&!this.menuTarget.classList.contains("hidden")&&this.leave()}};s.targets=["menu"];let i=s;export{i as default};
