/* Thirsty Sprout WebAR (Three.js + WebXR + MindAR.js)
 *
 * PLACE mode  - tap your real plant (or any spot) and the plant pet is placed there.
 *                Android Chrome: WebXR (ARCore) keeps it fixed in the room, even if you walk around.
 *                Everything else (iPhone etc.): camera + gyroscope keep it fixed when you turn the phone.
 * FIND mode   - MindAR looks for the photo you taught it ("recognition photo") and puts the pet on it.
 * TAG mode    - MindAR looks for the printed Sprout Tag sticker (fallback when the photo is not recognized).
 * TEACH       - takes a photo of your plant, turns it into a MindAR target inside the browser, saves it.
 */
import * as THREE from 'three';

const cfg = window.SPROUT_AR;
const $ = (id) => document.getElementById(id);
const fill = (tpl, id) => tpl.replace('__ID__', id);
const stageEl = $('stage');

const plants = new Map(cfg.plants.map((p) => [p.id, p]));
const ids = cfg.plants.map((p) => p.id);
let selectedId = plants.has(cfg.selected) ? cfg.selected : ids[0];

let mode = null;            // 'place' | 'photo' | 'tag'
let kind = null;            // 'xr' | 'gyro' | 'marker'
let engine = null;          // { stop() } of whatever is running
let renderer, scene, camera;
let targetFound = false;
let xrSupported = false;
const objs = new Map();     // plant id -> {id, canvas, tex, mesh, group, size, bounce, phase}
const hearts = [];
const raycaster = new THREE.Raycaster();
const camPos = new THREE.Vector3();

/* ================= plant sprites ================= */
function redraw(o) {
  window.PlantArt.draw(o.canvas, plants.get(o.id));
  o.tex.needsUpdate = true;
}

function makeObj(id, size) {
  const canvas = document.createElement('canvas');
  canvas.width = canvas.height = 512;
  const tex = new THREE.CanvasTexture(canvas);
  const mat = new THREE.MeshBasicMaterial({ map: tex, transparent: true, side: THREE.DoubleSide, depthWrite: false });
  const mesh = new THREE.Mesh(new THREE.PlaneGeometry(size, size), mat);
  const group = new THREE.Group();
  group.add(mesh);
  const o = { id, canvas, tex, mesh, group, size, bounce: 0, phase: Math.random() * 6 };
  redraw(o);
  objs.set(id, o);
  return o;
}

function spawnHeart(o) {
  const c = document.createElement('canvas');
  c.width = c.height = 128;
  const g = c.getContext('2d');
  g.font = '96px serif'; g.textAlign = 'center'; g.textBaseline = 'middle';
  g.fillText('\u{1F49A}', 64, 70);
  const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: new THREE.CanvasTexture(c), transparent: true, depthWrite: false }));
  const s = o.size * 0.28;
  sprite.scale.set(s, s, 1);
  o.group.getWorldPosition(sprite.position);
  sprite.position.x += (Math.random() - 0.5) * o.size * 0.4;
  sprite.position.y += o.size * 0.35;
  scene.add(sprite);
  hearts.push({ sprite, life: 1, rise: o.size * 0.012 });
}

function animate(t) {
  if (kind === 'xr') renderer.xr.getCamera().getWorldPosition(camPos);

  objs.forEach((o) => {
    const bob = Math.sin(t / 500 + o.phase) * 0.02 * o.size;
    let s = 1 + Math.sin(t / 700 + o.phase) * 0.015;
    if (o.bounce > 0) {
      s *= 1 + 0.25 * Math.sin((1 - o.bounce) * Math.PI);
      o.bounce = Math.max(0, o.bounce - 0.04);
    }
    o.mesh.position.y = bob;
    o.mesh.scale.setScalar(s);
    // stay upright in the room but always turn to face you
    if (kind === 'xr') o.group.lookAt(camPos.x, o.group.position.y, camPos.z);
    else if (kind === 'gyro') o.group.lookAt(camera.position.x, o.group.position.y, camera.position.z);
  });

  for (let i = hearts.length - 1; i >= 0; i--) {
    const h = hearts[i];
    h.sprite.position.y += h.rise;
    h.life -= 0.02;
    h.sprite.material.opacity = Math.max(h.life, 0);
    if (h.life <= 0) { scene.remove(h.sprite); hearts.splice(i, 1); }
  }
}

