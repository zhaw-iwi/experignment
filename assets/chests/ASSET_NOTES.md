# Chest Asset Notes

The two runtime PNG files in this directory were generated specifically for this repository on 2026-09-15 with OpenAI's built-in image-generation tool. They are not copied from either reference project named in `.agents/skills/GAME_CHEST.md` and do not contain third-party logos or source artwork.

Use of the generated output is governed by the repository owner's applicable OpenAI terms. The assets were incorporated for the student-chest feature requested by the repository owner.

## Files

- `chest-closed.png`: original generated closed state, 1254 x 1254 pixels, RGBA with a transparent corner.
- `chest-open-gold.png`: open-state edit of the same design followed by background extraction, 1254 x 1254 pixels, RGBA with a transparent corner.

Both states use matching square canvases, centered frontal composition, wood, navy bands, and gold trim. No runtime optimization was applied because no lossless PNG optimizer is available in the repository toolchain; the originals were retained rather than introducing a visually lossy conversion.

## Prompt Lineage

Closed state prompt summary:

> Create a polished, closed, centered treasure chest for a university web application's game UI. Use friendly 3D illustration, wood, navy metal bands, gold trim, restrained blue accents, genuine transparency, and no text, logos, people, loose coins, particles, or watermark.

Open state prompt summary:

> Open the same chest while preserving its materials, palette, frontal angle, scale, centered square composition, and transparent background. Add only contained golden light; add no text, logos, loose rewards, particles, or watermark.

The first open-state edit incorrectly baked a checkerboard into the pixels and was rejected. A background-extraction edit produced the checked-in transparent version. Both final files were visually inspected at original resolution and their corner alpha was verified as zero.
