const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
function setup({reduce=false,saveData=false,reject=false}={}) {
 const handlers={}, events={}, mediaEvents={}, classes=new Set();
 const label={};
 const toggle={hidden:true,querySelector:()=>label,setAttribute(k,v){this[k]=v},addEventListener(k,v){handlers[k]=v}};
 const video={paused:true,ended:false,dataset:{src:'loop.mp4'},classList:{add:v=>classes.add(v),remove:v=>classes.delete(v)},hasAttribute:()=>!!video.src,load(){},
  async play(){if(reject)throw Error('autoplay blocked');this.paused=false;mediaEvents.playing()},
  pause(){this.paused=true;mediaEvents.pause()},addEventListener(k,v){mediaEvents[k]=v}};
 const reduced={matches:reduce,addEventListener(k,v){this.change=v}};
 const document={hidden:false,querySelectorAll:()=>[],getElementById:id=>id==='hero-video'?video:toggle,addEventListener(k,v){events[k]=v}};
 const window={matchMedia:()=>reduced,siteI18n:{t:s=>s}};
 vm.runInNewContext(fs.readFileSync('js/landing.js','utf8'),{window,document,navigator:{connection:{saveData}}});
 return {video,toggle,label,reduced,document,handlers,events,mediaEvents,classes};
}
(async()=>{
 let s=setup();await Promise.resolve();
 assert.equal(s.video.paused,false);assert.equal(s.toggle['aria-pressed'],'true');
 s.handlers.click();assert(s.video.paused);
 s.handlers.click();await Promise.resolve();assert(!s.video.paused);
 s.document.hidden=true;s.events.visibilitychange();assert(s.video.paused);
 s.document.hidden=false;s.events.visibilitychange();await Promise.resolve();assert(!s.video.paused);
 s.reduced.matches=true;s.reduced.change();assert(s.video.paused);
 for(const options of [{reduce:true},{saveData:true}]){
  const x=setup(options);assert.equal(x.video.src,undefined);assert(x.video.paused);
  x.handlers.click();await Promise.resolve();assert(!x.video.paused);
 }
 s=setup({reject:true});await Promise.resolve();assert(s.video.paused);assert(!s.toggle.hidden);
 s.mediaEvents.error();assert(s.toggle.hidden);assert(!s.classes.has('is-playing'));
 console.log('PASS: motion controls, reduced motion, data saver, visibility, autoplay rejection and failed media.');
})().catch(e=>{console.error(e);process.exit(1)});