/** The plant under the current raycaster ray (ignores the see-through parts of the picture). */
function pickPlant() {
  const hits = raycaster.intersectObjects([...objs.values()].map((o) => o.mesh), false);
  for (const h of hits) {
    const o = [...objs.values()].find((x) => x.mesh === h.object);
    if (!o || !h.uv) continue;
    const px = o.canvas.getContext('2d').getImageData(
      Math.min(511, Math.floor(h.uv.x * 512)), Math.min(511, Math.floor((1 - h.uv.y) * 512)), 1, 1).data;
    if (px[3] > 20) return o;
  }
  return null;
}

/* ================= HUD ================= */
function toast(msg) {
  const el = $('toast');
  el.textContent = msg;
  el.classList.remove('hidden');
  clearTimeout(toast.t);
  toast.t = setTimeout(() => el.classList.add('hidden'), 3500);
}

function setHint(text) {
  $('hint').textContent = text;
  $('hint-wrap').classList.toggle('hidden', !text);
}

function updateHud() {
  const p = plants.get(selectedId);
  if (!p) return;
  $('hud-name').textContent = p.name;
  $('hud-stage').textContent = p.stage_name + ' · ' + p.type_label;
  $('hud-hyd').style.width = p.hydration + '%';
  $('hud-hung').style.width = p.hunger + '%';
  $('hud-xp').style.width = p.xp_pct + '%';
}

function applyState(s) {
  const old = plants.get(s.id);
  plants.set(s.id, { ...s, target: s.target ?? old?.target });
  const o = objs.get(s.id);
  if (o) redraw(o);
  if (s.id === selectedId) updateHud();
  toast(s.message + (s.leveled_up ? ' It grew into a ' + s.stage_name + '!' : ''));
}

async function care(action, item) {
  const { ok, data } = await Sprout.send(fill(cfg.careUrl, selectedId), { action, item });
  if (!ok) return toast('Could not do that right now.');
  const o = objs.get(selectedId);
  if (o && action !== 'decorate') { o.bounce = 1; spawnHeart(o); }
  applyState(data);
}

async function interact(o) {
  o.bounce = 1;
  spawnHeart(o);
  selectedId = o.id;
  const { ok, data } = await Sprout.send(fill(cfg.interactUrl, o.id));
  if (ok) applyState(data);
  updateHud();
}

function placeHint() {
  if (mode !== 'place') return;
  const name = plants.get(selectedId).name;
  if (objs.has(selectedId)) return setHint('Tap ' + name + ' to play, or tap elsewhere to move it');
  setHint(kind === 'xr'
    ? 'Move the phone slowly, then tap the green ring to place ' + name
    : 'Tap your plant to place ' + name + ', then turn the phone around');
}

async function selectOffset(step) {
  const list = mode === 'photo' ? ids.filter((i) => plants.get(i).target) : ids;
  if (list.length < 2) return;
  selectedId = list[(list.indexOf(selectedId) + step + list.length) % list.length];
  updateHud();

  if (mode === 'photo') {                         // a different plant needs a different target file
    await stopAll({ keepIntro: true });
    return start('photo');
  }
  if (mode === 'tag') {                           // the tag shows one plant at a time
    const o = [...objs.values()][0];
    objs.delete(o.id); o.id = selectedId; objs.set(o.id, o); redraw(o);
    return;
  }
  placeHint();
}

/* ================= taps (gyro + marker modes) ================= */
function onTap(e) {
  if (kind === 'marker' && !targetFound) return;
  const r = e.currentTarget.getBoundingClientRect();
  const point = { x: ((e.clientX - r.left) / r.width) * 2 - 1, y: -((e.clientY - r.top) / r.height) * 2 + 1 };
  raycaster.setFromCamera(point, camera);

  const hit = pickPlant();
  if (hit) return interact(hit);

  if (kind === 'gyro') {                          // place or move the selected plant in the room
    const o = objs.get(selectedId) || makeObj(selectedId, 1.3);
    if (!o.group.parent) scene.add(o.group);
    raycaster.ray.at(2.5, o.group.position);
    o.bounce = 1;
    placeHint();
  }
}

