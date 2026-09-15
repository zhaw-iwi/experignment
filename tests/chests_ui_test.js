"use strict";

const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const chests = require("../assets/chests.js");

function fakeClassList() {
    const values = new Set();
    return {
        add: (...names) => names.forEach((name) => values.add(name)),
        remove: (...names) => names.forEach((name) => values.delete(name)),
        contains: (name) => values.has(name),
    };
}

function fakeElement() {
    const attributes = new Map();
    const listeners = new Map();
    return {
        classList: fakeClassList(),
        dataset: {},
        hidden: false,
        disabled: false,
        src: "",
        textContent: "",
        className: "",
        focused: false,
        addEventListener(type, listener) {
            listeners.set(type, listener);
        },
        setAttribute(name, value) {
            attributes.set(name, String(value));
        },
        removeAttribute(name) {
            attributes.delete(name);
        },
        getAttribute(name) {
            return attributes.get(name) ?? null;
        },
        focus() {
            this.focused = true;
        },
        click() {
            listeners.get("click")?.();
        },
    };
}

function fakeElements() {
    return {
        stage: fakeElement(),
        openButton: fakeElement(),
        image: fakeElement(),
        prompt: fakeElement(),
        position: fakeElement(),
        reveal: fakeElement(),
        title: fakeElement(),
        body: fakeElement(),
        status: fakeElement(),
        retryButton: fakeElement(),
        continueButton: fakeElement(),
        announcement: fakeElement(),
    };
}

function pngInfo(relativePath) {
    const buffer = fs.readFileSync(path.join(__dirname, "..", relativePath));
    assert.equal(buffer.subarray(0, 8).toString("hex"), "89504e470d0a1a0a", `${relativePath} should be a PNG`);
    assert.equal(buffer.subarray(12, 16).toString("ascii"), "IHDR", `${relativePath} should contain IHDR first`);
    return {
        width: buffer.readUInt32BE(16),
        height: buffer.readUInt32BE(20),
        colorType: buffer.readUInt8(25),
    };
}

global.window = {
    setTimeout,
    clearTimeout,
    matchMedia: () => ({ matches: false }),
};

assert.deepEqual(chests.CHEST_TIMING, { charge: 700, reveal: 120, settle: 360, particleTail: 430 });
assert.equal(chests.normalizeVariant("gold"), "gold");
assert.equal(chests.normalizeVariant("unknown"), "gold", "unknown variants should use the safe default");
assert.equal(chests.imageFor("gold", false), "assets/chests/chest-closed.png");
assert.equal(chests.imageFor("gold", true), "assets/chests/chest-open-gold.png");
assert.equal(chests.pendingCountLabel(1), "1 neue Truhe");
assert.equal(chests.pendingCountLabel(3), "3 neue Truhen");

const closedInfo = pngInfo(chests.CHEST_ASSETS.closed);
const openInfo = pngInfo(chests.CHEST_ASSETS.gold);
assert.deepEqual(closedInfo, { width: 1254, height: 1254, colorType: 6 });
assert.deepEqual(openInfo, closedInfo, "closed and open assets should share RGBA dimensions");
for (const source of Object.values(chests.CHEST_ASSETS)) {
    assert.doesNotMatch(source, /^https?:/i, "runtime chest assets must be local");
}

async function flushPromises() {
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
}

function fakeClock() {
    let now = 0;
    let nextId = 0;
    const timers = new Map();
    return {
        setTimeout(callback, milliseconds) {
            const id = ++nextId;
            timers.set(id, { callback, at: now + milliseconds });
            return id;
        },
        clearTimeout(id) {
            timers.delete(id);
        },
        advance(milliseconds) {
            const target = now + milliseconds;
            while (true) {
                const due = [...timers.entries()]
                    .filter(([, timer]) => timer.at <= target)
                    .sort((left, right) => left[1].at - right[1].at || left[0] - right[0])[0];
                if (!due) {
                    break;
                }
                const [id, timer] = due;
                timers.delete(id);
                now = timer.at;
                timer.callback();
            }
            now = target;
        },
    };
}

