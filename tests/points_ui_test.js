"use strict";

const assert = require("node:assert/strict");
const points = require("../assets/points.js");

assert.equal(points.formatCreditValue(1.4), "1.4", "decimal rewards should retain one meaningful decimal place");
assert.equal(points.formatCreditValue(0.25), "0.25", "quarter-point rewards should retain two decimal places");
assert.equal(points.clampProgressPercentage(67.5), 67.5, "below-target progress should keep its actual width");
assert.equal(points.clampProgressPercentage(112.5), 100, "above-target progress width should stop at 100 percent");
assert.equal(points.clampProgressPercentage(-5), 0, "progress width should not become negative");

const belowTarget = points.buildCreditSummary("Kurs A", {
    earned: 5.4,
    maximum: 8,
    percentage: 67.5,
});
assert.equal(belowTarget.state, "determinate");
assert.equal(belowTarget.earnedText, "5.4");
assert.equal(belowTarget.targetText, "8");
assert.equal(belowTarget.percentageText, "67.5%");
assert.equal(belowTarget.progressWidth, 67.5);

const atTarget = points.buildCreditSummary("Kurs A", {
    earned: 8,
    maximum: 8,
    percentage: 100,
});
assert.equal(atTarget.percentageText, "100%");
assert.equal(atTarget.progressWidth, 100);

const aboveTarget = points.buildCreditSummary("Kurs A", {
    earned: 9,
    maximum: 8,
    percentage: 112.5,
});
assert.equal(aboveTarget.percentageText, "112.5%", "visible percentage should remain uncapped");
assert.equal(aboveTarget.progressWidth, 100, "only the visual bar width should be capped");
assert.match(aboveTarget.progressValueText, /112\.5 Prozent/, "accessible value text should retain the true percentage");

const missingTarget = points.buildCreditSummary("Kurs A", {
    earned: 3.25,
    maximum: null,
    percentage: null,
});
assert.equal(missingTarget.state, "missing");
assert.equal(missingTarget.earnedText, "3.25");
assert.equal(missingTarget.percentageText, null);
assert.equal(missingTarget.progressWidth, null);
assert.match(missingTarget.targetLabel, /noch nicht festgelegt/);

const zeroTarget = points.buildCreditSummary("Kurs B", {
    earned: 2,
    maximum: 0,
    percentage: null,
});
assert.equal(zeroTarget.state, "zero");
assert.equal(zeroTarget.targetText, "0");
assert.equal(zeroTarget.percentageText, null);
assert.equal(zeroTarget.progressWidth, null);

assert.equal(
    points.experimentRewardValue({ confirmed: false, rewardCredits: 1.4, creditedReward: null }),
    "1.4",
    "an unconfirmed experiment should display its available reward"
);
assert.equal(
    points.experimentRewardValue({ confirmed: true, rewardCredits: 1.4, creditedReward: 1.4 }),
    "1.4",
    "a confirmed experiment should continue displaying its current credited reward"
);

process.stdout.write("points_ui_test.js: ok\n");