/* ================= engine: PLACE with WebXR (Android Chrome) ================= */
async function startXR() {
  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  renderer.xr.enabled = true;
  stageEl.append(renderer.domElement);
  scene = new THREE.Scene();
  camera = new THREE.PerspectiveCamera(70, 1, 0.01, 30);

  const reticle = new THREE.Mesh(
    new THREE.RingGeometry(0.06, 0.08, 32).rotateX(-Math.PI / 2),
    new THREE.MeshBasicMaterial({ color: 0x7bc96f }));
  reticle.matrixAutoUpdate = false;
  reticle.visible = false;
  scene.add(reticle);

  const session = await navigator.xr.requestSession('immersive-ar', {
    requiredFeatures: ['hit-test'],
    optionalFeatures: ['dom-overlay'],
    domOverlay: { root: $('ar-root') },
  });
  renderer.xr.setReferenceSpaceType('local');
  await renderer.xr.setSession(session);

  const viewer = await session.requestReferenceSpace('viewer');
  let hitSource = await session.requestHitTestSource({ space: viewer, entityTypes: ['plane', 'point'] });

  const controller = renderer.xr.getController(0);
  const rot = new THREE.Matrix4();
  controller.addEventListener('select', () => {
    rot.identity().extractRotation(controller.matrixWorld);
    raycaster.ray.origin.setFromMatrixPosition(controller.matrixWorld);
    raycaster.ray.direction.set(0, 0, -1).applyMatrix4(rot);

    const hit = pickPlant();
    if (hit) return interact(hit);

    if (reticle.visible) {                         // place or move the selected plant at the ring
      const o = objs.get(selectedId) || makeObj(selectedId, 0.4);
      if (!o.group.parent) scene.add(o.group);
      o.group.position.setFromMatrixPosition(reticle.matrix);
      o.group.position.y += o.size * 0.45;
      o.bounce = 1;
      placeHint();
    }
  });
  scene.add(controller);

  renderer.setAnimationLoop((t, frame) => {
    if (frame && hitSource) {
      const results = frame.getHitTestResults(hitSource);
      if (results.length) {
        reticle.visible = true;
        reticle.matrix.fromArray(results[0].getPose(renderer.xr.getReferenceSpace()).transform.matrix);
      } else {
        reticle.visible = false;
      }
    }
    animate(t);
    renderer.render(scene, camera);
  });

  session.addEventListener('end', () => { hitSource = null; if (engine) stopAll(); });
  engine = {
    stop: async () => {
      try { await session.end(); } catch (e) { /* already ended */ }
      renderer.setAnimationLoop(null);
      renderer.dispose();
      stageEl.replaceChildren();
    },
  };
}

/* ================= engine: PLACE with camera + gyroscope (iPhone, other Androids) ================= */
const zee = new THREE.Vector3(0, 0, 1);
const euler = new THREE.Euler();
const q0 = new THREE.Quaternion();
const q1 = new THREE.Quaternion(-Math.sqrt(0.5), 0, 0, Math.sqrt(0.5)); // camera looks out of the back of the phone

function deviceQuaternion(out, alpha, beta, gamma, orient) {
  euler.set(beta, alpha, -gamma, 'YXZ');
  out.setFromEuler(euler);
  out.multiply(q1);
  out.multiply(q0.setFromAxisAngle(zee, -orient));
}

async function askMotionPermission() {
  // iPhones only give motion data after a tap + permission prompt
  if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
    try { await DeviceOrientationEvent.requestPermission(); } catch (e) { /* user said no */ }
  }
}

