# Reusable Specification: Durable Chest Rewards And Advanced Opening Choreography

Use this document when adding a chest-based reward, achievement, feedback, unlock, or guidance mechanism to an application. It is project-independent and stack-independent. Adapt names and domain payloads to the target project, but preserve the durability, single-shot interaction, animation, accessibility, cleanup, and test invariants below.

The target experience is a deliberate one-activation sequence rather than an image swap:

1. A closed chest arrives and waits for an explicit user action.
2. Pressure builds for 700 ms while the closed chest shakes and compresses.
3. The chest bursts open: its image changes, the stage recoils, a shockwave expands, and deterministic circular/star particles travel outwards.
4. The reward or guidance panel reveals after 120 ms with a spring treatment.
5. The component settles into a stable open state 360 ms later while the particle tail finishes.

The main sequence therefore settles in about 1.2 seconds; the decorative particle tail may continue for about another 430 ms. Reduced-motion users receive the same content and acknowledgement immediately, without the pressure, burst, shockwave, recoil, particles, or spring motion.

## Non-Negotiable Invariants

- Create chest events on the server and persist them before presenting them.
- Apply the actual domain effect when the event is earned, not when an animation finishes. Opening normally acknowledges or reveals an already-earned result.
- Make trigger creation and open acknowledgement idempotent.
- Scope every read and write to the authenticated subject, tenant, and relevant context.
- Keep opened/dismissed rows as history; do not delete them merely to remove them from a pending queue.
- Use one shared presentation controller for contextual markers, inboxes, and queues.
- Require an explicit activation through a real `button`.
- Set the opened guard and disable the button synchronously, before any `await` or timer.
- Keep the visual choreography deterministic. Do not randomize variants, particle paths, delays, or shapes in the browser.
- Never make content visibility or UI settlement depend on acknowledgement latency.
- Cancel timers and invalidate stale callbacks whenever an event is replaced, the presentation closes, or the controller resets.
- Implement and test a true reduced-motion branch in JavaScript as well as reduced-motion CSS.

## Decide The Domain Contract First

Before writing tables or UI, record these project decisions:

- What earns a chest: a domain transition, milestone, scheduled calculation, instructor action, or contextual learning event?
- Is selection deterministic, configured, or random? If randomness is legitimate, perform it on the server, persist the selected outcome, and make retries return the same event.
- What does opening mean: `opened`, `shown`, `claimed`, or merely `acknowledged`?
- When is the domain effect applied? Prefer the same server transaction that creates the event. Do not make a balance, score, entitlement, or important feedback depend on a browser animation completing.
- Can a chest be dismissed unopened, expire, be replayed, or be studied directly without ceremony?
- Does the product need a queue, a contextual marker, a global inbox, or a combination?
- Which variants exist? Start with `gold` and `crystal`; add semantic variants only when users need the distinction.
- Which fields are safe to persist and return? Do not copy sensitive source content into telemetry or event payloads merely to explain a trigger.

A chest can carry scored or non-scored content:

- A scored reward may reveal a value and refresh authoritative totals after acknowledgement.
- An achievement may reveal a badge, evidence link, or unlocked capability.
- Instructional feedback may reveal only a title and safe rendered body. It need not contain points, balances, or loot vocabulary.

## Durable Architecture

Separate three concerns. Names are illustrative, not mandatory.

### Event or delivery rows

Use `chest_events` (or a domain-specific equivalent) as the durable presentation queue and audit history. Store enough of the awarded result to reproduce what the user was meant to see even if configuration later changes.

Recommended fields:

```text
id                     stable opaque identifier
tenant_id              when the application is multi-tenant
subject_id             user/player/participant that owns the event
context_id             optional scenario/course/account/workspace scope
event_key              stable trigger or reward family
event_type             reward/achievement/guidance/unlock/etc.
variant                 stable visual token such as gold or crystal
title                   bounded display title
summary_or_body         bounded plain text, safe Markdown, or validated structured content
value_payload           optional validated domain value; never authoritative client input
trigger_scope           idempotency scope for this occurrence
earned_at               server creation time
presented_at            optional time the UI actually rendered the event
opened_at               optional time explicit opening was acknowledged
dismissed_at            optional time an unopened event was dismissed
expires_at              optional delivery expiry
```

