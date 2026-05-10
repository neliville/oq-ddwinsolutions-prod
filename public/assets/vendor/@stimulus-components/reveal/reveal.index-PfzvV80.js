/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/@stimulus-components/reveal@5.0.0/dist/stimulus-reveal-controller.mjs
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import{Controller as s}from"@hotwired/stimulus";const t=class extends s{connect(){this.class=this.hasHiddenClass?this.hiddenClass:"hidden"}toggle(){this.itemTargets.forEach((s=>{s.classList.toggle(this.class)}))}show(){this.itemTargets.forEach((s=>{s.classList.remove(this.class)}))}hide(){this.itemTargets.forEach((s=>{s.classList.add(this.class)}))}};t.targets=["item"],t.classes=["hidden"];let e=t;export{e as default};
