const { test, expect } = require("@playwright/test");

const bootstrapCss = require.resolve("bootstrap/dist/css/bootstrap.min.css");
const bootstrapJs = require.resolve("bootstrap/dist/js/bootstrap.bundle.min.js");

const students = {
    none: ["below@students.zhaw.ch", "below1"],
    full: ["chestfull@students.zhaw.ch", "full1"],
    multi: ["chestmulti@students.zhaw.ch", "multi1"],
    reduced: ["chestreduced@students.zhaw.ch", "reduce1"],
    cleanup: ["chestcleanup@students.zhaw.ch", "clean1"],
    slow: ["chestslow@students.zhaw.ch", "slow1"],
    retry: ["chestretry@students.zhaw.ch", "retry1"],
    restore: ["chestrestore@students.zhaw.ch", "restore1"],
    tabs: ["chesttabs@students.zhaw.ch", "tabs1"],
};

async function installLocalBootstrap(page) {
    await page.route("https://cdn.jsdelivr.net/**", async (route) => {
        const assetPath = route.request().url().endsWith(".css") ? bootstrapCss : bootstrapJs;
        await route.fulfill({ path: assetPath });
    });
}

test.beforeEach(async ({ page }) => {
    await installLocalBootstrap(page);
});

async function login(page, student, options = {}) {
    await page.setViewportSize(options.viewport || { width: 1280, height: 900 });
    await page.emulateMedia({ reducedMotion: options.reducedMotion || "no-preference" });
    await page.goto("/");
    await page.getByLabel("Studierenden-E-Mail", { exact: true }).fill(student[0]);
    await page.getByLabel("Zugangscode", { exact: true }).fill(student[1]);
    await page.getByRole("button", { name: "Anmelden", exact: true }).click();
    await expect(page.locator("#overviewPanel")).toBeVisible();
}

function chest(page) {
    return {
        modal: page.locator("#studentChestModal"),
        stage: page.locator("#studentChestStage"),
        button: page.locator("#studentChestOpenButton"),
        image: page.locator("#studentChestImage"),
        position: page.locator("#studentChestPosition"),
        reveal: page.locator("#studentChestReveal"),
        title: page.locator("#studentChestRevealTitle"),
        body: page.locator("#studentChestBody"),
        status: page.locator("#studentChestSaveStatus"),
        retry: page.locator("#studentChestRetry"),
        continue: page.locator("#studentChestContinue"),
        announcement: page.locator("#studentChestAnnouncement"),
        inbox: page.locator("#studentChestInbox"),
        count: page.locator("#studentChestCount"),
    };
}

async function expectClosedChest(parts, count) {
    await expect(parts.modal).toBeVisible();
    await expect(parts.button).toBeEnabled();
    await expect(parts.button).toHaveAttribute("aria-label", "Truhe öffnen");
    await expect(parts.image).toHaveAttribute("src", "assets/chests/chest-closed.png");
    await expect(parts.reveal).toBeHidden();
    await expect(parts.stage).not.toHaveClass(/is-charging|is-bursting|is-open/);
    await expect(parts.count).toHaveText(String(count));
}

test("no pending chest leaves the student overview uninterrupted", async ({ page }) => {
    await login(page, students.none);
    const parts = chest(page);

    await expect(parts.modal).toBeHidden();
    await expect(parts.inbox).toBeHidden();
    await expect(page.locator("#studentCreditSummary")).toBeVisible();
});