async function startGyro() {
  const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
  const video = document.createElement('video');
  video.setAttribute('playsinline', ''); video.muted = true; video.autoplay = true;
  video.className = 'absolute inset-0 w-full h-full object-cover';
  video.srcObject = stream;
  stageEl.append(video);
  await video.play();

  const canvas = document.createElement('canvas');
  canvas.className = 'absolute inset-0 w-full h-full touch-none';
  stageEl.append(canvas);

  renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  scene = new THREE.Scene();
  camera = new THREE.PerspectiveCamera(60, 1, 0.1, 50);
  const resize = () => {
    renderer.setSize(stageEl.clientWidth, stageEl.clientHeight, false);
    camera.aspect = stageEl.clientWidth / stageEl.clientHeight;
    camera.updateProjectionMatrix();
  };
  resize();
  addEventListener('resize', resize);

  const target = new THREE.Quaternion();
  let haveSensor = false;
  const onOrientation = (e) => {
    if (e.alpha == null) return;
    const rad = (d) => (d || 0) * Math.PI / 180;
    const orient = rad(screen.orientation?.angle ?? window.orientation ?? 0);
    deviceQuaternion(target, rad(e.alpha), rad(e.beta), rad(e.gamma), orient);
    if (!haveSensor) { camera.quaternion.copy(target); haveSensor = true; }
  };
  addEventListener('deviceorientation', onOrientation);
  setTimeout(() => { if (!haveSensor) toast('Motion sensor unavailable: the plant will stay on the screen.'); }, 2500);

  canvas.addEventListener('pointerdown', onTap);
  renderer.setAnimationLoop((t) => {
    if (haveSensor) camera.quaternion.slerp(target, 0.4);   // smooths out jitter
    animate(t);
    renderer.render(scene, camera);
  });

  engine = {
    stop: async () => {
      removeEventListener('deviceorientation', onOrientation);
      removeEventListener('resize', resize);
      renderer.setAnimationLoop(null);
      stream.getTracks().forEach((t) => t.stop());
      renderer.dispose();
      stageEl.replaceChildren();
    },
  };
}

/* ================= engine: FIND (photo) and TAG (sticker) with MindAR ================= */
async function startMarker(url, opt) {
  const { MindARThree } = await import('mindar-image-three');
  const mindar = new MindARThree({
    container: stageEl, imageTargetSrc: url, uiLoading: 'no', uiScanning: 'no', uiError: 'no',
  });
  ({ renderer, scene, camera } = mindar);

  const anchor = mindar.addAnchor(0);
  const o = makeObj(selectedId, opt.size);
  o.group.position.set(...opt.pos);
  anchor.group.add(o.group);
  anchor.onTargetFound = () => { targetFound = true; setHint('Found it! Tap the plant to play'); };
  anchor.onTargetLost = () => { targetFound = false; setHint(opt.lookFor); };

  await mindar.start();
  renderer.domElement.addEventListener('pointerdown', onTap);
  renderer.setAnimationLoop((t) => { animate(t); renderer.render(scene, camera); });
  setHint(opt.lookFor);

  engine = {
    stop: async () => {
      renderer.setAnimationLoop(null);
      try { mindar.stop(); } catch (e) { /* not running */ }
      stageEl.replaceChildren();
    },
  };
}

/* ================= TEACH: photo -> MindAR target (compiled in the browser) ================= */
let shot = null;

function teachStep(step, msg) {
  $('teach-capture').classList.toggle('hidden', step !== 'aim');
  $('teach-save').classList.toggle('hidden', step !== 'review');
  $('teach-retake').classList.toggle('hidden', step !== 'review');
  $('teach-msg').textContent = msg || {
    aim: 'Aim at the plant AND its pot, with some surroundings in view. Hold still, then tap Capture.',
    review: 'Happy with this photo? Saving teaches the camera to recognize this view.',
  }[step] || 'Working…';
}

async function startTeach() {
  const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
  const video = document.createElement('video');
  video.setAttribute('playsinline', ''); video.muted = true; video.autoplay = true;
  video.className = 'absolute inset-0 w-full h-full object-cover';
  video.srcObject = stream;
  stageEl.append(video);
  await video.play();

  engine = {
    video,
    stop: async () => { stream.getTracks().forEach((t) => t.stop()); stageEl.replaceChildren(); },
  };
  $('intro').classList.add('hidden');
  $('teach').classList.remove('hidden');
  teachStep('aim');
}

async function loadCompiler() {
  // MindAR's compiler is an ES module (not a plain script), so it must be imported
  const mod = await import('https://cdn.jsdelivr.net/npm/mind-ar@1.2.5/dist/mindar-image.prod.js');
  if (!mod.Compiler) throw new Error('The MindAR learning tool did not load.');
  return mod.Compiler;
}

