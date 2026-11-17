# Scale line art

Turn a photo of a real scale into a flat, front-facing SVG drawing that matches the pictures on the Rongta Uzbekistan site. The drawing must be recognisable as that exact model, not a generic scale.

## Inputs

Ask for whatever is missing before drawing:

1. **Model name**, for example `RLS1000/RLS1100A`.
2. **At least one clear photo** of the real device, front or three-quarter view. A second photo from the side or back helps with the printer and the pole.
3. **Where it will be used**: catalog card, model page gallery, or hero. This sets how much detail to draw.

If there is no photo, say so and stop. Do not draw from the model name alone or from memory of what the model looks like.

## Step 1: read the photo

Before drawing, write a short parts list from the photo, and show it to the user:

- **Body**: overall proportions (width to height), flat or tall, rounded or boxy corners.
- **Platter**: width compared with the body, flat or tray, overhang.
- **Display**: on a pole, on the body, hanging, or a touchscreen; one line or several windows; seller side and buyer side.
- **Pole or arm**: position (centre, right, rear), height compared with the body.
- **Keyboard**: rows by columns of keys, where it sits, any separate function keys.
- **Printer**: where the label or receipt comes out (front right, side, top), cover shape.
- **Camera** (AI models): position and what it points at.
- **Anything distinctive**: feet, cable, handle, hook, second screen.

Count what can be counted (key rows and columns, display windows). If a part is not visible in the photo, list it as "not visible" and leave it out of the drawing. Never invent a part.

## Step 2: draw

### Canvas

- `viewBox="0 0 240 210"`, front view, device centred, base resting near y = 180.
- One soft ground shadow: an ellipse under the device, about 12 units tall.
- No perspective, no gradients, no drop shadows, no textures, no text other than display digits.
- Keep the real proportions from the photo. Scale the whole device to fit; do not stretch parts.

### Stroke and shape rules

- Outlines: 2 units, round joins, in the ink colour.
- Small parts (keys, paper, LCD frame): 1 to 1.5 units.
- Free lines (cables, hanger rods): 2.5 units, round caps. Camera arms: 5 units.
- Body corners: radius 6 to 9. Displays and paper: radius 1.5 to 3. Keys: radius 1.5.
- Keys are small squares of 6 units with a 3 unit gap, drawn in the real row and column count when it fits; otherwise keep the real ratio.

### Colour classes

Style only through these classes, never with inline colours, so the same drawing works on light and dark sections:

| Class | Use | Light theme |
|---|---|---|
| `i-body` | housings, pole, screen bezel | white fill, ink outline |
| `i-steel` | platter, pan | light steel fill `#C6CFD7`, ink outline |
| `i-neck` | support under the platter | steel grey fill |
| `i-dark` | LED display window, camera, screen glass | ink fill |
| `i-led` | LED digits on a dark display | brand orange text, display font, bold |
| `i-lcd` + `i-lcdt` | grey LCD and its dark digits | pale green-grey fill |
| `i-key` | keys and round buttons | pale grey fill, thin outline |
| `i-soft` | printer cover, recessed panels | pale grey fill |
| `i-paper` + `i-bar` | label or receipt with barcode lines | white fill, thin ink lines |
| `i-acc` | the produce on the platter | brand orange `#FF6400` |
| `i-leaf` | stem on the produce | thin ink line |
| `i-line`, `i-arm` | cables, rods, camera arm | ink stroke only |
| `i-ui`, `i-uihi` | tiles on a touchscreen; one highlighted | slate fill; highlight in orange |
| `i-aur` | AI recognition corner marks | orange stroke |
| `i-cone` | camera field of view | thin dashed steel line |
| `i-opt` | optional accessory | dashed outline |
| `i-shadow` | ground shadow | 10% ink |

On dark sections the parent has class `night`, which remaps these classes (dark navy bodies, light outlines, cyan highlights). Do not draw a separate dark version; check that the one drawing reads well in both.

### Signature details

Every drawing carries the same small story so the set looks like one family:

- **Produce on the platter**: three overlapping orange circles (radius 8 to 9) with one short curved stem.
- **Weight on the display**: `1.250`. If the model also has unit and total windows, show `1.250  12000  15000`.
- **A label coming out of the printer** when the model prints labels: a small white rectangle with five short barcode lines and one text line. For receipt-only models draw a narrow receipt with three lines. No paper at all for models that do not print.
- **AI models**: a camera, a dashed field-of-view cone to the platter, and two or three orange corner marks around the produce.

### Draw order

Shadow, then parts from back to front: pole and rear display, body, keyboard and printer, paper, platter support, platter, produce, AI marks.

## Step 3: detail crops

For a model page, also give two `viewBox` crops of the same drawing that zoom in on its most telling parts (for example display and printer, or camera and screen). Give each crop a short caption key such as `view.display`, `view.printer`, `view.platter`, `view.camera`, `view.screen`.

## Step 4: check against the photo

Before handing over, compare the drawing with the photo part by part and report:

- every part from the Step 1 list is present and in the right place;
- counts match (display windows, key rows and columns) or the simplification is stated;
- nothing is drawn that is not in the photo;
- proportions are within about 10% of the photo;
- the drawing reads at 90 pixels wide (catalog thumbnail) and on a dark background.

Render the SVG once and look at it if a renderer is available. If not, say the drawing was not viewed.

## Output

1. The parts list from Step 1.
2. The SVG markup: one `<svg class="ill" viewBox="0 0 240 210" role="img" aria-label="...">` with the model name in the label.
3. The two crop viewBoxes with caption keys, if asked for.
4. A one-line note of anything simplified or left out and why.

If the drawing is for the Rongta Uzbekistan Laravel project, the class definitions live in the site stylesheet (see `design/prototype-src/head.html`, section "illustrations"). Outside that project, include the class definitions in a `<style>` block inside the SVG so it stands alone.

## Other hardware

The same rules work for printers, POS terminals and PDAs: same canvas, strokes, classes and ground shadow. Replace the produce with the device's own output (a receipt, a label, a scanned barcode) as the single orange accent.