test("full motion follows the deterministic timeline and acknowledges only once", async ({ page }, testInfo) => {
    const acknowledgementIds = [];
    const remoteImageRequests = [];
    page.on("request", (request) => {
        if (request.resourceType() === "image" && !request.url().startsWith(testInfo.project.use.baseURL)) {
            remoteImageRequests.push(request.url());
        }
    });
    await page.route("**/api/open_student_chest.php", async (route) => {
        acknowledgementIds.push(route.request().postDataJSON().chestId);
        await route.continue();
    });

    await login(page, students.full, { reducedMotion: "no-preference" });
    const parts = chest(page);
    await expectClosedChest(parts, 1);
    await expect(parts.stage).toHaveAttribute("data-variant", "gold");
    const goldPalette = await parts.stage.evaluate((stage) => {
        const styles = getComputedStyle(stage);
        return {
            primary: styles.getPropertyValue("--chest-primary").trim(),
            secondary: styles.getPropertyValue("--chest-secondary").trim(),
            glow: styles.getPropertyValue("--chest-glow").trim(),
            particleGlow: styles.getPropertyValue("--chest-particle-glow").trim(),
        };
    });
    expect(goldPalette).toEqual({
        primary: "#ffe46b",
        secondary: "#77b7ff",
        glow: "rgb(255 228 107 / 52%)",
        particleGlow: "rgb(255 228 107 / 88%)",
    });
    await parts.modal.locator(".chest-modal-content").screenshot({
        path: testInfo.outputPath("chest-closed-desktop.png"),
        animations: "disabled",
    });

    await page.evaluate(() => {
        const stage = document.getElementById("studentChestStage");
        const reveal = document.getElementById("studentChestReveal");
        const image = document.getElementById("studentChestImage");
        window.__chestTimelineStart = performance.now();
        window.__chestTimeline = [];
        const record = () => window.__chestTimeline.push({
            time: performance.now() - window.__chestTimelineStart,
            stageClass: stage.className,
            revealHidden: reveal.hidden,
            image: image.getAttribute("src"),
        });
        window.__chestTimelineObserver = new MutationObserver(record);
        window.__chestTimelineObserver.observe(document.querySelector(".chest-presentation"), {
            attributes: true,
            subtree: true,
            attributeFilter: ["class", "hidden", "src", "aria-busy"],
        });
        record();
    });

    await parts.button.press("Enter");
    await page.evaluate(() => document.getElementById("studentChestOpenButton").click());
    await expect(parts.button).toBeDisabled();
    await expect(parts.button).toHaveAttribute("aria-label", "Truhe wird geöffnet");
    await expect(parts.stage).toHaveAttribute("aria-busy", "true");
    await expect(parts.stage).toHaveClass(/is-charging/);
    await expect(parts.image).toHaveAttribute("src", "assets/chests/chest-closed.png");

    const chargingAnimations = await page.evaluate(() => ({
        button: getComputedStyle(document.getElementById("studentChestOpenButton")).animationName,
        pressure: getComputedStyle(document.getElementById("studentChestStage"), "::before").animationName,
    }));
    expect(chargingAnimations).toEqual({ button: "chestPressure", pressure: "chestPressureBuild" });

    await expect(parts.stage).toHaveClass(/is-bursting/);
    await expect(parts.image).toHaveAttribute("src", "assets/chests/chest-open-gold.png");
    const burstAnimations = await page.evaluate(() => ({
        button: getComputedStyle(document.getElementById("studentChestOpenButton")).animationName,
        shockwave: getComputedStyle(document.getElementById("studentChestStage"), "::after").animationName,
        recoil: getComputedStyle(document.getElementById("studentChestStage")).animationName,
        particle: getComputedStyle(document.querySelector(".chest-particles span")).animationName,
        particleCount: document.querySelectorAll(".chest-particles span").length,
    }));
    expect(burstAnimations).toEqual({
        button: "chestBurst",
        shockwave: "chestShockwave",
        recoil: "chestStageRecoil",
        particle: "chestParticleBurst",
        particleCount: 14,
    });

    await expect(parts.reveal).toBeVisible();
    await expect(parts.title).toHaveText("Teilnahme angerechnet!");
    await expect(parts.body).toContainText("One Point Study");
    await expect(parts.announcement).toContainText("One Point Study");
    await expect(parts.stage).toHaveClass(/is-open/);
    await expect(parts.stage).not.toHaveAttribute("aria-busy", "true");
    await expect(parts.button).toHaveAttribute("aria-label", "Truhe geöffnet");
    await expect(parts.continue).toBeEnabled();
    await expect(parts.continue).toBeFocused();
    await expect(parts.stage).not.toHaveClass(/is-bursting/);
    await expect.poll(() => acknowledgementIds.length).toBe(1);

    const timeline = await page.evaluate(() => {
        window.__chestTimelineObserver.disconnect();
        return window.__chestTimeline;
    });
    const charging = timeline.find((entry) => entry.stageClass.includes("is-charging"));
    const bursting = timeline.find((entry) => entry.stageClass.includes("is-bursting"));
    const revealed = timeline.find((entry) => !entry.revealHidden);
    const opened = timeline.find((entry) => entry.stageClass.includes("is-open"));
    const cleaned = timeline.find((entry) => (
        entry.stageClass.includes("is-open")
        && !entry.stageClass.includes("is-bursting")
        && entry.time > opened.time
    ));
    expect(charging.time).toBeLessThan(250);
    expect(bursting.time).toBeGreaterThanOrEqual(600);
    expect(bursting.time).toBeLessThan(1300);
    expect(revealed.time - bursting.time).toBeGreaterThanOrEqual(80);
    expect(revealed.time - bursting.time).toBeLessThan(500);
    expect(opened.time - revealed.time).toBeGreaterThanOrEqual(280);
    expect(opened.time - revealed.time).toBeLessThan(800);
    expect(cleaned.time - opened.time).toBeGreaterThanOrEqual(350);
    expect(cleaned.time - opened.time).toBeLessThan(1000);

    const decodedAsset = await parts.image.evaluate(async (image) => {
        await image.decode();
        return { width: image.naturalWidth, height: image.naturalHeight, source: image.currentSrc };
    });
    expect(decodedAsset.width).toBe(1254);
    expect(decodedAsset.height).toBe(1254);
    expect(new URL(decodedAsset.source).pathname).toBe("/assets/chests/chest-open-gold.png");
    expect(remoteImageRequests).toEqual([]);

    await parts.modal.locator(".chest-modal-content").screenshot({
        path: testInfo.outputPath("chest-open-gold-desktop.png"),
        animations: "disabled",
    });
});

