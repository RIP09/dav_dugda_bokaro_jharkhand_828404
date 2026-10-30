/* Website CMS — small helpers: rich text editor, photo previews, slugs, confirmations, mobile menu */
(function () {
  'use strict';
  var d = document;
  var side = d.getElementById('side'), scrim = d.getElementById('sideScrim'), tog = d.getElementById('sideToggle');
  function setSide(o) { if (side) side.classList.toggle('open', o); if (scrim) scrim.classList.toggle('open', o); }
  if (tog) tog.addEventListener('click', function () { setSide(!side.classList.contains('open')); });
  if (scrim) scrim.addEventListener('click', function () { setSide(false); });

  // Rich text (Quill, self-hosted). Falls back to a plain textarea if Quill fails to load.
  function initRich() {
    d.querySelectorAll('[data-rich]').forEach(function (box) {
      var ta = d.getElementById(box.getAttribute('data-rich'));
      if (!window.Quill) { box.remove(); ta.hidden = false; ta.rows = 12; return; }
      var q = new Quill(box.querySelector('.rich-editor'), {
        theme: 'snow',
        modules: { toolbar: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['blockquote', 'link', 'image', 'video'], [{ align: [] }], ['clean']] }
      });
      var form = box.closest('form');
      function sync() { ta.value = q.root.innerHTML === '<p><br></p>' ? '' : q.root.innerHTML; }
      q.on('text-change', sync);
      if (form) form.addEventListener('submit', sync);
    });
  }
  window.addEventListener('load', initRich);

  // Live photo preview when choosing an image
  d.querySelectorAll('[data-upload]').forEach(function (u) {
    var input = u.querySelector('input[type=file]'), prev = u.querySelector('.upload-prev');
    if (!input || !prev) return;
    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      if (!f || !/^image\//.test(f.type)) return;
      var url = URL.createObjectURL(f);
      prev.innerHTML = '<img alt="" src="' + url + '">';
    });
  });

  // Slug suggestion from title
  d.querySelectorAll('[data-slug-from]').forEach(function (s) {
    var src = d.getElementById(s.getAttribute('data-slug-from'));
    if (!src || s.value) return;
    var touched = false;
    s.addEventListener('input', function () { touched = true; });
    src.addEventListener('input', function () {
      if (touched) return;
      s.value = src.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
    });
  });

  // Switch label Yes/No
  d.querySelectorAll('.switch input').forEach(function (c) {
    c.addEventListener('change', function () { var b = c.parentNode.querySelector('b'); if (b) b.textContent = c.checked ? (b.textContent === 'Blocked' || b.textContent === 'Active' ? 'Active' : 'Yes') : (b.textContent === 'Active' ? 'Blocked' : 'No'); });
  });

  // Icon picker preview
  var tpl = d.getElementById('icon-svgs');
  d.querySelectorAll('[data-icon-select]').forEach(function (sel) {
    var prev = sel.parentNode.querySelector('[data-icon-preview]');
    sel.addEventListener('change', function () {
      var src = tpl && tpl.content.querySelector('[data-i="' + sel.value + '"]');
      if (prev && src) prev.innerHTML = src.innerHTML;
    });
  });

  // Confirm before deleting
  d.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
  });

  // Drag & drop photos
  d.querySelectorAll('[data-dropzone]').forEach(function (z) {
    var input = z.querySelector('input[type=file]');
    ['dragenter', 'dragover'].forEach(function (ev) { z.addEventListener(ev, function (e) { e.preventDefault(); z.classList.add('drag'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { z.addEventListener(ev, function (e) { e.preventDefault(); z.classList.remove('drag'); }); });
    z.addEventListener('drop', function (e) { if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; } });
  });

  // Warn about unsaved changes
  d.querySelectorAll('form[data-cms-form]').forEach(function (f) {
    var dirty = false;
    f.addEventListener('input', function () { dirty = true; });
    f.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });
})();
