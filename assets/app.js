const STUDENT_EMAIL_KEY = "experiment_student_email_v2";
const PAGE_ALERT_TIMEOUT_MS = 5000;
const messageTimers = new WeakMap();

const state = {
    email: "",
    csrfToken: "",
    overview: null,
    activeExperimentId: null,
    chestPendingCount: 0,
    chestAssetsReady: false,
};

let chestController = null;
let chestModal = null;
let chestAssetPromise = null;
let chestFetchGeneration = 0;

const dom = {
    emailPanel: document.getElementById("emailPanel"),
    emailForm: document.getElementById("emailForm"),
    studentEmail: document.getElementById("studentEmail"),
    studentAccessCode: document.getElementById("studentAccessCode"),
    identifyButton: document.getElementById("identifyButton"),
    entryMessage: document.getElementById("entryMessage"),
    studentSessionControls: document.getElementById("studentSessionControls"),
    overviewPanel: document.getElementById("overviewPanel"),
    overviewMessage: document.getElementById("overviewMessage"),
    studentCreditSummary: document.getElementById("studentCreditSummary"),
    currentEmailBadge: document.getElementById("currentEmailBadge"),
    changeEmailButton: document.getElementById("changeEmailButton"),
    studentChestInbox: document.getElementById("studentChestInbox"),
    studentChestCount: document.getElementById("studentChestCount"),
    studentChestModal: document.getElementById("studentChestModal"),
    studentChestStage: document.getElementById("studentChestStage"),
    studentChestOpenButton: document.getElementById("studentChestOpenButton"),
    studentChestImage: document.getElementById("studentChestImage"),
    studentChestPrompt: document.getElementById("studentChestPrompt"),
    studentChestPosition: document.getElementById("studentChestPosition"),
    studentChestReveal: document.getElementById("studentChestReveal"),
    studentChestRevealTitle: document.getElementById("studentChestRevealTitle"),
    studentChestBody: document.getElementById("studentChestBody"),
    studentChestSaveStatus: document.getElementById("studentChestSaveStatus"),
    studentChestRetry: document.getElementById("studentChestRetry"),
    studentChestContinue: document.getElementById("studentChestContinue"),
    studentChestAnnouncement: document.getElementById("studentChestAnnouncement"),
    experimentRows: document.getElementById("experimentRows"),
    detailPanel: document.getElementById("detailPanel"),
};

document.addEventListener("DOMContentLoaded", () => {
    initializeChestExperience();
    const storedEmail = readStoredEmail();
    if (storedEmail) {
        dom.studentEmail.value = storedEmail;
    }

    restoreStudentSession();

    dom.emailForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const email = dom.studentEmail.value.trim().toLowerCase();
        const accessCode = dom.studentAccessCode.value;
        if (!isStudentEmail(email)) {
            showMessage(dom.entryMessage, "Bitte geben Sie eine gültige Studierenden-E-Mail-Adresse ein.", "warning");
            return;
        }
        if (accessCode.length < 5) {
            showMessage(dom.entryMessage, "Bitte geben Sie Ihren Zugangscode ein.", "warning");
            return;
        }
        await loginStudent(email, accessCode);
    });

    dom.changeEmailButton.addEventListener("click", async () => {
        try {
            await apiRequest("api/student_logout.php", { method: "POST" });
        } catch (error) {
            // Always clear the local view, even if the session already expired.
        }
        resetStudentSession(true);
    });
});

async function restoreStudentSession() {
    try {
        const session = await apiRequest("api/student_session.php", { method: "GET" });
        if (!session.authenticated) {
            resetStudentSession(false);
            return;
        }
        state.email = session.email;
        state.csrfToken = session.csrfToken;
        writeStoredEmail(session.email);
        await loadOverview(false);
    } catch (error) {
        resetStudentSession(false);
    }
}

async function loginStudent(email, accessCode) {
    clearMessage(dom.entryMessage);
    setIdentifyLoading(true);
    try {
        const session = await apiRequest("api/student_login.php", {
            method: "POST",
            body: JSON.stringify({ email, accessCode }),
        });
        state.email = session.email;
        state.csrfToken = session.csrfToken;
        dom.studentAccessCode.value = "";
        writeStoredEmail(session.email);
        await loadOverview(true);
    } catch (error) {
        showMessage(dom.entryMessage, error.message || "Die Anmeldung ist fehlgeschlagen.", "danger");
    } finally {
        setIdentifyLoading(false);
    }
}