test("multiple logged-out approvals open oldest first and reset every queue item", async ({ page }) => {
    const acknowledgementIds = [];
    await page.route("**/api/open_student_chest.php", async (route) => {
        acknowledgementIds.push(route.request().postDataJSON().chestId);
        await route.continue();
    });
    await login(page, students.multi, { reducedMotion: "reduce" });
    const parts = chest(page);

    await expectClosedChest(parts, 2);
    await expect(parts.position).toHaveText("Truhe 1 von 2");
    await parts.button.click();
    await expect(parts.body).toContainText("One Point Study");
    await expect(parts.stage).toHaveClass(/is-open/);
    await parts.continue.click();

    await expectClosedChest(parts, 1);
    await expect(parts.position).toHaveText("Truhe 1 von 1");
    await expect(parts.body).toHaveText("");
    await expect(parts.announcement).toHaveText("");
    await parts.button.click();
    await expect(parts.body).toContainText("Fractional Study");
    await parts.continue.click();

    await expect(parts.modal).toBeHidden();
    await expect(parts.inbox).toBeHidden();
    expect(acknowledgementIds).toEqual([202, 203]);
});

test("reduced motion reveals immediately without decorative phases", async ({ page }) => {
    let acknowledgementCount = 0;
    await page.route("**/api/open_student_chest.php", async (route) => {
        acknowledgementCount += 1;
        await route.continue();
    });
    await login(page, students.reduced, { reducedMotion: "reduce" });
    const parts = chest(page);
    await expectClosedChest(parts, 1);
    await page.evaluate(() => {
        const stage = document.getElementById("studentChestStage");
        window.__reducedMotionClasses = [];
        window.__reducedMotionObserver = new MutationObserver(() => {
            window.__reducedMotionClasses.push(stage.className);
        });
        window.__reducedMotionObserver.observe(stage, { attributes: true, attributeFilter: ["class"] });
    });

    const startedAt = Date.now();
    await parts.button.click();
    await expect(parts.stage).toHaveClass(/is-open/);
    await expect(parts.reveal).toBeVisible();
    expect(Date.now() - startedAt).toBeLessThan(500);
    await expect(parts.stage).not.toHaveAttribute("aria-busy", "true");
    await expect(parts.stage).not.toHaveClass(/is-charging|is-bursting/);
    const reducedState = await page.evaluate(() => {
        window.__reducedMotionObserver.disconnect();
        return {
            classes: window.__reducedMotionClasses,
            shockwaveDisplay: getComputedStyle(document.getElementById("studentChestStage"), "::after").display,
            particlesDisplay: getComputedStyle(document.querySelector(".chest-particles")).display,
        };
    });
    expect(reducedState.classes.some((value) => /is-charging|is-bursting/.test(value))).toBe(false);
    expect(reducedState.shockwaveDisplay).toBe("none");
    expect(reducedState.particlesDisplay).toBe("none");
    expect(acknowledgementCount).toBe(1);
});

