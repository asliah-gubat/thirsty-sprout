# Thirsty Sprout v3: anchored AR + plant recognition

Copy this package over your project (Replace). You do NOT need cleanup.bat again.
No migration is needed (no new database tables), so just reload the page. If anything looks stale, run `php artisan config:clear`.

## What the AR camera does now

| Your goal | How it works |
|---|---|
| 1. Tap the plant and the face is placed there | **Place my plant**: tap the real plant (iPhone) or the green ring (Android). |
| 2. The face stays when the camera moves | Android Chrome uses **WebXR** (ARCore): fixed in the room, even when you walk. Other phones use **camera + gyroscope**: fixed when you turn the phone. |
| 3. The camera recognizes the plant later | **Teach the camera this plant** saves a photo. **Find my plant** then looks for that view. **Sprout Tag** is the sticker fallback. |

Taps on the plant play with it (+XP). Care buttons and the arrows (switch plant) work in every mode.

## Teaching and finding a plant (do this on your phone)

1. Open the AR page, pick the plant, tap **Teach the camera this plant**.
2. Aim at the plant AND its pot, with some surroundings (shelf, wall, tiles) in view. Tap **Capture**, then **Save**.
   Saving converts the photo into a MindAR target inside your browser (10 to 60 seconds, shows a percentage).
3. Later: pick the plant, tap **Find my plant**, and point the camera at the same view.
4. If it does not lock on, use **Use Sprout Tag sticker**: print `public/targets/sprout-card.png` and stick it on the pot.
   One-time setup for the tag: compile the PNG at https://hiukim.github.io/mind-ar-js-doc/tools/compile and save the result as `public/targets/sprout-card.mind`.

## Honest limits

- A plant is soft and changes (leaves move, it grows), so recognizing it is the shakiest part. Photos that include the pot, a label, or a patterned background work best. Retake the photo after a big change.
- It recognizes the **view you taught**, from roughly the same angle and distance. It does not identify your plant from any angle.
- WebXR placement is lost when you leave AR. A browser cannot remember a room spot between sessions, which is exactly why Find my plant and the tag exist.
- On iPhone, WebXR is not available, so placement uses the gyroscope. It is fixed in direction but not in distance: walking toward the plant will not make it bigger.
- WebXR "tap the ring" snaps to detected surfaces and feature points, so tapping a leaf may land on the table or pot behind it. This is normal.
- The camera needs HTTPS (your tunnel link) on the phone. iPhone asks for "Motion & Orientation" permission the first time.