async function testFullMotionTimeline() {
    const elements = fakeElements();
    const clock = fakeClock();
    const originalSetTimeout = window.setTimeout;
    const originalClearTimeout = window.clearTimeout;
    window.setTimeout = clock.setTimeout;
    window.clearTimeout = clock.clearTimeout;
    let acknowledgementCount = 0;

    try {
        const controller = new chests.ChestController(elements, {
            prefersReducedMotion: () => false,
            acknowledge: async () => {
                acknowledgementCount += 1;
            },
        });
        controller.setEvents([{ id: 5, variant: "gold", experimentName: "Motion Experiment" }]);
        controller.presentFirst();
        const opening = controller.open();

        assert.equal(controller.phase, "charging");
        assert.equal(elements.openButton.disabled, true);
        assert.equal(elements.stage.getAttribute("aria-busy"), "true");
        assert.equal(elements.stage.classList.contains("is-charging"), true);
        assert.equal(elements.image.src, chests.CHEST_ASSETS.closed);

        clock.advance(699);
        await flushPromises();
        assert.equal(controller.phase, "charging");
        clock.advance(1);
        await flushPromises();
        assert.equal(controller.phase, "bursting");
        assert.equal(elements.stage.classList.contains("is-bursting"), true);
        assert.equal(elements.image.src, chests.CHEST_ASSETS.gold);

        clock.advance(119);
        await flushPromises();
        assert.equal(elements.reveal.hidden, true);
        clock.advance(1);
        await flushPromises();
        assert.equal(controller.phase, "revealed");
        assert.equal(elements.reveal.hidden, false);
        assert.equal(acknowledgementCount, 1);

        clock.advance(359);
        await flushPromises();
        assert.equal(controller.phase, "revealed");
        clock.advance(1);
        await flushPromises();
        await opening;
        assert.equal(controller.phase, "open");
        assert.equal(elements.stage.getAttribute("aria-busy"), null);
        assert.equal(elements.stage.classList.contains("is-bursting"), true);

        clock.advance(429);
        await flushPromises();
        assert.equal(elements.stage.classList.contains("is-bursting"), true);
        clock.advance(1);
        await flushPromises();
        assert.equal(elements.stage.classList.contains("is-bursting"), false);
    } finally {
        window.setTimeout = originalSetTimeout;
        window.clearTimeout = originalClearTimeout;
    }
}

async function testReducedMotionAndQueue() {
    const elements = fakeElements();
    let acknowledgementCount = 0;
    const controller = new chests.ChestController(elements, {
        prefersReducedMotion: () => true,
        acknowledge: async (event) => {
            acknowledgementCount += 1;
            return { event: { ...event, openedAt: "2026-09-15 12:00:00" } };
        },
    });
    controller.setEvents([
        { id: 10, variant: "gold", experimentName: "Erstes Experiment" },
        { id: 11, variant: "gold", experimentName: "Zweites Experiment" },
    ]);
    assert.equal(controller.presentFirst(), true);
    const firstOpen = controller.open();
    const duplicateOpen = controller.open();
    await Promise.all([firstOpen, duplicateOpen]);
    await flushPromises();

    assert.equal(controller.phase, "open");
    assert.equal(elements.image.src, chests.CHEST_ASSETS.gold);
    assert.equal(elements.reveal.hidden, false);
    assert.equal(elements.title.textContent, "Teilnahme angerechnet!");
    assert.match(elements.body.textContent, /Erstes Experiment/);
    assert.equal(elements.stage.classList.contains("is-charging"), false);
    assert.equal(elements.stage.classList.contains("is-bursting"), false);
    assert.equal(elements.stage.getAttribute("aria-busy"), null);
    assert.equal(acknowledgementCount, 1, "duplicate activation should acknowledge once");
    assert.equal(elements.continueButton.focused, true, "the primary next action should receive focus after settlement");

    await controller.continue();
    assert.equal(controller.phase, "closed");
    assert.equal(controller.current.id, 11);
    assert.equal(elements.image.src, chests.CHEST_ASSETS.closed);
    assert.equal(elements.reveal.hidden, true);
}