function resetStudentSession(clearRememberedEmail) {
    state.email = "";
    state.csrfToken = "";
    state.overview = null;
    state.activeExperimentId = null;
    state.chestPendingCount = 0;
    state.chestAssetsReady = false;
    chestFetchGeneration += 1;
    if (chestController) {
        chestController.setEvents([]);
    }
    if (chestModal) {
        chestModal.hide();
    }
    renderChestInbox();
    if (clearRememberedEmail) {
        clearStoredEmail();
        dom.studentEmail.value = "";
    }
    dom.studentAccessCode.value = "";
    renderSessionControls();
    dom.overviewPanel.classList.add("d-none");
    dom.emailPanel.classList.remove("d-none");
    dom.studentEmail.focus();
}

async function loadOverview(autoOpenChests = false) {
    clearMessage(dom.entryMessage);
    clearMessage(dom.overviewMessage);
    try {
        const overview = await apiRequest("api/student_overview.php", { method: "GET" });
        state.email = overview.email;
        state.overview = overview;
        writeStoredEmail(overview.email);
        renderOverview();
        renderSessionControls();
        dom.emailPanel.classList.add("d-none");
        dom.overviewPanel.classList.remove("d-none");
        try {
            await loadStudentChests(autoOpenChests);
        } catch (error) {
            showMessage(dom.overviewMessage, "Die Belohnungstruhen konnten nicht geladen werden.", "warning");
        }
    } catch (error) {
        if (state.email) {
            showMessage(dom.overviewMessage, error.message || "Die Übersicht konnte nicht geladen werden.", "danger");
        }
    }
}

function renderOverview() {
    dom.experimentRows.innerHTML = "";
    dom.detailPanel.classList.add("d-none");
    dom.detailPanel.innerHTML = "";

    const experiments = state.overview && Array.isArray(state.overview.experiments)
        ? state.overview.experiments
        : [];
    renderCreditSummary(state.overview?.group, state.overview?.credits);

    if (experiments.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");
        cell.colSpan = 7;
        cell.className = "text-secondary py-4";
        cell.textContent = "Aktuell sind keine Experimente für diese E-Mail-Adresse freigegeben.";
        row.appendChild(cell);
        dom.experimentRows.appendChild(row);
        return;
    }

    for (const experiment of experiments) {
        const row = document.createElement("tr");
        row.appendChild(textCell(experiment.name, experimentRowMeta(experiment)));
        row.appendChild(textCell(experiment.condition ? experiment.condition.name : "-", ""));
        row.appendChild(statusCell(experiment.assigned));
        row.appendChild(textCell(formatDateTime(experiment.assignedAt), ""));
        row.appendChild(textCell(StudentPoints.experimentRewardValue(experiment), ""));
        row.appendChild(statusCell(experiment.confirmed));

        const actionCell = document.createElement("td");
        actionCell.className = "text-end";
        const button = document.createElement("button");
        button.type = "button";
        button.className = "btn btn-sm";

        if (experiment.confirmed) {
            button.classList.add("btn-outline-secondary");
            button.disabled = true;
            button.textContent = "Angerechnet";
        } else if (experiment.canViewAccess) {
            button.classList.add("btn-primary");
            button.textContent = "Informationen";
            button.addEventListener("click", () => renderDetail(experiment.id));
        } else if (experiment.canClaim) {
            button.classList.add("btn-primary");
            button.textContent = experiment.canChooseCondition ? "Bedingung wählen" : "Teilnehmen";
            button.addEventListener("click", () => {
                if (experiment.canChooseCondition) {
                    renderConditionChoice(experiment.id);
                } else {
                    claimExperiment(experiment.id, null);
                }
            });
        } else {
            button.classList.add("btn-outline-secondary");
            button.disabled = true;
            button.textContent = experiment.isFull
                ? "Ausgebucht"
                : (experiment.configuredOpen ? "Noch nicht verfügbar" : "Geschlossen");
        }

        actionCell.appendChild(button);
        row.appendChild(actionCell);
        dom.experimentRows.appendChild(row);
    }
}