test("closing during charging cancels every stale callback before a replacement event", async ({ page }) => {
    let pendingReads = 0;
    await page.route("**/api/student_chests.php", async (route) => {
        pendingReads += 1;
        if (pendingReads === 2) {
            await route.fulfill({
                status: 200,
                contentType: "application/json",
                body: JSON.stringify({
                    status: "pending",
                    pendingCount: 1,
                    events: [{
                        id: 206,
                        eventType: "participation_credited",
                        variant: "gold",
                        experimentName: "Four Point Study",
                        earnedAt: "2026-09-15 10:00:00",
                        openedAt: null,
                    }],
                }),
            });
            return;
        }
        await route.continue();
    });
    await login(page, students.cleanup, { reducedMotion: "no-preference" });
    const parts = chest(page);
    await expectClosedChest(parts, 2);
    await parts.button.click();
    await expect(parts.stage).toHaveClass(/is-charging/);
    await parts.modal.getByRole("button", { name: "Truhe schließen" }).click();
    await expect(parts.modal).toBeHidden();
    await expect(parts.inbox).toBeFocused();
    await expect(parts.count).toHaveText("1");

    await parts.inbox.click();
    await expectClosedChest(parts, 1);
    await page.waitForTimeout(1900);
    await expect(parts.stage).not.toHaveClass(/is-charging|is-bursting|is-open/);
    await expect(parts.image).toHaveAttribute("src", "assets/chests/chest-closed.png");
    await expect(parts.body).toHaveText("");
    await expect(parts.announcement).toHaveText("");
    await expect(parts.position).toHaveText("Truhe 1 von 1");
});

test("slow acknowledgement does not delay reveal or visual settlement", async ({ page }) => {
    let acknowledgementCount = 0;
    let releaseAcknowledgement;
    await page.route("**/api/open_student_chest.php", async (route) => {
        acknowledgementCount += 1;
        await new Promise((resolve) => {
            releaseAcknowledgement = resolve;
        });
        await route.continue();
    });
    await login(page, students.slow, { reducedMotion: "no-preference" });
    const parts = chest(page);
    await expectClosedChest(parts, 1);

    await page.evaluate(() => {
        window.__slowChestStartedAt = performance.now();
        document.getElementById("studentChestOpenButton").click();
    });
    await expect(parts.reveal).toBeVisible();
    await expect(parts.stage).toHaveClass(/is-open/);
    const visualElapsed = await page.evaluate(() => performance.now() - window.__slowChestStartedAt);
    expect(visualElapsed).toBeLessThan(2300);
    await expect(parts.continue).toBeEnabled();
    await expect(parts.status).toBeHidden();
    await expect.poll(() => acknowledgementCount).toBe(1);
    expect(typeof releaseAcknowledgement).toBe("function");
    releaseAcknowledgement();
    await expect(parts.inbox).toBeHidden({ timeout: 5000 });
});

