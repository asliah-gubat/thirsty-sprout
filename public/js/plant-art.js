/* Draws a doodled plant pet on a <canvas>. The pot has the face; the plant on top grows with the stage.
 * Used by the garden, the choose screen AND the WebAR camera (as a texture), so it looks the same everywhere.
 *
 *   PlantArt.draw(canvas, {stage: 0-4, mood: 'happy'|'needy'|'wilting', personality, decoration, art: {leaf, bloom, pot}})
 */
(function () {
  const INK = '#16301F';

  function blob(g, circles, fill) {
    g.strokeStyle = INK; g.lineWidth = 14;
    circles.forEach(([x, y, r]) => { g.beginPath(); g.arc(x, y, r, 0, Math.PI * 2); g.stroke(); });
    g.fillStyle = fill;
    circles.forEach(([x, y, r]) => { g.beginPath(); g.arc(x, y, r, 0, Math.PI * 2); g.fill(); });
  }

  function leaf(g, x, y, angle, len, w, fill) {
    g.save(); g.translate(x, y); g.rotate(angle);
    g.beginPath(); g.ellipse(0, -len / 2, w, len / 2, 0, 0, Math.PI * 2);
    g.fillStyle = fill; g.fill(); g.lineWidth = 6; g.strokeStyle = INK; g.stroke();
    g.restore();
  }

  function stem(g, x1, y1, x2, y2, width, color) {
    g.lineCap = 'round';
    g.strokeStyle = INK; g.lineWidth = width + 10;
    g.beginPath(); g.moveTo(x1, y1); g.lineTo(x2, y2); g.stroke();
    g.strokeStyle = color; g.lineWidth = width;
    g.beginPath(); g.moveTo(x1, y1); g.lineTo(x2, y2); g.stroke();
  }

  function dot(g, x, y, r, fill) {
    g.beginPath(); g.arc(x, y, r, 0, Math.PI * 2);
    g.fillStyle = fill; g.fill(); g.lineWidth = 4; g.strokeStyle = INK; g.stroke();
  }

  function plantPart(g, p, leafCol) {
    const droop = p.mood === 'wilting' ? 0.9 : p.mood === 'needy' ? 0.35 : 0;
    const bloom = p.art.bloom;

    if (p.stage === 0) {                               // seed in a mound of soil
      g.lineWidth = 6; g.strokeStyle = INK;
      g.beginPath(); g.ellipse(256, 344, 74, 20, 0, 0, Math.PI * 2); g.fillStyle = '#7A4E2D'; g.fill(); g.stroke();
      g.beginPath(); g.ellipse(256, 326, 18, 13, 0, 0, Math.PI * 2); g.fillStyle = '#C9A06A'; g.fill(); g.stroke();
    } else if (p.stage === 1) {                        // sprout
      stem(g, 256, 340, 256, 285, 10, '#5E8F3A');
      leaf(g, 256, 288, -(0.75 + droop), 70, 26, leafCol);
      leaf(g, 256, 288, 0.75 + droop, 70, 26, leafCol);
    } else if (p.stage === 2) {                        // sapling
      stem(g, 256, 340, 256, 215, 12, '#5E8F3A');
      leaf(g, 256, 305, -(0.9 + droop), 78, 28, leafCol);
      leaf(g, 256, 305, 0.9 + droop, 78, 28, leafCol);
      leaf(g, 256, 258, -(0.7 + droop), 78, 28, leafCol);
      leaf(g, 256, 258, 0.7 + droop, 78, 28, leafCol);
      leaf(g, 256, 220, 0, 80, 28, leafCol);
    } else if (p.stage === 3) {                        // young tree
      stem(g, 256, 345, 256, 200, 22, '#8B5A33');
      blob(g, [[256, 150, 78], [196, 182, 52], [316, 182, 52]], leafCol);
      [[230, 140], [300, 165], [200, 185]].forEach(([x, y]) => dot(g, x, y, 10, bloom));
    } else {                                           // mature tree
      stem(g, 256, 345, 256, 190, 30, '#8B5A33');
      blob(g, [[256, 112, 96], [176, 165, 68], [336, 165, 68], [256, 190, 50]], leafCol);
      [[220, 90], [292, 78], [336, 150], [180, 158], [252, 135], [300, 120], [235, 188]].forEach(([x, y]) => dot(g, x, y, 12, bloom));
    }
  }

  function pot(g, p) {
    g.lineWidth = 6; g.strokeStyle = INK;
    g.beginPath(); g.moveTo(166, 352); g.lineTo(346, 352); g.lineTo(326, 470); g.lineTo(186, 470); g.closePath();
    g.fillStyle = p.art.pot; g.fill(); g.stroke();
    g.beginPath(); g.rect(154, 334, 204, 32);
    g.fillStyle = p.art.pot; g.fill(); g.fillStyle = 'rgba(0,0,0,.14)'; g.fill(); g.stroke();
  }

  function face(g, p) {
    const ey = 405;
    g.lineCap = 'round'; g.lineWidth = 6; g.strokeStyle = INK;
    [224, 288].forEach((x, i) => {
      g.fillStyle = INK;
      if (p.mood === 'wilting') {
        g.beginPath(); g.moveTo(x - 8, ey - 8); g.lineTo(x + 8, ey + 8); g.moveTo(x + 8, ey - 8); g.lineTo(x - 8, ey + 8); g.stroke();
      } else if (p.personality === 'sleepy') {
        g.beginPath(); g.arc(x, ey - 2, 9, 0.1 * Math.PI, 0.9 * Math.PI); g.stroke();
      } else if (p.personality === 'grumpy') {
        g.beginPath(); g.arc(x, ey + 2, 6, 0, Math.PI * 2); g.fill();
        g.beginPath(); g.moveTo(x - 12, ey - 13 + (i ? 7 : 0)); g.lineTo(x + 12, ey - 13 + (i ? 0 : 7)); g.stroke();
      } else {
        g.beginPath(); g.arc(x, ey, p.personality === 'shy' ? 7 : 9, 0, Math.PI * 2); g.fill();
        g.fillStyle = '#fff'; g.beginPath(); g.arc(x + 3, ey - 3, 3, 0, Math.PI * 2); g.fill();
      }
    });

    if (p.personality === 'shy' && p.mood !== 'wilting') {
      g.fillStyle = 'rgba(240,120,140,.55)';
      [[200, 428], [312, 428]].forEach(([x, y]) => { g.beginPath(); g.arc(x, y, 11, 0, Math.PI * 2); g.fill(); });
    }

    g.strokeStyle = INK; g.beginPath();
    if (p.mood === 'happy') g.arc(256, 424, 18, 0.15 * Math.PI, 0.85 * Math.PI);
    else if (p.mood === 'needy') { g.moveTo(240, 440); g.lineTo(272, 440); }
    else g.arc(256, 458, 16, 1.15 * Math.PI, 1.85 * Math.PI);
    g.stroke();

    if (p.mood === 'needy') {                           // sweat drop
      g.fillStyle = '#2F86B0';
      g.beginPath(); g.moveTo(336, 372); g.quadraticCurveTo(348, 394, 336, 400); g.quadraticCurveTo(324, 394, 336, 372); g.fill();
    }
  }

  function decoration(g, p) {
    g.lineWidth = 5; g.strokeStyle = INK;
    if (p.decoration === 'sunglasses') {
      g.fillStyle = INK;
      g.fillRect(202, 392, 44, 26); g.fillRect(266, 392, 44, 26); g.fillRect(244, 398, 24, 6);
    } else if (p.decoration === 'ribbon') {
      g.fillStyle = '#D9402F';
      g.beginPath(); g.moveTo(350, 352); g.lineTo(320, 334); g.lineTo(320, 370); g.closePath(); g.fill(); g.stroke();
      g.beginPath(); g.moveTo(350, 352); g.lineTo(382, 334); g.lineTo(382, 370); g.closePath(); g.fill(); g.stroke();
      dot(g, 350, 352, 9, '#B02E20');
    } else if (p.decoration === 'flower') {
      for (let i = 0; i < 5; i++) {
        const a = (i / 5) * Math.PI * 2;
        dot(g, 176 + Math.cos(a) * 14, 348 + Math.sin(a) * 14, 10, '#F28BB0');
      }
      dot(g, 176, 348, 8, '#F2C230');
    } else if (p.decoration === 'star') {
      g.fillStyle = '#F2C230';
      g.beginPath();
      for (let i = 0; i < 10; i++) {
        const r = i % 2 ? 14 : 34, a = -Math.PI / 2 + i * Math.PI / 5;
        g.lineTo(410 + Math.cos(a) * r, 80 + Math.sin(a) * r);
      }
      g.closePath(); g.fill(); g.stroke();
    }
  }

  function draw(canvas, p) {
    const g = canvas.getContext('2d');
    g.setTransform(1, 0, 0, 1, 0, 0);
    g.clearRect(0, 0, canvas.width, canvas.height);
    const k = canvas.width / 512;
    g.setTransform(k, 0, 0, k, 0, 0);
    g.lineJoin = 'round';

    const leafCol = p.mood === 'wilting' ? '#A98B4F' : p.mood === 'needy' ? '#BDBE55' : p.art.leaf;

    g.fillStyle = 'rgba(0,0,0,.16)';
    g.beginPath(); g.ellipse(256, 476, 112, 12, 0, 0, Math.PI * 2); g.fill();

    plantPart(g, p, leafCol);
    pot(g, p);
    face(g, p);
    decoration(g, p);
  }

  window.PlantArt = { draw };
})();