Add an index for the pending query, normally `(tenant_id, subject_id, context_id, opened_at, earned_at, id)`. Add a uniqueness constraint for the real trigger identity, for example `(tenant_id, subject_id, context_id, event_key, trigger_scope)`.

The exact status fields may differ, but distinguish server eligibility/creation from actual presentation when exposure matters. A row created by the server is not automatically a row seen by the user.

### Trigger state

Use a state table only when evaluating later triggers requires progress outside authoritative domain tables. Key it by subject/context and a stable state key. Examples include a milestone already rewarded, the last evaluated period, a cooldown, or a per-session interruption budget.

State keys must describe domain state, not DOM events. Prefer `account:savings-tier-2-awarded` or `tutorial:step-4-guidance-shown` over `button-clicked`.

An empty queue check must never suppress events earned later. Persisted pending rows, not a “checked today” flag, are the queue authority.

### Configuration or definitions

Use configuration rows when administrators need to enable reward families, tune thresholds, select variants, or edit content. Validate types and ranges on write. Snapshot presentation-critical values into the event row so historical events do not silently change.

Do not add configuration or state tables merely to imitate another project. Event persistence and idempotency are mandatory; optional tables must serve the target domain.

## Server Workflow

Perform meaningful action handling atomically where possible:

1. Authorize the subject and context.
2. Validate and apply the domain action.
3. Evaluate deterministic trigger rules against authoritative state.
4. Apply the reward/effect and insert its chest event in the same transaction.
5. Use a unique trigger scope or atomic upsert so retries cannot create duplicates.
6. Return newly created events or let the client refresh the pending queue.

Never let the browser invent a chest, its value, its ownership, or its opened state.

Use API shapes that fit the project. A typical contract is:

```http
GET  /api/chests?status=pending&context={contextId}
POST /api/chests/{eventId}/acknowledge
```

The pending endpoint must use stable ordering such as `earned_at ASC, id ASC`. The acknowledgement endpoint must:

- authenticate and enforce tenant/subject/context ownership;
- require the application's normal CSRF protection for cookie-authenticated writes;
- accept only allow-listed actions such as `presented`, `opened`, or `dismissed`;
- update timestamps once (`COALESCE(opened_at, now)` or an equivalent conditional update);
- return a successful current-state response on a duplicate request;
- never apply the domain reward a second time.

A project-neutral client event can have this shape:

```ts
type ChestEvent = {
  id: string;
  eventKey: string;
  eventType: string;
  variant: string;
  title: string;
  summary?: string;
  body?: string;
  value?: {label: string; display: string};
  links?: Array<{label: string; href: string}>;
  earnedAt: string;
  expiresAt?: string;
};
```

Treat server content as untrusted at the rendering boundary. Use text nodes for plain text and the application's established Markdown sanitizer/link hardening for formatted content. Do not inject server strings as raw HTML.

## Presentation Surfaces

Use one chest controller and one shared dialog, modal, drawer, or route. Contextual surfaces and inboxes should only select an event and open that shared presentation.

- A contextual marker may appear near the meaningful UI area, but it must not steal focus, move a caret, change a selection, submit a form, or reflow critical controls.
- Defer unsolicited markers while a focus-critical editor, destructive confirmation, blocking modal, drag operation, or similar interaction is active.
- A global inbox may show the pending count and open the stable queue.
- A queue must render each event closed and reset the controller before advancing.
- Do not couple generic queue fetching to daily-login semantics. Fetch on relevant entry, after relevant domain actions, on explicit inbox refresh, or according to product policy.
- Keep automatic-delivery budgets, expiry, cooldowns, and suppression policy centralized and testable.

## Advanced Opening Component

### Timing and phases

Use centralized timing constants:

```js
const CHEST_TIMING = Object.freeze({
  charge: 700,
  reveal: 120,
  settle: 360,
  particleTail: 430,
});
```

Model the component as explicit phases:

```text
idle -> closed -> charging -> bursting -> revealed -> open
                    \ reduced motion: closed -> revealed -> open
```

`is-charging`, `is-bursting`, and `is-open` are visual projection classes, not the source of truth. Keep the event, phase/open guard, animation token, timer registry, and acknowledgement state in the controller/component state.

### Minimum DOM

Adapt element and class names to the local system, but retain this semantic structure:

```html
<section class="chest-presentation" aria-labelledby="chest-title">
  <div id="chest-stage" class="chest-stage" data-variant="gold">
    <div class="chest-particles" aria-hidden="true">
      <span></span><span class="chest-star"></span>
      <span></span><span class="chest-star"></span>
      <span></span><span class="chest-star"></span>
      <span></span><span class="chest-star"></span>
      <span></span><span class="chest-star"></span>
      <span></span><span class="chest-star"></span>
      <span></span><span class="chest-star"></span>
    </div>
    <button id="chest-open" class="chest-button" type="button" aria-label="Open chest">
      <img id="chest-image" src="/assets/chests/chest-closed.png" alt="">
    </button>
  </div>
  <p id="chest-prompt">Open when you are ready.</p>
  <div id="chest-reveal" class="chest-reveal" aria-live="polite" hidden>
    <h2 id="chest-title"></h2>
    <div id="chest-content"></div>
    <p id="chest-save-status" role="status" hidden></p>
    <button id="chest-continue" type="button">Continue</button>
  </div>
</section>
```

Keep particles decorative and non-interactive. The empty `alt` is intentional because the button label conveys the action and the reward content conveys the result.

### Controller sequence

Implement equivalent logic in the target framework:

```js
render(event) {
  this.clearAnimation();
  this.current = event;
  this.phase = "closed";
  this.acknowledgementStarted = false;
  this.stage.dataset.variant = normalizeVariant(event.variant);
  this.stage.classList.remove("is-charging", "is-bursting", "is-open");
  this.stage.removeAttribute("aria-busy");
  this.button.classList.remove("is-charging", "is-bursting", "is-open");
  this.button.disabled = false;
  this.button.setAttribute("aria-label", "Open chest");
  this.image.src = imageFor(null, false);
  this.reveal.hidden = true;
  this.reveal.classList.remove("is-revealed");
}

async open() {
  if (!this.current || this.phase !== "closed") return;

  // These writes must be synchronous and precede every await.
  this.phase = "charging";
  const event = this.current;
  const token = ++this.animationToken;
  const reduced = prefersReducedMotion();
  this.button.disabled = true;
  this.button.setAttribute("aria-label", "Chest is opening");
  this.stage.setAttribute("aria-busy", "true");

  if (!reduced) {
    this.button.classList.add("is-charging");
    this.stage.classList.add("is-charging");
    this.prompt.textContent = "The chest is about to open…";
    if (!await this.delay(CHEST_TIMING.charge, token)) return;
    this.button.classList.remove("is-charging");
    this.stage.classList.remove("is-charging");
    this.button.classList.add("is-bursting");
    this.stage.classList.add("is-bursting");
  }

  if (token !== this.animationToken) return;
  this.phase = "bursting";
  this.image.src = imageFor(event.variant, true);

  // Start one idempotent request without awaiting it. For a strict "shown"
  // metric, move this call to immediately after the reveal becomes visible.
  this.acknowledgementStarted = true;
  void this.acknowledgeOnce(event).catch((error) => {
    if (this.current?.id === event.id) this.showAcknowledgementError(error, event);
  });

  if (!await this.delay(reduced ? 0 : CHEST_TIMING.reveal, token)) return;
  this.phase = "revealed";
  this.reveal.hidden = false;
  this.reveal.classList.add("is-revealed");
  this.prompt.textContent = "Chest opened.";
  announce(`Chest opened: ${event.title}`);

  if (!await this.delay(reduced ? 0 : CHEST_TIMING.settle, token)) return;
  this.phase = "open";
  this.button.classList.remove("is-charging", "is-bursting");
  this.button.classList.add("is-open");
  this.button.setAttribute("aria-label", "Chest opened");
  this.stage.classList.add("is-open");
  this.stage.removeAttribute("aria-busy");

  if (reduced) {
    this.stage.classList.remove("is-charging", "is-bursting");
  } else {
    this.delay(CHEST_TIMING.particleTail, token).then((current) => {
      if (current) this.stage.classList.remove("is-bursting");
    });
  }

}
```

`acknowledgeOnce` must cache or guard the automatic request by event ID for the active presentation. A manual Retry action may issue another idempotent request, but it must not call `open()` or replay the animation. Handle authorization expiry through the application's normal session recovery.

When acknowledgement means “shown,” begin it only after the reveal has been inserted and made visible. A `requestAnimationFrame` boundary is appropriate if the product requires evidence that the browser rendered the content. In either interpretation, do not await the network before continuing the visual timeline.

