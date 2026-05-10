/**
 * Bundled by jsDelivr using Rollup v2.79.2 and Terser v5.39.0.
 * Original file: /npm/@stimulus-components/sortable@5.0.3/dist/stimulus-sortable.mjs
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import{Controller as t}from"@hotwired/stimulus";import e from"sortablejs";import{FetchRequest as a}from"@rails/request.js";const s=class extends t{initialize(){this.onUpdate=this.onUpdate.bind(this)}connect(){this.sortable=new e(this.element,{...this.defaultOptions,...this.options})}disconnect(){this.sortable.destroy(),this.sortable=void 0}async onUpdate({item:t,newIndex:e}){if(!t.dataset.sortableUpdateUrl)return;const s=this.resourceNameValue?`${this.resourceNameValue}[${this.paramNameValue}]`:this.paramNameValue,i=new FormData;return i.append(s,e+1),await new a(this.methodValue,t.dataset.sortableUpdateUrl,{body:i,responseKind:this.responseKindValue}).perform()}get options(){return{animation:this.animationValue||this.defaultOptions.animation||150,handle:this.handleValue||this.defaultOptions.handle||void 0,onUpdate:this.onUpdate}}get defaultOptions(){return{}}};s.values={resourceName:String,paramName:{type:String,default:"position"},responseKind:{type:String,default:"html"},animation:Number,handle:String,method:{type:String,default:"patch"}};let i=s;export{i as default};
