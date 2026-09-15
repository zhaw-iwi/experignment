(function exposeStudentPoints(root, factory) {
    const points = factory();
    if (typeof module === "object" && module.exports) {
        module.exports = points;
        return;
    }
    root.StudentPoints = points;
}(typeof globalThis !== "undefined" ? globalThis : this, function createStudentPoints() {
    const numberFormatter = new Intl.NumberFormat("de-CH", { maximumFractionDigits: 2 });

    function finiteNumber(value) {
        if (value === null || value === undefined || value === "") {
            return null;
        }
        const number = Number(value);
        return Number.isFinite(number) ? number : null;
    }

    function formatCreditValue(value) {
        return numberFormatter.format(finiteNumber(value) ?? 0);
    }

    function formatPercentageValue(value) {
        const percentage = finiteNumber(value);
        return percentage === null ? "" : numberFormatter.format(percentage);
    }

    function clampProgressPercentage(value) {
        const percentage = finiteNumber(value);
        if (percentage === null) {
            return null;
        }
        return Math.min(100, Math.max(0, percentage));
    }

    function buildCreditSummary(groupName, credits) {
        const values = credits || {};
        const earned = finiteNumber(values.earned) ?? 0;
        const target = finiteNumber(values.maximum);
        const percentage = finiteNumber(values.percentage);
        const courseName = String(groupName || "Kurs");
        const earnedText = formatCreditValue(earned);

        if (target === null) {
            return {
                state: "missing",
                courseName,
                earnedText,
                targetText: "–",
                targetLabel: "Punkteziel noch nicht festgelegt",
                percentageText: null,
                progressWidth: null,
                progressValueText: null,
                progressCaption: "Für diesen Kurs ist noch kein Punkteziel festgelegt.",
            };
        }

        const targetText = formatCreditValue(target);
        if (target <= 0 || percentage === null) {
            return {
                state: "zero",
                courseName,
                earnedText,
                targetText,
                targetLabel: "Punkteziel",
                percentageText: null,
                progressWidth: null,
                progressValueText: null,
                progressCaption: "Für ein Punkteziel von 0 wird kein Prozentwert angezeigt.",
            };
        }

        const percentageValue = formatPercentageValue(percentage);
        return {
            state: "determinate",
            courseName,
            earnedText,
            targetText,
            targetLabel: "Punkteziel",
            percentageText: `${percentageValue}%`,
            progressWidth: clampProgressPercentage(percentage),
            progressValueText: `${earnedText} von ${targetText} Punkten, ${percentageValue} Prozent`,
            progressCaption: `${earnedText} von ${targetText} Punkten erreicht`,
        };
    }

    function experimentRewardValue(experiment) {
        const row = experiment || {};
        const creditedReward = finiteNumber(row.creditedReward);
        const reward = row.confirmed && creditedReward !== null
            ? creditedReward
            : row.rewardCredits;
        return formatCreditValue(reward);
    }

    return Object.freeze({
        buildCreditSummary,
        clampProgressPercentage,
        experimentRewardValue,
        formatCreditValue,
        formatPercentageValue,
    });
}));