function initializeChestExperience() {
    if (!window.StudentChests || !window.bootstrap?.Modal) {
        return;
    }

    chestModal = window.bootstrap.Modal.getOrCreateInstance(dom.studentChestModal);
    chestController = new window.StudentChests.ChestController({
        stage: dom.studentChestStage,
        openButton: dom.studentChestOpenButton,
        image: dom.studentChestImage,
        prompt: dom.studentChestPrompt,
        position: dom.studentChestPosition,
        reveal: dom.studentChestReveal,
        title: dom.studentChestRevealTitle,
        body: dom.studentChestBody,
        status: dom.studentChestSaveStatus,
        retryButton: dom.studentChestRetry,
        continueButton: dom.studentChestContinue,
        announcement: dom.studentChestAnnouncement,
    }, {
        acknowledge: (event) => apiRequest("api/open_student_chest.php", {
            method: "POST",
            body: JSON.stringify({ chestId: event.id }),
        }),
        onAcknowledged: () => {
            state.chestPendingCount = Math.max(0, state.chestPendingCount - 1);
            renderChestInbox();
        },
        onQueueChange: () => renderChestInbox(),
        onQueueEmpty: () => chestModal.hide(),
    });

    dom.studentChestInbox.addEventListener("click", () => openChestQueue());
    dom.studentChestModal.addEventListener("shown.bs.modal", () => {
        if (chestController?.phase === "closed") {
            dom.studentChestOpenButton.focus({ preventScroll: true });
        }
    });
    dom.studentChestModal.addEventListener("hidden.bs.modal", () => {
        chestController?.cancelPresentation();
        if (state.email) {
            void loadStudentChests(false)
                .catch(() => {
                    showMessage(dom.overviewMessage, "Die Belohnungstruhen konnten nicht aktualisiert werden.", "warning");
                })
                .finally(() => restoreFocusAfterChest());
        }
    });
}

function restoreFocusAfterChest() {
    const target = state.chestPendingCount > 0 ? dom.studentChestInbox : dom.changeEmailButton;
    target.focus({ preventScroll: true });
}

async function loadStudentChests(autoOpen) {
    if (!chestController || !state.email) {
        return;
    }

    const generation = ++chestFetchGeneration;
    const payload = await apiRequest("api/student_chests.php", { method: "GET" });
    if (generation !== chestFetchGeneration || !state.email) {
        return;
    }

    const events = Array.isArray(payload.events) ? payload.events : [];
    state.chestPendingCount = Number(payload.pendingCount) || 0;
    state.chestAssetsReady = state.chestPendingCount === 0;
    chestController.setEvents(events);
    renderChestInbox();
    if (state.chestPendingCount === 0) {
        return;
    }

    await ensureChestAssets();
    if (generation !== chestFetchGeneration || !state.email) {
        return;
    }
    state.chestAssetsReady = true;
    renderChestInbox();
    if (autoOpen) {
        openChestQueue();
    }
}

function ensureChestAssets() {
    if (!chestAssetPromise) {
        chestAssetPromise = window.StudentChests.preloadChestAssets();
    }
    return chestAssetPromise;
}

function openChestQueue() {
    if (!chestModal || !chestController || !state.chestAssetsReady) {
        return;
    }
    if (chestController.presentFirst()) {
        chestModal.show();
    }
}

function renderChestInbox() {
    const count = Math.max(0, Number(state.chestPendingCount) || 0);
    const visible = state.email !== "" && count > 0;
    dom.studentChestInbox.classList.toggle("d-none", !visible);
    dom.studentChestInbox.disabled = !visible || !state.chestAssetsReady;
    dom.studentChestCount.textContent = String(count);
    dom.studentChestInbox.setAttribute(
        "aria-label",
        visible ? `${window.StudentChests?.pendingCountLabel(count) || `${count} neue Truhen`} öffnen` : "Keine neuen Truhen"
    );
}

function renderCreditSummary(group, credits) {
    const summary = StudentPoints.buildCreditSummary(group?.name, credits);
    const percentageMetric = summary.percentageText === null
        ? ""
        : `
            <div class="points-metric" data-testid="credit-percentage">
                <strong class="points-metric-value">${escapeHtml(summary.percentageText)}</strong>
                <span class="points-metric-label">vom Punkteziel</span>
            </div>
        `;
    const progress = summary.progressWidth === null
        ? `<p class="points-progress-caption mb-0" data-testid="credit-progress-unavailable">${escapeHtml(summary.progressCaption)}</p>`
        : `
            <div
                id="studentCreditProgress"
                class="points-progress-track"
                role="progressbar"
                aria-label="Punktefortschritt"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="${summary.progressWidth}"
                aria-valuetext="${escapeHtml(summary.progressValueText)}"
                data-testid="credit-progress"
            >
                <div class="points-progress-fill" style="width: ${summary.progressWidth}%"></div>
            </div>
            <p class="points-progress-caption mb-0">${escapeHtml(summary.progressCaption)}</p>
        `;

    dom.studentCreditSummary.dataset.pointsState = summary.state;
    dom.studentCreditSummary.innerHTML = `
        <div class="points-summary-header">
            <div>
                <p class="eyebrow mb-1">Punktefortschritt</p>
                <h2 id="studentCreditHeading" class="h5 mb-0">${escapeHtml(summary.courseName)}</h2>
            </div>
        </div>
        <div class="points-metrics" aria-labelledby="studentCreditHeading">
            <div class="points-metric" data-testid="credit-earned">
                <strong class="points-metric-value">${escapeHtml(summary.earnedText)}</strong>
                <span class="points-metric-label">Punkte erreicht</span>
            </div>
            <div class="points-metric" data-testid="credit-target">
                <strong class="points-metric-value">${escapeHtml(summary.targetText)}</strong>
                <span class="points-metric-label">${escapeHtml(summary.targetLabel)}</span>
            </div>
            ${percentageMetric}
        </div>
        <div class="points-progress-wrap">
            ${progress}
        </div>
    `;
}

