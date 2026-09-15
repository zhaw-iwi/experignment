const { test, expect } = require("@playwright/test");

const bootstrapCss = require.resolve("bootstrap/dist/css/bootstrap.min.css");
const bootstrapJs = require.resolve("bootstrap/dist/js/bootstrap.bundle.min.js");

const students = {
    below: ["below@students.zhaw.ch", "below1"],
    above: ["above@students.zhaw.ch", "above1"],
    courseB: ["courseb@students.zhaw.ch", "courseb1"],
    missing: ["missing@students.zhaw.ch", "missing1"],
    zero: ["zero@students.zhaw.ch", "zero1"],
};

test.beforeEach(async ({ page }) => {
    await page.route("https://cdn.jsdelivr.net/**", async (route) => {
        const assetPath = route.request().url().endsWith(".css") ? bootstrapCss : bootstrapJs;
        await route.fulfill({ path: assetPath });
    });
});

async function login(page, student, viewport) {
    await page.setViewportSize(viewport);
    await page.goto("/");
    await page.getByLabel("Studierenden-E-Mail", { exact: true }).fill(student[0]);
    await page.getByLabel("Zugangscode", { exact: true }).fill(student[1]);
    await page.getByRole("button", { name: "Anmelden", exact: true }).click();
    await expect(page.locator("#overviewPanel")).toBeVisible();
}

async function metricValue(page, testId) {
    return page.getByTestId(testId).locator(".points-metric-value");
}

async function assertProgress(page, visiblePercentage, clampedPercentage) {
    const progress = page.getByRole("progressbar", { name: "Punktefortschritt", exact: true });
    await expect(progress).toHaveCount(1);
    await expect(progress).toHaveAttribute("aria-valuemin", "0");
    await expect(progress).toHaveAttribute("aria-valuemax", "100");
    await expect(progress).toHaveAttribute("aria-valuenow", String(clampedPercentage));
    await expect(progress).toHaveAttribute("aria-valuetext", new RegExp(`${visiblePercentage} Prozent`));

    const dimensions = await progress.evaluate((track) => {
        const fill = track.querySelector(".points-progress-fill");
        const trackBox = track.getBoundingClientRect();
        const fillBox = fill.getBoundingClientRect();
        return {
            trackWidth: trackBox.width,
            fillWidth: fillBox.width,
            contained: fillBox.right <= trackBox.right + 0.5,
        };
    });
    expect(dimensions.contained).toBe(true);
    expect(dimensions.fillWidth / dimensions.trackWidth).toBeCloseTo(clampedPercentage / 100, 1);
}

async function assertRewardCell(page, experimentName, expectedReward) {
    const row = page.getByRole("row").filter({ hasText: experimentName });
    await expect(row).toHaveCount(1);
    const cells = row.getByRole("cell");
    await expect(cells).toHaveCount(7);
    await expect(cells.nth(4)).toHaveText(expectedReward);
}

test("Course A below target shows fractional progress and experiment rewards", async ({ page }, testInfo) => {
    await login(page, students.below, { width: 1280, height: 900 });

    await expect(page.locator("#studentCreditHeading")).toHaveText("Course A");
    await expect(await metricValue(page, "credit-earned")).toHaveText("5.4");
    await expect(await metricValue(page, "credit-target")).toHaveText("8");
    await expect(await metricValue(page, "credit-percentage")).toHaveText("67.5%");
    await assertProgress(page, "67.5", 67.5);
    await assertRewardCell(page, "Fractional Study", "1.4");
    await assertRewardCell(page, "Eight Point Bonus", "8");

    await page.screenshot({ path: testInfo.outputPath("course-a-below-desktop.png"), fullPage: true });
});

test("Course A above target keeps the true percentage and clamps the bar", async ({ page }, testInfo) => {
    await login(page, students.above, { width: 1280, height: 900 });

    await expect(await metricValue(page, "credit-earned")).toHaveText("9");
    await expect(await metricValue(page, "credit-target")).toHaveText("8");
    await expect(await metricValue(page, "credit-percentage")).toHaveText("112.5%");
    await assertProgress(page, "112.5", 100);

    await page.screenshot({ path: testInfo.outputPath("course-a-above-desktop.png"), fullPage: true });
});

test("Course B uses its independent ten-point target", async ({ page }) => {
    await login(page, students.courseB, { width: 1280, height: 900 });

    await expect(page.locator("#studentCreditHeading")).toHaveText("Course B");
    await expect(await metricValue(page, "credit-earned")).toHaveText("4");
    await expect(await metricValue(page, "credit-target")).toHaveText("10");
    await expect(await metricValue(page, "credit-percentage")).toHaveText("40%");
    await assertProgress(page, "40", 40);
});

test("missing target omits percentage and determinate progress", async ({ page }) => {
    await login(page, students.missing, { width: 1280, height: 900 });

    await expect(page.locator("#studentCreditSummary")).toHaveAttribute("data-points-state", "missing");
    await expect(await metricValue(page, "credit-earned")).toHaveText("1.4");
    await expect(page.getByTestId("credit-target")).toContainText("Punkteziel noch nicht festgelegt");
    await expect(page.getByTestId("credit-percentage")).toHaveCount(0);
    await expect(page.getByRole("progressbar")).toHaveCount(0);
});

test("zero target omits percentage and determinate progress", async ({ page }) => {
    await login(page, students.zero, { width: 1280, height: 900 });

    await expect(page.locator("#studentCreditSummary")).toHaveAttribute("data-points-state", "zero");
    await expect(await metricValue(page, "credit-earned")).toHaveText("1");
    await expect(await metricValue(page, "credit-target")).toHaveText("0");
    await expect(page.getByTestId("credit-percentage")).toHaveCount(0);
    await expect(page.getByRole("progressbar")).toHaveCount(0);
});

test("mobile below-target summary fits the viewport", async ({ page }) => {
    await login(page, students.below, { width: 375, height: 812 });
    await expect(await metricValue(page, "credit-percentage")).toHaveText("67.5%");
    await assertProgress(page, "67.5", 67.5);

    const layout = await page.locator("#studentCreditSummary").evaluate((summary) => {
        const box = summary.getBoundingClientRect();
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
});

test("mobile above-target summary retains true progress text", async ({ page }, testInfo) => {
    await login(page, students.above, { width: 375, height: 812 });
    await expect(await metricValue(page, "credit-percentage")).toHaveText("112.5%");
    await assertProgress(page, "112.5", 100);

    await page.screenshot({ path: testInfo.outputPath("course-a-above-mobile.png"), fullPage: true });
});