### Cancellable delays and reset

Raw `setTimeout` calls can mutate the next event after a dialog closes or queue item changes. Use a generation token and a timer registry:

```js
delay(milliseconds, token) {
  if (milliseconds === 0) return Promise.resolve(token === this.animationToken);
  return new Promise((resolve) => {
    const id = window.setTimeout(() => {
      this.timers.delete(id);
      resolve(token === this.animationToken);
    }, milliseconds);
    this.timers.set(id, resolve);
  });
}

clearAnimation() {
  this.animationToken += 1;
  this.timers.forEach((resolve, id) => {
    window.clearTimeout(id);
    resolve(false);
  });
  this.timers.clear();
  this.stage.classList.remove("is-charging", "is-bursting", "is-open");
  this.stage.removeAttribute("aria-busy");
  this.button.classList.remove("is-charging", "is-bursting", "is-open");
  this.button.disabled = true;
  this.button.setAttribute("aria-label", "Open chest");
  this.image.src = imageFor(null, false);
  this.reveal.classList.remove("is-revealed");
  this.reveal.hidden = true;
  this.acknowledgementStarted = false;
  this.phase = "idle";
  this.current = null;
}
```

Call `clearAnimation()` before rendering every event, when leaving the detail view, when the host dialog closes, on component unmount, on sign-out/session reset, and before clearing a queue. An in-flight acknowledgement may finish, but its callback must update only the matching event and must not mutate the newly rendered event.

## CSS Choreography Contract

Implement these effects with project-local names and dimensions. Preserve their relationships even when visual values are tuned:

- Put palette values on the stage as CSS custom properties. `data-variant` changes the primary, secondary, glow, and particle-glow tokens in one place.
- Use `stage::before` for the ambient glow and its 700 ms pressure build.
- Use `stage::after` for the 600 ms expanding shockwave.
- Give the stage a 300 ms recoil during `is-bursting`.
- Give the button a 700 ms accelerating shake/compression during `is-charging`.
- Give the button a 260 ms spring burst during `is-bursting`.
- Stop idle float/arrival animation while charging, bursting, or open.
- Keep the open state stable: no looping or replayed open animation.
- Reveal the content/value panel over 520 ms with a spring easing curve.
- Animate 14 predetermined particles for 900 ms. Alternate circular and star shapes and alternate primary/secondary colors.

Suggested palette tokens:

```css
.chest-stage {
  --chest-primary: #ffe46b;
  --chest-secondary: #b8e445;
  --chest-glow: rgb(255 228 107 / 52%);
  --chest-particle-glow: rgb(255 228 107 / 88%);
  position: relative;
  display: grid;
  min-height: 17rem;
  isolation: isolate;
  place-items: center;
}

.chest-stage[data-variant="crystal"] {
  --chest-primary: #8ee7ff;
  --chest-secondary: #b89cff;
  --chest-glow: rgb(142 231 255 / 50%);
  --chest-particle-glow: rgb(142 231 255 / 88%);
}

.chest-stage::before {
  position: absolute;
  z-index: -1;
  width: 16rem;
  height: 16rem;
  border-radius: 50%;
  background: radial-gradient(circle, var(--chest-glow), transparent 70%);
  content: "";
  filter: blur(2px);
  pointer-events: none;
  animation: chestAmbientPulse 1500ms ease-in-out infinite alternate;
}

.chest-stage::after {
  position: absolute;
  z-index: -1;
  width: 8rem;
  height: 8rem;
  border: .35rem solid var(--chest-primary);
  border-radius: 50%;
  content: "";
  opacity: 0;
  pointer-events: none;
}

.chest-button { animation: chestArrival 600ms cubic-bezier(.2, 1.2, .2, 1); }
.chest-button img { animation: chestFloat 1800ms ease-in-out 650ms infinite alternate; }
.chest-stage.is-charging::before { animation: chestPressureBuild 700ms cubic-bezier(.35, 0, .65, 1) both; }
.chest-stage.is-bursting::after { animation: chestShockwave 600ms ease-out both; }
.chest-stage.is-bursting { animation: chestStageRecoil 300ms ease-out both; }
.chest-button.is-charging { animation: chestPressure 700ms linear both; }
.chest-button.is-bursting { animation: chestBurst 260ms cubic-bezier(.18, 1.35, .35, 1) both; }
.chest-button.is-open { animation: none; cursor: default; transform: none; }
.chest-button.is-charging img,
.chest-button.is-bursting img,
.chest-button.is-open img { animation: none; }
.chest-reveal.is-revealed { animation: chestReveal 520ms cubic-bezier(.18, 1.35, .35, 1) both; }

.chest-particles {
  position: absolute;
  z-index: 2;
  inset: 0;
  overflow: visible;
  pointer-events: none;
}

.chest-particles span {
  position: absolute;
  top: 49%;
  left: 50%;
  width: var(--particle-size, .55rem);
  height: var(--particle-size, .55rem);
  border-radius: 50%;
  background: var(--particle-color, var(--chest-primary));
  box-shadow: 0 0 18px var(--chest-particle-glow);
  opacity: 0;
}

.chest-stage.is-bursting .chest-particles span {
  animation: chestParticleBurst 900ms cubic-bezier(.12, .72, .2, 1) var(--particle-delay, 0ms) both;
}

.chest-particles .chest-star {
  border-radius: 0 !important;
  clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 94%, 50% 72%, 21% 94%, 32% 57%, 2% 35%, 39% 35%);
}

.chest-particles span:nth-child(even) { --particle-color: var(--chest-secondary); }
.chest-particles span:nth-child(1)  { --particle-x: -8.8rem; --particle-y: -4.8rem; --particle-rotation: -130deg; --particle-delay: 0ms; }
.chest-particles span:nth-child(2)  { --particle-x: -5.5rem; --particle-y: -7.8rem; --particle-rotation: 95deg; --particle-delay: 45ms; --particle-size: .85rem; }
.chest-particles span:nth-child(3)  { --particle-x: -1.9rem; --particle-y: -9rem; --particle-rotation: -70deg; --particle-delay: 90ms; }
.chest-particles span:nth-child(4)  { --particle-x: 3.2rem; --particle-y: -8.5rem; --particle-rotation: 140deg; --particle-delay: 20ms; --particle-size: .75rem; }
.chest-particles span:nth-child(5)  { --particle-x: 7.4rem; --particle-y: -6.1rem; --particle-rotation: 210deg; --particle-delay: 75ms; }
.chest-particles span:nth-child(6)  { --particle-x: 9.3rem; --particle-y: -2.1rem; --particle-rotation: -115deg; --particle-delay: 120ms; --particle-size: .9rem; }
.chest-particles span:nth-child(7)  { --particle-x: 8.5rem; --particle-y: 3.3rem; --particle-rotation: 165deg; --particle-delay: 35ms; }
.chest-particles span:nth-child(8)  { --particle-x: 5.1rem; --particle-y: 6.4rem; --particle-rotation: -190deg; --particle-delay: 145ms; --particle-size: .8rem; }
.chest-particles span:nth-child(9)  { --particle-x: 1.6rem; --particle-y: 7.2rem; --particle-rotation: 105deg; --particle-delay: 80ms; }
.chest-particles span:nth-child(10) { --particle-x: -3.6rem; --particle-y: 6.8rem; --particle-rotation: -155deg; --particle-delay: 125ms; --particle-size: .85rem; }
.chest-particles span:nth-child(11) { --particle-x: -7.5rem; --particle-y: 4.6rem; --particle-rotation: 175deg; --particle-delay: 55ms; }
.chest-particles span:nth-child(12) { --particle-x: -9.4rem; --particle-y: .2rem; --particle-rotation: -95deg; --particle-delay: 165ms; --particle-size: .75rem; }
.chest-particles span:nth-child(13) { --particle-x: -5.9rem; --particle-y: -2.5rem; --particle-rotation: 135deg; --particle-delay: 180ms; }
.chest-particles span:nth-child(14) { --particle-x: 6.1rem; --particle-y: .6rem; --particle-rotation: -145deg; --particle-delay: 195ms; --particle-size: .7rem; }
```

Use these keyframe shapes or tuned equivalents that preserve pressure, compression, release, and settlement:

