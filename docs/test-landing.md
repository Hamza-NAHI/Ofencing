# TEST: minimalist ONescrime landing page

Created from main; only the homepage is redesigned. Existing coach, events and contact pages remain available; their training links now lead to contact instead of the removed demo calendar. QA's Google Calendar work is separate and is not merged into this experiment. The homepage no longer displays the old demo schedule or event cards.

## Assets

- `assets/fencing-sculpture.jpg`: 1536 × 1024 generated 3D-style still, optimized JPEG. The central épée is behind the entire mask bib; its bell opens toward the handle.
- `assets/fencing-sculpture-loop.mp4`: silent 8-second, 24 fps H.264 motion loop derived from the corrected still. This is a subtle camera push in/out, not real rotating 3D geometry or recorded fencing footage.
- Existing `assets/omar-nahi-coach.jpeg`: coach portrait, unchanged.

Artwork made with built-in image generation. Final edit prompt: “Correct only the central épée. Place it entirely behind the mask and bib. Convex bell toward the blade; hollow underside toward the grip. Preserve the foil, sabre, materials, ivory background and composition.” No external stock asset licenses are needed for the generated artwork.

## Behavior

French remains the default; the shared language switcher supports English and Arabic (RTL). New copy is isolated in `js/landing-translations.js`. Existing pages keep their own stylesheet. The hero has a real image fallback, so it works without JavaScript, if video fails or autoplay is blocked. Reduced-motion and data-saver preferences prevent automatic video loading. A translated play/pause button is provided. Video pauses outside the viewport or when the tab is hidden.

## Recreate video from the final JPEG

```sh
ffmpeg -loop 1 -i assets/fencing-sculpture.jpg -vf "scale=2400:1600,zoompan=z='1.005+0.015*(1-cos(2*PI*on/192))':x='iw/2-iw/zoom/2':y='ih/2-ih/zoom/2':d=1:s=1200x800:fps=24" -t 8 -an -c:v libx264 -crf 26 -preset medium -pix_fmt yuv420p -movflags +faststart assets/fencing-sculpture-loop.mp4
```

Run `node tests/landing.cjs` for static checks and `node tests/landing-motion.cjs` for motion behavior tests. These passed, along with JS syntax checks and FFprobe media validation. Browser visual checks could not run because Chromium's download timed out in the development environment. Preview checks should cover 390px and 1440px widths, all three languages, no-JS, reduced motion, play/pause and failed media. No build dependencies; deploy TEST as a Vercel preview to inspect without changing production. The existing contact form is still a demo, not a functioning booking backend.