function captureShot() {
  const v = engine.video;
  const k = Math.min(1, 512 / Math.max(v.videoWidth, v.videoHeight));   // small = faster to learn, smaller file
  shot = document.createElement('canvas');
  shot.width = Math.round(v.videoWidth * k);
  shot.height = Math.round(v.videoHeight * k);
  shot.getContext('2d').drawImage(v, 0, 0, shot.width, shot.height);
  v.pause();
  teachStep('review');
}

async function saveShot() {
  teachStep('busy', 'Getting ready…');
  try {
    const Compiler = await loadCompiler();
    const img = new Image();
    img.src = shot.toDataURL('image/jpeg', 0.9);
    await img.decode();

    const compiler = new Compiler();
    await compiler.compileImageTargets([img], (p) => teachStep('busy', 'Learning the picture… ' + Math.round(p) + '%'));
    const buffer = await compiler.exportData();
    const jpeg = await new Promise((r) => shot.toBlob(r, 'image/jpeg', 0.85));

    teachStep('busy', 'Saving…');
    const fd = new FormData();
    fd.append('target', new Blob([buffer]), 'plant.mind');
    fd.append('photo', jpeg, 'plant.jpg');
    const up = await Sprout.upload(fill(cfg.targetUrl, selectedId), fd);
    let { ok, status, data, text } = up;

    // Connection dropped? The file may still have arrived: ask the server.
    if (status === 0 || up.bodyLost) {
      teachStep('busy', 'Checking whether it was saved…');
      const saved = await targetSaved();
      if (saved) {
        ok = true; status = 200; data = { target: saved };
      } else if (status === 0) {
        const e = new Error('Could not reach the server (' + up.networkError + ') at ' + up.url
          + '. Check that your tunnel is still running and that APP_URL in .env matches your current tunnel link.');
        e.noHint = true;
        throw e;
      }
    }
    if (ok && !data?.target) {
      // the server said OK but the reply is not the expected JSON (often a PHP warning printed into it)
      const e = new Error('The server replied with something unexpected (status ' + status + '): '
        + String(text || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 200)
        + ' Your file may still have been saved: reload the page and check "Find my plant".');
      e.noHint = true;
      throw e;
    }
    if (!ok) {
      const mb = (buffer.byteLength / 1048576).toFixed(1);
      let why;
      if (status === 413 || data?.message === '') why = 'The server refused the ' + mb + ' MB file. PHP\'s upload limit is too small (see README: raise upload_max_filesize and post_max_size, then restart serve).';
      else if (status === 419) why = 'Your session expired. Reload the page and try again.';
      else if (status === 422) why = data?.message || 'The server did not accept the file.';
      else why = 'Server error ' + status + (data?.message ? ': ' + data.message : '') + '.';
      const e = new Error(why);
      e.noHint = true;
      throw e;
    }

    plants.get(selectedId).target = data.target;
    await stopAll();
    toast('Saved! Now choose "Find my plant".');
  } catch (err) {
    console.error(err);
    teachStep('review', 'That did not work: ' + err.message + (err.noHint ? '' : ' Try a photo with more detail, like a patterned pot or a label.'));
  }
}

