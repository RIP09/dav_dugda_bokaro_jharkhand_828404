/* DAV Public School, Dugda — site interactions. Vanilla JS, no libraries. */
(function () {
  'use strict';
  var d = document, w = window;
  var reduce = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
  d.documentElement.classList.remove('no-js');

  /* Header shadow + back-to-top progress ring */
  var header = d.getElementById('header'), toTop = d.getElementById('toTop'), ring = d.getElementById('toTopRing');
  var ticking = false;
  function onScroll() {
    var y = w.scrollY || 0, h = d.documentElement.scrollHeight - w.innerHeight;
    if (header) header.classList.toggle('is-scrolled', y > 10);
    if (toTop) {
      toTop.classList.toggle('is-visible', y > 600);
      if (ring) ring.style.strokeDashoffset = String(125.7 * (1 - Math.min(1, y / Math.max(h, 1))));
    }
    ticking = false;
  }
  w.addEventListener('scroll', function () { if (!ticking) { ticking = true; w.requestAnimationFrame(onScroll); } }, { passive: true });
  onScroll();
  if (toTop) toTop.addEventListener('click', function () { w.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }); });

  /* Mobile drawer */
  var drawer = d.getElementById('drawer'), burger = d.getElementById('burger'), closeBtn = d.getElementById('drawerClose');
  function setDrawer(open) {
    if (!drawer) return;
    drawer.classList.toggle('is-open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    d.body.style.overflow = open ? 'hidden' : '';
    if (open && closeBtn) closeBtn.focus();
  }
  if (burger) burger.addEventListener('click', function () { setDrawer(true); });
  if (closeBtn) closeBtn.addEventListener('click', function () { setDrawer(false); });
  if (drawer) drawer.addEventListener('click', function (e) { if (e.target === drawer) setDrawer(false); });
  d.addEventListener('keydown', function (e) { if (e.key === 'Escape') setDrawer(false); });

  /* Split page titles into words for the rise-in effect */
  d.querySelectorAll('[data-split]').forEach(function (el) {
    if (reduce) return;
    var words = el.textContent.trim().split(/\s+/);
    el.setAttribute('aria-label', el.textContent.trim());
    el.innerHTML = words.map(function (wd, i) { return '<span class="w" aria-hidden="true"><span style="--i:' + i + '">' + wd.replace(/[<>&]/g, '') + '</span></span>'; }).join(' ');
  });

  /* Scroll reveal + animated counters */
  var revealEls = d.querySelectorAll('[data-reveal]');
  function countUp(el) {
    var target = parseInt(el.getAttribute('data-count'), 10) || 0, start = null, dur = 1600;
    if (reduce) { el.textContent = target.toLocaleString('en-IN'); return; }
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min(1, (ts - start) / dur), e = 1 - Math.pow(1 - p, 4);
      el.textContent = Math.round(target * e).toLocaleString('en-IN');
      if (p < 1) w.requestAnimationFrame(step);
    }
    w.requestAnimationFrame(step);
  }
  if ('IntersectionObserver' in w && !reduce) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        en.target.classList.add('is-in');
        en.target.querySelectorAll('[data-count]').forEach(countUp);
        io.unobserve(en.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('is-in'); });
    d.querySelectorAll('[data-count]').forEach(countUp);
  }

  /* Hero slider: crossfade, progress dots, swipe, pause on hover / hidden tab */
  d.querySelectorAll('[data-slider]').forEach(function (hero) {
    var slides = hero.querySelectorAll('[data-slide]'), dots = hero.querySelectorAll('[data-dot]');
    if (slides.length < 2) return;
    var interval = parseInt(hero.getAttribute('data-interval'), 10) || 6500, cur = 0, timer = null, paused = false;
    hero.style.setProperty('--interval', interval + 'ms');
    function go(n) {
      slides[cur].classList.remove('is-active'); if (dots[cur]) dots[cur].classList.remove('is-active');
      cur = (n + slides.length) % slides.length;
      slides[cur].classList.add('is-active');
      if (dots[cur]) { dots[cur].classList.remove('is-active'); void dots[cur].offsetWidth; dots[cur].classList.add('is-active'); }
      restart();
    }
    function restart() { clearTimeout(timer); if (!paused && !reduce) timer = setTimeout(function () { go(cur + 1); }, interval); }
    dots.forEach(function (b) { b.addEventListener('click', function () { go(parseInt(b.getAttribute('data-dot'), 10)); }); });
    var prev = hero.querySelector('[data-prev]'), next = hero.querySelector('[data-next]');
    if (prev) prev.addEventListener('click', function () { go(cur - 1); });
    if (next) next.addEventListener('click', function () { go(cur + 1); });
    hero.addEventListener('mouseenter', function () { paused = true; hero.classList.add('is-paused'); clearTimeout(timer); });
    hero.addEventListener('mouseleave', function () { paused = false; hero.classList.remove('is-paused'); restart(); });
    d.addEventListener('visibilitychange', function () { if (d.hidden) clearTimeout(timer); else restart(); });
    var sx = null;
    hero.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    hero.addEventListener('touchend', function (e) {
      if (sx === null) return; var dx = e.changedTouches[0].clientX - sx;
      if (Math.abs(dx) > 50) go(cur + (dx < 0 ? 1 : -1)); sx = null;
    });
    hero.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') go(cur - 1); if (e.key === 'ArrowRight') go(cur + 1); });
    restart();
  });

  /* Testimonials rotator */
  d.querySelectorAll('[data-quotes]').forEach(function (box) {
    var qs = box.querySelectorAll('[data-quote]'), dots = box.querySelectorAll('[data-qdot]'), cur = 0, t;
    if (qs.length < 2) return;
    function show(n) {
      qs[cur].classList.remove('is-active'); dots[cur].classList.remove('is-active');
      cur = (n + qs.length) % qs.length; qs[cur].classList.add('is-active'); dots[cur].classList.add('is-active');
      clearTimeout(t); if (!reduce) t = setTimeout(function () { show(cur + 1); }, 7000);
    }
    dots.forEach(function (b, i) { b.addEventListener('click', function () { show(i); }); });
    if (!reduce) t = setTimeout(function () { show(1); }, 7000);
  });

  /* Lightbox for gallery photos */
  var lb = null, lbItems = [], lbIndex = 0;
  function buildLb() {
    lb = d.createElement('div'); lb.className = 'lb'; lb.setAttribute('role', 'dialog'); lb.setAttribute('aria-modal', 'true');
    lb.innerHTML = '<span class="lb-count"></span><button class="lb-close" aria-label="Close">&#10005;</button><button class="lb-prev" aria-label="Previous">&#8249;</button><button class="lb-next" aria-label="Next">&#8250;</button><figure><img alt=""><figcaption></figcaption></figure>';
    d.body.appendChild(lb);
    lb.querySelector('.lb-close').onclick = closeLb;
    lb.querySelector('.lb-prev').onclick = function () { showLb(lbIndex - 1); };
    lb.querySelector('.lb-next').onclick = function () { showLb(lbIndex + 1); };
    lb.addEventListener('click', function (e) { if (e.target === lb) closeLb(); });
    var sx = null;
    lb.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) { if (sx === null) return; var dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) showLb(lbIndex + (dx < 0 ? 1 : -1)); sx = null; });
    d.addEventListener('keydown', function (e) {
      if (!lb.classList.contains('is-open')) return;
      if (e.key === 'Escape') closeLb(); if (e.key === 'ArrowLeft') showLb(lbIndex - 1); if (e.key === 'ArrowRight') showLb(lbIndex + 1);
    });
  }
  function showLb(i) {
    lbIndex = (i + lbItems.length) % lbItems.length;
    var a = lbItems[lbIndex], img = lb.querySelector('img');
    img.src = a.getAttribute('href'); img.alt = a.getAttribute('data-caption') || '';
    lb.querySelector('figcaption').textContent = a.getAttribute('data-caption') || '';
    lb.querySelector('.lb-count').textContent = (lbIndex + 1) + ' / ' + lbItems.length;
  }
  function closeLb() { lb.classList.remove('is-open'); d.body.style.overflow = ''; }
  d.querySelectorAll('[data-lightbox]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      e.preventDefault(); if (!lb) buildLb();
      var group = a.closest('[data-lightbox-group]');
      lbItems = Array.prototype.slice.call((group || d).querySelectorAll('[data-lightbox]'));
      showLb(lbItems.indexOf(a)); lb.classList.add('is-open'); d.body.style.overflow = 'hidden';
      lb.querySelector('.lb-close').focus();
    });
  });

  /* Faculty department filter */
  d.querySelectorAll('[data-filter-group]').forEach(function (bar) {
    var target = d.querySelector('[data-filter-target="' + bar.getAttribute('data-filter-group') + '"]');
    if (!target) return;
    bar.querySelectorAll('[data-filter]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var f = btn.getAttribute('data-filter');
        bar.querySelectorAll('[data-filter]').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
        target.querySelectorAll('[data-tags]').forEach(function (it) {
          it.classList.toggle('is-hidden', f !== '*' && it.getAttribute('data-tags') !== f);
        });
      });
    });
  });

  /* Load the Google map only when asked (keeps the page fast on phones) */
  d.querySelectorAll('[data-map]').forEach(function (box) {
    var btn = box.querySelector('.map-load');
    function load() { box.innerHTML = '<iframe src="' + box.getAttribute('data-map') + '" title="School location on map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>'; }
    if (btn) btn.addEventListener('click', load);
    if ('IntersectionObserver' in w && w.innerWidth > 900) {
      var mo = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { load(); mo.disconnect(); } }, { rootMargin: '200px' });
      mo.observe(box);
    }
  });

  /* Copy link */
  d.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var txt = b.getAttribute('data-copy');
      (navigator.clipboard ? navigator.clipboard.writeText(txt) : Promise.reject()).then(function () {
        var old = b.innerHTML; b.textContent = 'Link copied ✓'; setTimeout(function () { b.innerHTML = old; }, 1800);
      }, function () { w.prompt('Copy this link:', txt); });
    });
  });

  /* Friendly form checks before submitting (the server checks again) */
  d.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var bad = null;
      form.querySelectorAll('.ff').forEach(function (ff) {
        var input = ff.querySelector('input,select,textarea'); if (!input) return;
        var msg = '';
        if (input.required && !input.value.trim()) msg = 'Please fill this in.';
        else if (input.type === 'tel' && input.value.trim() && !/^[0-9+\-\s]{10,16}$/.test(input.value.trim())) msg = 'Please enter a valid 10-digit mobile number.';
        else if (input.type === 'email' && input.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim())) msg = 'Please enter a valid email.';
        var err = ff.querySelector('.ff-err');
        if (msg) {
          ff.classList.add('has-err');
          if (!err) { err = d.createElement('span'); err.className = 'ff-err'; ff.appendChild(err); }
          err.textContent = msg; if (!bad) bad = input;
        } else { ff.classList.remove('has-err'); if (err) err.remove(); }
      });
      if (bad) { e.preventDefault(); bad.focus(); return; }
      var btn = form.querySelector('button[type=submit]');
      if (btn) { btn.disabled = true; btn.style.opacity = '.7'; btn.firstChild && (btn.firstChild.textContent = 'Sending… '); }
    });
  });
})();
