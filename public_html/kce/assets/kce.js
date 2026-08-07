(()=>{
 const form=document.querySelector('#chatForm'),q=document.querySelector('#question'),messages=document.querySelector('#messages'),intro=document.querySelector('#intro'),send=document.querySelector('#send');let conversation='',submitting=false;
 const esc=s=>{const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML};
 const inline=source=>{
  const links=[];let s=source;
  const saveLink=(label,url)=>{const key=`@@KCELINK${links.length}@@`;links.push(`<a href="${url}" target="_blank" rel="noopener noreferrer nofollow">${label}</a>`);return key};
  s=s.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/gi,(_,label,url)=>saveLink(label,url));
  s=s.replace(/(^|[\s(])(https?:\/\/[^\s<]+)/gi,(all,prefix,url)=>{let trailing='';while(/[.,;:!?)]$/.test(url)){trailing=url.slice(-1)+trailing;url=url.slice(0,-1)}return prefix+saveLink(url,url)+trailing});
  s=s.replace(/`([^`]+)`/g,'<code>$1</code>').replace(/\*\*([^*]+)\*\*/g,'<strong>$1</strong>').replace(/\*([^*]+)\*/g,'<em>$1</em>');
  links.forEach((link,index)=>{s=s.replace(`@@KCELINK${index}@@`,link)});return s;
 };
 function markdown(source){
  const safe=esc(source).replace(/\r/g,''),lines=safe.split('\n');let html='',paragraph=[],list=null,code=false,codeLines=[];
  const flushP=()=>{if(paragraph.length){html+='<p>'+inline(paragraph.join(' '))+'</p>';paragraph=[]}};
  const flushList=()=>{if(list){html+=`</${list}>`;list=null}};
  const cells=line=>line.trim().replace(/^\|/,'').replace(/\|$/,'').split('|').map(cell=>cell.trim());
  const tableDivider=line=>/^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?\s*$/.test(line);
  for(let i=0;i<lines.length;i++){
   const line=lines[i].trimEnd();
   if(line.trim().startsWith('```')){flushP();flushList();if(code){html+='<pre><code>'+codeLines.join('\n')+'</code></pre>';codeLines=[];code=false}else code=true;continue}
   if(code){codeLines.push(line);continue}
   let m;if(!line.trim()){flushP();flushList();continue}
   if(line.includes('|')&&i+1<lines.length&&tableDivider(lines[i+1])){flushP();flushList();const headers=cells(line);i+=2;const rows=[];while(i<lines.length&&lines[i].includes('|')&&lines[i].trim()!==''){rows.push(cells(lines[i]));i++}i--;html+='<div class="markdown-table-wrap"><table><thead><tr>'+headers.map(cell=>`<th>${inline(cell)}</th>`).join('')+'</tr></thead><tbody>'+rows.map(row=>'<tr>'+headers.map((_,column)=>`<td>${inline(row[column]||'')}</td>`).join('')+'</tr>').join('')+'</tbody></table></div>';continue}
   if(/^(?:-{3,}|\*{3,}|_{3,})$/.test(line.trim())){flushP();flushList();html+='<hr>';continue}
   if((m=line.match(/^(#{1,4})\s+(.+)$/))){flushP();flushList();const level=Math.min(4,m[1].length+1);html+=`<h${level}>${inline(m[2])}</h${level}>`;continue}
   if((m=line.match(/^[-*]\s+(.+)$/))){flushP();if(list!=='ul'){flushList();html+='<ul>';list='ul'}html+='<li>'+inline(m[1])+'</li>';continue}
   if((m=line.match(/^\d+[.)]\s+(.+)$/))){flushP();if(list!=='ol'){flushList();html+='<ol>';list='ol'}html+='<li>'+inline(m[1])+'</li>';continue}
   if((m=line.match(/^&gt;\s?(.+)$/))){flushP();flushList();html+='<blockquote>'+inline(m[1])+'</blockquote>';continue}
   paragraph.push(line.trim());
  }
  if(codeLines.length)html+='<pre><code>'+codeLines.join('\n')+'</code></pre>';flushP();flushList();return html;
 }
 function bubble(role,text,target=messages,label='JAWABAN AI'){const el=document.createElement('div');el.className='bubble '+role;el.innerHTML=role==='ai'?`<span class="ai-label">${esc(label)}</span><div class="ai-content">${markdown(text)}</div>`:esc(text);target.append(el);messages.scrollTop=messages.scrollHeight;return el}
 function answerGroup(index){const group=document.createElement('section');group.className='answer-group';group.dataset.answerIndex=index;messages.append(group);return group}
 function modelLabel(index){return `JAWABAN AI ${index+1}`}
 function articles(items,target=messages){if(!items.length)return;const wrap=document.createElement('section');wrap.className='article-results';wrap.innerHTML='<div class="article-heading">ARTIKEL RELEVAN DARI KUMPULBLOGGER</div><div class="article-grid"></div>';const grid=wrap.querySelector('.article-grid');items.forEach(a=>{const card=document.createElement('article');card.innerHTML=`<span>ARTIKEL EDITORIAL</span><h3>${esc(a.title)}</h3><p>${esc(a.excerpt)}</p><a href="${esc(a.url)}" target="_blank" rel="noopener">Baca artikel →</a>`;grid.append(card)});target.append(wrap)}
 function track(ad){fetch('api/event.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:ad.id,type:'impression',conversation_id:conversation,token:ad.impression_token}),keepalive:true}).catch(()=>{})}
 function sponsors(items,countImpression=true,target=messages){if(!items.length)return;const wrap=document.createElement('section');wrap.className='sponsored-wrap';wrap.innerHTML='<div class="sponsored-heading">SPONSORED CONTENT · IKLAN</div>';items.forEach(ad=>{const card=document.createElement('article');card.className='sponsor';const url=`api/event.php?id=${ad.id}&type=click&conversation_id=${encodeURIComponent(conversation)}&token=${encodeURIComponent(ad.impression_token)}`;card.innerHTML=`<div><h3>${esc(ad.title)}</h3><p>${esc(ad.body)}</p><a href="${url}" target="_blank" rel="noopener sponsored">Kunjungi sponsor →</a></div>${ad.banner_url?`<img src="${esc(ad.banner_url)}" alt="Mini banner ${esc(ad.title)}" loading="lazy">`:''}`;wrap.append(card);if(countImpression)track(ad)});target.append(wrap)}
 async function captcha(){if(typeof grecaptcha==='undefined')throw new Error('Verifikasi manusia belum siap. Muat ulang halaman.');const script=document.querySelector('script[src*="recaptcha/api.js"]'),key=new URL(script.src).searchParams.get('render');return new Promise((resolve,reject)=>grecaptcha.ready(()=>grecaptcha.execute(key,{action:'kce_chat'}).then(resolve).catch(reject)))}
 function setBusy(busy){submitting=busy;send.disabled=busy;send.classList.toggle('is-loading',busy);form.setAttribute('aria-busy',String(busy));q.setAttribute('aria-disabled',String(busy));}
 async function submit(){
  const text=q.value.trim();if(!text||submitting)return;intro.style.display='none';messages.classList.add('active');bubble('user',text);q.value='';setBusy(true);const responses={};let receivedAnswers=0;
  const ensureResponse=index=>{if(responses[index])return responses[index];const group=answerGroup(index),output=bubble('ai',index===0?'Menghubungkan ke sumber jawaban…':'Menunggu Jawaban AI 1 selesai…',group,modelLabel(index));if(index===0)output.classList.add('streaming');responses[index]={group,output,answer:'',failed:false};return responses[index]};
  try{
   const token=await captcha(),response=await fetch('api/stream.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'text/event-stream'},body:JSON.stringify({message:text,captcha_token:token})});if(!response.ok||!response.body)throw new Error('Streaming tidak tersedia pada server.');
    const reader=response.body.getReader(),decoder=new TextDecoder();let buffer='';
    while(true){const {value,done}=await reader.read();buffer+=decoder.decode(value||new Uint8Array(),{stream:!done});const events=buffer.split('\n\n');buffer=events.pop()||'';for(const packet of events){const dataLine=packet.split('\n').find(line=>line.startsWith('data:'));if(!dataLine)continue;let event;try{event=JSON.parse(dataLine.slice(5).trim())}catch(e){continue}if(event.type==='meta'){conversation=event.conversation_id;for(let index=0;index<Number(event.answer_count||2);index++)ensureResponse(index)}if(event.type==='answer_start'){const item=ensureResponse(Number(event.answer_index));item.output.classList.add('streaming');item.output.innerHTML=`<span class="ai-label"><i></i> ${esc(modelLabel(Number(event.answer_index)))} · MENGHUBUNGKAN</span><div class="ai-content"><p>Menyiapkan jawaban…</p></div>`}if(event.type==='delta'){const item=ensureResponse(Number(event.answer_index));if(item.answer==='')receivedAnswers++;item.answer+=event.content;item.output.innerHTML=`<span class="ai-label"><i></i> ${esc(modelLabel(Number(event.answer_index)))} · LIVE</span><div class="ai-content">${markdown(item.answer)}</div>`;messages.scrollTop=messages.scrollHeight}if(event.type==='answer_done'){const item=ensureResponse(Number(event.answer_index));item.output.classList.remove('streaming');if(item.answer)item.output.querySelector('.ai-label').innerHTML=esc(modelLabel(Number(event.answer_index)))}if(event.type==='model_error'){const item=ensureResponse(Number(event.answer_index));item.failed=true;item.output.classList.remove('streaming');item.output.innerHTML='<span class="ai-label error-label">'+esc(modelLabel(Number(event.answer_index)))+' · GANGGUAN</span><div class="ai-content"><p>'+esc(event.message)+'</p></div>'}if(event.type==='related'){const item=responses[Number(event.answer_index)];if(item){articles(event.articles||[],item.group);sponsors(event.sponsored||[],true,item.group)}}if(event.type==='error')throw new Error(event.message+(event.code?' ('+event.code+')':''));}if(done)break}
    if(!receivedAnswers)throw new Error('Kedua sumber AI tidak menghasilkan jawaban.');Object.values(responses).forEach(item=>{item.output.classList.remove('streaming');if(!item.failed&&item.answer)item.output.querySelector('.ai-label').innerHTML=esc(modelLabel(Number(item.group.dataset.answerIndex)))});
   }catch(error){const item=responses[0]||ensureResponse(0);item.output.innerHTML='<span class="ai-label error-label">GANGGUAN</span><div class="ai-content"><p>'+esc(error.message)+'</p></div>'}finally{Object.values(responses).forEach(item=>item.output.classList.remove('streaming'));setBusy(false);q.focus();messages.scrollTop=messages.scrollHeight}
 }
 async function loadHistory(){try{const r=await fetch('api/history.php',{credentials:'same-origin'}),data=await r.json();conversation=data.conversation_id||conversation;if(!data.messages?.length)return;intro.style.display='none';messages.classList.add('active');let answerIndex=0;data.messages.forEach(m=>{if(m.role!=='assistant'){bubble('user',m.content);answerIndex=0;return}const group=answerGroup(answerIndex);bubble('ai',m.content,group,modelLabel(answerIndex));if(m.articles?.length)articles(m.articles,group);if(m.sponsored?.length)sponsors(m.sponsored,false,group);answerIndex++})}catch(e){}}
 form.addEventListener('submit',e=>{e.preventDefault();submit()});q.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();submit()}});document.querySelectorAll('.prompts button').forEach(b=>b.onclick=()=>{if(submitting)return;q.value=b.textContent;submit()});
 async function init(){await loadHistory();const initial=(form.dataset.initialQuestion||'').trim();if(initial){q.value=initial;history.replaceState({},'',location.pathname);await submit()}}
 init();
})();