async function targetSaved() {
  try {
    const res = await fetch(cfg.stateUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
    const list = await res.json();
    return list.find((p) => p.id === selectedId)?.target || null;
  } catch (e) {
    return null;
  }
}

/* ================= start / stop ================= */
function refreshIntro() {
  const sel = $('intro-plant');
  if (!sel.options.length) {
    cfg.plants.forEach((p) => sel.add(new Option(p.name, p.id)));
  }
  sel.value = selectedId;
  const p = plants.get(selectedId);
  $('go-photo').disabled = !p.target;
  $('photo-note').textContent = p.target
    ? 'Point at the view you taught. The plant appears on it.'
    : 'Teach the camera this plant first (button below).';
  $('place-note').textContent = xrSupported
    ? 'Uses your phone\'s AR tracking. The plant stays fixed in the room, even when you walk around.'
    : 'Uses the camera and motion sensors. The plant stays fixed when you turn the phone.';
}

async function stopAll(opts = {}) {
  if (engine) { const e = engine; engine = null; await e.stop(); }
  objs.forEach((o) => o.tex.dispose());
  objs.clear();
  hearts.length = 0;
  mode = null; kind = null; targetFound = false;
  $('panel').classList.add('hidden');
  $('btn-exit').classList.add('hidden');
  $('teach').classList.add('hidden');
  setHint('');
  if (!opts.keepIntro) { $('intro').classList.remove('hidden'); refreshIntro(); }
}

async function start(chosen, forceBasic = false) {
  $('start-error').textContent = '';
  if (!navigator.mediaDevices?.getUserMedia) {
    $('start-error').textContent = 'This browser cannot open the camera. Try Chrome (Android) or Safari (iPhone).';
    return;
  }
  mode = chosen === 'teach' ? null : chosen;

  try {
    if (chosen === 'place') {
      await askMotionPermission();
      kind = 'gyro';
      if (xrSupported && !forceBasic) {
        try { kind = 'xr'; await startXR(); }
        catch (e) {
          console.warn('WebXR failed, using the basic camera instead', e);
          await stopAll({ keepIntro: true });
          try { renderer?.dispose(); } catch (err) { /* nothing to clean */ }
          stageEl.replaceChildren();
          mode = 'place'; kind = 'gyro';
          await startGyro();
        }
      } else {
        await startGyro();
      }
    } else if (chosen === 'photo') {
      kind = 'marker';
      await startMarker(plants.get(selectedId).target, { size: 0.9, pos: [0, 0.1, 0.05], lookFor: 'Point the camera at your plant, from the same view you taught' });
    } else if (chosen === 'tag') {
      kind = 'marker';
      await startMarker(cfg.tagUrl, { size: 1.1, pos: [0, 0.45, 0.05], lookFor: 'Point the camera at your Sprout Tag' });
    } else {
      await startTeach();
      return;
    }
  } catch (err) {
    console.error(err);
    await stopAll();
    const secure = location.protocol === 'https:' || location.hostname === 'localhost';
    $('start-error').textContent = !secure
      ? 'The camera needs HTTPS. Open this site through your https:// link.'
      : 'Could not start AR. Allow camera access in your browser settings, then try again.';
    return;
  }

  $('intro').classList.add('hidden');
  $('panel').classList.remove('hidden');
  $('btn-exit').classList.remove('hidden');
  updateHud();
  if (mode === 'place') placeHint();
  const list = mode === 'photo' ? ids.filter((i) => plants.get(i).target) : ids;
  $('prev').classList.toggle('invisible', list.length < 2);
  $('next').classList.toggle('invisible', list.length < 2);
}

/* ================= wire up ================= */
$('go-place').addEventListener('click', () => start('place'));
$('go-basic').addEventListener('click', () => start('place', true));
$('go-photo').addEventListener('click', () => start('photo'));
$('go-tag')?.addEventListener('click', () => start('tag'));
$('go-teach').addEventListener('click', () => start('teach'));
$('intro-plant').addEventListener('change', (e) => { selectedId = parseInt(e.target.value, 10); refreshIntro(); });

$('teach-capture').addEventListener('click', captureShot);
$('teach-retake').addEventListener('click', () => { engine.video.play(); teachStep('aim'); });
$('teach-save').addEventListener('click', saveShot);
$('teach-cancel').addEventListener('click', () => stopAll());

$('btn-water').addEventListener('click', () => care('water'));
$('btn-feed').addEventListener('click', () => care('feed'));
$('btn-decor').addEventListener('click', () => {
  const keys = Object.keys(cfg.decorations);
  care('decorate', keys[(keys.indexOf(plants.get(selectedId).decoration) + 1) % keys.length]);
});
$('prev').addEventListener('click', () => selectOffset(-1));
$('next').addEventListener('click', () => selectOffset(1));
$('btn-exit').addEventListener('click', () => stopAll());

// while the WebXR overlay is up, taps on our buttons must not also count as "place the plant"
['panel', 'btn-exit', 'toast', 'teach'].forEach((id) =>
  $(id).addEventListener('beforexrselect', (e) => e.preventDefault()));

refreshIntro();
navigator.xr?.isSessionSupported?.('immersive-ar')
  .then((ok) => { xrSupported = ok; refreshIntro(); })
  .catch(() => {});

if (window.__arBoot) window.__arBoot.ready = true;