```css
@keyframes chestArrival {
  from { opacity: 0; transform: translateY(4rem) scale(.72) rotate(-5deg); }
  to { opacity: 1; transform: translateY(0) scale(1) rotate(0); }
}

@keyframes chestFloat {
  from { transform: translateY(0) rotate(-.5deg); }
  to { transform: translateY(-.45rem) rotate(.5deg); }
}

@keyframes chestAmbientPulse {
  from { transform: scale(.92); opacity: .75; }
  to { transform: scale(1.08); opacity: 1; }
}

@keyframes chestPressure {
  0% { transform: translate(0, 0) scale(1); }
  14% { transform: translate(-.08rem, 0) rotate(-.5deg) scale(1.002); }
  28% { transform: translate(.1rem, -.02rem) rotate(.7deg) scale(1.008); }
  42% { transform: translate(-.16rem, .03rem) rotate(-1deg) scale(1.015); }
  54% { transform: translate(.2rem, -.04rem) rotate(1.4deg) scale(1.025); }
  65% { transform: translate(-.28rem, .03rem) rotate(-1.8deg) scale(1.035); }
  75% { transform: translate(.34rem, -.06rem) rotate(2.3deg) scale(1.045); }
  84% { transform: translate(-.4rem, .05rem) rotate(-2.8deg) scale(1.055); }
  92% { transform: translate(.35rem, .08rem) rotate(2.2deg) scale(1.02, 1.08); }
  100% { transform: translateY(.18rem) rotate(0) scale(1.08, .9); }
}

@keyframes chestBurst {
  0% { transform: translateY(.18rem) scale(1.08, .9); }
  52% { transform: translateY(-.7rem) scale(1.15, 1.12) rotate(-1.5deg); }
  78% { transform: translateY(.08rem) scale(.97, 1.03) rotate(.7deg); }
  100% { transform: translateY(0) scale(1) rotate(0); }
}

@keyframes chestPressureBuild {
  0% { transform: scale(.92); opacity: .72; filter: blur(3px); }
  70% { transform: scale(1.12); opacity: 1; filter: blur(1px); }
  100% { transform: scale(.82); opacity: 1; filter: blur(0); }
}

@keyframes chestShockwave {
  0% { opacity: .9; transform: scale(.45); }
  100% { opacity: 0; transform: scale(2.25); }
}

@keyframes chestStageRecoil {
  0%, 100% { transform: translateY(0); }
  35% { transform: translateY(.35rem); }
  70% { transform: translateY(-.18rem); }
}

@keyframes chestParticleBurst {
  0% { opacity: 0; transform: translate(-50%, -50%) scale(.2) rotate(0); }
  18%, 72% { opacity: 1; }
  100% { opacity: 0; transform: translate(calc(-50% + var(--particle-x)), calc(-50% + var(--particle-y))) scale(1.3) rotate(var(--particle-rotation)); }
}

@keyframes chestReveal {
  0% { opacity: 0; transform: translateY(1rem) scale(.72); }
  65% { opacity: 1; transform: translateY(-.12rem) scale(1.04); }
  100% { opacity: 1; transform: translateY(0) scale(1); }
}
```

The `::before` and `::after` pseudo-elements also need their base size, background/border, opacity, `pointer-events: none`, and stacking styles. Give the stage a fixed/minimum block size and the images a stable aspect ratio so image swapping and particles do not cause layout shift or escape the dialog unexpectedly.

## Reduced Motion

Check `window.matchMedia("(prefers-reduced-motion: reduce)").matches` at activation time. In that branch:

- do not add charging or bursting classes;
- skip all choreography delays;
- swap to the correct open image immediately;
- reveal and announce the same content immediately;
- enter the same stable `open` phase;
- send exactly one acknowledgement;
- keep all actions and queue navigation available.

Add component-scoped CSS as a defensive layer:

```css
@media (prefers-reduced-motion: reduce) {
  .chest-stage::after,
  .chest-particles { display: none !important; }

  .chest-stage::before,
  .chest-button,
  .chest-button img,
  .chest-reveal.is-revealed {
    animation-duration: .01ms !important;
    animation-iteration-count: 1 !important;
  }
}
```

Do not rely on CSS alone: JavaScript delays would still make a motionless interface wait unnecessarily.

## Accessibility And Responsive Behavior