async function testFailureRetryWithoutReplay() {
    const elements = fakeElements();
    let acknowledgementCount = 0;
    const controller = new chests.ChestController(elements, {
        prefersReducedMotion: () => true,
        acknowledge: async (event) => {
            acknowledgementCount += 1;
            if (acknowledgementCount === 1) {
                throw new Error("offline");
            }
            return { event };
        },
    });
    controller.setEvents([{ id: 20, variant: "gold", experimentName: "Retry Experiment" }]);
    controller.presentFirst();
    await controller.open();
    await flushPromises();

    assert.equal(controller.phase, "open");
    assert.equal(elements.retryButton.hidden, false);
    assert.match(elements.status.textContent, /nicht gespeichert/);
    assert.equal(elements.image.src, chests.CHEST_ASSETS.gold);

    await controller.retryAcknowledgement();
    assert.equal(acknowledgementCount, 2);
    assert.equal(controller.phase, "open", "retry should not replay the animation");
    assert.equal(elements.image.src, chests.CHEST_ASSETS.gold);
    assert.equal(elements.stage.classList.contains("is-charging"), false);
}

async function testCancellationInvalidatesOldSequence() {
    const elements = fakeElements();
    let acknowledgementCount = 0;
    const controller = new chests.ChestController(elements, {
        prefersReducedMotion: () => false,
        acknowledge: async () => {
            acknowledgementCount += 1;
        },
    });
    controller.setEvents([{ id: 30, variant: "gold", experimentName: "Old" }]);
    controller.presentFirst();
    const oldSequence = controller.open();
    controller.cancelPresentation();
    controller.setEvents([{ id: 31, variant: "gold", experimentName: "New" }]);
    controller.presentFirst();
    await oldSequence;

    assert.equal(controller.current.id, 31);
    assert.equal(controller.phase, "closed");
    assert.equal(elements.image.src, chests.CHEST_ASSETS.closed);
    assert.equal(elements.reveal.hidden, true);
    assert.equal(acknowledgementCount, 0, "cancelled sequence must not acknowledge an old event");
}

async function testLateAcknowledgementCannotMutateReplacement() {
    const elements = fakeElements();
    let resolveAcknowledgement;
    let acknowledgedCount = 0;
    const controller = new chests.ChestController(elements, {
        prefersReducedMotion: () => true,
        acknowledge: () => new Promise((resolve) => {
            resolveAcknowledgement = resolve;
        }),
        onAcknowledged: () => {
            acknowledgedCount += 1;
        },
    });
    controller.setEvents([{ id: 40, variant: "gold", experimentName: "Old" }]);
    controller.presentFirst();
    await controller.open();
    await flushPromises();

    controller.cancelPresentation();
    controller.setEvents([{ id: 41, variant: "gold", experimentName: "Replacement" }]);
    controller.presentFirst();
    resolveAcknowledgement({ event: { id: 40 } });
    await flushPromises();

    assert.equal(controller.current.id, 41);
    assert.equal(controller.phase, "closed");
    assert.equal(controller.acknowledgementSucceeded, false);
    assert.equal(acknowledgedCount, 0, "a late response must not update the replacement presentation");
    assert.equal(elements.image.src, chests.CHEST_ASSETS.closed);
    assert.equal(elements.reveal.hidden, true);
}

(async () => {
    await testFullMotionTimeline();
    await testReducedMotionAndQueue();
    await testFailureRetryWithoutReplay();
    await testCancellationInvalidatesOldSequence();
    await testLateAcknowledgementCannotMutateReplacement();
    process.stdout.write("chests_ui_test.js: ok\n");
})().catch((error) => {
    process.stderr.write(`${error.stack || error.message}\n`);
    process.exit(1);
});
