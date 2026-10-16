/* Shared helper: fetch with the CSRF token. */
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  async function send(url, body, method) {
    const res = await fetch(url, {
      method: method || 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: body ? JSON.stringify(body) : undefined,
    });
    let data = null;
    try { data = await res.json(); } catch (e) { /* empty body */ }
    return { ok: res.ok, status: res.status, data };
  }

  async function upload(url, formData) {
    let res;
    try {
      res = await fetch(url, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: formData,
      });
    } catch (e) {
      // the request itself failed (no connection, tunnel dropped, wrong address)
      return { ok: false, status: 0, data: null, text: '', networkError: e.message || String(e), url };
    }
    let text = '';
    let bodyLost = false;
    try { text = await res.text(); } catch (e) { bodyLost = true; }   // reply was cut off
    let data = null;
    try { data = JSON.parse(text); } catch (e) { /* not JSON */ }
    return { ok: res.ok, status: res.status, data, text, bodyLost, url };
  }

  window.Sprout = { send, upload };
})();