- Use a native button with a visible focus indicator and a touch target suitable for the target platform.
- Keep its label current: “Open chest,” “Chest is opening,” then “Chest opened.”
- Put `aria-busy="true"` on the stage only during the active opening sequence and remove it on settle and reset.
- Announce the result politely when it is revealed, not for every decorative phase.
- Keep decorative images at `alt=""` and particles at `aria-hidden="true"`.
- Do not use color or chest art as the only indication of reward type or meaning.
- Do not move focus when an unsolicited marker arrives. When a user opens a dialog, follow the host dialog's focus trap and restoration rules.
- Disabling a focused button can drop focus in some browsers. Once the result appears, move focus deliberately to the revealed heading or primary Continue action when needed by the host dialog pattern; test the chosen behavior with a keyboard.
- Size the stage and image responsively. Verify that image, long localized content, error status, and Continue/Next actions do not overlap or create horizontal overflow on small screens.
- Contain the effect inside its presentation surface so it cannot move outside layout, shift the page, or interfere with other components.

## Variants And Assets

Use one closed image plus one open image for each variant. Map stable server tokens to local assets in one function and use a safe default for unknown variants:

```js
function imageFor(variant, opened) {
  if (!opened) return assetUrl("chests/chest-closed.png");
  return variant === "crystal"
    ? assetUrl("chests/chest-open-crystals.png")
    : assetUrl("chests/chest-open-gold.png");
}
```

Normalize naming once (`crystal` versus `crystals`) instead of spreading aliases through CSS and rendering code. Keep the image mapping and `data-variant` mapping consistent.

Host runtime assets locally. Record source, author/license or owner approval, original commit/version where relevant, and any optimization performed. Do not hotlink another project. Copy only the runtime assets needed by the component, not an entire source-art collection. If redistribution cannot be established, create or obtain approved replacement art while retaining the state interface and choreography.

Preload or decode the closed and possible open images before the interaction becomes available. Use consistent intrinsic dimensions/aspect ratios to prevent a flash or layout jump. Losslessly optimize large PNGs only after visual equivalence is verified.

## Failure, Concurrency, And Lifecycle Rules

- Synchronously guard and disable on every activation path: pointer, touch, keyboard, and programmatic `.click()`.
- Make the server the second line of defense with an idempotent update and unique trigger creation.
- If acknowledgement fails, keep the content revealed and the component open. Show a non-blocking status and an explicit retry that does not replay the chest.
- If the session expires, preserve safe UI state while using the application's standard reauthentication/recovery path.
- If an event is revoked, expired, or changed elsewhere, refresh authoritative state without letting an old callback overwrite the new event.
- On close during charging/bursting, resolve cancelled delays as false and stop the old async sequence.
- On queue advance, reset every class, label, busy attribute, reveal state, image, guard, timer, and event-bound callback before enabling the next chest.
- If multiple tabs may act on the same event, accept that one tab may already have acknowledged it; render the server's current state instead of treating that as a fatal error.

## Required Tests

### Persistence, authorization, and API

- A meaningful domain transition creates the expected event exactly once.
- Retrying the domain action or trigger evaluation does not duplicate an event or effect.
- Pending events are returned in stable order and remain discoverable after an earlier empty check.
- Acknowledging one event removes only that event from the pending result.
- Duplicate acknowledgement returns safely and does not duplicate a balance, score, entitlement, counter, or exposure count.
- Another user, tenant, or context cannot read or acknowledge the event.
- Invalid actions and payload fields are rejected; cookie-authenticated writes require CSRF protection.
- Opened/dismissed events remain available to authorized history/audit queries.

### Full-motion browser behavior

Explicitly emulate `prefers-reduced-motion: no-preference`; do not inherit a test suite's reduced-motion default.

- The component begins with the closed image and enabled real button.
- One activation disables the button synchronously, sets busy state, and enters `is-charging`.
- The charging button uses the pressure animation while the image remains closed.
- A second pointer or programmatic activation during charging causes no second acknowledgement.
- After 700 ms the stage/button enter `is-bursting`, the image maps to the correct open variant, the shockwave/recoil run, and the first particle uses the burst animation.
- After another 120 ms the correct title/content/value is visible and the reveal class is active.
- At about 1.2 seconds the stage is `is-open`, no longer busy, the button has the opened label, and next/continue actions are usable.
- After the particle tail, `is-bursting` is absent and the settled visual is stable.
- Exactly one automatic acknowledgement was sent.

### Reduced motion

- Emulate `prefers-reduced-motion: reduce`.
- One activation immediately swaps the correct image, reveals content, and reaches open state.
- No charging or bursting class appears; shockwave and particles are hidden.
- Exactly one acknowledgement is still sent and all actions remain usable.

### Cleanup, failures, and variants