function experimentRowMeta(experiment) {
    const parts = [];
    if (experiment.description) {
        parts.push(experiment.description);
    }
    if (experiment.opensAt) {
        parts.push(`ab ${formatDateTime(experiment.opensAt)}`);
    }
    if (experiment.closesAt) {
        parts.push(`bis ${formatDateTime(experiment.closesAt)}`);
    }
    return parts.join(" · ");
}

function renderSessionControls() {
    const hasEmail = state.email !== "";
    dom.studentSessionControls.classList.toggle("d-none", !hasEmail);
    dom.currentEmailBadge.textContent = hasEmail ? state.email : "";
}

function renderConditionChoice(experimentId) {
    const experiment = findExperiment(experimentId);
    if (!experiment) {
        return;
    }

    state.activeExperimentId = experimentId;
    dom.detailPanel.innerHTML = "";
    dom.detailPanel.classList.remove("d-none");
    dom.detailPanel.appendChild(detailHeader(experiment, "Bedingung wählen"));

    const group = document.createElement("div");
    group.className = "d-flex flex-wrap gap-2 mt-3";
    for (const condition of experiment.availableConditions || []) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "btn btn-primary";
        button.textContent = condition.name;
        button.addEventListener("click", () => claimExperiment(experiment.id, condition.id));
        group.appendChild(button);
    }
    dom.detailPanel.appendChild(group);
}

function renderDetail(experimentId) {
    const experiment = findExperiment(experimentId);
    if (!experiment) {
        return;
    }

    state.activeExperimentId = experimentId;
    dom.detailPanel.innerHTML = "";
    dom.detailPanel.classList.remove("d-none");
    dom.detailPanel.appendChild(detailHeader(experiment, "Zugangsinformationen"));

    if (!experiment.isOpen) {
        const alert = document.createElement("div");
        alert.className = "alert alert-secondary mb-0";
        alert.textContent = "Dieses Experiment ist geschlossen. Die Zugangsinformationen sind aktuell nicht verfügbar.";
        dom.detailPanel.appendChild(alert);
        return;
    }

    const accessItems = experiment.accessItems || [];
    if (accessItems.length > 0) {
        const grid = document.createElement("div");
        grid.className = "access-grid mt-3";
        for (const item of accessItems) {
            grid.appendChild(accessItemElement(item));
        }
        dom.detailPanel.appendChild(grid);
    }

    if (experiment.requiresTimeSlot) {
        dom.detailPanel.appendChild(slotSection(experiment));
    }

    if (accessItems.length === 0 && !experiment.requiresTimeSlot) {
        const empty = document.createElement("div");
        empty.className = "alert alert-info mt-3 mb-0";
        empty.textContent = "Für dieses Experiment sind keine zusätzlichen Zugangsinformationen hinterlegt.";
        dom.detailPanel.appendChild(empty);
    }
}

function detailHeader(experiment, title) {
    const wrapper = document.createElement("div");
    wrapper.className = "d-flex flex-column flex-md-row justify-content-between gap-2";

    const text = document.createElement("div");
    const eyebrow = document.createElement("p");
    eyebrow.className = "eyebrow mb-1";
    eyebrow.textContent = experiment.condition ? experiment.condition.name : experiment.name;
    const heading = document.createElement("h2");
    heading.className = "h4 mb-0";
    heading.textContent = title;
    text.append(eyebrow, heading);

    const badge = document.createElement("span");
    badge.className = experiment.confirmed ? "badge text-bg-success align-self-start" : "badge text-bg-light align-self-start";
    badge.textContent = experiment.confirmed ? "Angerechnet" : "Noch nicht angerechnet";

    wrapper.append(text, badge);
    return wrapper;
}