test("failed acknowledgement stays readable and mobile retry does not replay", async ({ page }) => {
    let acknowledgementAttempts = 0;
    await page.route("**/api/open_student_chest.php", async (route) => {
        acknowledgementAttempts += 1;
        if (acknowledgementAttempts === 1) {
            await route.fulfill({
                status: 503,
                contentType: "application/json",
                body: JSON.stringify({ error: "TEMPORARY_FAILURE", message: "Temporär nicht verfügbar." }),
            });
            return;
        }
        await route.continue();
    });
    await login(page, students.retry, {
        reducedMotion: "reduce",
        viewport: { width: 375, height: 812 },
    });
    const parts = chest(page);
    await expectClosedChest(parts, 1);
    await parts.button.click();

    await expect(parts.reveal).toBeVisible();
    await expect(parts.body).toContainText("Langzeitstudie zur verständlichen Entscheidungsfindung");
    await expect(parts.image).toHaveAttribute("src", "assets/chests/chest-open-gold.png");
    await expect(parts.status).toContainText("nicht gespeichert");
    await expect(parts.retry).toBeVisible();
    await expect(parts.stage).toHaveClass(/is-open/);
    const layout = await parts.modal.locator(".chest-modal-content").evaluate((modal) => {
        const box = modal.getBoundingClientRect();
        return {
            left: box.left,
            right: box.right,
            viewportWidth: document.documentElement.clientWidth,
            documentWidth: document.documentElement.scrollWidth,
        };
    });
    expect(layout.left).toBeGreaterThanOrEqual(0);
    expect(layout.right).toBeLessThanOrEqual(layout.viewportWidth + 0.5);
    expect(layout.documentWidth).toBeLessThanOrEqual(layout.viewportWidth + 1);

    await parts.retry.click();
    await expect(parts.status).toContainText("Öffnung gespeichert");
    await expect(parts.retry).toBeHidden();
    await expect(parts.stage).toHaveClass(/is-open/);
    await expect(parts.stage).not.toHaveClass(/is-charging|is-bursting/);
    expect(acknowledgementAttempts).toBe(2);
});

test("session restoration shows the count without opening or stealing focus", async ({ page }) => {
    await login(page, students.restore, { reducedMotion: "reduce" });
    const parts = chest(page);
    await expectClosedChest(parts, 1);
    await parts.modal.getByRole("button", { name: "Truhe schließen" }).click();
    await expect(parts.modal).toBeHidden();
    await expect(parts.inbox).toBeFocused();

    await page.reload();
    await expect(page.locator("#overviewPanel")).toBeVisible();
    await expect(parts.count).toHaveText("1");
    await expect(parts.inbox).toBeVisible();
    await expect(parts.modal).toBeHidden();
    const focusWasNotStolen = await page.evaluate(() => (
        !document.getElementById("studentChestModal").contains(document.activeElement)
        && document.activeElement !== document.getElementById("studentChestInbox")
    ));
    expect(focusWasNotStolen).toBe(true);

    await parts.inbox.press("Enter");
    await expect(parts.modal).toBeVisible();
    await expect(parts.button).toBeFocused();
    await page.keyboard.press("Shift+Tab");
    expect(await page.evaluate(() => document.getElementById("studentChestModal").contains(document.activeElement))).toBe(true);
    await page.keyboard.press("Escape");
    await expect(parts.modal).toBeHidden();
    await expect(parts.inbox).toBeFocused();
});

test("a second tab can acknowledge the same event without breaking the first", async ({ context, page }) => {
    await login(page, students.tabs, { reducedMotion: "reduce" });
    const first = chest(page);
    await expectClosedChest(first, 1);

    const secondPage = await context.newPage();
    await installLocalBootstrap(secondPage);
    await secondPage.setViewportSize({ width: 1280, height: 900 });
    await secondPage.emulateMedia({ reducedMotion: "reduce" });
    await secondPage.goto("/");
    await expect(secondPage.locator("#overviewPanel")).toBeVisible();
    const second = chest(secondPage);
    await expect(second.modal).toBeHidden();
    await expect(second.count).toHaveText("1");
    await second.inbox.click();
    await expectClosedChest(second, 1);
    await second.button.click();
    await expect(second.body).toContainText("Eight Point Bonus");
    await expect(second.inbox).toBeHidden();

    await first.button.click();
    await expect(first.body).toContainText("Eight Point Bonus");
    await expect(first.status).toBeHidden();
    await expect(first.retry).toBeHidden();
    await first.continue.click();
    await expect(first.modal).toBeHidden();
    await expect(first.inbox).toBeHidden();
    await secondPage.close();
});
