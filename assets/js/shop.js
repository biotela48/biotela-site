(function(){
  var KEY='bt_cart_v1', cat=null;
  function load(){try{return JSON.parse(localStorage.getItem(KEY))||[]}catch(e){return[]}}
  function save(c){try{localStorage.setItem(KEY,JSON.stringify(c))}catch(e){} fab()}
  function goal(n){try{ym(51638546,'reachGoal',n)}catch(e){}}
  function esc(s){return String(s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
  function rub(n){return n.toLocaleString('ru-RU')+' ₽'}
  function getCat(cb){
    if(cat)return cb(cat);
    fetch('/shop/catalog.json',{cache:'no-cache'}).then(function(r){return r.json()}).then(function(j){
      var m={},p={};j.products.forEach(function(x){p[x.slug]=x;x.variants.forEach(function(v){m[v.id]={stock:(v.stock===undefined?null:v.stock),id:v.id,name:x.name+', '+v.label,label:v.label,price:v.price,sku:v.sku||v.ozon_id||null,slug:x.slug}})});
      cat={map:m,bySlug:p};cb(cat)}).catch(function(){});
  }
  function count(){return load().reduce(function(a,i){return a+i.qty},0)}
  function fab(){
    var n=count(),onCart=!!document.getElementById('bt-cart');
    var bar=document.querySelector('.new-navbar-container');
    if(bar){
      var h=document.getElementById('bt-hdr');
      if(!h){h=document.createElement('a');h.id='bt-hdr';h.className='bt-hdr';h.href='/korzina';h.setAttribute('aria-label','Корзина');
        var mb=bar.querySelector('.menu-button-2');bar.insertBefore(h,mb||null)}
      h.innerHTML='<span class="ic">🛒</span><span class="lb">Корзина</span><i'+(n?'':' class="zero"')+'>'+n+'</i>';
      return;
    }
    var a=document.getElementById('bt-fab');
    if(!a){a=document.createElement('a');a.id='bt-fab';a.className='bt-fab';a.href='/korzina';document.body.appendChild(a)}
    a.innerHTML='🛒 Корзина <i>'+n+'</i>';
    a.style.display=(n>0&&!onCart)?'inline-flex':'none';
  }
  function add(id,q){var c=load(),f=c.filter(function(i){return i.id===id})[0];if(f)f.qty=Math.min(50,f.qty+q);else c.push({id:id,qty:q});save(c)}

  function buybox(){
    var slug=location.pathname.replace(/^\/|\.html$|\/$/g,'');
    var host=document.querySelector('.market-wrap');
    if(!host)return;
    getCat(function(C){
      var p=C.bySlug[slug];if(!p)return;
      var sel=(p.variants.filter(function(x){return x.stock!==0})[0]||p.variants[0]).id,qty=1;
      var box=document.createElement('div');box.className='bt-buybox';
      function draw(){
        var v=C.map[sel];
        box.innerHTML='<div class="bt-bb-title">Купить на сайте</div><div class="bt-vars">'+p.variants.map(function(x){
          return '<button type="button" class="bt-var'+(x.id===sel?' on':'')+'" data-id="'+x.id+'"><span>'+esc(x.label)+'</span><b>'+(x.stock===0?'нет в наличии':rub(x.price))+'</b></button>'}).join('')+'</div>'
          +'<div class="bt-row"><div class="bt-qty"><button type="button" data-d="-1" aria-label="Меньше">−</button><span>'+qty+'</span><button type="button" data-d="1" aria-label="Больше">+</button></div>'
          +(v.stock===0?'<button type="button" class="bt-buy" disabled style="background:#b8c4d2;cursor:default">Нет в наличии</button>':'<button type="button" class="bt-buy">В корзину · '+rub(v.price*qty)+'</button>')+'</div>'
          +'<div class="bt-added">Добавлено. <a href="/korzina">Перейти в корзину</a></div>'
          +'<div class="bt-note">Оплата картой, СБП или Ozon Картой. Доставка Ozon. <a href="/dostavka-i-oplata">Подробнее</a></div>';
      }
      box.addEventListener('click',function(e){
        var t=e.target.closest('button');if(!t)return;
        if(t.classList.contains('bt-var')){sel=t.getAttribute('data-id');draw()}
        else if(t.hasAttribute('data-d')){qty=Math.max(1,Math.min(20,C.map[sel].stock>0?C.map[sel].stock:20,qty+parseInt(t.getAttribute('data-d'))));draw()}
        else if(t.classList.contains('bt-buy')&&!t.disabled){add(sel,qty);goal('add_to_cart');draw();box.querySelector('.bt-added').style.display='block'}
      });
      draw();host.parentNode.insertBefore(box,host);
    });
  }

  var T0=Date.now();
  function cartPage(){
    var root=document.getElementById('bt-cart');if(!root)return;
    getCat(function(C){
      function render(){
        var c=load().filter(function(i){return C.map[i.id]&&C.map[i.id].stock!==0});
        if(!c.length){root.innerHTML='<div class="bt-empty">Корзина пуста. <a href="/">Выбрать товары</a></div>';return}
        var total=0,allSku=true;
        var rows=c.map(function(i){var v=C.map[i.id];total+=v.price*i.qty;if(!v.sku)allSku=false;
          return '<div class="bt-item"><div class="nm">'+esc(v.name)+'</div><div class="bt-qty"><button type="button" data-id="'+i.id+'" data-d="-1">−</button><span>'+i.qty+'</span><button type="button" data-id="'+i.id+'" data-d="1">+</button></div><div class="pr">'+rub(v.price*i.qty)+'</div><button type="button" class="rm" data-rm="'+i.id+'">Удалить</button></div>'}).join('');
        root.innerHTML=rows+'<div class="bt-total"><span>Товары</span><span>'+rub(total)+'</span></div>'
          +'<div class="bt-info">'+(allSku?'Стоимость доставки Ozon рассчитается на странице оплаты: там же выберете пункт выдачи или курьера.':'Для части товаров доставка Ozon подключается. Оформите заказ, и мы свяжемся с вами по доставке, либо закажите эти товары на Ozon и Wildberries.')+'</div>'
          +'<form class="bt-form" id="bt-form" novalidate><h2 style="font-size:22px;margin-top:20px">Ваши данные</h2>'
          +'<input type="text" name="name" placeholder="Имя" autocomplete="name" required>'
          +'<input type="tel" name="phone" placeholder="Телефон +7" autocomplete="tel" required>'
          +'<input type="email" name="email" placeholder="Почта (на неё придёт чек)" autocomplete="email" required>'
          +'<textarea name="comment" rows="2" placeholder="Комментарий к заказу (необязательно)"></textarea>'
                    +'<label class="ck"><input type="checkbox" name="agree"> <span>Я принимаю <a href="/oferta" target="_blank">публичную оферту</a> и <a href="/terms" target="_blank">политику конфиденциальности</a>, ознакомлен с <a href="/dostavka-i-oplata" target="_blank">условиями доставки, оплаты и возврата</a>. Товары не являются лекарственными средствами.</span></label>'
          +'<div class="bt-err" id="bt-err"></div><button type="submit" class="bt-buy" id="bt-pay" style="min-height:52px">Оплатить '+rub(total)+'</button></form>';
        var f=document.getElementById('bt-form');
        f.addEventListener('submit',function(e){
          e.preventDefault();var err=document.getElementById('bt-err'),b=document.getElementById('bt-pay');err.textContent='';
          var d=new FormData(f);
          var body={items:load(),name:d.get('name'),phone:d.get('phone'),email:d.get('email'),comment:d.get('comment'),elapsed:Date.now()-T0,agree:f.agree.checked,delivery:allSku};
          b.disabled=true;b.textContent='Создаём заказ…';goal('checkout_start');
          fetch('/shop/create-order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})
            .then(function(r){return r.json()}).then(function(j){
              if(j.payLink){goal('go_to_payment');try{sessionStorage.setItem('bt_last',j.extId)}catch(x){}location.href=j.payLink}
              else{err.textContent=(j.error||'Ошибка')+(j.detail?' ['+j.detail+']':'');b.disabled=false;b.textContent='Оплатить '+rub(total)}})
            .catch(function(){err.textContent='Нет связи с сервером. Попробуйте ещё раз.';b.disabled=false;b.textContent='Оплатить '+rub(total)});
        });
      }
      root.addEventListener('click',function(e){
        var t=e.target.closest('button');if(!t)return;var c=load(),id=t.getAttribute('data-id'),rm=t.getAttribute('data-rm');
        if(rm){c=c.filter(function(i){return i.id!==rm})}
        else if(id){c.forEach(function(i){if(i.id===id)i.qty=Math.max(1,Math.min(50,i.qty+parseInt(t.getAttribute('data-d'))))})}
        else return;save(c);render()});
      render();
    });
  }
  function done(){var s=document.getElementById('bt-clear');if(s){try{localStorage.removeItem(KEY)}catch(e){}goal('purchase_return')}}
  function init(){done();fab();buybox();cartPage()}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