- Close during charging, render a different event, wait beyond every old delay, and prove no old class, image, content, announcement, or callback appears.
- Advance through at least two queued events and prove each begins closed and is acknowledged independently.
- Delay the acknowledgement response beyond the animation and prove the component still settles on time.
- Fail the acknowledgement and prove content remains visible, a non-blocking retry appears, and retry does not replay animation.
- Cover every variant's `data-variant`, open image, semantic label, and palette tokens.
- Verify runtime images decode locally and no request reaches an external reference repository.
- Verify keyboard use, dialog focus restoration, mobile layout, long/localized content, and no horizontal overflow.

Use static screenshots for closed and settled-open states. Screenshot stabilization commonly disables animations, so supplement it with class/timing/animation-name assertions and a manual normal-motion review of the choreography.

## Implementation Order For An Agent

1. Read the target repository's architecture, auth, database, dialog, accessibility, asset, and test conventions.
2. Define earning, acknowledgement, dismissal, expiry, replay, variant, and reward-timing semantics.
3. Add the durable event model, uniqueness scope, indexes, and any justified state/configuration model.
4. Implement server-only trigger creation and atomic domain effects.
5. Implement scoped pending/history reads and idempotent acknowledgement.
6. Add local approved assets and a centralized image/variant mapping.
7. Build one shared controller and semantic DOM/component structure.
8. Implement the normal-motion choreography, cancellable delays, lifecycle reset, and non-blocking acknowledgement.
9. Implement the JavaScript and CSS reduced-motion paths.
10. Connect contextual markers and/or a global inbox to the shared controller without duplicating opening logic.
11. Add API, full-motion, reduced-motion, duplicate-activation, cleanup, slow/failing-network, queue, variant, accessibility, responsive, and visual tests.
12. Run the repository's complete relevant verification suite and inspect animation/screenshots at original resolution.

## Pitfalls To Avoid

- Client-created or client-valued rewards.
- Applying the real reward only after an animation or acknowledgement.
- A check-once flag that hides events earned later.
- Random browser-side variant or particle generation that makes retries and tests inconsistent.
- Reusing one unresolved timer variable while other phase timers remain live.
- Resetting classes without invalidating awaited callbacks.
- Awaiting the network before reveal or settlement.
- Replaying the animation as an acknowledgement retry.
- Marking an event “shown” before it was actually rendered when exposure metrics matter.
- Treating an event ID as authorization.
- Deleting opened rows instead of preserving history.
- Rendering unsafe server HTML.
- Using six generic sparkles or a single `is-open` image swap and calling it animation parity.
- A CSS-only reduced-motion implementation that still waits through JavaScript delays.
- Global reduced-motion overrides when component-scoped rules suffice.
- Hotlinking assets or copying unreviewed source-art collections.

## Reference Lineage

This specification is self-contained; the reference repositories are not required in a target project. The advanced choreography was derived from:

- Ba Ba Bank commit `06f5e0e1ba92e69b7079d2764cd5dad56cf5b50f` (`Enhance reward chest opening animation`): `site/app.js`, `site/customer/index.html`, `site/styles.css`, and `tests/monthly-interest-e2e.spec.js`.
- HAIC Chat's project-independent adaptation: `assets/js/education.js`, `assets/css/app.css`, the education chest markup in `index.php`, and `tests/browser/education-hints.spec.js`.

The Ba Ba Bank implementation demonstrates scored queued rewards. HAIC Chat demonstrates non-scored contextual guidance, safe content reveal, focus-sensitive arrival, generation-token timer cancellation, reduced motion, and stale-callback tests. Reuse the common invariants, not either project's terminology, endpoints, score model, framework, or delivery policy.

## Minimal Reusable Agent Prompt

> Implement a durable chest mechanism using `GAME_CHEST.md`. Adapt naming and payloads to this project's domain. Create server-owned, persisted, idempotent chest events; apply domain effects when earned; add scoped pending/history and idempotent acknowledgement endpoints; and use one shared presentation controller. Implement the complete 700 ms pressure, image-swap/burst/shockwave/recoil/14-particle, 120 ms spring reveal, 360 ms settlement, and 430 ms particle-tail choreography. Guard duplicate activation synchronously, keep acknowledgement off the visual critical path, cancel stale timers with a generation token, provide an immediate reduced-motion path, host approved assets locally, and add the required API and browser tests.