function accessItemElement(item) {
    const wrapper = document.createElement("div");
    wrapper.className = "access-item";

    const label = document.createElement("div");
    label.className = "small text-secondary mb-2";
    label.textContent = item.label;
    wrapper.appendChild(label);

    if (item.valueType === "url") {
        const group = document.createElement("div");
        group.className = "d-flex gap-2";
        const link = document.createElement("a");
        link.className = "btn btn-primary flex-grow-1";
        link.href = item.value;
        link.target = "_blank";
        link.rel = "noopener noreferrer";
        link.textContent = "Öffnen";
        const copy = document.createElement("button");
        copy.type = "button";
        copy.className = "btn btn-outline-primary copy-button";
        copy.title = "Link kopieren";
        copy.textContent = "⧉";
        copy.addEventListener("click", () => copyText(item.value));
        group.append(link, copy);
        wrapper.appendChild(group);
    } else {
        const value = document.createElement("div");
        value.className = "access-value";
        value.textContent = item.value;
        wrapper.appendChild(value);
    }

    return wrapper;
}

function slotSection(experiment) {
    const wrapper = document.createElement("section");
    wrapper.className = "mt-4";

    const heading = document.createElement("h3");
    heading.className = "h5";
    heading.textContent = "Zeitslot";
    wrapper.appendChild(heading);

    if (experiment.slotChoice) {
        const chosen = document.createElement("div");
        chosen.className = "access-item";
        chosen.innerHTML = `<div class="small text-secondary mb-2">Gewählter Zeitslot</div><div class="access-value">${escapeHtml(slotLabel(experiment.slotChoice))}</div>`;
        wrapper.appendChild(chosen);
    } else if (experiment.canChooseSlot) {
        const form = document.createElement("form");
        form.className = "d-grid gap-2";
        for (const slot of experiment.timeSlots || []) {
            const option = document.createElement("label");
            option.className = `slot-option ${slot.remainingCapacity <= 0 || !slot.isActive ? "disabled" : ""}`;
            const disabled = slot.remainingCapacity <= 0 || !slot.isActive;
            option.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="slotId" value="${slot.id}" ${disabled ? "disabled" : ""}>
                    <span class="form-check-label">${escapeHtml(slotLabel(slot))}</span>
                </div>
                <div class="small text-secondary mt-1">${slot.remainingCapacity} freie Plätze</div>
            `;
            form.appendChild(option);
        }

        const button = document.createElement("button");
        button.type = "submit";
        button.className = "btn btn-primary mt-2";
        button.textContent = "Zeitslot speichern";
        form.appendChild(button);
        form.addEventListener("submit", (event) => {
            event.preventDefault();
            const selected = form.querySelector("input[name='slotId']:checked");
            if (!selected) {
                showMessage(dom.overviewMessage, "Bitte wählen Sie einen Zeitslot aus.", "warning");
                return;
            }
            chooseSlot(experiment.id, selected.value);
        });
        wrapper.appendChild(form);
    } else {
        const alert = document.createElement("div");
        alert.className = "alert alert-secondary mb-0";
        alert.textContent = "Es ist aktuell keine Zeitslot-Auswahl verfügbar.";
        wrapper.appendChild(alert);
    }

    if (experiment.appointmentText) {
        const appointment = document.createElement("div");
        appointment.className = "access-item mt-3";
        appointment.innerHTML = `<div class="small text-secondary mb-2">Zugewiesene Uhrzeit</div><div class="access-value">${escapeHtml(experiment.appointmentText)}</div>`;
        wrapper.appendChild(appointment);
    }

    return wrapper;
}

async function claimExperiment(experimentId, conditionId) {
    clearMessage(dom.overviewMessage);
    try {
        const payload = await apiRequest("api/claim.php", {
            method: "POST",
            body: JSON.stringify({
                experimentId,
                conditionId,
            }),
        });
        state.overview = payload.overview;
        renderOverview();
        renderDetail(experimentId);
    } catch (error) {
        showMessage(dom.overviewMessage, error.message || "Die Zuweisung konnte nicht gespeichert werden.", "danger");
    }
}

async function chooseSlot(experimentId, slotId) {
    clearMessage(dom.overviewMessage);
    try {
        const payload = await apiRequest("api/choose_slot.php", {
            method: "POST",
            body: JSON.stringify({
                experimentId,
                slotId,
            }),
        });
        state.overview = payload.overview;
        renderOverview();
        renderDetail(experimentId);
    } catch (error) {
        showMessage(dom.overviewMessage, error.message || "Der Zeitslot konnte nicht gespeichert werden.", "danger");
    }
}

function textCell(primary, secondary) {
    const cell = document.createElement("td");
    const main = document.createElement("div");
    main.textContent = primary || "-";
    cell.appendChild(main);
    if (secondary) {
        const sub = document.createElement("div");
        sub.className = "small text-secondary";
        sub.textContent = secondary;
        cell.appendChild(sub);
    }
    return cell;
}

function statusCell(active) {
    const cell = document.createElement("td");
    const dot = document.createElement("span");
    dot.className = `status-dot ${active ? "yes" : "no"}`;
    dot.textContent = active ? "✓" : "–";
    cell.appendChild(dot);
    return cell;
}

function findExperiment(experimentId) {
    return (state.overview.experiments || []).find((experiment) => experiment.id === experimentId) || null;
}

function formatDateTime(value) {
    if (!value) {
        return "-";
    }
    const date = new Date(String(value).replace(" ", "T"));
    if (Number.isNaN(date.getTime())) {
        return value;
    }
    return new Intl.DateTimeFormat("de-CH", {
        dateStyle: "short",
        timeStyle: "short",
    }).format(date);
}

function slotLabel(slot) {
    const parts = [slot.label];
    if (slot.isUndated) {
        parts.push("ohne Termin");
    }
    if (slot.startsAt || slot.endsAt) {
        parts.push([formatDateTime(slot.startsAt), formatDateTime(slot.endsAt)].filter((value) => value !== "-").join(" - "));
    }
    return parts.filter(Boolean).join(" · ");
}

async function copyText(value) {
    try {
        await navigator.clipboard.writeText(value);
        showMessage(dom.overviewMessage, "Der Link wurde in die Zwischenablage kopiert.", "success");
    } catch (error) {
        showMessage(dom.overviewMessage, "Der Link konnte nicht automatisch kopiert werden.", "warning");
    }
}

function setIdentifyLoading(loading) {
    dom.identifyButton.disabled = loading;
    dom.identifyButton.textContent = loading ? "Lädt ..." : "Anmelden";
}

function isStudentEmail(email) {
    return /^[^@\s]+@students\.zhaw\.ch$/i.test(email);
}

function showMessage(target, message, type) {
    clearMessageTimer(target);
    target.className = `alert alert-${type}`;
    target.textContent = message;
    target.classList.remove("d-none");
    messageTimers.set(target, window.setTimeout(() => clearMessage(target), PAGE_ALERT_TIMEOUT_MS));
}

function clearMessage(target) {
    clearMessageTimer(target);
    target.className = "d-none";
    target.textContent = "";
}

function clearMessageTimer(target) {
    const timer = messageTimers.get(target);
    if (timer) {
        window.clearTimeout(timer);
        messageTimers.delete(target);
    }
}

function readStoredEmail() {
    try {
        return localStorage.getItem(STUDENT_EMAIL_KEY) || "";
    } catch (error) {
        return "";
    }
}

function writeStoredEmail(email) {
    try {
        localStorage.setItem(STUDENT_EMAIL_KEY, email);
    } catch (error) {
        // The page still works when browser storage is blocked.
    }
}

function clearStoredEmail() {
    try {
        localStorage.removeItem(STUDENT_EMAIL_KEY);
    } catch (error) {
        // Ignore storage errors.
    }
}

function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

async function apiRequest(url, options) {
    const requestOptions = options || {};
    const headers = {
        "Content-Type": "application/json",
        ...(requestOptions.headers || {}),
    };
    if ((requestOptions.method || "GET").toUpperCase() !== "GET" && state.csrfToken) {
        headers["X-CSRF-Token"] = state.csrfToken;
    }

    const response = await fetch(url, {
        credentials: "same-origin",
        ...requestOptions,
        headers,
    });

    let payload = {};
    try {
        payload = await response.json();
    } catch (error) {
        if (!response.ok) {
            throw new Error("Die Serverantwort konnte nicht gelesen werden.");
        }
    }

    if (!response.ok) {
        if (response.status === 401 && url !== "api/student_login.php") {
            resetStudentSession(false);
        }
        throw new Error(payload.message || "Die Anfrage ist fehlgeschlagen.");
    }

    return payload;
}
