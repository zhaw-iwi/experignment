const path = require("node:path");
const { defineConfig } = require("@playwright/test");

if (!process.env.POINTS_TEST_BASE_URL || !process.env.POINTS_TEST_ARTIFACT_DIR) {
    throw new Error("Run browser tests through tests/browser/run.mjs.");
}

module.exports = defineConfig({
    testDir: __dirname,
    testMatch: "student-points.spec.cjs",
    fullyParallel: false,
    workers: 1,
    retries: 0,
    timeout: 20_000,
    expect: {
        timeout: 5_000,
    },
    reporter: "line",
    outputDir: path.resolve(process.env.POINTS_TEST_ARTIFACT_DIR),
    use: {
        baseURL: process.env.POINTS_TEST_BASE_URL,
        browserName: "chromium",
        locale: "de-CH",
        screenshot: "only-on-failure",
        trace: "retain-on-failure",
    },
});
